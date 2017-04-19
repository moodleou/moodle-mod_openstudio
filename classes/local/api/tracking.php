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
 * API functions for activity tracking.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Tracking API functions
 *
 * This code has been only minimally refactored from openstudio V1, so may still use terms like "slot".
 */
class tracking {

    const CREATE_CONTENT = 1;
    const READ_CONTENT = 2;
    const READ_CONTENT_VERSION = 3;
    const DELETE_CONTENT = 4;
    const DELETE_CONTENT_VERSION = 5;
    const UPDATE_CONTENT = 6;
    const UPDATE_CONTENT_VISIBILITY_PRIVATE = 7;
    const UPDATE_CONTENT_VISIBILITY_GROUP = 8;
    const UPDATE_CONTENT_VISIBILITY_MODULE = 9;
    const ARCHIVE_CONTENT = 10;
    const MODIFY_FOLDER = 11;
    const COPY_CONTENT = 12;
    const ADD_CONTENT_TO_FOLDER = 13;
    const LINK_CONTENT_TO_FOLDER = 14;
    const COPY_CONTENT_TO_FOLDER = 15;
    const UPDATE_CONTENT_VISIBILITY_TUTOR = 16;

    /**
     * Returns all actions belonging to a slot.
     *
     * @param int $slotid
     * @return mixed Returns false if no results, otherwise returns recordset results
     */
    public static function flags_slot_actions($slotid) {
        global $DB;

        $rs = $DB->get_recordset('openstudio_tracking', array('contentid' => $slotid), '', '*');
        if ($rs->valid()) {
            return $rs;
        }

        $rs->close();

        return false;
    }

    /**
     * Toggles an action on a slot on (adds to DB) or off (removes from DB).
     *
     * Note: the $actionid flag values are:
     *     tracking::CREATE_CONTENT
     *     tracking::READ_CONTENT
     *     tracking::READ_CONTENT_VERSION
     *     tracking::DELETE_CONTENT
     *     tracking::DELETE_CONTENT_VERSION
     *     tracking::UPDATE_CONTENT
     *     tracking::UPDATE_CONTENT_VISIBILITY_PRIVATE
     *     tracking::UPDATE_CONTENT_VISIBILITY_GROUP
     *     tracking::UPDATE_CONTENT_VISIBILITY_MODULE
     *     tracking::ARCHIVE_CONTENT
     *     tracking::MODIFY_SET
     *
     * @param int $slotid
     * @param int $actionid
     * @param int $userid
     * @param int $setid
     * @param bool $returnstudioactionid
     * @return mixed returns true or false or returns an int id from stuio_flags as inserting data if
     *                 $returnstudioactionid param is set to true
     */
    public static function log_action($slotid, $actionid, $userid, $setid = null, $returnstudioactionid = false) {
        global $DB;

        try {
            $isduplicate = false;
            if (in_array($actionid, array(
                    self::DELETE_CONTENT,
                    self::CREATE_CONTENT)) ) {
                // Let's just check that our tracking item is NOT a duplicate.
                $isduplicate = self::is_duplicate($slotid, $actionid);
            }

            if (!$isduplicate) {
                // Prepare data to insert.
                $insertdata = new \stdClass();
                $insertdata->contentid = $slotid;
                $insertdata->actionid = $actionid;
                $insertdata->userid = $userid;
                if ($setid) {
                    $insertdata->folderid = $setid;
                }
                $insertdata->timemodified = time();
                if (self::write_again($slotid, $actionid, $userid)) {
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
        } catch (\Exception $e) {
            return false;
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
    public static function is_duplicate($slotid, $actionid, $userid = '') {
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
    public static function write_again($slotid, $actionid, $userid) {
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
}
