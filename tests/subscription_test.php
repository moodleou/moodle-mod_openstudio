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
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/openstudio/api/subscription.php');
require_once('openstudio_testcase.php');

class mod_openstudio_subscription_testcase extends openstudio_testcase {

    private $pbslotid;

    /**
     * Sets up our fixtures.
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

        // Create course groups.
        $this->groups = new stdClass();
        $this->groupings = new stdClass();
        $this->groupings->a  = $this->getDataGenerator()->create_grouping(
                array('name' => 'Grouping A', 'courseid' => $this->course->id));
        $this->groups->one = $this->getDataGenerator()->create_group(
                array('courseid' => $this->course->id, 'name' => 'The Starks'));
        $this->groups->two = $this->getDataGenerator()->create_group(
                array('courseid' => $this->course->id, 'name' => 'The Lannisters'));

        // Add groups to our groupings.
        $insert = new stdClass();
        $insert->groupingid = $this->groupings->a->id;
        $insert->groupid = $this->groups->one->id;
        $DB->insert_record('groupings_groups', $insert);
        $insert->groupingid = $this->groupings->a->id;
        $insert->groupid = $this->groups->two->id;
        $DB->insert_record('groupings_groups', $insert);

        // Create Users.
        $this->users = new stdClass();
        $this->users->students = new stdClass();
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
        $this->users->teachers = new stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                array('email' => 'teacher1@ouunittest.com', 'username' => 'teacher1',
                        'firstname' => 'John', 'lastname' => 'Smith'));
        $this->users->teachers->two = $this->getDataGenerator()->create_user(
                array('email' => 'teacher2@ouunittest.com', 'username' => 'teacher2',
                        'firstname' => 'John', 'lastname' => 'Smith'));

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
                        $this->users->students->eight->id,
                        $this->users->students->nine->id,
                        $this->users->students->ten->id,
                        $this->users->teachers->two->id
                )
        ));

        // Create generic studios.
        // Set groupmode to separate groups.
        $this->studiolevels = $this->generator->create_instance(
                array('course' => $this->course->id),
                array('groupmode' => SEPARATEGROUPS, 'groupingid' => $this->groupings->a->id));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
    }

    protected function tearDown() {
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
            'visibility' => mod_openstudio\local\api\content::VISIBILITY_MODULE,
            'description' => mt_rand(). ' - The Best YouTube Link Ever',
            'tags' => array('Stark', 'Lannister', 'Targereyen'),
            'ownership' => 0,
            'sid' => 0 // For a new slot.
        );
    }

    /**
     * Populates the singleentrydata property with slotdetails and GROUP visibility
     */
    public function populate_group_data_array() {
        $this->singleentrydata = array(
            'name' => 'Slot No. X',
            'attachments' => '',
            'embedcode' => '',
            'weblink' => 'http://www.open.ac.uk/',
            'urltitle' => 'Vesica Timeline',
            'visibility' => mod_openstudio\local\api\content::VISIBILITY_GROUP,
            'description' => mt_rand(). ' - The Best YouTube Link Ever',
            'tags' => array('Stark', 'Lannister', 'Targereyen'),
            'ownership' => 0,
            'sid' => 0 // For a new slot.
        );
    }


    /**
     * Adds 3 flags each to our pinboard and non-pinboard slot.
     */
    public function add_flags() {
        // Add 3 flags to each of our 2 slots..
        studio_api_flags_toggle($this->contentid, mod_openstudio\local\api\flags::FAVOURITE,
                'on', $this->users->students->one->id);
        sleep(1);
        studio_api_flags_toggle($this->contentid, mod_openstudio\local\api\flags::FAVOURITE,
                'on', $this->users->students->four->id);
        sleep(1);
        studio_api_flags_toggle($this->contentid, mod_openstudio\local\api\flags::FAVOURITE,
                'on', $this->users->students->five->id);
        sleep(1);
        studio_api_flags_toggle($this->pbslotid, mod_openstudio\local\api\flags::MADEMELAUGH,
                'on', $this->users->students->six->id);
        sleep(1);
        studio_api_flags_toggle($this->pbslotid, mod_openstudio\local\api\flags::MADEMELAUGH,
                'on', $this->users->students->ten->id);
        sleep(1);
        studio_api_flags_toggle($this->pbslotid, mod_openstudio\local\api\flags::MADEMELAUGH,
                'on', $this->users->students->eight->id);
        sleep(1);
    }

