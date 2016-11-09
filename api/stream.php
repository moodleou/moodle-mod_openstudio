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

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\stream;
use mod_openstudio\local\api\tracking;

require_once($CFG->dirroot . '/tag/lib.php');

/**
 * Helper function to get slots from the pinboard.
 *
 * @param int $studioid
 * @param int $groupingid
 * @param int $userid
 * @param int $visibility
 * @param int $filtertype
 * @param int $filterscope
 * @param int $filterparticipation
 * @param int $filterstatus
 * @param string $filtertags
 * @param array $sortorder
 * @param int $pagestart
 * @param int $pagesize
 * @param bool $includecount
 * @param bool $canmanagecontent
 * @param bool $incollectionmode
 * @return mixed Return recordset of false if error.
 */
function studio_api_stream_get_pinboard_slots($studioid, $groupingid, $userid, $visibility,
        $filtertype = null, $filterscope = null,
        $filterparticipation = null, $filterstatus = null, $filtertags = null,
        $sortorder = array('id' => stream::SORT_BY_DATE, 'asc' => 0),
        $pagestart = 0, $pagesize = 0,
        $includecount = false, $canmanagecontent = false, $activitymode = false,
        $incollectionmode = false) {

    return studio_api_stream_get_slots($studioid, $groupingid, $userid, $userid, $visibility,
            null, $filtertype, $filterscope, $filterparticipation, $filterstatus, $filtertags,
            $sortorder,
            $pagestart, $pagesize, true, $includecount, $canmanagecontent,
            0, 0, $activitymode, false, $incollectionmode, false);

}

/**
 *
 * Example usage:
 *
 * 1) Get the logged in user's stream:
 * a) studio_api_stream_get_slots($studioid, $userid, $slotownerid, $visibility, ...)
 * b) $studioid = the studio instance to restrict the stream view
 * c) $userid = the logged in user's id.
 * d) $slotownerid = the logged in user's id.
 * e) $visibility can be group, module or private.
 *
 * 1) Get the stream of another user
 * a) studio_api_stream_get_slots($studioid, $userid, $slotownerid, $visibility, ...)
 * b) $studioid = the studio instance to restrict the stream view
 * c) $userid = the logged in user's id.
 * d) $slotownerid = the id of the user's stream to view
 * Note: visibility parameter is ignored in this case and will always be
 * set to visibility::WORKSPACE.
 *
 * NOTE: the $filtertype values are found in lib.php as:
 *     type::IMAGE
 *     type::VIDEO
 *     type::AUDIO
 *     type::DOCUMENT
 *     type::PRESENTATION
 *     type::SPREADSHEET
 *     type::URL
 *
 * NOTE: the $filterscope values are:
 *     mod_openstudio\local\api\stream::SCOPE_MY
 *     mod_openstudio\local\api\stream::SCOPE_EVERYONE
 *     mod_openstudio\local\api\stream::SCOPE_THEIRS
 *
 * NOTE: the $filterparticipation values are:
 *     mod_openstudio\local\api\stream::FILTER_FAVOURITES
 *     mod_openstudio\local\api\stream::FILTER_MOSTPOPULAR
 *     mod_openstudio\local\api\stream::FILTER_HELPME
 *     mod_openstudio\local\api\stream::FILTER_MOSTSMILES
 *     mod_openstudio\local\api\stream::FILTER_MOSTINSPIRATION
 *
 * NOTE: the $filterstatus values are:
 *     mod_openstudio\local\api\stream::FILTER_EMPTYCONTENT
 *     mod_openstudio\local\api\stream::FILTER_NOTREAD
 *     mod_openstudio\local\api\stream::FILTER_READ
 *     mod_openstudio\local\api\stream::FILTER_LOCKED
 *
 * NOTE: the $sortorder values are found in lib.php as:
 *     mod_openstudio\api\stream::SORT_BY_DATE
 *     mod_openstudio\api\stream::SORT_BY_USERNAME
 *     mod_openstudio\api\stream::SORT_BY_ACTIVITYTITLE
 *     mod_openstudio\api\stream::SORT_BY_COMMENTNUMBRS
 *
 * @param int $studioid
 * @param int $groupingid
 * @param int $userid
 * @param int $slotownerid
 * @param int $visibility
 * @param array $filterblocks
 * @param int $filtertype
 * @param int $filterscope
 * @param int $filterparticipation
 * @param int $filterstatus
 * @param string $filtertags
 * @param array $sortorder
 * @param int $pagestart
 * @param int $pagesize
 * @param bool $pinboardonly
 * @param bool $includecount
 * @param bool $canmanagecontent
 * @param int $groupid
 * @param int $groupmode
 * @param bool $activitymode
 * @param bool $canaccessallgroup
 * @param bool $incollectionmode
 * @param bool $slotreciprocalaccess
 * @param array $tutorroles Role IDs of roles considered to be "tutors"
 * @return mixed Return recordset of false if error.
 */
