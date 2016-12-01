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
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

use mod_openstudio\local\util;

/*
 * Three levels can be created:
 *   level1, level2, or level3
 *
 * Level1 data:
 *   studioid (required)
 *   name (required)
 *   required (optional)
 *   status (optional)
 *
 * Level2 and Level3 data
 *   parentid (required)
 *   name (required)
 *   required (optional)
 *   status (optional)
 *
 * @param int $level level to create (1, 2, or 3)
 * @param stdClass $data level data
 * @return int $result instance id
 */
function studio_api_levels_create($level, $data) {
    global $DB;

    $data = (object) $data;
    $tablename = '';
    $insertdata = new stdClass();

    // Validate data parameters.
    switch ($level) {
        case 1:
            if (!array_key_exists('openstudioid', $data)) {
                return false;
            }
            $tablename = 'openstudio_level1';
            $insertdata->openstudioid = $data->openstudioid;
            break;

        case 2:
            $insertdata->hidelevel = isset($data->hidelevel) ? $data->hidelevel : 0;

        case 3:
            if (!array_key_exists('parentid', $data)) {
                return false;
            }
            $tablename = 'openstudio_level' . $level;
            $fieldname = 'level' . ($level - 1) . 'id';
            $insertdata->$fieldname = $data->parentid;
            $insertdata->contenttype = isset($data->contenttype) ? $data->contenttype : 0;
            break;

        default:
            return false;
    }

    if (!array_key_exists('name', $data)) {
        return false;
    }

    $insertdata->name = $data->name;
    $insertdata->sortorder = $data->sortorder;
    $insertdata->required = isset($data->required) ? $data->required : 0;
    $insertdata->status = isset($data->status) ? $data->status : 0;
    $levelinstanceid = $DB->insert_record($tablename, $insertdata);

    return $levelinstanceid;
}

/*
 * Updates a level record.
 *
 * @param int $level level to create (1, 2, or 3)
 * @param int $levelid id for record
 * @param stdClass $data level data
 * @return bool result
 */
function studio_api_levels_update($level, $levelid, $data) {
    global $DB;

    $data = (object) $data;

    $tablename = '';

    if (in_array($level, array(1, 2, 3))) {
        $tablename = 'openstudio_level' . $level;
    }

    if (($tablename === '') || ($levelid <= 0)) {
        return false;
    }

    $data->id = $levelid;

    return $DB->update_record($tablename, $data);
}

/*
 * Returns a single level record.
 *
 * @param int $level level to create (1, 2, or 3)
 * @param int $levelid id for record
 * @return object $result
 */
function studio_api_levels_get_record($level, $levelid) {
    global $DB;

    $tablename = '';

    if (in_array($level, range(1, 3))) {
        $tablename = 'openstudio_level' . $level;
    }

    if (($tablename === '') || ($levelid <= 0)) {
        return false;
    }

    return $DB->get_record($tablename, array('id' => $levelid), '*');
}

function studio_api_levels_get_name($level, $levelid) {
    global $DB;

    $data = (object) array(
            'level1name' => '',
            'level2name' => '',
            'level3name' => ''
    );

    if ($level == 0) {
        $data->level1name = 'Pinboard';
        return $data;
    }

    if ($level == 1) {
        $level1data = studio_api_levels_get_record(1, $levelid);
        $data->level1name = $level1data->name;
        return $data;
    }

    if ($level == 2) {
        $level2data = studio_api_levels_get_record(2, $levelid);
        $level1data = studio_api_levels_get_record(1, $level2data->level1id);
        $data->level1name = $level1data->name;
        $data->level2name = $level2data->name;
        $data->level2hide = $level2data->hidelevel;
        return $data;
    }

    if ($level == 3) {
        $level3data = studio_api_levels_get_record(3, $levelid);
        $level2data = studio_api_levels_get_record(2, $level3data->level2id);
        $level1data = studio_api_levels_get_record(1, $level2data->level1id);
        $data->level1name = $level1data->name;
        $data->level2name = $level2data->name;
        $data->level2hide = $level2data->hidelevel;
        $data->level3name = $level3data->name;
        return $data;
    }

    return false;
}

