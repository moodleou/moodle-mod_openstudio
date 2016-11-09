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
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
use mod_openstudio\local\api\tracking;

defined('MOODLE_INTERNAL') || die();

/**
 * Returns all actions belonging to a slot.
 *
 * @param int $slotid
 * @return mixed Returns false if no results, otherwise returns recordset results
 */
function studio_api_tracking_flags_slot_actions($slotid) {
    global $DB;

    $rs = $DB->get_recordset('studio_tracking', array('contentid' => $slotid), '', '*');
    if ($rs->valid()) {
        return $rs;
    }

    $rs->close();

    return false;
}

/**
 * Toggles an action on a slot on (adds to DB) or off (removes from DB).
 *
 * Note: the $actionid flag values are found in lib.php as:
 *     mod_openstudio\local\api\tracking::CREATE_CONTENT
 *     mod_openstudio\local\api\tracking::READ_CONTENT
 *     mod_openstudio\local\api\tracking::READ_CONTENT_VERSION
 *     mod_openstudio\local\api\tracking::DELETE_CONTENT
 *     mod_openstudio\local\api\tracking::DELETE_CONTENT_VERSION
 *     mod_openstudio\local\api\tracking::UPDATE_CONTENT
 *     mod_openstudio\local\api\tracking::UPDATE_CONTENT_VISIBILITY_PRIVATE
 *     mod_openstudio\local\api\tracking::UPDATE_CONTENT_VISIBILITY_GROUP
 *     mod_openstudio\local\api\tracking::UPDATE_CONTENT_VISIBILITY_MODULE
 *     mod_openstudio\local\api\tracking::ARCHIVE_CONTENT
 *     mod_openstudio\local\api\tracking::MODIFY_SET
 *
 * @param int $slotid
 * @param int $actionid
 * @param int $userid
 * @param int $setid
 * @param bool $returnstudioactionid
 * @return mixed returns true or false or returns an int id from stuio_flags as inserting data if
 *                 $returnstudioactionid param is set to true
 */
function studio_api_tracking_log_action($slotid, $actionid, $userid, $setid = null, $returnstudioactionid = false) {
    global $DB;

    try {
        $isduplicate = false;
        if (in_array($actionid, array(
                tracking::DELETE_CONTENT,
                tracking::CREATE_CONTENT)) ) {
            // Let's just check that our tracking item is NOT a duplicate.
            $isduplicate = studio_api_tracking_is_duplicate($slotid, $actionid);
        }

        if (!$isduplicate) {
            // Prepare data to insert.
            $insertdata = new stdClass();
            $insertdata->contentid = $slotid;
            $insertdata->actionid = $actionid;
            $insertdata->userid = $userid;
            if ($setid) {
                $insertdata->folderid = $setid;
            }
            $insertdata->timemodified = time();
            if (studio_api_tracking_write_again($slotid, $actionid, $userid)) {
                if ($returnstudioactionid) {
                    return $DB->insert_record('openstudio_tracking', $insertdata);
                } else {
                    $DB->insert_record('openstudio_tracking', $insertdata, false);
                    return true;
                }
            } else {
                if ($returnstudioactionid) {
                    $params['contentid'] = $slotid;
                    $params['actionid'] = $actionid;
                    $result = $DB->get_record('openstudio_tracking', $params);
                    return $result->id;
                } else {
                    return true;
                }
            }
        }
    } catch (Exception $e) {
        // Defaults to returning false.
    }

    // This should ONLY happen if an entry for deleting a slot already
    // exists in the tracking table.
    return false;
}

/**
 * This only applies to certain action IDs where we can have only SINGLE entries.
 * Create and Delete for now.
 *
 * Note that the userid here is irrelevant, because irrespective of the user, a
 * create or delete action can only happen on a slot ONCE. It is required here
 * incase it is needed.
 *
 * @param int $slotid
 * @param int $actionid
 * @param int $userid
 * @return bool Return true if duplicate.
 */
function studio_api_tracking_is_duplicate($slotid, $actionid, $userid = '') {
    global $DB;

    $params = array();
    if ($userid != '') {
        $params['userid'] = $userid;
    }
    $params['contentid'] = $slotid;
    $params['actionid'] = $actionid;

    $actionexists = $DB->get_record('openstudio_tracking', $params);

    // We want $actionexists to be false as that means NO record was found.
    if ($actionexists == false) {
        return false;
    }

    return true;
}

/**
 * Check if a tracking record already exists within the last period (1 minute).
 * This is used to preevnt frequent common tracking records from being generated.
 *
 * @param int $slotid
 * @param int $actionid
 * @param int $userid
 * @return bool Return true if tracking record exists.
 */
function studio_api_tracking_write_again($slotid, $actionid, $userid) {
    global $DB;

    $sql = <<<EOF
  SELECT *
    FROM {openstudio_tracking}
   WHERE userid = ?
     AND contentid = ?
     AND actionid = ?
     AND timemodified > ?
ORDER BY timemodified
EOF;

    $lastacceptableupdatetime = time() - 60;
    $params[] = $userid;
    $params[] = $slotid;
    $params[] = $actionid;
    $params[] = $lastacceptableupdatetime;

    // Get Just 1 record.
    $x = $DB->get_records_sql($sql, $params, 0, 1);

    if (($x == false) && (count($x) < 1)) {
        return true; // Yes, write again.
    }

    return false; // Don't write.
}