function studio_api_stream_get_slots(
        $studioid, $groupingid, $userid, $slotownerid, $visibility,
        $filterblocks = null, $filtertype = null, $filterscope = null,
        $filterparticipation = null, $filterstatus = null, $filtertags = null,
        $sortorder = array('id' => stream::SORT_BY_DATE, 'asc' => 0),
        $pagestart = 0, $pagesize = 0,
        $pinboardonly = false, $includecount = false, $canmanagecontent = false,
        $groupid = 0, $groupmode = 0, $activitymode = false,
        $canaccessallgroup = false, $incollectionmode = false,
        $slotreciprocalaccess = false, $tutorroles = array()) {

    global $DB;

    $params = array();

    $limitfrom = 0;
    $limitnum = 0;
    if (($pagestart >= 0) && ($pagesize > 0)) {
        $limitfrom = $pagestart * $pagesize;
        $limitnum = $pagesize;
    }

    // If we're looking at someone's else stream, then visibility is
    // hardcoded to visibility::WORKSPACE.
    // Likewise $pinboardonly is set to false.
    if ($userid != $slotownerid) {
        $visibility = content::VISIBILITY_WORKSPACE;
    }

    // NOTE:
    //
    // To prevent concatenated string of SQL statements not inserting the
    // necessary whitespace character, a deliberate extra line has been
    // added before every statement using "EOF;".

    $wheresql = '';

    // For the views My Module, My Group, My Pinbaord and user's workspace, we can do an inner join with the user table.
    $userjoinsql = 'INNER JOIN {user} u ON u.id = s.userid ';

    if ($pinboardonly) {
        $filterblocks = null;

        $selectsql = <<<EOF
0 AS l3id, 0 AS l2id, 0 AS l1id,
'Pinboard' AS l3name, '' AS l2name, '' AS l1name,
0 AS l3sortorder, 0 AS l2sortorder, 0 AS l1sortorder,
0 AS l2hidelevel, 0 AS l3required, 0 AS l3contenttype,

EOF;

        $fromsql = 'FROM {openstudio_contents} s ';

        $wheresql = 'AND s.levelcontainer = 0 ';

    } else if (in_array($visibility, array(content::VISIBILITY_GROUP, content::VISIBILITY_MODULE, content::VISIBILITY_WORKSPACE))) {
        $selectsql = <<<EOF
l3.id AS l3id, l2.id AS l2id, l1.id AS l1id,
l3.name AS l3name, l2.name AS l2name, l1.name AS l1name,
l3.sortorder AS l3sortorder, l2.sortorder AS l2sortorder, l1.sortorder AS l1sortorder,
l2.hidelevel AS l2hidelevel, l3.required AS l3required, l3.contenttype AS l3contenttype,

EOF;

        $fromsql = <<<EOF
           FROM {openstudio_contents} s
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = s.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.openstudioid = ?

EOF;

        $params[] = $studioid;

        // We show slots, if the slot is filled, and if not filled, then only
        // if the slot level is not deleted.
        $wheresql = 'AND (COALESCE(s.id, l3.status) >= 0) ';

    } else {
        $selectsql = <<<EOF
l3.id AS l3id, l2.id AS l2id, l1.id AS l1id,
l3.name AS l3name, l2.name AS l2name, l1.name AS l1name,
l3.sortorder AS l3sortorder, l2.sortorder AS l2sortorder, l1.sortorder AS l1sortorder,
l2.hidelevel AS l2hidelevel, l3.required AS l3required, l3.contenttype AS l3contenttype,

EOF;

        $fromsql = <<<EOF
           FROM {openstudio_level3} l3
     INNER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
     INNER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.openstudioid = ?
LEFT OUTER JOIN {openstudio_contents} s ON s.levelcontainer = 3 AND s.levelid = l3.id

EOF;

        $params[] = $studioid;

        // We show slots, if the slot is filled, and if not filled, then only
        // if the slot level is not deleted.
        $wheresql = 'AND (COALESCE(s.id, l3.status) >= 0) ';

        // For My Studio view, users need to be shown slots that may or may not exists,
        //  so we need do a LEFT OUTER JOIN to the user table to return slot records
        // if a slot without userid cannot be matched to a user record.
        $userjoinsql = 'LEFT OUTER JOIN {user} u ON u.id = s.userid ';

    }

    // Permission checks.
    $permissionresult = studio_api_stream_internal_get_slots_permission_check(
            $studioid, $groupingid, $userid, $slotownerid, $visibility,
            $filterblocks, $filtertype, $filterscope,
            $filterparticipation, $filterstatus, $filtertags,
            $sortorder,
            $pagestart, $pagesize,
            $pinboardonly, $includecount, $canmanagecontent,
            $groupid, $groupmode, $activitymode,
            $canaccessallgroup, $incollectionmode,
            $slotreciprocalaccess, $tutorroles);
    $permissionsql = $permissionresult->sql;
    $params = array_merge($params, $permissionresult->params);
    $fromsql .= $permissionresult->fromsql;

    // Studio instance filter.
    $studioinstancesql = 'AND (s.openstudioid IS NULL OR s.openstudioid = ?) ';
    $params[] = $studioid;

    // Get filter SQL and params.
    $filterresult = studio_api_stream_internal_get_slots_filters(
            $studioid, $groupingid, $userid, $slotownerid, $visibility,
            $filterblocks, $filtertype, $filterscope,
            $filterparticipation, $filterstatus, $filtertags,
            $sortorder,
            $pagestart, $pagesize,
            $pinboardonly, $includecount, $canmanagecontent,
            $groupid, $groupmode, $activitymode,
            $canaccessallgroup, $incollectionmode,
            $slotreciprocalaccess, $tutorroles);
    $filterblockssql = $filterresult->filterblockssql;
    $filtertypesql = $filterresult->filtertypesql;
    $filtertagsql = $filterresult->filtertagsql;
    $filterflagsql = $filterresult->filterflagsql;
    $filterstatussql = $filterresult->filterstatussql;
    $incollectionmodesql = $filterresult->incollectionmodesql;
    $reciprocalactivityslotsql = $filterresult->reciprocalactivityslotsql;
    $params = array_merge($params, $filterresult->params);

    // Apply sort ordering.
    $sortordersql = '';
    if (is_array($sortorder)) {
        if (array_key_exists('id', $sortorder)) {
            if (array_key_exists('asc', $sortorder) && ($sortorder['asc'] == 1)) {
                $sortordering = 1;
            } else {
                $sortordering = 0;
            }

            $sortorderid = $sortorder['id'];
            switch ($sortorderid) {
                case stream::SORT_BY_USERNAME:
                    $sortordersql = 'ORDER BY s.userid DESC ';
                    if ($sortordering == 1) {
                        $sortordersql = 'ORDER BY s.userid ASC ';
                    }
                    break;

                case stream::SORT_BY_ACTIVITYTITLE:
                    if ($pinboardonly) {
                        $sortordersql = 'ORDER BY s.name DESC ';
                        if ($sortordering == 1) {
                            $sortordersql = 'ORDER BY s.name ASC ';
                        }
                    } else {
                        if (is_array($filterblocks)) {
                            $sortordersql = 'ORDER BY l1sortorder, l2sortorder, l3sortorder, s.name DESC ';
                            if ($sortordering == 1) {
                                $sortordersql = 'ORDER BY l1sortorder, l2sortorder, l3sortorder, s.name ASC ';
                            }
                        } else {
                            $sortordersql = 'ORDER BY l1name DESC, l2name DESC, l3name DESC, s.name DESC ';
                            if ($sortordering == 1) {
                                $sortordersql = 'ORDER BY l1name ASC, l2name ASC, l3name ASC, s.name ASC ';
                            }
                        }
                    }
                    break;

                case stream::SORT_BY_DATE:
                default:
                    $sortordersql = 'ORDER BY s.timemodified DESC NULLS LAST ';
                    if ($sortordering == 1) {
                        $sortordersql = 'ORDER BY s.timemodified ASC NULLS LAST ';
                    }
                    break;
            }
        }
    }

    /*
     * NOTE:
     * To make the construction of the where clauses predicatable,
     * a dummy statement of WHERE 1 = 1 has been added to the SQL below
     * so all dynamically constructed where clauses can begin with
     * "AND ....".
     *
     */

    // Construct the main SQL.
    $sqlselect = <<<EOF
SELECT {$selectsql}
       u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
       u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
       s.id, s.contenttype, s.mimetype, s.content, s.thumbnail, s.urltitle,
       s.name, s.description, s.ownership, s.ownershipdetail,
       s.visibility, s.userid, s.timemodified, s.timeflagged,
       s.levelid, s.levelcontainer, s.openstudioid, s.locktype, s.lockedby

EOF;

    $sqlmain = <<<EOF
{$fromsql}

{$userjoinsql}

 WHERE 1 = 1

{$permissionsql}

{$studioinstancesql}

{$wheresql}

{$filterblockssql}

{$filtertypesql}

{$filtertagsql}

{$filterflagsql}

{$filterstatussql}

{$incollectionmodesql}

{$reciprocalactivityslotsql}

AND (s.visibility IS NULL OR s.visibility != ?)

EOF;

    $params[] = content::VISIBILITY_INFOLDERONLY;

    if ($activitymode) {
        $sqlmain .= <<<EOF
AND (s.userid = ?
     OR (s.id IN (SELECT sf1.contentid
                    FROM {openstudio_flags} sf1
                   WHERE sf1.userid = ? AND sf1.flagid IN (?, ?, ?, ?, ?, ?)))
     OR (s.userid IN (SELECT sf2.personid
                        FROM {openstudio_flags} sf2
                       WHERE sf2.userid = ? AND sf2.flagid = ?))
    )

EOF;

        $params[] = $userid;
        $params[] = $userid;
        $params[] = flags::ALERT;
        $params[] = flags::FAVOURITE;
        $params[] = flags::NEEDHELP;
        $params[] = flags::MADEMELAUGH;
        $params[] = flags::INSPIREDME;
        $params[] = flags::FOLLOW_CONTENT;
        $params[] = $userid;
        $params[] = flags::FOLLOW_USER;

        $sql = $sqlselect . $sqlmain;
        $sqlwithsortorder = $sql . ' ORDER BY s.timeflagged DESC';
    } else {
        // SQL statement with additional sort order statement appended.
        $sql = $sqlselect . $sqlmain;
        $sqlwithsortorder = $sql . $sortordersql;
    }

    try {
        if ($includecount) {
            $sqlcount = "SELECT count(1) {$sqlmain}";
            $resultcount = $DB->count_records_sql($sqlcount, $params);
        }

        $result = $DB->get_recordset_sql($sqlwithsortorder, $params, $limitfrom, $limitnum);

        if (!$result->valid()) {
            return false;
        }

        if ($includecount) {
            return (object) array('slots' => $result, 'total' => $resultcount);
        }

        return $result;
    } catch (Exception $e) {
        // Default to returning false.
    }

    return false;
}

/**
 * Gets list of slots by given ids.
 *
 * @param int $slotids
 * @param array $slotid
 * @param mixed $ignoreslottype Default is false, or slot content type.
 * @param bool $slotreciprocalaccess Restrict studio work slot so user only sees slots that they also created.
 * @return object Return slots data.
 */
