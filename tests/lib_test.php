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
 * Unit tests for (some of) mod/openstudio/lib.php.
 *
 * @package    mod_openstudio
 * @category   phpunit
 * @copyright  2022 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/openstudio/lib.php');

/**
 * Unit tests for (some of) mod/openstudio/lib.php.
 *
 * @package    mod_openstudio
 * @category   phpunit
 * @copyright  2022 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {

    public static function openstudio_extract_blocks_activities_provider(): array {
        return [
                [
                        [],
                        [[], []],
                ],
                [
                        ['1_1', '1_2', '1_3', '2_4', '2_5'],
                        [
                                [1, 2],
                                [1, 2, 3, 4, 5],
                        ],
                ],
                [
                        ['1_1', '1_2', '1_3', '1_1', '1_2', '1_3', '2_4', '2_5', '2_4', '2_5'],
                        [
                                [1, 2],
                                [1, 2, 3, 4, 5],
                        ],
                ],
                [
                        ['1', '2', '3'],
                        [[], []],
                ]
        ];
    }

    /**
     * @dataProvider openstudio_extract_blocks_activities_provider
     * @return void
     */
    public function test_openstudio_extract_blocks_activities($before, $after) {
        $this->resetAfterTest(true);
        $result = openstudio_extract_blocks_activities($before);
        $this->assertSame($after, $result);
    }
}
