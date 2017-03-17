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
 * API functions for content streams.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

use mod_openstudio\local\util;

defined('MOODLE_INTERNAL') || die();

class stream {

    const FILTER_EMPTYCONTENT = 1;
    const FILTER_NOTREAD = 2;
    const FILTER_FAVOURITES = 3;
    const FILTER_MOSTPOPULAR = 4;
    const FILTER_HELPME = 5;
    const FILTER_MOSTSMILES = 6;
    const FILTER_MOSTINSPIRATION = 7;
    const FILTER_COMMENTS = 8;
    const FILTER_READ = 9;
    const FILTER_LOCKED = 10;
    const FILTER_TUTOR = 11;

    const FILTER_PEOPLE_GROUP = 0;
    const FILTER_PEOPLE_MODULE = 1;

    const FILTER_AREA_ALL = 0;
    const FILTER_AREA_PINBOARD = 1;
    const FILTER_AREA_ACTIVITY = 2;

    const SCOPE_EVERYONE = 1;
    const SCOPE_MY = 2;
    const SCOPE_THEIRS = 3;

    const SORT_ASC = 1;
    const SORT_DESC = 0;

    const SORT_BY_DATE = 1;
    const SORT_BY_ACTIVITYTITLE = 3;
    const SORT_BY_USERNAME = 2;
    const SORT_BY_COMMENTNUMBERS = 4;

    const SORT_PEOPLE_ACTIVTY = 1;
    const SORT_PEOPLE_NAME = 2;
    const SORT_PEOPLE_ASKINGFORHELP = 4;

    const LEVELFIELDS = 'l3.id AS l3id, l2.id AS l2id, l1.id AS l1id,
        l3.name AS l3name, l2.name AS l2name, l1.name AS l1name,
        l3.sortorder AS l3sortorder, l2.sortorder AS l2sortorder, l1.sortorder AS l1sortorder,
        l2.hidelevel AS l2hidelevel, l3.required AS l3required, l3.contenttype AS l3contenttype';

    const CONTENTFIELDS = 'u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
       u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
       s.id, s.contenttype, s.mimetype, s.content, s.thumbnail, s.urltitle,
       s.name, s.description, s.ownership, s.ownershipdetail,
       s.visibility, s.userid, s.timemodified, s.timeflagged,
       s.levelid, s.levelcontainer, s.openstudioid, s.locktype, s.lockedby, s.fileid';

    /**
     * Returns the SQL chunk and parameter to restrict results by group membership when in visible groups mode.
     *
     * @param bool $canaccessallgroup
     * @param int $groupingid
     * @param int $userid
     * @return array The SQL chunk followed by an array of parameters.
     */
    private static function visible_group_permission_sql($canaccessallgroup, $groupingid, $userid) {
        $sql = '';
        $params = [];
        if (!$canaccessallgroup) {
            // The user can only view groups if they are a member of a group in the grouping.
            $params[] = $groupingid;
            $params[] = $userid;

            $sql = <<<EOF
                        AND EXISTS (SELECT 1
                                      FROM {groups_members} gm2
                                      JOIN {groupings_groups} gg2 ON gg2.groupid = gm2.groupid AND gg2.groupingid = ?
                                     WHERE gm2.userid = ?)

EOF;
        }
        // Check the content is shared with a group the author is a member of, all groups, or module.
        $sql .= <<<EOF
                    AND (EXISTS (SELECT 1
                                   FROM {groups_members} gmocheck
                                  WHERE gmocheck.groupid = (0 - s.visibility) AND gmocheck.userid = s.userid)
                             OR s.visibility = 2
                             OR s.visibility = 3)

EOF;

        return [$sql, $params];
    }

