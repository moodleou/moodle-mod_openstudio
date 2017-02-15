<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for import API
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_openstudio_import_testcase extends \advanced_testcase {

    private $tempdir;
    private $studio;
    private $student;
    private $generator;

    /**
     * Set up a studio instance with a low pinboard limit, and create a temporary directory.
     */
    public function setUp() {
        $this->resetAfterTest(true);
        // Create course.
        $course = $this->getDataGenerator()->create_course();
        $this->student = $this->getDataGenerator()->create_user(['email' => 'student1@ouunittest.com', 'username' => 'student1']);
        $this->getDataGenerator()->enrol_user($this->student->id, $course->id, 5, 'manual');
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');
        $this->studio = $this->generator->create_instance(['course' => $course->id, 'idnumber' => 'OS1', 'pinboard' => 5]);

        $this->tempdir = make_temp_directory('openstudio/importtest');
    }

    /**
     * Clean up temporary directory.
     */
    public function tearDown() {
        mod_openstudio\local\api\filesystem::rrmdir($this->tempdir);
    }

    /**
     * Create a stored_file object from a fixture file.
     *
     * @param $filename
     * @return stored_file
     */
    private function create_storedfile_from_fixture($filename) {
        global $CFG, $USER;
        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        $filedata = (object) [
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => file_get_unused_draft_itemid(),
            'filepath' => '/',
            'filename' => 'importtest.zip',
            'contextid' => $usercontext->id
        ];
        return $fs->create_file_from_pathname($filedata, $CFG->dirroot . '/mod/openstudio/tests/importfiles/' . $filename);
    }

    /**
     * Test reading files from an archive.
     */
    public function test_get_archive_contents() {
        global $USER;
        $user = $USER;
        $this->setUser($this->student->id);
        $importfile = $this->create_storedfile_from_fixture('importtest.zip');
        $files = mod_openstudio\local\api\import::get_archive_contents($importfile);

        $this->assertCount(4, $files['files']); // Zip contains 5 files, but 1 is not permitted.
        $fixturefiles = ['test.pdf', 'test.odt', 'test.ods', 'test.pptx'];
        foreach ($files['files'] as $file) {
            $this->assertTrue(in_array($file->pathname, $fixturefiles));
            unset($fixturefiles[array_search($file->pathname, $fixturefiles)]);
        }
        $this->assertCount(0, $fixturefiles);
        $this->setUser($user);
    }

    /**
     * Test checking the import limit based on the pinboard limit setting.
     */
    public function test_check_import_limit() {
        $underarray = [1, 2, 3, 4];
        $exactarray = [1, 2, 3, 4, 5];
        $overarray = [1, 2, 3, 4, 5, 6];
        $this->assertTrue(mod_openstudio\local\api\import::check_import_limit($this->studio->id, $this->student->id, $underarray));
        $this->assertTrue(mod_openstudio\local\api\import::check_import_limit($this->studio->id, $this->student->id, $exactarray));
        $this->assertFalse(mod_openstudio\local\api\import::check_import_limit($this->studio->id, $this->student->id, $overarray));
    }

    /**
     * Test checking the import limit when the user already has some content.
     */
    public function test_check_import_limit_with_content() {
        $contentdata = [
            'openstudio' => 'OS1',
            'userid' => $this->student->id,
            'name' => random_string(),
            'description' => random_string()
        ];
        $this->generator->create_contents($contentdata);

        // We now have 1 post already, so are allowed 1 less.
        $underarray = [1, 2, 3];
        $exactarray = [1, 2, 3, 4];
        $overarray = [1, 2, 3, 4, 5];
        $this->assertTrue(mod_openstudio\local\api\import::check_import_limit($this->studio->id, $this->student->id, $underarray));
        $this->assertTrue(mod_openstudio\local\api\import::check_import_limit($this->studio->id, $this->student->id, $exactarray));
        $this->assertFalse(mod_openstudio\local\api\import::check_import_limit($this->studio->id, $this->student->id, $overarray));
    }

    /**
     * Test actually creating content from zip.
     */
    public function test_import_files() {
        global $DB, $USER;
        $user = $USER;
        $this->setUser($this->student->id);
        $importfile = $this->create_storedfile_from_fixture('importtest.zip');
        $files = mod_openstudio\local\api\import::get_archive_contents($importfile);
        $cm = get_coursemodule_from_id('openstudio', $this->studio->cmid);
        mod_openstudio\local\api\import::import_files($files, $cm, $this->student->id);

        $contents = $DB->get_records('openstudio_contents', ['openstudioid' => $this->studio->id, 'userid' => $this->student->id]);
        $this->assertCount(4, $contents);
        $fixturefiles = ['test.pdf', 'test.odt', 'test.ods', 'test.pptx'];
        foreach ($contents as $content) {
            $this->assertTrue(in_array($content->name, $fixturefiles));
            unset($fixturefiles[array_search($content->name, $fixturefiles)]);
        }
        $this->assertEmpty($fixturefiles);
        $this->setUser($user);
    }
}

