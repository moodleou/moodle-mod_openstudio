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

use mod_openstudio\local\api\rss;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

class subscription_test extends \advanced_testcase {

    protected $users;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or slots.
    protected $pinboardslots;
    protected $singleentrydata;
    protected $contentdata;
    private $pbslotid;

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

        // Create Users.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                array('email' => 'student1@ouunittest.com', 'username' => 'student1',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->students->two = $this->getDataGenerator()->create_user(
                array('email' => 'student2@ouunittest.com', 'username' => 'student2',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->students->three = $this->getDataGenerator()->create_user(
                array('email' => 'student3@ouunittest.com', 'username' => 'student3',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->students->four = $this->getDataGenerator()->create_user(
                array('email' => 'student4@ouunittest.com', 'username' => 'student4',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->students->five = $this->getDataGenerator()->create_user(
                array('email' => 'student5@ouunittest.com', 'username' => 'student5',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->students->six = $this->getDataGenerator()->create_user(
                array('email' => 'student6@ouunittest.com', 'username' => 'student6',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->students->seven = $this->getDataGenerator()->create_user(
                array('email' => 'student7@ouunittest.com', 'username' => 'student7',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->students->eight = $this->getDataGenerator()->create_user(
                array('email' => 'student8@ouunittest.com', 'username' => 'student8',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->students->nine = $this->getDataGenerator()->create_user(
                array('email' => 'student9@ouunittest.com', 'username' => 'student9',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->students->ten = $this->getDataGenerator()->create_user(
                array('email' => 'student10@ouunittest.com', 'username' => 'student10',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->teachers = new \stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                array('email' => 'teacher1@ouunittest.com', 'username' => 'teacher1',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->teachers->two = $this->getDataGenerator()->create_user(
                array('email' => 'teacher2@ouunittest.com', 'username' => 'teacher2',
                        'firstname' => 'John', 'lastname' => 'Smith'));

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
        // Set groupmode to separate groups.
        $this->studiolevels = $this->generator->create_instance(
                array('course' => $this->course->id, 'idnumber' => 'OS1'));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
    }

    protected function tearDown(): void {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';

    }

    /**
     * Populates the singleentrydata property with slotdetails and MODULE visibility
     */
    public function populate_single_data_array() {
        $this->singleentrydata = array(
            'name' => 'Slot No. X',
            'attachments' => '',
            'embedcode' => '',
            'weblink' => 'http://www.open.ac.uk/',
            'urltitle' => 'Vesica Timeline',
            'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
            'description' => mt_rand(). ' - The Best YouTube Link Ever',
            'tags' => array('Stark', 'Lannister', 'Targereyen'),
            'ownership' => 0,
            'sid' => 0 // For a new slot.
        );
    }

    /**
     * Tests creation and deletion of subscriptions via the functions in the subscription api.
     */
    public function test_create_update_delete_subscription() {
        $this->resetAfterTest(true);

        // To keep the test simple, we'll create a pinboard slot and one non pinboard slot.
        // Visibility on both is module.
        $this->singleentrydata = $this->generator->generate_single_data_array($this->users->students->one);
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);
        $this->singleentrydata['name'] = 'Slot Pinboarder';
        $this->pbslotid = \mod_openstudio\local\api\content::create_in_pinboard(
                $this->studiolevels->id, $this->users->students->two->id, $this->singleentrydata);
        $this->assertGreaterThan(0, \mod_openstudio\local\api\subscription::create(
                \mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->three->id, $this->studiolevels->id,
                \mod_openstudio\local\api\subscription::FORMAT_HTML));

        // The first one is an odd check as they also work with equalsTrue
        // because if it returns the ID 1 it is true!
        $this->assertGreaterThan(1, \mod_openstudio\local\api\subscription::create(
                \mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->three->id, $this->studiolevels->id,
                \mod_openstudio\local\api\subscription::FORMAT_HTML, $this->contentdata->id));

        // Check that duplicate only returns true.
        $this->assertEquals(true, \mod_openstudio\local\api\subscription::create(
                \mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->three->id, $this->studiolevels->id,
                \mod_openstudio\local\api\subscription::FORMAT_HTML, $this->contentdata->id));

        // This one is not a duplicate but should return an id larger than 1.
        $this->assertGreaterThan(1, \mod_openstudio\local\api\subscription::create(
                \mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->three->id, $this->studiolevels->id,
                \mod_openstudio\local\api\subscription::FORMAT_HTML, $this->pbslotid));

        // Check that duplicate only returns true again.
        $this->assertEquals(true, \mod_openstudio\local\api\subscription::create(
                \mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->three->id, $this->studiolevels->id,
                \mod_openstudio\local\api\subscription::FORMAT_HTML, $this->pbslotid));

        // Let's Create something now and then delete it.
        $x = \mod_openstudio\local\api\subscription::create(
                \mod_openstudio\local\api\subscription::MODULE, $this->users->students->eight->id,
                $this->studiolevels->id, \mod_openstudio\local\api\subscription::FORMAT_HTML, $this->contentdata->id);
        $this->assertGreaterThan(1, $x);

        // Let's try and update this first.
        $this->assertEquals(true, \mod_openstudio\local\api\subscription::update(
                $x, \mod_openstudio\local\api\subscription::FORMAT_PLAIN,
                \mod_openstudio\local\api\subscription::FREQUENCY_HOURLY));
        $this->assertEquals(true, \mod_openstudio\local\api\subscription::update($x, '', '', time()));

        // Let's try and make up an update.
        $this->assertEquals(false, \mod_openstudio\local\api\subscription::update(99999, '', '', time()));

        // Another user should not be able to delete.
        $this->assertEquals(false, \mod_openstudio\local\api\subscription::delete($x, $this->users->students->five->id, true));

        // Student 8 him/her self should be able to delete it.
        $this->assertEquals(true, \mod_openstudio\local\api\subscription::delete($x, $this->users->students->eight->id, true));
    }

    /**
     * This function tests actually sending a subscription email.
     *
     * This test is not generally run, but is here for developers to debug subscription emails.
     * It will only run if you set $testemail below, and will only actually send emails if you configure
     * your development environment to do so.
     */
    public function test_processs() {
        global $CFG;
        $testemail = ''; // Do not commit changes to this line!
        if (empty($testemail)) {
            $this->markTestSkipped('This test is not run unless a test email is set');
        }
        $CFG->noemailever = 0;

        $emailuser = $this->getDataGenerator()->create_user(
                array('email' => $testemail, 'username' => 'emailuser',
                        'firstname' => 'Email', 'lastname' => 'User'));
        $this->getDataGenerator()->enrol_user($emailuser->id, $this->course->id, $this->studentroleid, 'manual');
        $emailcontent = $this->generator->create_contents([
            'openstudio' => 'OS1',
            'userid' => $emailuser->id,
            'name' => 'Test',
            'description' => 'Test',
            'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
            'contenttype' => \mod_openstudio\local\api\content::TYPE_TEXT
        ]);

        $this->assertEquals(true, \mod_openstudio\local\api\subscription::create(\mod_openstudio\local\api\subscription::MODULE,
                $emailuser->id, $this->studiolevels->id, \mod_openstudio\local\api\subscription::FORMAT_HTML));

        $this->generator->create_notification([
            'userid' => $emailuser->id,
            'userfrom' => $this->users->students->one->id,
            'contentid' => $emailcontent,
        ]);
        $this->generator->create_notification([
            'userid' => $emailuser->id,
            'userfrom' => $this->users->students->one->id,
            'contentid' => $emailcontent,
        ]);
        // This notification should not be in the email.  Even on the first run, the timestamp threshold is set to 100.
        $this->generator->create_notification([
            'userid' => $emailuser->id,
            'userfrom' => $this->users->students->one->id,
            'contentid' => $emailcontent,
            'timecreated' => 10
        ]);

        // Add a studio level subscription for student 2 who is on the same module as
        // one and three who own courses.

        $emails = \mod_openstudio\local\api\subscription::process($this->studiolevels->id);
        $this->assertEquals(true, $emails);
    }

    /**
     * Check email headers when subscribed to studio.
     */
    public function test_email_process_headers(): void {
        $this->resetAfterTest(true);
        $content = $this->generator->create_contents([
            'openstudio' => 'OS1',
            'userid' => $this->users->students->one->id,
            'name' => 'Test',
            'description' => 'Test',
            'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
            'contenttype' => \mod_openstudio\local\api\content::TYPE_TEXT,
        ]);

        \mod_openstudio\local\api\subscription::create(\mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->one->id, $this->studiolevels->id, \mod_openstudio\local\api\subscription::FORMAT_HTML);

        $this->generator->create_notification([
            'userid' => $this->users->students->one->id,
            'userfrom' => $this->users->students->two->id,
            'contentid' => $content,
        ]);

        unset_config('noemailever');
        $sink = $this->redirectEmails();
        \mod_openstudio\local\api\subscription::process($this->studiolevels->id);
        $messages = $sink->get_messages();

        // Check 1 email sent.
        $this->assertEquals(1, count($messages));
        $this->assertEquals(\core_user::get_noreply_user()->email, $messages[0]->from);
        // Check headers.
        $this->assertStringContainsString('List-Unsubscribe-Post:', $messages[0]->header);
        $this->assertStringContainsString('user=' . $this->users->students->one->id, $messages[0]->header);
        $this->assertStringContainsString('&key=' . rss::generate_key($this->users->students->one->id, rss::UNSUBSCRIBE)
                , $messages[0]->header);
        $this->assertStringContainsString('Unsubscribe from this stream', $messages[0]->body);
    }
}
