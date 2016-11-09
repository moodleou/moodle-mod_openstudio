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

require_once('openstudio_testcase.php'); // Until this is moved to generator.

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();
class mod_openstudio_content_testcase extends openstudio_testcase {

    private $totalcontents;
    private $pinboardcontents;

    /**
     * Sets up our fixtures.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
        $teacherroleid = 3;
        $studentroleid = 5;
        $this->totalcontents = 24; // This is what the scripts below create for ONE CMID.
        $this->pinboardcontents = 3; // This is what the scripts below create for ONE CMID.

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create user.
        $this->users = new stdClass();
        $this->users->students = new stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                array('email' => 'student1@ouunittest.com', 'username' => 'student1'));
        $this->users->students->two = $this->getDataGenerator()->create_user(
                array('email' => 'student2@ouunittest.com', 'username' => 'student2'));

        $this->users->teachers = new stdClass();
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

    protected function tearDown() {
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
     * Tests the mod_openstudio\local\api\content::create() function in the content api.
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
                            'visibility' => STUDIO_VISIBILITY_MODULE,
                            'description' => 'YouTube link',
                            'tags' => array(random_string(), random_string(), random_string()),
                            'ownership' => 0,
                            'sid' => 0 // For a new content.
                    );
                    $contents[$contentlevelid][$contentcount] = mod_openstudio\local\api\content::create(
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

        $this->populate_single_data_array();
        $this->populate_content_data();
        $this->assertGreaterThan(0, $this->contentid, 'Slot creation failed - no contentid returned.');
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

        $this->populate_single_data_array();
        $this->populate_content_data();
        $this->setUser($this->users->students->one);
        $emptyresult = mod_openstudio\local\api\content::empty_content($this->users->students->one->id, $this->contentid);
        $this->assertNotEquals(false, $emptyresult);
    }

    /**
     * Tests the function that deletes a content.
     */
    public function test_delete() {
        $this->resetAfterTest(true);

        $this->populate_single_data_array();
        $this->populate_content_data();
        $this->setUser($this->users->students->one);
        $deleteresult = mod_openstudio\local\api\content::delete($this->users->students->one->id, $this->contentid);
        $this->assertNotEquals(false, $deleteresult);
    }

    /**
     * Tests creation of a thumbnail for content that uses an uploaded image.
     */
    public function test_create_thumbnail() {
        global $CFG;
        $this->resetAfterTest(true);

        $class = new ReflectionClass('mod_openstudio\local\api\content');
        $method = $class->getMethod('create_thumbnail');
        $method->setAccessible(true);

        // Local file to upload to server to simulate file upload.
        $filepath = $CFG->dirroot . '/mod/openstudio/tests/importfiles/test1.jpg';

        $this->populate_single_data_array();
        $this->populate_file_data();
        $this->populate_content_data();
        $this->setUser($this->users->students->one);
        $context = context_module::instance($this->studiolevels->cmid);
        $this->file->contextid = $context->id;
        // Override our YouTube video upload with type image.
        $this->contentdata->contenttype = mod_openstudio\local\api\content::TYPE_IMAGE;
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

        $class = new ReflectionClass('mod_openstudio\local\api\content');
        $method = $class->getMethod('get_contenttype');
        $method->setAccessible(true);

        $this->populate_single_data_array();
        $this->populate_file_data();
        $this->populate_content_data();
        // X Do a check on jpg for images. But first arrange our ...
        // X $file object the wya the application expects it!
        $x = $this->file;
        $this->file = '';
        $this->file['file'] = $x;
        $this->file['mimetype'] = array();
        $this->file['mimetype']['extension'] = 'jpg';
        $this->file['mimetype']['type'] = 'typejpg';

        $dataarray = $this->object_to_array($this->contentdata);
        $updateddata = $method->invokeArgs(null, array($dataarray, $this->file));

        $this->assertEquals(STUDIO_CONTENTTYPE_IMAGE, $updateddata['contenttype']);
        $this->assertEquals($this->file['file']->filename, $updateddata['content']);
    }

    /**
     * Tests internal function that processes content data to write to database.
     */
    public function test_process_data() {
        $this->resetAfterTest(true);

        $class = new ReflectionClass('mod_openstudio\local\api\content');
        $method = $class->getMethod('process_data');
        $method->setAccessible(true);

        $this->populate_single_data_array();

        $processeddata = $method->invokeArgs(null, array($this->singleentrydata));

        // Given the data $singleentrydata contains, urltitle should be empty and content should be populated.
        $this->assertNotEmpty($processeddata['content']);
        $this->assertEquals($processeddata['contenttype'], STUDIO_CONTENTTYPE_URL);
        // Ideally, we should check every data type. Perhaps later if we have time.
    }

