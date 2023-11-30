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
 * Unit tests for OpenStudio content API.
 *
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

use mod_openstudio\local\api\content;
use mod_openstudio\local\util;

defined('MOODLE_INTERNAL') || die();

class content_test extends \advanced_testcase {

    protected $users;
    protected $file;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or slots.
    protected $totalcontents;
    protected $pinboardslots;
    protected $singleentrydata;
    protected $contentdata;
    protected $groups;
    protected $groupings;
    protected $studio1, $studio2, $studio3, $studio4, $studio5, $studio6, $studio7, $studio8, $studio9, $studio10, $studio11,
            $studio12, $studio13, $studio14, $studio15, $studio16, $studio17, $studio18, $studio19;

    /**
     * Sets up our fixtures.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        $teacherroleid = 3;
        $studentroleid = 5;
        $this->totalcontents = 24; // This is what the scripts below create for ONE CMID.

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

        $this->users->teachers = new \stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                array('email' => 'teacher1@ouunittest.com', 'username' => 'teacher1'));

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Enroll our student in the course.
        $this->getDataGenerator()->enrol_user($this->users->students->one->id, $this->course->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->students->two->id, $this->course->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->one->id, $this->course->id, $teacherroleid, 'manual');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS1'));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
    }

    protected function tearDown(): void {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
    }

    /**
     * Generic function to recursively convert objects to arrays.
     * If an object is not passed, it returns false.
     *
     * @param object $obj Object to convert to arrat.
     * @return mixed Return converted objected or false if error.
     */
    public static function object_to_array($obj) {
        if (is_object($obj)) {
            $new = array();

            $obj = (array) $obj;
            if (is_array($obj)) {
                foreach ($obj as $key => $val) {
                    $new[$key] = static::object_to_array($val);
                }
            } else {
                $new = $obj;
            }

            return $new;
        }

        return false;
    }

    /**
     * Tests the \mod_openstudio\local\api\content::create() function in the content api.
     */
    public function test_create() {
        $this->resetAfterTest(true);

        // Create contents for all level data.
        $contents = array();
        $contentcount = 0;
        foreach ($this->studiolevels->leveldata['contentslevels'] as $activitylevels) {
            foreach ($activitylevels as $contentlevels) {
                foreach ($contentlevels as $contentlevelid) {
                    $contentcount++;
                    $data = array(
                            'name' => 'YouTube URL' . random_string(),
                            'attachments' => '',
                            'embedcode' => '',
                            'weblink' => 'http://www.open.ac.uk/',
                            'urltitle' => 'Vesica Timeline',
                            'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
                            'description' => 'YouTube link',
                            'tags' => array(random_string(), random_string(), random_string()),
                            'ownership' => 0,
                            'sid' => 0 // For a new content.
                    );
                    $contents[$contentlevelid][$contentcount] = \mod_openstudio\local\api\content::create(
                            $this->studiolevels->id,  $this->users->students->one->id,
                            3, $contentlevelid, $data); // Level 3 is for contents.
                    $this->assertGreaterThan(0, $contents[$contentlevelid][$contentcount],
                            'Slot creation failed - no contentid returned.');
                }
            }
        }
        // Count total contents created - should be 24 (based on our leveldata).
        $this->assertEquals($this->totalcontents, $contentcount);
    }

