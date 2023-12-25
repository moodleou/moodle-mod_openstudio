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
 * Feature tests for mod/openstudio/classes/task/updated_group_moving_contents.php.
 *
 * @package    mod_openstudio
 * @copyright  2023 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

use advanced_testcase;
use cm_info;
use mod_openstudio\task\updated_group_moving_contents;
use mod_openstudio_generator;

/**
 * Test cases for mod/openstudio/classes/task/updated_group_moving_contents.php.
 *
 * @package    mod_openstudio
 * @category   phpunit
 * @copyright  2023 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class updated_group_moving_content_test extends advanced_testcase {

    private $course;
    private $users;
    private $groups;
    private $groupings;
    private $studentroleid = 5;
    /** @var mod_openstudio_generator */
    private $generator;
    private $studios;
    private $task;
    private $contents;

    protected function setUp(): void {
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        $this->task = new updated_group_moving_contents();

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create users.
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
        $this->users->students->four = $this->getDataGenerator()->create_user([
            'email' => 'student4@ouunittest.com',
            'username' => 'student4',
        ]);

        // Enroll.
        $this->getDataGenerator()->enrol_user($this->users->students->one->id, $this->course->id, $this->studentroleid);
        $this->getDataGenerator()->enrol_user($this->users->students->two->id, $this->course->id, $this->studentroleid);
        $this->getDataGenerator()->enrol_user($this->users->students->three->id, $this->course->id, $this->studentroleid);

        // Create groupings.
        $this->groupings = new \stdClass();
        $this->groupings->a = $this->getDataGenerator()->create_grouping([
            'name' => 'Grouping A',
            'courseid' => $this->course->id,
        ]);
        $this->groupings->b = $this->getDataGenerator()->create_grouping([
            'name' => 'Grouping B',
            'courseid' => $this->course->id,
        ]);

        // Create groups.
        $this->groups = new \stdClass();
        $this->groups->one = $this->getDataGenerator()->create_group(['courseid' => $this->course->id, 'name' => 'Group 1']);
        $this->groups->two = $this->getDataGenerator()->create_group(['courseid' => $this->course->id, 'name' => 'Group 2']);
        $this->groups->three = $this->getDataGenerator()->create_group(['courseid' => $this->course->id, 'name' => 'Group 3']);
        $this->groups->four = $this->getDataGenerator()->create_group(['courseid' => $this->course->id, 'name' => 'Group 4']);
        $this->groups->five = $this->getDataGenerator()->create_group(['courseid' => $this->course->id, 'name' => 'Group 5']);
        $this->groups->six = $this->getDataGenerator()->create_group(['courseid' => $this->course->id, 'name' => 'Group 6']);

        // Add group 1, 2, 3 to group A.
        groups_assign_grouping($this->groupings->a->id, $this->groups->one->id);
        groups_assign_grouping($this->groupings->a->id, $this->groups->two->id);
        groups_assign_grouping($this->groupings->a->id, $this->groups->three->id);
        // Insert group 4, 5 to grouping B.
        groups_assign_grouping($this->groupings->b->id, $this->groups->four->id);
        groups_assign_grouping($this->groupings->b->id, $this->groups->five->id);

        // Add 3 students to group 1.
        groups_add_member($this->groups->one->id, $this->users->students->one->id, 'mod_openstudio');
        groups_add_member($this->groups->one->id, $this->users->students->two->id, 'mod_openstudio');
        groups_add_member($this->groups->one->id, $this->users->students->three->id, 'mod_openstudio');

        $this->studios = new \stdClass();
        // Create OS2 1.
        $this->studios->one = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS1',
            'groupingid' => $this->groupings->a->id,
            'groupmode' => SEPARATEGROUPS,
        ]);
        $this->studios->one->leveldata = $this->generator->create_mock_levels($this->studios->one->id);
        $this->studios->one->cm = cm_info::create(get_coursemodule_from_id('openstudio', $this->studios->one->cmid));

        // Create OS2 2.
        $this->studios->two = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS2',
            'groupingid' => $this->groupings->b->id,
            'groupmode' => SEPARATEGROUPS,
        ]);
        $this->studios->two->leveldata = $this->generator->create_mock_levels($this->studios->two->id);

        // Create OS2 3.
        $this->studios->three = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS3',
            'groupingid' => $this->groupings->b->id,
            'groupmode' => SEPARATEGROUPS,
        ]);
        $this->studios->three->leveldata = $this->generator->create_mock_levels($this->studios->three->id);
        $this->studios->three->cm = get_coursemodule_from_id('openstudio', $this->studios->three->cmid);

        // Create OS2 4 (normal without groupmode).
        $this->studios->four = $this->generator->create_instance([
            'course' => $this->course->id,
            'idnumber' => 'OS4',
        ]);
        $this->studios->four->leveldata = $this->generator->create_mock_levels($this->studios->four->id);
        $this->studios->four->cm = get_coursemodule_from_id('openstudio', $this->studios->four->cmid);

        $this->contents = new \stdClass();
        $this->contents->student1 = new \stdClass();
        // Create content 1 of user 1 on OS 1.
        $this->contents->student1->one = $this->generator->generate_content_data(
            $this->studios->one, $this->users->students->one->id,
            [
                'name' => 'Content 1 of User 1 on OS 1',
                'visibility' => -$this->groups->one->id,
            ]
        );
        // Create content 1 of user 1 on OS 3.
        $this->contents->student1->two = $this->generator->generate_content_data(
            $this->studios->three, $this->users->students->one->id,
            [
                'name' => 'Content 1 of User 1 on OS 3 - Normal visibility',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
            ]
        );

        $this->contents->student2 = new \stdClass();
        // Create content 1 of user 2 on OS 1.
        $this->contents->student2->one = $this->generator->generate_content_data(
            $this->studios->one, $this->users->students->two->id,
            [
                'name' => 'Content 1 of User 2 on OS 1',
                'visibility' => -$this->groups->one->id,
            ]
        );
    }

    public function test_nothing_happen_when_all_contents_are_correct(): void {
        global $DB;
        $this->resetAfterTest(true);

        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        $this->task->execute();

        // Making sure no data changed.
        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Check logs.
        $this->assertEquals('', $this->task->get_and_clear_test_mtrace_buffer());
    }

    public function test_user_removed_from_group_1_and_added_to_group_2(): void {
        global $DB;
        $this->resetAfterTest(true);

        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Remove user 1 from group 1.
        groups_remove_member($this->groups->one->id, $this->users->students->one->id);

        // Add user 1 to group 2.
        groups_add_member($this->groups->two->id, $this->users->students->one->id, 'mod_openstudio');

        $this->task->execute();

        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->two->id,
        ]));

        // Check logs.
        $log = $this->task->get_and_clear_test_mtrace_buffer();
        $expectedlog = $this->build_log(
            $this->contents->student1->one->id, $this->groups->one->id, $this->groups->two->id,
            $this->users->students->one->id, $this->studios->one->id, $this->course->id,
        );
        $this->assertEquals($expectedlog, $log);
    }

    public function test_user1_removed_from_group_1_not_add_to_any_group_remaining_incorrect(): void {
        global $DB;
        $this->resetAfterTest(true);

        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Remove user 1 from group 1.
        groups_remove_member($this->groups->one->id, $this->users->students->one->id);

        $allowedgroups = groups_get_activity_allowed_groups($this->studios->one->cm, $this->users->students->one->id);
        $this->assertSame([], $allowedgroups);

        $this->task->execute();

        // User 1 is removed from group 1, but there are no new groups. It will remain as incorrect visibility.
        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Check logs.
        $log = $this->task->get_and_clear_test_mtrace_buffer();
        // No content was moved.
        $this->assertEquals('', $log);
    }

    public function test_user_removed_and_added_to_multiple_groups_should_move_to_the_latest_group(): void {
        global $DB;
        $this->resetAfterTest(true);

        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Remove user 1 from group 1.
        groups_remove_member($this->groups->one->id, $this->users->students->one->id);

        // Add user 1 to group 2.
        groups_add_member($this->groups->two->id, $this->users->students->one->id, 'mod_openstudio');

        // Add user 1 to group 3.
        groups_add_member($this->groups->three->id, $this->users->students->one->id, 'mod_openstudio');

        $this->task->execute();

        // Group 3 is the latest new group, must update to the latest group.
        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->three->id,
        ]));

        // Check logs.
        $log = $this->task->get_and_clear_test_mtrace_buffer();
        $expectedlog = $this->build_log(
            $this->contents->student1->one->id, $this->groups->one->id, $this->groups->three->id,
            $this->users->students->one->id, $this->studios->one->id, $this->course->id,
        );
        $this->assertEquals($expectedlog, $log);
    }

    /**
     * Test user one was removed from group 1 and was added to group 2,
     * user two was removed from group 1 and was added to group 3
     * and user three still in group one.
     */
    public function test_users_group_changes(): void {
        global $DB;
        $this->resetAfterTest(true);

        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Create content 1 of user 2.
        $this->generator->generate_content_data($this->studios->one, $this->users->students->two->id, [
            'name' => 'Content 1 of User 2 on OS 1',
            'visibility' => -$this->groups->one->id,
        ]);

        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->two->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Create content 1 of user 3.
        $this->generator->generate_content_data($this->studios->one, $this->users->students->three->id, [
            'name' => 'Content 1 of User 3 on OS 1',
            'visibility' => -$this->groups->one->id,
        ]);

        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->three->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Remove user 1 from group 1.
        groups_remove_member($this->groups->one->id, $this->users->students->one->id);

        // Remove user 2 from group 1.
        groups_remove_member($this->groups->one->id, $this->users->students->two->id);

        // Add user 1 to group 2.
        groups_add_member($this->groups->two->id, $this->users->students->one->id, 'mod_openstudio');

        // Add user 2 to group 3.
        groups_add_member($this->groups->three->id, $this->users->students->two->id, 'mod_openstudio');

        $this->task->execute();

        // User one's contents should be in group 2.
        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->two->id,
        ]));

        // User two's contents should be in group 3.
        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->two->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->three->id,
        ]));

        // User three's contents should be still in group one.
        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->three->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Check logs.
        $log = $this->task->get_and_clear_test_mtrace_buffer();
        // Order by content id (Content 2 => Content 1).
        $expectedlog = $this->build_log(
            $this->contents->student2->one->id, $this->groups->one->id, $this->groups->three->id,
            $this->users->students->two->id, $this->studios->one->id, $this->course->id);
        $expectedlog .= $this->build_log(
            $this->contents->student1->one->id, $this->groups->one->id, $this->groups->two->id,
            $this->users->students->one->id, $this->studios->one->id, $this->course->id);
        $this->assertEquals($expectedlog, $log);
    }

    public function test_student1_group_1_to_group_2_student2_group_4_to_group_5_different_activity(): void {
        global $DB;
        $this->resetAfterTest(true);

        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Add Student 2 to group 4.
        groups_add_member($this->groups->four->id, $this->users->students->two->id, 'mod_openstudio');

        // Create content 1 of user 2 on OS 2.
        $content1ofuser2 = $this->generator->generate_content_data($this->studios->two, $this->users->students->two->id, [
            'name' => 'Content 1 of User 2 on OS 2',
            'visibility' => -$this->groups->four->id,
        ]);

        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->two->id,
            'openstudioid' => $this->studios->two->id,
            'visibility' => -$this->groups->four->id,
        ]));

        // Remove Student 2 from group 4.
        groups_remove_member($this->groups->four->id, $this->users->students->two->id);
        // Add Student 2 to group 5.
        groups_add_member($this->groups->five->id, $this->users->students->two->id, 'mod_openstudio');

        // Remove Student 1 from group 1.
        groups_remove_member($this->groups->one->id, $this->users->students->one->id);
        // Add Student 1 to group 2.
        groups_add_member($this->groups->two->id, $this->users->students->one->id, 'mod_openstudio');

        $this->task->execute();

        // Student 1's contents should be in group 2.
        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->two->id,
        ]));

        // Student 2's contents should be in group 5.
        $this->assertEquals(1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->two->id,
            'openstudioid' => $this->studios->two->id,
            'visibility' => -$this->groups->five->id,
        ]));

        // Check logs.
        $log = $this->task->get_and_clear_test_mtrace_buffer();
        // Order by OS ID DESC (OS2 => OS1) then content ID DESC (Content 2 => Content 1).
        $expectedlog = $this->build_log(
            $content1ofuser2->id, $this->groups->four->id, $this->groups->five->id,
            $this->users->students->two->id, $this->studios->two->id, $this->course->id);
        $expectedlog .= $this->build_log(
            $this->contents->student1->one->id, $this->groups->one->id, $this->groups->two->id,
            $this->users->students->one->id, $this->studios->one->id, $this->course->id);
        $this->assertEquals($expectedlog, $log);
    }

    public function test_grouping_remove_group_and_add_new_group(): void {
        global $DB;
        $this->resetAfterTest(true);

        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Remove group 1 out of grouping A.
        groups_unassign_grouping($this->groupings->a->id, $this->groups->one->id);

        // Add student 1 to group 6.
        groups_add_member($this->groups->six->id, $this->users->students->one->id, 'mod_openstudio');

        // Add group 6 to grouping A.
        groups_assign_grouping($this->groupings->a->id, $this->groups->six->id);

        // Create content 1 of user 1 on OS 2.
        $content1 = $this->generator->generate_content_data($this->studios->two, $this->users->students->one->id, [
            'name' => 'Content 1 of User 1 on OS 2',
            'visibility' => -$this->groups->four->id,
        ]);

        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->two->id,
            'visibility' => -$this->groups->four->id,
        ]));

        $this->task->execute();

        // Now contents is moved to group 6.
        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->six->id,
        ]));
        $this->assertEmpty($DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));
        // Test no effect on OS 2.
        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->two->id,
            'visibility' => -$this->groups->four->id,
        ]));

        // Check logs.
        $log = $this->task->get_and_clear_test_mtrace_buffer();
        $expectedlog = $this->build_log(
            $this->contents->student1->one->id, $this->groups->one->id, $this->groups->six->id,
            $this->users->students->one->id, $this->studios->one->id, $this->course->id);
        $this->assertEquals($expectedlog, $log);
    }

    public function test_grouping_remove_group_and_no_new_group(): void {
        global $DB;
        $this->resetAfterTest(true);

        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Remove group 1 out of grouping A.
        groups_unassign_grouping($this->groupings->a->id, $this->groups->one->id);

        $this->task->execute();

        // Now contents is still be incorrect in group 1.
        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // No group was moved.
        $this->assertEquals('', $this->task->get_and_clear_test_mtrace_buffer());
    }

    public function test_change_entire_grouping(): void {
        global $DB;
        $this->resetAfterTest(true);

        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->one->id,
        ]));

        // Update using grouping B.
        $sql = "UPDATE {course_modules}
                   SET groupingid = ?
                 WHERE instance = ?";
        $DB->execute($sql, [$this->groupings->b->id, $this->studios->one->id]);

        // Grouping B has 2 groups: 4, 5. Student1 is not a member of those groups.
        // Student1 now joins the groups.
        groups_add_member($this->groups->four->id, $this->users->students->one->id, 'mod_openstudio');
        groups_add_member($this->groups->five->id, $this->users->students->one->id, 'mod_openstudio');

        $this->task->execute();

        // If change grouping ID, move to the latest group of new grouping B: group 5.
        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->one->id,
            'openstudioid' => $this->studios->one->id,
            'visibility' => -$this->groups->five->id,
        ]));

        // Check logs.
        $log = $this->task->get_and_clear_test_mtrace_buffer();
        $expectedlog = $this->build_log(
            $this->contents->student1->one->id, $this->groups->one->id, $this->groups->five->id,
            $this->users->students->one->id, $this->studios->one->id, $this->course->id);
        $this->assertEquals($expectedlog, $log);
    }

    public function test_switching_groups_inside_a_grouping(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($this->users->students->four->id, $course1->id,
            $this->studentroleid);

        $grouping1 = $this->getDataGenerator()->create_grouping([
            'name' => 'Grouping Tooltip 1',
            'courseid' => $course1->id,
        ]);

        $group1a = $this->getDataGenerator()->create_group([
            'courseid' => $course1->id, 'name' => 'Group tooltip 1A',
        ]);
        $group1b = $this->getDataGenerator()->create_group([
            'courseid' => $course1->id, 'name' => 'Group tooltip 1B',
        ]);

        groups_assign_grouping($grouping1->id, $group1a->id);
        groups_assign_grouping($grouping1->id, $group1b->id);

        groups_add_member($group1a->id, $this->users->students->four->id, 'mod_openstudio');

        // Create OS2 A.
        $os1 = $this->generator->create_instance([
            'course' => $course1->id,
            'idnumber' => 'OS2A',
            'groupingid' => $grouping1->id,
            'groupmode' => SEPARATEGROUPS,
        ]);
        $os1->leveldata = $this->generator->create_mock_levels($os1->id);

        $content1 = $this->generator->generate_content_data($os1, $this->users->students->four->id, [
            'name' => 'Content 1 of User 4 on OS 1A',
            'visibility' => -$group1a->id,
        ]);
        $this->assertNotFalse($content1);

        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->four->id,
            'openstudioid' => $os1->id,
            'visibility' => -$group1a->id,
        ]));

        groups_remove_member($group1a->id, $this->users->students->four->id);

        groups_add_member($group1b->id, $this->users->students->four->id, 'mod_openstudio');

        $this->task->execute();

        $this->assertEmpty($DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->four->id,
            'openstudioid' => $os1->id,
            'visibility' => -$group1a->id,
        ]));

        $this->assertEquals( 1, $DB->count_records('openstudio_contents', [
            'userid' => $this->users->students->four->id,
            'openstudioid' => $os1->id,
            'visibility' => -$group1b->id,
        ]));

        // Check logs.
        $log = $this->task->get_and_clear_test_mtrace_buffer();
        $expectedlog = $this->build_log(
            $content1->id, $group1a->id, $group1b->id,
            $this->users->students->four->id, $os1->id, $course1->id);
        $this->assertEquals($expectedlog, $log);
    }

    private function build_log(int $contentid, int $currentgroupid, int $latestgroupid,
                               int $userid, int $studioid, int $courseid): string {
        return get_string('cron_updategroupcontent:movedlog', 'mod_openstudio', (object)[
            'contentid' => $contentid,
            'fromgroupid' => $currentgroupid,
            'togroupid' => $latestgroupid,
            'userid' => $userid,
            'studioid' => $studioid,
            'courseid' => $courseid,
        ]) . '\n';
    }
}
