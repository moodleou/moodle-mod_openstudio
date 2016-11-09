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
defined('MOODLE_INTERNAL') || die();

use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\tracking;

/**
 * Returns all flags belonging to a slot.
 *
 * @param int $slotid Slot id to get flag data for.
 * @param int $flagid Get data for a specific flag.
 * @return object|bool Return false if no results, otherwise returns record/recordset results.
 */
function studio_api_flags_get_slot_flags($slotid, $flagid = null, $userid = null) {
    global $DB;

    $params = array('contentid' => $slotid);

    if (!empty($flagid)) {
        $params = array('contentid' => $slotid, 'flagid' => $flagid);
    }
    if (!empty($userid)) {
        $params['userid'] = $userid;
    }
    if (count($params) > 1) {
        return $DB->get_record('openstudio_flags', $params);
    }

    $rs = $DB->get_recordset('openstudio_flags', $params);
    if ($rs->valid()) {
        return $rs;
    }

    return false;
}

/**
 * Toggles a flag on a slot on (adds to DB) or off (removes from DB).
 *
 * NOTE: the $flagid values are found in lib.php as:
 *     participation::ALERT
 *     participation::FAVOURITE
 *     participation::NEEDHELP
 *     participation::MADEMELAUGH
 *     participation::INSPIREDME
 *     participation::READ_SLOT
 *
 * @param int $slotid
 * @param int $flagid
 * @param string $toggle String 'on' or 'off'.
 * @param int $userid
 * @param int $setid the set the slot belongs to
 * @param bool $returnstudioflagid true or false
 * @return int|bool Returns true or false or returns an int id from stuio_flags as inserting data
 *                  if $returnstudioflagid param is set to true
 */
function studio_api_flags_toggle($slotid, $flagid, $toggle, $userid, $setid = null, $returnstudioflagid = false) {
    return studio_api_flags_toggle_internal($slotid, 0, null, $flagid, $toggle, $userid, $setid, $returnstudioflagid);
}

/**
 * Toggles a flag associated with a person: on (adds to DB) or off (removes from DB).
 *
 * NOTE: the $flagid values are found in lib.php as:
 *     participation::FOLLOW_USER
 *
 * @param int $personid Person id to flag.
 * @param int $flagid Flag id.
 * @param bool $toggle Turn on or off the flag.
 * @param int $userid User id doing the flagging.
 * @param bool $returnstudioflagid Return flag id if set to true.
 * @return mixed Return result of flag setting.
 */
function studio_api_flags_user_toggle($personid, $flagid, $toggle, $userid, $returnstudioflagid = false) {
    return studio_api_flags_toggle_internal(0, $personid, null, $flagid, $toggle, $userid, null, $returnstudioflagid);
}

/**
 * Toggles a flag associated with a comment: on (adds to DB) or off (removes from DB).
 *
 * This has less parameters than the slot and user equivalents, as only one flag (MADEMELAUGH)
 * applies to comments, and comment flags cannot currently be removed.
 *
 * NOTE: the $flagid values are found in lib.php as:
 *     participation::MADEMELAUGH
 *
 * @param int $slotid The Slot the comment belongs to.
 * @param int $commentid Comment ID to flag.
 * @param int $userid User id doing the flagging.
 * @param bool $returnstudioflagid Return flag id if set to true.
 * @return mixed Return result of flag setting.
 */
function studio_api_flags_comment_toggle($slotid, $commentid, $userid, $returnstudioflagid = false) {
    return studio_api_flags_toggle_internal($slotid, 0, $commentid,
            flags::COMMENT_LIKE, 'on', $userid, null, $returnstudioflagid);
}

