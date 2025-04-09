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

namespace mod_openstudio;

use advanced_testcase;
use cm_info;
use mod_openstudio\completion\custom_completion;
use mod_openstudio\local\api\comments;
use mod_openstudio\local\api\content;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

/**
 * Class for unit testing mod_openstudio/custom_completion.
 *
 * @package   mod_openstudio
 * @copyright 2022 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_openstudio\completion\custom_completion
 */
class custom_completion_test extends advanced_testcase {

    /** @var \stdClass - Users collection object. */
    protected $users;
    /** @var \stdClass - Course object. */
    protected $course;
    /** @var \mod_openstudio_generator - Generator of mod_openstudio */
    protected $generator; // Contains mod_openstudio specific data generator functions.
    /** @var int - User role ID. */
    protected $studentroleid = 5;

    /**
     * Sets up our testcases.
     */
    protected function setUp(): void {
        global $CFG;

        $this->resetAfterTest(true);

        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        $CFG->enablecompletion = true;

        // Create course.
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

        // Create Users.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user([
                'email' => 'student1@ouunittest.com',
                'username' => 'student1',
        ]);
        $this->users->students->two = $this->getDataGenerator()->create_user([
                'email' => 'student2@ouunittest.com',
                'username' => 'student2',
        ]);
        $this->users->students->three = $this->getDataGenerator()->create_user([
            'email' => 'student3@ouunittest.com',
            'username' => 'student3',
        ]);

        // Enroll our students in the course.
        $this->getDataGenerator()->enrol_user($this->users->students->one->id, $this->course->id, $this->studentroleid);

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');
    }

    protected function tearDown(): void {
        global $CFG;
        $this->course = '';
        $this->generator = '';
        $CFG->enablecompletion = false;
    }

    /**
     * Test for get_defined_custom_rules().
     *
     * @covers ::get_defined_custom_rules
     */
    public function test_get_defined_custom_rules(): void {
        $rules = custom_completion::get_defined_custom_rules();
        $this->assertCount(6, $rules);
        $this->assertEquals(
                ['completiontrackingrestricted', 'completionposts', 'completioncomments', 'completionpostscomments', 'completionwordcountmin', 'completionwordcountmax'],
                $rules
        );
    }

    /**
     * Test completion state when creating contents and folders.
     */
    public function test_completion_posts_passed(): void {
        global $DB;
        $this->resetAfterTest(true);

        $expectposts = 1;
        $key = custom_completion::COMPLETION_POSTS;
        $studentid = $this->users->students->one->id;

        // Create generic studios.
        $openstudio = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                $key => $expectposts,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $this->assertEquals($expectposts, $openstudio->{$key});

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey($key, $cm->customdata->customcompletionrules);

        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 1 will create folder 1, we don't count folders.
        $folder1id = $this->generator->create_folders([
                'openstudio' => 'OS1',
                'name' => 'TestSet',
                'description' => 'foo',
                'userid' => $studentid,
        ]);
        $this->assertIsInt($folder1id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 1 will create content 1.
        $content1 = $this->generator->generate_content_data($openstudio, $studentid,
                $this->generator->generate_single_data_array());
        $this->assertObjectHasProperty('id', $content1);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($key));

        // Test we do not count deleted contents.
        $deleteddata = [
                'deletedby' => $studentid,
                'deletedtime' => time(),
        ];
        $DB->update_record('openstudio_contents', (object) array_merge($deleteddata, ['id' => $content1->id]));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 1 will create content 2.
        $content2 = $this->generator->generate_content_data($openstudio, $studentid,
                $this->generator->generate_single_data_array());
        $this->assertObjectHasProperty('id', $content2);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($key));

        // Try to test with API.
        content::delete($studentid, $content2->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));
    }

    /**
     * Test completion state when creating contents and folders.
     */
    public function test_completion_posts_passed_with_folders_has_contents(): void {
        $this->resetAfterTest(true);
        $this->markTestSkipped('Folder is deleted should be deleted all contents inside it.');

        $expectposts = 2;
        $key = custom_completion::COMPLETION_POSTS;
        $studentid = $this->users->students->one->id;

        // Create generic studios.
        $openstudio = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                $key => $expectposts,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $this->assertEquals($expectposts, $openstudio->{$key});

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey($key, $cm->customdata->customcompletionrules);

        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 1 will create folder 1, we don't count folders.
        $folderdata = [
                'openstudio' => 'OS1',
                'name' => 'TestSet',
                'description' => 'foo',
                'userid' => $studentid,
        ];
        $folder1id = $this->generator->create_folders($folderdata);
        $this->assertIsInt($folder1id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 1 will create content 1.
        $content1 = $this->generator->generate_content_data($openstudio, $studentid,
                $this->generator->generate_single_data_array());
        $this->assertObjectHasProperty('id', $content1);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // Now create content in folder.
        $contentdata = [
                'openstudio' => 'OS1',
                'visibility' => content::VISIBILITY_INFOLDERONLY,
                'userid' => $studentid,
                'name' => 'folder_content',
                'contenttype' => content::TYPE_TEXT,
                'description' => random_string(),
        ];
        $contentid = $this->generator->create_contents($contentdata);
        $content = content::get($contentid);
        $this->generator->create_folder_contents([
                'openstudio' => 'OS1',
                'folder' => $folderdata['name'],
                'content' => $content->name,
                'userid' => $studentid,
        ]);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($key));

        // Now delete folder, should remove + delete all contents in that folder => COMPLETION_INCOMPLETE.
        content::delete($studentid, $folder1id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));
    }

    /**
     * Test completion state when creating comments on contents.
     */
    public function test_completion_comments_passed(): void {
        global $DB;
        $this->resetAfterTest(true);

        $expectcomments = 2;
        $key = custom_completion::COMPLETION_COMMENTS;
        $studentid = $this->users->students->one->id;
        $student2id = $this->users->students->two->id;

        // Create generic studios.
        $openstudio = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                $key => $expectcomments,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $this->assertEquals($expectcomments, $openstudio->{$key});

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey($key, $cm->customdata->customcompletionrules);

        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 1 will create content 1.
        $content1 = $this->generator->generate_content_data($openstudio, $studentid,
                $this->generator->generate_single_data_array());
        $this->assertObjectHasProperty('id', $content1);

        $comment1id = $this->generator->create_comment((object) [
                'contentid' => $content1->id,
                'userid' => $studentid,
                'comment' => random_string(),
        ]);
        $this->assertIsInt($comment1id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 2 will create content 2.
        $content2 = $this->generator->generate_content_data($openstudio, $student2id,
                $this->generator->generate_single_data_array());
        $this->assertObjectHasProperty('id', $content2);
        // User 1 will comment on content 2 of user 2.
        $comment2id = $this->generator->create_comment((object) [
                'contentid' => $content2->id,
                'userid' => $studentid,
                'comment' => random_string(),
        ]);
        $this->assertIsInt($comment2id);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($key));

        // Test we do not count deleted comments.
        $deleteddata = [
                'deletedby' => $studentid,
                'deletedtime' => time(),
        ];
        $DB->update_record('openstudio_comments', (object) array_merge($deleteddata, ['id' => $comment1id]));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // Try to test with APIs.
        $comment3id = $this->generator->create_comment((object) [
                'contentid' => $content1->id,
                'userid' => $studentid,
                'comment' => random_string(),
        ]);
        $this->assertIsInt($comment3id);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($key));

        content::delete($studentid, $content1->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));
    }

    /**
     * Test completion state when creating comments on contents and folders.
     *
     * @depends test_completion_comments_passed
     */
    public function test_completion_comments_passed_on_folders(): void {
        $this->resetAfterTest(true);

        $expectcomments = 1;
        $key = custom_completion::COMPLETION_COMMENTS;
        $studentid = $this->users->students->one->id;

        // Create generic studios.
        $openstudio = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                $key => $expectcomments,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $this->assertEquals($expectcomments, $openstudio->{$key});

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey($key, $cm->customdata->customcompletionrules);

        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 1 will create folder as content.
        $folder1id = $this->generator->create_folders([
                'openstudio' => 'OS1',
                'name' => 'TestSet',
                'description' => 'foo',
                'userid' => $studentid,
        ]);
        $this->assertIsInt($folder1id);

        $comment1id = $this->generator->create_comment((object) [
                'contentid' => $folder1id,
                'userid' => $studentid,
                'comment' => random_string(),
        ]);
        $this->assertIsInt($comment1id);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($key));
    }

    /**
     * Test completion state when creating contents and folders and its comments.
     */
    public function test_completion_posts_and_comments_passed(): void {
        global $DB;
        $this->resetAfterTest(true);

        $expectposts = 1;
        $expectcomments = 3;
        $expecttotal = $expectposts + $expectcomments;
        $key = custom_completion::COMPLETION_POSTS_COMMENTS;
        $studentid = $this->users->students->one->id;

        // Create generic studios.
        $openstudio = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                $key => $expecttotal,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $this->assertEquals($expecttotal, $openstudio->{$key});

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey($key, $cm->customdata->customcompletionrules);

        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 1 will create content 1.
        $content1 = $this->generator->generate_content_data($openstudio, $studentid,
                $this->generator->generate_single_data_array());
        $this->assertObjectHasProperty('id', $content1);

        // User 1 will create a comment.
        $comment1id = $this->generator->create_comment((object) [
                'contentid' => $content1->id,
                'userid' => $studentid,
                'comment' => random_string(),
        ]);
        $this->assertIsInt($comment1id);
        // Only have 1 posts, and 1 comments now.
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // Now we will create 2 comments to pass the completion.
        $comment2id = $this->generator->create_comment((object) [
                'contentid' => $content1->id,
                'userid' => $studentid,
                'comment' => random_string(),
        ]);
        $this->assertIsInt($comment2id);
        $comment3id = $this->generator->create_comment((object) [
                'contentid' => $content1->id,
                'userid' => $studentid,
                'comment' => random_string(),
        ]);
        $this->assertIsInt($comment3id);
        // Now we have 1 posts, 3 comments. Then it should pass.
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($key));

        // Test we do not count deleted comments.
        $deleteddata = [
                'deletedby' => $studentid,
                'deletedtime' => time(),
        ];
        $DB->update_record('openstudio_comments', (object) array_merge($deleteddata, ['id' => $comment1id]));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));
    }

    /**
     * Test overall all states are passed.
     *
     * @depends test_get_defined_custom_rules
     * @depends test_completion_posts_passed
     * @depends test_completion_comments_passed
     * @depends test_completion_posts_and_comments_passed
     * @covers  activity_custom_completion::get_overall_completion_state
     */
    public function test_completion_all_passed(): void {
        $this->resetAfterTest(true);

        $expectposts = 1;
        $expectcomments = 3;
        $expecttotal = 4;
        $studentid = $this->users->students->one->id;

        // Create generic studios.
        $openstudio = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                custom_completion::COMPLETION_POSTS => $expectposts,
                custom_completion::COMPLETION_COMMENTS => $expectcomments,
                custom_completion::COMPLETION_POSTS_COMMENTS => $expecttotal,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $this->assertEquals($expectposts, $openstudio->{custom_completion::COMPLETION_POSTS});
        $this->assertEquals($expectcomments, $openstudio->{custom_completion::COMPLETION_COMMENTS});
        $this->assertEquals($expecttotal, $openstudio->{custom_completion::COMPLETION_POSTS_COMMENTS});

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey(custom_completion::COMPLETION_POSTS, $cm->customdata->customcompletionrules);
        $this->assertArrayHasKey(custom_completion::COMPLETION_COMMENTS, $cm->customdata->customcompletionrules);
        $this->assertArrayHasKey(custom_completion::COMPLETION_POSTS_COMMENTS, $cm->customdata->customcompletionrules);

        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_overall_completion_state());

        // User 1 will create content 1.
        $content1 = $this->generator->generate_content_data($openstudio, $studentid,
                $this->generator->generate_single_data_array());
        $this->assertObjectHasProperty('id', $content1);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_overall_completion_state());

        // Check comments state.
        $comment1id = $this->generator->create_comment((object) [
                'contentid' => $content1->id,
                'userid' => $studentid,
                'comment' => random_string(),
        ]);
        $this->assertIsInt($comment1id);
        // Now we will create 2 comments to pass the completion.
        $comment2id = $this->generator->create_comment((object) [
                'contentid' => $content1->id,
                'userid' => $studentid,
                'comment' => random_string(),
        ]);
        $this->assertIsInt($comment2id);
        // But 1 content + 2 comments are not passed total state (need 4 records).
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_overall_completion_state());

        // Now we create a comment 3 to pass total state.
        $comment3id = $this->generator->create_comment((object) [
                'contentid' => $content1->id,
                'userid' => $studentid,
                'comment' => random_string()
        ]);
        $this->assertIsInt($comment3id);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_overall_completion_state());
        $this->assertEquals(COMPLETION_COMPLETE, $completioninfo->internal_get_state($cm, $studentid, null));
    }

    /**
     * Test comment completion when user B delete root comment and all replies include user A's comment
     * who already completed the activity.
     *
     * @depends test_completion_comments_passed
     */
    public function test_completion_comments_passed_when_userb_delete_usera_comments(): void {
        $this->resetAfterTest(true);

        $expectcomments = 1;
        $key = custom_completion::COMPLETION_COMMENTS;
        $studentid = $this->users->students->one->id;
        $student2id = $this->users->students->two->id;

        // Create generic studios.
        $openstudio = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                $key => $expectcomments,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $this->assertEquals($expectcomments, $openstudio->{$key});

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey($key, $cm->customdata->customcompletionrules);

        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 1 will create content 1.
        $content1 = $this->generator->generate_content_data($openstudio, $student2id,
                $this->generator->generate_single_data_array());
        $this->assertObjectHasProperty('id', $content1);

        // User 2 will create a comment.
        $comment1id = $this->generator->create_comment((object) [
                'contentid' => $content1->id,
                'userid' => $student2id,
                'comment' => random_string()
        ]);
        $this->assertIsInt($comment1id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // User 1 will comment on content 1 of user 2.
        $comment2id = $this->generator->create_comment((object) [
                'contentid' => $content1->id,
                'userid' => $studentid,
                'comment' => random_string(),
                'inreplyto' => $comment1id,
        ]);
        $this->assertIsInt($comment2id);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($key));

        // Delete root comment of student 2.
        comments::delete($comment1id, $student2id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));
    }

    public function test_update_completion_success(): void {
        global $CFG;
        $this->resetAfterTest(true);

        $studentid = $this->users->students->one->id;

        $openstudio = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                custom_completion::COMPLETION_COMMENTS => 1,
        ]);

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertTrue(custom_completion::update_completion($cm, $studentid, COMPLETION_UNKNOWN));

        $CFG->enablecompletion = false;
        $this->assertFalse(custom_completion::update_completion($cm, $studentid, COMPLETION_UNKNOWN));
    }

    public function test_update_completion_false_if_tracking_manual(): void {
        $this->resetAfterTest(true);

        $studentid = $this->users->students->one->id;

        $openstudio = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
                'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertFalse(custom_completion::update_completion($cm, $studentid, COMPLETION_UNKNOWN));
    }

    /**
     * Test completion state when select both completion word count and posts
     *
     * @covers \mod_openstudio\completion\custom_completion::get_state
     */
    public function test_completion_word_count_with_completion_posts(): void {
        $this->resetAfterTest(true);

        $expectedposts = 1;
        $expectwordcountmin = 5;
        $expectwordcountmax = 10;
        $keycompletionpost = custom_completion::COMPLETION_POSTS;
        $keycompletionwordcountmin = custom_completion::COMPLETION_WORD_COUNT_MIN;
        $keycompletionwordcountmax = custom_completion::COMPLETION_WORD_COUNT_MAX;
        $studentid = $this->users->students->one->id;
        $student2id = $this->users->students->two->id;
        $student3id = $this->users->students->three->id;

        // Create generic studios.
        $openstudio = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS1',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            $keycompletionpost => $expectedposts,
            $keycompletionwordcountmin => $expectwordcountmin,
            $keycompletionwordcountmax => $expectwordcountmax,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $this->assertEquals($expectedposts, $openstudio->{$keycompletionpost});
        $this->assertEquals($expectwordcountmin, $openstudio->{$keycompletionwordcountmin});
        $this->assertEquals($expectwordcountmax, $openstudio->{$keycompletionwordcountmax});

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey($keycompletionpost, $cm->customdata->customcompletionrules);
        $this->assertArrayHasKey($keycompletionwordcountmin, $cm->customdata->customcompletionrules);

        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $customcompletionstudent2 = new custom_completion($cm, $student2id,
                $completioninfo->get_core_completion_state($cm, $student2id));
        $customcompletionstudent3 = new custom_completion($cm, $student3id,
                $completioninfo->get_core_completion_state($cm, $student3id));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($keycompletionpost));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletionstudent2->get_state($keycompletionpost));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletionstudent3->get_state($keycompletionpost));

        // User 1 will create content with description not meet min word count.
        $content1 = $this->generator->generate_content_data($openstudio, $studentid,
            [
                'name' => 'Test Not Meet Min Word Count',
                'weblink' => 'http://www.open.ac.uk/',
                'urltitle' => 'Vesica Timeline',
                'visibility' => content::VISIBILITY_MODULE,
                'description' => 'The Best YouTube',
                'tags' => ['Stark', 'Lannister', 'Targereyen'],
                'ownership' => 0,
                'sid' => 0,
            ]);
        $this->assertObjectHasProperty('id', $content1);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($keycompletionpost));

        // User 2 will create content with description meet min word count.
        $content2 = $this->generator->generate_content_data($openstudio, $student2id,
            [
                'name' => 'Test Meet Min Word Count',
                'weblink' => 'http://www.open.ac.uk/',
                'urltitle' => 'Vesica Timeline',
                'visibility' => content::VISIBILITY_MODULE,
                'description' => 'The Best YouTube For Me You',
                'tags' => ['Stark', 'Lannister', 'Targereyen'],
                'ownership' => 0,
                'sid' => 0,
            ]);
        $this->assertObjectHasProperty('id', $content2);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletionstudent2->get_state($keycompletionwordcountmin));

        // User 3 will create content with description not met max word count.
        $content3 = $this->generator->generate_content_data($openstudio, $student3id,
            [
                'name' => 'Test Meet Min Word Count',
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.open.ac.uk/',
                'urltitle' => 'Vesica Timeline',
                'visibility' => content::VISIBILITY_MODULE,
                'description' => 'The Best YouTube For Me You be My Girl Girl Girl',
                'tags' => ['Stark', 'Lannister', 'Targereyen'],
                'ownership' => 0,
                'sid' => 0,
            ]);
        $this->assertObjectHasProperty('id', $content3);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletionstudent3->get_state($keycompletionwordcountmax));
    }

    /**
     * Test completion state when select both completion word count and comments.
     *
     * @covers \mod_openstudio\completion\custom_completion::get_state
     */
    public function test_completion_word_count_min_with_completion_comments(): void {
        $this->resetAfterTest(true);

        $expectcomments = 1;
        $expectwordcountmin = 5;
        $expectwordcountmax = 10;
        $keycompletioncomments = custom_completion::COMPLETION_COMMENTS;
        $keycompletionwordcountmin = custom_completion::COMPLETION_WORD_COUNT_MIN;
        $keycompletionwordcountmax = custom_completion::COMPLETION_WORD_COUNT_MAX;
        $studentid = $this->users->students->one->id;

        // Create generic studios.
        $openstudio = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS1',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            $keycompletioncomments => $expectcomments,
            $keycompletionwordcountmin => $expectwordcountmin,
            $keycompletionwordcountmax => $expectwordcountmax,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($keycompletioncomments));

        // User 1 will create content.
        $content1 = $this->generator->generate_content_data($openstudio, $studentid,
            $this->generator->generate_single_data_array());
        $this->assertObjectHasProperty('id', $content1);

        // User 1 have the comment but so sort => incomplete.
        $comment1id = $this->generator->create_comment((object) [
            'contentid' => $content1->id,
            'userid' => $studentid,
            'comment' => "This is the",
        ]);
        $this->assertIsInt($comment1id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($keycompletioncomments));

        // User 1 have the comment but so long => incomplete.
        $comment2id = $this->generator->create_comment((object) [
            'contentid' => $content1->id,
            'userid' => $studentid,
            'comment' => "This is the comment that not meet word count completion because so long",
        ]);
        $this->assertIsInt($comment2id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($keycompletioncomments));

        // User 1 have the comment that meet condition word count min/max.
        $comment3id = $this->generator->create_comment((object) [
            'contentid' => $content1->id,
            'userid' => $studentid,
            'comment' => "This is the comment that meet word count",
        ]);
        $this->assertIsInt($comment3id);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($keycompletioncomments));
    }

    /**
     * Test completion state when select both completion word count and comments.
     *
     * @covers \mod_openstudio\completion\custom_completion::get_state
     */
    public function test_completion_word_count_min_with_completion_posts_and_comments(): void {
        $this->resetAfterTest(true);

        $expectcomments = 2;
        $expectposts = 1;
        $expecttotal = $expectcomments + $expectposts;
        $expectwordcountmin = 5;
        $expectwordcountmax = 10;
        $keycompletiontotal = custom_completion::COMPLETION_POSTS_COMMENTS;
        $keycompletionwordcountmin = custom_completion::COMPLETION_WORD_COUNT_MIN;
        $keycompletionwordcountmax = custom_completion::COMPLETION_WORD_COUNT_MAX;
        $studentid = $this->users->students->one->id;

        // Create generic studios.
        $openstudio = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS1',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            $keycompletiontotal => $expecttotal,
            $keycompletionwordcountmin => $expectwordcountmin,
            $keycompletionwordcountmax => $expectwordcountmax,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($keycompletiontotal));

        // User 1 will create content pass description word count completion,
        // user 1 need 2 comment or 2 post pass word count for complete.
        $content1 = $this->generator->generate_content_data($openstudio, $studentid,
            $this->generator->generate_single_data_array());
        $this->assertObjectHasProperty('id', $content1);

        // User 1 create comment not pass word count.
        $comment1id = $this->generator->create_comment((object) [
            'contentid' => $content1->id,
            'userid' => $studentid,
            'comment' => "This is the",
        ]);
        $this->assertIsInt($comment1id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($keycompletiontotal));

        // User 1 create comment pass word count.
        $comment2id = $this->generator->create_comment((object) [
            'contentid' => $content1->id,
            'userid' => $studentid,
            'comment' => "This is the comment that meet word count",
        ]);
        $this->assertIsInt($comment2id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($keycompletiontotal));

        $comment3id = $this->generator->create_comment((object) [
            'contentid' => $content1->id,
            'userid' => $studentid,
            'comment' => "This is the second comment that meet word count",
        ]);
        $this->assertIsInt($comment3id);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($keycompletiontotal));
    }

    /**
     * Test completion state when tracking posts in "My Activities" only.
     *
     * @covers \mod_openstudio\completion\custom_completion::get_state
     */
    public function test_completion_contents_completion_tracking_restricted(): void {
        $this->resetAfterTest(true);

        $studentid = $this->users->students->one->id;
        $key = custom_completion::COMPLETION_POSTS;
        $expectposts = 1;
        // Create generic studios.
        $openstudio = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            custom_completion::COMPLETION_TRACKING_RESTRICTED => 1,
            $key => $expectposts,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $level1 = reset($openstudio->leveldata['contentslevels']);
        $level2 = reset($level1);
        $level3 = reset($level2);

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey($key, $cm->customdata->customcompletionrules);
        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state(custom_completion::COMPLETION_POSTS));


        // Incomplete because user 1 create content not in My activities.
        $contentdata = [
            'openstudio' => 'OS2',
            'userid' => $studentid,
            'contenttype' => content::TYPE_TEXT,
            'name' => random_string(),
            'description' => random_string(),
        ];
        $this->generator->create_contents($contentdata);
        $this->assertEquals(COMPLETION_INCOMPLETE,
                $customcompletion->get_state(custom_completion::COMPLETION_TRACKING_RESTRICTED));

        // User 1 will create content in My Activities, and it will be counted.
        $contentdata = [
            'openstudio' => 'OS2',
            'userid' => $studentid,
            'name' => random_string(),
            'description' => random_string(),
            'levelcontainer' => 3,
            'levelid' => $level3,
        ];
        $this->generator->create_contents($contentdata);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state(custom_completion::COMPLETION_POSTS));
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state(custom_completion::COMPLETION_TRACKING_RESTRICTED));
    }

    /**
     * Test completion state when tracking comments in "My Activities" only.
     *
     * @covers \mod_openstudio\completion\custom_completion::get_state
     */
    public function test_completion_comments_completion_tracking_restricted(): void {
        $this->resetAfterTest(true);

        $studentid = $this->users->students->one->id;
        $key = custom_completion::COMPLETION_COMMENTS;
        $expectcomments = 1;
        // Create generic studios.
        $openstudio = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            custom_completion::COMPLETION_TRACKING_RESTRICTED => 1,
            $key => $expectcomments,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $level1 = reset($openstudio->leveldata['contentslevels']);
        $level2 = reset($level1);
        $level3 = reset($level2);

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey($key, $cm->customdata->customcompletionrules);
        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state(custom_completion::COMPLETION_COMMENTS));

        // Incomplete because user 1 create content not in My activities.
        $contentdata = [
            'openstudio' => 'OS2',
            'userid' => $studentid,
            'contenttype' => content::TYPE_TEXT,
            'name' => random_string(),
            'description' => random_string(),
        ];
        $content1 = $this->generator->create_contents($contentdata);
        // User 1 comments in content1 => incomplete.
        $this->generator->create_comment((object) [
            'contentid' => $content1,
            'userid' => $studentid,
            'comment' => "This is my comment",
        ]);
        $this->assertEquals(COMPLETION_INCOMPLETE,
                $customcompletion->get_state(custom_completion::COMPLETION_TRACKING_RESTRICTED));

        // User 1 will create content in My Activities, and it will be counted.
        $contentdata = [
            'openstudio' => 'OS2',
            'userid' => $studentid,
            'name' => random_string(),
            'description' => random_string(),
            'levelcontainer' => 3,
            'levelid' => $level3,
        ];
        $content2 = $this->generator->create_contents($contentdata);
        // User 1 comments in content2 => complete.
        $comment2id = $this->generator->create_comment((object) [
            'contentid' => $content2,
            'userid' => $studentid,
            'comment' => "This is my comment",
        ]);
        $this->assertIsInt($comment2id);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state(custom_completion::COMPLETION_COMMENTS));
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state(custom_completion::COMPLETION_TRACKING_RESTRICTED));
    }

    /**
     * Test completion state when tracking both posts and comments in "My Activities" only.
     *
     * @covers \mod_openstudio\completion\custom_completion::get_state
     */
    public function test_completion_posts_and_comments_completion_tracking_restricted(): void {
        $this->resetAfterTest(true);

        $studentid = $this->users->students->one->id;
        $key = custom_completion::COMPLETION_POSTS_COMMENTS;
        $expectcomments = 2;
        $expectposts = 1;
        $expecttotal = $expectcomments + $expectposts;
        // Create generic studios.
        $openstudio = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            custom_completion::COMPLETION_TRACKING_RESTRICTED => 1,
            $key => $expecttotal,
        ]);
        $openstudio->leveldata = $this->generator->create_mock_levels($openstudio->id);

        $level1 = reset($openstudio->leveldata['contentslevels']);
        $level2 = reset($level1);
        $level3 = reset($level2);

        $cm = cm_info::create(get_coursemodule_from_id('openstudio', $openstudio->cmid));
        $this->assertArrayHasKey($key, $cm->customdata->customcompletionrules);
        $completioninfo = new \completion_info($cm->get_course());
        $customcompletion = new custom_completion($cm, $studentid, $completioninfo->get_core_completion_state($cm, $studentid));
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));

        // Incomplete because user 1 create content not in My activities.
        $contentdata = [
            'openstudio' => 'OS2',
            'userid' => $studentid,
            'contenttype' => content::TYPE_TEXT,
            'name' => random_string(),
            'description' => random_string(),
        ];
        $content1 = $this->generator->create_contents($contentdata);
        // User 1 comments in content1 => incomplete.
        $this->generator->create_comment((object) [
            'contentid' => $content1,
            'userid' => $studentid,
            'comment' => "This is my comment",
        ]);
        $this->assertEquals(COMPLETION_INCOMPLETE,
                $customcompletion->get_state(custom_completion::COMPLETION_TRACKING_RESTRICTED));

        // User 1 will create content in My Activities, and it will be counted.
        $contentdata = [
            'openstudio' => 'OS2',
            'userid' => $studentid,
            'name' => random_string(),
            'description' => random_string(),
            'levelcontainer' => 3,
            'levelid' => $level3,
        ];
        $content2 = $this->generator->create_contents($contentdata);
        // The first comment by User 1 is incomplete as it doesn't meet the condition.
        $comment2id = $this->generator->create_comment((object) [
            'contentid' => $content2,
            'userid' => $studentid,
            'comment' => "This is my first comment",
        ]);
        $this->assertIsInt($comment2id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state($key));
        $this->assertEquals(COMPLETION_INCOMPLETE,
                $customcompletion->get_state(custom_completion::COMPLETION_TRACKING_RESTRICTED));

        // The second comment from User 1 meets the condition, so it is marked as complete.
        $comment3id = $this->generator->create_comment((object) [
            'contentid' => $content2,
            'userid' => $studentid,
            'comment' => "This is my second comment",
        ]);
        $this->assertIsInt($comment3id);
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state($key));
        $this->assertEquals(COMPLETION_COMPLETE, $customcompletion->get_state(custom_completion::COMPLETION_TRACKING_RESTRICTED));
    }
}
