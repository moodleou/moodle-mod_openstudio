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
 * API functions for folder feature.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

use mod_openstudio\local\util\defaults;
use mod_openstudio\local\util\feature;

class folder {

    const PERMISSION_REORDER = 1;

    const PROVENANCE_UNLINKED = 2;
    const PROVENANCE_EDITED = 1;
    const PROVENANCE_COPY = 0;

    /**
     * Add a content post to a folder
     *
     * @param int $folderid The folder to add the content to.
     * @param int $contentid The content to add to the folder
     * @param int $userid The idea of the user performing the action
     * @param object|array $foldercontent The data to add to the foldercontent record
     * @param int $context
     * @return bool|int Return ID if record creation was succesful, or false if not.
     */
    public static function add_content($folderid, $contentid, $userid, $foldercontent = [], $context = 0) {
        global $DB;

        $foldercontent = (object) $foldercontent;
        $insertdata = new \stdClass();

        try {
            $folder = content::get($folderid);
            $content = content::get($contentid);
            if ($folder && $content) {
                $params = ['folderid' => $folderid, 'contentid' => $contentid, 'status' => levels::ACTIVE];
                if ($DB->record_exists('openstudio_folder_contents', $params)) {
                    // Don't add the same content to the folder more than once!
                    return true;
                }

                $insertdata->contentid = $content->id;
                $insertdata->folderid = $folder->id;

                if (isset($foldercontent->name)) {
                    $insertdata->name = $foldercontent->name;
                }
                if (isset($foldercontent->description)) {
                    $insertdata->description = $foldercontent->description;
                }
                $foldercontents = self::get_contents_with_templates($folderid);
                if (isset($foldercontent->contentorder)) {
                    $counter = 0;
                    $position = 0;
                    foreach ($foldercontents as $foldercontentinstance) {
                        $counter++;
                        $realcontent = !isset($foldercontent->template);
                        if ($realcontent && ($foldercontentinstance->contentorder == $foldercontent->contentorder)) {
                            $position = $counter;
                        }
                        if ($realcontent && ($foldercontentinstance->contentorder > $foldercontent->contentorder)) {
                            $DB->set_field('openstudio_folder_contents', 'contentorder', $counter,
                                    ['folderid' => $folderid, 'contentid' => $foldercontentinstance->id]);
                        }
                    }
                    $insertdata->contentorder = $position;
                } else {
                    // If no slotorder is specified, default to the number of slots in the folder, plus 1.
                    $contentcount = count($foldercontents);
                    $insertdata->contentorder = $contentcount + 1;
                }
                if (isset($foldercontent->foldercontenttemplateid)) {
                    $insertdata->foldercontenttemplateid = $foldercontent->foldercontenttemplateid;
                }
                if (isset($foldercontent->provenanceid)) {
                    $insertdata->provenanceid = $foldercontent->provenanceid;
                    $insertdata->provenancestatus = self::PROVENANCE_COPY;
                }
                $insertdata->contentmodified = $content->timemodified;
                $insertdata->timemodified = time();

                $foldercontentid = $DB->insert_record('openstudio_folder_contents', $insertdata);

                if (isset($foldercontent->provenanceid) && $content->contenttype <= content::TYPE_TEXT) {
                    // If we've just copied a textual content to a folder, update its contenthash
                    // to use the provenance ID, so its content hash matches the original.
                    item::delete($contentid, item::CONTENT);
                    item::log($contentid);
                }

                // Update folder content last modified time and showextradata.
                $updatedata = new \stdClass();
                $updatedata->timemodified = time();
                $updatedata->showextradata = 0;
                $updatedata->id = $folderid;

                $DB->update_record('openstudio_contents', $updatedata);
                tracking::log_action($folderid, tracking::MODIFY_FOLDER, $userid);
                switch ($context) {
                    case 1:
                        tracking::log_action($contentid, tracking::LINK_CONTENT_TO_FOLDER, $userid, $folderid);
                        break;

                    case 2:
                        tracking::log_action($contentid, tracking::COPY_CONTENT_TO_FOLDER, $userid, $folderid);
                        break;

                    case 0:
                    default:
                        tracking::log_action($contentid, tracking::ADD_CONTENT_TO_FOLDER, $userid, $folderid);
                        break;
                }
                return $foldercontentid;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add an existing content post to the folder
     *
     * If the content belongs to the user and $softlink is true, just adds the existing
     * content to the folder with {@see self::add_content}.  Otherwise, if allowed,
     * created a copy of the content belonging to the folder's owner, and adds the copy
     * to the folder.
     *
     * @param int $folderid The folder the content is being added to
     * @param int $contentid The content to collect
     * @param int $userid The user to log the action against
     * @param int $contenttemplateid Optional, the ID of the templated slot to fill with the collected content.
     * @param bool $softlink If true, create a soft link to the existing content, if false create a copy within the folder.
     * @return int ID of the slot that was added to the set, or false if the slot can't be collected
     * @throws \coding_exception
     */
    public static function collect_content($folderid, $contentid, $userid, $contenttemplateid = null, $softlink = false) {
        global $DB;

        $set = self::get($folderid);
        if (!$set) {
            throw new \coding_exception('Folder does not exist.');
        }
        $slot = content::get($contentid);
        if (!$slot) {
            throw new \coding_exception('Content does not exist.');
        }
        $studio = $DB->get_record('openstudio', ['id' => $set->openstudioid]);
        $cm = get_coursemodule_from_instance('openstudio', $studio->id);

        // First, check that both the set and the slot are in the same studio.
        if ($set->openstudioid != $slot->openstudioid) {
            throw new \coding_exception('Content and folder do not belong to the same Studio instance.');
        }

        $ownslot = $set->userid == $slot->userid;

        if (!empty($contenttemplateid)) {
            $setslottemplate = template::get_content($contenttemplateid);
            $setslottemplate->foldercontenttemplateid = $setslottemplate->id;
            $setslottemplate = (array) $setslottemplate;
        } else {
            $setslottemplate = [];
        }
        $setslottemplate['provenanceid'] = self::determine_content_provenance($contentid);

        if ($softlink) {
            if (!$ownslot) {
                throw new \coding_exception('Attempted to soft-link a different user\'s content.');
            }

            if (self::add_content($folderid, $contentid, $set->userid, $setslottemplate, 1)) {
                return $contentid;
            } else {
                return false;
            }
        } else {
            if (!$ownslot) {
                if (($studio->themefeatures & feature::ENABLEFOLDERSANYCONTENT) != feature::ENABLEFOLDERSANYCONTENT) {
                    throw new \coding_exception('Copying a different user\'s content is not enabled in this studio.');
                }
            }
            $newcontent = self::copy_content($contentid, $set->userid, null, null, $cm);

            tracking::log_action($contentid, tracking::COPY_CONTENT, $userid);
            if (self::add_content($folderid, $newcontent->id, $set->userid, $setslottemplate, 2)) {
                return $newcontent->id;
            } else {
                return false;
            }
        }
    }

    /**
     * Return the content record for the folder.
     *
     * A folder is really just a content record with contenttype == content::TYPE_FOLDER
     *
     * @param int $folderid set id.
     * @return object|bool Return the folder record, or false if it doesn't exist.
     */
    public static function get($folderid) {
        global $DB;

        $sql = <<<EOF
         SELECT l3.id AS l3id, l2.id AS l2id, l1.id AS l1id,
                l3.name AS l3name, l2.name AS l2name, l1.name AS l1name,
                u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
                u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                s.*
           FROM {openstudio_contents} s
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = s.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
     INNER JOIN {user} u ON u.id = s.userid
          WHERE s.id = ?
            AND s.contenttype = ?

EOF;
        return $DB->get_record_sql($sql, ['id' => $folderid, content::TYPE_FOLDER]);
    }

    /**
     * Return the folder content and associated content requested by content id.
     *
     * @param int $folderid folder id.
     * @param int $contentid set slot id.
     * @return object|bool The slot record, with additional fields from the set slot record.
     */
    public static function get_content($folderid, $contentid) {
        global $DB;

        $sql = <<<EOF
  SELECT s.*, fc.id AS fcid, fc.folderid, fc.name AS fcname,
         fc.description AS fcdescription, fc.foldercontenttemplateid as fctemplateid,
         fc.contentorder, fc.timemodified AS setslottimemodified,
         fc.provenanceid, fc.provenancestatus
    FROM {openstudio_contents} s
    JOIN {openstudio_folder_contents} fc ON s.id = fc.contentid
   WHERE fc.folderid = :folderid
     AND fc.contentid = :contentid
     AND fc.status > :status
ORDER BY fc.contentorder ASC
EOF;

        $params = [
            'folderid' => $folderid,
            'contentid' => $contentid,
            'status' => levels::SOFT_DELETED
        ];

        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Get the folder that contains the content.
     *
     * If the content is on more than one folder, this will return the first that is was added to, and not deleted from.
     *
     * @param $contentid
     * @return object|false
     */
    public static function get_containing_folder($contentid) {
        global $DB;
        $sql = <<<EOF
  SELECT c1.*
    FROM {openstudio_contents} c
    JOIN {openstudio_folder_contents} fc ON c.id = fc.contentid
    JOIN {openstudio_contents} c1 ON fc.folderid = c1.id
   WHERE c.id = ?
     AND fc.status > ?
ORDER BY fc.timemodified
   LIMIT 1
EOF;

        $params = [$contentid, levels::SOFT_DELETED];
        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Return the content records plus folder content data for each content within the folder
     *
     * @param int $folderid the set id
     * @return array The content records with additional fields from the folder content records, sorted by contentorder, keyed by ID
     */
    public static function get_contents($folderid) {
        global $DB;

        $sql = <<<EOF
    SELECT c.*, fc.id AS fcid, fc.folderid, fc.name AS fcname,
           fc.description AS fcdescription,
           fc.contentorder, fc.foldercontenttemplateid, fc.timemodified AS fctimemodified,
           fc.provenancestatus, fc.provenanceid
      FROM {openstudio_contents} c
      JOIN {openstudio_folder_contents} fc ON c.id = fc.contentid
     WHERE fc.folderid = :folderid
       AND fc.status != :status
  ORDER BY fc.contentorder ASC
EOF;

        $slots = $DB->get_records_sql($sql, ['folderid' => $folderid, 'status' => levels::SOFT_DELETED]);
        foreach ($slots as $slot) {
            if (!empty($slot->provenanceid) && $slot->provenancestatus == self::PROVENANCE_EDITED) {
                if (!empty($slot->fcname)) {
                    $slot->name = $slot->fcname;
                }
                if (!empty($slot->fcdescription)) {
                    $slot->description = $slot->fcdescription;
                }
            }
        }
        return $slots;
    }

    /**
     * Return the archived versions of soft-deleted folder contents
     *
     * @param int $folderid the set id
     * @return array The content records with additional fields from the folder content records, sorted by contentorder, keyed by ID
     */
    public static function get_deleted_contents($folderid) {
        global $DB;

        $sql = <<<EOF
    SELECT cv.*, fc.id AS foldercontentid, fc.folderid, fc.name AS fcname,
           fc.description AS fcdescription,
           fc.contentorder, fc.foldercontenttemplateid, fc.timemodified AS fctimemodified,
           fc.provenancestatus, fc.provenanceid
      FROM {openstudio_content_versions} cv
      JOIN {openstudio_folder_contents} fc ON cv.contentid = fc.contentid
     WHERE fc.folderid = :folderid
       AND fc.status = :status
  ORDER BY fc.contentorder ASC
EOF;

        $slots = $DB->get_records_sql($sql, ['folderid' => $folderid, 'status' => levels::SOFT_DELETED]);
        foreach ($slots as $slot) {
            if (!empty($slot->provenanceid) && $slot->provenancestatus == self::PROVENANCE_EDITED) {
                if (!empty($slot->fcname)) {
                    $slot->name = $slot->fcname;
                }
                if (!empty($slot->fcdescription)) {
                    $slot->description = $slot->fcdescription;
                }
            }
        }
        return $slots;
    }

    /**
     * Return the slot records as with folder::get_contents() but also return any template records for preconfigured but as-yet
     * uninstantiated contents.
     *
     * @param int $folderid The ID of the set to get the slots for
     * @return array The content records with additional folder content fields, plus the openstudio_folder_slot_template
     *               records, keyed by contentorder
     */
    public static function get_contents_with_templates($folderid) {
        global $DB;
        // Get slots.
        $realcontents = self::get_contents($folderid);

        // Get slot templates.
        $sql = <<<EOF
    SELECT sst.*, sst.id AS foldercontenttemplateid, 1 AS template
      FROM {openstudio_content_templates} sst
      JOIN {openstudio_folder_templates} st ON sst.foldertemplateid = st.id
      JOIN {openstudio_level3} l ON st.levelid = l.id
      JOIN {openstudio_contents} s ON s.levelid = l.id
     WHERE s.id = ?
       AND sst.status = ?
       AND st.status = ?
  ORDER BY sst.contentorder
EOF;

        $templates = $DB->get_records_sql($sql, [$folderid, levels::ACTIVE, levels::ACTIVE]);
        $sortedcontents = [];

        // Add real slots.
        foreach ($realcontents as $realcontent) {
            $sortedcontents[$realcontent->contentorder] = $realcontent;
        }

        foreach ($templates as $template) {
            $isfound = false;
            foreach ($realcontents as $realcontent) {
                if ($realcontent->foldercontenttemplateid == $template->id) {
                    $isfound = true;
                }
            }
            if (!$isfound) {
                if (isset($sortedcontents[$template->contentorder])) {
                    // See if there's a space to backfill.
                    for ($i = 1; $i < $template->contentorder; $i++) {
                        if (!isset($sortedcontents[$i])) {
                            $sortedcontents[$i] = $template;
                        }
                    }
                    $tryposition = $template->contentorder;
                    while (!in_array($template, $sortedcontents)) {
                        // Find the next available position.
                        $tryposition++;
                        if (!isset($sortedcontents[$tryposition])) {
                            $sortedcontents[$tryposition] = $template;
                        }
                    }
                } else {
                    // Check we haven't got an empty position to backfill.
                    for ($i = 1; $i < $template->contentorder; $i++) {
                        if (!isset($sortedcontents[$i])) {
                            $sortedcontents[$i] = $template;
                        }
                    }
                    if (!in_array($template, $sortedcontents)) {
                        $sortedcontents[$template->contentorder] = $template;
                    }
                }
            }
        }

        ksort($sortedcontents);
        return array_values($sortedcontents);

    }

    /**
     * Return the first content item in the folder (or the template for the first content if it's unfilled)
     *
     * @param int $folderid set id.
     * @param bool $excludetemplates true to show only real slots.
     * @return object|bool Return the folder content record, or false if there is none.
     */
    public static function get_first_content($folderid, $excludetemplates = false) {
        $contents = self::get_contents_with_templates($folderid);

        if ($excludetemplates) {
            $contents = self::filter_empty_templates($contents);
        }

        if (empty($contents)) {
            return false;
        } else {
            return reset($contents);
        }
    }

    /**
     * Update set slot record.
     *
     * @param object $foldercontent set slot record.
     * @return bool True if record update was succesful.
     * @throws \coding_exception If an invalid ID or no ID was passed in the $setslot object
     */
    public static function update_content($foldercontent) {
        global $DB;

        if (!isset($foldercontent->id)) {
            throw new \coding_exception('$foldercontent passed to folder::update_content must contain an id');
        }
        $updatedata = (object) [];

        $foldercontentrecord = $DB->get_record('openstudio_folder_contents', ['id' => $foldercontent->id]);
        if ($foldercontentrecord) {
            try {

                $updatedata->id = $foldercontentrecord->id;

                if (isset($foldercontent->contentid)) {
                    $updatedata->contentid = $foldercontent->contentid;
                }
                if (isset($foldercontent->name)) {
                    $updatedata->name = $foldercontent->name;
                }
                if (isset($foldercontent->description)) {
                    $updatedata->description = $foldercontent->description;
                }
                if (isset($foldercontent->contentorder)) {
                    $updatedata->contentorder = $foldercontent->contentorder;
                }
                if (isset($foldercontent->foldercontenttemplateid)) {
                    $updatedata->foldercontenttemplateid = $foldercontent->foldercontenttemplateid;
                }
                if (isset($foldercontent->provenancestatus)) {
                    $updatedata->provenancestatus = $foldercontent->provenancestatus;
                }
                $updatedata->timemodified = time();

                $DB->update_record('openstudio_folder_contents', $updatedata);

                // Update set slot last modified time.
                $DB->set_field('openstudio_contents', 'timemodified', time(), ['id' => $foldercontentrecord->contentid]);

            } catch (\Exception $e) {
                return false;
            }
        } else {
            throw new \coding_exception('$foldercontent passed to folder::update_content '.
                    'must contain an id for a record that exists in openstudio_folder_contents');
        }

        return true;
    }

    /**
     * Set the contentorders for a content within a folder
     *
     * Convenience function for doing a quick update when we're just re-ordering contents.
     *
     * @param int $folderid The ID of the folder the contents belong to
     * @param array $contentorders An array of new contentorders, keyed by current contentorders
     * @return bool True of the contentorders are updated successfully
     * @throws \coding_exception
     */
    public static function update_contentorders($folderid, $contentorders) {
        global $DB;

        // Check that we're not trying to put 2 slots in the same position.
        $contentordercounts = array_count_values($contentorders);
        arsort($contentordercounts);
        foreach ($contentordercounts as $contentordercount) {
            if ($contentordercount != 1) {
                throw new \coding_exception('$contentorders array passed to folder::update_contentorders '.
                        'cannot contain the same value more than once');
            }
        }

        // Remove any contents that aren't being updated.
        $updatedorders = [];
        foreach ($contentorders as $position => $neworder) {
            if ($position + 1 != $neworder) {
                $updatedorders[$position] = $neworder;
            }
        }
        if (empty($updatedorders)) {
            // Nothing to do.
            return true;
        }

        // Get contents and templates in their current order.
        $contentsandtemplates = self::get_contents_with_templates($folderid);

        // Update the contents to their new order.
        foreach ($updatedorders as $position => $neworder) {
            if (!array_key_exists($position, $contentsandtemplates)) {
                throw new \coding_exception('new contentorders passed to folder::update_contentorders '
                        . 'must be greater than 1 and less than the number of slots and '
                        . 'templates in the set.');
            }
            $foldercontent = $contentsandtemplates[$position];
            if (isset($foldercontent->template)) {
                throw new \coding_exception('folder::update_contentorders can only update the contentorder '
                        . 'of slots that have been filled, not templates.');
            }
            $updaterecord = (object) [
                    'id' => $foldercontent->fcid,
                    'contentorder' => $neworder,
                    'timemodified' => time()
            ];
            $DB->update_record('openstudio_folder_contents', $updaterecord, true);
        }

        $DB->set_field('openstudio_contents', 'timemodified', time(), ['id' => $folderid]);
        return true;
    }

    /**
     * Delete content from folder.
     *
     * @param int $folderid The ID of the set to remove the slot from.
     * @param int $contentid The ID of the slot to remove
     * @param int $userid The ID of the user performing the deletion.
     * @return bool True if the slot was removed from the set.
     * @throws \coding_exception if the passed $folderid and $contentid don't match any records in openstudio_folder_contents
     */
    public static function remove_content($folderid, $contentid, $userid) {
        global $DB;

        $params = ['folderid' => $folderid, 'contentid' => $contentid, 'status' => levels::ACTIVE];
        if (!$foldercontentrecord = $DB->get_record('openstudio_folder_contents', $params)) {
            throw new \coding_exception(
                    '$folderid and $contentid passed to folder::remove_content must exist in a record in ' .
                    'openstudio_folder_contents');
        }
        try {
            $foldercontentrecord->status = levels::SOFT_DELETED;
            if ($DB->update_record('openstudio_folder_contents', $foldercontentrecord)) {
                $foldercontents = self::get_contents_with_templates($folderid);
                $counter = 0;
                foreach ($foldercontents as $foldercontent) {
                    $counter++;
                    if (!isset($foldercontent->template)) {
                        $DB->set_field('openstudio_folder_contents', 'contentorder', $counter,
                                ['folderid' => $folderid, 'contentid' => $foldercontent->id]);
                    }
                }

                tracking::log_action($folderid, tracking::MODIFY_FOLDER, $userid);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove all contents from a folder
     *
     * @param int $folderid The ID of the folder to be emptied.
     * @param int $userid The user performing the deletion, for logging.
     * @return int The number of openstudio_folder_contents records removed (0 if the set is already empty)
     * @throws \coding_exception If $folderid does not refer to a record in openstudio_contents
     */
    public static function remove_contents($folderid, $userid) {
        global $DB;

        if (!$DB->record_exists('openstudio_contents', ['id' => $folderid])) {
            throw new \coding_exception('$folderid passed to folder::empty must refer to the id of a '.
                    'record in openstudio_contents');
        }

        $params = ['folderid' => $folderid, 'status' => levels::ACTIVE];
        $foldercontents = $DB->get_records('openstudio_folder_contents', $params);
        if (count($foldercontents) > 0) {
            foreach ($foldercontents as $foldercontent) {
                $foldercontent->status = levels::SOFT_DELETED;
                $DB->update_record('openstudio_folder_contents', $foldercontent, true);
            }
        }

        tracking::log_action($folderid, tracking::MODIFY_FOLDER, $userid);

        return count($foldercontents);
    }

    /**
     * Count the number of actual content posts contained within a folder.
     *
     * @param $folderid int The ID of the set
     * @param $includetemplated bool Include all contents, or just non-templated "additional" contents?
     * @return int Return number of content posts in a folder.
     */
    public static function count_contents($folderid, $includetemplated = true) {
        global $DB;

        $sql = <<<EOL
    SELECT count(fc.id)
      FROM {openstudio_folder_contents} fc
     WHERE fc.folderid = ?
       AND fc.status = 0
EOL;

        if (!$includetemplated) {
            $sql .= ' AND fc.foldercontenttemplateid IS NULL';
        }

        return $DB->count_records_sql($sql, [$folderid]);
    }

    /**
     * Determine the provenance for a new copy based on the content we're copying from
     *
     * If the source content is not in a folder, has no provenance of its own, is it's own provenance,
     * or has been edited since being copied, then use the source content's ID for provenance.
     * If the source content is an unedited copy of another content, return it's own provenanceid for
     * the new copy's provenance.
     *
     * @param int $sourcecontentid The ID of the content we are copying from
     * @return int The ID of the content to use for provenance
     * @throws \coding_exception if we pass a content ID that doesn't exist
     */
    public static function determine_content_provenance($sourcecontentid) {
        global $DB;

        if (!$DB->record_exists('openstudio_contents', ['id' => $sourcecontentid, 'deletedtime' => null])) {
            throw new \coding_exception('Source content does not exist');
        }

        $params = ['contentid' => $sourcecontentid, 'status' => levels::ACTIVE];
        // If we get more than one result, then it's becuase we have multiple soft-links, which will
        // all have the same provenance so we can use IGNORE_MULTIPLE safely.
        $sourcecontent = $DB->get_record('openstudio_folder_contents', $params, '*', IGNORE_MULTIPLE);

        if (!$sourcecontent || empty($sourcecontent->provenanceid) || $sourcecontent->provenanceid == $sourcecontentid
                || $sourcecontent->provenancestatus != self::PROVENANCE_COPY
        ) {
            return $sourcecontentid;
        } else {
            return $sourcecontent->provenanceid;
        }
    }

    /**
     * Return the provenance data for a content post
     *
     * If the content belongs to a folder, returns the ID of the content it was copied from,
     * the provenance status, and the owner of the original slot.
     *
     * @param int $folderid
     * @param int $contentid
     * @return \stdClass|null Database record containing provenance data
     */
    public static function get_content_provenance($folderid, $contentid) {
        global $DB;

        list($usql, $params) = $DB->get_in_or_equal([self::PROVENANCE_COPY, self::PROVENANCE_EDITED]);
        $where = 'provenancestatus '. $usql . ' AND contentid = ? AND status > ?';
        $params[] = $contentid;
        $params[] = levels::SOFT_DELETED;
        if (($folderid != null) && ($folderid > 0)) {
            $where .= ' AND folderid = ?';
            $params[] = $folderid;
        }
        // If there's more than one record, then they're all soft links so will have the same provenance,
        // so we can safely IGNORE_MULTIPLE.
        $setslot = $DB->get_record_select('openstudio_folder_contents', $where, $params, '*', IGNORE_MULTIPLE);
        if ($setslot) {
            $sql = <<<EOF
            SELECT c.id, c.name, u.id as userid, u.firstname, u.lastname, u.middlename, u.firstnamephonetic,
                   u.lastnamephonetic, u.alternatename, fc.id as foldercontentid, fc.contentid as slotid,
                   fc.name as fcname, fc.description as fcdescription, fc.provenanceid, fc.provenancestatus
              FROM {openstudio_contents} c
              JOIN {openstudio_folder_contents} fc ON fc.provenanceid = c.id
              JOIN {user} u ON c.userid = u.id
             WHERE fc.id = ?
EOF;

            return $DB->get_record_sql($sql, [$setslot->id]);
        }
        return null;
    }

    /**
     * Get folder content records for all copies of the given slot
     *
     * @param $contentid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_content_copies($contentid) {
        global $DB;

        list($usql, $params) = $DB->get_in_or_equal([self::PROVENANCE_COPY, self::PROVENANCE_EDITED]);
        $where = 'provenancestatus '. $usql . ' AND provenanceid = ? AND contentid != ?';
        $params = array_merge($params, [$contentid, $contentid]);
        return $DB->get_records_select('openstudio_folder_contents', $where, $params);
    }

    /**
     * Get folder content records for all soft links of the given content
     *
     * This function is only meant for internal use so that copies can be created
     * when a soft-linked slot is edited
     *
     * @param $contentid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_content_softlinks($contentid) {
        global $DB;

        $params = ['provenanceid' => $contentid, 'contentid' => $contentid];
        return $DB->get_records('openstudio_folder_contents', $params);
    }

    /**
     * Creates a copy of the cotnent post that's only visible within a folder.
     *
     * @param int $userid The user copying the content.
     * @param int $contentid The ID of the content to copy.
     * @param string $name New name to override the original content name.
     * @param string $description New description to override the original description.
     * @param object $cm Course module record for search indexing.
     * @throws \dml_exception If there is no content record matching $contentid.
     * @return bool|mixed
     */
    public static function copy_content($contentid, $userid, $name = '', $description = '', $cm = null) {
        global $DB;

        $insertdata = $DB->get_record('openstudio_contents', ['id' => $contentid], '*', MUST_EXIST);

        $insertdata->levelid = 0;
        $insertdata->levelcontainer = 0;
        $insertdata->visibility = content::VISIBILITY_INFOLDERONLY;
        $insertdata->userid = $userid;
        $insertdata->deletedby = null;
        $insertdata->deletedtime = null;
        $insertdata->timemodified = time();

        if (!empty($name)) {
            $insertdata->name = $name;
        }
        if (!empty($description)) {
            $insertdata->description = $description;
        }

        $insertdata->id = $DB->insert_record('openstudio_contents', $insertdata);

        // Index new content for search.
        if ($cm != null) {
            $newcontentdata = content::get_record($userid, $insertdata->id);
            search::update($cm, $newcontentdata);
        }

        // Log the contenthash for the new slot.
        item::log($insertdata->id);

        return $insertdata;
    }

    /**
     * Calculate the number of slots allowed to be added to a given set
     *
     * Pinboard slots are limited by the pinboardsetlimit.  Pre-defined sets are limited
     * by their template's additonalslots setting, or the default value for that setting.
     *
     * @param int $limit The pinboardfolderlimit setting for the studio instance.
     * @param int $folderid Optional, the ID of the folder
     * @param int $levelid Optional, the ID of the level for the pre-definted folder
     * @return int The maximum number of contents allowed to be added
     */
    public static function get_addition_limit($limit, $folderid = 0, $levelid = 0) {

        $currentcontents = 0;
        if (!empty($folderid)) {
            $folder = self::get($folderid);
            $currentcontents = self::count_contents($folderid, false);
            $levelid = $folder->levelid;
        }
        if ($levelid) {
            $foldertemplate = template::get_by_levelid($levelid);
            if ($foldertemplate) {
                $limit = $foldertemplate->additionalcontents;
            } else {
                $limit = defaults::FOLDERTEMPLATEADDITIONALCONTENTS;
            }
        }

        return $limit - $currentcontents;
    }

    /**
     * Filter a list of folder contents to remove those which are empty templates.
     *
     * @param $contents
     * @return array
     */
    public static function filter_empty_templates($contents) {
        return array_filter($contents, function($content) {
            return !isset($content->template);
        });
    }
}
