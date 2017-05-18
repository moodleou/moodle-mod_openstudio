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

class rss_testcase extends \advanced_testcase {

    private $course;
    private $users;
    private $groups;
    private $groupings;
    private $generator; // Contains mod_openstudio specific data generator functions.
    private $studiolevels; // Generic studio instance with no levels or slots.
    private $totalslots;
    private $pinboardslots;
    private $keysalt;
    private $singleentrydata;

    /**
     * Sets up our fixtures.
     */
    protected function setUp() {
        global $DB;
        $this->resetAfterTest(true);
        $teacherroleid = 3;
        $studentroleid = 5;
        $this->totalslots = 24; // This is what the scripts below create for ONE CMID.
        $this->pinboardslots = 3; // This is what the scripts below create for ONE CMID.

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create course groups.
        $this->groups = new \stdClass();
        $this->groupings = new \stdClass();
        $this->groupings->a  = $this->getDataGenerator()->create_grouping(
                array('name' => 'Grouping A', 'courseid' => $this->course->id));
        $this->groups->one = $this->getDataGenerator()->create_group(
                array('courseid' => $this->course->id, 'name' => 'The Starks'));
        $this->groups->two = $this->getDataGenerator()->create_group(
                array('courseid' => $this->course->id, 'name' => 'The Lannisters'));
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
        $this->studiolevels = $this->generator->create_instance(
                array('course' => $this->course->id),
                array('groupmode' => 1, 'groupingid' => $this->groupings->a->id));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
    }

    protected function tearDown() {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';

    }
    /**
     * Defines a key salt for testing purposes.
     */
    public function define_key_salt() {
        $this->keysalt = 'There_is_a_beast_in_every_man_,_and_it_stirs_when_you_put_a_sword_in_his_hand';
    }

    /**
     * Populates array with slot data to create slot(s).
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
     * Tests the key generation function.
     */
    public function test_rss_api_generate_key() {
        $this->resetAfterTest(true);
        $this->define_key_salt();
        // See that the function generates a key which matches the expect sha1 hash.
        $this->assertEquals(\mod_openstudio\local\api\rss::generate_key($this->users->students->three->id,
                \mod_openstudio\local\api\rss::MODULE),
                sha1($this->users->students->three->id . '_' . \mod_openstudio\local\api\rss::MODULE . '_' . $this->keysalt));
    }

    /**
     * Tests the key validtion function.
     */
    public function test_rss_api_validate_key() {
        $this->resetAfterTest(true);
        $this->define_key_salt();
        // Generate a key for student 3.
        $key = \mod_openstudio\local\api\rss::generate_key(
                $this->users->students->three->id, \mod_openstudio\local\api\rss::ACTIVITY);
        // Validate the key - should return true.
        $isvalidated = \mod_openstudio\local\api\rss::validate_key(
                $key, $this->users->students->three->id, \mod_openstudio\local\api\rss::ACTIVITY);
        $this->assertEquals(true, $isvalidated);
        // Just because we're fun, pass garbage to the function and see if it does come back as false.
        $this->assertEquals(false, \mod_openstudio\local\api\rss::validate_key('a6NJdkjda86nbGujdnAKh#&!jafkIU',
                $this->users->students->three->id, \mod_openstudio\local\api\rss::ACTIVITY));
    }