    /**
     * Returns the SQL chunk required to check if a user can view content shared with tutor.
     *
     * This is specific to the OU's concept of a "tutor".  In short, it checks that the viewing user has one of the "tutor" roles
     * assigned in the course context that the openstudio belongs to, and that they are in a group with the user who shared the
     * content, in the grouping that the studio instance is configured to use.
     *
     * @param array $tutorroles
     * @param int $userid
     * @param int|null $groupid
     * @return array The SQL chunk, and an array of parameters.
     */
    private static function tutor_permission_sql(array $tutorroles, $userid, $groupid = null) {
        global $DB;
        $sql = '';
        $params = [];

        if (!empty($tutorroles)) {
            list($rolesql, $roleparams) = $DB->get_in_or_equal($tutorroles);
            $subquery = <<<EOF
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
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'openstudio'
                  JOIN {groupings_groups} gg3 ON gg3.groupingid = cm.groupingid AND gg3.groupid = tg1.id
                 WHERE ra.roleid {$rolesql}
                   AND ra.userid = ?
                   AND s1.id = s.id
                   AND tgm2.userid = ?
EOF;

            // The following parameters are only applicable if visibility::TUTOR is enabled.
            // This is specific to the OU's concept of a "tutor".
            $params[] = content::VISIBILITY_TUTOR;
            $params = array_merge($params, $roleparams);
            $params[] = $userid;
            $params[] = $userid;
            if (!is_null($groupid)) {
                $subquery .= ' AND tg1.id = ?';
                $params[] = $groupid;
            }
            $sql = <<<EOF
                OR (
                    s.visibility = ?
                    AND EXISTS (
                        {$subquery}
                    )
                )
EOF;
        }
        return [$sql, $params];
    }

