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
 * Unit tests for search_result_followed event.
 *
 * @package   mod_openstudio
 * @copyright 2026 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\event;

use advanced_testcase;
use coding_exception;
use context_system;

/**
 * Tests for search_result_followed event.
 *
 * @covers \mod_openstudio\event\search_result_followed
 * @package   mod_openstudio
 * @copyright 2026 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_result_followed_test extends advanced_testcase {

    /**
     * Helper to create a valid event.
     *
     * @param array $other
     * @return search_result_followed
     */
    private function create_event(array $other): search_result_followed {
        return search_result_followed::create([
            'context' => context_system::instance(),
            'userid' => 1,
            'other' => $other,
        ]);
    }

    /**
     * Test description includes token, position and URL.
     */
    public function test_description_contains_key_fields(): void {
        $this->resetAfterTest();

        $event = $this->create_event([
            'searchtoken' => 'abc123',
            'pos' => 3,
            'url' => 'https://example.com/mod/openstudio/content.php?id=1&sid=42',
        ]);

        $description = $event->get_description();

        $this->assertStringContainsString('abc123', $description);
        $this->assertStringContainsString('result no. 3', $description);
        $this->assertStringContainsString('content.php', $description);
    }

    /**
     * Test event name is stable.
     */
    public function test_event_name(): void {
        $this->resetAfterTest();

        $event = $this->create_event([
            'searchtoken' => 'tok',
            'pos' => 1,
            'url' => 'https://example.com/mod/openstudio/content.php?id=1&sid=1',
        ]);

        $this->assertEquals('Search result followed', $event->get_name());
    }

    /**
     * Test that missing required fields throw a coding_exception.
     *
     * @dataProvider missing_field_provider
     */
    public function test_missing_required_field_throws(string $missingfield): void {
        $this->resetAfterTest();

        $valid = [
            'searchtoken' => 'tok',
            'pos' => 1,
            'url' => 'https://example.com/mod/openstudio/content.php?id=1&sid=1',
        ];
        unset($valid[$missingfield]);

        $this->expectException(coding_exception::class);
        $this->create_event($valid)->trigger();
    }

    /**
     * @return array
     */
    public static function missing_field_provider(): array {
        return [
            'missing searchtoken' => ['searchtoken'],
            'missing pos' => ['pos'],
            'missing url' => ['url'],
        ];
    }
}
