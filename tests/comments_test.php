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

class comments_testcase extends \advanced_testcase {

    protected $users;
    protected $file;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or slots.
    protected $singleentrydata;
    protected $contentdata;
    protected $teacherroleid;
    protected $studentroleid;
    protected $pinboardcontents;

    /**
     * Sets up our fixtures.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        $this->teacherroleid = 3;
        $this->studentroleid = 5;

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                array('email' => 'student1@ouunittest.com', 'username' => 'student1'));
        $this->users->students->two = $this->getDataGenerator()->create_user(
                array('email' => 'student2@ouunittest.com', 'username' => 'student2'));
        $this->users->students->three = $this->getDataGenerator()->create_user(
                array('email' => 'student3@ouunittest.com', 'username' => 'student3'));
        $this->users->students->four = $this->getDataGenerator()->create_user(
                array('email' => 'student4@ouunittest.com', 'username' => 'student4'));
        $this->users->students->five = $this->getDataGenerator()->create_user(
                array('email' => 'student5@ouunittest.com', 'username' => 'student5'));
        $this->users->students->six = $this->getDataGenerator()->create_user(
                array('email' => 'student6@ouunittest.com', 'username' => 'student6'));
        $this->users->students->seven = $this->getDataGenerator()->create_user
        (array('email' => 'student7@ouunittest.com', 'username' => 'student7'));
        $this->users->students->eight = $this->getDataGenerator()->create_user(
                array('email' => 'student8@ouunittest.com', 'username' => 'student8'));
        $this->users->students->nine = $this->getDataGenerator()->create_user(
                array('email' => 'student9@ouunittest.com', 'username' => 'student9'));
        $this->users->students->ten = $this->getDataGenerator()->create_user(
                array('email' => 'student10@ouunittest.com', 'username' => 'student10'));
        $this->users->teachers = new \stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                array('email' => 'teacher1@ouunittest.com', 'username' => 'teacher1'));
        $this->users->teachers->two = $this->getDataGenerator()->create_user(
                array('email' => 'teacher2@ouunittest.com', 'username' => 'teacher2'));

        // Enroll our students and teacher (users) in the course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->one->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->two->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->three->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->four->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->five->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->six->id, $this->course->id, $this->studentroleid, 'manual');
        // Student 7 is NOT enrolled in any course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->eight->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->nine->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->ten->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->one->id, $this->course->id, $this->teacherroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->two->id, $this->course->id, $this->teacherroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

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
     * Tests the mod_openstudio\local\api\comments::create() function
     */
    public function test_comments_api_create() {
        $this->resetAfterTest(true);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);