function studio_api_stream_get_slots_by_ids(
        $userid, $slotids, $ignoreslottype = false, $slotreciprocalaccess = false) {
    global $DB;

    list($filterinsql, $filterinparams) = $DB->get_in_or_equal($slotids);
    $params = $filterinparams;

    $sql = <<<EOF
         SELECT l3.id AS l3id, l2.id AS l2id, l1.id AS l1id,
                l3.name AS l3name, l2.name AS l2name, l1.name AS l1name,
                l2.hidelevel AS l2hidelevel, l3.required AS l3required, l3.contenttype AS l3contenttype,
                u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
                u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                s.id, s.contenttype, s.mimetype, s.content, s.thumbnail, s.urltitle,
                s.name, s.description, s.ownership, s.ownershipdetail,
                s.visibility, s.userid, s.timemodified,
                s.levelid, s.levelcontainer, s.openstudioid, s.lockedby, s.locktype
           FROM {openstudio_contents} s
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = s.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.openstudioid = s.openstudioid
     INNER JOIN {user} u ON u.id = s.userid
          WHERE s.id {$filterinsql}

EOF;

    if ($ignoreslottype !== false) {
        $sql .= ' AND s.contenttype <> ? ';
        $params[] = (int) $ignoreslottype;
    }

    if ($slotreciprocalaccess) {
        $sql .= <<<EOF
    AND EXISTS (SELECT 1
                  FROM {openstudio_contents} ras
                 WHERE ras.userid = ?
                   AND ras.levelid = s.levelid
                   AND ras.levelcontainer = s.levelcontainer)

EOF;
        $params[] = $userid;
    }

    try {
        $result = $DB->get_recordset_sql($sql, $params);
        if (!$result->valid()) {
            return false;
        }

        return $result;
    } catch (Exception $e) {
        // Default to returning false.
    }

    return false;
}

/**
 * Gets slot and slot version data.
 *
 * @param int $viewerid
 * @param int $slotid
 * @param bool $includedeleted True to include deleted slot verisons.
 * @return object Return slot and slot version data.
 */
function studio_api_stream_get_slot_and_versions($viewerid, $slotid, $includedeleted = false) {
    global $DB;

    try {
        $result = (object) array();

        $result->slotdata = studio_api_slot_get_record($viewerid, $slotid);

        if ($includedeleted) {
            $result->slotversions = $DB->get_records('studio_slot_versions',
                    array('contentid' => $slotid), 'timemodified desc');
        } else {
            $sql = <<<EOF
  SELECT *
    FROM {openstudio_content_versions}
   WHERE contentid = ?
     AND deletedby IS NULL
ORDER BY timemodified desc

EOF;

            $result->slotversions = $DB->get_records_sql($sql, array($slotid));
        }

        return $result;
    } catch (Exception $e) {
        // Default to returning false.
    }

    return false;
}


