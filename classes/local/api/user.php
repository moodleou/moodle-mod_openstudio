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
 * Openstudio User API
 *
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

/**
 * User API functions
 *
 * This code has been only minimally refactored from openstudio V1, so may still use terms like "slot".
 */
class user {

    /**
     * Get user record data based on user id.
     *
     * @param int $userid user id to fetch data for.
     * @return object return user data.
     */
    public static function get_user_by_id($userid) {
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
    public static function get_activity_status($studioid, $userid) {
        global $DB;

        // Caculate the user's last active date which can be determined by entries
        // in the studio_flags or studio_tracking table.
        $sql = <<<EOF
SELECT max(f.timemodified) AS fmodified
  FROM {openstudio_flags} f
 WHERE f.userid = ?

EOF;
        $fmodified = $DB->get_field_sql($sql, array($userid));

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
                s.id, s.deletedby, s.userid, s.levelcontainer, 
                s.visibility, s.levelid, s.openstudioid,
                s.showextradata AS showextradata,
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
                // Use showextradata field to indicate that this is an auto-generated folder.
                if ((int) $progress->id == 0 || ($progress->showextradata && $progress->slotcontenttype == content::TYPE_FOLDER)) {
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
     * @return object/array with users or false if error encounterd.
     */
    public static function get_all(
            $studioid,
            $groupmode,
            $groupingid,
            $groupid = 0,
            $filtertype = stream::FILTER_PEOPLE_MODULE,
            $sorttype = stream::SORT_PEOPLE_ACTIVTY,
            $sortorder = stream::SORT_DESC,
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
                u.picture, u.description, u.imagealt,
                u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                (SELECT max(f.timemodified)
                FROM {openstudio_flags} f 
            	JOIN {openstudio_contents} fc ON fc.id = f.contentid AND fc.openstudioid = ?
                WHERE f.userid = u.id) flagtimemodified,
                (SELECT max(sc.timemodified)
                FROM {openstudio_contents} sc 
                WHERE sc.userid = u.id
                AND sc.openstudioid = ?) slottimemodified
           FROM {openstudio_contents} s
     INNER JOIN {user} u ON u.id = s.userid
          WHERE s.openstudioid = ?
            AND u.id != ?
EOF;

        $sqlparams[] = $studioid;
        $sqlparams[] = $studioid;
        $sqlparams[] = $studioid;
        $sqlparams[] = $USER->id;

        // Filter people by group membership.
        $sqlgroup = '';
        if ($filtertype == stream::FILTER_PEOPLE_GROUP) {
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
        if ($sorttype == stream::SORT_PEOPLE_ASKINGFORHELP) {
            $sqlhelpneeded = <<<EOF
AND f.flagid = ?
EOF;

            $sqlparams[] = flags::NEEDHELP;
        }
        $sql .= " {$sqlhelpneeded}";

        $sql .= " GROUP BY u.id, u.username, u.firstname, u.lastname, u.email, u.picture,";
        $sql .= " u.description, u.imagealt, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename";

        // Sort the result based on requested ordering.
        $sqlsort = '';
        switch ($sorttype) {
            case stream::SORT_PEOPLE_NAME:
                if ($sortorder == stream::SORT_ASC) {
                    $sqlsort = ' ORDER BY u.lastname ASC, u.firstname ASC';
                } else {
                    $sqlsort = ' ORDER BY u.lastname DESC, u.firstname DESC';
                }
                break;

            case stream::SORT_PEOPLE_ASKINGFORHELP:
                if ($sortorder == stream::SORT_ASC) {
                    $sqlsort = ' ORDER BY flagtimemodified ASC,
                                      slottimemodified ASC';
                } else {
                    $sqlsort = ' ORDER BY flagtimemodified DESC,
                                      slottimemodified DESC';
                }
                break;

            case stream::SORT_PEOPLE_ACTIVTY:
            default:
                if ($sortorder == stream::SORT_ASC) {
                    $sqlsort = ' ORDER BY flagtimemodified ASC,
                                      slottimemodified ASC';
                } else {
                    $sqlsort = ' ORDER BY flagtimemodified DESC,
                                      slottimemodified DESC';
                }
                break;
        }
        $sql .= " {$sqlsort}";

        if ($includecount) {
            $sqlcount = "SELECT count(*) FROM ({$sql}) people";
            $resultcount = $DB->count_records_sql($sqlcount, $sqlparams);
        }

        $users = $DB->get_records_sql($sql, $sqlparams, $limitfrom, $limitnum);
        if (!empty($users)) {
            if ($includecount) {
                return (object) array('people' => $users, 'total' => $resultcount);
            } else {
                return $users;
            }
        }

        return false;
    }

    /**
     * Calculates and return all user progress in using the specified studio instance.
     *
     * @param int $studioid filter data to specified studio instance.
     * @param int $userids user ids to fetch data for.
     * @return array return array containing the user's progress.
     */
    public static function get_all_users_activity_status($studioid, $userids) {
        global $DB;

        if (empty($userids)) {
            return [];
        }

        // Return user id sql and user id array.
        list($useridsql, $useridparams) = $DB->get_in_or_equal($userids);

        $usersactivitydata = [];

        // Caculate the user's last active date which can be determined by entries
        // in the studio_flags or studio_tracking table.
        // Count of all comments posted by a user for a given studio.
        $sql = <<<EOF
  SELECT s.userid,
         (SELECT max(f.timemodified) 
            FROM {openstudio_flags} f
            JOIN {openstudio_contents} fc ON fc.id = f.contentid AND fc.openstudioid = ?
           WHERE f.userid = s.userid) fmodified,
         (SELECT max(t.timemodified) 
            FROM {openstudio_tracking} t
            JOIN {openstudio_contents} tc ON tc.id = t.contentid AND tc.openstudioid = ?
           WHERE t.userid = s.userid) tmodified,
         (SELECT count(c.id) 
            FROM {openstudio_comments} c
            JOIN {openstudio_contents} cc ON cc.id = c.contentid AND cc.openstudioid = ?
           WHERE c.userid = s.userid AND c.deletedby IS NULL) AS totalpostedcomments,
         (SELECT count(oc.id) 
            FROM {openstudio_comments} oc
            JOIN {openstudio_contents} occ ON occ.id = oc.contentid AND occ.openstudioid = ?
           WHERE oc.userid != s.userid AND oc.deletedby IS NULL) AS totalpostedcommentsexcludeown
    FROM {openstudio_contents} s
   WHERE s.openstudioid = ?
     AND s.userid {$useridsql}
GROUP BY s.userid
EOF;

        $results = $DB->get_recordset_sql($sql,
                array_merge([$studioid, $studioid, $studioid, $studioid, $studioid], $useridparams));
        foreach ($results as $result) {
            $lastactivedate = false;

            if ((int) $result->fmodified > 0) {
                $lastactivedate = $result->fmodified;
            }

            if ((int) $result->tmodified > 0) {
                if ($result->tmodified > $lastactivedate) {
                    $lastactivedate = $result->tmodified;
                }
            }
            if (!$lastactivedate) {
                $lastactivedate = 'unknown';
            }

            $activitydata = [
                    'totalpostedcomments' => $result->totalpostedcomments,
                    'totalpostedcommentsexcludeown' => $result->totalpostedcommentsexcludeown,
                    'lastactivedate' => $lastactivedate
            ];
            $usersactivitydata[$result->userid] = $activitydata;
        }
        $results->close();

        // Gather user's content usage in terms of number of activity contents that they have
        // populated and put them into a progress array.
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
                l1.sortorder AS level1sortorder,
                s.userid
           FROM {openstudio_level3} l3
     INNER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
     INNER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.openstudioid = ?
LEFT OUTER JOIN {openstudio_contents} s ON s.levelcontainer = 3 AND s.levelid = l3.id AND s.userid {$useridsql}
          WHERE l1.status >= 0
            AND l2.status >= 0
            AND l3.status >= 0
       ORDER BY l1.sortorder ASC, l2.sortorder ASC, l3.sortorder ASC

EOF;

        $results = $DB->get_recordset_sql($sql, array_merge([$studioid], $useridparams));

        $filledcontentsarray = [];
        $totalcontentsarray = [];
        foreach ($results as $result) {
            // Collect the total array by level3id.
            $totalcontentsarray[$result->level3id] = 1;

            // Calculate the filled content.
            if ((int) $result->id) {
                if (array_key_exists($result->userid, $filledcontentsarray)) {
                    $filledcontentsarray[$result->userid]++;
                } else {
                    $filledcontentsarray[$result->userid] = 1;
                }
            }
        }
        $results->close();

        $counttotalcontents = count($totalcontentsarray);

        // Calculate smiley mood: happy or sad.
        foreach ($userids as $userid) {
            $activitydata = !empty($usersactivitydata[$userid]) ? $usersactivitydata[$userid] : [];

            $filledcontents = 0;
            $participationlevel = 0;
            if (!empty($filledcontentsarray[$userid])) {
                $filledcontents = $filledcontentsarray[$userid];
                // Total comment exclude own.
                $participationlevel = $activitydata['totalpostedcommentsexcludeown'] / $filledcontentsarray[$userid];
            }

            $participationstatus = 'low';
            if ($participationlevel >= 1) {
                $participationstatus = 'high';
            }

            $extraactivitydata = [
                    'totalcontents' => $counttotalcontents,
                    'filledcontents' => $filledcontents,
                    'participationstatus' => $participationstatus
            ];

            $usersactivitydata[$userid] = array_merge($activitydata, $extraactivitydata);
        }

        return $usersactivitydata;

    }
}
