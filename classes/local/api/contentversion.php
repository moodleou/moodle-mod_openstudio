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
 *
 *
 * @package
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

use mod_openstudio\local\util;

class contentversion {

    /**
     * Get content version record.
     *
     * @param int $versionid
     * @param int $viewerid
     * @param bool $includedeleted
     * @return object|false Return content version data with associated metadata for the content.
     */
    public static function get($versionid, $viewerid, $includedeleted = false) {
        global $DB;

        $sql = <<<EOF
         SELECT l3.id AS l3id, l2.id AS l2id, l1.id AS l1id,
                l3.name AS l3name, l2.name AS l2name, l1.name AS l1name,
                u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
                u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                cv.*,
                c.levelid, c.levelcontainer,
                c.ownership, c.ownershipdetail,
                c.visibility, c.userid, c.openstudioid
           FROM {openstudio_content_versions} cv
     INNER JOIN {openstudio_contents} c on c.id = cv.contentid
     INNER JOIN {user} u ON u.id = c.userid
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = c.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
          WHERE cv.id = ?

EOF;

        $versiondata = $DB->get_record_sql($sql, [$versionid]);
        if ($versiondata === false) {
            return false;
        }

        $versiondata = util::add_additional_content_data($versiondata);
        if ($versiondata->userid == $viewerid) {
            $versiondata->isownedbyviewer = true;
        } else {
            $versiondata->isownedbyviewer = false;
        }
        $versiondata->visibilitycontext = content::VISIBILITY_PRIVATE;
        if (!$versiondata->isownedbyviewer) {
            // We need to get the course associated with the content.
            $cm = util::get_coursemodule_from_studioid($versiondata->openstudioid);
            if ($cm === false) {
                throw new \moodle_exception('errorunexpectedbehaviour', 'studio', '');
            }

            $ismember = group::is_content_group_member(
                    $cm->groupmode, $versiondata->visibility, $cm->groupingid, $versiondata->userid, $viewerid);
            if ($ismember) {
                $versiondata->visibilitycontext = content::VISIBILITY_GROUP;
            } else {
                $versiondata->visibilitycontext = content::VISIBILITY_MODULE;
            }
        }

        // Find other versions of this content so that we can also return counts and next/previous IDs.
        $sql = <<<EOF
  SELECT cv.*
    FROM {openstudio_content_versions} cv
   WHERE cv.contentid = ?
ORDER BY cv.id DESC

EOF;

        $contentversions = $DB->get_recordset_sql($sql, [$versiondata->contentid]);
        if ($contentversions->valid()) {
            // Filtered out deleted slot versions.
            $versiondata->numberofversionsincludingdeleted = 0;
            $versiondata->numberofversions = 0;
            $versiondata->numberofversionsnotdeleted = 0;
            $contentversionsfiltered = [];
            foreach ($contentversions as $contentversionid => $contentversiondata) {
                $versiondata->numberofversionsincludingdeleted++;
                if (($contentversiondata->deletedby <= 0) || $includedeleted) {
                    $versiondata->numberofversions++;
                    $contentversionsfiltered[$contentversionid] = $contentversiondata;
                }
                if ($contentversiondata->deletedby <= 0) {
                    $versiondata->numberofversionsnotdeleted++;
                }
            }
            $contentversions->close();
            $counter = $versiondata->numberofversions;

            // Calculate version number and next and previous version id for given content.
            $versiondata->previousversionid = $versiondata->id;
            $versiondata->nextversionid = $versiondata->id;
            foreach ($contentversionsfiltered as $contentversionid => $contentversiondata) {
                if ($contentversionid == $versionid) {
                    $versiondata->versionnumber = $counter;
                }
                if ($contentversionid > $versionid) {
                    $versiondata->nextversionid = $contentversionid;
                }
                if ($contentversionid < $versionid) {
                    $versiondata->previousversionid = $contentversionid;
                    break;
                }
                $counter--;
            }
        } else {
            $versiondata->versionnumber = 1;
            $versiondata->numberofversions = 1;
            $versiondata->previousversionid = $versiondata->id;
            $versiondata->nextversionid = $versiondata->id;
        }

        return $versiondata;
    }

    /**
     * Return a count of the versions for a content post.
     *
     * @param int $contentid
     * @param bool $includedeleted
     * @return mixed Return count of content versions or false if error.
     */
    public static function count($contentid, $includedeleted = false) {
        global $DB;

        $table = 'openstudio_content_versions';
        $params = ['contentid' => $contentid];

        if ($includedeleted) {
            return $DB->count_records($table, $params);
        } else {
            return $DB->count_records_select($table, 'contentid = :contentid AND deletedtime IS NULL', $params);
        }
    }

    /**
     * Delete oldest content version.
     *
     * @param int $contentid
     * @param int $userid
     * @return bool Return true on success.
     */
    public static function delete_oldest($contentid, $userid) {
        global $DB;

        try {
            $sql = <<<EOF
UPDATE {openstudio_content_versions} cv
   SET deletedby = ?,
       deletedtime = ?
 WHERE cv.id IN (SELECT MIN(cv2.id)
                   FROM {openstudio_content_versions} cv2
                  WHERE cv2.contentid = ?
                    AND cv2.deletedtime IS NULL)

EOF;

            $DB->execute($sql, [$userid, time(), $contentid]);

            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * Gets content and content version data.
     *
     * @param int $contentid
     * @param int $viewerid
     * @param bool $includedeleted True to include deleted slot verisons.
     * @return object|false Return slot and slot version data.
     */
    public static function get_content_and_versions($contentid, $viewerid, $includedeleted = false) {
        global $DB;

        try {
            $result = (object) [];

            $result->contentdata = content::get_record($viewerid, $contentid);

            $table = 'openstudio_content_versions';
            $params = ['contentid' => $contentid];
            $order = 'timemodified DESC';

            if ($includedeleted) {
                $result->contentversions = $DB->get_records($table, $params, $order);
            } else {
                $where = 'contentid = :contentid AND deletedby IS NULL';
                $result->contentversions = $DB->get_records_select($table, $where, $params, $order);
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }

    }

}