    /**
     * Adds 2 comments to a slot (based on the id).
     * @param int $slotid
     */
    public function add_comments($slotid) {
        // Adds 2 comments to normal slot.
        studio_api_comments_create($slotid, $this->users->students->four->id, 'Fire and Blood');
        sleep(1);
        studio_api_comments_create($slotid, $this->users->students->three->id, 'Winter is Coming');
    }

    /**
     * Tests the studio_api_notification_slot() to see if it produced the desired filtered output.
     */
    public function test_notification_api_slot_filter() {
        $this->resetAfterTest(true);

        $this->populate_single_data_array();

        // To keep the test simple, we'll create a pinboard slot and one non pinboard slot.
        $this->populate_content_data();

        // Add flags.
        $this->add_flags();

        // Add flags.
        $this->add_comments($this->contentid);

        $slotactivity = studio_api_notification_slot($this->contentid, 99);

        $slots = array();
        foreach ($slotactivity as $y) {
            // Get the slotid.
            $slotid = $y->id;
            $slots[$slotid][] = $y;
        }
        // Slots should cointain just one array for our normal slot.
        $this->assertEquals(1, count($slots));

        // Run the filter function.
        $fslots = studio_api_notification_filter_slots($slots);

        $blockarray = current($this->studiolevels->leveldata['contentslevels']);
        $blockid = key($this->studiolevels->leveldata['contentslevels']);

        $activityarray = current($this->studiolevels->leveldata['contentslevels'][$blockid]);
        $activityid = key($this->studiolevels->leveldata['contentslevels'][$blockid]);

        $slotarray = current($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);
        $slotid = key($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);

        // Now let's loop through our slot's details, it should contain 5 objects, 3 for flags, 2 for comments.
        // All full of rich data.
        $flags = 0;
        $comments = 0;
        $records = 0;
        foreach ($fslots as $slot) {
            $this->assertEquals(6, count($slot));
            foreach ($slot as $record) {
                $records++;
                if ($records == 1) {
                    // Check that the first record is the right comment!
                    $this->assertEquals('Winter is Coming', $record->commenttext);
                }
                if ($record->commentid > 0) {
                    $comments++;
                    $this->assertNotEmpty($record->commenttext);
                }
                if ($record->flagid > 0) {
                    $flags++;
                    $this->assertNotEmpty($record->flagname);
                    $this->assertNotEmpty($record->flagdesc);
                }
                // Test general values in each record.
                $this->assertEquals(
                        $this->studiolevels->leveldata['contentslevels'][$blockid][$activityid][$slotid], $record->levelid);
                $this->assertEquals(3, $record->levelcontainer);
                $this->assertGreaterThan(0, $record->userid);
                $this->assertGreaterThan(0, $record->l3id);
                $this->assertGreaterThan(0, $record->l2id);
                $this->assertGreaterThan(0, $record->l1id);
                $this->assertNotEmpty($record->l1name);
                $this->assertNotEmpty($record->l2name);
                $this->assertNotEmpty($record->l3name);
            }
        }
        $this->assertEquals(3, $flags);
        $this->assertEquals(2, $comments);
    }

