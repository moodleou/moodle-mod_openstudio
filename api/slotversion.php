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

/**
 * Get slot version record.
 *
 * @param int $viewerid
 * @param int $slotid
 * @param bool $includedeleted
 * @return mixed Return recordset of false if error.
 */
function studio_api_slotversion_get_record($viewerid, $slotid, $includedeleted = false) {
    global $DB;

    $sql = <<<EOF
         SELECT l3.id AS l3id, l2.id AS l2id, l1.id AS l1id,
                l3.name AS l3name, l2.name AS l2name, l1.name AS l1name,
                u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
                u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                sv.*,
                s.levelid, s.levelcontainer,
                s.ownership, s.ownershipdetail,
                s.visibility, s.userid, s.openstudioid
           FROM {openstudio_content_versions} sv
     INNER JOIN {openstudio_contents} s on s.id = sv.contentid
     INNER JOIN {user} u ON u.id = s.userid
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = s.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
          WHERE sv.id = ?

EOF;

    $slotdata = $DB->get_record_sql($sql, array($slotid));
    if ($slotdata === false) {
        return false;
    }

    $slotdata = mod_openstudio\local\util::add_additional_content_data($slotdata);
    if ($slotdata->userid == $viewerid) {
        $slotdata->isownedbyviewer = true;
    } else {
        $slotdata->isownedbyviewer = false;
    }
    $slotdata->visibilitycontext = STUDIO_VISIBILITY_PRIVATE;
    if (!$slotdata->isownedbyviewer) {
        // We need to get the course associated with the slot.
        $cm = mod_openstudio\local\util::get_coursemodule_from_studioid($slotdata->studioid);
        if ($cm === false) {
            throw new moodle_exception('errorunexpectedbehaviour', 'studio', '');
        }

        $ismember = studio_api_group_is_slot_group_member(
                $cm->groupmode, $slotdata->visibility, $cm->groupingid, $slotdata->userid, $viewerid);
        if ($ismember) {
            $slotdata->visibilitycontext = STUDIO_VISIBILITY_GROUP;
        } else {
            $slotdata->visibilitycontext = STUDIO_VISIBILITY_MODULE;
        }
    }

    $sql = <<<EOF
  SELECT sv.*
    FROM {openstudio_content_versions} sv
   WHERE sv.contentid = ?
ORDER BY sv.id DESC

EOF;

    $slotversionsdata = $DB->get_recordset_sql($sql, array($slotdata->slotid));
    if ($slotversionsdata->valid()) {
        // Filtered out deleted slot versions.
        $slotdata->numberofversionsincludingdeleted = count($slotversionsdata);
        $slotdata->numberofversions = 0;
        $slotdata->numberofversionsnotdeleted = 0;
        $slotversionsdatafiltered = array();
        foreach ($slotversionsdata as $slotversionid => $slotversiondata) {
            if (($slotversiondata->deletedby <= 0) || $includedeleted) {
                $slotdata->numberofversions++;
                $slotversionsdatafiltered[$slotversionid] = $slotversiondata;
            }
            if ($slotversiondata->deletedby <= 0) {
                $slotdata->numberofversionsnotdeleted++;
            }
        }
        $slotversionsdata->close();
        $counter = $slotdata->numberofversions;

        // Calculate version number and next and previous version id for given slot.
        $slotdata->previousversionid = $slotdata->id;
        $slotdata->nextversionid = $slotdata->id;
        foreach ($slotversionsdatafiltered as $slotversionid => $slotversiondata) {
            if ($slotversionid == $slotid) {
                $slotdata->versionnumber = $counter;
            }
            if ($slotversionid > $slotid) {
                $slotdata->nextversionid = $slotversionid;
            }
            if ($slotversionid < $slotid) {
                $slotdata->previousversionid = $slotversionid;
                break;
            }
            $counter--;
        }
    } else {
        $slotdata->versionnumber = 1;
        $slotdata->numberofversions = 1;
        $slotdata->previousversionid = $slotdata->id;
        $slotdata->nextversionid = $slotdata->id;
    }

    return $slotdata;
}

/**
 * Get slot versions count.
 *
 * @param int $slotid
 * @param bool $includedeleted
 * @return mixed Return count of slot versions of false if error.
 */
function studio_api_slotversion_getcount($slotid, $includedeleted = false) {
    global $DB;

    if ($includedeleted) {
        return $DB->count_records('studio_content_versions',
                array('contentid' => $slotid));
    }

    $sql = <<<EOF
SELECT count(*)
  FROM {openstudio_content_versions} sv
 WHERE contentid = ?
   AND deletedtime IS NULL

EOF;

    return $DB->count_records_sql($sql, array('contentid' => $slotid));
}

/**
 * Delete oldest slot versions.
 *
 * @param int $userid
 * @param int $slotid
 * @return bool Return true on success.
 */
function studio_api_slotversion_delete_oldest($userid, $slotid) {
    global $DB;

    try {
        $sql = <<<EOF
UPDATE {openstudio_content_versions} sv
   SET deletedby = ?,
       deletedtime = ?
 WHERE sv.id IN (SELECT MIN(sv2.id)
                   FROM {openstudio_content_versions} sv2
                  WHERE sv2.contentid = ?
                    AND sv2.deletedtime IS NULL)

EOF;

        $DB->execute($sql, array($userid, time(), $slotid));

        return true;
    } catch (Exception $e) {
        // Default to returning false.
    }

    return false;
}