        $this->assertNotEquals(false, \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->four->id, 'Fire and Blood'));
        $this->assertNotEquals(false, \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->three->id, 'Winter is Coming'));
        $this->assertGreaterThan(1, \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->three->id, 'Winter is Coming'));
    }

    /**
     * Tests the mod_openstudio\local\api\comments::delete() and mod_openstudio\local\api\comments::delete_all() functions
     */
    public function test_comments_api_delete() {
        $this->resetAfterTest(true);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);

        $comment1 = \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->four->id, 'Fire and Blood');
        \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->three->id, 'Winter is Coming');
        \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->three->id, 'Innovation at Work');
        \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->two->id, 'Experience the Innovation');
        \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->one->id, 'Keep calm and carry on');

        $this->assertEquals(false, \mod_openstudio\local\api\comments::delete($comment1, $this->users->students->three->id, true));
        $this->assertEquals(true, \mod_openstudio\local\api\comments::delete($comment1, $this->users->students->three->id));
        $this->assertEquals(true, \mod_openstudio\local\api\comments::delete_all(
                $this->contentdata->id, $this->users->students->three->id));
        $this->assertEquals(0, \mod_openstudio\local\api\comments::total_for_user($this->studiolevels->id,
                $this->users->students->three->id));
    }

    /**
     * Tests all the get and count functions
     */
    public function test_comments_api_get() {
        $this->resetAfterTest(true);

        current($this->studiolevels->leveldata['contentslevels']);
        $blockid = key($this->studiolevels->leveldata['contentslevels']);

        current($this->studiolevels->leveldata['contentslevels'][$blockid]);
        $activityid = key($this->studiolevels->leveldata['contentslevels'][$blockid]);

        current($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);
        $contentid = key($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);

        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);

        $content2 = \mod_openstudio\local\api\content::create(
                $this->studiolevels->id,  $this->users->students->three->id, 3,
                $this->studiolevels->leveldata['contentslevels'][$blockid][$activityid][$contentid], $this->singleentrydata);

        $comment1 = \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->four->id, 'Fire and Blood');
        \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->three->id, 'Winter is Coming');
        \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->three->id, 'Innovation at Work');
        \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->two->id, 'Experience the Innovation');
        \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->one->id, 'Keep calm and carry on');
        \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->one->id, 'Keep calm and carry on 2');
        \mod_openstudio\local\api\comments::create(
                $this->contentdata->id, $this->users->students->one->id, 'Keep calm and carry on 3');
        \mod_openstudio\local\api\comments::create(
                $content2, $this->users->students->three->id, 'Winter is Coming');
        \mod_openstudio\local\api\comments::create(
                $content2, $this->users->students->three->id, 'Innovation at Work');

        // Get a real comment and check we have the correct one.
        $getcomment = \mod_openstudio\local\api\comments::get($comment1);
        $this->assertNotEquals(false, $getcomment);
        $this->assertEquals('Fire and Blood', $getcomment->commenttext);
        $this->assertEquals($this->users->students->four->firstname, $getcomment->firstname);
        // Try and get a non-existant comment.
        $this->assertEquals(false, \mod_openstudio\local\api\comments::get(777));

        // Check that getting all comments for this content gets the correct number.
        $this->assertEquals(7, iterator_count(\mod_openstudio\local\api\comments::get_for_content($this->contentdata->id)));
        // Check that limiting the results works correctly.
        $this->assertEquals(4,
                iterator_count(\mod_openstudio\local\api\comments::get_for_content($this->contentdata->id, null, 4)));

        // Delete a comment, to check that $withdeleted works correctly.
        \mod_openstudio\local\api\comments::delete($comment1, $this->users->students->four->id);
        $this->assertFalse(\mod_openstudio\local\api\comments::get($comment1));
        $this->assertNotFalse(\mod_openstudio\local\api\comments::get($comment1, null, true));
        $this->assertEquals(6, iterator_count(\mod_openstudio\local\api\comments::get_for_content($this->contentdata->id)));
        $this->assertEquals(7,
                iterator_count(\mod_openstudio\local\api\comments::get_for_content($this->contentdata->id, null, 0, true)));
    }

    public function test_studio_api_comments_replies() {
        $this->resetAfterTest(true);
        $contentid1 = $this->generator->create_contents(array(
            'openstudio' => 'OS1',
            'userid' => $this->users->students->one->id,
            'name' => 'Slot1',
            'description' => random_string()
        ));
        $contentid2 = $this->generator->create_contents(array(
            'openstudio' => 'OS1',
            'userid' => $this->users->students->two->id,
            'name' => 'Slot2',
            'description' => random_string()
        ));
        $commentid = $this->generator->create_comment(array(
            'contentid' => $contentid1,
            'userid' => $this->users->students->four->id,
            'comment' => random_string()
        ));

        $replyid = \mod_openstudio\local\api\comments::create(
                $contentid1, $this->users->students->one->id, random_string(),
                null, null, null, $commentid);

        $reply = \mod_openstudio\local\api\comments::get($replyid);
        $this->assertEquals($commentid, $reply->inreplyto);

        // Cannot create a reply to a reply.
        $this->assertFalse(\mod_openstudio\local\api\comments::create(
                $contentid1, $this->users->students->three->id, random_string(),
                null, null, null, $replyid));
        // Cannot reply to a non-existent comment.
        $this->assertFalse(\mod_openstudio\local\api\comments::create(
                $contentid1, $this->users->students->three->id, random_string(),
                null, null, null, $replyid + 1));
        // Cannot create a reply on a different content to the comment.
        $this->assertFalse(\mod_openstudio\local\api\comments::create(
                $contentid2, $this->users->students->three->id, random_string(),
                null, null, null, $commentid));

    }

    public function test_total_for_content() {
        $this->resetAfterTest(true);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);

        // No comments to start with.
        $this->assertEquals(0, \mod_openstudio\local\api\comments::total_for_content($this->contentdata->id));

        $comment = (object) [
                'contentid' => $this->contentdata->id,
                'userid' => $this->users->students->four->id,
                'comment' => random_string()
        ];
        $comment->id = $this->generator->create_comment($comment);
        $this->generator->create_comment((object) [
                'contentid' => $this->contentdata->id,
                'userid' => $this->users->students->three->id,
                'comment' => random_string()
        ]);
        $this->generator->create_comment((object) [
                'contentid' => $this->contentdata->id,
                'userid' => $this->users->students->three->id,
                'comment' => random_string()
        ]);

        // 3 Comments created.
        $this->assertEquals(3, \mod_openstudio\local\api\comments::total_for_content($this->contentdata->id));

        $this->generator->create_comment((object) [
                'contentid' => $this->contentdata->id,
                'userid' => $this->users->students->three->id,
                'comment' => random_string(),
                'deleted' => true
        ]);

        // 4 comments, but the new one is deleted, so total should still be 3.
        $this->assertEquals(3, \mod_openstudio\local\api\comments::total_for_content($this->contentdata->id));
    }

    public function test_total_for_user() {
        $this->resetAfterTest(true);
        $studio2 = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS2'));
        $contentid1 = $this->generator->create_contents(array(
                'openstudio' => 'OS1',
                'userid' => $this->users->students->one->id,
                'name' => 'Content 1',
                'description' => random_string()
        ));
        $contentid2 = $this->generator->create_contents(array(
                'openstudio' => 'OS1',
                'userid' => $this->users->students->two->id,
                'name' => 'Content 2',
                'description' => random_string()
        ));
        // No comments yet.
        $this->assertEquals(0, \mod_openstudio\local\api\comments::total_for_user(
                $this->studiolevels->id, $this->users->students->one->id));
        $this->assertEquals(0, \mod_openstudio\local\api\comments::total_for_user(
                $this->studiolevels->id, $this->users->students->two->id));
        $this->assertEquals(0, \mod_openstudio\local\api\comments::total_for_user($studio2->id, $this->users->students->one->id));
        $this->assertEquals(0, \mod_openstudio\local\api\comments::total_for_user($studio2->id, $this->users->students->two->id));

        $this->generator->create_comment((object) [
                'contentid' => $contentid1,
                'userid' => $this->users->students->one->id,
                'comment' => random_string(),
        ]);
        $this->generator->create_comment((object) [
                'contentid' => $contentid1,
                'userid' => $this->users->students->two->id,
                'comment' => random_string(),
        ]);
        $this->generator->create_comment((object) [
                'contentid' => $contentid2,
                'userid' => $this->users->students->one->id,
                'comment' => random_string(),
        ]);

        // Student 1 has 2 comments.
        $this->assertEquals(2, \mod_openstudio\local\api\comments::total_for_user(
                $this->studiolevels->id, $this->users->students->one->id));
        // 1 comment is on their own content, so excluding own should give us 1.
        $this->assertEquals(1, \mod_openstudio\local\api\comments::total_for_user(
                $this->studiolevels->id, $this->users->students->one->id, true));
        // Student 2 has 1 comment.
        $this->assertEquals(1, \mod_openstudio\local\api\comments::total_for_user(
                $this->studiolevels->id, $this->users->students->two->id));
        // There are still no comments in the second studio.
        $this->assertEquals(0, \mod_openstudio\local\api\comments::total_for_user($studio2->id, $this->users->students->one->id));
        $this->assertEquals(0, \mod_openstudio\local\api\comments::total_for_user($studio2->id, $this->users->students->two->id));
    }

    public function test_get_attachment() {
        $this->resetAfterTest(true);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $comment1 = (object) [
                'contentid' => $this->contentdata->id,
                'userid' => $this->users->students->one->id,
                'comment' => random_string(),
                'filepath' => 'mod/studio/tests/importfiles/test.mp3',
                'filecontext' => \context_module::instance($this->studiolevels->cmid),
        ];
        $comment1->id = $this->generator->create_comment($comment1);
        $comment2 = (object) [
                'contentid' => $this->contentdata->id,
                'userid' => $this->users->students->two->id,
                'comment' => random_string()
        ];
        $comment2->id = $this->generator->create_comment($comment2);

        $attachment = \mod_openstudio\local\api\comments::get_attachment($comment1->id);
        $this->assertNotFalse($attachment);
        $this->assertEquals('test.mp3', $attachment->filename);
        $this->assertFalse(\mod_openstudio\local\api\comments::get_attachment($comment2->id));
    }

}