    /**
     * Creates a slot, adds comments and actions and tests if the right data is returned by functions
     * in the subscription api.
     */
    public function test_notification_api_slot() {
        $this->resetAfterTest(true);

        $this->populate_single_data_array();

        // To keep the test simple, we'll create a pinboard slot and one non pinboard slot.
        $this->populate_content_data();
        $this->singleentrydata['name'] = 'Slot Pinboarder';
        $this->pbslotid = mod_openstudio\local\api\content::create_in_pinboard(
                $this->studiolevels->id, $this->users->students->two->id, $this->singleentrydata);

        // Add flags.
        $this->add_flags();

        // Should not be false.
        $slotactivity = studio_api_notification_slot($this->contentid, 99);
        $pbslotactivity = studio_api_notification_slot($this->pbslotid, 99);
        $this->assertNotEquals(false, $slotactivity);
        $this->assertNotEquals(false, $pbslotactivity);

        // Let's test our normal slot.
        $count = 0;
        foreach ($slotactivity as $sa) {
            $count++;
            if ($sa->l3id == '') {
                // Other than the record from studio_slots, the rest should have the following characteristics.
                $this->assertGreaterThan(0, ($sa->commentid || $sa->flagid) || $sa->actionid);
                $this->assertEquals(0, $sa->levelid);
                $this->assertEquals(0, $sa->levelcontainer);
            } else {
                // Now, in our records from the slots table.
                $this->assertEquals($this->studiolevels->id, $sa->openstudioid);
                $this->assertEquals(3, $sa->levelcontainer);
                $this->assertEquals('Slot No. X', $sa->name);
                $this->assertEquals(0, $sa->flagid);
            }
        }
        // X 3 flags and one record from the slots table and one slot created action.
        $this->assertEquals(5, $count);

        // Let's test our pinboard slot.
        $count = 0;
        foreach ($pbslotactivity as $sa) {
            $count++;
            if ($sa->name == '') {
                // Other than the record from studio_slots, the rest should have the following characteristics.
                $this->assertGreaterThan(0, ($sa->commentid || $sa->flagid) || $sa->actionid);
                $this->assertEquals(0, $sa->levelid);
                $this->assertEquals(0, $sa->levelcontainer);
            } else {
                // Now, in our records from the slots table.
                $this->assertEquals($this->studiolevels->id, $sa->openstudioid);
                $this->assertEquals(0, $sa->levelcontainer);
                $this->assertEquals('Slot Pinboarder', $sa->name);
                $this->assertEquals(0, $sa->flagid);
            }
        }
        // X 3 flags and one record from the slots table and one slot created action.
        $this->assertEquals(5, $count);

        // Let's pick our normal slot for testing going forward.
        // Add some comments to it.
        $this->add_comments($this->contentid);

        $slotactivity = studio_api_notification_slot($this->contentid, 99);
        // Run the data again, total count should be 7, four with flags, 2 with comments...
        // X 1 for the slot and 1 for the created action.
        // Let's test our normal slot.
        $comments = 0;
        $flags = 0;
        $total = 0;
        foreach ($slotactivity as $sa) {
            $total++;
            // Let's make sure our comments come first.
            if ($total == 1) {
                $this->assertEquals('Winter is Coming', $sa->commenttext);
            }
            if ($total == 2) {
                $this->assertEquals('Fire and Blood', $sa->commenttext);
            }

            if ($sa->commentid > 0) {
                // Count comments.
                $comments++;
            } else if ($sa->flagid > 0) {
                // Count flags.
                $flags++;
            }
        }

        // 5 flags and comments and one record from the slots table and a slot created action.
        $this->assertEquals(7, $total);
        $this->assertEquals(3, $flags);
        $this->assertEquals(2, $comments);
    }

    /**
     * Creates a slot with GROUP visibility, adds comments and actions and
     * tests if the right data is returned by functions
     * in the subscription api.
     */
    public function test_notification_api_group_seperategroups() {
        $this->resetAfterTest(true);

        $this->populate_group_data_array();

        // To keep the test simple, we'll create a pinboard slot and one non pinboard slot.
        $this->populate_content_data();
        $this->singleentrydata['name'] = 'Slot Pinboarder';
        $this->pbslotid = mod_openstudio\local\api\content::create_in_pinboard(
                $this->studiolevels->id, $this->users->students->two->id, $this->singleentrydata);

        // Add Comments.
        $this->add_comments($this->contentid);
        $this->add_comments($this->pbslotid);

        // Add flags.
        $this->add_flags();

        // We know that querying data works as the underlying data structure of the query is the same.
        // What we really need to test here is that a user from a different group CAN see this
        // if the share setting is mod_openstudio\local\api\content::VISIBILITY_GROUP.
        // Student 9 is in another group and group mode is seperate, so this should not find the slot.
        $slotactivity = studio_api_notification_group(
                $this->users->students->nine->id, $this->studiolevels->id, array(), array(), 1);
        $this->assertEquals(false, $slotactivity);

        // Same group now, should NOT choke.
        $slotactivity = studio_api_notification_group(
                $this->users->students->one->id, $this->studiolevels->id, array(), array(), 1);
        $this->assertNotEquals(false, $slotactivity);

        $slots = array();
        foreach ($slotactivity as $y) {
            // Get the slotid.
            $slotid = $y->id;
            $slots[$slotid][] = $y;
        }

        $fslots = studio_api_notification_filter_slots($slots);
        // The $flsots should contain 2 entries, which will then further contain multiple.
        // StdClasses for each of flag / activity / comment.
        $this->assertEquals(2, count($fslots));
    }