function studio_api_stream_internal_get_slots_permission_check(
        $studioid, $groupingid, $userid, $slotownerid, $visibility,
        $filterblocks = null, $filtertype = null, $filterscope = null,
        $filterparticipation = null, $filterstatus = null, $filtertags = null,
        $sortorder = array('id' => stream::SORT_BY_DATE, 'asc' => 0),
        $pagestart = 0, $pagesize = 0,
        $pinboardonly = false, $includecount = false, $canmanagecontent = false,
        $groupid = 0, $groupmode = 0, $activitymode = false,
        $canaccessallgroup = false, $incollectionmode = false,
        $slotreciprocalaccess = false, $tutorroles = array()) {

    global $DB;

    $permissionsql = '';
    $fromsql = '';
    $params = array();

    // Permission checks.
    switch ($visibility) {
        case content::VISIBILITY_PRIVATE:
        case content::VISIBILITY_PRIVATE_PINBOARD:
            // Find all slots that belong to the $userid.
            if ($pinboardonly) {
                $permissionsql .= <<<EOF
AND (s.id IS NULL OR s.userid = ?)
AND (s.deletedby IS NULL AND s.deletedtime IS NULL)

EOF;
            } else {
                $fromsql .= 'AND (s.id IS NULL OR s.userid = ?) ';
            }

            $params[] = $userid;
            break;

        case content::VISIBILITY_GROUP:
            // Get course id from studioid.
            $courseid = mod_openstudio\local\util::get_courseid_from_studioid($studioid);

            // If we cant find the course id, which shouldnt be possible anyway, then
            // set courseid to zero so that any permission check using it will fail anyway.
            if ($courseid == false) {
                $courseid = 0;
            }

            // The following SQL snippet is only applicable if visibility::TUTOR is enabled.
            // This is specific to the OU's concept of a "tutor".
            $roleparams = array();
            if (!empty($tutorroles)) {
                list($rolesql, $roleparams) = $DB->get_in_or_equal($tutorroles);
                $tutorsubquery = <<<EOF
                    SELECT 1
                      FROM {role_assignments} ra
                      JOIN {role} r ON ra.roleid = r.id
                      JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                      JOIN {course} c ON c.id = ctx.instanceid
                      JOIN {openstudio} st ON c.id = st.course
                      JOIN {openstudio_contents} s1 ON s1.openstudioid = st.id
                      JOIN {groups_members} tgm1 ON tgm1.userid = s1.userid
                      JOIN {groups} tg1 ON tg1.id = tgm1.groupid AND tg1.courseid = c.id
                      JOIN {groups_members} tgm2 ON tg1.id = tgm2.groupid
                      JOIN {course_modules} cm ON cm.instance = st.id
                      JOIN {modules} m ON m.id = cm.module AND m.name = 'studio'
                      JOIN {groupings_groups} gg3 ON gg3.groupingid = cm.groupingid AND gg3.groupid = tg1.id
                     WHERE ra.roleid {$rolesql}
                       AND ra.userid = ?
                       AND s1.id = s.id
                       AND tgm2.userid = ?

EOF;
            }

            if (($groupmode == 2) && ($groupid > 0)) {
                // If all group visible, and a specific group id has been requested...

                $params[] = content::TYPE_NONE;
                $params[] = content::VISIBILITY_MODULE;
                $params[] = content::VISIBILITY_GROUP;
                $params[] = $groupid;
                $params[] = $groupingid;
                $params[] = $groupid;

                $grouppermissionchecksql = '';
                if (!$canaccessallgroup) {
                    $params[] = $groupingid;
                    $params[] = $userid;

                    $grouppermissionchecksql = <<<EOF
AND EXISTS (SELECT 1
              FROM {groups_members} gm2
              JOIN {groupings_groups} gg2 ON gg2.groupid = gm2.groupid AND gg2.groupingid = ?
             WHERE gm2.userid = ?)

EOF;
                }
                $grouppermissionchecksql .= <<<EOF
AND (EXISTS (SELECT 1
               FROM {groups_members} gmocheck
              WHERE gmocheck.groupid = (0 - s.visibility) AND gmocheck.userid = s.userid)
         OR s.visibility = 2
         OR s.visibility = 3)

EOF;

                $tutorpermissionsql = '';
                if (!empty($tutorroles)) {
                    // The following parameters are only applicable if visibility::TUTOR is enabled.
                    // This is specific to the OU's concept of a "tutor".
                    $params[] = content::VISIBILITY_TUTOR;
                    $params = array_merge($params, $roleparams);
                    $params[] = $userid;
                    $params[] = $userid;
                    $params[] = $groupid;
                    $tutorpermissionsql = <<<EOF
                        OR (
                            s.visibility = ?
                            AND EXISTS (
                                {$tutorsubquery}
                                AND tg1.id = ?
                            )
                        )
EOF;
                }

                $permissionsql = <<<EOF
AND (
    s.contenttype <> ?
    AND (
        (
            (
                s.visibility = ?
                OR s.visibility = ?
                OR (
                    s.visibility < 0
                    AND (0 - s.visibility) = ?
                )
            )
            AND EXISTS (
                SELECT 1
                  FROM {groups_members} gm1
                  JOIN {groupings_groups} gg1 ON gg1.groupid = gm1.groupid AND gg1.groupingid = ?
                 WHERE gm1.groupid = ?
                   AND gm1.userid = s.userid
            )
            {$grouppermissionchecksql}
        )
        {$tutorpermissionsql}
    )
)

EOF;

            } else if (($groupmode == 2) && ($groupid <= 0)) {
                // If all group visible, and no specific group id has been requested...

                $params[] = content::TYPE_NONE;
                $params[] = content::VISIBILITY_MODULE;
                $params[] = content::VISIBILITY_GROUP;
                $params[] = $groupingid;

                $grouppermissionchecksql = '';
                if (!$canaccessallgroup) {
                    $params[] = $groupingid;
                    $params[] = $userid;

                    $grouppermissionchecksql = <<<EOF
AND EXISTS (SELECT 1
              FROM {groups_members} gm2
              JOIN {groupings_groups} gg2 ON gg2.groupid = gm2.groupid AND gg2.groupingid = ?
             WHERE gm2.userid = ?)

EOF;
                }
                $grouppermissionchecksql .= <<<EOF
AND (EXISTS (SELECT 1
               FROM {groups_members} gmocheck
              WHERE gmocheck.groupid = (0 - s.visibility) AND gmocheck.userid = s.userid)
         OR s.visibility = 2
         OR s.visibility = 3)

EOF;

                $tutorpermissionsql = '';
                if (!empty($tutorroles)) {
                    $tutorpermissionsql = <<<EOF
                        OR (
                            s.visibility = ?
                            AND EXISTS (
                                {$tutorsubquery}
                            )
                        )
EOF;

                    // The following parameters are only applicable if visibility::TUTOR is enabled.
                    // This is specific to the OU's concept of a "tutor".
                    $params[] = content::VISIBILITY_TUTOR;
                    $params = array_merge($params, $roleparams);
                    $params[] = $userid;
                    $params[] = $userid;
                }

                $permissionsql = <<<EOF
AND (
    s.contenttype <> ?
    AND (
        (
            (
                s.visibility = ?
                OR s.visibility = ?
                OR s.visibility < 0
            )
            AND EXISTS (
                SELECT 1
                  FROM {groups_members} gm1
                  JOIN {groupings_groups} gg1 ON gg1.groupid = gm1.groupid AND gg1.groupingid = ?
                 WHERE gm1.userid = s.userid
            )
            {$grouppermissionchecksql}
        )
        {$tutorpermissionsql}
    )
)

EOF;

            } else if (($groupmode != 2) && $groupid > 0) {
                // If group mode is not all group visible,
                // and a specific group id has been requested...

                $params[] = content::TYPE_NONE;
                $params[] = content::VISIBILITY_MODULE;
                $params[] = content::VISIBILITY_GROUP;
                $params[] = $groupingid;
                $params[] = $userid;
                $params[] = $groupid;
                $params[] = content::VISIBILITY_MODULE;

                $grouppermissionchecksql = '';
                if (!$canaccessallgroup) {
                    $params[] = $groupid;
                    $params[] = $userid;

                    $grouppermissionchecksql = <<<EOF
AND EXISTS (SELECT 1
              FROM {groups_members} gm3
             WHERE gm3.groupid = (0 - s.visibility)
               AND gm3.groupid = ?
               AND gm3.userid = ?)

EOF;
                }
                $grouppermissionchecksql .= <<<EOF
AND EXISTS (SELECT 1
              FROM {groups_members} gmocheck
             WHERE (gmocheck.groupid = (0 - s.visibility) AND gmocheck.userid = s.userid)
               AND s.visibility < 0)

EOF;

                $tutorpermissionsql = '';
                if (!empty($tutorroles)) {
                    // The following parameters are only applicable if visibility::TUTOR is enabled.
                    // This is specific to the OU's concept of a "tutor".
                    $params[] = content::VISIBILITY_TUTOR;
                    $params = array_merge($params, $roleparams);
                    $params[] = $userid;
                    $params[] = $userid;
                    $params[] = $groupid;
                    $tutorpermissionsql = <<<EOF
                        OR (
                            s.visibility = ?
                            AND EXISTS (
                                {$tutorsubquery}
                                AND tg1.id = ?
                            )
                        )
EOF;
                }

                $permissionsql = <<<EOF
AND (
    s.contenttype <> ?
    AND (
        (
            (
                (
                    s.visibility = ?
                    OR s.visibility = ?
                )
                AND EXISTS (
                    SELECT 1
                      FROM {groups_members} gm1
                      JOIN {groupings_groups} gg ON gg.groupid = gm1.groupid AND gg.groupingid = ?
                      JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ?
                     WHERE gm1.groupid = ?
                       AND gm1.userid = s.userid
                )
            )
            OR (
                    (
                        s.visibility = ?
                        OR s.visibility < 0
                    )
                    {$grouppermissionchecksql}
            )
        )
        {$tutorpermissionsql}
    )
)

EOF;

            } else {
                // View all groups slot that user is member of...

                $params[] = content::TYPE_NONE;
                $params[] = content::VISIBILITY_MODULE;
                $params[] = content::VISIBILITY_GROUP;
                $params[] = $groupingid;
                $params[] = $userid;
                $params[] = content::VISIBILITY_MODULE;

                $grouppermissionchecksql = '';
                if (!$canaccessallgroup) {
                    $params[] = $userid;

                    $grouppermissionchecksql = <<<EOF
                 AND EXISTS (SELECT 1
                               FROM {groups_members} gm3
                              WHERE gm3.groupid = (0 - s.visibility)
                                AND gm3.userid = ?)

EOF;
                }
                $grouppermissionchecksql .= <<<EOF
AND EXISTS (SELECT 1
              FROM {groups_members} gmocheck
             WHERE (gmocheck.groupid = (0 - s.visibility) AND gmocheck.userid = s.userid)
               AND (s.visibility < 0 OR s.visibility = 2 OR s.visibility = 3))

EOF;

                $tutorpermissionsql = '';
                if (!empty($tutorroles)) {
                    $tutorpermissionsql = <<<EOF
                        OR (
                            s.visibility = ?
                            AND EXISTS (
                                {$tutorsubquery}
                            )
                        )
EOF;

                    // The following parameters are only applicable if visibility::TUTOR is enabled.
                    // This is specific to the OU's concept of a "tutor".
                    $params[] = content::VISIBILITY_TUTOR;
                    $params = array_merge($params, $roleparams);
                    $params[] = $userid;
                    $params[] = $userid;
                }

                $permissionsql = <<<EOF
AND (
    s.contenttype <> ?
    AND (
        (
            (
                (
                    s.visibility = ?
                    OR s.visibility = ?
                )
                AND EXISTS (
                    SELECT 1
                      FROM {groups_members} gm1
                      JOIN {groupings_groups} gg ON gg.groupid = gm1.groupid AND gg.groupingid = ?
                      JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ?
                     WHERE gm1.userid = s.userid
                )
            )
            OR (
                (
                    s.visibility = ?
                    OR s.visibility < 0
                )
                {$grouppermissionchecksql}
            )
        )
        {$tutorpermissionsql}
    )
)

EOF;
            }
            break;

        case content::VISIBILITY_MODULE:
            $permissionsql = 'AND s.contenttype <> ? AND s.visibility = ? ';
            $params[] = content::TYPE_NONE;
            $params[] = content::VISIBILITY_MODULE;

            // If the user does not have managecontent capability then we
            // restrict the content based on permissions.
            if (!$canmanagecontent) {
                // Get course id from studioid.
                $courseid = mod_openstudio\local\util::get_courseid_from_studioid($studioid);
                // If we cant find the course id, which shouldnt be possible anyway, then
                // set courseid to zero so that any permission check using it will fail anyway.
                if ($courseid == false) {
                    $courseid = 0;
                }

                // Find all slots that has been granted to courses
                // that the $userid is a also enrolled on.
                $permissionsql .= <<<EOF
AND EXISTS (SELECT 1
              FROM {user_enrolments} ue
              JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ?
             WHERE ue.userid = ?)

EOF;

                $params[] = $courseid;
                $params[] = $userid;
            }
            break;

        case content::VISIBILITY_WORKSPACE:
            $permissionsql = 'AND s.userid = ? ';
            $params[] = $slotownerid;

            // If the user does not have managecontent capability then we
            // restrict the content based on permissions.
            if (!$canmanagecontent) {
                // Get course id from studioid.
                $courseid = mod_openstudio\local\util::get_courseid_from_studioid($studioid);
                // If we cant find the course id, which shouldnt be possible anyway, then
                // set courseid to zero so that any permission check using it will fail anyway.
                if ($courseid == false) {
                    $courseid = 0;
                }

                if ($canaccessallgroup) {
                    $permissionsql .= <<<EOF
AND s.contenttype <> ?
AND (   (2 = ? AND s.visibility = ?)
     OR (s.visibility < 0)
     OR (s.visibility = ?
         AND EXISTS (SELECT 1
                       FROM {user_enrolments} ue
                       JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ?
                      WHERE ue.userid = ?))
    )

EOF;

                    $params[] = content::TYPE_NONE;
                    $params[] = $groupmode;
                    $params[] = content::VISIBILITY_GROUP;
                    $params[] = content::VISIBILITY_MODULE;
                    $params[] = $courseid;
                    $params[] = $userid;
                } else {
                    $permissionsql .= <<<EOF
AND s.contenttype <> ?
AND (   (           (2 = ? AND s.visibility = ?)
         AND EXISTS (SELECT 1
                       FROM {groups_members} gm1
                       JOIN {groupings_groups} gg1 ON gg1.groupid = gm1.groupid AND gg1.groupingid = ?
                      WHERE gm1.userid = s.userid)
         AND EXISTS (SELECT 1
                       FROM {groups_members} gm2
                       JOIN {groupings_groups} gg2 ON gg2.groupid = gm2.groupid AND gg2.groupingid = ?
                      WHERE gm2.userid = ?))
     OR (           s.visibility < 0
         AND EXISTS (SELECT 1
                       FROM {groups_members} gm3
                      WHERE gm3.groupid = (0 - s.visibility)
                        AND gm3.userid = ?))
     OR (           s.visibility = ?
         AND EXISTS (SELECT 1
                       FROM {user_enrolments} ue
                       JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ?
                      WHERE ue.userid = ?))
    )

EOF;

                    $params[] = content::TYPE_NONE;
                    $params[] = $groupmode;
                    $params[] = content::VISIBILITY_GROUP;
                    $params[] = $groupingid;
                    $params[] = $groupingid;
                    $params[] = $userid;
                    $params[] = $userid;
                    $params[] = content::VISIBILITY_MODULE;
                    $params[] = $courseid;
                    $params[] = $userid;
                }
            }
            break;
    }

    return (object) array(
        'sql' => $permissionsql,
        'fromsql' => $fromsql,
        'params' => $params
    );
}

