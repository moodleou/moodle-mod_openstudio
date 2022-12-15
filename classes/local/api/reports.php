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
 * Reports API
 *
 * @package mod_studio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

/**
 * Reports API functions
 *
 * This code has been only minimally refactored from openstudio V1, so may still use terms like "slot".
 */
class reports {

    /**
     * Extracts summary of activity data for a given studio instance.
     *
     * @param int $courseid
     * @param int $studioid
     * @return object Returns activity summary data.
     */
    public static function get_activity_summary($courseid, $studioid) {
        global $DB;

        $sql = <<<EOF
SELECT COUNT(DISTINCT ue.userid)
  FROM {user_enrolments} ue
  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ?

EOF;

        $registeredusers = $DB->get_field_sql($sql, array($courseid));

        $sql = <<<EOF
SELECT COUNT(DISTINCT s.userid)
  FROM {openstudio_contents} s
 WHERE s.openstudioid = ?

EOF;

        $activeusers = $DB->get_field_sql($sql, array($studioid));

        $sql = <<<EOF
SELECT AVG(usertotals.total)
  FROM (SELECT s.userid, COUNT(s.id) AS total
          FROM {openstudio_contents} s
         WHERE s.openstudioid = ?
           AND s.deletedby IS NULL
      GROUP BY s.userid) AS usertotals

EOF;

        $averagecontentperuser = round($DB->get_field_sql($sql, array($studioid)));

        $sql = <<<EOF
SELECT AVG(usertotals.total)
  FROM (SELECT s.userid, COUNT(s.id) AS total
          FROM {openstudio_contents} s
         WHERE s.openstudioid = ?
           AND s.deletedby IS NULL
           AND s.levelcontainer = 0
      GROUP BY s.userid) AS usertotals

EOF;

        $averagepinboardcontentperuser = round($DB->get_field_sql($sql, array($studioid)));

        $sql = <<<EOF
SELECT AVG(usertotals.total)
  FROM (SELECT s.userid, COUNT(s.id) AS total
          FROM {openstudio_contents} s
         WHERE s.openstudioid = ?
           AND s.levelcontainer > 0
      GROUP BY s.userid) AS usertotals

EOF;

        $averageactivitycontentperuser = round($DB->get_field_sql($sql, array($studioid)));

        $sql = <<<EOF
SELECT COUNT(s.id) as slots,
       COUNT(s.deletedby) slotsdeleted
  FROM {openstudio_contents} s
 WHERE s.openstudioid = ?

EOF;

        $slots = $DB->get_record_sql($sql, array($studioid));

        $sql = <<<EOF
SELECT COUNT(DISTINCT sv.id) as slotversions,
       COUNT(sv.deletedby) as slotversionsdeleted
  FROM {openstudio_content_versions} sv
  JOIN {openstudio_contents} s ON s.id = sv.contentid
 WHERE s.openstudioid = ?
   AND s.deletedby IS NULL

EOF;

        $slotversions = $DB->get_record_sql($sql, array($studioid));

        $sql = <<<EOF
SELECT COUNT(DISTINCT sc.id) as slotcomments,
       COUNT(sc.deletedby) as slotcommentsdeleted
  FROM {openstudio_comments} sc
  JOIN {openstudio_contents} s ON s.id = sc.contentid
 WHERE s.openstudioid = ?
   AND s.deletedby IS NULL

EOF;

        $result = $DB->get_record_sql($sql, array($studioid));
        $slotcomments = 0;
        $slotcommentsdeleted = 0;
        if ($result) {
            $slotcomments = $result->slotcomments;
            $slotcommentsdeleted = $result->slotcommentsdeleted;
        }

        $sql = <<<EOF
SELECT COUNT(DISTINCT sc.id) as slotaudiocomments
  FROM {openstudio_contents} s
  JOIN {openstudio_comments} sc ON sc.contentid = s.id AND sc.deletedby IS NULL
  JOIN {files} f ON f.itemid = sc.id AND f.component = 'mod_openstudio' AND f.filearea = 'contentcomment'
 WHERE s.openstudioid = ?
   AND s.deletedby IS NULL

EOF;

        $result = $DB->get_record_sql($sql, array($studioid));
        $slotaudiocomments = 0;
        if ($result) {
            $slotaudiocomments = $result->slotaudiocomments;
        }

        $slotcomments = (object) array(
                'slotcomments' => $slotcomments,
                'slotcommentsdeleted' => $slotcommentsdeleted,
                'slotaudiocomments' => $slotaudiocomments
        );

        return (object) array(
                'registeredusers' => $registeredusers,
                'activeusers' => $activeusers,
                'averagecontentperuser' => $averagecontentperuser,
                'averagepinboardcontentperuser' => $averagepinboardcontentperuser,
                'averageactivitycontentperuser' => $averageactivitycontentperuser,
                'slots' => $slots,
                'slotversions' => $slotversions,
                'slotcomments' => $slotcomments);
    }

