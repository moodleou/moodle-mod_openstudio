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
 * Studip unit tests - test locallib functions
 *
 * @package    mod_openstudio
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/openstudio/api/group.php'); // Until this is refactored.

class mod_openstudio_lastupload_test extends advanced_testcase {

    private $singleentrydata;
    private $singleentrydataprivate;
    private $course;
    private $generator; // Contains mod_openstudio specific data generator functions.
    private $studiolevels; // Generic studio instance with no levels or slots.
    private $totalcontents;
    private $pinboardcontents;

    protected function setUp() {
        $this->resetAfterTest(true);
        $this->teacherroleid = 3;
        $this->studentroleid = 5;
        $this->totalcontents = 24; // This is what the scripts below create for ONE CMID.
        $this->pinboardcontents = 3; // This is what the scripts below create for ONE CMID.

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create user.
        $this->users = new stdClass();
        $this->users->students = new stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                array('email' => 'student1@ouunittest.com', 'username' => 'student1'));
        $this->users->students->two = $this->getDataGenerator()->create_user(
                array('email' => 'student2@ouunittest.com', 'username' => 'student2'));
        $this->users->students->three = $this->getDataGenerator()->create_user(
                array('email' => 'student3@ouunittest.com', 'username' => 'student3'));

        $this->users->teachers = new stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                array('email' => 'teacher1@ouunittest.com', 'username' => 'teacher1'));

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Enroll our student in the course.
        $this->getDataGenerator()->enrol_user($this->users->students->one->id, $this->course->id,
                $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->students->two->id, $this->course->id,
                $this->studentroleid, 'manual');

        $this->getDataGenerator()->enrol_user($this->users->students->three->id, $this->course->id,
                $this->studentroleid, 'manual');

        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->one->id, $this->course->id, $this->teacherroleid, 'manual');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS1'));
        // Create generic studios1.
        $this->studiolevels->one = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS2'));
        // Create generic studios2.
        $this->studiolevels->two = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS3'));
        // Create generic studios3.
        $this->studiolevels->three = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS4'));
        // Create generic studios4.
        $this->studiolevels->four = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS5'));
        // Create generic studios5.
        $this->studiolevels->final = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS6'));

        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
        $this->studiolevels->one->leveldata = $this->generator->create_mock_levels(  $this->studiolevels->one->id);
        $this->studiolevels->two->leveldata = $this->generator->create_mock_levels($this->studiolevels->two->id);
        $this->studiolevels->three->leveldata = $this->generator->create_mock_levels($this->studiolevels->three->id);
        $this->studiolevels->four->leveldata = $this->generator->create_mock_levels($this->studiolevels->four->id);
        $this->studiolevels->final->leveldata = $this->generator->create_mock_levels($this->studiolevels->final->id);
    }

    protected function tearDown() {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
    }

    /**
     * publics array with slot data to create slot(s).
     */
    private function create_public_single_data_array() {
        $this->singleentrydata = array(
                'name' => 'Test Lat Upload Studio',
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.open.ac.uk/',
                'urltitle' => 'Lat Upload Studio',
                'visibility' => STUDIO_VISIBILITY_MODULE,
                'description' => 'Lat Upload Studio Description',
                'tags' => array('AB1'),
                'ownership' => 0,
                'sid' => 0 // For a new slot.
        );
    }

    /**
     * private array with slot data to create slot(s).
     */
    private function create_private_single_data_array() {
        $this->singleentrydataprivate = array(
                'name' => 'Test Lat Upload Studio Private ABCCC',
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.open.ac.uk/',
                'urltitle' => 'Lat Upload Studio',
                'visibility' => STUDIO_VISIBILITY_PRIVATE,
                'description' => 'Lat Upload Studio Description',
                'tags' => array('AB1'),
                'ownership' => 0,
                'sid' => 1 // For a new slot.
        );
    }

    /**
     * Publics array with multi slot data to create slot(s).
     */
    private function create_public_multi_data_array($name, $description, $visibility, $tag, $id) {
        return array (
                'name' => $name,
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.open.ac.uk/',
                'urltitle' => 'Lat Upload Studio',
                'visibility' => $visibility,
                'description' => $description,
                'tags' => array($tag),
                'ownership' => 0,
                'sid' => $id // For a new slot.
        );
    }

    /**
     * Creates new public slots.
     */
    private function create_public_slot_data() {
        $blockarray = current($this->studiolevels->leveldata['contentslevels']);
        $blockid = key($this->studiolevels->leveldata['contentslevels']);

        $activityarray = current($this->studiolevels->leveldata['contentslevels'][$blockid]);
        $activityid = key($this->studiolevels->leveldata['contentslevels'][$blockid]);

        $contentarray = current($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);
        $contentid = key($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);
        $this->contentid = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id, 3,
                $this->studiolevels->leveldata['contentslevels'][$blockid][$activityid][$contentid], $this->singleentrydata);
        $this->contentdata = mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentid);
    }

    /**
     * Creates new private slots
     */
    private function create_private_slot_data() {
        $blockarray = current($this->studiolevels->two->leveldata['contentslevels']);
        $blockid = key($this->studiolevels->two->leveldata['contentslevels']);

        $activityarray = current($this->studiolevels->two->leveldata['contentslevels'][$blockid]);
        $activityid = key($this->studiolevels->two->leveldata['contentslevels'][$blockid]);

        $contentarray = current($this->studiolevels->two->leveldata['contentslevels'][$blockid][$activityid]);
        $contentid = key($this->studiolevels->two->leveldata['contentslevels'][$blockid][$activityid]);
        $this->contentid = mod_openstudio\local\api\content::create($this->studiolevels->two->id,
                $this->users->students->one->id, 0,
                $this->studiolevels->two->leveldata['contentslevels'][$blockid][$activityid][$contentid],
                $this->singleentrydataprivate);
        $this->contentdata = mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentid);
    }

    /**
     * Creates multi new slots in studio 3.
     */
    private function create_multi_slots_data($n, $visi, $studio) {
        $blockarray = current($studio->leveldata['contentslevels']);
        $blockid = key($studio->leveldata['contentslevels']);

        $activityarray = current($studio->leveldata['contentslevels'][$blockid]);
        $activityid = key($studio->leveldata['contentslevels'][$blockid]);

        $contentarray = current($studio->leveldata['contentslevels'][$blockid][$activityid]);
        $contentid = key($studio->leveldata['contentslevels'][$blockid][$activityid]);
        for ($i = 1; $i <= $n; $i++) {
            $number = rand(1, 100);
            $name = 'New Slot '.$i.$number;
            $description = 'New Description'.$i.$number;
            $visibility = $visi;
            $tag = 'Tag Add'.$i.$number;
            $sid = $i.$number;
            $data = $this->create_public_multi_data_array($name, $description, $visibility, $tag, $sid);
            $this->contentid = mod_openstudio\local\api\content::create($studio->id,
                    $this->users->students->one->id, 0,
                    $studio->leveldata['contentslevels'][$blockid][$activityid][$contentid], $data);
            $this->contentdata = mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentid);
        }
    }

    /**
     *  Get all slot of studio with time add.
     */
    private function get_all_slot_with_time_add($studiid, $DB) {
        $limittimeadd = strtotime(date('Y-m-d', strtotime('-30 days')));
        $sql = "SELECT ss.*, ssi.timeadded
        FROM {openstudio_contents} ss
        INNER JOIN {openstudio_content_items} ssi ON ss.id = ssi.containerid
        WHERE ss.openstudioid =:openstudioid AND ss.deletedtime IS NULL
            AND ssi.timeadded >= :timelimit AND ssi.containertype =:stdcontenttype
        ORDER BY ssi.timeadded DESC";

        $slotparams = array(
                'openstudioid'     => $studiid,
                'stdcontenttype' => STUDIO_SLOT_ITEM_CONTAINER_SLOT,
                'timelimit' => $limittimeadd
        );
        return $DB->get_recordset_sql($sql, $slotparams);
    }

    /**
     *  Upload all slot of studio with time add.
     */
    private function upload_timeadd_slot($studiid, $DB, $time) {
        $sql = "SELECT ssi.*
        FROM {openstudio_contents} ss
        INNER JOIN {openstudio_content_items} ssi ON ss.id = ssi.containerid
        WHERE ss.openstudioid =:openstudioid AND ss.deletedtime IS NULL AND ssi.containertype =:stdcontenttype";

        $slotparams = array(
                'openstudioid'     => $studiid,
                'stdcontenttype' => STUDIO_SLOT_ITEM_CONTAINER_SLOT,
                'timelimit' => $time
        );
        $data = $DB->get_recordset_sql($sql, $slotparams);
        foreach ($data as $key) {
            $key->timeadded = $time;
            $result = $DB->update_record('openstudio_content_items', $key);
        }
        $data->close();
    }

    /**
     *  upload slot of openstudio with time add.
     */
    private function upload_timeadd_one_slot($DB, $time, $slotid) {
        $slot = $DB->get_record('openstudio_content_items', array('containerid' => $slotid));
        $slot->timeadded = $time;
        $DB->update_record('openstudio_content_items', $slot);
    }

    public function setup_before_test() {
        $this->resetAfterTest(true);
        $this->create_public_single_data_array();
        $this->create_private_single_data_array();
        $this->create_public_slot_data();
        $this->create_private_slot_data();
    }

    /**
     * public slot in studio all user can view
     */
    public function test_all_user_saw_lastupload() {
        global $DB;
        $userid1 = $this->users->students->one->id;
        $userid2 = $this->users->students->two->id;
        $this->setUser($userid1);
        $n = 5;
        $cm = get_coursemodule_from_instance('openstudio', $this->studiolevels->id);
        $cminstance = $DB->get_record('openstudio', array('id' => $cm->instance), '*', MUST_EXIST);
        $permissions = mod_openstudio\local\util::check_permission($cm, $cminstance, $this->course);
        $this->create_multi_slots_data($n, mod_openstudio\local\api\content::VISIBILITY_MODULE, $this->studiolevels);
        $record = $this->get_all_slot_with_time_add($this->studiolevels->id, $DB);
        // Update timeadd of n slot timeadd = timeadd - 1 days.
        foreach ($record as $key => $value) {
            $checkpermission = mod_openstudio\local\util::can_read_content($cminstance, $permissions, $value);
            $this->assertEquals(true, $checkpermission);
            $limittimeadd = strtotime(date('Y-m-d', strtotime('-'.$n.' days')));
            $this->upload_timeadd_one_slot($DB, $limittimeadd, $value->id);
            $n--;
        }
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course, $userid1);
        $lastuploadtime = strtotime(date('Y-m-d', strtotime('-1 days')));
        $this->assertEquals($lastuploadtime, $result);

        $this->setUser($userid2);
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course, $userid2);
        $this->assertEquals($lastuploadtime, $result);
    }

    /**
     * Studio not have slot return null.
     */
    public function test_empty_studio() {
        global $DB;
        $this->setup_before_test();
        $userid1 = $this->users->students->one->id;
        $userid2 = $this->users->students->two->id;
        $this->setUser($userid1);
        // Studio not have slot return null.
        $cm = get_coursemodule_from_instance('openstudio', $this->studiolevels->one->id);
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course, $userid1);
        $record = $this->get_all_slot_with_time_add($this->studiolevels->one->id, $DB);
        $this->assertEquals(false, $record->valid());

        $this->setUser($userid2);
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course, $userid2);
        $record = $this->get_all_slot_with_time_add($this->studiolevels->one->id, $DB);
        $this->assertEquals(false, $record->valid());
    }

    /**
     * There is 1 slot in a studio but the student cannot view it
     */
    public function test_difference_users_see_difference_slot() {
        global $DB;
        $this->setup_before_test();
        $userid1 = $this->users->students->one->id;
        $userid2 = $this->users->students->two->id;
        $this->setUser($userid1);
        $limittimeadd = strtotime(date('Y-m-d', strtotime('-1 days')));
        // User 1 can see.
        $this->upload_timeadd_slot($this->studiolevels->two->id, $DB, $limittimeadd);
        $cm = get_coursemodule_from_instance('openstudio', $this->studiolevels->two->id);
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course, $userid1);
        $this->assertEquals($limittimeadd, $result);
        // User 2 can't see.
        $this->setUser($userid2);
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course, $userid2);
        $this->assertEmpty($result);
    }

    /**
     * There are several slots in the studio and the student can view them all.
     */
    public function test_user_can_view_all_slots() {
        global $DB;
        $userid1 = $this->users->students->one->id;
        $userid2 = $this->users->students->two->id;
        $this->setUser($userid1);
        // There are several slots in the studio and the student can view them all.
        $this->setUser($userid1);
        $n = 6;
        $this->create_multi_slots_data($n, STUDIO_VISIBILITY_MODULE, $this->studiolevels->three);
        $record = $this->get_all_slot_with_time_add($this->studiolevels->three->id, $DB);
        // Update timeadd of n slot timeadd = timeadd - 1 days.
        foreach ($record as $key => $value) {
            $limittimeadd = strtotime(date('Y-m-d', strtotime('-'.$n.' days')));
            $this->upload_timeadd_one_slot($DB, $limittimeadd, $value->id);
            $n--;
        }
        $cm = get_coursemodule_from_instance('openstudio', $this->studiolevels->three->id);
        $cminstance = $DB->get_record('openstudio', array('id' => $cm->instance), '*', MUST_EXIST);
        $permissions = mod_openstudio\local\util::check_permission($cm, $cminstance, $this->course);
        $record = $this->get_all_slot_with_time_add($this->studiolevels->three->id, $DB);
        foreach ($record as $key => $value) {
            $checkpermission = mod_openstudio\local\util::can_read_content($cminstance, $permissions, $value);
            $this->assertEquals(true, $checkpermission);
        }
        $lastuploadtime = strtotime(date('Y-m-d', strtotime('-1 days')));
        // User 1 can view last upload.
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course, $userid1);
        $this->assertEquals($lastuploadtime, $result);
        // User 2 can view last upload too. Same lastuploadtime.
        $this->setUser($userid2);
        foreach ($record as $key => $value) {
            $checkpermission = mod_openstudio\local\util::can_read_content($cminstance, $permissions, $value);
            $this->assertEquals(true, $checkpermission);
        }
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course, $userid1);
        $this->assertEquals($lastuploadtime, $result);
    }

    /**
     * There are several slots in the studio and the student can view the second, but not the first.
     */
    public function test_several_slots_difference_permission_view() {
        global $DB;
        $userid1 = $this->users->students->one->id;
        $userid2 = $this->users->students->two->id;
        $userid3 = $this->users->students->three->id;
        $this->setUser($userid1);
        $limittimeadd1 = strtotime(date('Y-m-d', strtotime('-1 days')));
        $limittimeadd3 = strtotime(date('Y-m-d', strtotime('-3 days')));
        // There are several slots in the studio and the student can view the second, but not the first.
        $this->create_multi_slots_data(1, STUDIO_VISIBILITY_MODULE, $this->studiolevels->four);
        $this->upload_timeadd_one_slot($DB, $limittimeadd3, $this->contentid);
        $this->create_multi_slots_data(1, STUDIO_VISIBILITY_PRIVATE, $this->studiolevels->four);
        $this->upload_timeadd_one_slot($DB, $limittimeadd1, $this->contentid);
        $cm = get_coursemodule_from_instance('openstudio', $this->studiolevels->four->id);
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course);
        // User 1 can see 2 slot, last upload is slot private. one day before.
        $this->assertEquals($limittimeadd1, $result);

        $this->setUser($userid2);
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course);
        // User 2 can see 1 slot, last upload is slot module. three day before, limittimeadd3.
        $this->assertEquals($limittimeadd3, $result);

        $this->setUser($userid3);
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course);
        // User 3 can see 1 slot, last upload is slot module. three day before, limittimeadd3.
        $this->assertEquals($limittimeadd3, $result);
    }

    /**
     * There are several slots in the studio and the student can view them all, but they are all over 30 days old.
     */
    public function test_several_slots_create_before_30_day() {
        global $DB;
        $userid1 = $this->users->students->one->id;
        $userid2 = $this->users->students->two->id;
        $this->setUser($userid1);
        $limittimeadd = strtotime(date('Y-m-d', strtotime('-31 days')));
        // There are several slots in the studio and the student can view them all, but they are all over 30 days old.
        $this->setUser($userid1);
        $n = 5;
        $cm = get_coursemodule_from_instance('openstudio', $this->studiolevels->final->id);
        $cminstance = $DB->get_record('openstudio', array('id' => $cm->instance), '*', MUST_EXIST);
        $permissions = mod_openstudio\local\util::check_permission($cm, $cminstance, $this->course);
        $this->create_multi_slots_data($n, STUDIO_VISIBILITY_MODULE, $this->studiolevels->final);
        $record = $this->get_all_slot_with_time_add($this->studiolevels->final->id, $DB);
        // Update timeadd of n slot timeadd = timeadd - 1 days;.
        foreach ($record as $key => $value) {
            $checkpermission = mod_openstudio\local\util::can_read_content($cminstance, $permissions, $value);
            // Check user have permission view slot.
            $this->assertEquals(true, $checkpermission);
            $limittimeadd = strtotime(date('Y-m-d', strtotime('(-31-'.$n.') days')));
            $this->upload_timeadd_one_slot($DB, $limittimeadd, $value->id);
            $n--;
        }
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course);
        // User 1 can't see last upload of slot module. Slot was created more than 30 day before.
        $this->assertEmpty($result);

        // User 2 can't see last upload of slot module. Slot was created more than 30 day before.
        $this->setUser($userid2);
        $result = mod_openstudio\local\util::get_last_modified($cm, $this->course);
        $this->assertEmpty($result);
    }
}