/*
 * Gets all records for a given level below a parent.
 *
 * @param int $level level to create (1, 2, or 3)
 * @param int $parentid id for record
 * @param bool $showdeleted Show deleted records
 * @param bool $status filter by status if needed
 * @return array $result
 */
function studio_api_levels_get_records(
        $level, $parentid, $showdeleted = false, $sortorder = '', $status = '') {

    global $DB;

    if ($sortorder == '') {
        $orderby = 'sortorder ASC';
    } else {
        $orderby = $sortorder;
    }

    $tablename = '';

    if (in_array($level, range(1, 3))) {
        $tablename = 'openstudio_level' . $level;
    }

    if (($tablename === '') || ($parentid <= 0)) {
        return false;
    }

    if ($level === 1) {
        $fieldname = 'openstudioid';
    } else {
        $fieldname = 'level' . ($level - 1) . 'id';
    }

    if ($status == '') {
        // On average 20 records are returned, so get_records() is OK to use for now.
        $result = $DB->get_records($tablename, array($fieldname => $parentid), $orderby);
    } else {
        $result = $DB->get_records($tablename, array($fieldname => $parentid, 'status' => $status), $orderby);
    }

    if ($result === false) {
        return false;
    }

    if ($showdeleted) {
        return $result;
    }

    // Filter our deleted records from the returned result set.
    $resultfiltered = array();
    foreach ($result as $record) {
        if ($record->status >= 0) {
            $resultfiltered[] = $record;
        }
    }

    return $resultfiltered;
}

function studio_api_levels_is_defined($studioid) {
    global $DB;

    $sql = <<<EOF
SELECT DISTINCT 1
  FROM {openstudio_level1} l1
  JOIN {openstudio_level2} l2 ON l2.level1id = l1.id AND l2.status >= 0
  JOIN {openstudio_level3} l3 ON l3.level2id = l2.id AND l3.status >= 0
 WHERE l1.openstudioid = ?
   AND l1.status >= 0

EOF;

    return $DB->record_exists_sql($sql, array($studioid));
}

/*
 * Helper function to get the first block structure in the specified Studio instance.
 *
 * @param int $studioid Studio ID.
 * @return array $result
 */
function studio_api_levels_get_first_block_in_studio($studioid) {
    global $DB;

    // Note, we only find the block not marked as deleted and also the block which have contents.

    $sql = <<<EOF
  SELECT DISTINCT l1.id, l1.sortorder
    FROM {openstudio_level1} l1
    JOIN {openstudio_level2} l2 ON l2.level1id = l1.id AND l2.status >= 0
    JOIN {openstudio_level3} l3 ON l3.level2id = l2.id AND l3.status >= 0
   WHERE l1.openstudioid = ?
     AND l1.status >= 0
ORDER BY l1.sortorder, l1.id

EOF;

    $result = $DB->get_records_sql($sql, array($studioid), 0, 1);
    if (count($result) > 0) {
        foreach ($result as $key => $value) {
            return $value->id;
        }
    }

    return false;
}

/*
 * Helper function to get the the previous and next block structure relative to the
 * specified block.
 *
 * This is used to present pagination by block structure.
 *
 * @param int $studioid Studio ID.
 * @param int $blockid Current block id to get previous and next block id from.
 * @return object Return the next and previous block information.
 */
