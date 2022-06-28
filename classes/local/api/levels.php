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
 * API public static functions for pre-defined content levels.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

class levels {

    const ACTIVE = 0;
    const SOFT_DELETED = -1;

    /**
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
     * @param \stdClass|array $data level data
     * @return int instance id
     */
    public static function create($level, $data) {
        global $DB;

        $data = (object) $data;
        $tablename = '';
        $insertdata = new \stdClass();

        // Validate data parameters.
        switch ($level) {
            case 1:
                if (!property_exists($data, 'openstudioid')) {
                    return false;
                }
                $tablename = 'openstudio_level1';
                $insertdata->openstudioid = $data->openstudioid;
                break;

            case 2:
                $insertdata->hidelevel = isset($data->hidelevel) ? $data->hidelevel : 0;
                // No break statement, since other than hidelevel, level2 records use the same fields as level3 records.

            case 3:
                if (!property_exists($data, 'parentid')) {
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

        if (!property_exists($data, 'name')) {
            return false;
        }

        $insertdata->name = $data->name;
        $insertdata->sortorder = $data->sortorder;
        $insertdata->required = isset($data->required) ? $data->required : 0;
        $insertdata->status = isset($data->status) ? $data->status : 0;
        $levelinstanceid = $DB->insert_record($tablename, $insertdata);

        return $levelinstanceid;
    }

    /**
     * Updates a level record.
     *
     * @param int $level level to create (1, 2, or 3)
     * @param int $levelid id for record
     * @param \stdClass $data level data
     * @return bool result
     */
    public static function update($level, $levelid, $data) {
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

    /**
     * Returns a single level record.
     *
     * @param int $level level to create (1, 2, or 3)
     * @param int $levelid id for record
     * @return object|bool $result
     */
    public static function get_record($level, $levelid) {
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

    /**
     * Get the name data for the level and its parent levels.
     *
     * @param int $level
     * @param int $levelid
     * @return bool|object An object containing level1name, level2name, and level3name, or false.
     */
    public static function get_name($level, $levelid) {
        $data = (object) array(
                'level1name' => '',
                'level2name' => '',
                'level3name' => ''
        );

        if ($level == 0) {
            $data->level1name = 'Pinboard';
            return $data;
        }

        if ($level != 0 && $levelid <= 0) {
            return false;
        }

        if ($level == 1) {
            $level1data = self::get_record(1, $levelid);
            if (!$level1data) {
                return false;
            }
            $data->level1name = $level1data->name;
            return $data;
        }

        if ($level == 2) {
            $level2data = self::get_record(2, $levelid);
            $level1data = '';
            if (!empty($level2data->level1id)) {
                $level1data = self::get_record(1, $level2data->level1id);
            }
            if (!$level2data || !$level1data) {
                return false;
            }
            $data->level1name = $level1data->name;
            $data->level2name = $level2data->name;
            $data->level2hide = $level2data->hidelevel;
            return $data;
        }

        if ($level == 3) {
            $level3data = self::get_record(3, $levelid);
            $level2data = '';
            $level1data = '';
            if (!empty($level3data->level2id)) {
                $level2data = self::get_record(2, $level3data->level2id);
                if (!empty($level2data->level1id)) {
                    $level1data = self::get_record(1, $level2data->level1id);
                }
            }
            if (!$level3data || !$level2data || !$level1data) {
                return false;
            }
            $data->level1name = $level1data->name;
            $data->level2name = $level2data->name;
            $data->level2hide = $level2data->hidelevel;
            $data->level3name = $level3data->name;
            return $data;
        }

        return false;
    }

    /**
     * Gets all records for a given level below a parent.
     *
     * @param int $level level to create (1, 2, or 3)
     * @param int $parentid id for record
     * @param bool $showdeleted Show deleted records
     * @param string $sortorder SQL ORDER BY statement.  Defaults to 'sortorder ASC' if blank.
     * @param int $status filter by status if needed
     * @return array|bool $result
     */
    public static function get_records(
            $level, $parentid, $showdeleted = false, $sortorder = '', $status = null) {

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

        if (is_null($status)) {
            // On average 20 records are returned, so get_records() is OK to use for now.
            $result = $DB->get_records($tablename, array($fieldname => $parentid), $orderby);
        } else {
            $result = $DB->get_records($tablename,
                    array($fieldname => $parentid, 'status' => $status), $orderby);
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

    /**
     * If there is at least one set of levels 1-3 defined for the studio, return true.
     *
     * @param int $studioid
     * @return bool
     */
    public static function defined_for_studio($studioid) {
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

    /**
     * Helper function to get the first level structure in the specified Studio instance.
     *
     * @param int $studioid Studio ID.
     * @return array|bool $result
     */
    public static function get_first_l1_in_studio($studioid) {
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

    /**
     * Helper public static function to check if a block has defined activities and contents.
     *
     * @param int $level1id SBlock id to check.
     * @return int Return count of number of contents in given block.
     */
    public static function l1_has_l3s($level1id) {
        global $DB;

        $sql = <<<EOF
SELECT COUNT(l3.id)
  FROM {openstudio_level3} l3
  JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
  JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
 WHERE l1.id = ?
   AND l1.status >= 0

EOF;

        return $DB->count_records_sql($sql, array($level1id));
    }

    /**
     * Helper function to get the number of level3s specified for each level1
     * in a given studio instance.
     *
     * @param int $studioid Studio instance id to check.
     * @return array Return count of number of contents in given block.
     */
    public static function l1s_count_l3s($studioid) {
        global $DB;

        $sql = <<<EOF
  SELECT l1.id, COUNT(l3.id) AS count
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
     * Deletes (hard delete) an unpopulated level, and all levels beneath.
     *
     * @param int $level
     * @param int $levelid
     * @return bool Return true if successful.
     */
    private static function delete_unpopulated($level, $levelid) {
        global $DB;

        switch($level) {
            case 1:
                // Cascade down from level 1.
                $sqll3 = <<<EOF
DELETE FROM {openstudio_level3} l3
      WHERE l3.level2id IN 
        (SELECT l2.id 
           FROM {openstudio_level2} l2
           JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
          WHERE l1.id = ?)

EOF;

                $sqll2 = <<<EOF
DELETE FROM {openstudio_level2} l2
      WHERE l2.level1id = 
        (SELECT l1.id
        FROM {openstudio_level1} l1
        WHERE l1.id = ?)

EOF;

                $sqll1 = <<<EOF
DELETE FROM {openstudio_level1} l1
WHERE l1.id = ?

EOF;

                $params = array($levelid);
                break;

            case 2:
                // Cascade down from level 2.
                $sqll3 = <<<EOF
DELETE FROM {openstudio_level3} l3
      WHERE l3.level2id IN 
        (SELECT l2.id 
        FROM {openstudio_level2} l2 
        WHERE l2.id = ?)

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

        $transaction = $DB->start_delegated_transaction();

        try {
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
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return false;
    }

    /**
     * This function either soft deletes a level by setting its status to -1 or undoes
     * soft delete by setting the status to 0.
     *
     * Cascades down as needed.
     *
     * @param int $level
     * @param int $levelid
     * @param bool $undo If true, will UNDO the soft delete and set status 0 and WILL cascae down to all levels.
     * @return bool Return true if operation is successful.
     */
    public static function soft_delete($level, $levelid, $undo = false) {
        global $DB;

        if ($undo) {
            $newstatus = self::ACTIVE;
        } else {
            $newstatus = self::SOFT_DELETED;
        }

        switch($level) {
            case 1:
                // Cascade down from level 1.
                $sqll3 = <<<EOF
UPDATE {openstudio_level3}
   SET status = ?
 WHERE level2id IN (SELECT l3.level2id
                      FROM (SELECT * FROM {openstudio_level3}) l3
                              JOIN {openstudio_level2} l2 ON l3.level2id = l2.id
                              JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
                             WHERE l1.id = ?)

EOF;

                $sqll2 = <<<EOF
UPDATE {openstudio_level2}
   SET status = ?
 WHERE level1id IN (SELECT l2.level1id
                      FROM (SELECT * FROM {openstudio_level2}) l2
                              JOIN {openstudio_level1} l1 ON l2.level1id = l1.id
                             WHERE l1.id = ?)

EOF;

                $sqll1 = <<<EOF
UPDATE {openstudio_level1}
   SET status = ?
 WHERE {openstudio_level1}.id = ?

EOF;

                $params = array($newstatus, $levelid);
                break;

            case 2:
                // Cascade down from level 2.
                $sqll3 = <<<EOF
UPDATE {openstudio_level3}
   SET status = ?
 WHERE level2id IN (SELECT l3.level2id
                      FROM (SELECT * FROM {openstudio_level3}) l3
                              JOIN {openstudio_level2} l2 ON l3.level2id = l2.id
                             WHERE l2.id = ?)

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

        $transaction = $DB->start_delegated_transaction();

        try {
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
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return false;
    }

    /**
     * Default delete function. This is called in the controllers and determines whether to hard or soft delete.
     *
     * If there is content within a level it will be soft-deleted.  If it is empty, it will be hard deleted.
     *
     * @param int $level level to create (1, 2, or 3)
     * @param int $levelid Id for the level record.
     * @param bool $studioid The ID of the studio to check for content.
     * @return bool $result
     */
    public static function delete($level, $levelid, $studioid) {
        // Get all contents.
        $contents = content::get_all_records($studioid);

        if (in_array($level, range(1, 3))) {
            if ($contents) {
                $activecontents = self::count_contents_in_level($level, $levelid, $contents);
            } else {
                $activecontents = 0;
            }

            if ($activecontents > 0) {
                // This has active contents, softdelete.
                if (!self::soft_delete($level, $levelid)) {
                    return false;
                }
            } else {
                // Hard delete.
                if (!self::delete_unpopulated($level, $levelid)) {
                    return false;
                }
            }

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
    public static function cleanup_sortorder($level, $parentid) {
        $records = self::get_records($level, $parentid);

        // Loop through and fix sortorder.
        $sortorder = 0;
        foreach ($records as $r) {
            $sortorder++;

            // Reset sortorder.
            $r->sortorder = $sortorder;
            self::update($level, $r->id, $r);
        }
    }

    /**
     * Gets the latest sort order number.
     *
     * @param int $level
     * @param int $parentid
     * @return bool Return true if successful.
     */
    public static function get_latest_sortorder($level, $parentid) {
        global $DB;

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
     * @param \moodle_recordset $studiocontents
     * @return mixed Return content counts or false if error.
     */
    public static function count_contents_in_level($level, $levelid, $studiocontents) {
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

    /**
     * Reads the import XML, verifies it and uses it to generate a series of DB INSERT statements
     * to create the block/activity structure as described in the XML.
     *
     * Existing block/activity structure is soft deleted first.
     *
     * @param int $studioid Studio ID to import block structure into.
     * @param string $xml The import XML to verify and use to import the requested structure.
     * @return mixed Returns false if error occurred, otherwise true.
     */
    public static function import_xml($studioid, $xml) {
        global $DB;

        $xml = self::fixup_legacy_xml($xml);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml);
        if ($xml === false) {
            return false;
        }

        $transaction = $DB->start_delegated_transaction();

        try {
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
                                            if ((int) $content->contenttype == content::TYPE_FOLDER) {
                                                $dataobject['contenttype'] = content::TYPE_FOLDER;
                                            }
                                        }

                                        $contentid = $DB->insert_record('openstudio_level3', (object) $dataobject, true);

                                        if ($dataobject['contenttype'] == content::TYPE_FOLDER && isset($content->template)) {
                                            $dataobject = (object) array(
                                                    'levelcontainer'  => 3,
                                                    'levelid'         => $contentid,
                                                    'guidance'        => $content->template->guidance . '',
                                                    'additionalcontents' => (int) $content->template->additionalcontents,
                                                    'status'          => self::ACTIVE
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
                                                            'status'        => self::ACTIVE
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
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return false;
    }

    /**
     * If we get XML that calls things "slot"s, change it to "content"
     *
     * @param string $xml The original XML string
     * @return string The fixed XML.
     */
    public static function fixup_legacy_xml($xml) {
        return preg_replace('~(</?(additional)?)slot((s|order)?>)~', '\1content\3', $xml);
    }

    /**
     * Get all activities.
     *
     * @param int $id
     * @return array|bool
     */
    public static function get_all_activities(int $id) {
        $blocks = static::get_records(1, $id);

        if (!empty($blocks)) {
            foreach ($blocks as $block) {
                $block->activities = static::get_records(2, $block->id);
            }
        }

        return $blocks;
    }
}