    /**
     * Tess the function to retrieve a single content record from the content api.
     */
    public function test_get_record() {
        $this->resetAfterTest(true);

        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $this->contentdata->id, 'Slot creation failed - no contentid returned.');
        $this->assertNotEquals(false, $this->contentdata);
        $this->assertEquals($this->contentdata->name, $this->singleentrydata['name']);
        $this->assertEquals($this->contentdata->description, $this->singleentrydata['description']);
        $this->assertEquals($this->contentdata->visibility, $this->singleentrydata['visibility']);
        $this->assertEquals($this->contentdata->userid,  $this->users->students->one->id);
        $this->assertEquals($this->contentdata->urltitle,  'Vesica Timeline');
        // A YouTube embed URL will not have a title. That's what our data passes.
    }

    /**
     * Tests the function that empties a content.
     */
    public function test_empty_content() {
        $this->resetAfterTest(true);

        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->setUser($this->users->students->one);
        $emptyresult = \mod_openstudio\local\api\content::empty_content($this->users->students->one->id, $this->contentdata->id);
        $this->assertNotEquals(false, $emptyresult);
    }

    /**
     * Tests the function that deletes a content.
     */
    public function test_delete() {
        $this->resetAfterTest(true);

        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->setUser($this->users->students->one);
        $deleteresult = \mod_openstudio\local\api\content::delete($this->users->students->one->id, $this->contentdata->id);
        $this->assertNotEquals(false, $deleteresult);
    }

    /**
     * Tests creation of a thumbnail for content that uses an uploaded image.
     */
    public function test_create_thumbnail() {
        global $CFG;
        $this->resetAfterTest(true);

        $class = new \ReflectionClass('\mod_openstudio\local\api\content');
        $method = $class->getMethod('create_thumbnail');
        $method->setAccessible(true);

        // Local file to upload to server to simulate file upload.
        $filepath = $CFG->dirroot . '/mod/openstudio/tests/importfiles/test1.jpg';

        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->file = $this->generator->generate_file_data($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->setUser($this->users->students->one);
        $context = \context_module::instance($this->studiolevels->cmid);
        $this->file->contextid = $context->id;
        // Override our YouTube video upload with type image.
        $this->contentdata->contenttype = \mod_openstudio\local\api\content::TYPE_IMAGE;
        $fs = get_file_storage();
        $fs->create_file_from_pathname($this->file, $filepath);

        $this->assertNotEquals(false, $method->invokeArgs(null,
                array($this->contentdata, $this->file->contextid, 'mod_openstudio',
                        'content', $this->file->itemid, '/', $this->file->filename)));
    }

    /**
     * Tests the function that returns the cotnent type of a content.
     */
    public function test_get_contenttype() {
        $this->resetAfterTest(true);

        $class = new \ReflectionClass('\mod_openstudio\local\api\content');
        $method = $class->getMethod('get_contenttype');
        $method->setAccessible(true);

        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->file = $this->generator->generate_file_data($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        // X Do a check on jpg for images. But first arrange our ...
        // X $file object the wya the application expects it!
        $x = $this->file;
        $this->file = [];
        $this->file['file'] = $x;
        $this->file['mimetype'] = array();
        $this->file['mimetype']['extension'] = 'jpg';
        $this->file['mimetype']['type'] = 'typejpg';

        $dataarray = $this->object_to_array($this->contentdata);
        $updateddata = $method->invokeArgs(null, array($dataarray, $this->file));

        $this->assertEquals(\mod_openstudio\local\api\content::TYPE_IMAGE, $updateddata['contenttype']);
        $this->assertEquals($this->file['file']->filename, $updateddata['content']);
    }

    /**
     * Tests internal function that processes content data to write to database.
     */
    public function test_process_data() {
        $this->resetAfterTest(true);

        $class = new \ReflectionClass('\mod_openstudio\local\api\content');
        $method = $class->getMethod('process_data');
        $method->setAccessible(true);

        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);

        $processeddata = $method->invokeArgs(null, array($this->singleentrydata));

        // Given the data $singleentrydata contains, urltitle should be empty and content should be populated.
        $this->assertNotEmpty($processeddata['content']);
        $this->assertEquals($processeddata['contenttype'], \mod_openstudio\local\api\content::TYPE_URL);
        // Ideally, we should check every data type. Perhaps later if we have time.
    }

    /**
     * Tests creation of a pinboard content.
     */
    public function test_create_in_pinboard() {
        $this->resetAfterTest(true);

        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $pinboardcontentid = \mod_openstudio\local\api\content::create_in_pinboard($this->studiolevels->id,
                $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $pinboardcontentid, 'Pinboard content creation failed.');
        $pinboardcontentdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id,
                $pinboardcontentid);
        $this->assertNotEquals(false, $pinboardcontentdata);
        $this->assertEquals($pinboardcontentdata->name, $this->singleentrydata['name']);
        $this->assertEquals($pinboardcontentdata->description, $this->singleentrydata['description']);
        $this->assertEquals($pinboardcontentdata->visibility, $this->singleentrydata['visibility']);
        $this->assertEquals($pinboardcontentdata->userid,  $this->users->students->one->id);
        $this->assertEquals($pinboardcontentdata->levelid, 0);
    }

    /**
     * Tests the \mod_openstudio\local\api\content::update() function
     */
    public function test_update() {
        $this->resetAfterTest(true);

        $this->setUser($this->users->students->one);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $this->contentdata->id, 'Slot creation failed - no contentid returned.');
        $this->singleentrydata['name'] = 'This is the new name!!';
        $this->singleentrydata['descriptions'] = 'This is the new description!!';

        $updatedcontentid = \mod_openstudio\local\api\content::update(
                $this->users->students->one->id, $this->contentdata->id, $this->singleentrydata);
        $this->assertNotEquals(false, $updatedcontentid);
        $updatedcontentdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $updatedcontentid);
        $this->assertEquals($updatedcontentid, $this->contentdata->id);
        $this->assertEquals($updatedcontentdata->name, $this->singleentrydata['name']);
        $this->assertEquals($updatedcontentdata->description, $this->singleentrydata['description']);
    }

    /**
     * Tests version deleting.
     */
    public function test_version_delete() {
        $this->resetAfterTest(true);

        global $DB;

        // Create a content, update it so it has a version, then let's try to delete the version.
        $this->setUser($this->users->students->one);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $this->contentdata->id, 'Slot creation failed - no contentid returned.');
        $this->singleentrydata['name'] = 'This is the new name!!';
        $this->singleentrydata['descriptions'] = 'This is the new description!!';
        $this->singleentrydata['weblink'] = 'http://open.edu';

        $context = \context_module::instance($this->studiolevels->cmid);

        $updatedcontentid = \mod_openstudio\local\api\content::update(
                $this->users->students->one->id, $this->contentdata->id, $this->singleentrydata, null, $context, true);
        $this->assertNotEquals(false, $updatedcontentid);
        $this->assertEquals($updatedcontentid, $this->contentdata->id);
        // Update successful, now get the version number and delete it.
        $version = $DB->get_record('openstudio_content_versions', array('contentid' => $updatedcontentid), '*', MUST_EXIST);

        \mod_openstudio\local\api\content::version_delete($this->users->students->one->id, $version->id);

        $this->assertFalse($DB->record_exists('openstudio_content_versions',
                array('id' => $version->id, 'deletedby' => null, 'deletedtime' => null)));
    }

    public function test_content_useristutor() {
        global $DB;
        // Setup tutor groups.
        $tutorrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $tutorrole2 = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->users->tutors = new \stdClass();
        $this->users->tutors->one = $this->getDataGenerator()->create_user(
                array('email' => 'tutor1@ouunittest.com', 'username' => 'tutor1'));
        $this->users->tutors->two = $this->getDataGenerator()->create_user(
                array('email' => 'tutor2@ouunittest.com', 'username' => 'tutor2'));
        $this->users->tutors->three = $this->getDataGenerator()->create_user(
                array('email' => 'tutor2@ouunittest.com', 'username' => 'tutor3'));
        $this->users->tutors->four = $this->getDataGenerator()->create_user(
                array('email' => 'tutor2@ouunittest.com', 'username' => 'tutor4'));
        $this->getDataGenerator()->enrol_user($this->users->tutors->one->id, $this->course->id,
                $tutorrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->tutors->two->id, $this->course->id,
                $tutorrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->tutors->four->id, $this->course->id,
                $tutorrole2->id, 'manual');

        // Create tutor groups.
        $groups = new \stdClass();
        $groups->one = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));
        $groups->two = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));
        $groups->three = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));

        $groupings = new \stdClass();
        $groupings->one = $this->getDataGenerator()->create_grouping(array('courseid' => $this->course->id));
        $groupings->two = $this->getDataGenerator()->create_grouping(array('courseid' => $this->course->id));

        $DB->set_field('course_modules', 'groupingid', $groupings->one->id, array('id' => $this->studiolevels->cmid));

        $this->getDataGenerator()->create_grouping_group(array(
                        'groupid' => $groups->one->id,
                        'groupingid' => $groupings->one->id)
        );
        $this->getDataGenerator()->create_grouping_group(array(
                        'groupid' => $groups->two->id,
                        'groupingid' => $groupings->one->id)
        );
        $this->getDataGenerator()->create_grouping_group(array(
                        'groupid' => $groups->three->id,
                        'groupingid' => $groupings->two->id)
        );

        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->students->one->id,
                'groupid' => $groups->one->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->students->two->id,
                'groupid' => $groups->two->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->tutors->one->id,
                'groupid' => $groups->one->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->tutors->two->id,
                'groupid' => $groups->two->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->tutors->three->id,
                'groupid' => $groups->three->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->students->one->id,
                'groupid' => $groups->three->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->tutors->four->id,
                'groupid' => $groups->one->id
        ));

        // Create content.
        $tutorcontentid = $this->generator->create_contents(array(
                'openstudio' => 'OS1',
                'userid' => $this->users->students->one->id,
                'name' => 'Test Slot',
                'description' => 'Test Slot'
        ));

        $tutorroles = array($tutorrole->id);
        // Student 1 in in the right group, but isn't a tutor.
        $this->assertFalse(\mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->students->one->id, $tutorroles));
        // Student 2 isn't a tutor or in the right group.
        $this->assertFalse(\mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->students->two->id, $tutorroles));
        // Tutor 2 is a tutor, but is in the wrong group.
        $this->assertFalse(\mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->tutors->two->id, $tutorroles));
        // Tutor 1 is a tutor, and is in the right group.
        $this->assertTrue(\mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->tutors->one->id, $tutorroles));
        // Tutor 3 is a tutor and in a group with student1, but the group is not in the right grouping.
        $this->assertFalse(\mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->tutors->three->id, $tutorroles));

        // Tutor 4 is in the right group, but has the wrong role.
        $this->assertFalse(\mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->tutors->four->id, $tutorroles));

        // Tutor 4 is in the right group, and their role is now included in the list.
        $tutorroles[] = $tutorrole2->id;
        $this->assertTrue(\mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->tutors->four->id, $tutorroles));

    }

    public function test_get() {

        $contentdata = array(
                'openstudio' => 'OS1',
                'userid' => $this->users->students->one->id,
                'name' => random_string(),
                'description' => random_string()
        );
        $contentid = $this->generator->create_contents($contentdata);

        $content = \mod_openstudio\local\api\content::get($contentid);
        $this->assertEquals($contentdata['name'], $content->name);
        $this->assertEquals($contentdata['description'], $content->description);
    }

    public function test_get_all_records() {

        $contentcount = rand(10, 20);
        for ($i = 1; $i <= $contentcount; $i++) {
            $contentdata = array(
                    'openstudio' => 'OS1',
                    'userid' => $this->users->students->one->id,
                    'name' => random_string(),
                    'description' => random_string()
            );
            $this->generator->create_contents($contentdata);
        }

        $content = \mod_openstudio\local\api\content::get_all_records($this->studiolevels->id);
        $this->assertEquals($contentcount, iterator_count($content));

        // Create another instance to check filtering by studio ID.
        $studio2 = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS2'));
        $contentcount2 = rand(10, 20);
        for ($i = 1; $i <= $contentcount2; $i++) {
            $contentdata = array(
                    'openstudio' => 'OS2',
                    'userid' => $this->users->students->one->id,
                    'name' => random_string(),
                    'description' => random_string()
            );
            $this->generator->create_contents($contentdata);
        }
        $content = \mod_openstudio\local\api\content::get_all_records($studio2->id);
        $this->assertEquals($contentcount2, iterator_count($content));

        $content = \mod_openstudio\local\api\content::get_all_records();
        $this->assertEquals($contentcount + $contentcount2, iterator_count($content));

    }

    public function test_get_record_via_levels() {

        $level1 = reset($this->studiolevels->leveldata['contentslevels']);
        $level2 = reset($level1);
        $level3 = reset($level2);

        $contentdata = array(
                'openstudio' => 'OS1',
                'userid' => $this->users->students->one->id,
                'name' => random_string(),
                'description' => random_string(),
                'levelcontainer' => 3,
                'levelid' => $level3
        );
        $contentid = $this->generator->create_contents($contentdata);

        $content = \mod_openstudio\local\api\content::get_record_via_levels($this->studiolevels->id,
                $this->users->students->one->id, 3, $level3);

        $this->assertEquals($contentid, $content->id);
        $this->assertEquals($contentdata['name'], $content->name);
    }

    public function test_get_total() {
        // Create content in an activity for 2 users, leave the rest blank.
        // The total count for one user should show the content defined in that activity used.
        $level1 = reset($this->studiolevels->leveldata['contentslevels']);
        $level2 = reset($level1);

        foreach ($level2 as $level3) {
            $contentdata = array(
                    'openstudio' => 'OS1',
                    'userid' => $this->users->students->one->id,
                    'name' => random_string(),
                    'description' => random_string(),
                    'levelcontainer' => 3,
                    'levelid' => $level3
            );
            $this->generator->create_contents($contentdata);
            $contentdata = array(
                    'openstudio' => 'OS1',
                    'userid' => $this->users->students->two->id,
                    'name' => random_string(),
                    'description' => random_string(),
                    'levelcontainer' => 3,
                    'levelid' => $level3
            );
            $this->generator->create_contents($contentdata);
        }

        $totals = \mod_openstudio\local\api\content::get_total($this->studiolevels->id,
                $this->users->students->one->id);
        $this->assertEquals(count($level2), $totals->used);
        $this->assertEquals($this->totalcontents, $totals->total);

    }

    public function test_get_pinboard_total() {
        global $DB;
        $pinboardlimit = rand(10, 20);
        $studio2 = $this->generator->create_instance(
                array('course' => $this->course->id, 'idnumber' => 'OS2', 'pinboard' => $pinboardlimit));

        $lastid = null;
        $pinboardused = rand(2, 9);
        for ($i = 1; $i <= $pinboardused; $i++) {
            $contentdata = array(
                    'openstudio' => 'OS2',
                    'userid' => $this->users->students->one->id,
                    'name' => random_string(),
                    'description' => random_string(),
            );
            $lastid = $this->generator->create_contents($contentdata);
        }
        // Set one content so it looks like it's been emptied.
        $contentdata['name'] = '';
        $contentdata['description'] = '';
        $contentdata['contenttype'] = \mod_openstudio\local\api\content::TYPE_NONE;
        $contentdata['id'] = $lastid;
        unset($contentdata['openstudio']);
        $DB->update_record('openstudio_contents', (object)$contentdata);

        $totals = \mod_openstudio\local\api\content::get_pinboard_total($studio2->id,
                $this->users->students->one->id);

        $this->assertEquals($pinboardused - 1, $totals->used);
        $this->assertEquals(1, $totals->empty);
        $this->assertEquals($pinboardused, $totals->usedandempty);
        $this->assertEquals($pinboardlimit, $totals->total);
        $this->assertEquals($pinboardlimit - $pinboardused, $totals->available);

    }

    public function test_restore_version() {
        global $DB;
        // Create a content, update it so it has a version, then restore the version.
        $this->setUser($this->users->students->one);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $this->contentdata->id, 'Slot creation failed - no contentid returned.');
        $this->singleentrydata['name'] = 'This is the new name!!';
        $this->singleentrydata['descriptions'] = 'This is the new description!!';
        $this->singleentrydata['weblink'] = 'http://open.edu';

        $context = \context_module::instance($this->studiolevels->cmid);

        $updatedcontentid = \mod_openstudio\local\api\content::update(
                $this->users->students->one->id, $this->contentdata->id, $this->singleentrydata, null, $context, true);
        $this->assertNotEquals(false, $updatedcontentid);
        $this->assertEquals($updatedcontentid, $this->contentdata->id);
        $this->assertEquals($this->singleentrydata['weblink'],
                $DB->get_field('openstudio_contents', 'content', array('id' => $updatedcontentid)));
        // Update successful, now get the version number and restore it.
        $version = $DB->get_record('openstudio_content_versions', array('contentid' => $updatedcontentid), '*', MUST_EXIST);

        \mod_openstudio\local\api\content::restore_version($this->users->students->one->id, $version->id);

        $this->assertNotEquals($this->singleentrydata['weblink'],
                $DB->get_field('openstudio_contents', 'content', array('id' => $updatedcontentid)));
    }

    public function test_get_image_exif_data() {
        global $CFG;
        $this->resetAfterTest(true);

        // Local file to upload to server to simulate file upload.
        $context = \context_module::instance($this->studiolevels->cmid);
        $filepath = $CFG->dirroot . '/mod/openstudio/tests/importfiles/geotagged.jpg';
        $filedata = (object)array(
            'filearea' => 'content',
            'filepath' => '/',
            'filename' => 'geotagged.jpg',
            'component' => 'mod_openstudio',
            'datecreated' => time(),
            'datemodified' => time(),
            'itemid' => 1,
            'contextid' => $context->id
        );
        $fs = get_file_storage();
        $fs->create_file_from_pathname($filedata, $filepath);

        $info = \mod_openstudio\local\api\content::get_image_exif_data(
                $context->id, 'mod_openstudio', 'content', 1, '/', 'geotagged.jpg');

        // Check we've extracted the known GPS data from the image.
        $this->assertEquals(32.211898833055557, $info['GPSData']['lat']);
        $this->assertEquals((string) -110.72466283333, (string) $info['GPSData']['lng']);

    }

    public function test_broken_image_process() {
        global $PAGE;
        $PAGE->set_url('/');
        if (!extension_loaded('imagick') || !class_exists('Imagick')) {
            $this->markTestSkipped('Imagick not enabled');
        }
        $this->resetAfterTest(true);

        // Add to server to simulate file upload.
        $context = \context_module::instance($this->studiolevels->cmid);
        $filedata = (object) array(
                'filearea' => 'content',
                'filepath' => '/',
                'filename' => 'geotagged.jpg',
                'component' => 'mod_openstudio',
                'datecreated' => time(),
                'datemodified' => time(),
                'itemid' => 1,
                'contextid' => $context->id
        );
        $fs = get_file_storage();
        $fs->create_file_from_string($filedata, 'this really is not a jpg');

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('There was an error when processing your image');
        \mod_openstudio\local\api\content::strip_metadata_for_image(['file' => $filedata], $context, 1);
    }

    public function test_rotate_image() {
        global $CFG;
        $this->resetAfterTest(true);

        $context = \context_module::instance($this->studiolevels->cmid);

        // Path to the error image.
        $filepath = $CFG->dirroot . '/mod/openstudio/tests/importfiles/20191116_170509.jpg';
        $fs = get_file_storage();
        $contenttumbnailfile = (object) array(
                'contextid' => $context->id,
                'component' => 'mod_openstudio',
                'filearea' => 'content',
                'itemid' => 1,
                'filepath' => '/',
                'filename' => '20191116_170509.jpg',
        );
        $contentfile = $fs->create_file_from_pathname($contenttumbnailfile, $filepath);

        // Convert to the error thumbnail.
        $contenttumbnailfile->filearea = 'contentthumbnail';
        $errorthumbnail = @$fs->convert_image(
                $contenttumbnailfile, $contentfile, \mod_openstudio\local\util\defaults::CONTENTTHUMBNAIL_WIDTH, null, true, null);

        $errorimageinfo = $errorthumbnail->get_imageinfo();

        // Rotate the error thumbnail to original orientation.
        $contenttumbnailfile = (array) $contenttumbnailfile;
        $rotatedimg = \mod_openstudio\local\api\content::rotate_thumbnail_to_original($contentfile->get_id(), $errorthumbnail,
                $contenttumbnailfile);
        $rotatedimageinfo = $rotatedimg->get_imageinfo();

        $this->assertEquals($errorimageinfo['width'], $rotatedimageinfo['height']);
        $this->assertEquals($errorimageinfo['height'], $rotatedimageinfo['width']);
    }

    public function test_auto_create_folder() {
        $params = [
            'course' => $this->course->id,
            'idnumber' => 'OS2',
        ];
        // Create generic studios.
        $studiolevels = $this->generator->create_instance($params);
        $leveldata = $this->generator->create_mock_levels($studiolevels->id);

        $level1 = reset($leveldata['contentslevels']);
        $level2 = reset($level1);
        $level3 = reset($level2);

        $openstudio = $this->generator->get_studio_by_idnumber($params['idnumber']);
        $foldervialevel = content::get_record_via_levels($openstudio->id, $this->users->students->one->id, 3, $level3);

        // No folder is created.
        $this->assertFalse($foldervialevel);

        $folderid = util::get_folder_id($openstudio, $this->users->students->one->id, $level3);
        $this->assertIsInt($folderid);

        // Get folder via level again, and check if it has the same ID.
        $foldervialevel = content::get_record_via_levels($openstudio->id, $this->users->students->one->id, 3, $level3);

        $this->assertEquals($folderid, $foldervialevel->id);
    }

    /**
     * @return array[]
     */
    public function get_lowest_sharing_level_provider(): array {
        return [
            [
                [content::VISIBILITY_PRIVATE,
                    content::VISIBILITY_TUTOR,
                    content::VISIBILITY_GROUP,
                    content::VISIBILITY_MODULE],
                1
            ],
            [
                [content::VISIBILITY_TUTOR,
                    content::VISIBILITY_GROUP,
                    content::VISIBILITY_MODULE],
                7
            ],
            [
                [content::VISIBILITY_GROUP,
                    content::VISIBILITY_MODULE],
                2
            ],
            [
                [content::VISIBILITY_MODULE],
                3
            ],
            [
                [6, 5, 4],
                content::VISIBILITY_PRIVATE
            ],
        ];
    }

    /**
     * @dataProvider get_lowest_sharing_level_provider
     * @param array $visibilityarray An array of sharing levels.
     * @param int $expectedlowestmode The expected lowest sharing level.
     * @return void
     */
    public function test_get_lowest_sharing_level($visibilityarray, $expectedlowestmode): void {
        $this->assertEquals($expectedlowestmode, util::get_lowest_mode($visibilityarray));
    }

    public function test_get_visibility(): void {
        global $DB;
        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create course groups.
        $this->groups = new \stdClass();
        $this->groupings = new \stdClass();
        $this->groupings->a  = $this->getDataGenerator()->create_grouping(
            ['name' => 'Grouping A', 'courseid' => $this->course->id]);
        $this->groups->one = $this->getDataGenerator()->create_group([
            'courseid' => $this->course->id, 'name' => 'Group 1']);
        $this->groups->two = $this->getDataGenerator()->create_group([
            'courseid' => $this->course->id, 'name' => 'Group 2']);

        // Add groups to our groupings.
        $insert = new \stdClass();
        $DB->insert_record('groupings_groups', (object)[
            'groupingid' => $this->groupings->a->id,
            'groupid' => $this->groups->one->id,
        ]);
        $insert->groupingid = $this->groupings->a->id;
        $insert->groupid = $this->groups->two->id;

        // Add user one to group one and group two.
        $this->generator->add_users_to_groups([
            $this->groups->one->id => [
                $this->users->students->one->id,
            ]
        ]);
        $this->generator->add_users_to_groups([
            $this->groups->two->id => [
                $this->users->students->one->id,
            ]
        ]);

        // Create generic studios.
        $this->studio1 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS1',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_PRIVATE,
                content::VISIBILITY_TUTOR,
                content::VISIBILITY_GROUP,
                content::VISIBILITY_MODULE,
            ]),
        ]);

        // Private is the lowest sharing level.
        $visibility = util::get_visibility($this->studio1->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_PRIVATE, $visibility);

        $this->studio2 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS3',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_TUTOR,
                content::VISIBILITY_GROUP,
            ]),
        ]);
        // Tutor is the lowest sharing level.
        $visibility = util::get_visibility($this->studio2->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_TUTOR, $visibility);

        $this->studio3 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS3',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_GROUP,
                content::VISIBILITY_MODULE,
            ]),
        ]);
        // Content is shared to the first group when user has many groups.
        $visibility = util::get_visibility($this->studio3->id, $this->users->students->one->id);
        // The groupid should be the first group.
        $this->assertEquals(-$this->groups->one->id, $visibility);

        $this->studio4 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS3',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => content::VISIBILITY_MODULE,
        ]);
        // Module is the lowest sharing level.
        $visibility = util::get_visibility($this->studio4->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_MODULE, $visibility);

        $this->studio5 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS3',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_GROUP,
                content::VISIBILITY_MODULE,
            ]),
        ]);
        // Group is the lowest sharing level but user two isn't added to any group.
        $visibility = util::get_visibility($this->studio5->id, $this->users->students->two->id);
        $this->assertEquals(content::VISIBILITY_MODULE, $visibility);

        $this->studio6 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS3',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => content::VISIBILITY_GROUP,
        ]);
        // Group is the only sharing level but user two isn't added to any group.
        $visibility = util::get_visibility($this->studio6->id, $this->users->students->two->id);
        $this->assertEquals(content::VISIBILITY_PRIVATE, $visibility);

        $this->studio7 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS3',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_TUTOR,
                content::VISIBILITY_GROUP,
                content::VISIBILITY_MODULE,
            ]),
        ]);
        // Tutor is the lowest sharing level but user two isn't added to any group.
        $visibility = util::get_visibility($this->studio7->id, $this->users->students->two->id);
        $this->assertEquals(content::VISIBILITY_TUTOR, $visibility);

        $this->studio8 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS3',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_TUTOR,
                content::VISIBILITY_GROUP,
            ]),
        ]);
        // Tutor is the lowest sharing level but user two isn't added to any group.
        $visibility = util::get_visibility($this->studio8->id, $this->users->students->two->id);
        $this->assertEquals(content::VISIBILITY_TUTOR, $visibility);

        $this->studio9 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS3',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_TUTOR,
                content::VISIBILITY_MODULE,
            ]),
        ]);
        // Tutor is the lowest sharing level but user two isn't added to any group.
        $visibility = util::get_visibility($this->studio9->id, $this->users->students->two->id);
        $this->assertEquals(content::VISIBILITY_TUTOR, $visibility);

        $this->studio10 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS3',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => content::VISIBILITY_MODULE,
        ]);
        // Module is the only sharing level but user two isn't added to any group.
        $visibility = util::get_visibility($this->studio10->id, $this->users->students->two->id);
        $this->assertEquals(content::VISIBILITY_MODULE, $visibility);

        // Studio setting No group.
        $this->studio11 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => NOGROUPS,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_TUTOR,
                content::VISIBILITY_GROUP,
            ]), // Sharing level include tutor and group.
        ]);
        $visibility = util::get_visibility($this->studio11->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_PRIVATE, $visibility);

        $this->studio12 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => NOGROUPS,
            'allowedvisibility' => content::VISIBILITY_TUTOR, // Sharing level include tutor.
        ]);
        $visibility = util::get_visibility($this->studio12->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_PRIVATE, $visibility);

        $this->studio13 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => NOGROUPS,
            'allowedvisibility' => content::VISIBILITY_GROUP, // Sharing level include group.
        ]);
        $visibility = util::get_visibility($this->studio13->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_PRIVATE, $visibility);

        $this->studio14 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => NOGROUPS,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_TUTOR,
                content::VISIBILITY_MODULE,
            ]), // Sharing level include tutor and module.
        ]);
        $visibility = util::get_visibility($this->studio14->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_MODULE, $visibility);

        $this->studio15 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => NOGROUPS,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_GROUP,
                content::VISIBILITY_MODULE,
            ]), // Sharing level include group and module.
        ]);
        $visibility = util::get_visibility($this->studio15->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_MODULE, $visibility);

        $this->studio16 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => NOGROUPS,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_TUTOR,
                content::VISIBILITY_GROUP,
                content::VISIBILITY_MODULE,
            ]), // Sharing level include tutor, group and module.
        ]);
        $visibility = util::get_visibility($this->studio16->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_MODULE, $visibility);

        $this->studio17 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => NOGROUPS,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_PRIVATE,
                content::VISIBILITY_TUTOR,
                content::VISIBILITY_GROUP,
                content::VISIBILITY_MODULE,
            ]), // Sharing level include private, tutor, group and module.
        ]);
        $visibility = util::get_visibility($this->studio17->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_PRIVATE, $visibility);

        // Sharing level include group and setting group mode but no grouping.
        $this->studio18 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'groupingid' => NOGROUPS,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => content::VISIBILITY_GROUP,
        ]);
        $visibility = util::get_visibility($this->studio18->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_PRIVATE, $visibility);

        // Sharing level include tutor, group, module and setting group mode but no grouping.
        $this->studio19 = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'groupingid' => NOGROUPS,
            'groupmode' => content::VISIBILITY_GROUP,
            'allowedvisibility' => implode(',', [
                content::VISIBILITY_TUTOR,
                content::VISIBILITY_GROUP,
                content::VISIBILITY_MODULE,
            ]),
        ]);
        $visibility = util::get_visibility($this->studio19->id, $this->users->students->one->id);
        $this->assertEquals(content::VISIBILITY_MODULE, $visibility);

    }

    public function test_resize_image_using_imagick(): void {
        global $CFG;
        if (!extension_loaded('imagick') || !class_exists('Imagick')) {
            $this->markTestSkipped('Imagick not enabled');
        }
        $this->resetAfterTest(true);

        $context = \context_module::instance($this->studiolevels->cmid);

        // Path to the original image.
        $filepath = $CFG->dirroot . '/mod/openstudio/tests/importfiles/picture_big_size.jpg';
        $fs = get_file_storage();
        $contenttumbnailfile = (object) [
                'contextid' => $context->id,
                'component' => 'mod_openstudio',
                'filearea' => 'content',
                'itemid' => 1,
                'filepath' => '/',
                'filename' => 'picture_big_size.jpg',
        ];
        $contentfile = $fs->create_file_from_pathname($contenttumbnailfile, $filepath);

        $thumbnailwidth = \mod_openstudio\local\util\defaults::CONTENTTHUMBNAIL_WIDTH;
        $contenttumbnailfile->filearea = 'contentthumbnail';
        $thumbnail = \mod_openstudio\local\api\content::resize_image_imagick($contenttumbnailfile, $contentfile, $thumbnailwidth);
        $this->assertInstanceOf(\stored_file::class, $thumbnail);
        $thumbnailinfo = $thumbnail->get_imageinfo();

        // Check new width/height after resize.
        $this->assertEquals($thumbnailinfo['width'], $thumbnailwidth);
        $this->assertEquals($thumbnailinfo['height'], $thumbnailwidth);
    }
}