function studio_api_levels_get_previous_and_next_block_in_studio($studioid, $blockid) {
    global $DB;

    // Note, we only show blocks not marked as deleted and also blocks which have contents.

    $sql = <<<EOF
  SELECT DISTINCT l1.id, l1.name, l1.sortorder
    FROM {openstudio_level1} l1
    JOIN {openstudio_level2} l2 ON l2.level1id = l1.id AND l2.status >= 0
    JOIN {openstudio_level3} l3 ON l3.level2id = l2.id AND l3.status >= 0
   WHERE l1.openstudioid = ?
     AND l1.status >= 0
ORDER BY l1.sortorder, l1.id

EOF;

    $result = $DB->get_records_sql($sql, array($studioid));
    $previousid = false;
    $previousname = '';
    $nextid = false;
    $nextname = '';
    $currentid = $blockid;
    $currentname = studio_api_levels_get_name(1, $blockid);
    if (count($result) > 0) {
        $resultordered = array();
        $resultindex = 0;
        foreach ($result as $key => $value) {
            $resultordered[$resultindex++] = $value;
        }
        for ($counter = 0; $counter < $resultindex; $counter++) {
            if ($resultordered[$counter]->id == $blockid) {
                if ($counter > 0) {
                    $previousid = $resultordered[$counter - 1]->id;
                    $previousname = $resultordered[$counter - 1]->name;
                }
                if ($counter < (count($resultordered) - 1)) {
                    $nextid = $resultordered[$counter + 1]->id;
                    $nextname = $resultordered[$counter + 1]->name;
                }
            }
        }

    }

    return (object) array(
            'previous' => $previousid,
            'previousname' => $previousname,
            'current' => $blockid,
            'currentname' => $currentname->level1name,
            'next' => $nextid,
            'nextname' => $nextname);
}

/*
 * Helper function to check if a block has defined activities and contents.
 *
 * @param int $level1id SBlock id to check.
 * @return int Return count of number of contents in given block.
 */
function studio_api_levels_block_has_contents($level1id) {
    global $DB;

    $sql = <<<EOF
SELECT COUNT(l3.*)
  FROM {openstudio_level3} l3
  JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
  JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
 WHERE l1.id = ?
   AND l1.status >= 0

EOF;

    return $DB->count_records_sql($sql, array($level1id));
}

/*
 * Helper function to get the number of contents specified for each level1 block
 * in a given studio instance.
 *
 * @param int $studioid Studio instance id to check.
 * @return int Return count of number of contents in given block.
 */
function studio_api_levels_block_content_count($studioid) {
    global $DB;

    $sql = <<<EOF
  SELECT l1.id, COUNT(l3.*)
    FROM {openstudio_level3} l3
    JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
    JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
   WHERE l1.openstudioid = ?
     AND l1.status >= 0
GROUP BY l1.id

EOF;

    $returndata = array();

    $sqlresult = $DB->get_recordset_sql($sql, array($studioid));
    if (!$sqlresult->valid()) {
        return $returndata;
    }

    foreach ($sqlresult as $result) {
        $returndata[$result->id] = $result->count;
    }

    return $returndata;
}

/**
 * Deletes (hard delete) unpopulated L1 blocks, L2 activities and L3 contents from database.
 *
 * @param int $level
 * @param int $levelid
 * @return bool Return true if successful.
 */
function studio_api_levels_unpopulated_delete($level, $levelid) {
    global $DB;

    switch($level) {
        case 1:
            // Cascade down from blocks.
            $sqll3 = <<<EOF
DELETE FROM {openstudio_level3} l3
      USING {openstudio_level2} l2, {openstudio_level1} l1
      WHERE l3.level2id = l2.id
        AND l2.level1id = l1.id
        AND l1.id = ?

EOF;

            $sqll2 = <<<EOF
DELETE FROM {openstudio_level2} l2
      USING {openstudio_level1} l1
      WHERE l2.level1id = l1.id
        AND l1.id = ?

EOF;

            $sqll1 = <<<EOF
DELETE FROM {openstudio_level1} l1
WHERE l1.id = ?

EOF;

            $params = array($levelid);
            break;

        case 2:
            // Cascade down from activities.
            $sqll3 = <<<EOF
DELETE FROM {openstudio_level3} l3
      USING {openstudio_level2} l2
      WHERE l3.level2id = l2.id
        AND l2.id = ?

EOF;

            $sqll2 = <<<EOF
DELETE FROM {openstudio_level2} l2
      WHERE l2.id = ?

EOF;

            $sqll1 = '';

            $params = array($levelid);
            break;

        case 3:
            $sqll3 = <<<EOF
DELETE FROM {openstudio_level3} l3
      WHERE l3.id = ?

EOF;

            $sqll2 = '';
            $sqll1 = '';

            $params = array($levelid);
            break;

        default:
            return false;
    }

    try {
        $transaction = $DB->start_delegated_transaction();

        if ($sqll3 != '') {
            $DB->execute($sqll3, $params);
        }

        if ($sqll2 != '') {
            $DB->execute($sqll2, $params);
        }

        if ($sqll1 != '') {
            $DB->execute($sqll1, $params);
        }

        // Assuming the 3 deletes work, we get to the following line.
        $transaction->allow_commit();

        return true;
    } catch (Exception $e) {
         $transaction->rollback($e);
    }

    return false;
}