    /*
     * Extracts summary of slot activity data for a given studio instance.
     *
     * @param int $studioid
     * @return object Returns slot data.
     */
    public static function get_total_slots($studioid) {
        global $DB;

        $sql = <<<EOF
  SELECT s.openstudioid,
         s.levelid,
         s.levelcontainer,
         s.contenttype,
         COUNT(DISTINCT s.userid) AS totalusers,
         COUNT(s.id) AS totalslots
    FROM {openstudio_contents} s
   WHERE s.openstudioid = ?
     AND s.deletedby IS NULL
GROUP BY s.openstudioid,
         s.levelid,
         s.levelcontainer,
         s.contenttype
ORDER BY s.levelcontainer,
         s.levelid,
         COUNT(s.id) DESC

EOF;

        $slotactivity = $DB->get_recordset_sql($sql, array($studioid));

        $sql = <<<EOF
  SELECT s.openstudioid,
         s.levelcontainer,
         s.levelid,
         s.visibility,
         COUNT(s.id) AS totalslots
    FROM {openstudio_contents} s
   WHERE s.openstudioid = ?
     AND s.deletedby IS NULL
     AND s.visibility > 0
GROUP BY s.openstudioid,
         s.levelcontainer,
         s.levelid,
         s.visibility
UNION
  SELECT s.openstudioid,
         s.levelcontainer,
         s.levelid,
         -1,
         COUNT(s.id) AS totalslots
    FROM {openstudio_contents} s
   WHERE s.openstudioid = ?
     AND s.deletedby IS NULL
     AND s.visibility < 0
GROUP BY s.openstudioid,
         s.levelcontainer,
         s.levelid,
         s.visibility
ORDER BY levelcontainer,
         levelid,
         totalslots DESC

EOF;

        $slotvisbility = $DB->get_recordset_sql($sql, array($studioid, $studioid));

        return (object) array(
                'slotactivity' => $slotactivity,
                'slotvisbility' => $slotvisbility);
    }

    /*
     * Extracts summary of flagging activity data for a given studio instance.
     *
     * @param int $studioid
     * @return object Returns flag data.
     */
    public static function get_total_flags($studioid) {
        global $DB;

        $sql = <<<EOF
  SELECT f.flagid,
         COUNT(DISTINCT f.contentid) AS totalslots,
         COUNT(DISTINCT f.userid) AS totalusers
    FROM {openstudio_flags} f
    JOIN {openstudio_contents} s ON s.id = f.contentid
   WHERE s.openstudioid = ?
     AND s.deletedby IS NULL
     AND f.personid = 0
GROUP BY f.flagid
ORDER BY COUNT(DISTINCT f.contentid) DESC

EOF;

        $slots = $DB->get_recordset_sql($sql, array($studioid));

        $sql = <<<EOF
   SELECT f.contentid,
          u.firstname,
          u.lastname,
          s.levelid,
          s.levelcontainer,
          s.name,
          COUNT(DISTINCT f.id) + COUNT(DISTINCT sc.id) AS totals
     FROM {openstudio_flags} f
     JOIN {openstudio_contents} s ON s.id = f.contentid
     JOIN {user} u ON u.id = f.userid
LEFT JOIN {openstudio_comments} sc ON sc.contentid = f.contentid
    WHERE s.openstudioid = ?
      AND s.deletedby IS NULL
      AND f.flagid != ?
      AND f.personid = 0
 GROUP BY f.contentid,
          u.firstname,
          u.lastname,
          s.levelid,
          s.levelcontainer,
          s.name
 ORDER BY COUNT(DISTINCT f.id) + COUNT(DISTINCT sc.id) DESC

EOF;

        $slotstop20 = $DB->get_recordset_sql($sql, array($studioid, flags::READ_CONTENT), 0, 20);
        return (object) [
                'slots' => $slots,
                'slotstop20' => $slotstop20
        ];
    }

