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

use mod_openstudio\local\api\comments;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

/**
 * Get user record data based on user id.
 *
 * @param int $userid user id to fetch data for.
 * @return object return user data.
 */
function studio_api_user_get_user_by_id($userid) {
    global $DB;

    return $DB->get_record('user', array('id' => $userid));
}

/**
 * Calculates and return the user progress in using the specified studio instance.
 *
 * @param int $studioid filter data to specified studio instance.
 * @param int $userid user id to fetch data for.
 * @return array return array containing the user's progress.
 */
function studio_api_user_get_activity_status($studioid, $userid) {
    global $DB;

    // Caculate the user's last active date which can be determined by entries
    // in the studio_flags or studio_tracking table.
    $sql = <<<EOF
SELECT max(f.timemodified) AS fmodified
  FROM {openstudio_flags} f
 WHERE f.userid = ?

EOF;
    $fmodified = $DB->get_field_sql($sql, array($userid));
    $fmodified = false;

    $sql = <<<EOF
SELECT max(t.timemodified) AS tmodified
  FROM {openstudio_tracking} t
 WHERE t.userid = ?

EOF;
    $tmodified = $DB->get_field_sql($sql, array($userid));

    $lastactivedate = false;
    if ((int) $fmodified > 0) {
        $lastactivedate = $fmodified;
    }
    if ((int) $tmodified > 0) {
        if ($tmodified > $lastactivedate) {
            $lastactivedate = $tmodified;
        }
    }
    if (!$lastactivedate) {
        $lastactivedate = 'unknown';
    }

    $sql = <<<EOF
         SELECT l1.id AS level1id,
                l2.id AS level2id,
                l3.id AS level3id,
                s.id,
                s.contenttype AS slotcontenttype,
                l3.contenttype AS slotcontenttype2,
                l1.name AS level1name,
                l2.name AS level2name,
                l3.name AS level3name,
                l1.sortorder AS level1sortorder
           FROM {openstudio_level3} l3
     INNER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
     INNER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.openstudioid = ?
LEFT OUTER JOIN {openstudio_contents} s ON s.levelcontainer = 3 AND s.levelid = l3.id AND s.userid = ?
          WHERE l1.status >= 0
            AND l2.status >= 0
            AND l3.status >= 0
       ORDER BY l1.sortorder ASC, l2.sortorder ASC, l3.sortorder ASC

EOF;

    // Gather user's slot usage in terms of number of activity slots that they have
    // populated and put them into a progress array.
    $countemptyslots = 0;
    $countfilledslots = 0;
    $counttotalslots = 0;

    $progressdetaildataarray = array();
    $progressdataarray = array();
    $progressdata = $DB->get_recordset_sql($sql, array($studioid, $userid));
    if ($progressdata->valid()) {
        foreach ($progressdata as $progress) {
            // Create first progressdataarray record if it doesnt already exists.
            if (!array_key_exists($progress->level1id, $progressdataarray)) {
                $progressdataarray[$progress->level1id] = (object) array(
                        'level1id' => $progress->level1id,
                        'sortorder' => $progress->level1sortorder,
                        'levelname' => $progress->level1name,
                        'countslots' => 0,
                        'countfilledslots' => 0);
            }

            // Calculate the slot counts.
            $counttotalslots++;
            $progressdataarray[$progress->level1id]->countslots++;
            if ((int) $progress->id == 0) {
                $countemptyslots++;
            } else {
                $countfilledslots++;
                $progressdataarray[$progress->level1id]->countfilledslots++;
            }

            // Populate the progressdetaildataarray.
            if (!isset($progressdetaildataarray[$progress->level1id])) {
                $progressdetaildataarray[$progress->level1id] = array();
            }
            if (!isset($progressdetaildataarray[$progress->level1id][$progress->level2id])) {
                $progressdetaildataarray[$progress->level1id][$progress->level2id] = array();
            }
            $progressdetaildataarray[$progress->level1id][$progress->level2id][$progress->level3id] = $progress;
        }
    }

    $totalpostedcomments = comments::total_for_user($studioid, $userid);

    // Calculate smiley mood: happy or sad.
    //
    // Note from Jane Bromley:
    // I'm not sure if people would recommend re-use of the algorithm below: it's a bit simple, the students
    // can find it a bit confusing, and it goes straight from unhappy to happy with no in between.
    // But anyway: it works out the ratio of the number of comments the student has made on photos against
    // the number of photos the student has posted. The student's participation is a happy face if
    // the number_of_comments/number_of_photos >= 1.
    if ($countfilledslots > 0) {
        $totalpostedcomments2 = comments::total_for_user($studioid, $userid, true);
        $participationlevel = $totalpostedcomments2 / $countfilledslots;
    } else {
        $participationlevel = 0;
    }
    if ($participationlevel >= 1) {
        $participationstatus = 'high';
    } else {
        $participationstatus = 'low';
    }

    return array('lastactivedate' => $lastactivedate,
                 'emptyslots' => $countemptyslots,
                 'filledslots' => $countfilledslots,
                 'totalslots' => $counttotalslots,
                 'totalpostedcomments' => $totalpostedcomments,
                 'participationlevel' => $participationlevel,
                 'participationstatus' => $participationstatus,
                 'progress' => $progressdataarray,
                 'progressdetail' => $progressdetaildataarray);
}