/**
 * Toggles a flag associated with a slot or person: on (adds to DB) or off (removes from DB).
 *
 * NOTE: the $flagid values are found in lib.php as:
 *     participation::ALERT
 *     participation::FAVOURITE
 *     participation::NEEDHELP
 *     participation::MADEMELAUGH
 *     participation::INSPIREDME
 *     participation::READ_SLOT
 *     participation::FOLLOW_USER
 *     participation::COMMENT_LIKE
 *
 * @param int $slotid Slot id to flag.
 * @param int $personid Person id to flag.
 * @param int $commentid Comment id to flag.
 * @param int $flagid Flag id.
 * @param bool $toggle Turn on or off the flag.
 * @param int $userid User id doing the flagging.
 * @param int $setid The set the slot belongs to.
 * @param bool $returnstudioflagid Return flag id if set to true.
 * @return mixed Return result of flag setting.
 */
function studio_api_flags_toggle_internal(
        $slotid, $personid, $commentid, $flagid, $toggle, $userid, $setid = null, $returnstudioflagid = false) {

    global $DB;

    try {
        // First test to see if this does exist in the DB.
        if (!$DB->get_record('openstudio_flags',
                array(
                    'contentid' => $slotid,
                    'personid' => $personid,
                    'commentid' => $commentid,
                    'flagid' => $flagid,
                    'userid' => $userid
                ),
                '*', IGNORE_MISSING)) {

            // If we are here, the record does not exist.
            if ($toggle == 'off') {
                // Can't do this because the record does not exist!
                return true;
            } else if ($toggle == 'on') {
                // Prepare data.
                $insertdata = new stdClass();
                $insertdata->contentid = $slotid;
                $insertdata->personid = $personid;
                $insertdata->commentid = $commentid;
                $insertdata->flagid = $flagid;
                $insertdata->userid = $userid;
                if ($setid) {
                    $insertdata->folderid = $setid;
                }
                $insertdata->timemodified = time();

                // Add the record to the DB.
                $insertid = $DB->insert_record('openstudio_flags', $insertdata,
                        (($returnstudioflagid === true) ? true : false));

                // Update slot flagged time field.
                if (in_array($flagid,
                        array(flags::ALERT,
                                flags::FAVOURITE,
                                flags::NEEDHELP,
                                flags::MADEMELAUGH,
                                flags::INSPIREDME,
                                flags::COMMENT))) {
                    $DB->set_field('openstudio_contents', 'timeflagged', time(), array('id' => $slotid));
                }

                if ($returnstudioflagid) {
                    return $insertid;
                }
            } else {
                return false;
            }
        } else {
            // If we are here, the record does exist.
            if ($toggle == 'on') {
                // This should not have happened because the record already exists with an 'on' toggle.

                // Update slot flagged time field if flag is comment.
                if ($flagid == flags::COMMENT) {
                    $DB->set_field('openstudio_contents', 'timeflagged', time(), array('id' => $slotid));
                }

                // Update flag time for read slot.
                if ($flagid == flags::READ_CONTENT) {
                    $DB->set_field('openstudio_flags', 'timemodified', time(),
                            array('contentid' => $slotid, 'userid' => $userid, 'flagid' => flags::READ_CONTENT));
                }
            } else if ($toggle == 'off') {
                // Just wipe the record from the DB. This should return true or false.
                return $DB->delete_records('openstudio_flags',
                        array(
                            'contentid' => $slotid,
                            'personid' => $personid,
                            'commentid' => $commentid,
                            'flagid' => $flagid,
                            'userid' => $userid
                        ));
            } else {
                return false;
            }
        }

        if ($setid) {
            studio_api_tracking_log_action($setid, tracking::MODIFY_FOLDER, $userid);
        }
        return true;
    } catch (Exception $e) {
        // Defaults to returning false.
        return false;
    }

    return false;
}

/**
 * Get total count of flags for a given slot and flag.
 *
 * @param int $slotid Slot flag to count.
 * @param int $flagid Flag to count.
 * @return int Return flag count.
 */
function studio_api_flags_get_slot_flag_total($slotid, $flagid) {
    global $DB;

    $sql = <<<EOF
SELECT count(id)
  FROM {openstudio_flags}
 WHERE contentid = ?
   AND flagid = ?
EOF;

    return (int) $DB->get_field_sql($sql, array($slotid, $flagid));
}

