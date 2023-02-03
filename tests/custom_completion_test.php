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

        // Enroll our students in the course.
        $this->getDataGenerator()->enrol_user($this->users->students->one->id, $this->course->id, $this->studentroleid);

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');
    }

    protected function tearDown(): void {
        global $CFG;
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
        $CFG->enablecompletion = false;
    }

    /**
     * Test for get_defined_custom_rules().
     *
     * @covers ::get_defined_custom_rules
     */
    public function test_get_defined_custom_rules(): void {
        $rules = custom_completion::get_defined_custom_rules();
        $this->assertCount(3, $rules);
        $this->assertEquals(
                ['completionposts', 'completioncomments', 'completionpostscomments'],
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
        $this->assertObjectHasAttribute('id', $content1);
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
        $this->assertObjectHasAttribute('id', $content2);
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
        $this->assertObjectHasAttribute('id', $content1);
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
        $this->assertObjectHasAttribute('id', $content1);

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
        $this->assertObjectHasAttribute('id', $content2);
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
        $this->assertObjectHasAttribute('id', $content1);

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
        $this->assertObjectHasAttribute('id', $content1);
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
        $this->assertObjectHasAttribute('id', $content1);

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
}