/**
 * This function either soft deleted a level by setting its status to -1 or undoes soft delete by setting the status to 0.
 * Cascades down as needed.
 *
 * @param int $level
 * @param int $levelid
 * @param bool $undo If true, will UNDO the soft delete and set status  0 and WILL cascade down to all levels.
 * @return bool Return true if operation is successful.
 */
function studio_api_levels_softdelete($level, $levelid, $undo = false) {
    global $DB;

    if ($undo) {
        $newstatus = STUDIO_LEVELS_STATUS_DEFAULT;
    } else {
        $newstatus = STUDIO_LEVELS_STATUS_SOFT_DELETED;
    }

    switch($level) {
        case 1:
            // Cascade down from blocks.
            $sqll3 = <<<EOF
UPDATE {openstudio_level3}
   SET status = ?
 WHERE level2id IN (SELECT l3.level2id
                      FROM {openstudio_level3} l3,
                           {openstudio_level2} l2,
                           {openstudio_level1} l1
                     WHERE l3.level2id = l2.id
                       AND l2.level1id = l1.id
                       AND l1.id = ?)

EOF;

            $sqll2 = <<<EOF
UPDATE {openstudio_level2}
   SET status = ?
 WHERE level1id IN (SELECT l2.level1id
                      FROM {openstudio_level2} l2,
                           {openstudio_level1} l1
                     WHERE l2.level1id = l1.id
                       AND l1.id = ?)

EOF;

            $sqll1 = <<<EOF
UPDATE {openstudio_level1}
   SET status = ?
 WHERE {openstudio_level1}.id = ?

EOF;

            $params = array($newstatus, $levelid);
            break;

        case 2:
            // Cascade down from activities.
            $sqll3 = <<<EOF
UPDATE {openstudio_level3}
   SET status = ?
 WHERE level2id IN (SELECT l3.level2id
                      FROM {openstudio_level3} l3,
                           {openstudio_level2} l2
                     WHERE l3.level2id = l2.id
                       AND l2.id = ?)

EOF;

            $sqll2 = <<<EOF
UPDATE {openstudio_level2}
   SET status = ?
 WHERE {openstudio_level2}.id = ?

EOF;

            $sqll1 = '';
            $params = array($newstatus, $levelid);
            break;

        case 3:
            $sqll3 = <<<EOF
UPDATE {openstudio_level3}
   SET status = ?
 WHERE {openstudio_level3}.id = ?

EOF;

            $sqll2 = '';
            $sqll1 = '';

            $params = array($newstatus, $levelid);
            break;

        default:
            return false;
    }

    try {
        $transaction = $DB->start_delegated_transaction();
        if ($sqll3 != '') {
            $DB->execute($sqll3, $params);
        }
        if ($sqll2 != '') {
            $DB->execute($sqll2, $params);
        }
        if ($sqll1 != '') {
            $DB->execute($sqll1, $params);
        }

        // Assuming the 3 deletes work, we get to the following line.
        $transaction->allow_commit();

        return true;
    } catch (Exception $e) {
         $transaction->rollback($e);
    }

    return false;
}

/*
 * Default delete function. This is called in the controllers and determines whether to hard or soft delete.
 *
 * @param int $level level to create (1, 2, or 3)
 * @param int $levelid id for record
 * @param bool $softdelete do soft or hard delete, default is hard delete
 * @return bool $result
 */
