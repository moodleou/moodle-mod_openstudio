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
 * API functions for RSS and Atom feeds.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

use mod_openstudio\local\util;

/**
 * RSS API Functions
 *
 * This code has been only minimally refactored from openstudio V1, so may still use terms like "slot".
 * RSS feeds are not currently used in mod_openstudio, but the API has been kept in case we want to implement them in the future.
 */
class rss {

    const PINBOARD = 0;
    const ACTIVITY = 1;
    const GROUP = 2;
    const MODULE = 3;
    const CONTENT = 4;

    /**
     * Gerenate SHA1 key from userid and feedtype.
     *
     * @param int $userid
     * @param int $feedtype
     * @return strng Return generated key.
     */
    public static function generate_key($userid, $feedtype) {
        $keysalt = 'There_is_a_beast_in_every_man_,_and_it_stirs_when_you_put_a_sword_in_his_hand';

        // Return our key.
        return sha1($userid . '_' . $feedtype . '_' . $keysalt);
    }

    /**
     * Validate SHA1 key against userid and feedtype.
     *
     * @param int $key
     * @param int $userid
     * @param int $feedtype
     * @return strng Return true if key is valid.
     */
    public static function validate_key($key, $userid, $feedtype) {
        $correctkey = self::generate_key($userid, $feedtype);
        if ($correctkey == $key) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get Pinboard RSS.
     *
     * @param int $userid
     * @param int $studioid
     * @param int $limitfrom
     * @param int $limitnum
     * @param int $time
     * @param array $flags
     * @return strng Return true if key is valid.
     */
    public static function pinboard(
            $userid, $studioid, $limitfrom = 0, $limitnum = 25, $time = '', array $flags = null) {

        global $DB;

        if ($time == '') {
            $time = time();
        }

        if (empty($flags)) {
            $flags = array(1);
        }

        // Prepare flags.
        list($flagssql, $flagsparams) = $DB->get_in_or_equal($flags, SQL_PARAMS_QM, 'param', false);
        $flagssql = "AND {openstudio_flags}.flagid $flagssql";

        // Prepare SQL.
        // Visbility is irrelevant as this pertains to a specific user only.
        $sql = <<<EOF
        SELECT {openstudio_contents}.id,
                max({openstudio_contents}.name) as name,
                max({openstudio_contents}.content) as content,
                max({openstudio_contents}.mimetype) as mimetype,
                max({openstudio_contents}.thumbnail) as thumbnail,
                max({openstudio_contents}.description) as description,
                max({openstudio_contents}.levelid) as levelid,
                max({openstudio_contents}.userid) as userid,
                max({openstudio_contents}.openstudioid) as studioid,
                max({openstudio_contents}.levelcontainer) as levelcontainer,
                max(COALESCE({openstudio_contents}.timemodified,
                {openstudio_flags}.timemodified,
                {openstudio_comments}.timemodified)) as timemodified,
                max({user}.firstname) as firstname,
                max({user}.lastname) as lastname,
                max(l1.id) as l1id,
                max(l1.name) as l1name,
                max(l2.id) as l2id,
                max(l2.name) as l2name,
                max(l2.hidelevel) as l2hidelevel,
                max(l3.id) as l3id,
                max(l3.name) as l3name
           FROM {openstudio_contents}
     INNER JOIN {user} ON {user}.id = {openstudio_contents}.userid
      LEFT JOIN {openstudio_flags} ON {openstudio_flags}.contentid = {openstudio_contents}.id
      LEFT JOIN {openstudio_comments} ON {openstudio_comments}.contentid = {openstudio_contents}.id
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = {openstudio_contents}.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
          WHERE (   {openstudio_flags}.timemodified <= ?
                 OR {openstudio_contents}.timemodified <= ?
                 OR {openstudio_comments}.timemodified <= ?
                )

{$flagssql}

            AND {openstudio_contents}.levelid = ?
            AND {openstudio_contents}.levelcontainer = ?
            AND {openstudio_contents}.openstudioid = ?
            AND {openstudio_contents}.userid = ?
            AND {openstudio_contents}.contenttype != ?
       GROUP BY {openstudio_contents}.id
       ORDER BY timemodified DESC

EOF;

        // Prepare Params.
        $params[] = $time;
        $params[] = $time;
        $params[] = $time;
        foreach ($flags as $flag) {
            $params[] = $flag;
        }
        $params[] = 0;
        $params[] = 0;
        $params[] = $studioid;
        $params[] = $userid;
        $params[] = content::TYPE_NONE;

        try {
            $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
            if ($rs->valid()) {
                return $rs;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Get My Workspace RSS.
     *
     * @param int $userid
     * @param int $studioid
     * @param int $limitfrom
     * @param int $limitnum
     * @param int $time
     * @param array $flags
     * @return strng Return true if key is valid.
     */
    public static function activity(
            $userid, $studioid, $limitfrom = 0, $limitnum = 25, $time = '', array $flags = null) {

        global $DB;

        if ($time == '') {
            $time = time();
        }

        if (empty($flags)) {
            $flags = array(1);
        }

        // Prepare flags.
        list($flagssql, $flagsparams) = $DB->get_in_or_equal($flags, SQL_PARAMS_QM, 'param', false);
        $flagssql = "AND {openstudio_flags}.flagid $flagssql";

        // Prepare SQL. This will bring back a user's slots at levelcontainer = 3 for a specific studio.
        // Visbility is irrelevant as this pertains to a specific user only.
        $sql = <<<EOF
         SELECT {openstudio_contents}.id,
                max({openstudio_contents}.name) as name,
                max({openstudio_contents}.content) as content,
                max({openstudio_contents}.mimetype) as mimetype,
                max({openstudio_contents}.thumbnail) as thumbnail,
                max({openstudio_contents}.description) as description,
                max({openstudio_contents}.levelid) as levelid,
                max({openstudio_contents}.userid) as userid,
                max({openstudio_contents}.openstudioid) as studioid,
                max({openstudio_contents}.levelcontainer) as levelcontainer,
                max(COALESCE({openstudio_contents}.timemodified,
                {openstudio_flags}.timemodified,
                {openstudio_comments}.timemodified)) as timemodified,
                max({openstudio_contents}.visibility) as visibility,
                max({user}.firstname) as firstname,
                max({user}.lastname) as lastname,
                max(l1.id) as l1id,
                max(l1.name) as l1name,
                max(l2.id) as l2id,
                max(l2.name) as l2name,
                max(l2.hidelevel) as l2hidelevel,
                max(l3.id) as l3id,
                max(l3.name) as l3name,
                max({user}.firstname) as firstname,
                max({user}.lastname) as lastname
           FROM {openstudio_contents}
     INNER JOIN {user} ON {user}.id = {openstudio_contents}.userid
      LEFT JOIN {openstudio_flags} ON {openstudio_flags}.contentid = {openstudio_contents}.id
      LEFT JOIN {openstudio_comments} ON {openstudio_comments}.contentid = {openstudio_contents}.id
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = {openstudio_contents}.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
          WHERE (   {openstudio_flags}.timemodified <= ?
                 OR {openstudio_contents}.timemodified <= ?
                 OR {openstudio_comments}.timemodified <= ?
                )

{$flagssql}

            AND {openstudio_contents}.levelcontainer = ?
            AND {openstudio_contents}.openstudioid = ?
            AND {openstudio_contents}.userid = ?
            AND {openstudio_contents}.contenttype != ?
       GROUP BY {openstudio_contents}.id
       ORDER BY timemodified DESC

EOF;

        // Prepare Params.
        $params[] = $time;
        $params[] = $time;
        $params[] = $time;
        foreach ($flags as $flag) {
            $params[] = $flag;
        }
        $params[] = 3;
        $params[] = $studioid;
        $params[] = $userid;
        $params[] = content::TYPE_NONE;

        try {
            $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
            if ($rs->valid()) {
                return $rs;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Get Group RSS.
     *
     * @param int $userid
     * @param int $studioid
     * @param int $ownerid
     * @param array $tutorroles
     * @param int $limitfrom
     * @param int $limitnum
     * @param int $time
     * @param array $flags
     * @return strng Return true if key is valid.
     */
    public static function group(
            $userid, $studioid, $ownerid = '', array $tutorroles = array(),
                $limitfrom = 0, $limitnum = 25, $time = '', array $flags = null) {

        global $DB;

        if ($time == '') {
            $time = time();
        }

        // This is flags NOT IN.
        if (empty($flags)) {
            $flags = array(1);
        }

        // Prepare flags.
        list($flagssql, $flagsparams) = $DB->get_in_or_equal($flags, SQL_PARAMS_QM, 'param', false);
        $flagssql = "AND {openstudio_flags}.flagid $flagssql";

        // Get groupmode/groupidid from studioid.
        $cm = util::get_coursemodule_from_studioid($studioid);
        if ($cm === false) {
            return false;
        }
        if (($cm->groupmode > 0) && ($cm->groupingid > 0)) {
            $groupmode = $cm->groupmode;
            $groupingid = $cm->groupingid;
        } else {
            return false;
        }

        // Prepare SQL.
        $sql = <<<EOF
         SELECT s.id,
                max(s.name) as name,
                max(s.content) as content,
                max(s.mimetype) as mimetype,
                max(s.thumbnail) as thumbnail,
                max(s.description) as description,
                max(s.levelid) as levelid,
                max(s.levelcontainer) as levelcontainer,
                max(s.userid) as userid,
                max(s.openstudioid) as studioid,
                max(s.levelcontainer) as levelcontainer,
                max(COALESCE(s.timemodified,
                {openstudio_flags}.timemodified,
                {openstudio_comments}.timemodified)) as timemodified,
                max(s.visibility) as visibility,
                max({user}.firstname) as firstname,
                max({user}.lastname) as lastname,
                max(l1.id) as l1id,
                max(l1.name) as l1name,
                max(l2.id) as l2id,
                max(l2.name) as l2name,
                max(l2.hidelevel) as l2hidelevel,
                max(l3.id) as l3id,
                max(l3.name) as l3name
           FROM {openstudio_contents} s
     INNER JOIN {user} ON {user}.id = s.userid
      LEFT JOIN {openstudio_flags} ON {openstudio_flags}.contentid = s.id
      LEFT JOIN {openstudio_comments} ON {openstudio_comments}.contentid = s.id
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = s.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
          WHERE (   {openstudio_flags}.timemodified <= ?
                 OR s.timemodified <= ?
                 OR {openstudio_comments}.timemodified <= ?
                )

{$flagssql}

            AND s.openstudioid = ?
            AND s.contenttype != ?
            AND

EOF;

        $params[] = $time;
        $params[] = $time;
        $params[] = $time;
        foreach ($flags as $flag) {
            $params[] = $flag;
        }
        $params[] = $studioid;
        $params[] = content::TYPE_NONE;

        if ($userid != $ownerid && $ownerid != '') {
            $sql .= 's.userid = ? AND ';
            $params[] = $ownerid;
        }

        $tutorparams = array();
        $tutorpermissionsql = '';
        if (!empty($tutorroles)) {
            // The following is only applicable if content::VISIBILITY_TUTOR is enabled.
            // This is specific to the OU's concept of a "tutor".
            list($rolesql, $roleparams) = $DB->get_in_or_equal($tutorroles);
            $tutorpermissionsql = <<<EOF
            OR (
                s.visibility = ?
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
                       AND s1.id = s.id
                       AND tgm2.userid = ?
                )
            )
EOF;
            $tutorparams[] = content::VISIBILITY_TUTOR;
            $tutorparams = array_merge($tutorparams, $roleparams);
            $tutorparams[] = $userid;
            $tutorparams[] = $userid;
        }

        $sql .= <<<EOF
(
    (
        (
            (
                2 = ?
                AND (
                    s.visibility = ?
                    OR s.visibility = ?
                    OR s.visibility < 0
                )
            ) OR (
                2 <> ?
                AND (
                    s.visibility = ?
                    OR s.visibility = ?
                )
            )
        ) AND EXISTS (
            SELECT 1
            FROM {groups_members} gm1
            JOIN {groupings_groups} gg1 ON gg1.groupid = gm1.groupid AND gg1.groupingid = ?
            WHERE gm1.userid = s.userid
        ) AND EXISTS (
            SELECT 1
            FROM {groups_members} gm2
            JOIN {groupings_groups} gg2 ON gg2.groupid = gm2.groupid AND gg2.groupingid = ?
            WHERE gm2.userid = ?
        )
    ) OR (
        (
            2 <> ? AND s.visibility < 0
        ) AND EXISTS (
            SELECT 1
            FROM {groups_members} gm3
            JOIN {groups_members} gm4 ON gm4.groupid = gm3.groupid AND gm4.userid = ?
            WHERE gm3.groupid = (0 - s.visibility)
            AND gm3.userid = s.userid
        )
    ) {$tutorpermissionsql}
)
GROUP BY s.id
ORDER BY timemodified DESC

EOF;

        $params[] = $groupmode;
        $params[] = content::VISIBILITY_MODULE;
        $params[] = content::VISIBILITY_GROUP;
        $params[] = $groupmode;
        $params[] = content::VISIBILITY_MODULE;
        $params[] = content::VISIBILITY_GROUP;
        $params[] = $groupingid;
        $params[] = $groupingid;
        $params[] = $userid;
        $params[] = $groupmode;
        $params[] = $userid;
        $params = array_merge($params, $tutorparams);
        try {
            $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
            if ($rs->valid()) {
                return $rs;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Get Module RSS.
     *
     * @param int $userid
     * @param int $studioid
     * @param int $limitfrom
     * @param int $limitnum
     * @param int $time
     * @param array $flags
     * @return strng Return true if key is valid.
     */
    public static function module(
            $userid, $studioid, $ownerid = '', $limitfrom = 0, $limitnum = 25, $time = '', array $flags = null) {

        global $DB;

        if ($time == '') {
            $time = time();
        }

        if (empty($flags)) {
            $flags = array(1);
        }

        // Prepare flags.
        list($flagssql, $flagsparams) = $DB->get_in_or_equal($flags, SQL_PARAMS_QM, 'param', false);
        $flagssql = "AND {openstudio_flags}.flagid $flagssql";

        // Prepare SQL.
        $sql = <<<EOF
        SELECT {openstudio_contents}.id,
                max({openstudio_contents}.name) as name,
                max({openstudio_contents}.content) as content,
                max({openstudio_contents}.mimetype) as mimetype,
                max({openstudio_contents}.thumbnail) as thumbnail,
                max({openstudio_contents}.description) as description,
                max({openstudio_contents}.levelid) as levelid,
                max({openstudio_contents}.userid) as userid,
                max({openstudio_contents}.openstudioid) as studioid,
                max({openstudio_contents}.levelcontainer) as levelcontainer,
                max(COALESCE({openstudio_contents}.timemodified,
                {openstudio_flags}.timemodified,
                {openstudio_comments}.timemodified)) as timemodified,
                max({openstudio_contents}.visibility) as visibility,
                max({user}.firstname) as firstname,
                max({user}.lastname) as lastname,
                max(l1.id) as l1id,
                max(l1.name) as l1name,
                max(l2.id) as l2id,
                max(l2.name) as l2name,
                max(l2.hidelevel) as l2hidelevel,
                max(l3.id) as l3id,
                max(l3.name) as l3name
           FROM {openstudio_contents}
     INNER JOIN {user} ON {user}.id = {openstudio_contents}.userid
      LEFT JOIN {openstudio_flags} ON {openstudio_flags}.contentid = {openstudio_contents}.id
      LEFT JOIN {openstudio_comments} ON {openstudio_comments}.contentid = {openstudio_contents}.id
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = {openstudio_contents}.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
          WHERE (   {openstudio_flags}.timemodified <= ?
                 OR {openstudio_contents}.timemodified <= ?
                 OR {openstudio_comments}.timemodified <= ?
                )

{$flagssql}

            AND {openstudio_contents}.openstudioid = ?
            AND {openstudio_contents}.contenttype != ?
            AND

EOF;

        $params[] = $time;
        $params[] = $time;
        $params[] = $time;
        foreach ($flags as $flag) {
            $params[] = $flag;
        }
        $params[] = $studioid;
        $params[] = content::TYPE_NONE;

        if ($userid != $ownerid && $ownerid != '') {
            $sql .= '{openstudio_contents}.userid = ?  AND ';
            $params[] = $ownerid;
        }

        $sql .= <<<EOF
(           {openstudio_contents}.visibility = ?
 AND EXISTS (SELECT 1
               FROM {user_enrolments} ue1
               JOIN {enrol} e1 ON e1.id = ue1.enrolid
              WHERE ue1.userid = {openstudio_contents}.userid
         AND EXISTS (SELECT 1
                       FROM {user_enrolments} ue2
                       JOIN {enrol} e2 ON e2.id = ue2.enrolid AND e2.courseid = e1.courseid
                      WHERE ue2.userid = ?))
)
GROUP BY {openstudio_contents}.id
ORDER BY timemodified DESC

EOF;

        $params[] = content::VISIBILITY_MODULE;
        $params[] = $userid;

        try {
            $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
            if ($rs->valid()) {
                return $rs;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Get Slot RSS.
     *
     * @param int $slotid
     * @param int $limitfrom
     * @param int $limitnum
     * @param int $time
     * @param array $flags
     * @return strng Return true if key is valid.
     */
    public static function slot($slotid, $limitfrom = 0, $limitnum = 25, $time = '', array $flags = null) {
        global $DB;

        if ($time == '') {
            $time = time();
        }

        // This is falgs IN.
        if (empty($flags)) {
            $flags = array(2, 3, 4, 5);
        }

        // Prepare flags.
        list($flagssql, $flagsparams) = $DB->get_in_or_equal($flags);
        $flagssql = "AND {openstudio_flags}.flagid $flagssql";

        // Prepare SQL.
        $sql = <<<EOF
     SELECT comboquery.id,
            comboquery.timemodified,
            comboquery.name,
            comboquery.description,
            comboquery.flagid as flagid,
            comboquery.userid as userid,
            comboquery.levelid as levelid,
            comboquery.levelid as levelcontainer,
            comboquery.commentid as commentid,
             comboquery.commenttext as commenttext,
            {user}.firstname as firstname,
            {user}.lastname as lastname,
            l1.id as l1id,
            l1.name as l1name,
            l2.id as l2id,
            l2.name as l2name,
            l2.hidelevel as l2hidelevel,
            l3.id as l3id,
            l3.name as l3name
       FROM (
                    (SELECT id,
                            name,
                            description,
                            timemodified,
                            0 as flagid,
                            userid,
                            levelid,
                            levelcontainer,
                            0 as commentid,
                            NULL as commenttext
                       FROM {openstudio_contents}
                      WHERE id = ?)

            UNION ALL

                     (SELECT contentid as id,
                             NULL as name,
                             NULL as description,
                             timemodified,
                             flagid,
                             userid,
                             0 as levelid,
                             0 as levelcontainer,
                             0 as commentid,
                             NULL as commenttext
                        FROM {openstudio_flags}
                       WHERE contentid = ?
                         AND timemodified <= ?


EOF;

        $sql .= $flagssql;

        $sql .= <<<EOF
              )

                UNION ALL

                     (SELECT contentid as id,
                             NULL as name,
                             NULL as description,
                             timemodified,
                             0 as flagid,
                             userid,
                             0 as levelid,
                             0 as levelcontainer,
                             id as commentid,
                             commenttext
                        FROM {openstudio_comments}
                       WHERE contentid = ?
                         AND deletedby IS NOT NULL
                         AND timemodified <= ?)

                ) as comboquery

     INNER JOIN {user} ON {user}.id = comboquery.userid
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = comboquery.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
       ORDER BY comboquery.timemodified DESC

EOF;

        $params[] = $slotid;
        $params[] = $slotid;
        $params[] = $time;
        foreach ($flags as $flag) {
            $params[] = $flag;
        }
        $params[] = $slotid;
        $params[] = $time;

        try {
            $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
            if ($rs->valid()) {
                return $rs;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}