    /**
     * Returns the SQL chunks and parameters to restrict results based on user permissions.
     *
     * Returns an object with the following properties:
     * $sql - The main SQL chunk to append to the WHERE clause.
     * $fromsql - An extra SQL chunk to append to the FROM clause.
     * $params - An array of parameters required by $sql.
     *
     * If viewing the Pinboard, restrict results to those owned by $userid.
     * If viewing the Group stream,
     *   If visible group mode,
     *     If viewing one group, restrict results to non-private content posted by members of that group.
     *       If the user cannot view all groups, also check that they are in a group in the grouping.
     *       If a piece of content is restricted to tutors, check that the user is a tutor and in the same group as the author.
     *     If not viewing a particular group, restrict results to non-private content posted by members of a group in the grouping.
     *       If the user cannot view all groups, also check that they are in a group in the grouping.
     *       If a piece of content is restricted to tutors, check that the user is a tutor in the group the content is shared with.
     *   If not in visible group mode (i.e. in separate group mode, otherwise the group stream would be disabled).
     *     If viewing one group, restrict to non-private content posted by members of that group.
     *       If the user cannot view all groups, also check that they are a member of that group.
     *       If a piece of content is restricted to tutors, check that the user is a tutor in the group the content is shared with.
     *     If not viewing a particlar group, restrict results to non-private content posted by members of groups.
     *       If the user cannot view all groups, also check that they are a member of the group the content is shared with.
     *       If a piece of content is restricted to tutors, check that the user is a tutor and in the same group as the author.
     * If viewing the Module stream,
     *   If the user cannot manage content, check that they are enrolled on the course,
     *   Restrict results to content shared with the module.
     * If viewing the Activities stream,
     *   If the user can manage content, just restrict to the content owned by the user we are viewing.
     *   If the use cannot manage content,
     *     If the user can access all groups, restrict to content shared with any group (in Visible Groups mode),
     *         a single group or the module.
     *     If the user cannot access all groups, restrict to content shared as above with members of the same groups as them.
     *
     * @param int $studioid The ID of the studio instance.
     * @param int $groupingid The ID of the grouping configured for the studio.
     * @param int $userid The ID of the user viewing content.
     * @param int $contentownerid The ID of the user who's stream we are viewing, if not our own.
     * @param int $visibility content::VISIBILITY_* constant respresenting the stream we are viewing.
     * @param bool $pinboardonly Only show pinboard (private) content?
     * @param bool $canmanagecontent Does the use have openstudio:managecontent?
     * @param int $groupid The ID of the group stream we are viewing (if any)
     * @param int $groupmode The group mode configured for the studio instance.
     * @param bool $canaccessallgroup Does the user have site:accessallgroups?
     * @param array $tutorroles The IDs of the tutor roles configured for the studio instance.
     * @return object {$sql: '', $fromsql: '', $params: []}
     */
    private static function get_permission_sql(
            $studioid, $groupingid, $userid, $contentownerid, $visibility, $pinboardonly = false, $canmanagecontent = false,
            $groupid = 0, $groupmode = 0, $canaccessallgroup = false, $tutorroles = array()) {

        $permissionsql = '';
        $fromsql = '';
        $params = array();

        // Permission checks.
        switch ($visibility) {
            case content::VISIBILITY_PRIVATE:
            case content::VISIBILITY_PRIVATE_PINBOARD:
                // Find all content that belong to the $userid.
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
                $courseid = util::get_courseid_from_studioid($studioid);

                // If we cant find the course id, which shouldnt be possible anyway, then
                // set courseid to zero so that any permission check using it will fail anyway.
                if ($courseid == false) {
                    $courseid = 0;
                }

                if (($groupmode == VISIBLEGROUPS) && ($groupid > 0)) {
                    // If all group visible, and a specific group id has been requested...

                    $params[] = content::TYPE_NONE;
                    $params[] = content::VISIBILITY_MODULE;
                    $params[] = content::VISIBILITY_GROUP;
                    $params[] = $groupid;
                    $params[] = $groupingid;
                    $params[] = $groupid;

                    list($grouppermissionchecksql, $grouparams) = self::visible_group_permission_sql(
                            $canaccessallgroup, $groupingid, $userid);
                    $params = array_merge($params, $grouparams);

                    list($tutorpermissionsql, $tutorparams) = self::tutor_permission_sql($tutorroles, $userid, $groupid);
                    $params = array_merge($params, $tutorparams);

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

                } else if (($groupmode == VISIBLEGROUPS) && ($groupid <= 0)) {
                    // If all group visible, and no specific group id has been requested...

                    $params[] = content::TYPE_NONE;
                    $params[] = content::VISIBILITY_MODULE;
                    $params[] = content::VISIBILITY_GROUP;
                    $params[] = $groupingid;

                    list($grouppermissionchecksql, $grouparams) = self::visible_group_permission_sql(
                            $canaccessallgroup, $groupingid, $userid);
                    foreach ($grouparams as $param) {
                        $params[] = $param;
                    }

                    list($tutorpermissionsql, $tutorparams) = self::tutor_permission_sql($tutorroles, $userid);
                    $params = array_merge($params, $tutorparams);

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

                } else if (($groupmode != VISIBLEGROUPS) && $groupid > 0) {
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

                    list($tutorpermissionsql, $tutorparams) = self::tutor_permission_sql($tutorroles, $userid, $groupid);
                    $params = array_merge($params, $tutorparams);

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

                    list($tutorpermissionsql, $tutorparams) = self::tutor_permission_sql($tutorroles, $userid, $groupid);
                    $params = array_merge($params, $tutorparams);

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
                    $courseid = util::get_courseid_from_studioid($studioid);
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
                $params[] = $contentownerid;

                // If the user does not have managecontent capability then we
                // restrict the content based on permissions.
                if (!$canmanagecontent) {
                    // Get course id from studioid.
                    $courseid = util::get_courseid_from_studioid($studioid);
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

    /**
     * Process a list of level 1 IDs into SQL and paramters.
     *
     * @param array|int $filterblocks Array of level 1 IDs, or -1.
     * @return array SQL condition followed by array of parameters.
     */
    private static function block_filter_sql($filterblocks) {
        global $DB;

        // Filter by block level.
        $sql = '';
        $params = [];

        if (is_array($filterblocks)) {
            // Defensive check to make sure the parameters are what we
            // expect, which is an array of numbers.
            $filterblockschecked = array();
            foreach ($filterblocks as $value) {
                if (trim($value) != '') {
                    $filterblockschecked[] = (int) $value;
                }
            }
            if (!empty($filterblockschecked)) {
                list($insql, $params) = $DB->get_in_or_equal($filterblockschecked);
                $sql = 'AND l1.id ' . $insql;
            }
        } else if ($filterblocks === -1) {
            $sql = 'AND s.levelid = 0 ';
        }
        return [$sql, $params];
    }

    /**
     * Process a list of content types into SQL and parameters.
     *
     * Each top-level type includes a number of subtypes, so we filter by any of the subtypes
     *
     * @param string $filtertype Comma-separated list of content types (content::TYPE_* constants).
     * @return array The SQL clause to filter by type, and array of parameters.
     */
    private static function type_filter_sql($filtertype) {
        global $DB;

        // Filter by slot content type.
        $sql = '';
        $params = [];
        $filtertypearray = explode(',', $filtertype);
        $subtypes = [];
        foreach ($filtertypearray as $filtertypearrayitem) {
            $filtertypearrayitem = trim($filtertypearrayitem);
            if ($filtertypearrayitem !== '') {
                switch ($filtertypearrayitem) {
                    case content::TYPE_IMAGE:
                        $itemsubtypes = [content::TYPE_IMAGE, content::TYPE_IMAGE_EMBED, content::TYPE_URL_IMAGE];
                        break;

                    case content::TYPE_VIDEO:
                        $itemsubtypes = [content::TYPE_VIDEO, content::TYPE_VIDEO_EMBED, content::TYPE_URL_VIDEO];
                        break;

                    case content::TYPE_AUDIO:
                        $itemsubtypes = [content::TYPE_AUDIO, content::TYPE_AUDIO_EMBED, content::TYPE_URL_AUDIO];
                        break;

                    case content::TYPE_DOCUMENT:
                        $itemsubtypes = [
                            content::TYPE_DOCUMENT,
                            content::TYPE_DOCUMENT_EMBED,
                            content::TYPE_URL_DOCUMENT,
                            content::TYPE_URL_DOCUMENT_PDF,
                            content::TYPE_URL_DOCUMENT_DOC
                        ];
                        break;

                    case content::TYPE_PRESENTATION:
                        $itemsubtypes = [
                            content::TYPE_PRESENTATION,
                            content::TYPE_PRESENTATION_EMBED,
                            content::TYPE_URL_PRESENTATION,
                            content::TYPE_URL_PRESENTATION_PPT
                        ];
                        break;

                    case content::TYPE_SPREADSHEET:
                        $itemsubtypes = [
                            content::TYPE_SPREADSHEET,
                            content::TYPE_SPREADSHEET_EMBED,
                            content::TYPE_URL_SPREADSHEET,
                            content::TYPE_URL_SPREADSHEET_XLS
                        ];
                        break;

                    case content::TYPE_URL:
                        $itemsubtypes = [
                            content::TYPE_TEXT,
                            content::TYPE_URL,
                            content::TYPE_URL_VIDEO,
                            content::TYPE_URL_VIDEO,
                            content::TYPE_URL_AUDIO,
                            content::TYPE_URL_DOCUMENT,
                            content::TYPE_URL_DOCUMENT_PDF,
                            content::TYPE_URL_DOCUMENT_DOC,
                            content::TYPE_URL_PRESENTATION,
                            content::TYPE_URL_PRESENTATION_PPT,
                            content::TYPE_URL_SPREADSHEET,
                            content::TYPE_URL_SPREADSHEET_XLS
                        ];
                        break;

                    case content::TYPE_FOLDER:
                        $itemsubtypes = [content::TYPE_FOLDER];
                        break;

                    default:
                        $itemsubtypes = [];
                        break;
                }
                $subtypes = array_merge($subtypes, $itemsubtypes);
            }
        }
        if (!empty($subtypes)) {
            $subtypes[] = -1;
            list($typesql, $params) = $DB->get_in_or_equal($subtypes);
            $sql = "AND (s.contenttype {$typesql}) ";
        }
        return [$sql, $params];
    }

    /**
     * Process list of tags into SQL and parameters
     *
     * @param string $filtertags Comma-separated list of tags.
     * @return array SQL and parameters
     */
    private static function tag_filter_sql($filtertags) {
        global $DB;

        $sql = '';
        $params = [];
        // Filter by tags.
        $cleanedtags = \core_tag_tag::normalize(explode(',', $filtertags));
        if (count($cleanedtags) > 0) {
            list($insql, $params) = $DB->get_in_or_equal($cleanedtags);
            $subsql = "SELECT id FROM {tag} WHERE name {$insql} ";
            $sql = <<<EOF
                AND EXISTS (SELECT 1
                              FROM {tag_instance}
                             WHERE tagid IN ({$subsql})
                               AND itemid = s.id
                )
EOF;

        }

        return [$sql, $params];
    }

    /**
     * Process list of participation flags and scope into SQL and parameters
     *
     * For filter flags that correspond to participation flags, restricts to content with those flags set.
     * For the "comments" filter flag, restricts to content with comments.
     * For the "tutor" filter flag, resticts to content with TUTOR visibility.
     *
     * @param string $filterparticipation Comma-separated list of filter flags
     * @param int $userid ID of the viewing user, for scope conditions
     * @param int $scope Filter scope, a stream::SCOPE_* constant.
     * @return array SQL and paramters
     */
    private static function participation_filter_sql($filterparticipation, $userid, $scope = null) {
        global $DB;
        // Filter by participation.
        $sql = '';
        $params = [];
        $flags = [];
        $filterflags = explode(',', $filterparticipation);
        foreach ($filterflags as $filterflag) {
            $filterflag = (int) trim($filterflag);
            if ($filterflag !== '') {
                // Map filter flag to participation flag.
                if ($filterflag === self::FILTER_FAVOURITES) {
                    $flags[] = flags::FAVOURITE;
                } else if ($filterflag === self::FILTER_MOSTSMILES) {
                    $flags[] = flags::MADEMELAUGH;
                } else if ($filterflag === self::FILTER_MOSTINSPIRATION) {
                    $flags[] = flags::INSPIREDME;
                } else if ($filterflag === self::FILTER_HELPME) {
                    $flags[] = flags::NEEDHELP;
                }
            }
        }
        $participationsql = '';
        if (!empty($flags)) {
            list($insql, $inparams) = $DB->get_in_or_equal($flags);
            $participationsql .= "EXISTS (SELECT f.id FROM {openstudio_flags} f WHERE f.contentid = s.id AND f.flagid {$insql} ";
            $params = array_merge($params, $inparams);
            if ($scope === self::SCOPE_MY) {
                $participationsql .= ' AND f.userid = ? ';
                $params[] = $userid;
            } else if ($scope === self::SCOPE_THEIRS) {
                $participationsql .= ' AND f.userid <> ? ';
                $params[] = $userid;
            }
            $participationsql .= ' ) ';
        }
        $commentsql = '';
        if (in_array(self::FILTER_COMMENTS, $filterflags)) {
            $commentsql .= 'EXISTS (SELECT c.id FROM {openstudio_comments} c WHERE c.contentid = s.id AND c.deletedby IS NULL ';
            if ($scope === self::SCOPE_MY) {
                $commentsql .= ' AND c.userid = ? ';
                $params[] = $userid;
            } else if ($scope === self::SCOPE_THEIRS) {
                $commentsql .= ' AND c.userid <> ? ';
                $params[] = $userid;
            }
            $commentsql .= ' ) ';
        }
        $tutorsql = '';
        if (in_array(self::FILTER_TUTOR, $filterflags)) {
            $tutorsql .= 's.visibility = ? ';
            $params[] = content::VISIBILITY_TUTOR;
        }

        $conditions = array_filter([$participationsql, $commentsql, $tutorsql]);
        if (!empty($conditions)) {
            $sql = 'AND (' . implode(' OR ', $conditions) . ')';
        }

        return [$sql, $params];
    }

    /**
     * Process status filter and scope into SQL and params.
     *
     * If the filter is READ or NOTREAD, restrict by those with or without the READ_CONTENT flag, respectively.
     * If the filter is EMPTYCONTENT, restrict by those with no content ID (just an empty level)
     * If the filter is LOCKED, restrict by those that are currently locked.
     *
     * @param int $filterstatus Status filter, stream::FILTER_* constant.
     * @param int $userid ID of the viewing user, for scope conditions
     * @param int $scope Filter scope, a stream::SCOPE_* constant.
     * @return array SQL and paramters
     */
    private static function status_filter_sql($filterstatus, $userid, $scope = null) {
        // Filter by status.
        $sql = '';
        $params = [];
        switch ($filterstatus) {
            case self::FILTER_READ:
            case self::FILTER_NOTREAD:
                if ($filterstatus === self::FILTER_NOTREAD) {
                    $not = ' NOT';
                } else {
                    $not = '';
                }
                $sql .= 'AND' . $not . ' EXISTS (SELECT f.id FROM {openstudio_flags} f WHERE f.contentid = s.id AND f.flagid = ? ';
                $params[] = flags::READ_CONTENT;
                if ($scope === self::SCOPE_MY) {
                    // Find all slots the the user has (not) read.
                    $sql .= ' AND f.userid = ? ';
                    $params[] = $userid;
                } else if ($scope === self::SCOPE_THEIRS) {
                    $sql .= ' AND f.userid <> ? ';
                    $params[] = $userid;
                }
                $sql .= ' ) ';
                break;

            case self::FILTER_EMPTYCONTENT:
                $sql .= 'AND s.id IS NULL ';
                break;

            case self::FILTER_LOCKED:
                $sql .= 'AND s.locktype > 0 AND s.lockedby IS NOT NULL ';
                break;
        }

        return [$sql, $params];
    }

    /**
     * Generate reciprocal access SQL and parameters
     *
     * Restrict to only show content if the viewing user also has a piece of content in the same activity level.
     *
     * @param $visibility
     * @param $userid
     * @return array
     */
    private static function reciprocal_access_sql($visibility, $userid) {
        $sql = '';
        $params = [];

        if (in_array($visibility, [null, content::VISIBILITY_MODULE, content::VISIBILITY_GROUP, content::VISIBILITY_WORKSPACE])) {
            $sql = <<<EOF
    AND EXISTS (SELECT 1
                  FROM {openstudio_contents} rac
                 WHERE rac.userid = ?
                   AND rac.levelid = s.levelid
                   AND rac.levelcontainer = s.levelcontainer)

EOF;

            $params[] = $userid;
        }
        return [$sql, $params];
    }

    /**
     *
     * Example usage:
     *
     * 1) Get the logged in user's stream:
     * a) stream::get_contents($studioid, $userid, $slotownerid, $visibility, ...)
     * b) $studioid = the studio instance to restrict the stream view
     * c) $userid = the logged in user's id.
     * d) $slotownerid = the logged in user's id.
     * e) $visibility can be group, module or private.
     *
     * 1) Get the stream of another user
     * a) stream::get_contents($studioid, $userid, $slotownerid, $visibility, ...)
     * b) $studioid = the studio instance to restrict the stream view
     * c) $userid = the logged in user's id.
     * d) $slotownerid = the id of the user's stream to view
     * Note: visibility parameter is ignored in this case and will always be
     * set to content::VISIBILITY_WORKSPACE
     *
     * NOTE: the $filtertype values are found in mod_openstudio\local\api\content as:
     *     content::TYPE_IMAGE
     *     content::TYPE_VIDEO
     *     content::TYPE_AUDIO
     *     content::TYPE_DOCUMENT
     *     content::TYPE_PRESENTATION
     *     content::TYPE_SPREADSHEET
     *     content::TYPE_URL
     *
     * NOTE: the $filterscope values are found in this class as:
     *     stream::SCOPE_MY
     *     stream::SCOPE_EVERYONE
     *     stream::SCOPE_THEIRS
     *
     * NOTE: the $filterparticipation values are found in this class as:
     *     stream::FILTER_FAVOURITES
     *     stream::FILTER_MOSTPOPULAR
     *     stream::FILTER_HELPME
     *     stream::FILTER_MOSTSMILES
     *     stream::FILTER_MOSTINSPIRATION
     *
     * NOTE: the $filterstatus values are found in this class as:
     *     stream::FILTER_EMPTYCONTENT
     *     stream::FILTER_NOTREAD
     *     stream::FILTER_READ
     *     stream::FILTER_LOCKED
     *
     * NOTE: the $sortorder values are found in this class as:
     *     stream::SORT_BY_DATE
     *     stream::SORT_BY_USERNAME
     *     stream::SORT_BY_ACTIVITYTITLE
     *     stream::SORT_BY_COMMENTNUMBERS
     *
     * @param int $studioid
     * @param int $groupingid
     * @param int $userid
     * @param int $contentownerid
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
    public static function get_contents(
            $studioid, $groupingid, $userid, $contentownerid, $visibility,
            $filterblocks = null, $filtertype = null, $filterscope = null,
            $filterparticipation = null, $filterstatus = null, $filtertags = null,
            $sortorder = array('id' => self::SORT_BY_DATE, 'asc' => self::SORT_DESC),
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
        // hardcoded to content::VISIBILITY_WORKSPACE
        // Likewise $pinboardonly is set to false.
        if ($userid != $contentownerid) {
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
0 AS l2hidelevel, 0 AS l3required, 0 AS l3contenttype

EOF;

            $fromsql = 'FROM {openstudio_contents} s ';

            $wheresql = 'AND s.levelcontainer = 0 ';

        } else if (in_array($visibility, [content::VISIBILITY_GROUP, content::VISIBILITY_MODULE, content::VISIBILITY_WORKSPACE])) {
            $selectsql = self::LEVELFIELDS;

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
            $selectsql = self::LEVELFIELDS;

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
            // so we need do a LEFT OUTER JOIN to the user table to return slot records
            // if a slot without userid cannot be matched to a user record.
            $userjoinsql = 'LEFT OUTER JOIN {user} u ON u.id = s.userid ';

        }

        // Permission checks.
        $permissionresult = self::get_permission_sql($studioid, $groupingid, $userid, $contentownerid, $visibility, $pinboardonly,
                $canmanagecontent, $groupid, $groupmode, $canaccessallgroup, $tutorroles);
        $permissionsql = $permissionresult->sql;
        $params = array_merge($params, $permissionresult->params);
        $fromsql .= $permissionresult->fromsql;

        // Studio instance filter.
        $studioinstancesql = 'AND (s.openstudioid IS NULL OR s.openstudioid = ?) ';
        $params[] = $studioid;

        // Get filter SQL and params.
        list($filterblockssql, $blockparams) = self::block_filter_sql($filterblocks);
        list($filtertypesql, $typeparams) = self::type_filter_sql($filtertype);
        list($filtertagsql, $tagparams) = self::tag_filter_sql($filtertags);
        list($filterflagsql, $flagparams) = self::participation_filter_sql($filterparticipation, $userid, $filterscope);
        list($filterstatussql, $statusparams) = self::status_filter_sql($filterstatus, $userid, $filterscope);
        if ($slotreciprocalaccess) {
            list($reciprocalaccesssql, $reciprocalparams) = self::reciprocal_access_sql($visibility, $userid);
        } else {
            $reciprocalaccesssql = '';
            $reciprocalparams = [];
        }
        $params = array_merge($params, $blockparams, $typeparams, $tagparams, $flagparams, $statusparams, $reciprocalparams);

        // Apply sort ordering.
        $sortordersql = '';
        if (is_array($sortorder)) {
            if (array_key_exists('id', $sortorder)) {
                if (array_key_exists('asc', $sortorder) && ($sortorder['asc'] == self::SORT_ASC)) {
                    $sortordering = self::SORT_ASC;
                } else {
                    $sortordering = self::SORT_DESC;
                }

                $sortorderid = $sortorder['id'];
                switch ($sortorderid) {
                    case self::SORT_BY_USERNAME:
                        $sortordersql = 'ORDER BY s.userid DESC ';
                        if ($sortordering == self::SORT_ASC) {
                            $sortordersql = 'ORDER BY s.userid ASC ';
                        }
                        break;

                    case self::SORT_BY_ACTIVITYTITLE:
                        if ($pinboardonly) {
                            $sortordersql = 'ORDER BY s.name DESC ';
                            if ($sortordering == self::SORT_ASC) {
                                $sortordersql = 'ORDER BY s.name ASC ';
                            }
                        } else {
                            if (is_array($filterblocks)) {
                                $sortordersql = 'ORDER BY l1sortorder, l2sortorder, l3sortorder, s.name DESC ';
                                if ($sortordering == self::SORT_ASC) {
                                    $sortordersql = 'ORDER BY l1sortorder, l2sortorder, l3sortorder, s.name ASC ';
                                }
                            } else {
                                $sortordersql = 'ORDER BY l1name DESC, l2name DESC, l3name DESC, s.name DESC ';
                                if ($sortordering == self::SORT_ASC) {
                                    $sortordersql = 'ORDER BY l1name ASC, l2name ASC, l3name ASC, s.name ASC ';
                                }
                            }
                        }
                        break;

                    case self::SORT_BY_DATE: // Fall through as sorting by date is the default.
                    default:
                        $sortordersql = 'ORDER BY s.timemodified DESC NULLS LAST ';
                        if ($sortordering == self::SORT_ASC) {
                            $sortordersql = 'ORDER BY s.timemodified ASC NULLS LAST ';
                        }
                        break;
                }
            }
        }

        // NOTE:
        // To make the construction of the where clauses predicatable,
        // a dummy statement of WHERE 1 = 1 has been added to the SQL below
        // so all dynamically constructed where clauses can begin with
        // "AND ....".

        // Construct the main SQL.
        $sqlselect = 'SELECT '. $selectsql . ', ' . self::CONTENTFIELDS . ' ';

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

{$reciprocalaccesssql}

AND (s.visibility IS NULL OR s.visibility != ?)

EOF;

        $params[] = content::VISIBILITY_INFOLDERONLY;

        if ($activitymode) {
            $sqlmain .= <<<EOF
AND (s.userid = ?
     OR (s.id IN (SELECT sf1.slotid
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
                return (object) array('contents' => $result, 'total' => $resultcount);
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * Gets list of contents by given ids.
     *
     * @param int $userid
     * @param array $contentids
     * @param bool $reciprocalaccess Restrict studio work slot so user only sees slots that they also created.
     * @return object|false Return contents data.
     */
    public static function get_contents_by_ids($userid, $contentids, $reciprocalaccess = false) {
        global $DB;

        list($filterinsql, $filterinparams) = $DB->get_in_or_equal($contentids);
        $params = $filterinparams;

        $fields = self::LEVELFIELDS . ', ' . self::CONTENTFIELDS;
        $sql = <<<EOF
         SELECT {$fields}
           FROM {openstudio_contents} s
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = s.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.openstudioid = s.openstudioid
     INNER JOIN {user} u ON u.id = s.userid
          WHERE s.id {$filterinsql}

EOF;

        if ($reciprocalaccess) {
            list($reciprocalsql, $reciprocalparams) = self::reciprocal_access_sql(null, $userid);
            $sql .= $reciprocalsql;
            $params = array_merge($params, $reciprocalparams);
        }

        try {
            $result = $DB->get_recordset_sql($sql, $params);
            if (!$result->valid()) {
                return false;
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }

    }

}

