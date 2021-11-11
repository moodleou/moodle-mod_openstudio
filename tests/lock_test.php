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

class lock_testcase extends \advanced_testcase {

    protected $users;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or slots.
    protected $pinboardslots;
    protected $singleentrydata;
    protected $contentdata;

    /**
     * Sets up our fixtures.
     */
    protected function setUp(): void {
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
                array('email' => 'student1@ouunittest.com', 'username' => 'student1'));
        $this->users->students->two = $this->getDataGenerator()->create_user(
                array('email' => 'student2@ouunittest.com', 'username' => 'student2'));

        $this->users->teachers = new \stdClass();
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

    protected function tearDown(): void {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
    }

    /**
     * Tests the studio_api_lock_slot($userid, $slotid, $locktype)
     */
    public function test_studio_api_lock_slot() {
        $this->resetAfterTest(true);

        $this->setUser($this->users->students->one);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $this->contentdata->id, 'Slot creation failed - no slotid returned.');

        // Confidence check, get the current state of the slot.
        $slotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals(0, $slotdata->locktype);
        $this->assertEquals(0, $slotdata->lockedby);
        $this->assertEquals(0, $slotdata->lockedtime);

        // Student adds a lock to the slot.
        $lockedtype = \mod_openstudio\local\api\lock::CRUD;// Default lock button action.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $timenow  = time();
        $this->assertEquals(true, $changedslot);

        // Check the state of the now locked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($this->contentdata->id, $updatedslotdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->students->one->id, $updatedslotdata->lockedby);

        // Student removes a lock to the slot.
        $lockedtype = \mod_openstudio\local\api\lock::NONE;
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $timenow  = time();
        $this->assertNotEquals(false, $changedslot);

        // Check the state of the now unlocked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->students->one->id, $updatedslotdata->lockedby);
        $this->assertEquals(true, in_array($updatedslotdata->lockedtime, [$timenow, $timenow - 1])); // 1 second tolerance.

        // Teacher adds a lock to the slot.
        $lockedtype = \mod_openstudio\local\api\lock::CRUD; // Default lock button action.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->teachers->one->id, $this->contentdata->id, $lockedtype);
        $timenow  = time();
        $this->assertNotEquals(false, $changedslot);

