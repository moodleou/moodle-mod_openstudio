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
 * API Functions for social paritication flags
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

class flags {

    const ALERT = 1;
    const FAVOURITE = 2;
    const NEEDHELP = 3;
    const MADEMELAUGH = 4;
    const INSPIREDME = 5;
    const READ_CONTENT = 6;
    const FOLLOW_CONTENT = 7;
    const FOLLOW_USER = 8;
    const COMMENT = 9;
    const COMMENT_LIKE = 10;
    const TUTOR = 11;

    /**
     * Returns all flags belonging to a slot.
     *
     * @param int $contentid Slot id to get flag data for.
     * @param int $flagid Get data for a specific flag.
     * @param int $userid The ID of the user if we're looking for user-specific flags.
     * @return \moodle_recordset|false Return false if no results, otherwise returns record/recordset results.
     */
    public static function get_content_flags($contentid, $flagid = null, $userid = null) {
        global $DB;

        $params = ['contentid' => $contentid];
        $where = 'commentid IS NULL AND contentid = :contentid';

        if (!empty($flagid)) {
            $params['flagid'] = $flagid;
            $where .= ' AND flagid = :flagid';
        }
        if (!empty($userid)) {
            $params['userid'] = $userid;
            $where .= ' AND userid = :userid';
        }
        $rs = $DB->get_recordset_select('openstudio_flags', $where, $params);
        if ($rs->valid()) {
            return $rs;
        }

        return false;
    }

