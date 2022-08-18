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
 * Unit tests for search posts code.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/openstudio/tests/fixtures/testable_openstudio_search.php');

use mod_openstudio\local\api\content;
use mod_openstudio\search\posts;
use mod_openstudio\local\api\search;

 /**
  * Test case for generic functions in classes/search/ where covered.
  *
  * @package mod_openstudio
  * @copyright 2017 The Open University
  * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */
class search_posts_test extends \advanced_testcase {

    private $user;
    private $course;
    private $generator; // Contains mod_openstudio specific data generator functions.
    private $openstudiolevels; // Generic studio instance with no levels or posts.
    private $cm;
    private $studentroleid;
    private $emptycontent;
    private $filecontent;
    private $sharedcontent;
    private $privatecontent;
    private $foldercontent;

    /**
     * Sets up our fixtures.
     */
    protected function setUp(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->studentroleid = 5;

        // Enable global search for this test.
        testable_openstudio_search::instance();

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->user = $this->getDataGenerator()->create_user(
                ['email' => 'student1@ouunittest.com', 'username' => 'student1']);

        // Enroll our students in the course.
        $this->getDataGenerator()->enrol_user(
                $this->user->id, $this->course->id, $this->studentroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $openstudiodata = ['course' => $this->course->id, 'enablefolders' => 1, 'idnumber' => 'OS2'];
        $this->openstudiolevels = $this->generator->create_instance($openstudiodata);
        $this->openstudiolevels->leveldata = $this->generator->create_mock_levels($this->openstudiolevels->id);

        $blockid = key($this->openstudiolevels->leveldata['contentslevels']);
        $activityid = key($this->openstudiolevels->leveldata['contentslevels'][$blockid]);
        $content1id = key($this->openstudiolevels->leveldata['contentslevels'][$blockid][$activityid]);

        $this->cm = get_coursemodule_from_id('openstudio', $this->openstudiolevels->cmid);

        // Create content entry.
        $emptycontententry = (object)[
            'name' => 'Empty content entry',
            'visibility' => content::VISIBILITY_MODULE,
            'contenttype' => content::TYPE_NONE,
            'ownership' => 0,
            'sid' => 0
        ];

        $sharedcontententry = $this->generator->generate_single_data_array($this->user);
        $sharedcontententry['contenttype'] = content::TYPE_URL;

        $file = $this->create_file($CFG->dirroot . '/mod/openstudio/tests/importfiles/test.txt');
        $filecontententry = (object) [
            'openstudioid' => $this->cm->instance,
            'levelid' => 0,
            'levelcontainer' => 0,
            'contenttype' => content::TYPE_DOCUMENT,
            'mimetype' => '',
            'content' => 'Content entry',
            'fileid' => $file->get_itemid(),
            'name' => 'File entry',
            'description' => random_string(),
            'textformat' => 0,
            'commentformat' => 0,
            'ownershipdetail' => '',
            'showextradata' => false,
            'visibility' => content::VISIBILITY_MODULE,
            'userid' => $this->user->id,
            'timemodified' => time(),
        ];

        $privatecontententry = $this->generator->generate_single_data_array($this->user);
        $privatecontententry['contenttype'] = content::TYPE_URL;
        $privatecontententry['visibility'] = content::VISIBILITY_PRIVATE;

        $foldercontententry = $this->generator->generate_single_data_array($this->user);
        $foldercontententry['contenttype'] = content::TYPE_FOLDER;

        // Create real content based on entry.
        $this->emptycontent = content::create_in_pinboard($this->openstudiolevels->id, $this->user->id,
                $emptycontententry, $this->cm);

        $this->sharedcontent = content::create(
                $this->openstudiolevels->id,  $this->user->id, 3,
                $this->openstudiolevels->leveldata['contentslevels'][$blockid][$activityid][$content1id], $sharedcontententry,
                null, null, $this->cm);

        $this->privatecontent = content::create_in_pinboard($this->openstudiolevels->id, $this->user->id,
                $privatecontententry, $this->cm);

        $this->foldercontent = content::create(
                $this->openstudiolevels->id,  $this->user->id, 3,
                $this->openstudiolevels->leveldata['contentslevels'][$blockid][$activityid][$content1id], $foldercontententry,
                null, null, $this->cm);

        // Function create_in_pinboard doesn't support content fileid.
        $this->filecontent = $DB->insert_record('openstudio_contents', $filecontententry);

        self::fix_timemodified_order();
    }

    public function tearDown(): void {
        testable_openstudio_search::$fakeresult = null;
        parent::tearDown();
    }

    /**
     * Ensures everything in openstudio_contents has a unique timemodified in same order as
     * the creation id.
     */
    public static function fix_timemodified_order() {
        global $DB;

        $index = 100;
        foreach ($DB->get_fieldset_sql('SELECT id FROM {openstudio_contents} ORDER BY id') as $id) {
            $DB->set_field('openstudio_contents', 'timemodified', $index++, ['id' => $id]);
        }
    }

    /**
     * Test function not get any record belong to empty content or folder.
     */
    public function test_search_posts_index() {
        $posts = new posts();
        $results = self::recordset_to_array($posts->get_recordset_by_timestamp());

        // Check that function return 3 posts not empty and folder content.
        $this->assertCount(3, $results);
    }

    /**
     * Test check get document.
     */
    public function test_check_get_document_function() {
        global $CFG;

        $posts = new posts();

        $context = context_module::instance($this->cm->id);

        $results = self::recordset_to_array($posts->get_recordset_by_timestamp());

        $doc = $posts->get_document($results[0], ['lastindexedtime' => 0]);
        $this->assertEquals('The GoT Vesica Timeline', $doc->get('title'));
        $this->assertEquals('The Best YouTube Link Ever', $doc->get('content'));
        $this->assertEquals('Stark Lannister Targereyen', $doc->get('description1'));
        $this->assertEquals('Vesica Timeline Block 1 Activity 1 Slot 1', $doc->get('description2'));
        $this->assertEquals($context->id, $doc->get('contextid'));
        $this->assertEquals(\core_search\manager::TYPE_TEXT, $doc->get('type'));
        $this->assertEquals($this->course->id, $doc->get('courseid'));
        $this->assertEquals($this->sharedcontent, $doc->get('itemid'));
        $this->assertEquals(\core_search\manager::NO_OWNER_ID, $doc->get('owneruserid'));

        // Check search result url.
        $url = $posts->get_doc_url($doc)->out(false);
        $this->assertEquals($CFG->wwwroot . '/mod/openstudio/content.php?id=' . $this->cm->id.'&sid='.$this->sharedcontent, $url);

        // Create a second instance to check the context restriction.
        $openstudiodata = ['course' => $this->course->id, 'enablefolders' => 1, 'idnumber' => 'OSother'];
        $other = $this->generator->create_instance($openstudiodata);
        $othercontext = context_module::instance($other->cmid);

        // Test get_document_recordset with and without context.
        $results = self::recordset_to_array($posts->get_document_recordset(0));
        $this->assertCount(3, $results);
        $results = self::recordset_to_array($posts->get_document_recordset(0, $context));
        $this->assertCount(3, $results);
        $results = self::recordset_to_array($posts->get_document_recordset(0, $othercontext));
        $this->assertCount(0, $results);
    }

    /**
     * Test check search posts with global search.
     */
    public function test_check_global_search() {
        // Use global search system for default.
        set_config('modulesitesearch', 2, 'local_moodleglobalsearch');
        set_config('activitysearch', 2, 'local_moodleglobalsearch');
        set_config('nonosepsitesearch', 1, 'local_moodleglobalsearch');

        // Create new post.
        $posts = new posts();
        $postsdata = self::recordset_to_array($posts->get_recordset_by_timestamp());

        // Add folder to fake data.
        $fakedata = new \stdClass();
        $fakedata->totalcount = 1;
        $fakedata->actualpage = 1;

        $resultdata = new \core_search\document($postsdata[0]->id, 'mod_openstudio', 'posts');

        $resultdata->set('contextid', \context_module::instance($this->cm->id)->id);
        $resultdata->set('courseid', $postsdata[0]->course);
        $resultdata->set('title', $postsdata[0]->urltitle);
        $resultdata->set('content', $postsdata[0]->content);
        $resultdata->set('modified', $postsdata[0]->timemodified);
        $resultdata->set_extra('coursefullname', $this->course->fullname);

        $fakedata->results[] = $resultdata;

        testable_openstudio_search::$fakeresult = $fakedata;

        // Search post.
        $data = search::query($this->cm, 'This is a post');

        $this->assertCount(1, $data->result);
        $post = array_pop($data->result);
        $this->assertEquals($resultdata->get('itemid'), $post->intref1);
    }

    /**
     *  Tests group support for os posts.
     */
    public function test_posts_for_group_support() {
        global $DB;
        $posts = new posts();
        $this->setAdminUser();

        $teacherroleid = 3;
        $studentroleid = 5;

        $generator = $this->getDataGenerator();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $this->course->id, $teacherroleid, 'manual');
        $student1 = $generator->create_user();
        $generator->enrol_user($student1->id, $this->course->id, $studentroleid, 'manual');
        $student2 = $generator->create_user();
        $generator->enrol_user($student2->id, $this->course->id, $studentroleid, 'manual');

        $group1 = $generator->create_group(['courseid' => $this->course->id]);
        $group2 = $generator->create_group(['courseid' => $this->course->id]);

        $grouping = $generator->create_grouping(['courseid' => $this->course->id, 'name' => 'Grouping A']);

        // Add groups to our groupings.
        $insert = new \stdClass();
        $insert->groupingid = $grouping->id;
        $insert->groupid = $group1->id;

        $DB->insert_record('groupings_groups', $insert);
        $insert->groupingid = $grouping->id;
        $insert->groupid = $group2->id;
        $DB->insert_record('groupings_groups', $insert);

        $this->generator->add_users_to_groups([
                $group1->id => [
                        $student1->id,
                        $teacher->id
                ],
                $group2->id => [
                        $student2->id
                ]
        ]);

        $role = $DB->get_record('role', ['id' => $teacherroleid], '*', MUST_EXIST);
        unassign_capability('moodle/site:accessallgroups', $role->id);

        $studiogroup = $this->generator->create_instance(['course' => $this->course->id],
                ['groupmode' => 1, 'groupingid' => $grouping->id]);
        $studiogroup->leveldata = $this->generator->create_mock_levels($studiogroup->id);

        $blockid = key($studiogroup->leveldata['contentslevels']);
        $activityid = key($studiogroup->leveldata['contentslevels'][$blockid]);
        $content1id = key($studiogroup->leveldata['contentslevels'][$blockid][$activityid]);

        $contententry1 = array(
                'name' => 'The new content 1',
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.youtube.com/watch?v=R4XSeW4B5Rg',
                'urltitle' => 'Vesica Timeline',
                'visibility' => 0 - $group1->id,
                'description' => 'os rainbow',
                'tags' => array('Communist', 'Socialist', 'Democrat'),
                'ownership' => 0,
                'sid' => 0 // For a new content.
        );
        $contententry2 = array(
                'name' => 'The new content 2',
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.youtube.com/watch?v=R4XSeW4B5Rg',
                'urltitle' => 'Vesica Timeline',
                'visibility' => 0 - $group2->id,
                'description' => 'os rainbow',
                'tags' => array('Communist', 'Socialist', 'Democrat'),
                'ownership' => 0,
                'sid' => 0 // For a new content.
        );

        $content1 = content::create(
                $studiogroup->id, $student1->id, 3,
                $studiogroup->leveldata['contentslevels'][$blockid][$activityid][$content1id], $contententry1,
                null, null, $this->cm);
        $content2 = content::create(
                $studiogroup->id, $student2->id, 3,
                $studiogroup->leveldata['contentslevels'][$blockid][$activityid][$content1id], $contententry2,
                null, null, $this->cm);

        $this->setUser($student1);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $posts->check_access($content1));
        $this->setUser($student2);
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $posts->check_access($content1));
        $this->setUser($teacher);
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $posts->check_access($content2));
    }

    /**
     * Test check get document.
     */
    public function test_check_get_permission() {
        $posts = new posts();

        // Check as admin.
        $this->setAdminUser();

        // Deleted if trying to get  belong to empty content since it will not indexed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $posts->check_access($this->emptycontent));
        // Deleted if trying to get  that not existed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $posts->check_access(0));
        // Check return granted when get url  and folder .
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $posts->check_access($this->sharedcontent));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $posts->check_access($this->privatecontent));

        // Check as owner user.
        $this->setUser($this->user);

        // Deleted if trying to get  belong to empty content since it will not indexed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $posts->check_access($this->emptycontent));
        // Deleted if trying to get  that not existed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $posts->check_access(0));
        // Check return granted when get url  and folder .
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $posts->check_access($this->sharedcontent));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $posts->check_access($this->privatecontent));

        // Check as non-owner user.
        // Create Users and enroll to course.
        $anotheruser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user(
                $anotheruser->id, $this->course->id, $this->studentroleid, 'manual');
        $this->setUser($anotheruser);

        // Deleted if trying to get  belong to empty content since it will not indexed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $posts->check_access($this->emptycontent));
        // Deleted if trying to get  that not existed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $posts->check_access(0));
        // Check return granted when get url , folder  and deny when get private .
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $posts->check_access($this->sharedcontent));
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $posts->check_access($this->privatecontent));
    }

    /**
     * Test check file index.
     */
    public function test_check_file_index() {
        global $CFG;

        $posts = new posts();

        $doc = $posts->get_document((object)[
            'id' => $this->filecontent,
            'name' => 'Sample name 1',
            'description' => 'Sample description 1',
            'openstudioid' => $this->cm->instance,
            'userid' => $this->user->id,
            'timemodified' => 0,
        ]);

        $postsareaid = \core_search\manager::generate_areaid('mod_openstudio', 'posts');
        $searcharea = \core_search\manager::get_search_area($postsareaid);

        $this->assertCount(0, $doc->get_files());

        $searcharea->attach_files($doc);
        $files = $doc->get_files();

        $this->assertCount(1, $files);

        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            switch ($file->get_filearea()) {
                case posts::FILEAREA['CONTENT']:
                    $this->assertEquals('test.txt', $file->get_filename());
                    $this->assertEquals('TEST', $file->get_content());
                    break;

                default:
                    break;
            }
        }
    }

    /**
     * Converts recordset to array, indexed numberically (0, 1, 2).
     *
     * @param \moodle_recordset $rs Record set to convert
     * @return \stdClass[] Array of converted records
     */
    protected static function recordset_to_array(moodle_recordset $rs) {
        $result = [];
        foreach ($rs as $rec) {
            $result[] = $rec;
        }
        $rs->close();
        return $result;
    }

    /**
     * Create file from pathname.
     *
     * @param string $path file path
     * @return object file created.
     */
    protected function create_file($path) {
        static $itemid;
        $itemid++;
        $this->file = new \stdClass();
        $this->file->filearea = 'content';
        $this->file->filename = basename($path);
        $this->file->filepath = '/';
        $this->file->sortorder = 0;
        $this->file->author = $this->user->firstname . ' ' . $this->user->lastname;
        $this->file->license = 'allrightsreserved';
        $this->file->datemodified = time();
        $this->file->datecreated = time();
        $this->file->component = 'mod_openstudio';
        $this->file->itemid = $itemid;
        $context = \context_module::instance($this->cm->id);
        $this->file->contextid = $context->id;
        $fs = get_file_storage();
        return $fs->create_file_from_pathname($this->file, $path);
    }
}