    /*
     * Extracts storage usage for a given studio instance.
     *
     * @param int $studioid
     * @return object Returns storage data.
     */
    public static function get_total_storage($studioid) {
        global $DB;

        $commenttext = comments::COMMENT_TEXT_AREA;

        $sql = <<<EOF
  SELECT s.mimetype,
         COUNT(s.id) AS totals,
         SUM(f.filesize) as storage
    FROM {openstudio_contents} s
    JOIN {files} f ON f.itemid = s.fileid AND f.component = 'mod_openstudio'
   WHERE s.openstudioid = ?
     AND s.fileid IS NOT NULL
GROUP BY s.mimetype
ORDER BY SUM(f.filesize) DESC, COUNT(s.id) DESC

EOF;

        $storagebymimetype = $DB->get_recordset_sql($sql, array($studioid));

        $sql = <<<EOF
  SELECT SUM(f.filesize) as storage
    FROM {openstudio_contents} s
    JOIN {files} f ON f.itemid = s.fileid AND f.component = 'mod_openstudio'
                                          AND f.filearea = 'contentthumbnail'
   WHERE s.openstudioid = ?
     AND s.fileid IS NOT NULL

EOF;

        $storagebythumbnail = $DB->get_field_sql($sql, array($studioid));

        $sql = <<<EOF
  SELECT SUM(f.filesize) as storage
    FROM {openstudio_contents} s
    JOIN {files} f ON f.itemid = s.fileid AND f.component = 'mod_openstudio'
                                          AND f.filearea = 'content'
   WHERE s.openstudioid = ?
     AND s.fileid IS NOT NULL

EOF;

        $storagebyslot = $DB->get_field_sql($sql, array($studioid));

        $sql = <<<EOF
  SELECT SUM(f.filesize) as storage
    FROM {openstudio_content_versions} sv
    JOIN {openstudio_contents} s ON s.id = sv.contentid
    JOIN {files} f ON f.itemid = sv.fileid AND f.component = 'mod_openstudio'
                                           AND f.filearea = 'content'
   WHERE s.openstudioid = ?
     AND sv.fileid IS NOT NULL

EOF;

        $storagebyslotversion = $DB->get_field_sql($sql, array($studioid));

        $sql = <<<EOF
  SELECT SUM(f.filesize) as storage
    FROM {openstudio_contents} s
    JOIN {openstudio_comments} c on c.contentid = s.id
    JOIN {files} f ON f.itemid = c.id AND f.component = 'mod_openstudio'
                                      AND f.filearea IN (
                                                            'contentcomment', '$commenttext'
                                                        )
   WHERE s.openstudioid = ?

EOF;

        $storagebycomment = $DB->get_field_sql($sql, array($studioid));

        $sql = <<<EOF
  SELECT f.userid,
         u.firstname,
         u.lastname,
         COUNT(f.id) AS totals,
         SUM(f.filesize) as storage
    FROM {files} f
    JOIN {user} u ON u.id = f.userid
    WHERE (
               (f.itemid IN (SELECT s.fileid
                               FROM {openstudio_contents} s
                              WHERE s.openstudioid = ?) AND f.component = 'mod_openstudio'
                                                    AND f.filearea IN ('content', 'contentthumbnail'))
            OR (f.itemid IN (SELECT sv.fileid
                               FROM {openstudio_content_versions} sv
                               JOIN {openstudio_contents} ss ON ss.id = sv.contentid
                              WHERE ss.openstudioid = ?) AND f.component = 'mod_openstudio'
                                               AND f.filearea IN ('content', 'contentthumbnail'))
            OR (f.itemid IN (SELECT c.id
                               FROM {openstudio_comments} c
                               JOIN {openstudio_contents} sss ON sss.id = c.contentid
                              WHERE sss.openstudioid = ?) AND f.component = 'mod_openstudio'
                                                      AND f.filearea IN (
                                                                            'contentcomment', '$commenttext'
                                                                        ))
           )
     AND f.filesize > 0
GROUP BY f.userid,
         u.firstname,
         u.lastname
ORDER BY SUM(f.filesize) DESC, COUNT(f.id) DESC

EOF;

        $storagebyuser = $DB->get_recordset_sql($sql, array($studioid, $studioid, $studioid), 0, 20);

        return (object) array(
                'slotsbymimetype' => $storagebymimetype,
                'storagebyuser' => $storagebyuser,
                'storagebythumbnail' => $storagebythumbnail,
                'storagebycomment' => $storagebycomment,
                'storagebyslot' => $storagebyslot,
                'storagebyslotversion' => $storagebyslotversion);
    }

    /*
     * Show summary of Studio logged activities.
     *
     * @param int $courseid Course id.
     * @param int $cmid Course module id.
     * @return object Returns studio logged activities.
     */
    public static function get_activity_log($courseid, $cmid) {
        global $DB;

        $sql = <<<EOF
  SELECT action, count(*) AS total
    FROM {log}
   WHERE course = ?
     AND module = 'openstudio'
     AND cmid = ?
GROUP BY action
ORDER BY count(*) DESC

EOF;

        $activitylog = $DB->get_recordset_sql($sql, array($courseid, $cmid));

        return $activitylog;
    }
}