/**
 * Get all users who are active in the studio module, excluding the logged in user.
 *
 * Result can be filtered and sorted by:
 * 1) User activity date
 * 2) User who has asked for help
 * 3) User name
 *
 * For users to be listed, they must have intereacte with the Studio instance.
 *
 * @param int $studioid filter people by involvement in studio instance.
 * @param int $groupmode Group mode  to restrict to if necessary.
 * @param int $groupingid Group grouping id to restrict to if necessary.
 * @param int $groupid Group id to restrict to if necessary.
 * @param int $filtertype filter by module or group.
 * @param int $sorttype sort type.
 * @param int $sortorder sort order (ascending or descending).
 * @param int $pagestart Result pagination start position
 * @param int $pagesize Result page size.
 * @param int $includecount True to include result count for the request.
 * @return object return SQL recordset or false if error encounterd.
 */
function studio_api_user_get_all(
        $studioid,
        $groupmode,
        $groupingid,
        $groupid = 0,
        $filtertype = STUDIO_FILTER_PEOPLE_MODULE,
        $sorttype = STUDIO_SORT_PEOPLE_ACTIVTY,
        $sortorder = STUDIO_SORT_DESC,
        $pagestart = 0,
        $pagesize = 0,
        $includecount = false) {

    global $DB, $USER;

    $sqlparams = array();

    $limitfrom = 0;
    $limitnum = 0;
    if (($pagestart >= 0) && ($pagesize > 0)) {
        $limitfrom = $pagestart * $pagesize;
        $limitnum = $pagesize;
    }

    // This SQL gets the list of people who have created slots and also get the dates when they last flagged on slots.
    $sql = <<<EOF
SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email,
                u.picture, u.url, u.description, u.imagealt,
                u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                max(s.timemodified) AS slottimemodified,
                max(f.timemodified) AS flagtimemodified
           FROM {openstudio_contents} s
     INNER JOIN {user} u ON u.id = s.userid
LEFT OUTER JOIN {openstudio_flags} f ON f.userid = u.id AND f.contentid = s.id
          WHERE s.openstudioid = ?
            AND u.id != ?

EOF;

    $sqlparams[] = $studioid;
    $sqlparams[] = $USER->id;

    // Filter people by group membership.
    $sqlgroup = '';
    if ($filtertype == STUDIO_FILTER_PEOPLE_GROUP) {
        if ($groupid > 0) {
            if ($groupmode == 2) {
                $sqlgroup = <<<EOF
AND EXISTS (    SELECT 1
                  FROM {groups_members} gm1
                  JOIN {groupings_groups} gg ON gg.groupid = gm1.groupid
                 WHERE gm1.groupid = ?
                   AND gm1.userid = u.id
                   AND gg.groupingid = ?)
EOF;

                $sqlparams[] = $groupid;
                $sqlparams[] = $groupingid;
            } else {
                $sqlgroup = <<<EOF
AND EXISTS (    SELECT 1
                  FROM {groups_members} gm1
                  JOIN {groupings_groups} gg ON gg.groupid = gm1.groupid
                  JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ?
                 WHERE gm1.groupid = ?
                   AND gm1.userid = u.id
                   AND gg.groupingid = ?)
EOF;

                $sqlparams[] = $USER->id;
                $sqlparams[] = $groupid;
                $sqlparams[] = $groupingid;
            }
        } else {
            if ($groupmode == 2) {
                $sqlgroup = <<<EOF
AND EXISTS (    SELECT 1
                  FROM {groups_members} gm1
                  JOIN {groupings_groups} gg ON gg.groupid = gm1.groupid
                 WHERE gm1.userid = u.id
                   AND gg.groupingid = ?)
EOF;

                $sqlparams[] = $groupingid;
            } else {
                $sqlgroup = <<<EOF
AND EXISTS (    SELECT 1
                  FROM {groups_members} gm1
                  JOIN {groupings_groups} gg ON gg.groupid = gm1.groupid
                  JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ?
                 WHERE gm1.userid = u.id
                   AND gg.groupingid = ?)
EOF;

                $sqlparams[] = $USER->id;
                $sqlparams[] = $groupingid;
            }
        }
    }
    $sql .= " {$sqlgroup}";

    // Filter people by those asking for help.
    $sqlhelpneeded = '';
    if ($sorttype == STUDIO_SORT_PEOPLE_ASKINGFORHELP) {
        $sqlhelpneeded = <<<EOF
AND f.flagid = ?
EOF;

        $sqlparams[] = STUDIO_PARTICPATION_FLAG_NEEDHELP;
    }
    $sql .= " {$sqlhelpneeded}";

    $sql .= " GROUP BY u.id, u.username, u.firstname, u.lastname, u.email, u.picture, u.url,";
    $sql .= " u.description, u.imagealt, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename";

    // Sort the result based on requested ordering.
    $sqlsort = '';
    switch ($sorttype) {
        case STUDIO_SORT_PEOPLE_NAME:
            if ($sortorder == STUDIO_SORT_ASC) {
                $sqlsort = ' ORDER BY u.lastname ASC, u.firstname ASC';
            } else {
                $sqlsort = ' ORDER BY u.lastname DESC, u.firstname DESC';
            }
            break;

        case STUDIO_SORT_PEOPLE_ASKINGFORHELP:
            if ($sortorder == STUDIO_SORT_ASC) {
                $sqlsort = ' ORDER BY flagtimemodified ASC NULLS FIRST,
                                      slottimemodified ASC NULLS FIRST';
            } else {
                $sqlsort = ' ORDER BY flagtimemodified DESC NULLS LAST,
                                      slottimemodified DESC NULLS LAST';
            }
            break;

        case STUDIO_SORT_PEOPLE_ACTIVTY:
        default:
            if ($sortorder == STUDIO_SORT_ASC) {
                $sqlsort = ' ORDER BY flagtimemodified ASC NULLS FIRST,
                                      slottimemodified ASC NULLS FIRST';
            } else {
                $sqlsort = ' ORDER BY flagtimemodified DESC NULLS LAST,
                                      slottimemodified DESC NULLS LAST';
            }
            break;
    }
    $sql .= " {$sqlsort}";

    if ($includecount) {
        $sqlcount = "SELECT count(*) FROM ({$sql}) people";
        $resultcount = $DB->count_records_sql($sqlcount, $sqlparams);
    }

    $userdata = $DB->get_recordset_sql($sql, $sqlparams, $limitfrom, $limitnum);
    if ($userdata->valid()) {
        if ($includecount) {
            return (object) array('people' => $userdata, 'total' => $resultcount);
        } else {
            return $userdata;
        }
    }

    return false;
}
