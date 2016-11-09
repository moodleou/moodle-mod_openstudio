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
use mod_openstudio\local\api\content;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper function to check if two users belong to the same group.
 *
 * If groupid is given (groupid < 0), then the check is done against that group.
 * Otherwise (group == STUDIO_VISIBILITY_GROUP), the user is checked against a
 * group colletion.
 *
 * The two checks exists because:
 * a) legacy reason, the original check was done against group collection;
 * b) to support check for all group membership.
 *
 * @param int $groupmode Group mode to check against.
 * @param int $groupid Group id to check against.
 * @param int $groupingid Group grouping id to check against.
 * @param int $userid User to base check against.
 * @param int $useridtocheck User that needs to be verified as a member.
 * @return int Return true or false depending on membership check.
 */
function studio_api_group_is_slot_group_member($groupmode, $groupid, $groupingid, $userid, $useridtocheck) {
    if (($groupid == content::VISIBILITY_GROUP) || ($groupid < 0)) {
        if ($groupid > 0) {
            // Not restricted to a specific group
            if ($groupmode == VISIBLEGROUPS) {
                // Any group members in the grouping can view the slot
                $ismember = studio_api_group_has_same_memberships($groupingid, $userid, $useridtocheck, false);
            } else if ($groupmode == SEPARATEGROUPS) {
                // Only users in a group with the user can view the slot
                $ismember = studio_api_group_has_same_memberships($groupingid, $userid, $useridtocheck, true);
            }
        } else {
            // Note: the groupid is a negative number, so we need to make it a positive number.
            $groupid = 0 - $groupid;

            // Check users against specified group id.
            $ismember = $ismember = studio_api_group_has_same_group($groupid, $userid, $useridtocheck);
        }

        return $ismember;
    }

    return false;
}

/**
 * For a given group grouping, check if two users belong to the same group grouping.
 *
 * @param int $groupingid Group grouping id.
 * @param int $userid
 * @param int $useridtocheck
 * @param boolean $strict Sety to true if check is for exact group membership
 * @return int Return count of same groups for users, or false if not in same group.
 */
function studio_api_group_has_same_memberships($groupingid, $userid, $useridtocheck, $strict = false) {
    global $DB;

    if ($userid == $useridtocheck) {
        return 1;
    }

    if ($strict) {
        $sql = <<<EOF
SELECT count(DISTINCT gm1.groupid)
  FROM {groups_members} gm1
  JOIN {groupings_groups} gg ON gg.groupid = gm1.groupid
  JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ? AND gg.groupingid = ?
 WHERE gm1.userid = ?

EOF;

        $result = $DB->count_records_sql($sql, array($userid, $groupingid, $useridtocheck));
    } else {
        $sql = <<<EOF
SELECT count(DISTINCT gm1.groupid)
  FROM {groups_members} gm1
  JOIN {groupings_groups} gg1 ON gg1.groupid = gm1.groupid AND gg1.groupingid = ?
 WHERE gm1.userid = ?
   AND EXISTS (SELECT count(DISTINCT gm2.groupid)
                 FROM {groups_members} gm2
                 JOIN {groupings_groups} gg2 ON gg2.groupid = gm2.groupid AND gg2.groupingid = gg1.groupingid
                WHERE gm2.userid = ?)

EOF;

        $result = $DB->count_records_sql($sql, array($groupingid, $userid, $useridtocheck));
    }

    if ($result === false) {
        return false;
    }

    return $result;
}

/**
 * Check if two users are in the same group.
 *
 * @param int $groupid Group to check against.
 * @param int $userid
 * @param int $useridtocheck
 * @return boolean Return true or false
 */
function studio_api_group_has_same_group($groupid, $userid, $useridtocheck) {
    global $DB;

    if ($userid == $useridtocheck) {
        return true;
    }

    $sql = <<<EOF
SELECT gm1.id
  FROM {groups_members} gm1
  JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ?
 WHERE gm1.groupid = ?
   AND gm1.userid = ?

EOF;

    return $DB->record_exists_sql($sql, array($useridtocheck, $groupid, $userid));
}

/**
 * Check if two users are enrolled on the same course.
 *
 * @param int $courseid Course to check against.
 * @param int $userid
 * @param int $useridtocheck
 * @return int
 */
function studio_api_group_has_same_course($courseid, $userid, $useridtocheck) {
    global $DB;

    if ($userid == $useridtocheck) {
        return true;
    }

    $sql = <<<EOF
SELECT count(ue.*)
  FROM {user_enrolments} ue
  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ?
 WHERE ue.userid = ?

EOF;

    $result1 = (bool) $DB->count_records_sql($sql, array($courseid, $useridtocheck));
    if (!$result1) {
        return false;
    }

    $result2 = (bool) $DB->count_records_sql($sql, array($courseid, $userid));
    if (!$result2) {
        return false;
    }

    if ($result1 === $result2) {
        return true;
    }

    return false;
}

/**
 * Check if two users are in the same group.
 *
 * @param int $courseid Course to resrtict group list against.
 * @param int $groupingid Grouping to resrtict group list against.
 * @param int $userid User to resrtict group list against.
 * @param int $groupmode Moodle group mode: visible or separate
 * @return mixed Return false on error, or recordset of group list.
 */
function studio_api_group_list($courseid, $groupingid, $userid, $groupmode = 1) {
    global $DB;

    if ($groupingid > 0) {
        $sql = <<<EOF
SELECT DISTINCT gm.groupid, g.name
  FROM {groups_members} gm
  JOIN {groupings_groups} gg ON gg.groupid = gm.groupid
  JOIN {groups} g ON g.id = gm.groupid
 WHERE gg.groupingid = ?

EOF;

        if ($groupmode == 2) {
            // All visible groups.
            $sql .= ' ORDER BY g.name ';
            $result = $DB->get_recordset_sql($sql, array($groupingid));
        } else {
            $sql .= ' AND gm.userid = ? ';
            $sql .= ' ORDER BY g.name ';
            $result = $DB->get_recordset_sql($sql, array($groupingid, $userid));
        }
    } else {
        $sql = <<<EOF
SELECT DISTINCT gm.groupid, g.name
  FROM {groups_members} gm
  JOIN {groups} g ON g.id = gm.groupid
 WHERE g.courseid = ?

EOF;

        if ($groupmode == 2) {
            // All visible groups.
            $sql .= ' ORDER BY g.name ';
            $result = $DB->get_recordset_sql($sql, array($courseid));
        } else {
            $sql .= ' AND gm.userid = ? ';
            $sql .= ' ORDER BY g.name ';
            $result = $DB->get_recordset_sql($sql, array($courseid, $userid));
        }
    }

    if (!$result->valid()) {
        return false;
    }

    return $result;
}

function studio_api_group_get_name($groupid) {
    global $DB;
    return $DB->get_field('groups', 'name', array('id' => $groupid));
}
