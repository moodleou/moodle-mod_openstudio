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
use mod_openstudio\local\api\subscription;
use mod_openstudio\local\api\tracking;

/**
 * Get notificatin data for given user and studio.
 *
 * @param int $userid
 * @param int $studioid
 * @param int $type
 * @param int $slotid
 * @return mixed return notification data or false if error.
 */
function studio_api_notification_getdata($userid, $studioid, $type = null, $slotid = null) {
    global $DB;

    try {
        if ($type === null) {
            $subscriptions = $DB->get_records('openstudio_subscriptions',
                    array('userid' => $userid, 'openstudioid' => $studioid));
            $result = array();
            foreach ($subscriptions as $subscription) {
                $result[$subscription->subscription] = $subscription;
            }
            return $result;
        } else {
            if (($type == subscription::CONTENT) && ($slotid != null)) {
                $subscription = $DB->get_record('openstudio_subscriptions',
                        array('openstudioid' => $studioid, 'userid' => $userid,
                                'subscription' => $type, 'contentid' => $slotid),
                        '*', MUST_EXIST);
                return array($type => $subscription);
            } else {
                $subscription = $DB->get_record('openstudio_subscriptions',
                        array('openstudioid' => $studioid, 'userid' => $userid,
                                'subscription' => $type),
                        '*', MUST_EXIST);
                return array($type => $subscription);
            }
        }
    } catch (Exception $e) {
        // Default to returning false.
    }

    return false;
}

/**
 * Notifications at the module level.
 *
 * @param int $userid
 * @param int $studioid
 * @param array $flags
 * @param array $actions
 * @param int $timefrom
 * @param int $timeto
 * @param int $ownerid
 * @param int $limitfrom
 * @param int $limitnum
 * @return mixed Return recordset of result or false if error.
 */
function studio_api_notification_module($userid, $studioid, array $flags = null, array $actions = null,
                                        $timefrom, $timeto = '', $ownerid = '', $limitfrom = 0, $limitnum = 250) {

    global $DB;

    // Timeto is the greater of the 2 dates. So, from 1998 to 2012.
    if ($timeto == '') {
        // NOW, basically!
        $timeto = time();
    }
    if (empty($flags)) {
        $flags = array(
                flags::FAVOURITE,
                flags::NEEDHELP,
                flags::MADEMELAUGH,
                flags::INSPIREDME
        );
    }

    if (empty($actions)) {
        $actions = array(
                tracking::CREATE_CONTENT,
                tracking::READ_CONTENT,
                tracking::DELETE_CONTENT,
                tracking::UPDATE_CONTENT,
                tracking::UPDATE_CONTENT_VISIBILITY_PRIVATE,
                tracking::UPDATE_CONTENT_VISIBILITY_GROUP,
                tracking::UPDATE_CONTENT_VISIBILITY_MODULE,
                tracking::MODIFY_FOLDER
        );
    }

    // Prepare actions SQL.
    list($actionssql, $actionsparams) = $DB->get_in_or_equal($actions);
    $actionssql = "AND {openstudio_tracking}.actionid $actionssql";

    // Prepare flags.
    list($flagssql, $flagsparams) = $DB->get_in_or_equal($flags);
    $flagssql = "AND {openstudio_flags}.flagid $flagssql";

    $internallimit = 25;
    $internaloffset = 0;

    // Prepare SQL.
    $sql = <<<EOF
SELECT comboquery.id,
       comboquery.timemodified,
       comboquery.actionid as actionid,
       comboquery.flagid as flagid,
       comboquery.openstudioid as openstudioid,
       comboquery.levelid,
       comboquery.levelcontainer,
       comboquery.name as name,
       comboquery.userid as userid,
       comboquery.commentid as commentid,
       comboquery.commenttext as commenttext,
       comboquery.visibility as visibility,
       {user}.firstname as firstname,
       {user}.lastname as lastname,
       l1.id as l1id,
       l1.name as l1name,
       l2.id as l2id,
       l2.name as l2name,
       l2.hidelevel as l2hidelevel,
       l3.id as l3id,
       l3.name as l3name
  FROM ((SELECT id,
                timemodified,
                name,
                levelid,
                levelcontainer,
                openstudioid,
                visibility,
                userid,
                0 as actionid,
                0 as flagid,
                0 as commentid,
                NULL as commenttext
           FROM {openstudio_contents}
          WHERE (timemodified >= ? AND timemodified <= ?)
            AND openstudioid = ?
            AND contenttype != ?
            AND

EOF;

    $params[] = $timefrom;
    $params[] = $timeto;
    $params[] = $studioid;
    $params[] = content::TYPE_NONE;

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' {openstudio_contents}.userid = ? AND ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
(           visibility = ?
 AND EXISTS (SELECT 1
               FROM {user_enrolments} ue1
               JOIN {enrol} e1 ON e1.id = ue1.enrolid
              WHERE ue1.userid = {openstudio_contents}.userid
         AND EXISTS (SELECT 1
                       FROM {user_enrolments} ue2
                       JOIN {enrol} e2 ON e2.id = ue2.enrolid
                                      AND e2.courseid = e1.courseid
                      WHERE ue2.userid = ?))
)
ORDER BY timemodified DESC
LIMIT ? OFFSET ?)

EOF;

    $params[] = content::VISIBILITY_MODULE;
    $params[] = $userid;
    $params[] = $internallimit;
    $params[] = $internaloffset;

    $sql .= <<<EOF
UNION ALL

    (SELECT contentid as id,
            timemodified,
            NULL as name,
            0 as levelid,
            0 as levelcontainer,
            0 as openstudioid,
            0 as visibility,
            userid,
            0 as actionid,
            flagid,
            0 as commentid,
            NULL as commenttext
      FROM {openstudio_flags}
     WHERE contentid IN (SELECT id
                        FROM {openstudio_contents}
                       WHERE (timemodified >= ? AND timemodified <= ?)
                         AND openstudioid = ?
                         AND contenttype != ?
                         AND

EOF;

    $params[] = $timefrom;
    $params[] = $timeto;
    $params[] = $studioid;
    $params[] = content::TYPE_NONE;

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' {openstudio_contents}.userid = ? AND ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
(           visibility = ?
 AND EXISTS (SELECT 1
               FROM {user_enrolments} ue1
               JOIN {enrol} e1 ON e1.id = ue1.enrolid
              WHERE ue1.userid = {openstudio_contents}.userid
         AND EXISTS (SELECT 1
                       FROM {user_enrolments} ue2
                       JOIN {enrol} e2 ON e2.id = ue2.enrolid
                                      AND e2.courseid = e1.courseid
                      WHERE ue2.userid = ?)))
)
AND (timemodified >= ? AND timemodified <= ?)

EOF;

    $sql .= $flagssql;

    $sql .= <<<EOF
    AND (EXISTS (SELECT 1
                   FROM {user_enrolments} ue1
                   JOIN {enrol} e1 ON e1.id = ue1.enrolid
                  WHERE ue1.userid = {openstudio_flags}.userid
                    AND EXISTS (SELECT 1
                                FROM {user_enrolments} ue2
                                JOIN {enrol} e2 ON e2.id = ue2.enrolid AND e2.courseid = e1.courseid
                               WHERE ue2.userid = ?))
         )
    ORDER BY timemodified DESC
    LIMIT ? OFFSET ?
)
EOF;

    $params[] = content::VISIBILITY_MODULE;
    $params[] = $userid;
    $params[] = $timefrom;
    $params[] = $timeto;
    foreach ($flags as $flag) {
        $params[] = $flag;
    }
    $params[] = $userid;
    $params[] = $internallimit;
    $params[] = $internaloffset;

    $sql .= <<<EOF
UNION ALL

    (SELECT contentid as id,
            timemodified,
            NULL as name,
            0 as levelid,
            0 as levelcontainer,
            0 as openstudioid,
            0 as visibility,
            userid,
            actionid,
            0 as flagid,
            0 as commentid,
            NULL as commenttext
       FROM {openstudio_tracking}
      WHERE contentid IN (SELECT id
                         FROM {openstudio_contents}
                        WHERE (timemodified >= ? AND timemodified <= ?)
                          AND openstudioid = ?
                          AND contenttype != ?
                          AND

EOF;

    $params[] = $timefrom;
    $params[] = $timeto;
    $params[] = $studioid;
    $params[] = content::TYPE_NONE;

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' {openstudio_contents}.userid = ? AND ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
    (           visibility = ?
     AND EXISTS (SELECT 1
           FROM {user_enrolments} ue1
           JOIN {enrol} e1 ON e1.id = ue1.enrolid
          WHERE ue1.userid = {openstudio_contents}.userid
            AND EXISTS (SELECT 1
                          FROM {user_enrolments} ue2
                          JOIN {enrol} e2 ON e2.id = ue2.enrolid AND e2.courseid = e1.courseid
                         WHERE ue2.userid = ?))
    )
)
AND (timemodified >= ? AND timemodified <= ?)

EOF;

    $sql .= $actionssql;

    $sql .= <<<EOF
    AND (EXISTS (SELECT 1
                   FROM {user_enrolments} ue1
                   JOIN {enrol} e1 ON e1.id = ue1.enrolid
                  WHERE ue1.userid = {openstudio_tracking}.userid
                    AND EXISTS (SELECT 1
                                  FROM {user_enrolments} ue2
                                  JOIN {enrol} e2 ON e2.id = ue2.enrolid AND e2.courseid = e1.courseid
                                 WHERE ue2.userid = ?))
         )
    ORDER BY timemodified DESC
    LIMIT ? OFFSET ?
)

EOF;

    $params[] = content::VISIBILITY_MODULE;
    $params[] = $userid;
    $params[] = $timefrom;
    $params[] = $timeto;
    foreach ($actions as $action) {
        $params[] = $action;
    }
    $params[] = $userid;
    $params[] = $internallimit;
    $params[] = $internaloffset;

    $sql .= <<<EOF
UNION ALL

    (SELECT contentid as id,
            timemodified,
            NULL as name,
            0 as levelid,
            0 as levelcontainer,
            0 as openstudioid,
            0 as visibility,
            userid,
            0 as actionid,
            0 as flagid,
            id as commentid,
            commenttext
       FROM {openstudio_comments}
      WHERE contentid IN (SELECT id
                         FROM {openstudio_contents}
                        WHERE (timemodified >= ? AND timemodified <= ?)
                          AND openstudioid = ?
                          AND contenttype != ?
                          AND

EOF;

    $params[] = $timefrom;
    $params[] = $timeto;
    $params[] = $studioid;
    $params[] = content::TYPE_NONE;

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' {openstudio_contents}.userid = ? AND ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
    (           visibility = ?
     AND EXISTS (SELECT 1
                   FROM {user_enrolments} ue1
                   JOIN {enrol} e1 ON e1.id = ue1.enrolid
                  WHERE ue1.userid = {openstudio_contents}.userid
             AND EXISTS (SELECT 1
                           FROM {user_enrolments} ue2
                           JOIN {enrol} e2 ON e2.id = ue2.enrolid
                            AND e2.courseid = e1.courseid
                          WHERE ue2.userid = ?))
    )
)
AND (timemodified >= ? AND timemodified <= ?)
AND deletedby IS NULL

EOF;

    $sql .= <<<EOF
    AND (EXISTS (SELECT 1
                   FROM {user_enrolments} ue1
                   JOIN {enrol} e1 ON e1.id = ue1.enrolid
                  WHERE ue1.userid = {openstudio_comments}.userid
             AND EXISTS (SELECT 1
                           FROM {user_enrolments} ue2
                           JOIN {enrol} e2 ON e2.id = ue2.enrolid AND e2.courseid = e1.courseid
                          WHERE ue2.userid = ?)))
    ORDER BY timemodified DESC
       LIMIT ? OFFSET ?
)

EOF;

    $params[] = content::VISIBILITY_MODULE;
    $params[] = $userid;
    $params[] = $timefrom;
    $params[] = $timeto;
    $params[] = $userid;
    $params[] = $internallimit;
    $params[] = $internaloffset;

    $sql .= <<<EOF
) AS comboquery
     INNER JOIN {user} ON {user}.id = comboquery.userid
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = comboquery.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
       ORDER BY comboquery.timemodified DESC

EOF;

    try {
        $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
        if ($rs->valid()) {
            return $rs;
        }
    } catch (Exception $e) {
        // Default to returning false.
    }

    return false;
}

