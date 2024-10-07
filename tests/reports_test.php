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
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

defined('MOODLE_INTERNAL') || die();

namespace mod_openstudio;

use mod_openstudio\local\api\comments;
use mod_openstudio\local\api\reports;

global $CFG;
require_once($CFG->dirroot . '/mod/openstudio/tests/test_utils.php');

/**
 *  Unit tests for mod/openstudio/classes/local/api/reports.php
 *
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reports_test extends \advanced_testcase {

    protected $users;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or slots.
    protected $studiolevelscontext; // Context of generic studio instance with no levels or slots.
    protected $studentroleid;

    /**
     * Sets up our fixtures.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        $this->studentroleid = 5;

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user([
                'email' => 'student1@ouunittest.com',
                'username' => 'student1',
        ]);

        // Enroll our students and teacher (users) in the course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->one->id, $this->course->id, $this->studentroleid);

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
        ]);
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
        $this->studiolevelscontext = \context_module::instance($this->studiolevels->cmid);
    }

    protected function tearDown(): void {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
    }

    public function test_get_total_storage() {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->users->students->one;

        $contentid1 = $this->generator->create_contents([
                'openstudio' => 'OS1',
                'userid' => $user->id,
                'name' => 'Slot1',
                'description' => random_string(),
        ]);

        // The function file_save_draft_area_files still uses $USER.
        $this->setUser($user);

        // Make a comment which has 1 audio attachment + 2 comment text files.
        $filename = 'test1.jpg';
        [$itemid, $link] = test_utils::create_draft_file($filename);
        $commenttext = '<p>Test image link: <img src="' . $link . '" alt="image"/></p>';

        $filename2 = 'test2.jpg';
        [, $link2] = test_utils::create_draft_file($filename2, $itemid);
        $commenttext .= '<p>Another image link: <img src="' . $link2 . '" alt="image"/></p>';
        $commentid = $this->generator->create_comment([
                'contentid' => $contentid1,
                'userid' => $user->id,
                'comment' => $commenttext,
                'filecontext' => $this->studiolevelscontext,
                'commenttextitemid' => $itemid,
                'filepath' => 'mod/openstudio/tests/importfiles/test.mp3',
        ]);

        $commentfiledata = $DB->get_record('files', [
                'itemid' => $commentid,
                'filearea' => comments::COMMENT_TEXT_AREA,
                'filename' => $filename,
                'userid' => $user->id,
        ], 'id, filesize');
        $this->assertNotFalse($commentfiledata);

        $commentfiledata2 = $DB->get_record('files', [
                'itemid' => $commentid,
                'filearea' => comments::COMMENT_TEXT_AREA,
                'filename' => $filename2,
                'userid' => $user->id,
        ], 'id, filesize');
        $this->assertNotFalse($commentfiledata2);

        $commentaudiodata = $DB->get_record('files', [
                'itemid' => $commentid,
                'filearea' => 'contentcomment',
                'filename' => 'test.mp3',
                'userid' => $user->id,
        ], 'id, filesize');
        $this->assertNotFalse($commentaudiodata);

        $storage = reports::get_total_storage($this->studiolevels->id);

        $this->assertObjectHasProperty('storagebyslot', $storage);
        $this->assertObjectHasProperty('storagebyslotversion', $storage);
        $this->assertObjectHasProperty('storagebythumbnail', $storage);
        $this->assertObjectHasProperty('storagebycomment', $storage);

        $expectedtotalstorage = $commentfiledata->filesize
                + $commentfiledata2->filesize
                + $commentaudiodata->filesize;
        $this->assertEquals($expectedtotalstorage, $storage->storagebycomment);
    }
}
