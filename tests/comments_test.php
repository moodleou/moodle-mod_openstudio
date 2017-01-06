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

defined('MOODLE_INTERNAL') || die();

require_once('openstudio_testcase.php'); // Until this is moved to generator.

class mod_openstudio_comments_testcase extends openstudio_testcase  {

    /**
     * Sets up our fixtures.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
        $this->teacherroleid = 3;
        $this->studentroleid = 5;
        $this->totalcontents = 24; // This is what the scripts below create for ONE CMID.
        $this->pinboardcontents = 3; // This is what the scripts below create for ONE CMID.

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new stdClass();
        $this->users->students = new stdClass();
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
        $this->users->teachers = new stdClass();
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

    protected function tearDown() {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
    }

    /**
     * Tests the mod_openstudio\local\api\comments::create() function
     */
    public function test_comments_api_create() {
        $this->resetAfterTest(true);
        $this->populate_single_data_array();
        $this->populate_content_data();

        $this->assertNotEquals(false, mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->four->id, 'Fire and Blood'));
        $this->assertNotEquals(false, mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->three->id, 'Winter is Coming'));
        $this->assertGreaterThan(1, mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->three->id, 'Winter is Coming'));
    }

    /**
     * Tests the mod_openstudio\local\api\comments::delete() and mod_openstudio\local\api\comments::delete_all() functions
     */
    public function test_comments_api_delete() {
        $this->resetAfterTest(true);
        $this->populate_single_data_array();
        $this->populate_content_data();

        $comment1 = mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->four->id, 'Fire and Blood');
        mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->three->id, 'Winter is Coming');
        mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->three->id, 'Innovation at Work');
        mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->two->id, 'Experience the Innovation');
        mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->one->id, 'Keep calm and carry on');

        $this->assertEquals(false, mod_openstudio\local\api\comments::delete($comment1, $this->users->students->three->id, true));
        $this->assertEquals(true, mod_openstudio\local\api\comments::delete($comment1, $this->users->students->three->id));
        $this->assertEquals(true, mod_openstudio\local\api\comments::delete_all(
                $this->contentid, $this->users->students->three->id));
        $this->assertEquals(0, mod_openstudio\local\api\comments::total_for_user($this->studiolevels->id,
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

        $this->populate_single_data_array();
        $this->populate_content_data(); // Slot is owned by user1.

        $content2 = mod_openstudio\local\api\content::create(
                $this->studiolevels->id,  $this->users->students->three->id, 3,
                $this->studiolevels->leveldata['contentslevels'][$blockid][$activityid][$contentid], $this->singleentrydata);

        $comment1 = mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->four->id, 'Fire and Blood');
        mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->three->id, 'Winter is Coming');
        mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->three->id, 'Innovation at Work');
        mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->two->id, 'Experience the Innovation');
        mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->one->id, 'Keep calm and carry on');
        mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->one->id, 'Keep calm and carry on 2');
        mod_openstudio\local\api\comments::create(
                $this->contentid, $this->users->students->one->id, 'Keep calm and carry on 3');
        mod_openstudio\local\api\comments::create(
                $content2, $this->users->students->three->id, 'Winter is Coming');
        mod_openstudio\local\api\comments::create(
                $content2, $this->users->students->three->id, 'Innovation at Work');

        // Get a real comment and check we have the correct one.
        $getcomment = mod_openstudio\local\api\comments::get($comment1);
        $this->assertNotEquals(false, $getcomment);
        $this->assertEquals('Fire and Blood', $getcomment->commenttext);
        $this->assertEquals($this->users->students->four->firstname, $getcomment->firstname);
        // Try and get a non-existant comment.
        $this->assertEquals(false, mod_openstudio\local\api\comments::get(777));

        // Check that getting all comments for this content gets the correct number.
        $this->assertEquals(7, iterator_count(mod_openstudio\local\api\comments::get_for_content($this->contentid)));
        // Check that limiting the results works correctly.
        $this->assertEquals(4, iterator_count(mod_openstudio\local\api\comments::get_for_content($this->contentid, null, 4)));

        // Delete a comment, to check that $withdeleted works correctly.
        mod_openstudio\local\api\comments::delete($comment1, $this->users->students->four->id);
        $this->assertFalse(mod_openstudio\local\api\comments::get($comment1));
        $this->assertNotFalse(mod_openstudio\local\api\comments::get($comment1, null, true));
        $this->assertEquals(6, iterator_count(mod_openstudio\local\api\comments::get_for_content($this->contentid)));
        $this->assertEquals(7, iterator_count(mod_openstudio\local\api\comments::get_for_content($this->contentid, null, 0, true)));
    }

    public function test_studio_api_comments_replies() {
        $this->resetAfterTest(true);
        $contentid1 = $this->generator->create_contents(array(
            'studio' => 'OS1',
            'userid' => $this->users->students->one->id,
            'name' => 'Slot1',
            'description' => random_string()
        ));
        $contentid2 = $this->generator->create_contents(array(
            'studio' => 'OS1',
            'userid' => $this->users->students->two->id,
            'name' => 'Slot2',
            'description' => random_string()
        ));
        $commentid = $this->generator->create_comment(array(
            'contentid' => $contentid1,
            'userid' => $this->users->students->four->id,
            'comment' => random_string()
        ));

        $replyid = mod_openstudio\local\api\comments::create(
                $contentid1, $this->users->students->one->id, random_string(),
                null, null, null, $commentid);

        $reply = mod_openstudio\local\api\comments::get($replyid);
        $this->assertEquals($commentid, $reply->inreplyto);

        // Cannot create a reply to a reply.
        $this->assertFalse(mod_openstudio\local\api\comments::create(
                $contentid1, $this->users->students->three->id, random_string(),
                null, null, null, $replyid));
        // Cannot reply to a non-existent comment.
        $this->assertFalse(mod_openstudio\local\api\comments::create(
                $contentid1, $this->users->students->three->id, random_string(),
                null, null, null, $replyid + 1));
        // Cannot create a reply on a different content to the comment.
        $this->assertFalse(mod_openstudio\local\api\comments::create(
                $contentid2, $this->users->students->three->id, random_string(),
                null, null, null, $commentid));

    }

    public function test_total_for_content() {
        $this->resetAfterTest(true);
        $this->populate_single_data_array();
        $this->populate_content_data();

        // No comments to start with.
        $this->assertEquals(0, mod_openstudio\local\api\comments::total_for_content($this->contentid));

        $comment = (object) [
                'contentid' => $this->contentid,
                'userid' => $this->users->students->four->id,
                'comment' => random_string()
        ];
        $comment->id = $this->generator->create_comment($comment);
        $this->generator->create_comment((object) [
                'contentid' => $this->contentid,
                'userid' => $this->users->students->three->id,
                'comment' => random_string()
        ]);
        $this->generator->create_comment((object) [
                'contentid' => $this->contentid,
                'userid' => $this->users->students->three->id,
                'comment' => random_string()
        ]);

        // 3 Comments created.
        $this->assertEquals(3, mod_openstudio\local\api\comments::total_for_content($this->contentid));

        $this->generator->create_comment((object) [
                'contentid' => $this->contentid,
                'userid' => $this->users->students->three->id,
                'comment' => random_string(),
                'deleted' => true
        ]);

        // 4 comments, but the new one is deleted, so total should still be 3.
        $this->assertEquals(3, mod_openstudio\local\api\comments::total_for_content($this->contentid));
    }

    public function test_total_for_user() {
        $this->resetAfterTest(true);
        $studio2 = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS2'));
        $contentid1 = $this->generator->create_contents(array(
                'studio' => 'OS1',
                'userid' => $this->users->students->one->id,
                'name' => 'Content 1',
                'description' => random_string()
        ));
        $contentid2 = $this->generator->create_contents(array(
                'studio' => 'OS1',
                'userid' => $this->users->students->two->id,
                'name' => 'Content 2',
                'description' => random_string()
        ));
        // No comments yet.
        $this->assertEquals(0, mod_openstudio\local\api\comments::total_for_user(
                $this->studiolevels->id, $this->users->students->one->id));
        $this->assertEquals(0, mod_openstudio\local\api\comments::total_for_user(
                $this->studiolevels->id, $this->users->students->two->id));
        $this->assertEquals(0, mod_openstudio\local\api\comments::total_for_user($studio2->id, $this->users->students->one->id));
        $this->assertEquals(0, mod_openstudio\local\api\comments::total_for_user($studio2->id, $this->users->students->two->id));

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
        $this->assertEquals(2, mod_openstudio\local\api\comments::total_for_user(
                $this->studiolevels->id, $this->users->students->one->id));
        // 1 comment is on their own content, so excluding own should give us 1.
        $this->assertEquals(1, mod_openstudio\local\api\comments::total_for_user(
                $this->studiolevels->id, $this->users->students->one->id, true));
        // Student 2 has 1 comment.
        $this->assertEquals(1, mod_openstudio\local\api\comments::total_for_user(
                $this->studiolevels->id, $this->users->students->two->id));
        // There are still no comments in the second studio.
        $this->assertEquals(0, mod_openstudio\local\api\comments::total_for_user($studio2->id, $this->users->students->one->id));
        $this->assertEquals(0, mod_openstudio\local\api\comments::total_for_user($studio2->id, $this->users->students->two->id));
    }

    public function test_get_attachment() {
        $this->resetAfterTest(true);
        $this->populate_single_data_array();
        $this->populate_content_data();
        $comment1 = (object) [
                'contentid' => $this->contentid,
                'userid' => $this->users->students->one->id,
                'comment' => random_string(),
                'filepath' => 'mod/studio/tests/importfiles/test.mp3',
                'filecontext' => context_module::instance($this->studiolevels->cmid),
        ];
        $comment1->id = $this->generator->create_comment($comment1);
        $comment2 = (object) [
                'contentid' => $this->contentid,
                'userid' => $this->users->students->two->id,
                'comment' => random_string()
        ];
        $comment2->id = $this->generator->create_comment($comment2);

        $attachment = mod_openstudio\local\api\comments::get_attachment($comment1->id);
        $this->assertNotFalse($attachment);
        $this->assertEquals('test.mp3', $attachment->filename);
        $this->assertFalse(mod_openstudio\local\api\comments::get_attachment($comment2->id));
    }

    /**
     * TODO: Leaving this as-is for now, until the new render code is in place.
     */
    public function test_studio_renderlib_comments_nesting() {
        global $PAGE, $CFG;
        require_once($CFG->dirroot . '/mod/studio/renderlib.php');
        $class = new ReflectionClass('mod_studio_renderlib');
        $nestcomments = $class->getMethod('studio_renderlib_nest_comments');
        $nestcomments->setAccessible(true);
        $renderlib = new mod_studio_renderlib($PAGE, null);

        $comments = array(
            1 => (object) array(
                'id' => 1,
                'inreplyto' => null,
                'timemodified' => time(),
                'deletedby' => null
            ),
            2 => (object) array(
                'id' => 2,
                'inreplyto' => null,
                'timemodified' => time() + 100,
                'deletedby' => null
            ),
            3 => (object) array(
                'id' => 3,
                'inreplyto' => 1,
                'timemodified' => time() + 200,
                'deletedby' => null
            ),
            4 => (object) array(
                'id' => 4,
                'inreplyto' => 2,
                'timemodified' => time() + 300,
                'deletedby' => null
            ),
            5 => (object) array(
                'id' => 5,
                'inreplyto' => null,
                'timemodified' => time() + 400,
                'deletedby' => null

            ),
            6 => (object) array(
                'id' => 6,
                'inreplyto' => 5,
                'timemodified' => time() + 500,
                'deletedby' => null
            ),
            7 => (object) array(
                'id' => 7,
                'inreplyto' => 2,
                'timemodified' => time() + 600,
                'deletedby' => null
            ),
            8 => (object) array(
                'id' => 8,
                'inreplyto' => 2,
                'timemodified' => time() + 700,
                'deletedby' => null
            ),
            9 => (object) array(
                'id' => 9,
                'inreplyto' => null,
                'timemodified' => time() + 800,
                'deletedby' => null
            )
        );

        $nestedcomments = $nestcomments->invokeArgs($renderlib, array($comments));
        // There are 4 top level comments.
        $this->assertEquals(4, count($nestedcomments));
        $this->assertEquals(array(1, 2, 5, 9), array_keys($nestedcomments));
        // Comment 1 has 1 reply.
        $this->assertEquals(1, count($nestedcomments[1]->replies));
        $this->assertTrue(array_key_exists(3, $nestedcomments[1]->replies));

        // Comment 2 has 3 replies.
        $this->assertEquals(3, count($nestedcomments[2]->replies));
        $this->assertEquals(array(4, 7, 8), array_keys($nestedcomments[2]->replies));

        // Comment 5 has 1 reply.
        $this->assertEquals(1, count($nestedcomments[5]->replies));
        $this->assertTrue(array_key_exists(6, $nestedcomments[5]->replies));

        // Comment 9 has no replies.
        $this->assertEquals(0, count($nestedcomments[9]->replies));
    }

}