/**
 * Notifications at the group level.
 *
 * @param int $userid
 * @param int $studioid
 * @param array $flags
 * @param array $actions
 * @param int $timefrom
 * @param int $timeto
 * @param int $ownerid
 * @param int $limitfrom
 * @param int $limitnum
 * @return mixed Return recordset of result or false if error.
 */
function studio_api_notification_group($userid, $studioid, array $flags = null, array $actions = null,
                                       $timefrom = '', $timeto = '', $ownerid = '', $limitfrom = 0, $limitnum = 100) {

    global $DB;

    // Timeto is the greater of the 2 dates. So, from 1998 to 2012.
    if ($timefrom == '') {
        $timefrom = time() - (1 * 60 * 60);
    }
    if ($timeto == '') {
        $timeto = time();
    } // NOW, basically!
    if (empty($flags)) {
        $flags = array(
                flags::FAVOURITE,
                flags::NEEDHELP,
                flags::MADEMELAUGH,
                flags::INSPIREDME
        );
    }
    if (empty($actions)) {
        $actions = array(
                tracking::CREATE_CONTENT,
                tracking::READ_CONTENT,
                tracking::DELETE_CONTENT,
                tracking::UPDATE_CONTENT,
                tracking::UPDATE_CONTENT_VISIBILITY_PRIVATE,
                tracking::UPDATE_CONTENT_VISIBILITY_GROUP,
                tracking::UPDATE_CONTENT_VISIBILITY_MODULE,
                tracking::MODIFY_FOLDER
        );
    }
    $internallimit = 25;
    $internaloffset = 0;

    // Prepare actions SQL.
    list($actionssql, $actionsparams) = $DB->get_in_or_equal($actions);
    $actionssql = "AND {openstudio_tracking}.actionid $actionssql";

    // Prepare flags.
    list($flagssql, $flagsparams) = $DB->get_in_or_equal($flags);
    $flagssql = "AND {openstudio_flags}.flagid $flagssql";

    // Get groupmode/groupidid from studioid.
    $cm = mod_openstudio\local\util::get_coursemodule_from_studioid($studioid);
    if ($cm === false) {
        return false;
    }
    if (($cm->groupmode > 0) && ($cm->groupingid > 0)) {
        $groupmode = $cm->groupmode;
        $groupingid = $cm->groupingid;
    } else {
        return false;
    }

    $tutorroles = array_filter(explode(',', $DB->get_field('openstudio', 'tutorroles', array('id' => $studioid))));
    $tutorparams = array();
    $tutorpermissionsql = '';
    if (!empty($tutorroles)) {
        // The following is only applicable if visibility::TUTOR is enabled.
        // This is specific to the OU's concept of a "tutor".
        list($rolesql, $roleparams) = $DB->get_in_or_equal($tutorroles);
        $tutorpermissionsql = <<<EOF
            OR (
                {openstudio_contents}.visibility = ?
                AND EXISTS (
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
                       AND s1.id = {openstudio_contents}.id
                       AND tgm2.userid = ?
                )
            )
EOF;
        $tutorparams[] = tracking::TUTOR;
        $tutorparams = array_merge($tutorparams, $roleparams);
        $tutorparams[] = $userid;
        $tutorparams[] = $userid;
    }

    // Prepare SQL.
    $sql = <<<EOF
SELECT comboquery.id,
       comboquery.timemodified,
       comboquery.actionid as actionid,
       comboquery.flagid as flagid,
       comboquery.openstudioid as openstudioid,
       comboquery.levelid,
       comboquery.levelcontainer,
       comboquery.name as name,
       comboquery.userid as userid,
       comboquery.commentid as commentid,
       comboquery.commenttext as commenttext,
       comboquery.visibility as visibility,
       {user}.firstname as firstname,
       {user}.lastname as lastname,
       l1.id as l1id,
       l1.name as l1name,
       l2.id as l2id,
       l2.name as l2name,
       l2.hidelevel as l2hidelevel,
       l3.id as l3id,
       l3.name as l3name
  FROM ((SELECT id,
                timemodified,
                name,
                levelid,
                levelcontainer,
                openstudioid,
                visibility,
                userid,
                0 as actionid,
                0 as flagid,
                0 as commentid,
                NULL as commenttext
           FROM {openstudio_contents}
          WHERE (timemodified >= ? AND timemodified <= ?)
            AND openstudioid = ?
            AND contenttype != ?
            AND

EOF;

    $params[] = $timefrom;
    $params[] = $timeto;
    $params[] = $studioid;
    $params[] = content::TYPE_NONE;

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' {openstudio_contents}.userid = ? AND ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
    (
        (

            2 = ?
            AND (
                {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility < 0
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm1
                JOIN {groupings_groups} gg1 ON gg1.groupid = gm1.groupid AND gg1.groupingid = ?
                WHERE gm1.userid = {openstudio_contents}.userid
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm2
                JOIN {groupings_groups} gg2 ON gg2.groupid = gm2.groupid AND gg2.groupingid = ?
                WHERE gm2.userid = ?
            )

        ) OR (
            2 <> ?
            AND (
                {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility = ?
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm5
                JOIN {groupings_groups} gg3 ON gg3.groupid = gm5.groupid AND gg3.groupingid = ?
                JOIN {groups_members} gm6 ON gg3.groupid = gm5.groupid AND gm5.groupid = gm6.groupid
                WHERE gm5.userid = {openstudio_contents}.userid
                AND gm6.userid = ?
            )
        ) OR (
            (
                2 <> ?
                AND {openstudio_contents}.visibility < 0
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm3
                JOIN {groups_members} gm4 ON gm4.groupid = gm3.groupid AND gm4.userid = ?
                WHERE gm3.groupid = (0 - {openstudio_contents}.visibility)
                AND gm3.userid = {openstudio_contents}.userid
            )
        ) {$tutorpermissionsql}
    )
    ORDER BY timemodified DESC
    LIMIT ? OFFSET ?
)

EOF;

    $params[] = $groupmode;
    $params[] = content::VISIBILITY_MODULE;
    $params[] = content::VISIBILITY_GROUP;
    $params[] = $groupingid;
    $params[] = $groupingid;
    $params[] = $userid;
    $params[] = $groupmode;
    $params[] = content::VISIBILITY_MODULE;
    $params[] = content::VISIBILITY_GROUP;
    $params[] = $groupingid;
    $params[] = $userid;
    $params[] = $groupmode;
    $params[] = $userid;
    $params = array_merge($params, $tutorparams);
    $params[] = $internallimit;
    $params[] = $internaloffset;

    $sql .= <<<EOF
UNION ALL

    (SELECT {openstudio_flags}.contentid as id,
            {openstudio_flags}.timemodified,
            NULL as name,
            0 as levelid,
            0 as levelcontainer,
            0 as openstudioid,
            0 as visibility,
            {openstudio_flags}.userid,
            0 as actionid,
            {openstudio_flags}.flagid,
            0 as commentid,
            NULL as commenttext
       FROM {openstudio_flags}
      WHERE contentid IN (SELECT id
                         FROM {openstudio_contents}
                        WHERE (timemodified >= ? AND timemodified <= ?)
                          AND openstudioid = ?
                          AND contenttype != ?
                          AND

EOF;

    $params[] = $timefrom;
    $params[] = $timeto;
    $params[] = $studioid;
    $params[] = content::TYPE_NONE;

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' {openstudio_contents}.userid = ? AND ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
    (
        (

            2 = ?
            AND (
                {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility < 0
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm1
                JOIN {groupings_groups} gg1 ON gg1.groupid = gm1.groupid AND gg1.groupingid = ?
                WHERE gm1.userid = {openstudio_contents}.userid
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm2
                JOIN {groupings_groups} gg2 ON gg2.groupid = gm2.groupid AND gg2.groupingid = ?
                WHERE gm2.userid = ?
            )

        ) OR (
            2 <> ?
            AND (
                {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility = ?
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm5
                JOIN {groupings_groups} gg3 ON gg3.groupid = gm5.groupid AND gg3.groupingid = ?
                JOIN {groups_members} gm6 ON gg3.groupid = gm5.groupid AND gm5.groupid = gm6.groupid
                WHERE gm5.userid = {openstudio_contents}.userid
                AND gm6.userid = ?
            )
        ) OR (
            (
                2 <> ?
                AND {openstudio_contents}.visibility < 0
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm3
                JOIN {groups_members} gm4 ON gm4.groupid = gm3.groupid AND gm4.userid = ?
                WHERE gm3.groupid = (0 - {openstudio_contents}.visibility)
                AND gm3.userid = {openstudio_contents}.userid
            )
        ) {$tutorpermissionsql}
    )
) AND (
    {openstudio_flags}.timemodified >= ?
    AND {openstudio_flags}.timemodified <= ?
)

EOF;

    $sql .= $flagssql;

    $params[] = $groupmode;
    $params[] = content::VISIBILITY_MODULE;
    $params[] = content::VISIBILITY_GROUP;
    $params[] = $groupingid;
    $params[] = $groupingid;
    $params[] = $userid;
    $params[] = $groupmode;
    $params[] = content::VISIBILITY_MODULE;
    $params[] = content::VISIBILITY_GROUP;
    $params[] = $groupingid;
    $params[] = $userid;
    $params[] = $groupmode;
    $params[] = $userid;
    $params = array_merge($params, $tutorparams);
    $params[] = $timefrom;
    $params[] = $timeto;
    foreach ($flags as $flag) {
        $params[] = $flag;
    }

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' AND {openstudio_flags}.userid = ? ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
    ORDER BY timemodified DESC
    LIMIT ? OFFSET ?
)

EOF;

    $params[] = $internallimit;
    $params[] = $internaloffset;

    $sql .= <<<EOF
UNION ALL

    (SELECT {openstudio_tracking}.contentid as id,
            {openstudio_tracking}.timemodified,
            NULL as name,
            0 as levelid,
            0 as levelcontainer,
            0 as openstudioid,
            0 as visibility,
            {openstudio_tracking}.userid,
            {openstudio_tracking}.actionid,
            0 as flagid,
            0 as commentid,
            NULL as commenttext
       FROM {openstudio_tracking}
      WHERE contentid IN (SELECT id
                         FROM {openstudio_contents}
                        WHERE (timemodified >= ? AND timemodified <= ?)
                          AND openstudioid = ?
                          AND contenttype != ?
                          AND

EOF;

    $params[] = $timefrom;
    $params[] = $timeto;
    $params[] = $studioid;
    $params[] = content::TYPE_NONE;

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' {openstudio_contents}.userid = ?  AND ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
    (
        (

            2 = ?
            AND (
                {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility < 0
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm1
                JOIN {groupings_groups} gg1 ON gg1.groupid = gm1.groupid AND gg1.groupingid = ?
                WHERE gm1.userid = {openstudio_contents}.userid
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm2
                JOIN {groupings_groups} gg2 ON gg2.groupid = gm2.groupid AND gg2.groupingid = ?
                WHERE gm2.userid = ?
            )

        ) OR (
            2 <> ?
            AND (
                {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility = ?
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm5
                JOIN {groupings_groups} gg3 ON gg3.groupid = gm5.groupid AND gg3.groupingid = ?
                JOIN {groups_members} gm6 ON gg3.groupid = gm5.groupid AND gm5.groupid = gm6.groupid
                WHERE gm5.userid = {openstudio_contents}.userid
                AND gm6.userid = ?
            )
        ) OR (
            (
                2 <> ?
                AND {openstudio_contents}.visibility < 0
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm3
                JOIN {groups_members} gm4 ON gm4.groupid = gm3.groupid AND gm4.userid = ?
                WHERE gm3.groupid = (0 - {openstudio_contents}.visibility)
                AND gm3.userid = {openstudio_contents}.userid
            )
        ) {$tutorpermissionsql}
    )
) AND (
    {openstudio_tracking}.timemodified >= ?
    AND {openstudio_tracking}.timemodified <= ?
)

EOF;

    $sql .= $actionssql;

    $params[] = $groupmode;
    $params[] = content::VISIBILITY_MODULE;
    $params[] = content::VISIBILITY_GROUP;
    $params[] = $groupingid;
    $params[] = $groupingid;
    $params[] = $userid;
    $params[] = $groupmode;
    $params[] = content::VISIBILITY_MODULE;
    $params[] = content::VISIBILITY_GROUP;
    $params[] = $groupingid;
    $params[] = $userid;
    $params[] = $groupmode;
    $params[] = $userid;
    $params = array_merge($params, $tutorparams);
    $params[] = $timefrom;
    $params[] = $timeto;
    foreach ($actions as $action) {
        $params[] = $action;
    }

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' AND {openstudio_tracking}.userid = ? ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
    ORDER BY timemodified DESC
    LIMIT ? OFFSET ?
)

EOF;

    $params[] = $internallimit;
    $params[] = $internaloffset;

    $sql .= <<<EOF
UNION ALL

    (SELECT contentid as id,
            timemodified,
            NULL as name,
            0 as levelid,
            0 as levelcontainer,
            0 as openstudioid,
            0 as visibility,
            userid,
            0 as actionid,
            0 as flagid,
            id as commentid,
            commenttext
       FROM {openstudio_comments}
      WHERE contentid IN (SELECT id
                         FROM {openstudio_contents}
                        WHERE (timemodified >= ? AND timemodified <= ?)
                          AND openstudioid = ?
                          AND contenttype != ?
                          AND

EOF;

    $params[] = $timefrom;
    $params[] = $timeto;
    $params[] = $studioid;
    $params[] = content::TYPE_NONE;

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' {openstudio_contents}.userid = ? AND ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
    (
        (

            2 = ?
            AND (
                {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility < 0
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm1
                JOIN {groupings_groups} gg1 ON gg1.groupid = gm1.groupid AND gg1.groupingid = ?
                WHERE gm1.userid = {openstudio_contents}.userid
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm2
                JOIN {groupings_groups} gg2 ON gg2.groupid = gm2.groupid AND gg2.groupingid = ?
                WHERE gm2.userid = ?
            )

        ) OR (
            2 <> ?
            AND (
                {openstudio_contents}.visibility = ?
                OR {openstudio_contents}.visibility = ?
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm5
                JOIN {groupings_groups} gg3 ON gg3.groupid = gm5.groupid AND gg3.groupingid = ?
                JOIN {groups_members} gm6 ON gg3.groupid = gm5.groupid AND gm5.groupid = gm6.groupid
                WHERE gm5.userid = {openstudio_contents}.userid
                AND gm6.userid = ?
            )
        ) OR (
            (
                2 <> ?
                AND {openstudio_contents}.visibility < 0
            ) AND EXISTS (
                SELECT 1
                FROM {groups_members} gm3
                JOIN {groups_members} gm4 ON gm4.groupid = gm3.groupid AND gm4.userid = ?
                WHERE gm3.groupid = (0 - {openstudio_contents}.visibility)
                AND gm3.userid = {openstudio_contents}.userid
            )
        ) {$tutorpermissionsql}
    )
) AND (
    {openstudio_comments}.timemodified >= ?
    AND {openstudio_comments}.timemodified <= ?
) AND {openstudio_comments}.deletedby IS NULL

EOF;

    $params[] = $groupmode;
    $params[] = content::VISIBILITY_MODULE;
    $params[] = content::VISIBILITY_GROUP;
    $params[] = $groupingid;
    $params[] = $groupingid;
    $params[] = $userid;
    $params[] = $groupmode;
    $params[] = content::VISIBILITY_MODULE;
    $params[] = content::VISIBILITY_GROUP;
    $params[] = $groupingid;
    $params[] = $userid;
    $params[] = $groupmode;
    $params[] = $userid;
    $params = array_merge($params, $tutorparams);
    $params[] = $timefrom;
    $params[] = $timeto;

    if ($userid != $ownerid && $ownerid != '') {
        $sql .= ' AND {openstudio_comments}.userid = ? ';
        $params[] = $ownerid;
    }

    $sql .= <<<EOF
    ORDER BY timemodified DESC
       LIMIT ? OFFSET ?
)

EOF;

    $params[] = $internallimit;
    $params[] = $internaloffset;

    $sql .= <<<EOF
) AS comboquery
     INNER JOIN {user} ON {user}.id = comboquery.userid
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = comboquery.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
       ORDER BY comboquery.timemodified DESC

EOF;

    try {
        $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
        if ($rs->valid()) {
            return $rs;
        }
    } catch (Exception $e) {
        // Default to returning false.
    }

    return false;
}

/**
 * Notifications at the slot level.
 *
 * @param int $slotid
 * @param int $timefrom
 * @param int $timeto
 * @param array $flags
 * @param array $actions
 * @param int $limitfrom
 * @param int $limitnum
 * @return mixed Return recordset of result or false if error.
 */
function studio_api_notification_slot($slotid, $timefrom, $timeto = '', array $flags = null,
                                      array $actions = null, $limitfrom = 0, $limitnum = 100) {

    global $DB;

    // Timeto is the greater of the 2 dates. So, from 1998 to 2012.
    if ($timeto == '') {
        $timeto = time(); // NOW, basically!
    }

    if (empty($flags)) {
        $flags = array(
                flags::FAVOURITE,
                flags::NEEDHELP,
                flags::MADEMELAUGH,
                flags::INSPIREDME
        );
    }

    if (empty($actions)) {
        $actions = array(
                tracking::CREATE_CONTENT,
                tracking::READ_CONTENT,
                tracking::DELETE_CONTENT,
                tracking::UPDATE_CONTENT,
                tracking::UPDATE_CONTENT_VISIBILITY_PRIVATE,
                tracking::UPDATE_CONTENT_VISIBILITY_GROUP,
                tracking::UPDATE_CONTENT_VISIBILITY_MODULE,
                tracking::MODIFY_FOLDER
        );
    }

    $internallimit = 25;
    $internaloffset = 0;

    // Prepare actions SQL.
    list($actionssql, $actionsparams) = $DB->get_in_or_equal($actions);
    $actionssql = "AND {openstudio_tracking}.actionid $actionssql";

    // Prepare flags.
    list($flagssql, $flagsparams) = $DB->get_in_or_equal($flags);
    $flagssql = "AND {openstudio_flags}.flagid $flagssql";

    // Prepare SQL.
    $sql = <<<EOF
SELECT comboquery.id,
       comboquery.timemodified,
       comboquery.actionid as actionid,
       comboquery.flagid as flagid,
       comboquery.openstudioid as openstudioid,
       comboquery.levelid,
       comboquery.levelcontainer,
       comboquery.name as name,
       comboquery.userid as userid,
       comboquery.commentid as commentid,
       comboquery.commenttext as commenttext,
       comboquery.visibility as visibility,
       {user}.firstname as firstname,
       {user}.lastname as lastname,
       l1.id as l1id,
       l1.name as l1name,
       l2.id as l2id,
       l2.name as l2name,
       l2.hidelevel as l2hidelevel,
       l3.id as l3id,
       l3.name as l3name
  FROM ((SELECT id,
                timemodified,
                name,
                levelid,
                levelcontainer,
                openstudioid,
                userid,
                visibility,
                0 as actionid,
                0 as flagid,
                0 as commentid,
                NULL as commenttext
           FROM {openstudio_contents}
          WHERE id = ?
            AND contenttype != ?
       ORDER BY timemodified DESC
          LIMIT ? OFFSET ?)

        UNION ALL

        (SELECT contentid as id,
                timemodified,
                NULL as name,
                0 as levelid,
                0 as levelcontainer,
                0 as openstudioid,
                userid,
                0 as visibility,
                0 as actionid,
                flagid,
                0 as commentid,
                NULL as commenttext
           FROM {openstudio_flags}
          WHERE contentid = ?
            AND (timemodified >= ? AND timemodified <= ?)

EOF;

    $sql .= $flagssql;
    $sql .= <<<EOF
       ORDER BY timemodified DESC
          LIMIT ? OFFSET ?)

        UNION ALL

        (SELECT contentid as id,
                timemodified,
                NULL as name,
                0 as levelid,
                0 as levelcontainer,
                0 as openstudioid,
                userid,
                0 as visibility,
                actionid,
                0 as flagid,
                0 as commentid,
                NULL as commenttext
           FROM {openstudio_tracking}
          WHERE contentid = ?
            AND (timemodified >= ? AND timemodified <= ?)

EOF;

    $sql .= $actionssql;
    $sql .= <<<EOF
       ORDER BY timemodified DESC
          LIMIT ? OFFSET ?)

        UNION ALL

        (SELECT contentid as id,
                timemodified,
                NULL as name,
                0 as levelid,
                0 as levelcontainer,
                0 as openstudioid,
                userid,
                0 as visibility,
                0 as actionid,
                0 as flagid,
                id as commentid,
                commenttext
           FROM {openstudio_comments}
          WHERE contentid = ?
            AND (timemodified >= ? AND timemodified <= ?)
            AND deletedby IS NULL
       ORDER BY timemodified DESC
          LIMIT ? OFFSET ?)
) AS comboquery
     INNER JOIN {user} ON {user}.id = comboquery.userid
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = comboquery.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
       ORDER BY comboquery.timemodified DESC

EOF;

    $params = array();
    $params[] = $slotid;
    $params[] = content::TYPE_NONE;
    $params[] = $internallimit;
    $params[] = $internaloffset;
    $params[] = $slotid;
    $params[] = $timefrom;
    $params[] = $timeto;
    foreach ($flags as $flag) {
        $params[] = $flag;
    }
    $params[] = $internallimit;
    $params[] = $internaloffset;
    $params[] = $slotid;
    $params[] = $timefrom;
    $params[] = $timeto;
    foreach ($actions as $action) {
        $params[] = $action;
    }
    $params[] = $internallimit;
    $params[] = $internaloffset;
    $params[] = $slotid;
    $params[] = $timefrom;
    $params[] = $timeto;
    $params[] = $internallimit;
    $params[] = $internaloffset;

    try {
        $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
        if ($rs->valid()) {
            return $rs;
        }
    } catch (Exception $e) {
        // Default to returning false.
    }

    return false;
}

/**
 * This function filters slots for multiple actions with different timestamps to ONLY pick up
 * the actionid with the largest timestamp. This will help keep the information more useful
 * and relevant in notification emails.
 *
 * @param array $slots
 * @return array Return filtered slots.
 */
function studio_api_notification_filter_slots(array $slots) {
    // Assign to another variable to loop.
    $rawslots = $slots;

    // Prepare array with flags info.
    $flags = array();
    $flags[flags::FAVOURITE]['desc'] = get_string('contentfavouriteforrssdesc', 'openstudio');
    $flags[flags::NEEDHELP]['desc'] = get_string('contentneedhelpforrssdesc', 'openstudio');
    $flags[flags::MADEMELAUGH]['desc'] = get_string('contentmademelaughforrssdesc', 'openstudio');
    $flags[flags::INSPIREDME]['desc'] = get_string('contentinspiredmeforrssdesc', 'openstudio');
    $flags[flags::FAVOURITE]['title'] = get_string('contentfavouriteforrsstitle', 'openstudio');
    $flags[flags::NEEDHELP]['title'] = get_string('contentneedhelpforrsstitle', 'openstudio');
    $flags[flags::MADEMELAUGH]['title'] = get_string('contentmademelaughforrsstitle', 'openstudio');
    $flags[flags::INSPIREDME]['title'] = get_string('contentinspiredmeforrsstitle', 'openstudio');

    // Prepare array with actions info.
    $actions = array();
    $actions[tracking::CREATE_CONTENT]['title'] =
            get_string('contentactioncreatename', 'openstudio');
    $actions[tracking::CREATE_CONTENT]['desc'] =
            get_string('contentactioncreatedesc', 'openstudio');
    $actions[tracking::READ_CONTENT]['title'] =
            get_string('contentactionreadname', 'openstudio');
    $actions[tracking::READ_CONTENT]['desc'] =
            get_string('contentactionreaddesc', 'openstudio');
    $actions[tracking::UPDATE_CONTENT]['title'] =
            get_string('contentactionupdatename', 'openstudio');
    $actions[tracking::UPDATE_CONTENT]['desc'] =
            get_string('contentactionupdatedesc', 'openstudio');
    $actions[tracking::DELETE_CONTENT]['title'] =
            get_string('contentactionupdatedesc', 'openstudio');
    $actions[tracking::DELETE_CONTENT]['desc'] =
            get_string('contentactionupdatedesc', 'openstudio');
    $actions[tracking::UPDATE_CONTENT_VISIBILITY_GROUP]['title'] =
            get_string('contentactionvisibilitygroup', 'openstudio');;
    $actions[tracking::UPDATE_CONTENT_VISIBILITY_GROUP]['desc'] =
            $actions[tracking::UPDATE_CONTENT_VISIBILITY_GROUP]['title'];
    $actions[tracking::UPDATE_CONTENT_VISIBILITY_MODULE]['title'] =
            get_string('contentactionvisibilitymodule', 'openstudio');
    $actions[tracking::UPDATE_CONTENT_VISIBILITY_MODULE]['desc'] =
            $actions[tracking::UPDATE_CONTENT_VISIBILITY_MODULE];
    $actions[tracking::UPDATE_CONTENT_VISIBILITY_PRIVATE]['title'] =
            get_string('contentactionvisibilityprivate', 'openstudio');
    $actions[tracking::UPDATE_CONTENT_VISIBILITY_PRIVATE]['desc'] =
            $actions[tracking::UPDATE_CONTENT_VISIBILITY_PRIVATE]['title'];
    $actions[tracking::MODIFY_FOLDER]['title'] =
            get_string('contentactionfoldermodifyname', 'openstudio');
    $actions[tracking::MODIFY_FOLDER]['desc'] =
            get_string('contentactionfoldermodifydesc', 'openstudio');

    foreach ($slots as $slotid => $slot) {
        $studioid = 0;
        $levelcontainer = 0;
        $levelid = '';
        $slotname = '';
        $l1id = '';
        $l2id = '';
        $l3id = '';
        $l1name = '';
        $l2name = '';
        $l3name = '';
        $owner = '';
        $ownerid = '';
        $slottimemodified = '';
        $visibility = null;

        foreach ($slot as $key => $slotdata) {
            // Extract the slot name and leveldata and set it to each of the results coming from our
            // tracking and flag tables.
            if ($slotdata->openstudioid > 0) {
                $studioid = $slotdata->openstudioid;
            }
            if ($slotdata->levelcontainer > 0) {
                $levelcontainer = $slotdata->levelcontainer;
            }
            if ($slotdata->name != '') {
                $slotname = $slotdata->name;
            }
            if ($slotdata->l1id != '') {
                $l1id = $slotdata->l1id;
            }
            if ($slotdata->l2id != '') {
                $l2id = $slotdata->l2id;
            }
            if ($slotdata->l3id != '') {
                $l3id = $slotdata->l3id;
            }
            if ($slotdata->l1name != '') {
                $l1name = $slotdata->l1name;
            }
            if ($slotdata->l2name != '') {
                $l2name = $slotdata->l2name;
            }
            if ($slotdata->l3name != '') {
                $l3name = $slotdata->l3name;
            }
            if ($slotdata->visibility != '') {
                $visibility = $slotdata->visibility;
            }
            $slottimemodified = $slotdata->timemodified;
            if ($slotdata->actionid == 0 && $slotdata->flagid == 0 && $slotdata->commentid == 0) {
                $owner = $slotdata->firstname . ' ' . $slotdata->lastname;
                $ownerid = $slotdata->userid;
                $slottimemodified = $slotdata->timemodified;
                $levelid = $slotdata->levelid;
            }

            foreach ($rawslots[$slotid] as $rawslotdata) {
                // Check for the same action and only get the latest. Don't need to do this for flags AS
                // there won't be multiple entries for them.
                if ($slotdata->actionid > 0 && $slotdata->userid == $rawslotdata->userid
                        && $slotdata->actionid == $rawslotdata->actionid
                        && $slotdata->timemodified < $rawslotdata->timemodified
                ) {
                    // This will then ONLY leave the latest result with a matching user and actionid for us.
                    unset($slots[$slotid][$key]);
                }

            }
        }

        // Unfortunately, we have to loop through a second time to remove entry from studio_slots as we
        // don't need it to form our email.  We could not do this above as we may have lost the name!
        foreach ($slot as $key => $slotdata) {
            if (($slotdata->actionid == 0) && ($slotdata->flagid == 0) && ($slotdata->commentid == 0)) {
                unset($slots[$slotid][$key]);
            } else {
                $slotdata->l3name = $l3name;
                $slotdata->l2name = $l2name;
                $slotdata->l1name = $l1name;
                $slotdata->l3id = $l3id;
                $slotdata->l2id = $l2id;
                $slotdata->l1id = $l1id;
                $slotdata->name = $slotname;
                $slotdata->levelid = $levelid;
                $slotdata->openstudioid = $studioid;
                $slotdata->levelcontainer = $levelcontainer;
                $slotdata->owner = $owner;
                $slotdata->ownerid = $ownerid;
                $slotdata->slottimemodified = $slottimemodified;
                $slotdata->visibility = $visibility;

                if ($slotdata->flagid > 0) {
                    $slotdata->flagname = $flags[$slotdata->flagid]['title'];
                    $slotdata->flagdesc = $flags[$slotdata->flagid]['desc'];
                }

                if ($slotdata->actionid > 0) {
                    $slotdata->actionname = $actions[$slotdata->actionid]['title'];
                    $slotdata->actiondesc = $actions[$slotdata->actionid]['desc'];
                }
            }
        }
    }
    unset($rawslots); // This might help save on memory.

    // The function array_filter will remove the empty array() we may have left over from above.
    return array_filter($slots);
}

/**
 * Creates new subscription.
 *
 * @param int $subscriptiontype
 * @param int $userid
 * @param int $studioid
 * @param int $format
 * @param int $slotid
 * @param int $frequency
 * @return mixed Return id of created subscription, or false if error.
 */
function studio_api_notification_create_subscription(
        $subscriptiontype, $userid, $studioid, $format, $slotid = 0, $frequency = '') {

    global $DB;

    try {
        if ($frequency == '') {
            $frequency = subscription::FREQUENCY_DAILY;
        }

        if ($slotid == '') {
            $slotid = 0;
        }

        $insertdata = array();
        $insertdata['subscription'] = $subscriptiontype;
        $insertdata['userid'] = $userid;
        $insertdata['openstudioid'] = $studioid;
        $insertdata['contentid'] = $slotid;

        // Before inserting, let's check if a duplicate entry already exists.
        $subscriptionexists = $DB->get_record('openstudio_subscriptions', $insertdata);

        // Already exists, just update and return true.
        if ($subscriptionexists != false) {
            return studio_api_notification_update_subscription
            ($subscriptionexists->id, $format, $frequency);
        }

        // Otherwise, create the other fields and insert.
        $insertdata['frequency'] = $frequency;
        $insertdata['format'] = $format;
        $insertdata['timemodified'] = time();

        return $DB->insert_record('openstudio_subscriptions', $insertdata);
    } catch (Exception $e) {
        // Default to returning false.
    }

    return false;
}

/**
 * Update a subscription.
 *
 * @param int $subscriptionid
 * @param int $format
 * @param int $frequency
 * @param int $timeprocessed
 * @param int $userid
 * @param bool $checkpermissions
 * @return bool Return true if subscription is deleted.
 */
function studio_api_notification_update_subscription(
        $subscriptionid, $format = '', $frequency = '', $timeprocessed = '',
        $userid = null, $checkpermissions = false) {

    global $DB;

    $insertdata = array();
    $insertdata['id'] = $subscriptionid;

    if ($frequency != '') {
        $insertdata['frequency'] = $frequency;
    }

    if ($timeprocessed != '') {
        $insertdata['timeprocessed'] = time();
    }
    if ($format != '') {
        $insertdata['format'] = $format;
    }
    $insertdata['timemodified'] = time();

    try {
        $subscriptiondata = $DB->get_record('openstudio_subscriptions',
                array('id' => $subscriptionid), '*', MUST_EXIST);

        if ($checkpermissions) {
            if ($subscriptiondata->userid != $userid) {
                return false;
            }
        }

        if ($subscriptiondata != false) {
            // Update record.
            $result = $DB->update_record('openstudio_subscriptions', $insertdata);
        }

        if (!$result) {
            throw new Exception('Failed to update subscription.');
        }

        return true;
    } catch (Exception $e) {
        // Default to return false.
    }

    return false;
}

/**
 * Delete's a subscription.
 *
 * @param int $subscriptionid
 * @param int $userid
 * @param bool $checkpermissions
 * @return bool Return true if subscription is deleted.
 */
function studio_api_notification_delete_subscription(
        $subscriptionid, $userid = null, $checkpermissions = false) {

    global $DB;

    try {
        $subscriptiondata = $DB->get_record('openstudio_subscriptions',
                array('id' => $subscriptionid), '*', MUST_EXIST);

        if ($checkpermissions) {
            if ($subscriptiondata->userid != $userid) {
                return false;
            }
        }

        // Remove record.
        $result = $DB->delete_records('openstudio_subscriptions', array('id' => $subscriptionid));

        if ($result === false) {
            throw new Exception(get_string('errorfailedsoftdeletesubscription', 'openstudio'));
        }

        return true;
    } catch (Exception $e) {
        // Default to returning false.
    }

    return false;
}

/**
 * Formats DB results so it is easier to render email text.
 *
 * @param array $filteredslots
 * @return array Formatted slot records.
 */
function studio_api_notification_format_for_email($filteredslots) {
    $result = array();

    foreach ($filteredslots as $slotid => $slotrecords) {
        $result[$slotid]['flags'] = array();
        $result[$slotid]['comments'] = array();
        $result[$slotid]['actions'] = array();
        foreach ($slotrecords as $record) {
            if ($record->levelid == 0 && $record->levelcontainer == 0) {
                $pinboard = true;
            } else {
                $pinboard = false;
            }

            if ($record->flagid > 0) {
                $result[$slotid]['flags'][] = $record;
                $result[$slotid]['fullname'] = mod_openstudio\local\util::get_content_heading($record->l1name, $record->l2name,
                                $record->l3name, $pinboard, $record->l2hidelevel) . ' - ' . $record->name;
                $result[$slotid]['owner'] = $record->owner;
                $result[$slotid]['slottimemodified'] = $record->slottimemodified;
                $result[$slotid]['visibility'] = $record->visibility;

                // Prepare by each flag.
                $result[$slotid]['flagcounts'][$record->flagid][] = $record->flagname;
            }

            if ($record->commentid > 0) {
                $result[$slotid]['comments'][] = $record;
                $result[$slotid]['fullname'] = mod_openstudio\local\util::get_content_heading($record->l1name, $record->l2name,
                                $record->l3name, $pinboard, $record->l2hidelevel) . ' - ' . $record->name;
                $result[$slotid]['owner'] = $record->owner;
                $result[$slotid]['slottimemodified'] = $record->slottimemodified;
                $result[$slotid]['visibility'] = $record->visibility;
            }

            if ($record->actionid > 0) {
                $result[$slotid]['actions'][] = $record;
                $result[$slotid]['fullname'] = mod_openstudio\local\util::get_content_heading($record->l1name, $record->l2name,
                                $record->l3name, $pinboard, $record->l2hidelevel) . ' - ' . $record->name;
                $result[$slotid]['owner'] = $record->owner;
                $result[$slotid]['slottimemodified'] = $record->slottimemodified;
                $result[$slotid]['visibility'] = $record->visibility;
            }
        }
    }

    return $result;
}

/**
 * Verifies if subscription should be processed in this round of the cron.
 *
 * @param string $frequency Hourly or daily.
 * @param int $timeprocessed last processed time in UNIX timestamp.
 * @return bool Return true if processing should happen.
 */
function studio_api_notification_process_subscription_now($frequency, $timeprocessed) {
    if ($timeprocessed < 1) {
        // This has never been processed so certainly needs processing this round.
        return true;
    } else {
        if ($frequency == subscription::FREQUENCY_DAILY) {
            // Check if time processed was more than a day ago and if it was, return true, else return false.
            $cutofftime = time() - (1 * 60 * 60 * 24); // 24 hours.
            if ($timeprocessed < $cutofftime) {
                return true;
            } else {
                return false;
            }

        }

        if ($frequency == subscription::FREQUENCY_HOURLY) {
            // Check if time processed was more than an hour ago and if it was, return true, else return false.
            $cutofftime = time() - (1 * 60 * 60); // 1 hour.
            if ($timeprocessed < $cutofftime) {
                return true;
            } else {
                return false;
            }
        }
    }
}

/**
 * Processes subscription in subscription tables and send emails as needed.
 *
 * @param int $studioid Studio instance to restrict processing to.
 * @param int $frequency 0 is to process all subscriptions, otherwise subscription filter by frequency type.
 * @param int $processlimit 0 is unlimited, otherwise the number records that will be processed per studio, per execution.
 * @return bool Return true if processing is ok or false if no record was found for processing.
 */
function studio_api_notification_process_subscriptions($studioid = 0, $frequency = 0, $processlimit = 0) {
    global $CFG, $DB;

    // Required include containing the function to generate the email body.
    require_once("$CFG->dirroot/mod/studio/renderer.php");

    // Module context sql is defined here and is referenced in the for loop below.
    $modulecontextsql = <<<EOF
SELECT cm.id
  FROM {course_modules} cm,
       {modules} m
 WHERE m.name = ?
   AND cm.instance = ?
   AND m.id = cm.module

EOF;

    // Variable to record a log of the studios that was processed in this execution run.
    $uniquestudios = array();

    // If requested, restrict the subscriptions to process by studio instance id or/and subscription frequency type.
    $conditions = array();
    if ($studioid > 0) {
        $conditions['openstudioid'] = $studioid;
    }
    if ($frequency > 0) {
        $conditions['frequency'] = $frequency;
    }
    if (empty($conditions)) {
        $conditions = null;
    }

    // Order the subscription processing by timeprocessed, prioritising on subscription
    // records which have not been processed or have not been last processed
    // for the longest period.

    // The number of subscrition records to retrieve for processing per run is also limited by
    // the constant mod_openstudio\local\defaults::SUBSCRIPTIONTOPROCESSPERCRONRUN so that the job doesnt
    // consume too much server processing.

    $sortoder = 'COALESCE(timeprocessed, 0) ASC';

    // Fetch the subscription records to process.
    $rs = $DB->get_recordset('openstudio_subscriptions', $conditions, $sortoder,
            '*', 0, \mod_openstudio\local\util\defaults::SUBSCRIPTIONTOPROCESSPERCRONRUN);

    if ($rs->valid()) {
        $processcounts = array();
        foreach ($rs as $subscription) {
            // If the studioid is not cached, cache it.
            if (!mod_openstudio\local\util::cache_check('subscription_processed_studioid_' . $subscription->openstudioid)) {
                // Get the course data we need for the first time and cache it.
                // Get Course ID from studio record.
                $courseid = $DB->get_field("openstudio", 'course', array("id" => $subscription->openstudioid));

                // Get Course Code to add to subject line.
                $coursecode = $DB->get_field('course', 'shortname', array('id' => $courseid));
                mod_openstudio\local\util::cache_put('subscription_coursecode_for_' . $subscription->openstudioid, $coursecode);
                mod_openstudio\local\util::cache_put('subscription_processed_studioid_' . $subscription->openstudioid, true);
            } else {
                // If it is cached, just get the coursecode value.
                $coursecode = mod_openstudio\local\util::cache_get('subscription_coursecode_for_' . $subscription->openstudioid);
            }

            // Initialise processing count if necessary.
            if (!array_key_exists($subscription->openstudioid, $processcounts)) {
                $processcounts[$subscription->openstudioid] = 0;
            }

            // Check if number of records for a given studio instance exceeds $processlimit,
            // and if so, we dont process any more records for the studio instance.
            if (($processlimit > 0) && ($processcounts[$subscription->studioid] >= $processlimit)) {
                continue;
            }

            // Get and cache user info as needed.
            if (!mod_openstudio\local\util::cache_check('subscription_user_' . $subscription->userid)) {
                // If user does not exist in cache, cache the info we need.
                $userrecord = $DB->get_record("user", array("id" => $subscription->userid));
                $userdetails = new stdClass();
                $userdetails->id = $userrecord->id;
                $userdetails->firstname = $userrecord->firstname;
                $userdetails->lastname = $userrecord->lastname;
                $userdetails->firstnamephonetic = $userrecord->firstnamephonetic;
                $userdetails->lastnamephonetic = $userrecord->lastnamephonetic;
                $userdetails->middlename = $userrecord->middlename;
                $userdetails->alternatename = $userrecord->alternatename;
                $userdetails->email = $userrecord->email;
                $userdetails->mailformat = $userrecord->mailformat;
                mod_openstudio\local\util::cache_put('subscription_user_' . $subscription->userid, $userdetails);
            } else {
                // If user exists, just get data from the cache.
                $userdetails = mod_openstudio\local\util::cache_get('subscription_user_' . $subscription->userid);
            }
            if (!mod_openstudio\local\util::cache_check('openstudio' . $subscription->openstudioid . 'context')) {
                // Get Studio module class id. We'll need this for our context for capabilities.
                $params = array('openstudio', $subscription->openstudioid);
                $modulecontext = $DB->get_record_sql($modulecontextsql, $params);
                $context = context_module::instance($modulecontext->id);
                mod_openstudio\local\util::cache_put('openstudio' . $subscription->openstudioid . 'context', $context);
            } else {
                $context = mod_openstudio\local\util::cache_get('openstudio' . $subscription->openstudioid . 'context');
            }

            // Check if user has permissions for this to process.
            if (has_capability('mod/studio:viewothers', $context, $subscription->userid)) {
                // Set up unqiue studios to write log.
                $uniquestudios[$subscription->openstudioid] = $subscription->openstudioid;

                // Check if this falls within the timescale we are after.
                if (studio_api_notification_process_subscription_now(
                        $subscription->frequency, $subscription->timeprocessed)) {
                    // If the timeprocessed is empty, set to a low value for the query to run properly.
                    if ($subscription->timeprocessed == '' || $subscription->timeprocessed == null) {
                        $subscription->timeprocessed = 100;
                    }

                    // If we're here, this needs to be processed in this round.
                    // Determine Subscripton type and get data.
                    switch ($subscription->subscription) {
                        case subscription::GROUP:
                            $sdata = studio_api_notification_group($subscription->userid, $subscription->openstudioid,
                                    array(), array(), $subscription->timeprocessed);
                            $emailtype = 'stream';
                            $emailsubject = 'Studio: ' . $coursecode . ' - Latest Activity for your Group';
                            $vid = 2;
                            break;

                        case subscription::MODULE:
                            $sdata = studio_api_notification_module($subscription->userid, $subscription->openstudioid,
                                    array(), array(), $subscription->timeprocessed);
                            $emailtype = 'stream';
                            $emailsubject = 'Studio: ' . $coursecode . ' - Latest Activity for your Studio';
                            $vid = '';
                            break;

                        case subscription::CONTENT:
                            $sdata = studio_api_notification_slot($subscription->slotid,
                                    $subscription->timeprocessed, '', array(), array());
                            $emailtype = 'slot';
                            $emailsubject = 'Studio: ' . $coursecode . ' - Latest Activity for your Slot';
                            $vid = '';
                            break;
                    }

                    if ($sdata !== false) {
                        // Let's organize our sdata.
                        $slots = array();
                        foreach ($sdata as $data) {
                            // Get the slotid.
                            $slotid = $data->id;
                            $slots[$slotid][] = $data;
                        }
                        $filteredslots = studio_api_notification_filter_slots($slots);

                        // Let's get the content of the email.
                        $emailbody = mod_studio_renderer::studio_render_notification_email_body(
                                $filteredslots, $emailtype, $subscription->openstudioid,
                                $userdetails, $subscription->id, $vid, $subscription->subscription);
                        if ($emailbody !== false) {
                            if ($subscription->format == subscription::FORMAT_HTML) {
                                // OK, We have everything, let's generate and send the email.
                                $emailresult = email_to_user(
                                        $userdetails, $CFG->noreplyaddress, $emailsubject,
                                        $emailbody['plain'], $emailbody['html']);
                            }
                            if ($subscription->format == subscription::FORMAT_PLAIN) {
                                // OK, We have everything, let's generate and send the email.
                                $emailresult = email_to_user(
                                        $userdetails, $CFG->noreplyaddress, $emailsubject, $emailbody['plain']);
                            }
                        }
                    }

                    // Update the subscription entry.
                    studio_api_notification_update_subscription($subscription->id, '', '', time());

                    // Increment processing count for the studio instance.
                    $processcounts[$subscription->openstudioid] = $processcounts[$subscription->openstudioid] + 1;
                }
            } else {
                // Even though we havent really processed the user because of lack of permission,
                // we did process the record, so we update the subscription entry record to say it was processsed.
                studio_api_notification_update_subscription($subscription->id, '', '', time());
            }
        }

        // If recordset is valid, we also need to write a log of all studios we have sent-out mails for.
        foreach ($uniquestudios as $stid => $stsent) {
            // Get course id from $stid.
            $cm = get_coursemodule_from_id('openstudio', $stid);
            if ($cm !== false) {
                mod_openstudio\local\util::trigger_event($cm->id, 'subscription_sent', '',
                        "view.php?id={$stid}",
                        mod_openstudio\local\util::format_log_info($stid));
            }
        }

        $rs->close();
    }

    // Return true if any studio had subscription records that was fetched and processed.
    if (count($uniquestudios) > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if a user is subscribed to both module and group subs for the same studio.
 *
 * @param int $subscription object contaning ONE record from the studio_subscriptions table.
 * @return bool Return true of user has module and group subscription.
 */
function studio_api_notification_user_has_mod_and_group_subs($subscription) {
    global $DB;

    $sql = <<<EOF
SELECT *
  FROM {openstudio_subscriptions}
 WHERE openstudioid = ?
   AND userid = ?
   AND (contentid = ? OR contentid = NULL)

EOF;

    $params = array($subscription->studioid, $subscription->userid, 0);
    $result = $DB->get_records_sql($sql, $params);

    // The above combination of parameters CAN only produce a maximum of 2 results,
    // if it produces more, choke.
    //
    if (count($result) > 2) {
        // This means user is subcribed to both MODULE and GROUP streams.
        return false;
    } else {
        if (count($result) == 2) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * Following on from the above function, if the user is in both group and mod subscriptions,
 * this functions removes the duplicate slots before including them in the email.
 *
 * @param int $subscription
 * @param array $filteredslots
 * @return array Returned process slots.
 */
function studio_api_notification_remove_duplicate_slots_for_user_has_same_membership(
        $subscription, $filteredslots) {

    if ($subscription->subscription == subscription::MODULE) {
        // Check if we need to filter out any slots.
        if (studio_api_notification_user_has_mod_and_group_subs($subscription)) {
            // User is subcribed to both MODULE and GROUP streams for this studio.
            // Now let's check if the user who has subscribed and the slotowner of
            // any of the slots above are in the same group.
            // If they are, filter out the common slots.
            $groupmembers = array();
            $courseid = null;
            $groupmode = null;
            $groupingid = null;
            foreach ($filteredslots as $slotid => $slotrecords) {
                // Get the ownerid.
                $slotownerid = $slotrecords[key($slotrecords)]->ownerid;
                if ((int) $slotownerid <= 0) {
                    continue;
                }

                // Get the course/groupmode/groupingid associated with the slot; do this once only.
                if ($courseid == null) {
                    $cm = mod_openstudio\local\util::get_coursemodule_from_studioid($slotdata->studioid);
                    if ($cm === false) {
                        // If we cant find the course id, which shouldnt be possible anyway, then
                        // set courseid to zero so that any permission check using it will fail anyway.
                        $courseid = 0;
                        $groupmode = 0;
                        $groupingid = 0;
                    } else {
                        $courseid = $cm->course;
                        $groupmode = $cm->groupmode;
                        $groupingid = $cm->groupingid;
                    }
                }

                // Now check to make sure that $slotownerid and $subscription->userid
                // are NOT part of the same group.
                // If the cache does not exist, this is our first slot, DO the check.
                if (!mod_openstudio\local\util::cache_check('samegroupmembers' . $subscription->userid)) {
                    $ismember = studio_api_group_is_slot_group_member(
                            $groupmode,
                            $slotrecords[key($slotrecords)]->visibility,
                            $groupingid, $slotownerid, $subscription->userid);
                    if ($ismember) {
                        // If they are, remove this slot from the results as the subscribed user will
                        // see ALSO see it in the module subscription.
                        unset($filteredslots[$slotid]);

                        // Also add this slotowner to the list of users in the same group.
                        $groupmembers[] = $slotownerid;
                        mod_openstudio\local\util::cache_put('samegroupmembers' . $subscription->userid, $groupmembers);
                    }
                } else {
                    // If the cache does exist.
                    // Get the memberships and check if the slotowner is part of them.
                    $memberships = mod_openstudio\local\util::cache_get('samegroupmembers' . $subscription->userid);
                    if (in_array($slotownerid, $memberships)) {
                        // If the slotowner already exists in memberships just unset the slot.
                        unset($filteredslots[$slotid]);
                    } else {
                        // If the slotowner does not exist, we need to check in the database.
                        // If this newer user is part of the same group, remove the slot and add
                        // this user to the cache too.
                        $ismember = studio_api_group_is_slot_group_member(
                                $groupmode,
                                $slotrecords[key($slotrecords)]->visibility,
                                $groupingid, $slotownerid, $subscription->userid);
                        if ($ismember) {
                            // If they are, remove this slot from the results as the subscribed user will
                            // see ALSO see it in the module subscription.
                            unset($filteredslots[$slotid]);

                            // Also add this slotowner to the list of users in the same group.
                            $groupmembers[] = $slotownerid;
                            mod_openstudio\local\util::cache_put('samegroupmembers' . $subscription->userid, $groupmembers);
                        }
                    }
                }
            }

        }
    }

    return $filteredslots;
}