    /**
     * Returns all flags belonging to a comment.
     *
     * @param int $commentid Slot id to get flag data for.
     * @param int $flagid Get data for a specific flag.
     * @param int $userid The ID of the user if we're looking for user-specific flags.
     * @return \moodle_recordset|false Return false if no results, otherwise returns record/recordset results.
     */
    public static function get_comment_flags($commentid, $flagid = null, $userid = null) {
        global $DB;

        $params = ['commentid' => $commentid];

        if (!empty($flagid)) {
            $params = ['commentid' => $commentid, 'flagid' => $flagid];
        }
        if (!empty($userid)) {
            $params['userid'] = $userid;
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
     * $flagid values are constants from this class.
     *
     * @param int $contentid
     * @param int $flagid
     * @param string $toggle String 'on' or 'off'.
     * @param int $userid
     * @param int $folderid the set the slot belongs to
     * @param bool $returnflagid true or false
     * @return int|bool Returns true or false or returns an int id from openstudio_flags as inserting data
     *                  if $returnflagid param is set to true
     */
    public static function toggle($contentid, $flagid, $toggle, $userid, $folderid = null, $returnflagid = false) {
        return self::toggle_internal($contentid, 0, null, $flagid, $toggle, $userid, $folderid, $returnflagid);
    }

    /**
     * Toggles a flag associated with a person: on (adds to DB) or off (removes from DB).
     *
     * @param int $personid Person id to flag.
     * @param int $flagid Flag id.
     * @param bool $toggle Turn on or off the flag.
     * @param int $userid User id doing the flagging.
     * @param bool $returnflagid Return flag id if set to true.
     * @return mixed Return result of flag setting.
     */
    public static function user_toggle($personid, $flagid, $toggle, $userid, $returnflagid = false) {
        return self::toggle_internal(0, $personid, null, $flagid, $toggle, $userid, null, $returnflagid);
    }

    /**
     * Toggles a flag associated with a comment: on (adds to DB) or off (removes from DB).
     *
     * This has less parameters than the slot and user equivalents, as only one flag (COMMENT_LIKE)
     * applies to comments, and comment flags cannot currently be removed.
     *
     * @param int $contentid The Slot the comment belongs to.
     * @param int $commentid Comment ID to flag.
     * @param int $userid User id doing the flagging.
     * @param string $toggle 'on' or 'off'
     * @param bool $returnflagid Return flag id if set to true.
     * @param int $flagid
     * @return mixed Return result of flag setting.
     */
    public static function comment_toggle($contentid, $commentid, $userid, $toggle = 'on', $returnflagid = false,
            $flagid = self::COMMENT_LIKE) {
        return self::toggle_internal($contentid, 0, $commentid, $flagid, $toggle, $userid, null, $returnflagid);
    }

    /**
     * Toggles a flag associated with a slot or person: on (adds to DB) or off (removes from DB).
     *
     * @param int $contentid Slot id to flag.
     * @param int $personid Person id to flag.
     * @param int $commentid Comment id to flag.
     * @param int $flagid Flag id.
     * @param bool $toggle Turn on or off the flag.
     * @param int $userid User id doing the flagging.
     * @param int $folderid The set the slot belongs to.
     * @param bool $returnflagid Return flag id if set to true.
     * @return mixed Return result of flag setting.
     */
    private static function toggle_internal(
            $contentid, $personid, $commentid, $flagid, $toggle, $userid, $folderid = null, $returnflagid = false) {

        global $DB;

        try {
            // First test to see if this does exist in the DB.
            if (!$DB->get_record('openstudio_flags',
                    [
                            'contentid' => $contentid,
                            'personid' => $personid,
                            'commentid' => $commentid,
                            'flagid' => $flagid,
                            'userid' => $userid
                    ],
                    '*', IGNORE_MISSING)) {

                // If we are here, the record does not exist.
                if ($toggle == 'off') {
                    // Can't do this because the record does not exist!
                    return true;
                } else if ($toggle == 'on') {
                    // Prepare data.
                    $insertdata = new \stdClass();
                    $insertdata->contentid = $contentid;
                    $insertdata->personid = $personid;
                    $insertdata->commentid = $commentid;
                    $insertdata->flagid = $flagid;
                    $insertdata->userid = $userid;
                    if ($folderid) {
                        $insertdata->folderid = $folderid;
                    }
                    $insertdata->timemodified = time();

                    // Add the record to the DB.
                    $insertid = $DB->insert_record('openstudio_flags', $insertdata,
                            (($returnflagid === true) ? true : false));

                    // Update slot flagged time field.
                    if (in_array($flagid,
                            [self::ALERT,
                                    self::FAVOURITE,
                                    self::NEEDHELP,
                                    self::MADEMELAUGH,
                                    self::INSPIREDME,
                                    self::COMMENT])) {
                        $DB->set_field('openstudio_contents', 'timeflagged', time(), ['id' => $contentid]);
                    }

                    if ($returnflagid) {
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
                    if ($flagid == self::COMMENT) {
                        $DB->set_field('openstudio_contents', 'timeflagged', time(), ['id' => $contentid]);
                    }

                    // Update flag time for read slot.
                    if ($flagid == self::READ_CONTENT) {
                        $DB->set_field('openstudio_flags', 'timemodified', time(),
                                ['contentid' => $contentid, 'userid' => $userid, 'flagid' => self::READ_CONTENT]);
                    }
                } else if ($toggle == 'off') {
                    // Just wipe the record from the DB. This should return true or false.
                    return $DB->delete_records('openstudio_flags',
                            [
                                    'contentid' => $contentid,
                                    'personid' => $personid,
                                    'commentid' => $commentid,
                                    'flagid' => $flagid,
                                    'userid' => $userid
                            ]);
                } else {
                    return false;
                }
            }

            if ($folderid) {
                tracking::log_action($folderid, tracking::MODIFY_FOLDER, $userid);
            }
            return true;
        } catch (\Exception $e) {
            // Defaults to returning false.
            return false;
        }
    }

    /**
     * Get total count of flags for a given slot and flag.
     *
     * @param int $contentid Slot flag to count.
     * @param int $flagid Flag to count.
     * @return int Return flag count.
     */
    public static function count_for_content($contentid, $flagid) {
        global $DB;
        return $DB->count_records('openstudio_flags', ['contentid' => $contentid, 'flagid' => $flagid]);
    }

    /**
     * Get total count of flags applied to a comment
     *
     * @param int $commentid Comment to count.
     * @param int $flagid Flag to count.
     * @return int Return flag count.
     */
    public static function count_for_comment($commentid, $flagid = self::COMMENT_LIKE) {
        global $DB;
        return $DB->count_records('openstudio_flags', ['commentid' => $commentid, 'flagid' => $flagid]);
    }

    /**
     * Get count of flags by user within the given studio instance
     *
     * @param int $studioid The ID of the studio instance
     * @param int $userid The ID of the user to find flags for
     * @return array Return flag count.
     */
    public static function count_by_user($studioid, $userid) {
        global $DB;

        $sql = <<<EOF
    SELECT flagid, count(id) AS count
      FROM {openstudio_flags} f
     WHERE userid = ?
AND EXISTS (SELECT 1
              FROM {openstudio_contents} s
             WHERE s.id = f.contentid
               AND s.openstudioid = ?)
GROUP BY flagid

EOF;

        $flagtotals = $DB->get_records_sql($sql, [$userid, $studioid]);
        if ($flagtotals == false) {
            $flagtotals = [];
        }

        return $flagtotals;
    }

    /**
     * Get count of each flag for a content post.
     *
     * @param int $contentid
     * @return array Return flag counts.
     */
    public static function count_by_content($contentid) {
        global $DB;

        $sql = <<<EOF
  SELECT flagid, count(id) AS count
    FROM {openstudio_flags}
   WHERE contentid = ?
GROUP BY flagid
EOF;

        $flagtotals = $DB->get_records_sql($sql, [$contentid]);
        if ($flagtotals == false) {
            $flagtotals = [];
        }

        return $flagtotals;
    }

    /**
     * Return flag IDs active on the specified content post for the specified user.
     *
     * @param $contentid
     * @param $userid
     * @return array
     */
    public static function get_for_content_by_user($contentid, $userid) {
        global $DB;

        $flagstatus = $DB->get_fieldset_select(
                'openstudio_flags', 'flagid', 'contentid = ? AND userid = ?', [$contentid, $userid]);
        if ($flagstatus == false) {
            $flagstatus = [];
        }

        return $flagstatus;
    }

    /**
     * Return flag list of IDs active on the specified list content post for the specified user.
     *
     * @param array $contentids
     * @param int $userid
     * @return array
     */
    public static function get_list_flag_content_by_user($contentids, $userid) {
        if (empty($contentids) || empty($userid)) {
            return false;
        }
        global $DB;
        list($sqlcontent, $sqlparams) = $DB->get_in_or_equal($contentids, SQL_PARAMS_NAMED);
        $sqlflag = $DB->sql_group_concat('sf.flagid', ',');
        $sql = "SELECT sf.contentid, $sqlflag as flagstatus
                  FROM {openstudio_flags} sf
                 WHERE sf.contentid $sqlcontent
                   AND sf.userid = :userid
              GROUP BY sf.contentid
              ORDER BY sf.contentid";
        $sqlparams['userid'] = $userid;
        return $DB->get_records_sql($sql, $sqlparams);
    }

    /**
     * Get the last social flag (not FOLLOW_* or READ_CONTENT) set on a content post, with the flagging user's data.
     *
     * @param $contentid
     * @return bool
     */
    public static function get_most_recent($contentid) {
        global $DB;
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

        $params = [
            $contentid,
            self::FOLLOW_CONTENT,
            self::FOLLOW_USER,
            self::READ_CONTENT
        ];
        $lastactivityresult = $DB->get_records_sql($sql, $params, 0, 1);
        $lastactivity = false;
        if ($lastactivityresult) {
            foreach ($lastactivityresult as $activity) {
                $lastactivity = $activity;
                break;
            }
        }
        return $lastactivity;
    }

    public static function has_user_flagged_comment($commentid, $userid, $flag = self::COMMENT_LIKE) {
        global $DB;
        return $DB->record_exists('openstudio_flags', ['commentid' => $commentid, 'userid' => $userid, 'flagid' => $flag]);
    }

    /**
     * Deletes all flags associated with the supplied contentid id.
     *
     * @param int $contentid
     * @return bool Return true if flags are cleared for given contentid.
     * @throws \dml_exception
     */
    public static function clear($contentid) {
        global $DB;

        $flags = [
            self::ALERT,
            self::FAVOURITE,
            self::NEEDHELP,
            self::MADEMELAUGH,
            self::INSPIREDME,
            self::READ_CONTENT,
            self::FOLLOW_CONTENT
        ];
        list($fsql, $params) = $DB->get_in_or_equal($flags);
        $where = 'flagid ' . $fsql . ' AND contentid = ?';
        $params[] = $contentid;

        return $DB->delete_records_select('openstudio_flags', $where, $params);
    }
}