    /**
     * Creates a slot with GROUP visibility, adds comments and actions and
     * tests if the right data is returned by functions
     * in the subscription api.
     */
    public function test_notification_api_group_visiblegroups() {
        $this->resetAfterTest(true);

        // Create a new studio instance with groupmode set to VISIBLEGROUPS.
        $this->studiolevels = $this->generator->create_instance(
                array('course' => $this->course->id),
                array('groupmode' => VISIBLEGROUPS, 'groupingid' => $this->groupings->a->id));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);

        $this->populate_group_data_array();

        // To keep the test simple, we'll create a pinboard slot and one non pinboard slot.
        $this->populate_content_data();
        $this->singleentrydata['name'] = 'Slot Pinboarder';
        $this->pbslotid = mod_openstudio\local\api\content::create_in_pinboard(
                $this->studiolevels->id, $this->users->students->two->id, $this->singleentrydata);

        // Add Comments.
        $this->add_comments($this->contentid);
        $this->add_comments($this->pbslotid);

        // Add flags.
        $this->add_flags();

        // We know that querying data works as the underlying data structure of the query is the same.
        // What we really need to test here is that a user from a different group CAN see this
        // if the share setting is mod_openstudio\local\flags\visibility::GROUP.
        // Student 9 is in another group so in the same grouping, and groupmode is VISIBLE, so this shouldn't return false.
        $slotactivity = studio_api_notification_group(
                $this->users->students->nine->id, $this->studiolevels->id, array(), array(), 1);
        $this->assertNotEquals(false, $slotactivity);

        // Same group now, should NOT choke.
        $slotactivity = studio_api_notification_group(
                $this->users->students->one->id, $this->studiolevels->id, array(), array(), 1);
        $this->assertNotEquals(false, $slotactivity);

        $slots = array();
        foreach ($slotactivity as $y) {
            // Get the slotid.
            $slotid = $y->id;
            $slots[$slotid][] = $y;
        }