    /**
     * Tests creation of a pinboard content.
     */
    public function test_create_in_pinboard() {
        $this->resetAfterTest(true);

        $this->populate_single_data_array();
        $pinboardcontentid = mod_openstudio\local\api\content::create_in_pinboard($this->studiolevels->id,
                $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $pinboardcontentid, 'Pinboard content creation failed.');
        $pinboardcontentdata = mod_openstudio\local\api\content::get_record($this->users->students->one->id,
                $pinboardcontentid);
        $this->assertNotEquals(false, $pinboardcontentdata);
        $this->assertEquals($pinboardcontentdata->name, $this->singleentrydata['name']);
        $this->assertEquals($pinboardcontentdata->description, $this->singleentrydata['description']);
        $this->assertEquals($pinboardcontentdata->visibility, $this->singleentrydata['visibility']);
        $this->assertEquals($pinboardcontentdata->userid,  $this->users->students->one->id);
        $this->assertEquals($pinboardcontentdata->levelid, 0);
    }

    /**
     * Tests the mod_openstudio\local\api\content::update() function
     */
    public function test_update() {
        $this->resetAfterTest(true);

        $this->setUser($this->users->students->one);
        $this->populate_single_data_array();
        $this->populate_content_data();
        $this->assertGreaterThan(0, $this->contentid, 'Slot creation failed - no contentid returned.');
        $this->singleentrydata['name'] = 'This is the new name!!';
        $this->singleentrydata['descriptions'] = 'This is the new description!!';

        $updatedcontentid = mod_openstudio\local\api\content::update(
                $this->users->students->one->id, $this->contentid, $this->singleentrydata);
        $this->assertNotEquals(false, $updatedcontentid);
        $updatedcontentdata = mod_openstudio\local\api\content::get_record($this->users->students->one->id, $updatedcontentid);
        $this->assertEquals($updatedcontentid, $this->contentid);
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
        $this->populate_single_data_array();
        $this->populate_content_data();
        $this->assertGreaterThan(0, $this->contentid, 'Slot creation failed - no contentid returned.');
        $this->singleentrydata['name'] = 'This is the new name!!';
        $this->singleentrydata['descriptions'] = 'This is the new description!!';
        $this->singleentrydata['weblink'] = 'http://open.edu';

        $context = context_module::instance($this->studiolevels->cmid);

        $updatedcontentid = mod_openstudio\local\api\content::update(
                $this->users->students->one->id, $this->contentid, $this->singleentrydata, null, $context, true);
        $this->assertNotEquals(false, $updatedcontentid);
        $this->assertEquals($updatedcontentid, $this->contentid);
        // Update successful, now get the version number and delete it.
        $version = $DB->get_record('openstudio_content_versions', array('contentid' => $updatedcontentid), '*', MUST_EXIST);

        mod_openstudio\local\api\content::version_delete($this->users->students->one->id, $version->id);

        $this->assertFalse($DB->record_exists('openstudio_content_versions',
                array('id' => $version->id, 'deletedby' => null, 'deletedtime' => null)));
    }

