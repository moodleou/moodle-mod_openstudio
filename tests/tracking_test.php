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

// Make sure this isn't being directly accessed.
use mod_openstudio\local\api\tracking;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/openstudio/api/tracking.php'); // Until this is refactored.

class mod_openstudio_tracking_testcase extends advanced_testcase {

    private $users;
    private $generator;

    /**
     * Sets up our fixtures.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
        $studentroleid = 5;

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new stdClass();
        $this->users->students = new stdClass();
        $this->users->students->three = $this->getDataGenerator()->create_user(
                array('email' => 'student3@ouunittest.com', 'username' => 'student3'));

        // Enroll our students and teacher (users) in the course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->three->id,
                $course->id, $studentroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');
        $studiolevels = $this->generator->create_instance(array('course' => $course->id));
        $this->generator->create_mock_levels($studiolevels->id);
    }

    /**
     * Tests adding and duplication of actions against slots.
     * Tests functions in the tracking api.
     */
    public function test_studio_api_tracking_log_action_and_duplicates() {
        $this->resetAfterTest(true);

        // Pass an arbitrary slot ID.
        $this->assertEquals(true,
                studio_api_tracking_log_action(
                        2, tracking::READ_CONTENT, $this->users->students->three->id));
        // This should work as we will be allowed a duplicate. It should return an ID.
        $this->assertNotEquals(false,
                studio_api_tracking_log_action(
                        2, tracking::READ_CONTENT, $this->users->students->three->id, true));
        // Let's see if it tells us to write again - it shouldn't within 60 seconds!!
        $this->assertEquals(false,
                studio_api_tracking_write_again(
                        2, tracking::READ_CONTENT, $this->users->students->three->id));
        // Let's mimic a deletion tracking entry for our slot.
        $this->assertEquals(true,
                studio_api_tracking_log_action(
                        2, tracking::DELETE_CONTENT, $this->users->students->three->id));
        // Now let's try and delete again, we should get a false.
        $this->assertEquals(false,
                studio_api_tracking_log_action(
                        2, tracking::DELETE_CONTENT, $this->users->students->three->id));
        // We should also check the duplicate function directly.
        $this->assertEquals(true,
                studio_api_tracking_is_duplicate(2, tracking::DELETE_CONTENT));
    }

}
