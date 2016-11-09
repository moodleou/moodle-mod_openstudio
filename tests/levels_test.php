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

class mod_openstudio_levels_testcase extends advanced_testcase {

    private $course;
    private $generator; // Contains mod_openstudio specific data generator functions.
    private $studiogeneric; // Generic studio instance with no levels or slots.

    protected function setUp() {
        $this->resetAfterTest(true);
        $this->teacherroleid = 3;
        $this->studentroleid = 5;

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Assign Students a group.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $this->studiogeneric = $this->generator->create_instance(array('course' => $this->course->id));
    }

    protected function tearDown() {
        $this->course = '';
        $this->generator = '';
        $this->studiogeneric = '';

    }

    /**
     * Tests the level creation function of the levels api.
     */
    public function test_levels_api_create() {
        $this->resetAfterTest(true);

        // Test function parameter checks are working.
        $this->assertFalse(studio_api_levels_create('level', array()));

        // Test level 1 creation.
        $level1id = studio_api_levels_create(1,
                array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $this->assertGreaterThan(0, $level1id, 'Level1 create failed');

        // Test level 2 creation.
        $level2id = studio_api_levels_create(2,
                array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $this->assertGreaterThan(0, $level2id, 'Level2 create failed');

        // Test level 3 creation.
        $level3id = studio_api_levels_create(3,
                array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));
        $this->assertGreaterThan(0, $level3id, 'Level3 create failed');
    }

    /**
     * Tests the studio_api_levels_get_records() and studio_api_levels_get_record() function.
     */
    public function test_levels_api_get() {
        $this->resetAfterTest(true);

        // Let's first create sample data.
        $level1id = studio_api_levels_create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $level2id = studio_api_levels_create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level3id = studio_api_levels_create(
                3, array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));
        $level3ida = studio_api_levels_create(
                3, array('parentid' => $level2id, 'name' => 'slot a', 'sortorder' => 1));
        $level3idb = studio_api_levels_create(
                3, array('parentid' => $level2id, 'name' => 'slot b',  'sortorder' => 2));

        // Soft delete 1 record.
        studio_api_levels_delete(3, $level3idb, true);

        // Let's get it back to see if it exists.
        $this->assertNotEquals(false, studio_api_levels_get_record(1, $level1id));
        $this->assertNotEquals(false, studio_api_levels_get_record(2, $level2id));
        $this->assertNotEquals(false, studio_api_levels_get_record(3, $level3id));

        // Try getting multiple with and without show deleted.
        // We'll get back 2 because we harddeleted $level3idb whether we include showdeleted or not.
        $this->assertEquals(2, count(studio_api_levels_get_records(3, $level2id, false)));
        $this->assertEquals(2, count(studio_api_levels_get_records(3, $level2id, true)));
    }

    /**
     * Tests the studio_api_delete() function.
     */
    public function test_levels_api_delete() {
        $this->resetAfterTest(true);

        // Let's first create sample data.
        $level1id = studio_api_levels_create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $level2id = studio_api_levels_create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level3id = studio_api_levels_create(
                3, array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));

        // Try soft deletes and hard deletes.
        $this->assertNotEquals(false, studio_api_levels_delete(3, $level3id, $this->studiogeneric->id));
        $this->assertNotEquals(false, studio_api_levels_delete(3, $level3id, $this->studiogeneric->id));
        $this->assertNotEquals(false, studio_api_levels_delete(2, $level2id, $this->studiogeneric->id));
        $this->assertNotEquals(false, studio_api_levels_delete(2, $level2id, $this->studiogeneric->id));
        $this->assertNotEquals(false, studio_api_levels_delete(1, $level1id, $this->studiogeneric->id));
        $this->assertNotEquals(false, studio_api_levels_delete(1, $level1id, $this->studiogeneric->id));
    }

    /**
     * Tests the studio_api_update_function().
     */
    public function test_levels_api_update() {
        $this->resetAfterTest(true);

        $level1id = studio_api_levels_create(
                1, array('openstudioid' => $this->studiogeneric->id, 'name' => 'block', 'sortorder' => 1));
        $level2id = studio_api_levels_create(
                2, array('parentid' => $level1id, 'name' => 'activity', 'sortorder' => 1));
        $level3id = studio_api_levels_create(
                3, array('parentid' => $level2id, 'name' => 'slot', 'sortorder' => 1));
        $this->assertNotEquals(
                false, studio_api_levels_update(3, $level3id, array('name' => 'Silly Sally Saw Sixty Six Thistle Stix!')));
        $record = studio_api_levels_get_record(3, $level3id);
        $this->assertEquals('Silly Sally Saw Sixty Six Thistle Stix!', $record->name);
    }

}