    /**
     * Tests that the pinboard RSS feed generates correct data.
     */
    public function test_rss_api_pinboard() {
        $this->resetAfterTest(true);
        $this->populate_single_data_array();
        $this->setUser($this->users->students->one);
        // Let's create 30 pinboard slots for student1.
        for ($i = 0; $i < 30; $i++) {
            $this->singleentrydata['name'] = 'Slot No. ' . $i;
            ${'pbslot' . $i} = \mod_openstudio\local\api\content::create_in_pinboard(
                    $this->studiolevels->id, $this->users->students->one->id, $this->singleentrydata);

            // Let's add eight flags to each slot. 4 FAVOURITES & 4 MADEMELAUGH.
            \mod_openstudio\local\api\flags::toggle(${'pbslot' . $i}, \mod_openstudio\local\api\flags::FAVOURITE,
                    'on', $this->users->students->three->id);
            \mod_openstudio\local\api\flags::toggle(${'pbslot' . $i}, \mod_openstudio\local\api\flags::FAVOURITE,
                    'on', $this->users->students->four->id);
            \mod_openstudio\local\api\flags::toggle(${'pbslot' . $i}, \mod_openstudio\local\api\flags::FAVOURITE,
                    'on', $this->users->students->five->id);
            \mod_openstudio\local\api\flags::toggle(${'pbslot' . $i}, \mod_openstudio\local\api\flags::MADEMELAUGH,
                    'on', $this->users->students->six->id);
            \mod_openstudio\local\api\flags::toggle(${'pbslot' . $i}, \mod_openstudio\local\api\flags::MADEMELAUGH,
                    'on', $this->users->students->seven->id);
            \mod_openstudio\local\api\flags::toggle(${'pbslot' . $i}, \mod_openstudio\local\api\flags::MADEMELAUGH,
                    'on', $this->users->students->eight->id);
            \mod_openstudio\local\api\flags::toggle(${'pbslot' . $i}, \mod_openstudio\local\api\flags::MADEMELAUGH,
                    'on', $this->users->students->nine->id);

            // So time modified is different.
            sleep(1);

        }

        /*
         * Assuming user one has subscribed to his or her pinboard feed,
         * let's call the function.
         *
         * The results should return 25 slots.
         * 1. Check that the result is not empty or false!
         * 2. Slots should come back in descending order of date created
         * (datemodified in DB irrespective of creation or modification.)
         * 3. Each slot's levelid and levelcontainer should be 0.
         * 4. The userid on each slot should be that of student1.
         * 5. The studio id should match that of studio_level.
         * 6. Total slots returned should be no more than 25.
         */
        $pinboardrssslots = \mod_openstudio\local\api\rss::pinboard($this->users->students->one->id, $this->studiolevels->id, 0);
        // Check 1.
        $this->assertNotEquals(false, $pinboardrssslots);
        $returnedslots = 0;
        foreach ($pinboardrssslots as $slot) {
            $returnedslots++;

            if ($returnedslots > 1) {
                // Check 2. This will ONLY be true in this one instance so don't test for this after updating data!
                $this->assertEquals(1, ($nameno - $namenonext));
            }
            $nameparts = explode(' ', $slot->name);
            $nameno = $nameparts[2];
            $namenonext = $nameno - 1;
            // Check 3.
            $this->assertEquals(0, $slot->levelid);
            $this->assertEquals(0, $slot->levelcontainer);
            // Check 4.
            $this->assertEquals($this->users->students->one->id, $slot->userid);
            // Check 5.
            $this->assertEquals($this->studiolevels->id, $slot->studioid);
        }
        // Check 6.
        $this->assertLessThanOrEqual(25, $returnedslots);

        // Because we love excitement, let's modify a SLOT in both the slot AND tracking tables.
        // And see if it comes back first (as it should).
        $this->singleentrydata['description'] = 'This just in - UPDATED SLOT!!';
        $this->singleentrydata['name'] = 'This slot now has a new name!';
        // So timemodified increases.
        sleep(1);
        // Randomly picked $pbslot18 from those created in this function above.
        \mod_openstudio\local\api\content::update($this->users->students->one->id, $pbslot18, $this->singleentrydata);
        \mod_openstudio\local\api\flags::toggle($pbslot18, \mod_openstudio\local\api\flags::MADEMELAUGH,
                'on', $this->users->students->ten->id);
        // Let's query everything again and make sure $pbslot18 is the first result that comes back.
        $pinboardrssslots2 = \mod_openstudio\local\api\rss::pinboard($this->users->students->one->id,
                $this->studiolevels->id);
        // Just make sure we got something back!
        $this->assertNotEquals(false, $pinboardrssslots2);

        // Now loop through to run our test.
        foreach ($pinboardrssslots2 as $slot2) {
            $this->assertEquals($pbslot18, $slot2->id);
            $this->assertEquals($this->singleentrydata['name'], $slot2->name);
            // Just break after the first result if the test passes.
            break;
        }
    }

