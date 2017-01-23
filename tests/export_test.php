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
 * Export API unit tests
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_openstudioexport_testcase extends \advanced_testcase {
    public function test_encode_ids() {
        // Encode a single ID.
        self::assertEquals('bac', mod_openstudio\local\api\export::encode_ids([102]));
        // Encode multiple IDs.
        self::assertEquals('bacxhij', mod_openstudio\local\api\export::encode_ids([102, 789]));
        // Attempt to encode something else.
        $this->setExpectedException('coding_exception');
        mod_openstudio\local\api\export::encode_ids(['abc', 345]);
    }

    public function test_decode_ids() {
        // Decode a single ID.
        self::assertEquals([102], mod_openstudio\local\api\export::decode_ids('bac'));
        // Decode multiple IDs.
        self::assertEquals([102, 789], mod_openstudio\local\api\export::decode_ids('bacxhij'));
        // Attempt to decode something else.
        $this->setExpectedException('coding_exception');
        mod_openstudio\local\api\export::decode_ids('abcw!');
    }
}