        // Check the state of the now locked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($this->contentdata->id, $updatedslotdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->teachers->one->id, $updatedslotdata->lockedby);

    }

    /**
     * Tests studio_api_lock_slot_system($userid, $slotid, $locktype).
     */
    public function test_studio_api_lock_slot_system() {
        $this->resetAfterTest(true);

        // Create a slot, update it so it has a version, then let's try to delete the version.
        $this->setUser($this->users->students->one);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $this->contentdata->id, 'Slot creation failed - no slotid returned.');

        $lockedtype = \mod_openstudio\local\api\lock::ALL;

        // Student adds a lock to the slot.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $this->assertEquals(true, $changedslot);

        // Check the state of the now locked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($this->contentdata->id, $updatedslotdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->students->one->id, $updatedslotdata->lockedby);

        $lockedtype = \mod_openstudio\local\api\lock::NONE;

        // Student removes a lock to the slot.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // Check the state of the now unlocked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->students->one->id, $updatedslotdata->lockedby);

        $lockedtype = \mod_openstudio\local\api\lock::SOCIAL_CRUD;

        // Teacher changes a lock to the slot.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->teachers->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // Check the state of the now unlocked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($this->contentdata->id, $updatedslotdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->teachers->one->id, $updatedslotdata->lockedby);
    }

    /**
     * Tests studio_api_lock_check($slotid)
     */
    public function test_studio_api_lock_check() {
        $this->resetAfterTest(true);

        // Create a slot, update it so it has a version, then let's try to delete the version.
        $this->setUser($this->users->students->one);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $this->contentdata->id, 'Slot creation failed - no slotid returned.');

        $lockedtype = \mod_openstudio\local\api\lock::NONE;

        // Student removes a lock to the slot.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // Manually Check the state of the now unlocked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->students->one->id, $updatedslotdata->lockedby);
        $this->assertEquals(0, $updatedslotdata->lockedtime);

        // System checks the state of the now unlocked slot.
        $checkslot = \mod_openstudio\local\api\lock::check($this->contentdata->id);

        // Current check should be false as its unlocked.
        $this->assertEquals(false, $checkslot);

        $lockedtype = \mod_openstudio\local\api\lock::SOCIAL_CRUD;

        // Student changes the lock to the slot.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // System checks the state of the now unlocked slot.
        $checkslot = \mod_openstudio\local\api\lock::check($this->contentdata->id);

        // Current check should be student user its locked by.
        $this->assertEquals($this->users->students->one->id, $checkslot);

        // Check the state of the changed slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($this->contentdata->id, $updatedslotdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->students->one->id, $updatedslotdata->lockedby);

        $lockedtype = \mod_openstudio\local\api\lock::ALL;

        // Teacher adds a complete lock to the slot.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->teachers->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // System checks the state of the chenged slot.
        $checkslot = \mod_openstudio\local\api\lock::check($this->contentdata->id);

        // Current check should return teacher user its locked by.
        $this->assertEquals($this->users->teachers->one->id, $checkslot);

        // Check the state of the now locked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->teachers->one->id, $this->contentdata->id);
        $this->assertEquals($this->contentdata->id, $updatedslotdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->teachers->one->id, $updatedslotdata->lockedby);
    }

    /**
     * Tests test_studio_api_lock_schedule()
     */
    public function test_studio_api_lock_schedule() {
        $this->resetAfterTest(true);

        // Create a slot, update it so it has a version, then let's try to delete the version.
        $this->setUser($this->users->students->one);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $this->contentdata->id, 'Slot creation failed - no slotid returned.');

        $lockedtype = \mod_openstudio\local\api\lock::NONE;

        // Student removes a lock to the slot.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
                        $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // Check the state of the now unlocked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->students->one->id, $updatedslotdata->lockedby);
        $this->assertEquals(0, $updatedslotdata->lockedtime);

        // System check the state of the now unlocked slot.
        $checkslot = \mod_openstudio\local\api\lock::check($this->contentdata->id);

        // Current check return false as its unlocked.
        $this->assertEquals(false, $checkslot);

        $lockedtype = \mod_openstudio\local\api\lock::SOCIAL_CRUD;

        // Student changes the lock on the slot.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // System check the state of the now unlocked slot.
        $checkslot = \mod_openstudio\local\api\lock::check($this->contentdata->id);

        // Current check should be student user its locked by.
        $this->assertEquals($this->users->students->one->id, $checkslot);

        // Setup params for lock schedule function.
        $userid = $this->users->teachers->one->id;
        $level = 3;
        $level3id = $updatedslotdata->l3id;
        $locktype = \mod_openstudio\local\api\lock::SOCIAL_CRUD;
        $lockprocessed = time();
        $locktime = time() + 1000;
        $unlocktime = time() + 2000;

        // Required by studio_internal_getpagenameandparams in studio-internal-trigger-event.
        $_SERVER['REQUEST_URI'] = 'https://learn2.open.ac.uk/mod/openstudio/view.php?filteron=1&reset=0&id=498074';

        // Change the scheduled state of the slot.
        $checkschedule = \mod_openstudio\local\api\lock::schedule($userid, $level3id, $locktype, $locktime, $unlocktime);

        $scheduledl3slotdata = \mod_openstudio\local\api\levels::get_record($level, $level3id);
        $this->assertEquals($level3id, $scheduledl3slotdata->id);
        $this->assertEquals($lockprocessed, $scheduledl3slotdata->lockprocessed);
        $this->assertEquals($lockedtype, $scheduledl3slotdata->locktype);
        $this->assertEquals($locktime, $scheduledl3slotdata->locktime);
        $this->assertEquals($unlocktime, $scheduledl3slotdata->unlocktime);
    }

    /**
     * Tests studio_api_lock_reset_schedule($userid, $level3id)
     */
    public function test_studio_api_lock_reset_schedule() {
        $this->resetAfterTest(true);

        global $DB;

        // Create a slot, update it so it has a version, then let's try to delete the version.
        $this->setUser($this->users->students->one);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $this->contentdata->id, 'Slot creation failed - no slotid returned.');

        $lockedtype = \mod_openstudio\local\api\lock::NONE;

        // Student removes a lock from the slot.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // Check the state of the now unlocked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->students->one->id, $updatedslotdata->lockedby);
        $this->assertEquals(0, $updatedslotdata->lockedtime);

        $lockedtype = \mod_openstudio\local\api\lock::SOCIAL_CRUD;

        // Student changes the lock on the slot.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // Setup params for lock schedule function.
        $userid = $this->users->teachers->one->id;
        $level = 3;
        $level3id = $updatedslotdata->l3id;
        $locktype = \mod_openstudio\local\api\lock::SOCIAL_CRUD;
        $lockprocessed = time();
        $locktime = time() + 1000;
        $unlocktime = time() + 2000;
        // Required by studio_internal_getpagenameandparams in studio-internal-trigger-event.
        $_SERVER['REQUEST_URI'] = 'https://learn2.open.ac.uk/mod/openstudio/view.php?filteron=1&reset=0&id=498074';

        // Change the schedules state of the slot.
        $checkschedule = \mod_openstudio\local\api\lock::schedule($userid, $level3id, $locktype, $locktime, $unlocktime);
        $this->assertEquals(true, $checkschedule);

        // Check the state of the now unlocked slot.
        $scheduledl3slotdata = \mod_openstudio\local\api\levels::get_record($level, $level3id);
        $this->assertEquals(true, $checkschedule);
        $this->assertEquals($level3id, $scheduledl3slotdata->id);
        $this->assertEquals($lockprocessed, $scheduledl3slotdata->lockprocessed);
        $this->assertEquals($lockedtype, $scheduledl3slotdata->locktype);
        $this->assertEquals($locktime, $scheduledl3slotdata->locktime);
        $this->assertEquals($unlocktime, $scheduledl3slotdata->unlocktime);

        // Reset the schedule state of the slot.
        $resetslotl3schedule = \mod_openstudio\local\api\lock::reset_schedule($userid, $level3id );
        $this->assertEquals(true, $resetslotl3schedule);

        // Check the state of the now unset slot.
        $scheduledl3slotdata = \mod_openstudio\local\api\levels::get_record($level, $level3id);
        $this->assertEquals($lockprocessed, $scheduledl3slotdata->lockprocessed);
        $this->assertEquals(0, $scheduledl3slotdata->locktype);
        $this->assertEquals(0, $scheduledl3slotdata->locktime);
        $this->assertEquals(0, $scheduledl3slotdata->unlocktime);
    }

    /**
     * Tests studio_api_lock_processing($slotdata);
     */
    public function test_studio_api_lock_processing() {
        $this->resetAfterTest(true);

        // Create a slot, update it so it has a version, then let's try to delete the version.
        $this->setUser($this->users->students->one);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->assertGreaterThan(0, $this->contentdata->id, 'Slot creation failed - no slotid returned.');

        // Student removes a lock from the slot.
        $lockedtype = \mod_openstudio\local\api\lock::NONE;
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // Check the state of the now unlocked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);
        $this->assertEquals($lockedtype, $updatedslotdata->locktype);
        $this->assertEquals($this->users->students->one->id, $updatedslotdata->lockedby);
        $this->assertEquals(0, $updatedslotdata->lockedtime);

        // Student sets default lock on the slot. Now redundant.
        $lockedtype = \mod_openstudio\local\api\lock::CRUD;// Default for Users.
        $changedslot = \mod_openstudio\local\api\lock::lock_content(
            $this->users->students->one->id, $this->contentdata->id, $lockedtype);
        $this->assertNotEquals(false, $changedslot);

        // Check the defaulted state of the locked slot.
        $updatedslotdata = \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentdata->id);

        // Setting up all parameters for lock schedule testing.
        $userid = 0; // Set by system user.
        $level = 3;
        $level3id = $updatedslotdata->l3id;
        $locktype = \mod_openstudio\local\api\lock::COMMENT_CRUD;
        $lockprocessed = time();
        $locktime = time() + 1000;
        $unlocktime = time() + 2000;
        // Required by studio_internal_getpagenameandparams in studio-internal-trigger-event.
        $_SERVER['REQUEST_URI'] = 'https://learn2.open.ac.uk/mod/openstudio/view.php?filteron=1&reset=0&id=498074';

        // Change the schedule state of the slot.
        $checkschedule = \mod_openstudio\local\api\lock::schedule($userid, $level3id, $locktype, $locktime, $unlocktime);
        $this->assertEquals(true, $checkschedule);

        // Check the state of the now unlocked slot.
        $scheduledl3slotdata = \mod_openstudio\local\api\levels::get_record($level, $level3id);

        // Test the system lock.
        // Create $slotdata object.
        // Call \mod_openstudio\local\api\lock::processing($slotdata);
        // Test assertions for lock.
    }

}