function studio_api_stream_internal_get_slots_filters(
        $studioid, $groupingid, $userid, $slotownerid, $visibility,
        $filterblocks = null, $filtertype = null, $filterscope = null,
        $filterparticipation = null, $filterstatus = null, $filtertags = null,
        $sortorder = array('id' => stream::SORT_BY_DATE, 'asc' => 0),
        $pagestart = 0, $pagesize = 0,
        $pinboardonly = false, $includecount = false, $canmanagecontent = false,
        $groupid = 0, $groupmode = 0, $activitymode = false,
        $canaccessallgroup = false, $incollectionmode = false,
        $slotreciprocalaccess = false, $tutorroles = array()) {

    global $DB;

    $params = array();

    // Filter by block level.
    $filterblockssql = '';
    if (is_array($filterblocks)) {
        /*
         * Defensive check to make sure the parameters are what we
         * expect, which is an array of numbers.
         */
        $filterblockschecked = array();
        foreach ($filterblocks as $value) {
            if (trim($value) != '') {
                $filterblockschecked[] = (int) $value;
            }
        }
        if (!empty($filterblockschecked)) {
            list($filterblocksinsql, $filterblocksinparams)
                    = $DB->get_in_or_equal($filterblockschecked);
            $filterblockssql = "AND l1.id $filterblocksinsql";
            $params = array_merge($params, $filterblocksinparams);
        }
    } else if ($filterblocks === -1) {
        $filterblockssql = 'AND s.levelid = 0 ';
    }

    // Filter by slot content type.
    $filtertypesql = '';
    $filtertypearray = explode(',', $filtertype);
    foreach ($filtertypearray as $filtertypearrayitem) {
        $filtertypearrayitem = trim($filtertypearrayitem);
        if ($filtertypearrayitem !== '') {
            switch ($filtertypearrayitem) {
                case content::TYPE_IMAGE:
                    $filtertypesql .= 'OR s.contenttype IN '
                            . '(' . content::TYPE_IMAGE . ', '
                            . content::TYPE_IMAGE_EMBED . ', '
                            . content::TYPE_URL_IMAGE . ') ';
                    break;

                case content::TYPE_VIDEO:
                    $filtertypesql .= 'OR s.contenttype IN '
                            . '(' . content::TYPE_VIDEO . ', '
                            . content::TYPE_VIDEO_EMBED . ', '
                            . content::TYPE_URL_VIDEO . ') ';
                    break;

                case content::TYPE_AUDIO:
                    $filtertypesql .= 'OR s.contenttype IN '
                            . '(' . content::TYPE_AUDIO . ', '
                            . content::TYPE_AUDIO_EMBED . ', '
                            . content::TYPE_URL_AUDIO . ') ';
                    break;

                case content::TYPE_DOCUMENT:
                    $filtertypesql .= 'OR s.contenttype IN '
                            . '(' . content::TYPE_DOCUMENT . ', '
                            . content::TYPE_DOCUMENT_EMBED . ', '
                            . content::TYPE_URL_DOCUMENT . ', '
                            . content::TYPE_URL_DOCUMENT_PDF . ', '
                            . content::TYPE_URL_DOCUMENT_DOC . ') ';
                    break;

                case content::TYPE_PRESENTATION:
                    $filtertypesql .= 'OR s.contenttype IN '
                            . '(' . content::TYPE_PRESENTATION . ', '
                            . content::TYPE_PRESENTATION_EMBED . ', '
                            . content::TYPE_URL_PRESENTATION . ', '
                            . content::TYPE_URL_PRESENTATION_PPT . ') ';
                    break;

                case content::TYPE_SPREADSHEET:
                    $filtertypesql .= 'OR s.contenttype IN '
                            . '(' . content::TYPE_SPREADSHEET . ', '
                            . content::TYPE_SPREADSHEET_EMBED . ', '
                            . content::TYPE_URL_SPREADSHEET . ', '
                            . content::TYPE_URL_SPREADSHEET_XLS . ') ';
                    break;

                case content::TYPE_URL:
                    $filtertypesql .= 'OR s.contenttype IN '
                            . '(' . content::TYPE_TEXT . ', '
                            . content::TYPE_URL . ', '
                            . content::TYPE_URL_VIDEO . ', '
                            . content::TYPE_URL_VIDEO . ', '
                            . content::TYPE_URL_AUDIO . ', '
                            . content::TYPE_URL_DOCUMENT . ', '
                            . content::TYPE_URL_DOCUMENT_PDF . ', '
                            . content::TYPE_URL_DOCUMENT_DOC . ', '
                            . content::TYPE_URL_PRESENTATION . ', '
                            . content::TYPE_URL_PRESENTATION_PPT . ', '
                            . content::TYPE_URL_SPREADSHEET . ', '
                            . content::TYPE_URL_SPREADSHEET_XLS . ') ';
                    break;

                case content::COLLECTION:
                    $filtertypesql .= 'OR s.contenttype = ' . content::COLLECTION . ' ';
                    break;

                case content::TYPE_FOLDER:
                    $filtertypesql .= 'OR s.contenttype = ' . content::TYPE_FOLDER . ' ';
                    break;
            }
        }
    }
    if ($filtertypesql != '') {
        $filtertypesql = "AND (s.contenttype = -1 {$filtertypesql}) ";
    }

    // Filter by tags.
    $filtercleanedtags = core_tag_tag::normalize(explode(',', $filtertags));
    $filtertagsql = '';
    if (count($filtercleanedtags) > 0) {
        list($filtertaginsql, $filtertaginparams) = $DB->get_in_or_equal($filtercleanedtags);
        $filtertagsubsql = "SELECT id FROM {tag} WHERE name {$filtertaginsql} ";
        $filtertagsql = <<<EOF
AND EXISTS (SELECT 1
              FROM {tag_instance}
             WHERE tagid IN ({$filtertagsubsql})
               AND itemid = s.id
)
EOF;

        $params = array_merge($params, $filtertaginparams);
    }

    // Filter by participation.
    $filterflagsql = '';
    $filterflagarray = array();
    $filterparticipationarray = explode(',', $filterparticipation);
    foreach ($filterparticipationarray as $filterparticipationarrayitem) {
        $filterparticipationarrayitem = (int) trim($filterparticipationarrayitem);
        if ($filterparticipationarrayitem !== '') {
            // Map filterbyflag to flag id.
            if ($filterparticipationarrayitem === stream::FILTER_FAVOURITES) {
                $filterflagarray[] = flags::FAVOURITE;
            } else if ($filterparticipationarrayitem === stream::FILTER_MOSTSMILES) {
                $filterflagarray[] = flags::MADEMELAUGH;
            } else if ($filterparticipationarrayitem === stream::FILTER_MOSTINSPIRATION) {
                $filterflagarray[] = flags::INSPIREDME;
            } else if ($filterparticipationarrayitem === stream::FILTER_HELPME) {
                $filterflagarray[] = flags::NEEDHELP;
            }
        }
    }
    $filterflag1sql = '';
    if (!empty($filterflagarray)) {
        list($filterflaginsql, $filterflaginparams) = $DB->get_in_or_equal($filterflagarray);
        $filterflag1sql .= "EXISTS (SELECT f.id FROM {openstudio_flags} f WHERE f.contentid = s.id AND f.flagid {$filterflaginsql} ";
        $params = array_merge($params, $filterflaginparams);
        if ($filterscope === stream::SCOPE_MY) {
            $filterflag1sql .= ' AND f.userid = ? ';
            $params[] = $userid;
        } else if ($filterscope === stream::SCOPE_THEIRS) {
            $filterflag1sql .= ' AND f.userid <> ? ';
            $params[] = $userid;
        }
        $filterflag1sql .= ' ) ';
    }
    $filterflag2sql = '';
    if (in_array(stream::FILTER_COMMENTS, $filterparticipationarray)) {
        $filterflag2sql .= 'EXISTS (SELECT c.id FROM {openstudio_comments} c WHERE c.contentid = s.id AND c.deletedby IS NULL ';
        if ($filterscope === stream::SCOPE_MY) {
            $filterflag2sql .= ' AND c.userid = ? ';
            $params[] = $userid;
        } else if ($filterscope === stream::SCOPE_THEIRS) {
            $filterflag2sql .= ' AND c.userid <> ? ';
            $params[] = $userid;
        }
        $filterflag2sql .= ' ) ';
    }
    $filterflag3sql = '';
    if (in_array(stream::FILTER_TUTOR, $filterparticipationarray)) {
        $filterflag3sql .= 's.visibility = ?';
        $params[] = content::VISIBILITY_TUTOR;
    }

    if ((trim($filterflag1sql) != '') && (trim($filterflag2sql) != '')) {
        $filterflagsql = "AND ({$filterflag1sql} OR {$filterflag2sql}) ";
    } else if (trim($filterflag1sql) != '') {
        $filterflagsql = "AND {$filterflag1sql} ";
    } else if (trim($filterflag2sql) != '') {
        $filterflagsql = "AND {$filterflag2sql} ";
    } else if (trim($filterflag3sql) != '') {
        $filterflagsql = "AND {$filterflag3sql} ";
    }

    // Filter by status.
    $filterstatussql = '';
    switch ($filterstatus) {
        case stream::FILTER_READ:
            $filterstatussql .= 'AND EXISTS (SELECT f.id FROM {openstudio_flags} f WHERE f.contentid = s.id AND f.flagid = ? ';
            $params[] = flags::READ_CONTENT;
            if ($filterscope === stream::SCOPE_MY) {
                // Find all slots the the user has read.
                $filterstatussql .= ' AND f.userid = ? ';
                $params[] = $userid;
            } else if ($filterscope === stream::SCOPE_THEIRS) {
                $filterstatussql .= ' AND f.userid <> ? ';
                $params[] = $userid;
            }
            $filterstatussql .= ' ) ';
            break;

        case stream::FILTER_NOTREAD:
            $filterstatussql .= 'AND NOT EXISTS (SELECT f.id FROM {openstudio_flags} f WHERE f.contentid = s.id AND f.flagid = ? ';
            $params[] = flags::READ_CONTENT;
            if ($filterscope === stream::SCOPE_MY) {
                // Find all slots the the user has not read.
                $filterstatussql .= ' AND f.userid = ? ';
                $params[] = $userid;
            } else if ($filterscope === stream::SCOPE_THEIRS) {
                $filterstatussql .= ' AND f.userid <> ? ';
                $params[] = $userid;
            }
            $filterstatussql .= ' ) ';
            break;

        case stream::FILTER_EMPTYCONTENT:
            $filterstatussql .= 'AND s.id IS NULL ';
            break;

        case stream::FILTER_LOCKED:
            $filterstatussql .= 'AND s.locktype > 0 AND s.lockedby IS NOT NULL ';
            break;
    }

    $incollectionmodesql = '';
    if ($incollectionmode) {
        $incollectionmodesql = ' AND s.contenttype <> ? ';
        $params[] = content::COLLECTION;
    }

    $reciprocalactivityslotsql = '';
    if ($slotreciprocalaccess && in_array($visibility,
            array(content::VISIBILITY_MODULE, content::VISIBILITY_GROUP, content::VISIBILITY_WORKSPACE))) {
        $reciprocalactivityslotsql = <<<EOF
    AND EXISTS (SELECT 1
                  FROM {openstudio_contents} ras
                 WHERE ras.userid = ?
                   AND ras.levelid = s.levelid
                   AND ras.levelcontainer = s.levelcontainer)

EOF;

        $params[] = $userid;
    }

    return (object) array(
        'filterblockssql' => $filterblockssql,
        'filtertypesql' => $filtertypesql,
        'filtertagsql' => $filtertagsql,
        'filterflagsql' => $filterflagsql,
        'filterstatussql' => $filterstatussql,
        'incollectionmodesql' => $incollectionmodesql,
        'reciprocalactivityslotsql' => $reciprocalactivityslotsql,
        'params' => $params
    );
}