/**
 * Get total count of flags applied to a comment
 *
 * @param int $commentid Comment to count.
 * @param int $flagid Flag to count.
 * @return int Return flag count.
 */
function studio_api_flags_get_comment_flag_total($commentid, $flagid = flags::COMMENT_LIKE) {
    global $DB;

    $sql = <<<EOF
SELECT count(id)
  FROM {openstudio_flags}
 WHERE commentid = ?
   AND flagid = ?
EOF;

    return (int) $DB->get_field_sql($sql, array($commentid, $flagid));
}

/**
 * Get count of flags by user.
 *
 * @param int $userid
 * @return int Return flag count.
 */
function studio_api_flags_get_user_flag_total($studioid, $userid) {
    global $DB;

    $sql = <<<EOF
    SELECT flagid, count(id)
      FROM {openstudio_flags} f
     WHERE userid = ?
AND EXISTS (SELECT 1
              FROM {openstudio_contents} s
             WHERE s.id = f.contentid
               AND s.openstudioid = ?)
GROUP BY flagid

EOF;

    $flagtotals = $DB->get_records_sql($sql, array($userid, $studioid));
    if ($flagtotals == false) {
        $flagtotals = array();
    }

    return $flagtotals;
}

/**
 * Get count of al flags assocaited with slot by user.
 *
 * @param int $slotid
 * @param int $userid
 * @return array Return flag counts.
 */
function studio_api_flags_get_slot_user_flag_total($slotid, $userid) {
    global $DB;

    $sql = <<<EOF
  SELECT flagid, count(id)
    FROM {openstudio_flags}
   WHERE contentid = ?
GROUP BY flagid
EOF;

    $flagtotals = $DB->get_records_sql($sql, array($slotid));
    if ($flagtotals == false) {
        $flagtotals = array();
    }

    $sql = <<<EOF
SELECT flagid
  FROM {openstudio_flags}
 WHERE contentid = ?
   AND userid = ?
EOF;

    $flagstatus = $DB->get_records_sql($sql, array($slotid, $userid));
    if ($flagstatus == false) {
        $flagstatus = array();
    }

    $sql = <<<EOF
  SELECT f.*,
         u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
         u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
    FROM {openstudio_flags} f
    JOIN {user} u ON u.id = f.userid
   WHERE contentid = ?
     AND flagid NOT IN (?, ?, ?)
ORDER BY f.timemodified DESC
EOF;

    $lastactivityresult = $DB->get_records_sql(
            $sql,
            array($slotid,
                    flags::FOLLOW_CONTENT,
                    flags::FOLLOW_USER,
                    flags::READ_CONTENT),
            0, 1);
    $lastactivity = false;
    if ($lastactivityresult) {
        foreach ($lastactivityresult as $activity) {
            $lastactivity = $activity;
            break;
        }
    }

    return array('flagtotals' => $flagtotals, 'flagstatus' => $flagstatus, 'lastactivity' => $lastactivity);
}

function studio_api_flags_comment_flagged_by_user($commentid, $userid, $flag = flags::COMMENT_LIKE) {
    global $DB;
    return $DB->record_exists('openstudio_flags', array('commentid' => $commentid, 'userid' => $userid, 'flagid' => $flag));
}

/**
 * Deletes all flags associated with the supplied slot id.
 *
 * @param int $slotid
 * @return bool Return true if flags are cleared for given slotid.
 */
function studio_api_flags_clear_flags($slotid) {
    global $DB;

    $sql = <<<EOF
DELETE FROM {openstudio_flags}
      WHERE contentid = ?
        AND flagid IN (?, ?, ?, ?, ?, ?)
EOF;

    try {
        $DB->execute($sql, array($slotid,
                flags::ALERT,
                flags::FAVOURITE,
                flags::NEEDHELP,
                flags::MADEMELAUGH,
                flags::INSPIREDME,
                flags::READ_CONTENT));
        return true;
    } catch (Exception $e) {
        // Default to return false on exception.
        return false;
    }

}