    /**
     * Tests that the Activity RSS feed generates correct data.
     */
    public function test_rss_api_activity() {
        $this->resetAfterTest(true);
        $this->populate_single_data_array();

        $slots = array();
        // Create slots for all level data.
        $slotcount = 0;
        foreach ($this->studiolevels->leveldata['contentslevels'] as $activitylevels) {
            foreach ($activitylevels as $slotlevels) {
                foreach ($slotlevels as $slotlevelid) {
                    $slotcount++;
                    $data = array(
                        'name' => 'Slot No. ' . $slotcount,
                        'attachments' => '',
                        'embedcode' => '',
                        'weblink' => 'http://www.open.ac.uk/',
                        'urltitle' => 'Vesica Timeline',
                        'visibility' => \mod_openstudio\local\api\content::VISIBILITY_PRIVATE,
                        'description' => 'YouTube link',
                        'tags' => array(random_string(), random_string(), random_string()),
                        'ownership' => 0,
                        'sid' => 0 // For a new slot.
                    );
                    $slots[$slotlevelid][$slotcount] = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                            $this->users->students->one->id, 3, $slotlevelid, $data); // Level 3 is for slots.

                    // Let's add some flags to each slot.
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::FAVOURITE,
                            'on', $this->users->students->three->id);
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::INSPIREDME,
                            'on', $this->users->students->four->id);
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::NEEDHELP,
                            'on', $this->users->students->five->id);
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::MADEMELAUGH,
                            'on', $this->users->students->six->id);

                    // So time modified is different.
                    sleep(1);
                }
            }
        }

        /*
         * Assuming user one has subscribed to his or her mystudio feed,
         * let's call the function.
         *
         * The results should return 25 slots.
         * 1. Check that the result is not empty or false!
         * 2. Slots should come back in descending order of date created
         * (datemodified in DB irrespective of creation or modification.)
         * 3. Each slot's levelcontainer should be 3.
         * 4. Each slot's levelid should NOT be 0.
         * 5. The userid on each slot should be that of student1.
         * 6. The studio id should match that of studio_level.
         * 7. Total slots returned should be no more than 25.
         */
        $activityslotsrss = \mod_openstudio\local\api\rss::activity($this->users->students->one->id, $this->studiolevels->id);
        // Check 1.
        $this->assertNotEquals(false, $activityslotsrss);
        $returnedslots = 0;
        foreach ($activityslotsrss as $slot) {
            $returnedslots++;
            // Check 2.
            if ($returnedslots > 1) {
                $this->assertGreaterThan($namenonext, $nameno);
            }
            $nameparts = explode(' ', $slot->name);
            $nameno = $nameparts[2];
            $namenonext = $nameno - 1;
            // Check 3.
            $this->assertEquals(3, $slot->levelcontainer);
            // Check 4.
            $this->assertGreaterThan(0, $slot->levelid);
            // Check 5.
            $this->assertEquals($this->users->students->one->id, $slot->userid);
            // Check 6.
            $this->assertEquals($this->studiolevels->id, $slot->studioid);
        }
        // Check 7.
        $this->assertLessThanOrEqual(25, $returnedslots);
    }

    /**
     * Tests that the group RSS feed generates correct data.
     */
    public function test_rss_api_group() {
        $this->resetAfterTest(true);
        $this->populate_single_data_array();
        $slots = array();
        // Create slots for all level data.
        $slotcount = 0;
        foreach ($this->studiolevels->leveldata['contentslevels'] as $activitylevels) {
            foreach ($activitylevels as $slotlevels) {
                foreach ($slotlevels as $slotlevelid) {
                    $slotcount++;
                    $data = array(
                        'name' => 'Slot No. ' . $slotcount,
                        'attachments' => '',
                        'embedcode' => '',
                        'weblink' => 'http://www.open.ac.uk/',
                        'urltitle' => 'Vesica Timeline',
                        'visibility' => rand(2, 3), // Fluctuate between group and module visibility.
                        'description' => 'YouTube link',
                        'tags' => array(random_string(), random_string(), random_string()),
                        'ownership' => 0,
                        'sid' => 0 // For a new slot.
                    );
                    // These slots will be created by student 8, who is in the same group as students 6, 9, 10.
                    $slots[$slotlevelid][$slotcount] = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                            $this->users->students->eight->id, 3, $slotlevelid, $data); // Level 3 is for slots.

                    // Let's add some flags to each slot.
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::FAVOURITE,
                            'on', $this->users->students->three->id);
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::INSPIREDME,
                            'on', $this->users->students->four->id);
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::NEEDHELP,
                            'on', $this->users->students->five->id);
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::MADEMELAUGH,
                            'on', $this->users->students->six->id);

                    // So time modified is different.
                    sleep(1);
                }
            }
        }

        // Should not be false for student1 even he/she is in a different group given the  slot is
        // shared with setting \mod_openstudio\local\api\content\VISIBILITY_GROUP.
        $groupslotsrssfalse = \mod_openstudio\local\api\rss::group($this->users->students->one->id, $this->studiolevels->id);
        $this->assertNotEquals(false, $groupslotsrssfalse);

        // Should not be false for 6 as 6 is in the same group as 8.
        $groupslotsrss = \mod_openstudio\local\api\rss::group($this->users->students->six->id, $this->studiolevels->id);
        $this->assertNotEquals(false, $groupslotsrss);

        // Should not be false for 7 as 7 does not exist.
        $groupslotsrss = \mod_openstudio\local\api\rss::group($this->users->students->seven->id, $this->studiolevels->id);
        $this->assertEquals(false, $groupslotsrss);

        // Should not be false for 8 as 8 is the creator!!.
        $groupslotsrss = \mod_openstudio\local\api\rss::group($this->users->students->eight->id, $this->studiolevels->id);
        $this->assertNotEquals(false, $groupslotsrss);

        $returnedslots = '';

        foreach ($groupslotsrss as $slot) {
            $returnedslots++;
            // Check that these are all slot level slots!
            $this->assertEquals(3, $slot->levelcontainer);
            // Check that they aren't pinboards or have levelid = 0.
            $this->assertGreaterThan(0, $slot->levelid);
            // Check that these belong to the correct studio.
            $this->assertEquals($this->studiolevels->id, $slot->studioid);
            // Check that our expected user does own all of these.
            $this->assertEquals($this->users->students->eight->id, $slot->userid);
            // Finally, as group will return both module and group slots (but within the group only.
            // This we have established by checking for user 7 and a false returning for users in other groups).
            // Let's then make sure all our slots are either module or group visibility.
            $this->assertContains($slot->visibility,
                    [\mod_openstudio\local\api\content::VISIBILITY_GROUP, \mod_openstudio\local\api\content::VISIBILITY_MODULE]);
        }
        // Make sure total is less than or equal to 25.
        $this->assertLessThanOrEqual(25, $returnedslots);
    }

    /**
     * Tests that the module RSS feed generates correct data.
     */
    public function test_rss_api_module() {
        $this->resetAfterTest(true);
        $this->populate_single_data_array();
        $slots = array();
        // Create slots for all level data.
        $slotcount = 0;
        foreach ($this->studiolevels->leveldata['contentslevels'] as $activitylevels) {
            foreach ($activitylevels as $slotlevels) {
                foreach ($slotlevels as $slotlevelid) {
                    $slotcount++;
                    $data = array(
                        'name' => 'Slot No. ' . $slotcount + 2,
                        'attachments' => '',
                        'embedcode' => '',
                        'weblink' => 'http://www.open.ac.uk/',
                        'urltitle' => 'Vesica Timeline',
                        'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
                        'description' => 'YouTube link',
                        'tags' => array(random_string(), random_string(), random_string()),
                        'ownership' => 0,
                        'sid' => 0 // For a new slot.
                    );

                    // These slots will be created by student 7, who is in the same group as students 6, 8, 9, 10.
                    // Level 3 is for slots.
                    $slots[$slotlevelid][$slotcount] = \mod_openstudio\local\api\content::create(
                            $this->studiolevels->id, $this->users->students->eight->id, 3, $slotlevelid, $data);
                    $data['name'] = 'Slot No. ' . $slotcount + 1;
                    $slots[$slotlevelid][$slotcount + 1] = \mod_openstudio\local\api\content::create(
                            $this->studiolevels->id, $this->users->students->six->id, 3, $slotlevelid, $data);
                    // Let's add some flags to each slot.
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::FAVOURITE,
                            'on', $this->users->students->three->id);
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::INSPIREDME,
                            'on', $this->users->students->four->id);
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::NEEDHELP,
                            'on', $this->users->students->five->id);
                    \mod_openstudio\local\api\flags::toggle(
                            $slots[$slotlevelid][$slotcount], \mod_openstudio\local\api\flags::MADEMELAUGH,
                            'on', $this->users->students->nine->id);

                    // So time modified is different.
                    sleep(1);
                }
            }
        }

        // Should not be false for another user on the course.
        $moduleslotsrss = \mod_openstudio\local\api\rss::module($this->users->students->nine->id, $this->studiolevels->id);
        $this->assertNotEquals(false, $moduleslotsrss);
        $returnedslots = '';

        foreach ($moduleslotsrss as $slot) {
            $returnedslots++;
            // Check that these are all slot level slots!
            $this->assertEquals(3, $slot->levelcontainer);
            // Check that they aren't pinboards or have levelid = 0.
            $this->assertGreaterThan(0, $slot->levelid);
            // Check that these belong to the correct studio.
            $this->assertEquals($this->studiolevels->id, $slot->studioid);
            // Check that our expected users own all of these.
            $this->assertContains($slot->userid,
                    array($this->users->students->six->id, $this->users->students->eight->id));
            // Let's make sure all our slots are group visibility.
            $this->assertEquals(\mod_openstudio\local\api\content::VISIBILITY_MODULE, $slot->visibility);
        }
        // Make sure total is less than or equal to 25.
        $this->assertLessThanOrEqual(25, $returnedslots);
    }

    /**
     * Tests that the slot RSS feed generates correct data.
     */
    public function test_rss_api_slot() {
        $this->resetAfterTest(true);
        $this->populate_single_data_array();
        // To keep the test simple, we'll create a pinboard slot and test on that.
        $myslotid = \mod_openstudio\local\api\content::create_in_pinboard(
                $this->studiolevels->id, $this->users->students->three->id, $this->singleentrydata);
        // Let's add eight flags to each slot. 3 FAVOURITES & 4 MADEMELAUGH. CONCLUSION: TOTAL = 7.
        \mod_openstudio\local\api\flags::toggle(
                $myslotid, \mod_openstudio\local\api\flags::FAVOURITE, 'on',  $this->users->students->one->id);
        \mod_openstudio\local\api\flags::toggle(
                $myslotid, \mod_openstudio\local\api\flags::FAVOURITE, 'on', $this->users->students->four->id);
        \mod_openstudio\local\api\flags::toggle(
                $myslotid, \mod_openstudio\local\api\flags::FAVOURITE, 'on', $this->users->students->five->id);
        \mod_openstudio\local\api\flags::toggle(
                $myslotid, \mod_openstudio\local\api\flags::MADEMELAUGH, 'on', $this->users->students->six->id);
        \mod_openstudio\local\api\flags::toggle(
                $myslotid, \mod_openstudio\local\api\flags::MADEMELAUGH, 'on', $this->users->students->ten->id);
        \mod_openstudio\local\api\flags::toggle(
                $myslotid, \mod_openstudio\local\api\flags::MADEMELAUGH, 'on', $this->users->students->eight->id);
        \mod_openstudio\local\api\flags::toggle(
                $myslotid, \mod_openstudio\local\api\flags::MADEMELAUGH, 'on', $this->users->students->nine->id);

        // Should not be false.
        $slotsrss = \mod_openstudio\local\api\rss::slot($myslotid);
        $this->assertNotEquals(false, $slotsrss);

        $returnedparams = 0;
        $flags = 0;
        foreach ($slotsrss as $item) {
            $returnedparams++;
            // Check that the flagid is greater than 1 as our flags start at 2.
            if ($item->name == '') {
                $this->assertGreaterThan(1, $item->flagid);
                $flags++;
            }
        }

        // Make sure total is less than or equal to 25.
        $this->assertLessThanOrEqual(25, $returnedparams);
        $this->assertEquals(8, $returnedparams);
        $this->assertEquals(7, $flags);
    }

}
