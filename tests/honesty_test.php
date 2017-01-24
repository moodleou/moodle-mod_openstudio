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

class mod_openstudio_honesty_testcase extends advanced_testcase  {

    /**
     * Tests the honesty api.
     */
    public function test_honesty() {
        $this->resetAfterTest(true);
        $mockstudioid = 2;
        $mockuserid = 7;

        $this->assertEquals(false, mod_openstudio\local\api\honesty::get($mockstudioid, $mockuserid));
        $this->assertEquals(true, mod_openstudio\local\api\honesty::set($mockstudioid, $mockuserid, true));
        $this->assertNotEquals(true, mod_openstudio\local\api\honesty::get($mockstudioid, $mockuserid));
        $this->assertEquals(true, mod_openstudio\local\api\honesty::set($mockstudioid, $mockuserid, false));
        $this->assertEquals(false, mod_openstudio\local\api\honesty::get($mockstudioid, $mockuserid));
    }

}
