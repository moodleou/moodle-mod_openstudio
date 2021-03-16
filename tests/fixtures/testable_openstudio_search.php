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
 * Mock search class to test global search functionality
 *
 * @package    mod_openstudio
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/search/tests/fixtures/mock_search_engine.php');

class testable_openstudio_search extends \core_search\manager {

    public static $fakeresult;

    /**
     * Attaches the mock engine to search.
     *
     * Auto enables global search.
     *
     * @param  \core_search\engine|bool $searchengine
     * @return testable_openstudio_search
     */
    public static function instance($searchengine = false, bool $query = false) {

        // One per request, this should be purged during testing.
        if (self::$instance !== null) {
            return self::$instance;
        }

        set_config('enableglobalsearch', true);

        // Default to the mock one.
        if ($searchengine === false) {
            $searchengine = new \mock_search\engine();
        }

        self::$instance = new testable_openstudio_search($searchengine);

        return self::$instance;
    }

    /**
     * Returns the mock result stored in mod_openstudio/mocksearch config
     */
    public function paged_search(\stdClass $formdata, $pagenum) {
        return self::$fakeresult;
    }
}