function studio_api_levels_delete($level, $levelid, $studioid) {
    // Get all contents.
    $contents = mod_openstudio\local\api\content::get_all_records($studioid);

    if (in_array($level, range(1, 3))) {
        if ($contents) {
            $activecontents = studio_api_levels_count_contents_in_level($level, $levelid, $contents);
        } else {
            $activecontents = 0;
        }

        if ($activecontents > 0) {
            // This has active contents, softdelete.
            if (!studio_api_levels_softdelete($level, $levelid)) {
                return false;
            }
        } else {
            // Hard delete.
            if (!studio_api_levels_unpopulated_delete($level, $levelid)) {
                return false;
            }
        }

        // Unset contents to save memory.
        $contents = '';

        return true;
    }

    return false;
}

/**
 * This function cleans up the sortorder, changing the numbers from 1 upwards to whatever the group requires.
 *
 * @param int $level
 * @param int $parentid
 */
function studio_api_levels_cleanup_sortorder($level, $parentid) {
    $records = studio_api_levels_get_records($level, $parentid);

    // Loop through and fix sortorder.
    $sortorder = 0;
    foreach ($records as $r) {
        $sortorder++;

        // Reset sortorder.
        $r->sortorder = $sortorder;
        studio_api_levels_update($level, $r->id, $r);
    }
}

/**
 * Gets the latest sort order number.
 *
 * @param int $level
 * @param int $parentid
 * @return bool Return true if successful.
 */
function studio_api_levels_get_latest_sortorder($level, $parentid) {
    global $DB;

    $tablename = '';
    switch ($level) {
        case 1:
            $tablename = 'openstudio_level1';
            $params = array('openstudioid' => $parentid, 'status' => 0);
            break;

        case 2:
            $tablename = 'openstudio_level2';
            $params = array('level1id' => $parentid, 'status' => 0);
            break;
        case 3:
            $tablename = 'openstudio_level3';
            $params = array('level2id' => $parentid, 'status' => 0);
            break;

        default:
            return false;
    }

    $results = $DB->get_records($tablename, $params, 'sortorder DESC');
    if (count($results) > 0) {
        foreach ($results as $res) {
            return $res->sortorder + 1;
            break;
        }
    } else {
        return 1;
    }
}

/**
 * Count active contents in a given level id.
 *
 * @param int $level
 * @param int $levelid
 * @param recordset $studiocontents
 * @return mixed Return content counts or false if error.
 */
function studio_api_levels_count_contents_in_level($level, $levelid, $studiocontents) {
    $count = 0;

    if (($studiocontents == false) || !method_exists($studiocontents, 'valid')) {
        return 0;
    }

    switch($level) {
        case 1:
            if ($studiocontents->valid()) {
                foreach ($studiocontents as $content) {
                    if ($content->l1id == $levelid) {
                        $count++;
                    }
                }
                $studiocontents->close();
            }
            break;

        case 2:
            if ($studiocontents->valid()) {
                foreach ($studiocontents as $content) {
                    if ($content->l2id == $levelid) {
                        $count++;
                    }
                }
                $studiocontents->close();
            }
            break;

        case 3:
            if ($studiocontents->valid()) {
                foreach ($studiocontents as $content) {
                    if ($content->l3id == $levelid) {
                        $count++;
                    }
                }
                $studiocontents->close();
            }
            break;

        default:
            return false;
    }

    return $count;
}

/*
 * Reads the import XML, verifies it and uses it to generate a series of DB INSERT statements
 * to create the block/activity structure as described in the XML.
 *
 * Existing block/activity structure is soft deleted first.
 *
 * @param int $studioid Studio ID to import block structure into.
 * @param string $xml The import XML to verify and use to import the requested structure.
 * @return mixed Returns false if error occurred, otherwise true.
 */
