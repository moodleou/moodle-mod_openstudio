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

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

class file_testcase extends \advanced_testcase {

    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or contents.
    protected $totalcontents;
    protected $pinboardcontents;
    protected $contents;
    protected $fs;
    protected $filesizes;
    protected $filenames;
    protected $files;
    protected $users;

    /**
     * Prepares things before this test case is initialised.
     */
    public static function setUpBeforeClass() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/openstudio/lib.php');
    }

    /**
     * Sets up our fixtures.
     */
    protected function setUp() {
        global $CFG, $DB;
        $this->resetAfterTest(true);
        $studentroleid = 5;
        $this->totalcontents = 24; // This is what the scripts below create for ONE CMID.
        $this->pinboardcontents = 3; // This is what the scripts below create for ONE CMID.
        $this->fs = get_file_storage();
        $this->filesizes = array();
        $this->filenames = array('simple' => 'test.rtf', 'nbk' => 'test.nbk', 'html' => '', 'ipynb' => 'test.ipynb');

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create user.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                array('email' => 'student1@ouunittest.com', 'username' => 'student1'));
        $this->users->students->two = $this->getDataGenerator()->create_user(
                array('email' => 'student2@ouunittest.com', 'username' => 'student2'));

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Enroll our student in the course.
        $this->getDataGenerator()->enrol_user($this->users->students->one->id, $this->course->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->students->two->id, $this->course->id,
                $studentroleid, 'manual');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance(array('course' => $this->course->id));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);

        $studioid = $this->studiolevels->id;
        $userid = $this->users->students->one->id;

        $this->setUser($this->users->students->one);
        $this->cm = $DB->get_record('course_modules', array('id' => $this->studiolevels->cmid));
        $this->context = \context_module::instance($this->studiolevels->cmid);
        $usercontext = \context_user::instance($this->users->students->one->id);

        $this->files = array();
        $this->files['rtf'] = (object)array(
                'filesize' => filesize($CFG->dirroot.'/mod/openstudio/tests/importfiles/test.rtf'),
                'filename' => 'test.rtf',
                'draftid' => file_get_unused_draft_itemid()
        );
        $this->files['rtf']->contentdata = array(
                'name' => 'simplefile',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
                'attachments' => $this->files['rtf']->draftid,
                'content' => random_string(),
                'description' => '',
                'ownership' => 0,
                'sid' => 0, // For a new content.
                'fileid' => 0
        );
        $this->files['rtf']->draftrecord = (object)array(
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea'  => 'draft',
                'itemid'    => $this->files['rtf']->draftid,
                'filepath'  => '/',
                'filename'  => 'test.rtf'
        );
        $this->files['rtf']->file = $this->fs->create_file_from_pathname($this->files['rtf']->draftrecord,
                $CFG->dirroot.'/mod/openstudio/tests/importfiles/test.rtf');
        $this->files['rtf']->filedata = array(
                'id' => $this->files['rtf']->draftid,
                'mimetype' => array('type' => 'text/rtf', 'icon' => 'unknown', 'extension' => 'rtf'),
                'file' => (object) array(
                        'filename' => $this->files['rtf']->file->get_filename(),
                        'filepath' => $this->files['rtf']->file->get_filepath(),
                        'fullname' => $this->files['rtf']->file->get_filename(),
                        'size' => $this->files['rtf']->file->get_filesize(),
                        'sortorder' => $this->files['rtf']->file->get_sortorder(),
                        'author' => null,
                        'license' => null,
                        'datemodified' => $this->files['rtf']->file->get_timemodified(),
                        'datecreated' => $this->files['rtf']->file->get_timecreated(),
                        'isref' => false,
                        'mimetype' => $this->files['rtf']->file->get_mimetype(),
                        'type' => 'file',
                        'url' => $CFG->wwwroot.'/draftfile.php/'.$userid.'/user/draft/' .
                                $this->files['rtf']->draftid.'/'.$this->files['rtf']->file->get_filename(),
                        'icon' => 'http://localhost/ou-moodle2/theme/image.php/clean/core/1427877247/f/unknown-24',
                        'thumbnail' => 'http://localhost/ou-moodle2/theme/image.php/clean/core/1427877247/f/unknown-80'
                )
        );

        $this->files['nbk'] = (object)array(
                'filesize' => filesize($CFG->dirroot.'/mod/openstudio/tests/importfiles/test.nbk'),
                'filename' => 'test.nbk',
                'draftid' => file_get_unused_draft_itemid()
        );
        $this->files['nbk']->contentdata = array(
                'name' => 'simplefile',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
                'attachments' => $this->files['nbk']->draftid,
                'content' => random_string(),
                'description' => '',
                'ownership' => 0,
                'sid' => 0, // For a new content.
                'fileid' => 0
        );
        $this->files['nbk']->draftrecord = (object)array(
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea'  => 'draft',
                'itemid'    => $this->files['nbk']->draftid,
                'filepath'  => '/',
                'filename'  => 'test.nbk'
        );
        $this->files['nbk']->file = $this->fs->create_file_from_pathname($this->files['nbk']->draftrecord,
                $CFG->dirroot.'/mod/openstudio/tests/importfiles/test.nbk');
        $this->files['nbk']->filedata = array(
                'id' => $this->files['nbk']->draftid,
                'mimetype' => array('type' => 'text/nbk', 'icon' => 'unknown', 'extension' => 'nbk'),
                'file' => (object) array(
                        'filename' => $this->files['nbk']->file->get_filename(),
                        'filepath' => $this->files['nbk']->file->get_filepath(),
                        'fullname' => $this->files['nbk']->file->get_filename(),
                        'size' => $this->files['nbk']->file->get_filesize(),
                        'sortorder' => $this->files['nbk']->file->get_sortorder(),
                        'author' => null,
                        'license' => null,
                        'datemodified' => $this->files['nbk']->file->get_timemodified(),
                        'datecreated' => $this->files['nbk']->file->get_timecreated(),
                        'isref' => false,
                        'mimetype' => $this->files['nbk']->file->get_mimetype(),
                        'type' => 'file',
                        'url' => $CFG->wwwroot.'/draftfile.php/'.$userid.'/user/draft/' .
                                $this->files['nbk']->draftid.'/'.$this->files['nbk']->file->get_filename(),
                        'icon' => 'http://localhost/ou-moodle2/theme/image.php/clean/core/1427877247/f/unknown-24',
                        'thumbnail' => 'http://localhost/ou-moodle2/theme/image.php/clean/core/1427877247/f/unknown-80'
                )
        );
        $this->files['html'] = (object)array(
                'filesize' => filesize($CFG->dirroot.'/mod/openstudio/tests/importfiles/nbk/test.html')
        );
        $this->files['ipynb'] = (object)array(
                'filesize' => filesize($CFG->dirroot.'/mod/openstudio/tests/importfiles/nbk/test.ipynb')
        );
        $extractedfilenames = $this->files['nbk']->file->extract_to_storage(
                new \zip_packer(),
                $usercontext->id,
                'user',
                'draft',
                $this->files['nbk']->file->get_itemid(),
                $this->files['nbk']->file->get_filepath()
        );
        foreach ($extractedfilenames as $extractedfilename => $success) {
            if (pathinfo($extractedfilename, PATHINFO_EXTENSION) == 'ipynb') {
                $this->files['ipynb']->file = $this->fs->get_file(
                        $usercontext->id,
                        'user',
                        'draft',
                        $this->files['nbk']->file->get_itemid(),
                        $this->files['nbk']->file->get_filepath(),
                        $extractedfilename
                );
                $this->files['ipynb']->file->set_sortorder(1);
            } else {
                $this->files['html']->file = $this->fs->get_file(
                        $usercontext->id,
                        'user',
                        'draft',
                        $this->files['nbk']->file->get_itemid(),
                        $this->files['nbk']->file->get_filepath(),
                        $extractedfilename
                );
                $this->files['html']->file->set_sortorder(2);
            }
        }
        $this->files['ipynb']->filedata = array(
                'id' => $this->files['nbk']->draftid,
                'mimetype' => array('type' => 'document/unknown', 'icon' => 'unknown', 'extension' => 'ipynb'),
                'file' => (object) array(
                        'filename' => $this->files['ipynb']->file->get_filename(),
                        'filepath' => $this->files['ipynb']->file->get_filepath(),
                        'fullname' => $this->files['ipynb']->file->get_filename(),
                        'size' => $this->files['ipynb']->file->get_filesize(),
                        'sortorder' => $this->files['ipynb']->file->get_sortorder(),
                        'author' => null,
                        'license' => null,
                        'datemodified' => $this->files['ipynb']->file->get_timemodified(),
                        'datecreated' => $this->files['ipynb']->file->get_timecreated(),
                        'isref' => false,
                        'mimetype' => $this->files['ipynb']->file->get_mimetype(),
                        'type' => 'file',
                        'url' => $CFG->wwwroot.'/draftfile.php/'.$userid.'/user/draft/' .
                                $this->files['nbk']->draftid.'/'.$this->files['ipynb']->file->get_filename(),
                        'icon' => 'http://localhost/ou-moodle2/theme/image.php/clean/core/1427877247/f/unknown-24',
                        'thumbnail' => 'http://localhost/ou-moodle2/theme/image.php/clean/core/1427877247/f/unknown-80'
                )
        );
        $this->files['nbk']->file->delete();
        $this->files['rtf']->contentdata['id'] = \mod_openstudio\local\api\content::create($studioid, $userid, 0, 0,
                $this->files['rtf']->contentdata, $this->files['rtf']->filedata, $this->context);
        $this->files['nbk']->contentdata['id'] = \mod_openstudio\local\api\content::create($studioid, $userid, 0, 0,
                $this->files['nbk']->contentdata, $this->files['ipynb']->filedata, $this->context);
        $this->files['html']->filename = 'openstudio_' . $this->files['nbk']->contentdata['id'] . '_notebook.html';
        $this->files['ipynb']->filename = 'openstudio_' . $this->files['nbk']->contentdata['id'] . '_notebook.ipynb';
    }

    protected function tearDown() {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
        $this->fs->delete_area_files($this->context->id);
    }

    private function get_header_array() {
        $rawheaders = xdebug_get_headers();
        $headers = array();
        foreach ($rawheaders as $rawheader) {
            list($headername, $headervalue) = explode(':', $rawheader);
            $headers[trim($headername)] = trim($headervalue);
        }
        return $headers;
    }

    /**
     * @runInSeparateProcess
     */
    public function test_openstudio_pluginfile() {

        try {
            header('X-Header-Test: test');
        } catch (\PHPUnit\Framework\Error\Warning $e) {
            $this->markTestSkipped('Cannot run test_studio_pluginfile due to a problem with Moodle\'s PHPUnit integration. ' .
                'To workaround the problem, add ob_start(); at the start of the phpunit_util::bootstrap_moodle_info() '.
                'method. See MDL-49713 for more information.');
        }
        $options = array('dontdie' => true);

        $this->setUser($this->users->students->two);
        ob_start();
        openstudio_pluginfile($this->course, $this->cm, $this->context, 'content',
                              array($this->files['rtf']->contentdata['id'], $this->files['rtf']->filename), 0, $options);
        ob_end_clean();
        if (function_exists('xdebug_get_headers')) {
            $headers = $this->get_header_array();
            list($disposition, $filename) = explode(';', $headers['Content-Disposition']);
            $this->assertEquals('filename="'.$this->files['rtf']->filename.'"', trim($filename));
            $this->assertStringStartsWith('text/rtf', $headers['Content-type']);
            $this->assertEquals($this->files['rtf']->file->get_filesize(), $headers['Content-Length']);
        } else {
            $this->markTestSkipped('Cannot fully run test_studio_pluiginfile without xdebug enabled');
        }

        $options['filename'] = $this->files['html']->filename;
        ob_start();
        openstudio_pluginfile($this->course, $this->cm, $this->context, 'notebook',
                array($this->files['nbk']->contentdata['id'], $this->files['html']->file->get_filename()), 0, $options);
        ob_end_clean();
        if (function_exists('xdebug_get_headers')) {
            $headers = $this->get_header_array();
            list($disposition, $filename) = explode(';', $headers['Content-Disposition']);
            $this->assertEquals('filename="'.$this->files['html']->filename.'"', trim($filename));
            $this->assertStringStartsWith('text/html', $headers['Content-type']);
            $this->assertEquals($this->files['html']->file->get_filesize(), $headers['Content-Length']);
        }

        $options['filename'] = $this->files['ipynb']->filename;
        ob_start();
        openstudio_pluginfile($this->course, $this->cm, $this->context, 'notebook',
                array($this->files['nbk']->contentdata['id'], $this->files['ipynb']->file->get_filename()), 0, $options);
        ob_end_clean();
        if (function_exists('xdebug_get_headers')) {
            $headers = $this->get_header_array();
            list($disposition, $filename) = explode(';', $headers['Content-Disposition']);
            $this->assertEquals('filename="'.$this->files['ipynb']->filename.'"', trim($filename));
            $this->assertEquals($this->files['ipynb']->file->get_filesize(), $headers['Content-Length']);
        }
    }
}