    public function test_content_useristutor() {
        global $DB;
        // Setup tutor groups.
        $tutorrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $tutorrole2 = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->users->tutors = new stdClass();
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
        $groups = new stdClass();
        $groups->one = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));
        $groups->two = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));
        $groups->three = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));

        $groupings = new stdClass();
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
                'studio' => 'OS1',
                'userid' => $this->users->students->one->id,
                'name' => 'Test Slot',
                'description' => 'Test Slot'
        ));

        $tutorroles = array($tutorrole->id);
        // Student 1 in in the right group, but isn't a tutor.
        $this->assertFalse(mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->students->one->id, $tutorroles));
        // Student 2 isn't a tutor or in the right group.
        $this->assertFalse(mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->students->two->id, $tutorroles));
        // Tutor 2 is a tutor, but is in the wrong group.
        $this->assertFalse(mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->tutors->two->id, $tutorroles));
        // Tutor 1 is a tutor, and is in the right group.
        $this->assertTrue(mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->tutors->one->id, $tutorroles));
        // Tutor 3 is a tutor and in a group with student1, but the group is not in the right grouping.
        $this->assertFalse(mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->tutors->three->id, $tutorroles));

        // Tutor 4 is in the right group, but has the wrong role.
        $this->assertFalse(mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->tutors->four->id, $tutorroles));

        // Tutor 4 is in the right group, and their role is now included in the list.
        $tutorroles[] = $tutorrole2->id;
        $this->assertTrue(mod_openstudio\local\api\content::user_is_tutor(
                $tutorcontentid, $this->users->tutors->four->id, $tutorroles));

    }

    public function test_get() {

        $contentdata = array(
                'studio' => 'OS1',
                'userid' => $this->users->students->one->id,
                'name' => random_string(),
                'description' => random_string()
        );
        $contentid = $this->generator->create_contents($contentdata);

        $content = mod_openstudio\local\api\content::get($contentid);
        $this->assertEquals($contentdata['name'], $content->name);
        $this->assertEquals($contentdata['description'], $content->description);
    }

    public function test_get_all_records() {

        $contentcount = rand(10, 20);
        for ($i = 1; $i <= $contentcount; $i++) {
            $contentdata = array(
                    'studio' => 'OS1',
                    'userid' => $this->users->students->one->id,
                    'name' => random_string(),
                    'description' => random_string()
            );
            $this->generator->create_contents($contentdata);
        }

        $content = mod_openstudio\local\api\content::get_all_records($this->studiolevels->id);
        $this->assertEquals($contentcount, iterator_count($content));

        // Create another instance to check filtering by studio ID.
        $studio2 = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS2'));
        $contentcount2 = rand(10, 20);
        for ($i = 1; $i <= $contentcount2; $i++) {
            $contentdata = array(
                    'studio' => 'OS2',
                    'userid' => $this->users->students->one->id,
                    'name' => random_string(),
                    'description' => random_string()
            );
            $this->generator->create_contents($contentdata);
        }
        $content = mod_openstudio\local\api\content::get_all_records($studio2->id);
        $this->assertEquals($contentcount2, iterator_count($content));

        $content = mod_openstudio\local\api\content::get_all_records();
        $this->assertEquals($contentcount + $contentcount2, iterator_count($content));

    }

    public function test_get_record_via_levels() {

        $level1 = reset($this->studiolevels->leveldata['contentslevels']);
        $level2 = reset($level1);
        $level3 = reset($level2);

        $contentdata = array(
                'studio' => 'OS1',
                'userid' => $this->users->students->one->id,
                'name' => random_string(),
                'description' => random_string(),
                'levelcontainer' => 3,
                'levelid' => $level3
        );
        $contentid = $this->generator->create_contents($contentdata);

        $content = mod_openstudio\local\api\content::get_record_via_levels($this->studiolevels->id,
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
                    'studio' => 'OS1',
                    'userid' => $this->users->students->one->id,
                    'name' => random_string(),
                    'description' => random_string(),
                    'levelcontainer' => 3,
                    'levelid' => $level3
            );
            $this->generator->create_contents($contentdata);
            $contentdata = array(
                    'studio' => 'OS1',
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
                    'studio' => 'OS2',
                    'userid' => $this->users->students->one->id,
                    'name' => random_string(),
                    'description' => random_string(),
            );
            $lastid = $this->generator->create_contents($contentdata);
        }
        // Set one content so it looks like it's been emptied.
        $contentdata['name'] = '';
        $contentdata['description'] = '';
        $contentdata['contenttype'] = mod_openstudio\local\api\content::TYPE_NONE;
        $contentdata['id'] = $lastid;
        unset($contentdata['studio']);
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
        $this->populate_single_data_array();
        $this->populate_content_data();
        $this->assertGreaterThan(0, $this->contentid, 'Slot creation failed - no contentid returned.');
        $this->singleentrydata['name'] = 'This is the new name!!';
        $this->singleentrydata['descriptions'] = 'This is the new description!!';
        $this->singleentrydata['weblink'] = 'http://open.edu';

        $context = context_module::instance($this->studiolevels->cmid);

        $updatedcontentid = mod_openstudio\local\api\content::update(
                $this->users->students->one->id, $this->contentid, $this->singleentrydata, null, $context, true);
        $this->assertNotEquals(false, $updatedcontentid);
        $this->assertEquals($updatedcontentid, $this->contentid);
        $this->assertEquals($this->singleentrydata['weblink'],
                $DB->get_field('openstudio_contents', 'content', array('id' => $updatedcontentid)));
        // Update successful, now get the version number and restore it.
        $version = $DB->get_record('openstudio_content_versions', array('contentid' => $updatedcontentid), '*', MUST_EXIST);

        mod_openstudio\local\api\content::restore_version($this->users->students->one->id, $version->id);

        $this->assertNotEquals($this->singleentrydata['weblink'],
                $DB->get_field('openstudio_contents', 'content', array('id' => $updatedcontentid)));
    }

    public function test_get_image_exif_data() {
        global $CFG;
        $this->resetAfterTest(true);

        // Local file to upload to server to simulate file upload.
        $context = context_module::instance($this->studiolevels->cmid);
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

        $info = mod_openstudio\local\api\content::get_image_exif_data(
                $context->id, 'mod_openstudio', 'content', 1, '/', 'geotagged.jpg');

        // Check we've extracted the known GPS data from the image.
        $this->assertEquals(32.211898833055557, $info['GPSData']['lat']);
        $this->assertEquals(-110.72466283333, $info['GPSData']['lng']);

    }
}