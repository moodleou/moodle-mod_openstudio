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

class levels_testcase extends \advanced_testcase {

    private $course;
    private $generator; // Contains mod_openstudio specific data generator functions.
    private $studiogeneric; // Generic studio instance with no levels or slots.

    protected function setUp(): void {
        $this->resetAfterTest(true);

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Assign Students a group.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $this->studiogeneric = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS1'));
    }

    protected function tearDown(): void {
        $this->course = '';
        $this->generator = '';
        $this->studiogeneric = '';

    }

    /**
     * Tests the level creation function of the levels api.
     */
    public function test_create() {
        $this->resetAfterTest(true);

        // Test function parameter checks are working.
        $this->assertFalse(\mod_openstudio\local\api\levels::create('level', array()));

        // Test level 1 creation.
        $level1id = \mod_openstudio\local\api\levels::create(1,
                array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $this->assertGreaterThan(0, $level1id, 'Level1 create failed');

        // Test level 2 creation.
        $level2id = \mod_openstudio\local\api\levels::create(2,
                array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $this->assertGreaterThan(0, $level2id, 'Level2 create failed');

        // Test level 3 creation.
        $level3id = \mod_openstudio\local\api\levels::create(3,
                array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));
        $this->assertGreaterThan(0, $level3id, 'Level3 create failed');
    }

    /**
     * Tests the \mod_openstudio\local\api\levels::get_records() and \mod_openstudio\local\api\levels::get_record() function.
     */
    public function test_get() {
        $this->resetAfterTest(true);

        // Let's first create sample data.
        $level1id = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $level2id = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level3id = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));
        $level3ida = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id, 'name' => 'slot a', 'sortorder' => 1));
        $level3idb = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id, 'name' => 'slot b',  'sortorder' => 2));

        // Soft delete 1 record.
        \mod_openstudio\local\api\levels::delete(3, $level3idb, true);

        // Let's get it back to see if it exists.
        $this->assertNotEquals(false, \mod_openstudio\local\api\levels::get_record(1, $level1id));
        $this->assertNotEquals(false, \mod_openstudio\local\api\levels::get_record(2, $level2id));
        $this->assertNotEquals(false, \mod_openstudio\local\api\levels::get_record(3, $level3id));

        // Try getting multiple with and without show deleted.
        // We'll get back 2 because we harddeleted $level3idb whether we include showdeleted or not.
        $this->assertEquals(2, count(\mod_openstudio\local\api\levels::get_records(3, $level2id, false)));
        $this->assertEquals(2, count(\mod_openstudio\local\api\levels::get_records(3, $level2id, true)));
    }

    public function test_soft_delete() {
        global $DB;
        // Let's first create sample data.
        $level1id = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $level2id = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level3id = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));

        // Assert inital state.
        $this->assertTrue($DB->record_exists('openstudio_level1',
                array('id' => $level1id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));

        // Test deleting level 3 just deletes that level.
        \mod_openstudio\local\api\levels::soft_delete(3, $level3id);
        $this->assertTrue($DB->record_exists('openstudio_level1',
                array('id' => $level1id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertFalse($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::SOFT_DELETED)));

        // Test we can un-delete the level.
        \mod_openstudio\local\api\levels::soft_delete(3, $level3id, true);
        $this->assertTrue($DB->record_exists('openstudio_level1',
                array('id' => $level1id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));

        // Test deleting level2 cascades to level3.
        \mod_openstudio\local\api\levels::soft_delete(2, $level2id);
        $this->assertTrue($DB->record_exists('openstudio_level1',
                array('id' => $level1id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertFalse($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::SOFT_DELETED)));
        $this->assertFalse($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::SOFT_DELETED)));

        // Test un-deletion cascades as well.
        \mod_openstudio\local\api\levels::soft_delete(2, $level2id, true);
        $this->assertTrue($DB->record_exists('openstudio_level1',
                array('id' => $level1id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));

        // Test deleting level1 cascades to level2 and level3.
        \mod_openstudio\local\api\levels::soft_delete(1, $level1id);
        $this->assertFalse($DB->record_exists('openstudio_level1',
                array('id' => $level1id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level1',
                array('id' => $level1id, 'status' => \mod_openstudio\local\api\levels::SOFT_DELETED)));
        $this->assertFalse($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::SOFT_DELETED)));
        $this->assertFalse($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::SOFT_DELETED)));

        // Test un-deletion cascades as well.
        \mod_openstudio\local\api\levels::soft_delete(1, $level1id, true);
        $this->assertTrue($DB->record_exists('openstudio_level1',
                array('id' => $level1id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
    }

    /**
     * Tests the \mod_openstudio\local\api\levels::delete() function.
     */
    public function test_delete() {
        global $DB;
        $this->resetAfterTest(true);

        // Let's first create sample data.
        $level1id = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $level2id1 = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level2id2 = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level3id1 = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id1, 'name' => 'slot', 'sortorder' => 1));
        $level3id2 = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id1, 'name' => 'slot', 'sortorder' => 1));
        $level3id3 = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id2, 'name' => 'slot', 'sortorder' => 1));

        // Assert initial state.
        $this->assertTrue($DB->record_exists('openstudio_level1', array('id' => $level1id)));
        $this->assertTrue($DB->record_exists('openstudio_level2', array('id' => $level2id1)));
        $this->assertTrue($DB->record_exists('openstudio_level2', array('id' => $level2id2)));
        $this->assertTrue($DB->record_exists('openstudio_level3', array('id' => $level3id1)));
        $this->assertTrue($DB->record_exists('openstudio_level3', array('id' => $level3id2)));
        $this->assertTrue($DB->record_exists('openstudio_level3', array('id' => $level3id3)));

        // Delete unpopulated levels
        // Deleting Level3 should only delete that level.
        \mod_openstudio\local\api\levels::delete(3, $level3id1, $this->studiogeneric->id);
        $this->assertTrue($DB->record_exists('openstudio_level1', array('id' => $level1id)));
        $this->assertTrue($DB->record_exists('openstudio_level2', array('id' => $level2id1)));
        $this->assertTrue($DB->record_exists('openstudio_level2', array('id' => $level2id2)));
        $this->assertFalse($DB->record_exists('openstudio_level3', array('id' => $level3id1)));
        $this->assertTrue($DB->record_exists('openstudio_level3', array('id' => $level3id2)));
        $this->assertTrue($DB->record_exists('openstudio_level3', array('id' => $level3id3)));

        // Deleting Level2 should cascade to level3.
        \mod_openstudio\local\api\levels::delete(2, $level2id1, $this->studiogeneric->id);
        $this->assertTrue($DB->record_exists('openstudio_level1', array('id' => $level1id)));
        $this->assertFalse($DB->record_exists('openstudio_level2', array('id' => $level2id1)));
        $this->assertTrue($DB->record_exists('openstudio_level2', array('id' => $level2id2)));
        $this->assertFalse($DB->record_exists('openstudio_level3', array('id' => $level3id2)));
        $this->assertTrue($DB->record_exists('openstudio_level3', array('id' => $level3id3)));

        // Deleting Level1 should cascade to all levels.
        \mod_openstudio\local\api\levels::delete(1, $level1id, $this->studiogeneric->id);
        $this->assertFalse($DB->record_exists('openstudio_level1', array('id' => $level1id)));
        $this->assertFalse($DB->record_exists('openstudio_level2', array('id' => $level2id2)));
        $this->assertFalse($DB->record_exists('openstudio_level3', array('id' => $level3id3)));
    }

    public function test_delete_populated() {
        global $DB;
        $user = $this->getDataGenerator()->create_user(array('email' => 'student1@ouunittest.com', 'username' => 'student1'));
        $level1id = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $level2id = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level3id = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));

        $this->generator->create_contents([
                'openstudio' => 'OS1',
                'name' => random_string(),
                'description' => random_string(),
                'levelcontainer' => 3,
                'levelid' => $level3id,
                'userid' => $user->id]);

        // Calling delete() in a level with content beneath it should only soft delete.
        // Assert inital state.
        $this->assertTrue($DB->record_exists('openstudio_level1',
                array('id' => $level1id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::ACTIVE)));

        \mod_openstudio\local\api\levels::delete(1, $level1id, $this->studiogeneric->id);
        $this->assertTrue($DB->record_exists('openstudio_level1',
                array('id' => $level1id, 'status' => \mod_openstudio\local\api\levels::SOFT_DELETED)));
        $this->assertTrue($DB->record_exists('openstudio_level2',
                array('id' => $level2id, 'status' => \mod_openstudio\local\api\levels::SOFT_DELETED)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('id' => $level3id, 'status' => \mod_openstudio\local\api\levels::SOFT_DELETED)));
    }

    /**
     * Tests the \mod_openstudio\local\api\levels::update() function.
     */
    public function test_update() {
        $this->resetAfterTest(true);

        $level1id = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $level2id = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level3id = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));
        $this->assertNotEquals(
                false, \mod_openstudio\local\api\levels::update(3, $level3id,
                        array('name' => 'Silly Sally Saw Sixty Six Thistle Stix!')));
        $record = \mod_openstudio\local\api\levels::get_record(3, $level3id);
        $this->assertEquals('Silly Sally Saw Sixty Six Thistle Stix!', $record->name);
    }

    public function test_get_name() {
        $level1name = random_string();
        $level2name = random_string();
        $level3name = random_string();
        $level1id = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => $level1name, 'sortorder' => 1));
        $level2id = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => $level2name, 'sortorder' => 1));
        $level3id = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id, 'name' => $level3name, 'sortorder' => 1));

        $names = \mod_openstudio\local\api\levels::get_name(0, 0);
        $this->assertEquals('Pinboard', $names->level1name);
        $this->assertEmpty($names->level2name);
        $this->assertEmpty($names->level3name);

        $names = \mod_openstudio\local\api\levels::get_name(1, $level1id);
        $this->assertEquals($level1name, $names->level1name);
        $this->assertEmpty($names->level2name);
        $this->assertEmpty($names->level3name);

        $names = \mod_openstudio\local\api\levels::get_name(2, $level2id);
        $this->assertEquals($level1name, $names->level1name);
        $this->assertEquals($level2name, $names->level2name);
        $this->assertEquals(0, $names->level2hide);
        $this->assertEmpty($names->level3name);

        $names = \mod_openstudio\local\api\levels::get_name(3, $level3id);
        $this->assertEquals($level1name, $names->level1name);
        $this->assertEquals($level2name, $names->level2name);
        $this->assertEquals(0, $names->level2hide);
        $this->assertEquals($level3name, $names->level3name);

        $this->assertFalse(\mod_openstudio\local\api\levels::get_name(4, $level3id));
        $this->assertFalse(\mod_openstudio\local\api\levels::get_name(1, $level3id + 1));
    }

    public function test_defined_for_studio() {
        // Calling defined_for_studio() should only return true when there is a full set of 3 levels.
        $this->assertFalse(\mod_openstudio\local\api\levels::defined_for_studio($this->studiogeneric->id));
        $level1id = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $this->assertFalse(\mod_openstudio\local\api\levels::defined_for_studio($this->studiogeneric->id));
        $level2id = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $this->assertFalse(\mod_openstudio\local\api\levels::defined_for_studio($this->studiogeneric->id));
        \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));
        $this->assertTrue(\mod_openstudio\local\api\levels::defined_for_studio($this->studiogeneric->id));

        // A non-existant studio should return false.
        $this->assertFalse(\mod_openstudio\local\api\levels::defined_for_studio($this->studiogeneric->id + 1));

    }

    public function test_get_first_l1() {
        // No levels - should return false.
        $this->assertFalse(\mod_openstudio\local\api\levels::get_first_l1_in_studio($this->studiogeneric->id));

        $level1id1 = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'foo1', 'sortorder' => 1));
        $level2id1 = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id1, 'name' => 'bar1', 'sortorder' => 1));
        \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id1, 'name' => 'baz1', 'sortorder' => 1));

        // One set of levels - should return the level1 ID.
        $this->assertEquals($level1id1, \mod_openstudio\local\api\levels::get_first_l1_in_studio($this->studiogeneric->id));

        $level1id2 = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'foo2', 'sortorder' => 2));
        $level2id2 = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id2, 'name' => 'bar2', 'sortorder' => 2));
        \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id2, 'name' => 'baz2', 'sortorder' => 2));

        // Two sets of levels - should still return the first level1 ID.
        $this->assertEquals($level1id1, \mod_openstudio\local\api\levels::get_first_l1_in_studio($this->studiogeneric->id));

        // A non-existant studio should return false.
        $this->assertFalse(\mod_openstudio\local\api\levels::get_first_l1_in_studio($this->studiogeneric->id + 1));
    }

    public function test_l1_has_l3() {

        // Should return 0 until there is a full set of levels.
        $level1id = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'foo1', 'sortorder' => 1));
        $this->assertEquals(0, \mod_openstudio\local\api\levels::l1_has_l3s($level1id));

        $level2id = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'bar1', 'sortorder' => 1));
        $this->assertEquals(0, \mod_openstudio\local\api\levels::l1_has_l3s($level1id));

        \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id, 'name' => 'baz1', 'sortorder' => 1));
        $this->assertEquals(1, \mod_openstudio\local\api\levels::l1_has_l3s($level1id));

        $count = rand(10, 20);
        // Generate extra level3s until there are $count in total.
        for ($i = 1; $i < $count; $i++) {
            \mod_openstudio\local\api\levels::create(
                    3, array('parentid' => $level2id, 'name' => random_string(), 'sortorder' => $i + 1));
        }
        $this->assertEquals($count, \mod_openstudio\local\api\levels::l1_has_l3s($level1id));

        // Should return 0 for a non-existant level1.
        $this->assertEquals(0, \mod_openstudio\local\api\levels::l1_has_l3s($level1id + 1));
    }

    public function test_l1_count_l3s() {
        // Should return an empty array until there is a full set of levels.
        $level1id1 = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => random_string(), 'sortorder' => 1));
        $this->assertEmpty(\mod_openstudio\local\api\levels::l1s_count_l3s($this->studiogeneric->id));

        $level2id1 = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id1, 'name' => random_string(), 'sortorder' => 1));
        $this->assertEmpty(\mod_openstudio\local\api\levels::l1s_count_l3s($this->studiogeneric->id));

        // Should return the count of level3s keyed against the level1 ID.
        \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id1, 'name' => random_string(), 'sortorder' => 1));
        $levelcounts = \mod_openstudio\local\api\levels::l1s_count_l3s($this->studiogeneric->id);
        $this->assertCount(1, $levelcounts);
        $this->assertEquals(1, $levelcounts[$level1id1]);

        // Generate more levels and check we get the correct counts.
        $count1 = rand(10, 20);
        // Generate extra level3s until there are $count in total.
        for ($i = 1; $i < $count1; $i++) {
            \mod_openstudio\local\api\levels::create(
                    3, array('parentid' => $level2id1, 'name' => random_string(), 'sortorder' => $i + 1));
        }

        $level1id2 = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => random_string(), 'sortorder' => 2));
        $level2id2 = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id2, 'name' => random_string(), 'sortorder' => 2));
        $count2 = rand(10, 20);
        for ($i = 1; $i <= $count2; $i++) {
            \mod_openstudio\local\api\levels::create(
                    3, array('parentid' => $level2id2, 'name' => random_string(), 'sortorder' => $i));
        }

        $levelcounts = \mod_openstudio\local\api\levels::l1s_count_l3s($this->studiogeneric->id);
        $this->assertCount(2, $levelcounts);
        $this->assertEquals($count1, $levelcounts[$level1id1]);
        $this->assertEquals($count2, $levelcounts[$level1id2]);

        // Should return empty array for non-existant studio.
        $this->assertEmpty(\mod_openstudio\local\api\levels::l1s_count_l3s($this->studiogeneric->id + 1));
    }

    public function test_cleanup_sortorder() {
        global $DB;
        // Generate levels with weird sort orders.
        $level1count = rand(1, 5);
        $level2count = rand(1, 5);
        $level3count = rand(1, 5);

        $level1s = array();

        $level1sort = 0;
        for ($i = 1; $i <= $level1count; $i++) {
            $level1sort += rand(1, 5);
            $level1id = \mod_openstudio\local\api\levels::create(
                    1, array('openstudioid' => $this->studiogeneric->id, 'name' => random_string(), 'sortorder' => $level1sort));
            $level1 = (object) ['id' => $level1id, 'level2s' => []];

            $level2sort = 0;
            for ($j = 1; $j <= $level2count; $j++) {
                $level2sort += rand(1, 5);
                $level2id = \mod_openstudio\local\api\levels::create(
                        2, array('parentid' => $level1id, 'name' => random_string(), 'sortorder' => $level2sort));
                $level2 = (object) ['id' => $level2id, 'level3s' => []];

                $level3sort = 0;
                for ($k = 1; $k <= $level3count; $k++) {
                    $level3sort += rand(1, 5);
                    $level3id = \mod_openstudio\local\api\levels::create(
                            3, array('parentid' => $level2id, 'name' => random_string(), 'sortorder' => $level3sort));
                    $level2->level3s[] = $level3id;
                }
                $level1->level2s[] = $level2;
            }
            $level1s[] = $level1;
        }

        // Work through each generated level, cleaning up its children, and asserting they're now correctly sorted.
        \mod_openstudio\local\api\levels::cleanup_sortorder(1, $this->studiogeneric->id);
        $level1records = $DB->get_records('openstudio_level1',
                array('openstudioid' => $this->studiogeneric->id), 'sortorder asc');
        $sortcount = 1;
        foreach ($level1records as $level1record) {
            $this->assertEquals($sortcount, $level1record->sortorder);
            $sortcount++;
        }

        foreach ($level1s as $level1) {
            \mod_openstudio\local\api\levels::cleanup_sortorder(2, $level1->id);
            $level2records = $DB->get_records('openstudio_level2', array('level1id' => $level1->id), 'sortorder asc');
            $sortcount = 1;
            foreach ($level2records as $level2record) {
                $this->assertEquals($sortcount, $level2record->sortorder);
                $sortcount++;
            }

            foreach ($level1->level2s as $level2) {
                \mod_openstudio\local\api\levels::cleanup_sortorder(3, $level2->id);
                $level3records = $DB->get_records('openstudio_level3', array('level2id' => $level2->id), 'sortorder asc');
                $sortcount = 1;
                foreach ($level3records as $level3record) {
                    $this->assertEquals($sortcount, $level3record->sortorder);
                    $sortcount++;
                }
            }
        }

    }

    public function test_get_latest_sortorder() {
        // Generate random numbers of levels.
        $level1count = rand(1, 5);
        $level2count = rand(1, 5);
        $level3count = rand(1, 5);

        $level1s = array();

        $level1sort = 0;
        for ($i = 1; $i <= $level1count; $i++) {
            $level1sort++;
            $level1id = \mod_openstudio\local\api\levels::create(
                    1, array('openstudioid' => $this->studiogeneric->id, 'name' => random_string(), 'sortorder' => $level1sort));
            $level1 = (object) ['id' => $level1id, 'level2s' => []];

            $level2sort = 0;
            for ($j = 1; $j <= $level2count; $j++) {
                $level2sort++;
                $level2id = \mod_openstudio\local\api\levels::create(
                        2, array('parentid' => $level1id, 'name' => random_string(), 'sortorder' => $level2sort));
                $level2 = (object) ['id' => $level2id, 'level3s' => []];

                $level3sort = 0;
                for ($k = 1; $k <= $level3count; $k++) {
                    $level3sort++;
                    $level3id = \mod_openstudio\local\api\levels::create(
                            3, array('parentid' => $level2id, 'name' => random_string(), 'sortorder' => $level3sort));
                    $level2->level3s[] = $level3id;
                }
                $level1->level2s[] = $level2;
            }
            $level1s[] = $level1;
        }

        // Work through each generated level, checking the latest sortorder is as expected.
        $this->assertEquals($level1count + 1, \mod_openstudio\local\api\levels::get_latest_sortorder(1, $this->studiogeneric->id));

        foreach ($level1s as $level1) {
            $this->assertEquals($level2count + 1, \mod_openstudio\local\api\levels::get_latest_sortorder(2, $level1->id));

            foreach ($level1->level2s as $level2) {
                $this->assertEquals($level3count + 1, \mod_openstudio\local\api\levels::get_latest_sortorder(3, $level2->id));
            }
        }
    }

    public function test_count_contents_in_level() {
        // We create up to 5 pieces of content in each level, so we need 5 users.
        $users = [
            1 => $this->getDataGenerator()->create_user(array('email' => 'student1@ouunittest.com', 'username' => 'student1')),
            2 => $this->getDataGenerator()->create_user(array('email' => 'student2@ouunittest.com', 'username' => 'student2')),
            3 => $this->getDataGenerator()->create_user(array('email' => 'student3@ouunittest.com', 'username' => 'student3')),
            4 => $this->getDataGenerator()->create_user(array('email' => 'student4@ouunittest.com', 'username' => 'student4')),
            5 => $this->getDataGenerator()->create_user(array('email' => 'student5@ouunittest.com', 'username' => 'student5'))
        ];
        $level1id = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $level2id1 = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level2id2 = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level3ids[1] = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id1, 'name' => 'slot', 'sortorder' => 1));
        $level3ids[2] = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id1, 'name' => 'slot', 'sortorder' => 1));
        $level3ids[3] = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id2, 'name' => 'slot', 'sortorder' => 1));

        // Generate a random number of content pieces in each level3.
        $contentscounts = array(1 => rand(1, 5), 2 => rand(1, 5), 3 => rand(1, 5));

        foreach ($contentscounts as $key => $contentscount) {
            for ($i = 1; $i <= $contentscount; $i++) {
                $this->generator->create_contents([
                        'openstudio' => 'OS1',
                        'name' => random_string(),
                        'description' => random_string(),
                        'levelcontainer' => 3,
                        'levelid' => $level3ids[$key],
                        'userid' => $users[$i]->id]);
            }
        }

        $contents = \mod_openstudio\local\api\content::get_all_records($this->studiogeneric->id);
        $this->assertEquals($contentscounts[1],
                \mod_openstudio\local\api\levels::count_contents_in_level(3, $level3ids[1], $contents));
        $contents = \mod_openstudio\local\api\content::get_all_records($this->studiogeneric->id);
        $this->assertEquals($contentscounts[2],
                \mod_openstudio\local\api\levels::count_contents_in_level(3, $level3ids[2], $contents));
        $contents = \mod_openstudio\local\api\content::get_all_records($this->studiogeneric->id);
        $this->assertEquals($contentscounts[3],
                \mod_openstudio\local\api\levels::count_contents_in_level(3, $level3ids[3], $contents));
        $contents = \mod_openstudio\local\api\content::get_all_records($this->studiogeneric->id);
        $this->assertEquals($contentscounts[1] + $contentscounts[2],
                \mod_openstudio\local\api\levels::count_contents_in_level(2, $level2id1, $contents));
        $contents = \mod_openstudio\local\api\content::get_all_records($this->studiogeneric->id);
        $this->assertEquals($contentscounts[3],
                \mod_openstudio\local\api\levels::count_contents_in_level(2, $level2id2, $contents));
        $contents = \mod_openstudio\local\api\content::get_all_records($this->studiogeneric->id);
        $this->assertEquals($contentscounts[1] + $contentscounts[2] + $contentscounts[3],
                \mod_openstudio\local\api\levels::count_contents_in_level(1, $level1id, $contents));
    }

    public function test_fixup_legacy_xml() {
        global $CFG;
        $legacyxml = file_get_contents($CFG->dirroot . '/mod/openstudio/tests/fixtures/levels-legacy.xml');
        $xml = file_get_contents($CFG->dirroot . '/mod/openstudio/tests/fixtures/levels.xml');
        $this->assertEquals($xml, \mod_openstudio\local\api\levels::fixup_legacy_xml($legacyxml));
    }

    public function test_import_xml() {
        global $DB, $CFG;
        $xml = file_get_contents($CFG->dirroot . '/mod/openstudio/tests/fixtures/levels.xml');

        // Create an existing set of levels to confirm they are soft-deleted when we run the import.
        $level1id = \mod_openstudio\local\api\levels::create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $level2id = \mod_openstudio\local\api\levels::create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level3id = \mod_openstudio\local\api\levels::create(
                3, array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));

        \mod_openstudio\local\api\levels::import_xml($this->studiogeneric->id, $xml);
        // Exising records should now be soft deleted.
        $this->assertEquals(\mod_openstudio\local\api\levels::SOFT_DELETED,
                $DB->get_field('openstudio_level1', 'status', array('id' => $level1id)));
        $this->assertEquals(\mod_openstudio\local\api\levels::SOFT_DELETED,
                $DB->get_field('openstudio_level2', 'status', array('id' => $level2id)));
        $this->assertEquals(\mod_openstudio\local\api\levels::SOFT_DELETED,
                $DB->get_field('openstudio_level3', 'status', array('id' => $level3id)));

        // There should be a new set of records matching the XML structure.
        $block1 = $DB->get_record('openstudio_level1',
                array('openstudioid' => $this->studiogeneric->id, 'name' => 'block1'), '*', MUST_EXIST);
        $activity1 = $DB->get_record('openstudio_level2',
                array('level1id' => $block1->id, 'name' => 'activity1', 'hidelevel' => false), '*', MUST_EXIST);
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('level2id' => $activity1->id, 'name' => 'slot1', 'required' => false,
                        'contenttype' => \mod_openstudio\local\api\content::TYPE_NONE)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('level2id' => $activity1->id, 'name' => 'slot2', 'required' => true,
                        'contenttype' => \mod_openstudio\local\api\content::TYPE_FOLDER)));
        $this->assertTrue($DB->record_exists('openstudio_level3',
                array('level2id' => $activity1->id, 'name' => 'slot3', 'required' => false,
                        'contenttype' => \mod_openstudio\local\api\content::TYPE_FOLDER)));

        $block2 = $DB->get_record('openstudio_level1',
                array('openstudioid' => $this->studiogeneric->id, 'name' => 'block2'), '*', MUST_EXIST);
        $activity2 = $DB->get_record('openstudio_level2',
                array('level1id' => $block2->id, 'name' => 'activity2', 'hidelevel' => true), '*', MUST_EXIST);
        $slot4 = $DB->get_record('openstudio_level3',
                array('level2id' => $activity2->id, 'name' => 'slot4', 'required' => true,
                        'contenttype' => \mod_openstudio\local\api\content::TYPE_FOLDER),
                '*', MUST_EXIST);
        $template = $DB->get_record('openstudio_folder_templates',
                array('levelcontainer' => 3, 'levelid' => $slot4->id, 'additionalcontents' => 2), '*', MUST_EXIST);
        $this->assertEquals('Lorem ipsum dolor', $template->guidance);
        $tempcontent1  = $DB->get_record('openstudio_content_templates',
                array('foldertemplateid' => $template->id, 'permissions' => 0, 'contentorder' => 1), '*', MUST_EXIST);
        $this->assertEmpty($tempcontent1->guidance);
        $tempcontent2 = $DB->get_record('openstudio_content_templates',
                array('foldertemplateid' => $template->id,
                        'permissions' => \mod_openstudio\local\api\folder::PERMISSION_REORDER, 'contentorder' => 2),
                '*', MUST_EXIST);
        $this->assertEquals('sit amet', $tempcontent2->guidance);
    }

    public function test_get_all_activities_failed() {
        $blocks = \mod_openstudio\local\api\levels::get_all_activities(-1);
        $this->assertFalse($blocks);
    }

    /**
     * @depends test_get_all_activities_failed
     * @return void
     */
    public function test_get_all_activities() {
        $this->resetAfterTest(true);

        $level1id = \mod_openstudio\local\api\levels::create(1, [
                'openstudioid' => $this->studiogeneric->id,
                'name' => 'block',
                'sortorder' => 1,
        ]);
        $this->assertGreaterThan(0, $level1id);

        // Activity 1.
        $level2id1 = \mod_openstudio\local\api\levels::create(2, [
                'parentid' => $level1id,
                'name' => 'activity',
                'sortorder' => 1,
        ]);
        $this->assertGreaterThan(0, $level2id1);

        // Activity 2.
        $level2id2 = \mod_openstudio\local\api\levels::create(2, [
                'parentid' => $level1id,
                'name' => 'activity',
                'sortorder' => 1,
        ]);
        $this->assertGreaterThan(0, $level2id2);

        $blocks = \mod_openstudio\local\api\levels::get_all_activities($this->studiogeneric->id);
        $this->assertCount(1, $blocks);

        $block1 = $blocks[0];
        $this->assertEquals($level1id, $block1->id);
        $this->assertObjectHasAttribute('activities', $block1);
        $this->assertCount(2, $block1->activities);
    }
}
