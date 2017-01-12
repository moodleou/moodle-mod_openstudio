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
 * @package mod_studio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

/**
 * Get honesty check record for the given studio id and user id.
 *
 * @param int $studioid
 * @param int $userid
 * @return bool Returns honesty record or false if error
 */
function studio_api_tandc_get($studioid, $userid) {
    global $DB;

    $result = $DB->get_record('openstudio_honesty_checks',
            array('openstudioid' => (int) $studioid, 'userid' => (int) $userid), '*');

    return $result;
}

/**
 * Set honesty record for given studio id and user id.
 *
 * @param int $studioid
 * @param int $userid
 * @return bool Returns honesty record or true or false if error
 */
function studio_api_tandc_set($studioid, $userid, $seton = true) {
    global $DB;

    try {
        $result = $data = studio_api_tandc_get($studioid, $userid);
        if ($result == false) {
            $data = (object) array();
            $data->openstudioid = (int) $studioid;
            $data->userid = (int) $userid;
        }
        $data->timemodified = time();

        if ($seton) {
            if ($result == false) {
                return $DB->insert_record('openstudio_honesty_checks', $data);
            } else {
                return $DB->update_record('openstudio_honesty_checks', $data);
            }
        } else {
            return $DB->delete_records('openstudio_honesty_checks',
                    array('openstudioid' => $studioid, 'userid' => $userid));
        }
    } catch (Exception $e) {
        // Defaults to returning false.
        return false;
    }

    return false;
}