function studio_api_levels_import_xml($studioid, $xml) {
    global $DB;

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xml);
    if ($xml === false) {
        return false;
    }

    try {
        $transaction = $DB->start_delegated_transaction();

        $sql = <<<EOF
UPDATE {openstudio_content_templates}
   SET status = -1
 WHERE foldertemplateid IN (SELECT st.id
                           FROM {openstudio_folder_templates} st
                           JOIN {openstudio_level3} l3 ON l3.id = st.levelid
                           JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
                           JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
                          WHERE l1.openstudioid = ?)

EOF;

        $DB->execute($sql, array($studioid));

        $sql = <<<EOF
UPDATE {openstudio_folder_templates}
   SET status = -1
 WHERE levelid IN (SELECT l3.id
                     FROM {openstudio_level3} l3
                     JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
                     JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
                    WHERE l1.openstudioid = ?)

EOF;

        $DB->execute($sql, array($studioid));

        $sql = <<<EOF
UPDATE {openstudio_level3}
   SET status = -1
 WHERE level2id IN (SELECT l2.id
                      FROM {openstudio_level2} l2
                      JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
                     WHERE l1.openstudioid = ?)

EOF;

        $DB->execute($sql, array($studioid));

        $sql = <<<EOF
UPDATE {openstudio_level2}
   SET status = -1
 WHERE level1id IN (SELECT l1.id
                      FROM {openstudio_level1} l1
                     WHERE l1.openstudioid = ?)

EOF;

        $DB->execute($sql, array($studioid));

        $sql = <<<EOF
UPDATE {openstudio_level1}
   SET status = -1
 WHERE openstudioid = ?

EOF;

        $DB->execute($sql, array($studioid));

        $blocksortorder = 1;
        foreach ($xml->block as $block) {
            if (!isset($block->name) || (trim($block->name) == '')) {
                continue;
            }

            $dataobject = array(
                'openstudioid' => $studioid,
                'name' => $block->name . '',
                'required' => 0,
                'status' => 0,
                'sortorder' => $blocksortorder++
            );

            $blockid = $DB->insert_record('openstudio_level1', (object) $dataobject, true);

            if (isset($block->activities)) {
                if (isset($block->activities->activity)) {
                    $activitysortorder = 1;
                    foreach ($block->activities->activity as $activity) {
                        if (!isset($activity->name) || (trim($activity->name) == '')) {
                            continue;
                        }

                        $dataobject = array(
                            'level1id' => $blockid,
                            'name' => $activity->name . '',
                            'required' => 0,
                            'hidelevel' => 0,
                            'status' => 0,
                            'sortorder' => $activitysortorder++
                        );

                        if (isset($activity->hidelevel) && (((int) $activity->hidelevel) > 0)) {
                            $dataobject['hidelevel'] = 1;
                        }

                        $activityid = $DB->insert_record('openstudio_level2', (object) $dataobject, true);

                        if (isset($activity->contents)) {
                            if (isset($activity->contents->content)) {
                                $contentsortorder = 1;
                                foreach ($activity->contents->content as $content) {
                                    if (!isset($content->name) || (trim($content->name) == '')) {
                                        continue;
                                    }

                                    $dataobject = array(
                                        'level2id' => $activityid,
                                        'name' => $content->name . '',
                                        'required' => 0,
                                        'contenttype' => 0,
                                        'status' => 0,
                                        'sortorder' => $contentsortorder++
                                    );

                                    if (isset($content->required) && (((int) $content->required) > 0)) {
                                        $dataobject['required'] = 1;
                                    }

                                    if (isset($content->contenttype)) {
                                        if ((int) $content->contenttype == STUDIO_CONTENTTYPE_COLLECTION) {
                                            $dataobject['contenttype'] = STUDIO_CONTENTTYPE_COLLECTION;
                                        } else if ((int) $content->contenttype == STUDIO_CONTENTTYPE_SET) {
                                            $dataobject['contenttype'] = STUDIO_CONTENTTYPE_SET;
                                        }
                                    }

                                    $contentid = $DB->insert_record('openstudio_level3', (object) $dataobject, true);

                                    if ($dataobject['contenttype'] == STUDIO_CONTENTTYPE_SET && isset($content->template)) {
                                        $dataobject = (object) array(
                                            'levelcontainer'  => 3,
                                            'levelid'         => $contentid,
                                            'guidance'        => $content->template->guidance . '',
                                            'additionalcontents' => (int) $content->template->additionalcontents,
                                            'status'          => STUDIO_LEVELS_STATUS_DEFAULT
                                        );

                                        $foldertemplateid = $DB->insert_record('openstudio_folder_templates', $dataobject);

                                        if (isset($content->template->contents) && !empty($content->template->contents)) {
                                            foreach ($content->template->contents->content as $contenttemplate) {
                                                $dataobject = (object) array(
                                                    'foldertemplateid' => $foldertemplateid,
                                                    'name'          => $contenttemplate->name . '',
                                                    'guidance'      => $contenttemplate->guidance . '',
                                                    'permissions'   => (int) $contenttemplate->permissions,
                                                    'contentorder'     => (int) $contenttemplate->contentorder,
                                                    'status'        => STUDIO_LEVELS_STATUS_DEFAULT
                                                );
                                                $DB->insert_record('openstudio_content_templates', $dataobject, true);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $transaction->allow_commit();

        return true;
    } catch (Exception $e) {
        $transaction->rollback($e);
    }

    return false;
}

/*
 * Reads the import XML and converts it into a HTML rendered view of the XML
 * structure.  This function firstly verifies the XML is valid.  The HTML
 * is used todisplay to the user to confirm the content of the import XML
 * prior to actually importing the structure for real.
 *
 * @param string $xml The import XML to convert and verify.
 * @return mixed Returns false if error occurred, otherwise HTML string.
 */
function studio_api_levels_convert_import_xml_to_print($xml) {
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xml);
    if ($xml === false) {
        return false;
    }

    $hascontents = false;

    $html = "<ul>\n";
    foreach ($xml->block as $block) {
        if (!isset($block->name) || (trim($block->name) == '')) {
            continue;
        }
        $html .= "<li>{$block->name}\n";
        if (isset($block->activities)) {
            if (isset($block->activities->activity)) {
                $html .= "<ul>\n";
                foreach ($block->activities->activity as $activity) {
                    if (!isset($activity->name) || (trim($activity->name) == '')) {
                        continue;
                    }
                    $html .= "<li>{$activity->name}";
                    if (isset($activity->hidelevel) && ($activity->hidelevel > 0)) {
                        $html .= ' (hidden)';
                    }
                    if (isset($activity->contents)) {
                        if (isset($activity->contents->content)) {
                            $html .= "<ul>\n";
                            foreach ($activity->contents->content as $content) {
                                if (!isset($content->name) || (trim($content->name) == '')) {
                                    continue;
                                }

                                $hascontents = true;

                                $html .= "<li>";
                                if (isset($content->required) && ($content->required == 1)) {
                                    $html .= "{$content->name} (required)";
                                } else {
                                    $html .= $content->name;
                                }
                                if (isset($content->contenttype)) {
                                    if ($content->contenttype == STUDIO_CONTENTTYPE_COLLECTION) {
                                        $html .= ' (collection).';
                                    } else if ($content->contenttype == STUDIO_CONTENTTYPE_SET) {
                                        $html .= ' (set).';
                                        if (isset($content->template) && isset($content->template->contents)) {
                                            $html .= '<ul>';
                                            foreach ($content->template->contents->content as $contenttemplate) {
                                                $html .= "<li>{$contenttemplate->name}</li>";
                                            }
                                            $html .= '</ul>';
                                        }
                                    }
                                }
                                $html .= "</li>\n";
                            }
                            $html .= "</ul>\n";
                        }
                    }
                    $html .= "</li>\n";
                }
                $html .= "</ul>\n";
            }
        }
        $html .= "</li>\n";
    }
    $html .= "</ul>\n";

    if (!$hascontents) {
        return false;
    }

    return $html;
}

/*
 * Reads the given studioid block/ativity level structure and converts it to
 * XML string that can be used to import into another Studio.
 *
 * @param int $studioid Studio ID to get level data from.
 * @param boolean $returnasobject If true, return the data as an object instead of XML string.
 * @return mixed Returns true or false if error occurred, otherwise XML string or obejct data.
 */
function studio_api_levels_export_xml($studioid, $returnasobject = false) {
    $blocks = studio_api_levels_get_records(1, $studioid);

    $blockcounter = 0;
    foreach ($blocks as $block) {
        $activities = studio_api_levels_get_records(2, $block->id);
        if (!array_key_exists('activities', $blocks[$blockcounter])) {
            $blocks[$blockcounter]->activities = array();
        }
        foreach ($activities as $activity) {
            $blocks[$blockcounter]->activities[$activity->id] = $activity;

            $contents = studio_api_levels_get_records(3, $activity->id);
            if (!array_key_exists('contents', $blocks[$blockcounter]->activities[$activity->id])) {
                $blocks[$blockcounter]->activities[$activity->id]->contents = array();
            }
            foreach ($contents as $content) {
                if ($content->contenttype == STUDIO_CONTENTTYPE_SET) {
                    $template = studio_api_folder_template_get_by_levelid($content->id);
                    if ($template) {
                        $templatecontents = studio_api_folder_template_contents_get($template->id);
                        if (!empty($templatecontents)) {
                            $template->contents = $templatecontents;
                        }
                        $content->template = $template;
                    }
                }
                $blocks[$blockcounter]->activities[$activity->id]->contents[] = $content;
            }
        }
        $blockcounter++;
    }

    // The leading spaces are deliberate to prevent a pretty print of the XML.
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<blocks>\n";
    foreach ($blocks as $block) {
        $xml .= "    <block>\n";
        $xml .= "        <name>" . util::escape_xml($block->name) . "</name>\n";
        $xml .= "        <activities>\n";

        if (isset($block->activities)) {
            foreach ($block->activities as $activity) {
                $xml .= "            <activity>\n";
                $xml .= "                <name>" . util::escape_xml($activity->name) . "</name>\n";
                $xml .= "                <hidelevel>" . $activity->hidelevel . "</hidelevel>\n";
                $xml .= "                <contents>\n";

                if (isset($activity->contents)) {
                    foreach ($activity->contents as $content) {
                        $xml .= "                    <content>\n";
                        $xml .= "                        <name>" . util::escape_xml($content->name) . "</name>\n";
                        $xml .= "                        <required>{$content->required}</required>\n";
                        $xml .= "                        <contenttype>{$content->contenttype}</contenttype>\n";

                        if (isset($content->template)) {
                            $xml .= "                          <template>\n";
                            $xml .= "                              <guidance>".
                                    util::escape_xml($content->template->guidance) . "</guidance>\n";
                            $xml .= "                              <additionalcontents>{$content->template->additionalcontents}" .
                                    "</additionalcontents>\n";

                            if (isset($content->template->contents)) {
                                $xml .= "                               <contents>\n";
                                foreach ($content->template->contents as $contenttemplate) {
                                    $xml .= "                                   <content>\n";
                                    $xml .= "                                       <name>" .
                                            util::escape_xml($contenttemplate->name) . "</name>\n";
                                    $xml .= "                                       <guidance>" .
                                            util::escape_xml($contenttemplate->guidance) . "</guidance>\n";
                                    $xml .= "                                       <permissions>{$contenttemplate->permissions}" .
                                            "</permissions>\n";
                                    $xml .= "                                       <contentorder>{$contenttemplate->contentorder}" .
                                            "</contentorder>\n";
                                    $xml .= "                                   </content>\n";
                                }
                                $xml .= "                               </contents>\n";
                            }
                            $xml .= "                          </template>\n";
                        }

                        $xml .= "                    </content>\n";
                    }
                }

                $xml .= "                </contents>\n";
                $xml .= "            </activity>\n";
            }
        }

        $xml .= "        </activities>\n";
        $xml .= "    </block>\n";
    }
    $xml .= "</blocks>\n";

    if ($returnasobject) {
        return $blocks;
    }

    return $xml;
}

/**
 * This function recovers the course module from a level3 id.
 *
 * @param int $level3id pointing to a content level that needs to be .
 * $return $cm Course module object..
 */
function studio_api_levels_get_coursemodule($level3id) {
    global $DB;

    // Get course module id from $contentid, for event.
    $contentleveltwodata = $DB->get_record('openstudio_level2', array('id' => $level3id));
    $contentlevelonedata = $DB->get_record('openstudio_level1', array('id' => $contentleveltwodata->level1id));
    $cm = get_coursemodule_from_instance('openstudio', $contentlevelonedata->openstudioid);

    return $cm;
}
