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
 * Unit tests for embedcode API
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_openstudio;

defined('MOODLE_INTERNAL') || die();

class embedcode_testcase extends \advanced_testcase {

    /**
     * Test the parsing function.
     *
     * The actual response comes from the filter_ouembed API.  We're using a stub here, so just need to check that the returned
     * object contains the data that we'd expect.
     */
    public function test_parse() {
        $testurl = 'http://youtube.com/';
        $embedapi = new \mod_openstudio\local\tests\mock_filter_ouembed_api();
        $embedresponse = \mod_openstudio\local\api\embedcode::parse($embedapi, $testurl);
        $this->assertEquals(\mod_openstudio\local\api\content::TYPE_URL, $embedresponse->type);
        $this->assertEquals($testurl, $embedresponse->url);
        $this->assertContains($testurl, $embedresponse->html);
    }
}
