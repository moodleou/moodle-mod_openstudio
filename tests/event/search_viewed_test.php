<?php
// This file is part of Moodle - http://moodle.org/.
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
 * Unit tests for search_viewed event.
 *
 * @package   mod_openstudio
 * @copyright 2025 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\event;

use advanced_testcase;
use context_system;

/**
 * Tests for search_viewed event description and name.
 *
 * @covers \mod_openstudio\event\search_viewed
 * @package   mod_openstudio
 * @copyright 2025 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_viewed_test extends advanced_testcase {

    /**
     * Helper method to create the event safely.
     *
     * @param array $other
     * @return search_viewed
     */
    private function create_event(array $other): search_viewed {
        return search_viewed::create([
            'context' => context_system::instance(),
            'userid' => 1,
            'other' => $other,
        ]);
    }

    /**
     * Test search event includes term and count.
     */
    public function test_description_basic(): void {
        $this->resetAfterTest();

        $event = $this->create_event([
            'searchterm' => 'abc',
            'resultcount' => 10,
            'cmid' => 5,
        ]);

        $description = $event->get_description();

        $this->assertStringContainsString('abc', $description);
        $this->assertStringContainsString('10', $description);
        $this->assertMatchesRegularExpression('/view/i', $description);
    }

    /**
     * Test search event handles missing optional values gracefully.
     */
    public function test_description_missing_values(): void {
        $this->resetAfterTest();

        $event = $this->create_event([
            'cmid' => 5,
        ]);

        $description = $event->get_description();

        // Should not throw, should produce a readable description.
        $this->assertNotEmpty($description);

        // Default result count should be 0.
        $this->assertStringContainsString('0', $description);

        // Default search term should be 'unknown'.
        $this->assertStringContainsString('unknown', $description);
    }

    /**
     * Test search event name should remain stable.
     */
    public function test_event_name(): void {
        $this->resetAfterTest();

        $event = $this->create_event([
            'searchterm'  => 'anything',
            'resultcount' => 1,
            'cmid' => 5,
        ]);

        $this->assertEquals('View search', $event->get_name());
    }
}