function studio_api_stream_get_slot_activities(
        $currenttime = false, $endtime = false,
        $studioid, $groupingid, $userid, $slotownerid, $visibility,
        $filterblocks = null, $filtertype = null, $filterscope = null,
        $filterparticipation = null, $filterstatus = null, $filtertags = null,
        $sortorder = array('id' => stream::SORT_BY_DATE, 'asc' => 0),
        $pagestart = 0, $pagesize = 0,
        $pinboardonly = false, $includecount = false, $canmanagecontent = false,
        $groupid = 0, $groupmode = 0, $activitymode = false,
        $canaccessallgroup = false, $incollectionmode = false,
        $slotreciprocalaccess = false, $tutorroles = array()) {

    global $DB;

    try {

        $sql = '';
        $params = array();

        $limitfrom = 0;
        $limitnum = 0;

        // If we're looking at someone's else stream, then visibility is
        // hardcoded to visibility::WORKSPACE.
        // Likewise $pinboardonly is set to false.
        if ($userid != $slotownerid) {
            $visibility = content::VISIBILITY_WORKSPACE;
        }

        if (!$currenttime) {
            $currenttime = time();
        }

        $timewindow = $currenttime - (24 * 60 * 60);
        $timewindow = mktime(0, 0, 0, date('n', $timewindow), date('d', $timewindow), date('Y', $timewindow));
        $nextwindow = $timewindow - (24 * 60 * 60);

        if (!$endtime) {
            $oldesttimewindowsql = <<<EOF
    SELECT min(st.timemodified) AS timea, min(sf.timemodified) AS timeb
      FROM {openstudio_contents} s
INNER JOIN {openstudio_tracking} st ON st.contentid = s.id
INNER JOIN {openstudio_flags} sf ON sf.contentid = s.id
     WHERE s.openstudioid = ?

EOF;

            $oldesttimewindowsqlresult = $DB->get_record_sql($oldesttimewindowsql, array($studioid));
            if ($oldesttimewindowsqlresult == false) {
                $endtime = $timewindow;
            } else {
                if (($oldesttimewindowsqlresult->timea <= 0) && ($oldesttimewindowsqlresult->timeb <= 0)) {
                    $endtime = $timewindow;
                } else if ($oldesttimewindowsqlresult->timea > $oldesttimewindowsqlresult->timeb) {
                    $endtime = $oldesttimewindowsqlresult->timeb;
                } else {
                    $endtime = $oldesttimewindowsqlresult->timea;
                }
            }
        }

        // Permission checks.
        $permissionresult = studio_api_stream_internal_get_slots_permission_check(
                $studioid, $groupingid, $userid, $slotownerid, $visibility,
                $filterblocks, $filtertype, $filterscope,
                $filterparticipation, $filterstatus, $filtertags,
                $sortorder,
                $pagestart, $pagesize,
                $pinboardonly, $includecount, $canmanagecontent,
                $groupid, $groupmode, $activitymode,
                $canaccessallgroup, $incollectionmode,
            $slotreciprocalaccess, $tutorroles);
        $permissionsql = $permissionresult->sql;
        $permissionparam = $permissionresult->params;
        $permissionfromsql = $permissionresult->fromsql;

        // Get filter SQL and params.
        $filterresult = studio_api_stream_internal_get_slots_filters(
                $studioid, $groupingid, $userid, $slotownerid, $visibility,
                $filterblocks, $filtertype, $filterscope,
                $filterparticipation, $filterstatus, $filtertags,
                $sortorder,
                $pagestart, $pagesize,
                $pinboardonly, $includecount, $canmanagecontent,
                $groupid, $groupmode, $activitymode,
                $canaccessallgroup, $incollectionmode,
                $slotreciprocalaccess, $tutorroles);
        $filterblockssql = $filterresult->filterblockssql;
        $filtertypesql = $filterresult->filtertypesql;
        $filtertagsql = $filterresult->filtertagsql;
        $filterflagsql = $filterresult->filterflagsql;
        $filterstatussql = $filterresult->filterstatussql;
        $incollectionmodesql = $filterresult->incollectionmodesql;
        $reciprocalactivityslotsql = $filterresult->reciprocalactivityslotsql;
        $filterparams = $filterresult->params;

        if ($visibility == content::VISIBILITY_PRIVATE) {
            $filterstudioworksql = <<<EOF
 INNER JOIN {openstudio_level3} l3 ON l3.id = s.levelid AND l3.status = 0
 INNER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
 INNER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.studioid = ?

EOF;

        } else {
            $filterstudioworksql = <<<EOF
 LEFT JOIN {openstudio_level3} l3 ON l3.id = s.levelid AND l3.status = 0
 LEFT JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
 LEFT JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.studioid = ?

EOF;

        }

        /*
         * NOTE:
         * To make the construction of the where clauses predicatable,
         * a dummy statement of WHERE 1 = 1 has been added to the SQL below
         * so all dynamically constructed where clauses can begin with
         * "AND ....".
         *
         */

        // Construct the main SQL.
        $sql = <<<EOF
    SELECT sa.*,
           s.insetonly,
           s.id AS sid,
           s.openstudioid AS sstudioid,
           s.levelid AS slevelid,
           s.levelcontainer AS slevelcontainer,
           s.contenttype AS scontenttype,
           s.mimetype AS smimetype,
           s.content AS scontent,
           s.fileid AS sfileid,
           s.thumbnail AS sthumbnail,
           s.urltitle AS surltitle,
           s.name AS sname,
           s.description AS sdescription,
           s.textformat AS stextformat,
           s.commentformat AS scommentformat,
           s.ownership AS sownership,
           s.ownershipdetail AS sownershipdetail,
           s.showextradata AS sshowextradata,
           s.visibility AS svisibility,
           s.userid AS suserid,
           s.deletedby AS sdeletedby,
           s.deletedtime AS sdeletedtime,
           s.timemodified AS stimemodified,
           s.timeflagged AS stimeflagged,
           s.locktype AS slocktype,
           s.lockedby AS slockedby,
           s.lockedtime AS slockedtime,
           s.lockprocessed AS slockprocessed,
           u.firstname AS sfirstname,
           u.lastname AS slastname,
           l3.id AS l3id, l2.id AS l2id, l1.id AS l1id,
           l3.name AS l3name, l2.name AS l2name, l1.name AS l1name,
           l3.required AS l3required, l3.contenttype AS l3contenttype,
           sread.timemodified AS sreadtime
      FROM (
                (
                    SELECT 0 AS insetonly,
                           ss.id,
                           ss.openstudioid,
                           ss.levelid,
                           ss.levelcontainer,
                           ss.contenttype,
                           ss.mimetype,
                           ss.content,
                           ss.fileid,
                           ss.thumbnail,
                           ss.urltitle,
                           ss.name,
                           ss.description,
                           ss.textformat,
                           ss.commentformat,
                           ss.ownership,
                           ss.ownershipdetail,
                           ss.showextradata,
                           ss.visibility,
                           ss.userid,
                           ss.deletedby,
                           ss.deletedtime,
                           ss.timemodified,
                           ss.timeflagged,
                           ss.locktype,
                           ss.lockedby,
                           ss.lockedtime,
                           ss.lockprocessed
                      FROM {openstudio_contents} ss
                     WHERE ss.openstudioid = ?
                )
                UNION
                (
                    SELECT sset.id AS insetonly,
                           sslot.id,
                           sset.openstudioid,
                           sset.levelid,
                           sset.levelcontainer,
                           sslot.contenttype,
                           sslot.mimetype,
                           sslot.content,
                           sslot.fileid,
                           sslot.thumbnail,
                           sslot.urltitle,
                           sslot.name,
                           sslot.description,
                           sslot.textformat,
                           sslot.commentformat,
                           sslot.ownership,
                           sslot.ownershipdetail,
                           sslot.showextradata,
                           sset.visibility,
                           sset.userid,
                           sslot.deletedby,
                           sslot.deletedtime,
                           sslot.timemodified,
                           sslot.timeflagged,
                           sslot.locktype,
                           sslot.lockedby,
                           sslot.lockedtime,
                           sslot.lockprocessed
                      FROM {openstudio_folder_contents} sss
                INNER JOIN {openstudio_contents} sslot ON sslot.id = sss.contentid
                INNER JOIN {openstudio_contents} sset ON sset.id = sss.setid
                     WHERE sslot.openstudioid = ?
                       AND sset.openstudioid = ?
                )
           ) s
INNER JOIN (
               (
                   SELECT 'tracking' AS activitytype,
                           st.id AS rid,
                           st.contentid,
                           st.actionid AS activityid,
                           st.timemodified,
                           st.userid AS actioner,
                           st.setid,
                           stu.firstname,
                           stu.lastname
                      FROM {openstudio_tracking} st
                INNER JOIN {openstudio_contents} stslots ON stslots.id = st.contentid AND stslots.openstudioid = ?
                INNER JOIN {user} stu ON stu.id = st.userid
                     WHERE st.actionid in (?, ?, ?, ?, ?, ?, ?, ?, ?)
                       AND st.timemodified <= ? AND st.timemodified > ?
               )
               UNION
               (
                    SELECT 'flag' AS activitytype,
                           sf.id AS rid,
                           sf.contentid,
                           sf.flagid AS activityid,
                           sf.timemodified,
                           sf.userid AS actioner,
                           sf.setid,
                           sfu.firstname,
                           sfu.lastname
                      FROM {openstudio_flags} sf
                INNER JOIN {openstudio_contents} sfslots ON sfslots.id = sf.contentid AND sfslots.openstudioid = ?
                INNER JOIN {user} sfu ON sfu.id = sf.userid
                     WHERE sf.flagid in (?, ?, ?, ?, ?, ?)
                       AND sf.timemodified <= ? AND sf.timemodified > ?
               )
           ) sa ON sa.contentid = s.id
INNER JOIN {user} u ON u.id = s.userid

{$filterstudioworksql}

 LEFT JOIN {openstudio_flags} sread ON sread.contentid = s.id AND sread.flagid = ? AND sread.userid = ?
     WHERE 1 = 1

{$permissionfromsql}

{$permissionsql}

{$filterblockssql}

{$filtertypesql}

{$filtertagsql}

{$filterflagsql}

{$filterstatussql}

{$incollectionmodesql}

{$reciprocalactivityslotsql}

       AND (s.openstudioid IS NULL OR s.openstudioid = ?)

  ORDER BY sa.timemodified DESC

EOF;

        $slottorepotparams1 = array(
                tracking::CREATE_CONTENT,
                tracking::UPDATE_CONTENT,
                tracking::UPDATE_CONTENT_VISIBILITY_GROUP,
                tracking::UPDATE_CONTENT_VISIBILITY_TUTOR,
                tracking::ARCHIVE_CONTENT,
                tracking::COPY_CONTENT,
                tracking::ADD_CONTENT_TO_FOLDER,
                tracking::LINK_CONTENT_TO_FOLDER,
                tracking::COPY_CONTENT_TO_FOLDER,
                $currenttime,
                $timewindow
        );
        $slottorepotparams2 = array(
                flags::FAVOURITE,
                flags::NEEDHELP,
                flags::MADEMELAUGH,
                flags::INSPIREDME,
                flags::COMMENT,
                flags::COMMENT_LIKE,
                $currenttime,
                $timewindow
        );
        $slottorepotparams3 = array($studioid);
        $slottorepotparams4 = array(flags::READ_CONTENT, $userid);
        $slottorepotparams = array_merge(
                $slottorepotparams3,
                $slottorepotparams3,
                $slottorepotparams3,
                $slottorepotparams3,
                $slottorepotparams1,
                $slottorepotparams3,
                $slottorepotparams2,
                $slottorepotparams3,
                $slottorepotparams4,
                $permissionparam,
                $filterparams,
                $slottorepotparams3);

        $results = $DB->get_recordset_sql($sql, $slottorepotparams);
        if (!$results->valid()) {
            if ($timewindow > $endtime) {
                return studio_api_stream_get_slot_activities(
                        $nextwindow, false,
                        $studioid, $groupingid, $userid, $slotownerid, $visibility,
                        $filterblocks, $filtertype, $filterscope,
                        $filterparticipation, $filterstatus, $filtertags,
                        $sortorder, $pagestart, $pagesize,
                        $pinboardonly, $includecount, $canmanagecontent,
                        $groupid, $groupmode, $activitymode,
                        $canaccessallgroup, $incollectionmode,
                        $slotreciprocalaccess, $tutorroles
                );
            }

            return false;
        }

        $activities = array();
        $slots = array();
        $slotstofetch = array();
        $slotsduplicates = array();
        foreach ($results as $result) {
            if (($result->activitytype == 'tracking')
                    && ($result->setid > 0) && ($result->activityid == 1)
                    && ($result->slotid != $result->setid )) {
                // We filter out this activity.
            } else if ($result->activitytype == 'tracking') {
                $slotdupecheck = $result->activitytype . ':' . $result->slotid
                         . ':' . $result->activityid  . ':' . $result->timemodified
                         . ':' . $result->actioner. ':' . $result->setid;
                if (array_key_exists($slotdupecheck, $slotsduplicates)) {
                    continue;
                } else {
                    $slotsduplicates[$slotdupecheck] = $slotdupecheck;
                    $activities[] = $result;
                }
            } else {
                $activities[] = $result;
            }

            $slotid = (int) $result->slotid;
            if (!array_key_exists($slotid, $slots)) {
                $slots[$slotid] = (object) array(
                        'id' => $result->sid,
                        'insetonly' => $result->insetonly,
                        'studioid' => $result->sstudioid,
                        'levelid' => $result->slevelid,
                        'levelcontainer' => $result->slevelcontainer,
                        'contenttype' => $result->scontenttype,
                        'mimetype' => $result->smimetype,
                        'content' => $result->scontent,
                        'fileid' => $result->sfileid,
                        'thumbnail' => $result->sthumbnail,
                        'urltitle' => $result->surltitle,
                        'name' => $result->sname,
                        'description' => $result->sdescription,
                        'textformat' => $result->stextformat,
                        'commentformat' => $result->scommentformat,
                        'ownership' => $result->sownership,
                        'ownershipdetail' => $result->sownershipdetail,
                        'showextradata' => $result->sshowextradata,
                        'visibility' => $result->svisibility,
                        'userid' => $result->suserid,
                        'deletedby' => $result->sdeletedby,
                        'deletedtime' => $result->sdeletedtime,
                        'timemodified' => $result->stimemodified,
                        'timeflagged' => $result->stimeflagged,
                        'locktype' => $result->slocktype,
                        'lockedby' => $result->slockedby,
                        'lockedtime' => $result->slockedtime,
                        'lockprocessed' => $result->slockprocessed,
                        'firstname' => $result->firstname,
                        'lastname' => $result->lastname,
                        'sfirstname' => $result->sfirstname,
                        'slastname' => $result->slastname,
                        'l1name' => $result->l1name,
                        'l2name' => $result->l2name,
                        'l3name' => $result->l3name,
                        'readtime' => $result->sreadtime,
                );
            }

            $setid = (int) $result->setid;
            if (($setid <= 0) && ($result->insetonly > 0)) {
                $result->setid = $setid = (int) $result->insetonly;
            }
            if (($setid > 0) && !array_key_exists($setid, $slots) && !array_key_exists($setid, $slotstofetch)) {
                $slotstofetch[$setid] = $setid;
            }
        }

        $slotidlists = array();
        foreach ($slotstofetch as $setid) {
            if (!array_key_exists($setid, $slots)) {
                $slotidlists[] = $setid;
            }
        }

        if (!empty($slotidlists)) {
            list($filterslotdatasql, $filterslotdataparams) = $DB->get_in_or_equal($slotidlists);
            $slotdatasql = <<<EOF
    SELECT 0 AS insetonly,
           ss.*,
           su.firstname AS firstname,
           su.lastname AS lastname,
           su.firstname AS sfirstname,
           su.lastname AS slastname,
           s1.name AS l1name,
           s2.name AS l2name,
           s3.name AS l3name,
           sread.timemodified AS readtime
      FROM {openstudio_contents} ss
INNER JOIN {user} su ON su.id = ss.userid
 LEFT JOIN {openstudio_level3} s3 ON s3.id = ss.levelid AND ss.levelcontainer = 3
 LEFT JOIN {openstudio_level2} s2 ON s2.id = s3.level2id
 LEFT JOIN {openstudio_level1} s1 ON s1.id = s2.level1id
 LEFT JOIN {openstudio_flags} sread ON sread.contentid = ss.id AND sread.flagid = ? AND sread.userid = ?
     WHERE ss.id {$filterslotdatasql}

EOF;

            $filterslotdataparams = array_merge(
                    array(flags::READ_CONTENT, $userid),
                    $filterslotdataparams);
            $results = $DB->get_recordset_sql($slotdatasql,  $filterslotdataparams);
            if (!$results->valid()) {
                return false;
            }

            foreach ($results as $result) {
                $slotid = (int) $result->id;
                $slots[$slotid] = $result;
            }
        }

        $returndata = (object) array(
            'activities' => $activities,
            'slots' => $slots,
            'currenttime' => $currenttime,
            'timewindow' => $timewindow,
            'endtime' => $endtime
        );
    } catch (Exception $e) {
        // Default to returning false.
        $returndata = false;
    }

    return $returndata;
}