        $fslots = studio_api_notification_filter_slots($slots);
        // The $flsots should contain 2 entries, which will then further contain multiple.
        // StdClasses for each of flag / activity / comment.
        $this->assertEquals(2, count($fslots));
    }

    /**
     * Creates a slot with MODULE visbility, adds comments and actions and tests
     * if the right data is returned by functions
     * in the subscription api.
     */
    public function test_notification_api_module() {
        $this->resetAfterTest(true);

        $this->populate_single_data_array();

        // To keep the test simple, we'll create a pinboard slot and one non pinboard slot.
        $this->populate_content_data();
        $this->singleentrydata['name'] = 'Slot Pinboarder';
        $this->pbslotid = mod_openstudio\local\api\content::create_in_pinboard(
                $this->studiolevels->id, $this->users->students->two->id, $this->singleentrydata);

        // Add Comments.
        $this->add_comments($this->contentid);

        // Add flags.
        $this->add_flags();

        // We know that querying data works as the underlying data structure of the query is the same.
        // What we really need to test here is that a user who is not in the course CANNOT see this.
        // Student 9 is in the same course so this should return true.
        $slotactivity = studio_api_notification_module(
                $this->users->students->nine->id, $this->studiolevels->id, array(), array(), 99);
        $this->assertNotEquals(false, $slotactivity);

        // Student 7 is not in the course.
        $slotactivity = studio_api_notification_module(
                $this->users->students->seven->id, $this->studiolevels->id, array(), array(), 99);
        $this->assertEquals(false, $slotactivity);

        // We already test details in test_notification_api_group so won't do it here again!
    }

    /**
     * Tests creation and deletion of subscriptions via the functions in the subscription api.
     */
    public function test_notification_api_create_update_delete_subscription() {
        $this->resetAfterTest(true);

        $this->populate_single_data_array();

        // To keep the test simple, we'll create a pinboard slot and one non pinboard slot.
        // Visibility on both is module.
        $this->populate_content_data();
        $this->singleentrydata['name'] = 'Slot Pinboarder';
        $this->pbslotid = mod_openstudio\local\api\content::create_in_pinboard(
                $this->studiolevels->id, $this->users->students->two->id, $this->singleentrydata);
        $this->assertGreaterThan(0, studio_api_notification_create_subscription(
                mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->three->id, $this->studiolevels->id,
                mod_openstudio\local\api\subscription::FORMAT_HTML));

        // The first one is an odd check as they also work with equalsTrue
        // because if it returns the ID 1 it is true!
        $this->assertGreaterThan(1, studio_api_notification_create_subscription(
                mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->three->id, $this->studiolevels->id,
                mod_openstudio\local\api\subscription::FORMAT_HTML, $this->contentid));

        // Check that duplicate only returns true.
        $this->assertEquals(true, studio_api_notification_create_subscription(
                mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->three->id, $this->studiolevels->id,
                mod_openstudio\local\api\subscription::FORMAT_HTML, $this->contentid));

        // This one is not a duplicate but should return an id larger than 1.
        $this->assertGreaterThan(1, studio_api_notification_create_subscription(
                mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->three->id, $this->studiolevels->id,
                mod_openstudio\local\api\subscription::FORMAT_HTML, $this->pbslotid));

        // Check that duplicate only returns true again.
        $this->assertEquals(true, studio_api_notification_create_subscription(
                mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->three->id, $this->studiolevels->id,
                mod_openstudio\local\api\subscription::FORMAT_HTML, $this->pbslotid));

        // Let's Create something now and then delete it.
        $x = studio_api_notification_create_subscription(
                mod_openstudio\local\api\subscription::MODULE, $this->users->students->eight->id,
                $this->studiolevels->id, mod_openstudio\local\api\subscription::FORMAT_HTML, $this->contentid);
        $this->assertGreaterThan(1, $x);

        // Let's try and update this first.
        $this->assertEquals(true, studio_api_notification_update_subscription(
                $x, mod_openstudio\local\api\subscription::FORMAT_PLAIN,
                mod_openstudio\local\api\subscription::FREQUENCY_HOURLY));
        $this->assertEquals(true, studio_api_notification_update_subscription($x, '', '', time()));

        // Let's try and make up an update.
        $this->assertEquals(false, studio_api_notification_update_subscription(99999, '', '', time()));

        // Another user should not be able to delete.
        $this->assertEquals(false, studio_api_notification_delete_subscription($x, $this->users->students->five->id, true));

        // Student 8 him/her self should be able to delete it.
        $this->assertEquals(true, studio_api_notification_delete_subscription($x, $this->users->students->eight->id, true));
    }

    /*
     * This function tests that notifications are being processed and the email_to_user() function
     * is actually being called in our api.
     * The email_to_user() function called in studio_api_notification_process_subscriptions()
     * chokes if the local php.ini file (which runs phpUnit) does not have a valid (meaning other than localhost)
     * smtphost defined.
     * Also, your users must have a valid first and last name as moodle creates UTTER garbage if you don't
     * define them.
     * And this utter garbage breaks the iconv() function in lib/textlib.class.php when trying to
     * render email content as utf-8.
     *
     * Given this issue: the test is disabled and developers can manually enable it if they wish
     * to tun it.
     *
    public function test_notification_api_process_subscriptions() {
        global $CFG;
        // Throws an error if this is not empty in moodlelib.php on line 5337.
        // Presumably to avoid sending emails when testing.
        $CFG->noemailever = 0;

        $this->resetAfterTest(true);

        $this->populate_single_data_array();

        // To keep the test simple, we'll create a pinboard slot and one non pinboard slot.
        // Visibility on both is module.
        $this->create_slots();

        // Add Comments.
        $this->add_comments($this->contentid);

        // Add flags.
        $this->add_flags();
        $this->resetAfterTest(true);
        $this->populate_single_data_array();

        // Add a studio level subscription for student 2 who is on the same module as
        // one and three who own courses.
        $this->assertEquals(true, studio_api_notification_create_subscription(mod_openstudio\local\api\subscription::MODULE,
                $this->users->students->two->id, $this->studiolevels->id, mod_openstudio\local\api\subscription::FORMAT_HTML));

        $emails = studio_api_notification_process_subscriptions();
        $this->assertEquals(true, $emails);
    }
     */

}
