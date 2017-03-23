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
 *
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\notifications;
use mod_openstudio\local\tests\mock_content_notifiable;
use mod_openstudio\local\tests\mock_comment_notifiable;
use mod_openstudio\local\tests\mock_tutor_notifiable;

defined('MOODLE_INTERNAL') || die();

class notifications_test extends \advanced_testcase {

    private $users;
    private $groups;
    private $groupings;
    private $course;
    private $posts;
    private $comments;
    private $generator;
    private $flags;
    private $studio;

    protected function setUp() {
        $this->resetAfterTest(true);
        $teacherroleid = 3;
        $studentroleid = 5;

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create user.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                ['email' => 'student1@ouunittest.com', 'username' => 'student1']);
        $this->users->students->two = $this->getDataGenerator()->create_user(
                ['email' => 'student2@ouunittest.com', 'username' => 'student2']);
        $this->users->students->three = $this->getDataGenerator()->create_user(
                ['email' => 'student3@ouunittest.com', 'username' => 'student3']);

        $this->users->teachers = new \stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                ['email' => 'teacher1@ouunittest.com', 'username' => 'teacher1']);
        $this->users->teachers->two = $this->getDataGenerator()->create_user(
                ['email' => 'teacher2@ouunittest.com', 'username' => 'teacher2']);

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Enroll our students in the course.
        $this->getDataGenerator()->enrol_user($this->users->students->one->id, $this->course->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->students->two->id, $this->course->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->students->three->id, $this->course->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->one->id, $this->course->id, $teacherroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->two->id, $this->course->id, $teacherroleid, 'manual');

        $this->groups = new \stdClass();
        $this->groupings = new \stdClass();
        $this->groupings->one = $this->getDataGenerator()->create_grouping(
                ['name' => 'Grouping A', 'courseid' => $this->course->id]);
        $this->groupings->two  = $this->getDataGenerator()->create_grouping(
                ['name' => 'Grouping B', 'courseid' => $this->course->id]);
        $this->groups->one = $this->getDataGenerator()->create_group(['courseid' => $this->course->id, 'name' => 'Group 1']);
        $this->groups->two = $this->getDataGenerator()->create_group(['courseid' => $this->course->id, 'name' => 'Group 2']);
        $this->getDataGenerator()->create_grouping_group(
                ['groupingid' => $this->groupings->one->id, 'groupid' => $this->groups->one->id]);
        $this->getDataGenerator()->create_grouping_group(
                ['groupingid' => $this->groupings->two->id, 'groupid' => $this->groups->two->id]);

        $this->getDataGenerator()->create_group_member(
                ['userid' => $this->users->students->one->id, 'groupid' => $this->groups->one->id]);
        $this->getDataGenerator()->create_group_member(
                ['userid' => $this->users->students->one->id, 'groupid' => $this->groups->two->id]);
        // Student 2 is in a group with student 1 in the right grouping, but does not have the tutor role so in not their tutor.
        $this->getDataGenerator()->create_group_member(
                ['userid' => $this->users->students->two->id, 'groupid' => $this->groups->one->id]);
        // Teacher 1 has the tutor role in a group with student 1, which is in the right grouping, so is their tutor.
        $this->getDataGenerator()->create_group_member(
                ['userid' => $this->users->teachers->one->id, 'groupid' => $this->groups->one->id]);
        // Teacher 2 has the tutor role in a group with student 1, but it is not in the right grouping, so is not their tutor.
        $this->getDataGenerator()->create_group_member(
                ['userid' => $this->users->teachers->two->id, 'groupid' => $this->groups->two->id]);

        // Create generic studios.
        $this->studio = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS1',
            'tutorroles' => $teacherroleid,
            'groupingid' => $this->groupings->one->id,
            'groupmode' => SEPARATEGROUPS
        ]);

        $this->posts = new \stdClass();
        $this->posts->one = $this->generator->create_contents([
            'userid' => $this->users->students->one->id,
            'openstudio' => 'OS1',
            'name' => random_string(),
            'description' => random_string(),
            'content' => random_string(),
            'contenttype' => content::TYPE_TEXT,
            'visibility' => content::VISIBILITY_MODULE
        ]);
        $this->posts->two = $this->generator->create_contents([
            'userid' => $this->users->students->two->id,
            'openstudio' => 'OS1',
            'name' => random_string(),
            'description' => random_string(),
            'content' => random_string(),
            'contenttype' => content::TYPE_TEXT,
            'visibility' => content::VISIBILITY_MODULE
        ]);
        $this->posts->three = $this->generator->create_contents([
            'userid' => $this->users->students->one->id,
            'openstudio' => 'OS1',
            'name' => random_string(),
            'description' => random_string(),
            'content' => random_string(),
            'contenttype' => content::TYPE_TEXT,
            'visibility' => content::VISIBILITY_TUTOR
        ]);
        $this->comments = new \stdClass();
        $this->comments->one = $this->generator->create_comment([
            'contentid' => $this->posts->one,
            'userid' => $this->users->students->two->id,
            'comment' => random_string()
        ]);
        $this->comments->two = $this->generator->create_comment([
            'contentid' => $this->posts->one,
            'userid' => $this->users->students->three->id,
            'comment' => random_string()
        ]);

        // Student 1 gets notifications for content 1.
        $this->flags = new \stdClass();
        $this->flags->one = $this->generator->create_flag([
            'contentid' => $this->posts->one,
            'userid' => $this->users->students->one->id,
            'flagid' => flags::FOLLOW_CONTENT
        ]);
        // Student 2 gets notifications for comment 1.
        $this->flags->two = $this->generator->create_flag([
            'commentid' => $this->comments->one,
            'userid' => $this->users->students->two->id,
            'flagid' => flags::FOLLOW_CONTENT
        ]);
    }

    public function test_handle_event_content() {
        global $DB;
        $event = new mock_content_notifiable();
        $event->contentid = $this->posts->one;
        $event->context = \context_module::instance($this->studio->cmid);
        $event->set_courseid($this->course->id);

        notifications::handle_event($event);

        // We should get a single notification record against the content post, for Student 1.
        $record = $DB->get_record('openstudio_notifications', ['contentid' => $this->posts->one], '*', MUST_EXIST);
        $this->assertTrue($record->userid == $this->users->students->one->id);
        $this->assertNotEmpty($record->timecreated);
        $this->assertEmpty($record->timeread);
    }

    public function test_handle_event_comment() {
        global $DB;
        $event = new mock_comment_notifiable();
        $event->contentid = $this->posts->one;
        $event->commentid = $this->comments->one;
        $event->context = \context_module::instance($this->studio->cmid);
        $event->set_courseid($this->course->id);

        notifications::handle_event($event);

        // We should get a record for both Student 1 and Student 2, and no others.
        $this->assertCount(2, $DB->get_records('openstudio_notifications'));
        $student1params = [
            'contentid' => $this->posts->one,
            'commentid' => $this->comments->one,
            'userid' => $this->users->students->one->id
        ];
        $this->assertTrue($DB->record_exists('openstudio_notifications', $student1params));
        $student2params = [
            'contentid' => $this->posts->one,
            'commentid' => $this->comments->one,
            'userid' => $this->users->students->two->id
        ];
        $this->assertTrue($DB->record_exists('openstudio_notifications', $student2params));
    }

    public function test_handle_event_tutor() {
        global $DB;

        $event = new mock_tutor_notifiable();
        $event->contentid = $this->posts->three;
        $event->userid = $this->users->students->one->id;
        $event->context = \context_module::instance($this->studio->cmid);
        $event->set_courseid($this->course->id);

        notifications::handle_event($event);

        // We should have 1 record for teacher 1.  No other users are tutors for student 1 (see comments in setUp).
        $record = $DB->get_record('openstudio_notifications', ['contentid' => $this->posts->three], '*', MUST_EXIST);
        $this->assertEquals($this->users->teachers->one->id, $record->userid);
        $this->assertNotEmpty($record->timecreated);
        $this->assertEmpty($record->timeread);
    }

    public function test_handle_event_visibility() {
        global $DB;
        // Student two follows content three, but it's not visibile to them, so they shouldn't get notifications for it.
        $this->generator->create_flag([
            'contentid' => $this->posts->three,
            'userid' => $this->users->students->two->id,
            'flagid' => flags::FOLLOW_CONTENT
        ]);
        $event = new mock_content_notifiable();
        $event->contentid = $this->posts->three;
        $event->context = \context_module::instance($this->studio->cmid);
        $event->set_courseid($this->course->id);

        notifications::handle_event($event);
        $this->assertEmpty($DB->get_records('openstudio_notifications'));
    }

    public function test_get_current() {
        $this->assertCount(0, notifications::get_current($this->studio->id, $this->users->students->one->id));
        $this->assertCount(0, notifications::get_current($this->studio->id, $this->users->students->one->id, 1));
        $this->assertCount(0, notifications::get_current($this->studio->id, $this->users->students->two->id));

        $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);
        $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);
        $this->generator->create_notification([
            'userid' => $this->users->students->two->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);

        $this->assertCount(2, notifications::get_current($this->studio->id, $this->users->students->one->id));
        $this->assertCount(1, notifications::get_current($this->studio->id, $this->users->students->one->id, 1));
        $this->assertCount(1, notifications::get_current($this->studio->id, $this->users->students->two->id));
    }

    public function test_get_recent() {
        $threshold = strtotime('-1 hour');
        $this->assertCount(0, notifications::get_recent($this->studio->id, $this->users->students->one->id, $threshold));
        $this->assertCount(0, notifications::get_recent($this->studio->id, $this->users->students->two->id, $threshold));

        $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);
        $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);
        $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'timecreated' => $threshold - 1,
            'userfrom' => $this->users->students->two->id
        ]);

        $this->assertCount(2, notifications::get_recent($this->studio->id, $this->users->students->one->id, $threshold));
        $this->assertCount(0, notifications::get_recent($this->studio->id, $this->users->students->two->id, $threshold));
    }

    public function test_mark_read() {
        global $DB;
        $notification1 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);
        $notification2 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);

        $where = 'id = ? AND timeread IS NOT NULL';
        $this->assertFalse($DB->record_exists_select('openstudio_notifications', $where, [$notification1->id]));
        $this->assertFalse($DB->record_exists_select('openstudio_notifications', $where, [$notification2->id]));

        notifications::mark_read($notification1->id);

        $this->assertTrue($DB->record_exists_select('openstudio_notifications', $where, [$notification1->id]));
        $this->assertFalse($DB->record_exists_select('openstudio_notifications', $where, [$notification2->id]));
    }

    public function test_delete() {
        global $DB;
        $notification1 = $this->generator->create_notification([
                'userid' => $this->users->students->one->id,
                'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);
        $notification2 = $this->generator->create_notification([
                'userid' => $this->users->students->one->id,
                'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);

        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));

        notifications::delete($notification1->id);

        $this->assertFalse($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
    }

    public function test_delete_old_unread() {
        global $DB;
        $threshold = 2;
        set_config('notificationlimitunread', $threshold, 'openstudio');
        $oldtime = new \DateTime($threshold + 1 . ' days ago', \core_date::get_server_timezone_object());
        $notification1 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);
        $notification2 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'timecreated' => $oldtime->getTimestamp(),
            'userfrom' => $this->users->students->two->id
        ]);

        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));

        // This should deleted $notification2 as it was created before the threshold number of days.
        notifications::delete_old(notifications::FIELD_UNREAD);

        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertFalse($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
    }

    public function test_delete_old_read() {
        global $DB;
        $threshold = 2;
        set_config('notificationlimitread', $threshold, 'openstudio');
        $oldtime = new \DateTime($threshold + 1 . ' days ago', \core_date::get_server_timezone_object());
        $notification1 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'timecreated' => $oldtime->getTimestamp(),
            'timeread' => $oldtime->getTimestamp(),
            'userfrom' => $this->users->students->two->id
        ]);
        $notification2 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'timecreated' => $oldtime->getTimestamp(),
            'timeread' => time(),
            'userfrom' => $this->users->students->two->id
        ]);
        $notification3 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);

        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification3->id]));

        // This should deleted $notification1 as it has been read longer than the threshold number of days.
        notifications::delete_old(notifications::FIELD_READ);

        $this->assertFalse($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification3->id]));
    }

    public function test_delete_max() {
        global $DB;
        $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
        ]);
        $this->posts->four = $this->generator->create_contents([
            'userid' => $this->users->students->two->id,
            'openstudio' => 'OS2',
            'name' => random_string(),
            'description' => random_string(),
            'content' => random_string(),
            'contenttype' => content::TYPE_TEXT,
            'visibility' => content::VISIBILITY_MODULE
        ]);
        $limit = 5;
        set_config('notificationlimitmax', $limit, 'openstudio');
        $time = time();
        $firsttime = $time;
        for ($i = 0; $i < $limit * 2; $i++) {
            $this->generator->create_notification([
                'userid' => $this->users->students->one->id,
                'contentid' => $this->posts->one,
                'timecreated' => $time,
                'userfrom' => $this->users->students->two->id
            ]);
            $lasttime = $time;
            $time++;
        }
        for ($i = 0; $i < $limit; $i++) {
            $this->generator->create_notification([
                'userid' => $this->users->students->two->id,
                'contentid' => $this->posts->one,
                'userfrom' => $this->users->students->three->id
            ]);
        }
        $this->generator->create_notification([
                'userid' => $this->users->students->two->id,
                'contentid' => $this->posts->four,
                'userfrom' => $this->users->students->three->id
        ]);
        $firstparams = [
            'userid' => $this->users->students->one->id,
            'timecreated' => $firsttime
        ];
        $lastparams = [
            'userid' => $this->users->students->one->id,
            'timecreated' => $lasttime
        ];

        // Student 1 has more than the limit, so they should be culled, oldest first.
        // Student 2 has more than the limit, but they are not all in the same studio, so they should remain.
        $this->assertCount($limit * 2, $DB->get_records('openstudio_notifications', ['userid' => $this->users->students->one->id]));
        $this->assertCount($limit + 1, $DB->get_records('openstudio_notifications', ['userid' => $this->users->students->two->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', $firstparams));
        $this->assertTrue($DB->record_exists('openstudio_notifications', $lastparams));

        notifications::delete_max();

        $this->assertCount($limit, $DB->get_records('openstudio_notifications', ['userid' => $this->users->students->one->id]));
        $this->assertCount($limit + 1, $DB->get_records('openstudio_notifications', ['userid' => $this->users->students->two->id]));
        $this->assertFalse($DB->record_exists('openstudio_notifications', $firstparams));
        $this->assertTrue($DB->record_exists('openstudio_notifications', $lastparams));
    }

    public function test_delete_unread_for_post() {
        global $DB;
        $notification1 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id
        ]);
        $notification2 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'flagid' => flags::FAVOURITE,
            'userfrom' => $this->users->students->three->id
        ]);
        $notification3 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->two,
            'userfrom' => $this->users->students->three->id
        ]);
        $notification4 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'timeread' => time(),
            'userfrom' => $this->users->students->three->id
        ]);

        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification3->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification4->id]));

        // Notification 1 and 2 should be deleted.
        // Notification 3 is for a different post, 4 has been read.
        notifications::delete_unread_for_post($this->posts->one);

        $this->assertFalse($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertFalse($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification3->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification4->id]));
    }

    public function test_delete_unread_for_flag() {
        global $DB;
        $notification1 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'userfrom' => $this->users->students->two->id,
            'flagid' => flags::FAVOURITE
        ]);
        $notification2 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'flagid' => flags::FAVOURITE,
            'userfrom' => $this->users->students->three->id
        ]);
        $notification3 = $this->generator->create_notification([
            'userid' => $this->users->students->three->id,
            'contentid' => $this->posts->one,
            'flagid' => flags::FAVOURITE,
            'userfrom' => $this->users->students->two->id,
            'timeread' => time()
        ]);
        $notification4 = $this->generator->create_notification([
                'userid' => $this->users->students->one->id,
                'contentid' => $this->posts->one,
                'userfrom' => $this->users->students->two->id,
                'flagid' => flags::INSPIREDME
        ]);

        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification3->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification4->id]));

        // Notification 1 should be deleted.
        // Notification 2 is from a different user, 3 has been read, 4 is for a different flag.
        notifications::delete_unread_for_flag($this->posts->one, $this->users->students->two->id, flags::FAVOURITE);

        $this->assertFalse($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification3->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification4->id]));
    }

    public function test_delete_unread_for_comment() {
        global $DB;
        $notification1 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'commentid' => $this->comments->one,
            'userfrom' => $this->users->students->two->id
        ]);
        $notification2 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'commentid' => $this->comments->one,
            'flagid' => flags::MADEMELAUGH,
            'userfrom' => $this->users->students->three->id
        ]);
        $notification3 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'commentid' => $this->comments->two,
            'userfrom' => $this->users->students->two->id
        ]);
        $notification4 = $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'contentid' => $this->posts->one,
            'commentid' => $this->comments->one,
            'userfrom' => $this->users->students->two->id,
            'timeread' => time()
        ]);

        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification3->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification4->id]));

        // Notification 1 and 2 should be deleted.
        // Notification 3 is for a different comment, 4 has been read.
        notifications::delete_unread_for_comment($this->comments->one);

        $this->assertFalse($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertFalse($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification3->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification4->id]));
    }

    public function test_delete_unread_for_comment_flag() {
        global $DB;
        $notification1 = $this->generator->create_notification([
                'userid' => $this->users->students->one->id,
                'commentid' => $this->comments->one,
                'contentid' => $this->posts->one,
                'userfrom' => $this->users->students->two->id,
                'flagid' => flags::COMMENT_LIKE
        ]);
        $notification2 = $this->generator->create_notification([
                'userid' => $this->users->students->one->id,
                'commentid' => $this->comments->one,
                'contentid' => $this->posts->one,
                'flagid' => flags::COMMENT_LIKE,
                'userfrom' => $this->users->students->three->id
        ]);
        $notification3 = $this->generator->create_notification([
                'userid' => $this->users->students->one->id,
                'commentid' => $this->comments->one,
                'contentid' => $this->posts->one,
                'flagid' => flags::COMMENT_LIKE,
                'userfrom' => $this->users->students->two->id,
                'timeread' => time()
        ]);
        $notification4 = $this->generator->create_notification([
                'userid' => $this->users->students->two->id,
                'commentid' => $this->comments->two,
                'contentid' => $this->posts->one,
                'userfrom' => $this->users->students->three->id,
                'flagid' => flags::COMMENT_LIKE
        ]);

        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification3->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification4->id]));

        // Notification 1 should be deleted.
        // Notification 3 is from a different user, 4 has been read, 5 is for a different comment.
        notifications::delete_unread_for_comment_flag($this->comments->one, $this->users->students->two->id, flags::COMMENT_LIKE);

        $this->assertFalse($DB->record_exists('openstudio_notifications', ['id' => $notification1->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification2->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification3->id]));
        $this->assertTrue($DB->record_exists('openstudio_notifications', ['id' => $notification4->id]));
    }
}
