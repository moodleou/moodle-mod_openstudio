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

class group_testcase extends \advanced_testcase {

    private $course;
    private $course2;
    private $users;
    private $groups;
    private $groupings;
    private $teacherroleid;
    private $studentroleid;
    private $generator; // Contains mod_openstudio specific data generator functions.
    private $studioprivate;
    private $studiogroup;
    private $studiomodule;
    private $studioworkspace;
    private $studiogeneric; // Generic studio instance with no levels or slots.
    private $studiolevels; // Generic studio instance with levels only.
    private $totalslots;
    private $pinboardslots;

    /**
     * Sets up our fixtures
     */
    protected function setUp() {
        global $DB;
        $this->resetAfterTest(true);
        $this->teacherroleid = 3;
        $this->studentroleid = 5;
        $this->totalslots = 24; // This is what the scripts below create for ONE CMID.
        $this->pinboardslots = 3; // This is what the scripts below create for ONE CMID.

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();
        $this->course2 = $this->getDataGenerator()->create_course();

        // Create course groups.
        $this->groups = new \stdClass();
        $this->groupings = new \stdClass();
        $this->groupings->a  = $this->getDataGenerator()->create_grouping(
                array('name' => 'Grouping A', 'courseid' => $this->course->id));
        $this->groups->one = $this->getDataGenerator()->create_group(array(
                'courseid' => $this->course->id, 'name' => 'The Starks'));
        $this->groups->two = $this->getDataGenerator()->create_group(array(
                'courseid' => $this->course->id, 'name' => 'The Lannisters'));

        // Add groups to our groupings.
        $insert = new \stdClass();
        $insert->groupingid = $this->groupings->a->id;
        $insert->groupid = $this->groups->one->id;
        $DB->insert_record('groupings_groups', $insert);
        $insert->groupingid = $this->groupings->a->id;
        $insert->groupid = $this->groups->two->id;
        $DB->insert_record('groupings_groups', $insert);

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
        $this->users->students->seven = $this->getDataGenerator()->create_user(
                array('email' => 'student7@ouunittest.com', 'username' => 'student7'));
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
        $this->getDataGenerator()->enrol_user(
                $this->users->students->six->id, $this->course2->id, $this->studentroleid, 'manual');
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

        // Assign Students a group.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Note: Students 1 to 5 + teacher 1 belong to group 1, students 6 to 10 + teacher 2 to group 2.
        $this->generator->add_users_to_groups(array(
                $this->groups->one->id => array(
                                $this->users->students->one->id,
                                $this->users->students->two->id,
                                $this->users->students->three->id,
                                $this->users->students->four->id,
                                $this->users->students->five->id,
                                $this->users->teachers->one->id
                        ),
                $this->groups->two->id => array(
                                $this->users->students->six->id,
                                $this->users->students->seven->id,
                                $this->users->students->eight->id,
                                $this->users->students->nine->id,
                                $this->users->students->ten->id,
                                $this->users->teachers->two->id
                        )
        ));

        $this->studioprivate = $this->generator->create_instance(array('course' => $this->course->id));
        // Let's create and populate our mock levels.
        $this->studioprivate->leveldata = $this->generator->create_mock_levels($this->studioprivate->id);

        // Now let's create and populate some slots.
        $this->studioprivate->slotinstances = new \stdClass();
        $this->studioprivate->slotinstances->student = $this->generator->create_mock_contents($this->studioprivate->id,
                $this->studioprivate->leveldata, $this->users->students->one->id,
                \mod_openstudio\local\api\content::VISIBILITY_PRIVATE); // Student1 is owner of 24 normal and 3 pin slots of
                                                                        // 24 normal and 3 pin slots.

        $this->studiomodule = $this->generator->create_instance(array('course' => $this->course->id));
        // Let's create and populate our mock levels.
        $this->studiomodule->leveldata = $this->generator->create_mock_levels($this->studiomodule->id);

        // Now let's create and populate some slots.
        $this->studiomodule->slotinstances = new \stdClass();
        $this->studiomodule->slotinstances->student = $this->generator->create_mock_contents($this->studiomodule->id,
                $this->studiomodule->leveldata, $this->users->students->five->id,
                \mod_openstudio\local\api\content::VISIBILITY_MODULE); // Student5 is owner of 24 normal and 3 pin slots of
                                                                // 24 normal and 3 pin slots.

        $this->studiogroup = $this->generator->create_instance(array('course' => $this->course->id),
                array('groupingid' => $this->groupings->a->id));
        // Let's create and populate our mock levels.
        $this->studiogroup->leveldata = $this->generator->create_mock_levels($this->studiogroup->id);

        // Now let's create and populate some slots.
        $this->studiogroup->slotinstances = new \stdClass();
        $this->studiogroup->slotinstances->student6 = $this->generator->create_mock_contents($this->studiogroup->id,
                $this->studiogroup->leveldata, $this->users->students->six->id,
                \mod_openstudio\local\api\content::VISIBILITY_GROUP);
        $this->studiogroup->slotinstances->student8 = $this->generator->create_mock_contents($this->studiogroup->id,
                $this->studiogroup->leveldata, $this->users->students->eight->id,
                \mod_openstudio\local\api\content::VISIBILITY_GROUP);

        $this->studioworkspace = $this->generator->create_instance(array('course' => $this->course->id));
        // Let's create and populate our mock levels.
        $this->studioworkspace->leveldata = $this->generator->create_mock_levels($this->studioworkspace->id);

        // Now let's create and populate some slots.
        $this->studioworkspace->slotinstances = new \stdClass();
        $this->studioworkspace->slotinstances->student8 = $this->generator->create_mock_contents($this->studioworkspace->id,
                $this->studioworkspace->leveldata, $this->users->students->eight->id,
                \mod_openstudio\local\api\content::VISIBILITY_PRIVATE);
        $this->studioworkspace->slotinstances->student9 = $this->generator->create_mock_contents($this->studioworkspace->id,
                $this->studioworkspace->leveldata, $this->users->students->nine->id,
                \mod_openstudio\local\api\content::VISIBILITY_MODULE);
        $this->studioworkspace->slotinstances->student10 = $this->generator->create_mock_contents($this->studioworkspace->id,
                $this->studioworkspace->leveldata, $this->users->students->ten->id,
                \mod_openstudio\local\api\content::VISIBILITY_MODULE);
        $this->studioworkspace->slotinstances->student3 = $this->generator->create_mock_contents($this->studioworkspace->id,
                $this->studioworkspace->leveldata, $this->users->students->four->id,
                \mod_openstudio\local\api\content::VISIBILITY_GROUP);
        $this->studioworkspace->slotinstances->student4 = $this->generator->create_mock_contents($this->studioworkspace->id,
                $this->studioworkspace->leveldata, $this->users->students->three->id,
                \mod_openstudio\local\api\content::VISIBILITY_GROUP);

        // Create generic studios.
        $this->studiogeneric = $this->generator->create_instance(array('course' => $this->course->id));
        $this->studiolevels = $this->generator->create_instance(array('course' => $this->course->id));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);

    }

    protected function tearDown() {
        $this->course = '';
        $this->users = '';
        $this->groups = '';
        $this->teacherroleid = '';
        $this->studentroleid = '';
        $this->generator = '';
        $this->studioprivate = '';
        $this->studiogroup = '';
        $this->studiomodule = '';
        $this->studiogeneric = '';
        $this->studiolevels = '';
        $this->totalslots = '';
        $this->pinboardslots = '';
        $this->studioworkspace = '';
        $this->course2 = '';
    }

    /**
     * Tests the studio_api_group_has_same_memberships() function
     */
    public function test_group_has_same_memberships() {
        $this->resetAfterTest(true);

        $this->assertEquals(true, \mod_openstudio\local\api\group::has_same_memberships(
                $this->groupings->a->id, $this->users->students->one->id,
                $this->users->students->two->id, true));

        $this->assertEquals(false, \mod_openstudio\local\api\group::has_same_memberships(
                $this->groupings->a->id, $this->users->students->one->id,
                $this->users->students->eight->id, true));
    }

    /**
     * Tests the studio_api_group_has_same_course() function
     */
    public function test_group_has_same_course() {
        $this->resetAfterTest(true);

        $this->assertEquals(true, \mod_openstudio\local\api\group::has_same_course(
                $this->course->id, $this->users->students->one->id, $this->users->students->two->id));

        $this->assertEquals(true, \mod_openstudio\local\api\group::has_same_course(
                $this->course->id, $this->users->students->one->id, $this->users->students->eight->id));

        $this->assertEquals(false, \mod_openstudio\local\api\group::has_same_course(
                $this->course2->id, $this->users->students->six->id, $this->users->students->one->id));
    }

}
