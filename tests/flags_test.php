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

class flags_test extends \advanced_testcase   {

    protected $users;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or slots.
    protected $totalcontents;
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
        $this->totalcontents = 24; // This is what the scripts below create for ONE CMID.
        $this->pinboardslots = 3; // This is what the scripts below create for ONE CMID.

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                ['email' => 'student1@ouunittest.com', 'username' => 'student1']);
        $this->users->students->two = $this->getDataGenerator()->create_user(
                ['email' => 'student2@ouunittest.com', 'username' => 'student2']);
        $this->users->students->three = $this->getDataGenerator()->create_user(
                ['email' => 'student3@ouunittest.com', 'username' => 'student3']);
        $this->users->students->four = $this->getDataGenerator()->create_user(
                ['email' => 'student4@ouunittest.com', 'username' => 'student4']);
        $this->users->students->five = $this->getDataGenerator()->create_user(
                ['email' => 'student5@ouunittest.com', 'username' => 'student5']);
        $this->users->students->six = $this->getDataGenerator()->create_user(
                ['email' => 'student6@ouunittest.com', 'username' => 'student6']);
        $this->users->students->seven = $this->getDataGenerator()->create_user(
                ['email' => 'student7@ouunittest.com', 'username' => 'student7']);
        $this->users->students->eight = $this->getDataGenerator()->create_user(
                ['email' => 'student8@ouunittest.com', 'username' => 'student8']);
        $this->users->students->nine = $this->getDataGenerator()->create_user(
                ['email' => 'student9@ouunittest.com', 'username' => 'student9']);
        $this->users->students->ten = $this->getDataGenerator()->create_user(
                ['email' => 'student10@ouunittest.com', 'username' => 'student10']);
        $this->users->teachers = new \stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                ['email' => 'teacher1@ouunittest.com', 'username' => 'teacher1']);
        $this->users->teachers->two = $this->getDataGenerator()->create_user(
                ['email' => 'teacher2@ouunittest.com', 'username' => 'teacher2']);

        // Enroll our students and teacher (users) in the course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->one->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->two->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->three->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->four->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->five->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->six->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->eight->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->nine->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->ten->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->one->id, $this->course->id, $teacherroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->two->id, $this->course->id, $teacherroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance(['course' => $this->course->id, 'idnumber' => 'OS1']);
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
    }

    protected function tearDown(): void {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';

    }

    public function test_get_content_flags() {
        $this->resetAfterTest(true);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);

        // Add flags to studio.
        \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::FAVOURITE, 'on', $this->users->students->one->id);
        \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::NEEDHELP, 'on', $this->users->students->one->id);
        \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::MADEMELAUGH, 'on', $this->users->students->one->id);
        \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::INSPIREDME, 'on', $this->users->students->one->id);
        \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::FAVOURITE, 'on', $this->users->students->nine->id);
        \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::NEEDHELP, 'on', $this->users->students->nine->id);
        \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::MADEMELAUGH, 'on', $this->users->students->nine->id);
        \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::INSPIREDME, 'on', $this->users->students->nine->id);

        // Let's retrieve the data.
        $contentflags = \mod_openstudio\local\api\flags::get_content_flags($this->contentdata->id);
        $totalcount = 0;
        $usercount = 0;
        $flagcount = 0;
        foreach ($contentflags as $sf) {
            $totalcount++;
            if ($sf->flagid == \mod_openstudio\local\api\flags::NEEDHELP) {
                $flagcount++;
            }
            if ($sf->userid == $this->users->students->nine->id) {
                $usercount++;
            }
        }
        // Total should be 8.
        $this->assertEquals(8, $totalcount);
        // Let's just count to make sure we have 4 for user 9 and 2 of the NEEDHELP flags.
        $this->assertEquals(2, $flagcount);
        $this->assertEquals(4, $usercount);
    }

    public function test_toggle() {
        $this->resetAfterTest(true);
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        // These should execute just fine.
        $this->assertNotEquals(false, \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::FAVOURITE, 'on', $this->users->students->one->id));
        $this->assertNotEquals(false, \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::NEEDHELP, 'on', $this->users->students->one->id));
        $this->assertNotEquals(false, \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::MADEMELAUGH, 'on', $this->users->students->one->id));
        $this->assertNotEquals(false, \mod_openstudio\local\api\flags::toggle($this->contentdata->id,
                \mod_openstudio\local\api\flags::INSPIREDME, 'on', $this->users->students->one->id));

        // This one should choke because the user already has a flag.
        $this->assertEquals(true,
                \mod_openstudio\local\api\flags::toggle(
                    $this->contentdata->id, \mod_openstudio\local\api\flags::INSPIREDME, 'on', $this->users->students->one->id));
        // This should also choke because we are removing something that does not exist.
        $this->assertEquals(true,
                \mod_openstudio\local\api\flags::toggle(
                    $this->contentdata->id, \mod_openstudio\local\api\flags::INSPIREDME, 'off', $this->users->students->five->id));

        // Get all the flags and count, total should be 4.
        $contentflags = \mod_openstudio\local\api\flags::get_content_flags($this->contentdata->id);
        $this->assertEquals(4, iterator_count($contentflags));

        // Let's remove one and rerun the count.
        $this->assertEquals(true, \mod_openstudio\local\api\flags::toggle(
                $this->contentdata->id, \mod_openstudio\local\api\flags::INSPIREDME,
                'off', $this->users->students->one->id));
        $contentflags = \mod_openstudio\local\api\flags::get_content_flags($this->contentdata->id);
        $this->assertEquals(3, iterator_count($contentflags));

        // Also check the 2 new functions here to see our count by user and count by flag
        // First check count by flag.
        $this->assertEquals(1, \mod_openstudio\local\api\flags::count_for_content(
                $this->contentdata->id, \mod_openstudio\local\api\flags::FAVOURITE));
        $this->assertEquals(0, \mod_openstudio\local\api\flags::count_for_content(
                $this->contentdata->id, \mod_openstudio\local\api\flags::INSPIREDME));

        // Now check count by user.
        $flagtotals = \mod_openstudio\local\api\flags::count_by_content($this->contentdata->id);
        $this->assertEquals(3, count($flagtotals));
        $flagstatus = \mod_openstudio\local\api\flags::get_for_content_by_user(
                $this->contentdata->id, $this->users->students->one->id);
        $this->assertEquals(3, count($flagstatus));

        // Let's check that flagtotals' count for \mod_openstudio\api\flags::MADEMELAUGH = 1.
        $this->assertEquals(1, $flagtotals[\mod_openstudio\local\api\flags::MADEMELAUGH]->count);

        // Finally let's check that \mod_openstudio\api\flags::FAVOURITE exists in flagstatus.
        $this->assertArrayHasKey(\mod_openstudio\local\api\flags::FAVOURITE, $flagtotals);
    }

    public function test_toggle_folder_flags() {
        global $DB;
        $setid = $this->generator->create_folders([
            'openstudio' => 'OS1',
            'name' => 'TestSet',
            'description' => 'foo',
            'userid' => $this->users->students->one->id]);

        $slotid = $this->generator->create_contents([
            'openstudio' => 'OS1',
            'name' => 'TestSlot',
            'description' => 'foo',
            'file' => 'mod/openstudio/tests/importfiles/test1.jpg',
            'userid' => $this->users->students->one->id
        ]);

        $this->generator->create_folder_contents([
            'openstudio' => 'OS1',
            'content' => 'TestSlot',
            'folder' => 'TestSet',
            'userid' => $this->users->students->one->id
        ]);
        $flagparams = [
            'contentid' => $slotid,
            'userid' => $this->users->students->two->id,
            'flagid' => \mod_openstudio\local\api\flags::MADEMELAUGH
        ];
        $trackingparams = [
            'contentid' => $setid,
            'userid' => $this->users->students->two->id,
            'actionid' => \mod_openstudio\local\api\tracking::MODIFY_FOLDER
        ];

        $this->assertFalse($DB->record_exists('openstudio_flags', $flagparams));
        $this->assertFalse($DB->record_exists('openstudio_tracking', $trackingparams));

        \mod_openstudio\local\api\flags::toggle($slotid,
                \mod_openstudio\local\api\flags::MADEMELAUGH,
                'on',
                $this->users->students->two->id,
                $setid);

        $this->assertTrue($DB->record_exists('openstudio_flags', $flagparams));
        $this->assertTrue($DB->record_exists('openstudio_tracking', $trackingparams));
    }

    public function test_comment_toggle() {
        global $DB;
        $this->resetAfterTest(true);
        $slotid = $this->generator->create_contents([
            'openstudio' => 'OS1',
            'name' => 'Test Slot',
            'file' => 'mod/openstudio/tests/importfiles/test1.jpg',
            'userid' => $this->users->students->one->id
        ]);
        $commentid = $this->generator->create_comment([
            'contentid' => $slotid,
            'comment' => 'Lorem ipsum dolor sit amet',
            'userid' => $this->users->students->two->id
        ]);
        $flagid = \mod_openstudio\local\api\flags::comment_toggle($slotid, $commentid, $this->users->students->one->id, 'on', true);
        $assertparams = [
            'id' => $flagid,
            'contentid' => $slotid,
            'personid' => 0,
            'userid' => $this->users->students->one->id,
            'flagid' => \mod_openstudio\local\api\flags::COMMENT_LIKE,
            'commentid' => $commentid
        ];
        $this->assertTrue($DB->record_exists('openstudio_flags', $assertparams));
    }

    public function test_count_for_comment() {
        $this->resetAfterTest(true);
        $slotid = $this->generator->create_contents([
            'openstudio' => 'OS1',
            'name' => 'Test Slot',
            'file' => 'mod/openstudio/tests/importfiles/test1.jpg',
            'userid' => $this->users->students->one->id
        ]);
        $commentid = $this->generator->create_comment([
            'contentid' => $slotid,
            'comment' => 'Lorem ipsum dolor sit amet',
            'userid' => $this->users->students->two->id
        ]);
        // Check we get 0 when there are no flags.
        $this->assertEquals(0, \mod_openstudio\local\api\flags::count_for_comment($commentid));
        $comments = \mod_openstudio\local\api\comments::get_for_content($slotid);
        $this->assertEquals(0, $comments->current()->flagcount);

        $this->generator->create_flag([
            'userid' => $this->users->students->one->id,
            'commentid' => $commentid,
            'flagid' => \mod_openstudio\local\api\flags::COMMENT_LIKE
        ]);
        // Check we get 1 when there is a flag.
        $this->assertEquals(1, \mod_openstudio\local\api\flags::count_for_comment($commentid));
        $comments = \mod_openstudio\local\api\comments::get_for_content($slotid);
        $this->assertEquals(1, $comments->current()->flagcount);

        $this->generator->create_flag([
            'userid' => $this->users->students->three->id,
            'commentid' => $commentid,
            'flagid' => \mod_openstudio\local\api\flags::COMMENT_LIKE
        ]);
        $this->generator->create_flag([
            'userid' => $this->users->students->four->id,
            'commentid' => $commentid,
            'flagid' => \mod_openstudio\local\api\flags::COMMENT_LIKE
        ]);
        // Check we get the correct number when there's > 1 flag.
        $this->assertEquals(3, \mod_openstudio\local\api\flags::count_for_comment($commentid));
        $comments = \mod_openstudio\local\api\comments::get_for_content($slotid);
        $this->assertEquals(3, $comments->current()->flagcount);
    }

}
