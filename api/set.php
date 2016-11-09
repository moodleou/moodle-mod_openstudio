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
use mod_openstudio\local\api\folder;
use mod_openstudio\local\api\item;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\api\tracking as tracking1;
use mod_openstudio\local\util\defaults;
use mod_openstudio\local\util\feature;

/**
 * Add a slot to a set
 *
 * @param int setid The set to add the slot to.
 * @param int slotid The slot to add to the set
 * @param int userid The idea of the user performing the action
 * @param object|array $setslot The data to add to the setslot record
 * @param int context
 * @return object Return true if record update was succesful.
 */
function studio_api_set_slot_add($setid, $slotid, $userid, $setslot = array(), $context = 0) {
    global $DB;

    $setslot = (object) $setslot;
    $slotinsertdata = new stdClass();

    try {
        $set = mod_openstudio\local\api\content::get($setid);
        $slot = mod_openstudio\local\api\content::get($slotid);
        if ($set && $slot) {
            $params = array('folderid' => $setid, 'contentid' => $slotid, 'status' => levels::NORMAL);
            if ($DB->record_exists('openstudio_folder_contents', $params)) {
                // Don't add the same slot to the set more than once!
                return true;
            }

            $slotinsertdata->contentid = $slot->id;
            $slotinsertdata->folderid = $set->id;

            if (isset($setslot->name)) {
                $slotinsertdata->name = $setslot->name;
            }
            if (isset($setslot->description)) {
                $slotinsertdata->description = $setslot->description;
            }
            if (isset($setslot->contentorder)) {
                $setslots = studio_api_set_slots_get_with_templates($setid);
                $counter = 0;
                $position = 0;
                foreach ($setslots as $setslotinstance) {
                    $counter++;
                    if (!isset($isnotrealslot) && ($setslotinstance->contentorder == $setslot->contentorder)) {
                        $position = $counter;
                    }
                    if (!isset($isnotrealslot) && ($setslotinstance->contentorder > $setslot->contentorder)) {
                        $DB->set_field('openstudio_folder_contents', 'contentorder', $counter,
                                array('folderid' => $setid, 'contentid' => $setslotinstance->id));
                    }
                }
                $slotinsertdata->contentorder = $position;
            } else {
                // If no slotorder is specified, default to the number of slots in the set, plus 1.
                $slotcount = count(studio_api_set_slots_get_with_templates($set->id));
                $slotinsertdata->contentorder = $slotcount + 1;
            }
            if (isset($setslot->foldercontenttemplateid)) {
                $slotinsertdata->foldercontenttemplateid = $setslot->foldercontenttemplateid;
            }
            if (isset($setslot->provenanceid)) {
                $slotinsertdata->provenanceid = $setslot->provenanceid;
                $slotinsertdata->provenancestatus = folder::PROVENANCE_COPY;
            }
            $slotinsertdata->contentmodified = $slot->timemodified;
            $slotinsertdata->timemodified = time();

            $setslotid = $DB->insert_record('openstudio_folder_contents', $slotinsertdata);

            if (isset($setslot->provenanceid) && $slot->contenttype <= content::TYPE_TEXT) {
                // If we've just copied a textual slot to a set, update its contenthash
                // to use the provenance ID, so its content hash matches the original.
                studio_api_item_delete($slotid, item::CONTENT);
                studio_api_item_log($slotid);
            }

            // Update set slot last modified time.
            $DB->set_field('openstudio_contents', 'timemodified', time(), array('id' => $setid));
            studio_api_tracking_log_action($setid, tracking1::MODIFY_FOLDER, $userid);
            switch ($context) {
                case 1:
                    studio_api_tracking_log_action($slotid, tracking1::LINK_CONTENT_TO_FOLDER, $userid, $setid);
                    break;

                case 2:
                    studio_api_tracking_log_action($slotid, tracking1::COPY_CONTENT_TO_FOLDER, $userid, $setid);
                    break;

                case 0:
                default:
                    studio_api_tracking_log_action($slotid, tracking1::ADD_CONTENT_TO_FOLDER, $userid, $setid);
                    break;
            }
            return $setslotid;
        } else {
            return false;
        }
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Add an existing slot to the set
 *
 * If the slot belongs to the user and $softlink is true, just adds the existing
 * slot to the set with {@see studio_api_set_slot_add}.  Otherwise, if allowed,
 * created a copy of the slot belonging to the set's owner, and adds the copy
 * to the set.
 *
 * @param int $setid The set the slot is being added to
 * @param int $slotid The slot to collect
 * @return int ID of the slot that was added to the set, or false if the slot can't be collected
 * @throws coding_exception
 */
function studio_api_set_slot_collect($setid, $slotid, $userid, $setslottemplateid = null, $softlink = false) {
    global $DB;

    $set = studio_api_set_containing_slot_get_by_setid($setid);
    if (!$set) {
        throw new coding_exception('Set does not exist.');
    }
    $slot = mod_openstudio\local\api\content::get($slotid);
    if (!$slot) {
        throw new coding_exception('Slot does not exist.');
    }
    $studio = $DB->get_record('openstudio', array('id' => $set->openstudioid));
    $cm = get_coursemodule_from_instance('openstudio', $studio->id);

    // First, check that both the set and the slot are in the same studio.
    if ($set->openstudioid != $slot->openstudioid) {
        throw new coding_exception('Slot and set do not belong to the same Studio instance.');
    }

    $ownslot = $set->userid == $slot->userid;

    if (!empty($setslottemplateid)) {
        $setslottemplate = studio_api_set_template_slot_get($setslottemplateid);
        $setslottemplate->foldercontenttemplateid = $setslottemplate->id;
        $setslottemplate = (array) $setslottemplate;
    } else {
        $setslottemplate = array();
    }
    $setslottemplate['provenanceid'] = studio_api_set_slot_determine_provenance($slotid);

    if ($softlink) {
        if (!$ownslot) {
            throw new coding_exception('Attempted to soft-link a different user\'s slot.');
        }

        if (studio_api_set_slot_add($setid, $slotid, $set->userid, $setslottemplate, 1)) {
            return $slotid;
        } else {
            return false;
        }
    } else {
        if (!$ownslot) {
            if (($studio->themefeatures & feature::ENABLEFOLDERSANYCONTENT) != feature::ENABLEFOLDERSANYCONTENT) {
                throw new coding_exception('Copying a different user\'s slot is not enabled in this studio.');
            }
        }
        $newslot = studio_api_slot_copy_to_pinboard($set->userid, $slotid, $cm);
        studio_api_set_move_slot($newslot->id);

        studio_api_tracking_log_action($slotid, tracking1::COPY_CONTENT, $userid);
        if (studio_api_set_slot_add($setid, $newslot->id, $set->userid, $setslottemplate, 2)) {
            return $newslot->id;
        } else {
            return false;
        }
    }
}

/**
 * Create a set template.
 *
 * @param int $levelcontainer 1, 2, or 3 (probably 3), The level that the template is at
 * @param int $levelid The ID of the level the template is attached to
 * @param object|array $settemplate Data to be added to the template
 * @return boolean The ID of the created template, or false if failure.
 */
function studio_api_set_template_create($levelcontainer, $levelid, $settemplate = array()) {
    global $DB;

    $settemplate = (object) $settemplate;
    $templateinsertdata = new stdClass();

    try {
        $level = studio_api_levels_get_record($levelcontainer, $levelid);
        if ($level) {
            $templateinsertdata->levelid = $level->id;
            $templateinsertdata->levelcontainer = $levelcontainer;

            if (isset($settemplate->guidance)) {
                $templateinsertdata->guidance = $settemplate->guidance;
            }
            if (isset($settemplate->additionalcontents)) {
                $templateinsertdata->additionalcontents = $settemplate->additionalcontents;
            }
            $templateinsertdata->status = levels::NORMAL;

            return $DB->insert_record('openstudio_folder_templates', $templateinsertdata);
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Create a new slot template
 *
 * @param object $slottemplate The slot template record to create.
 * @return int|boolean The ID of the created slot template, or false if failure.
 */
function studio_api_set_template_slot_create($settemplateid, $slottemplate) {
    global $DB;

    $slottemplate = (object) $slottemplate;
    $templateinsertdata = new stdClass();

    try {
        $settemplate = studio_api_set_template_get_by_id($settemplateid);
        if ($settemplate) {
            $templateinsertdata->foldertemplateid = $settemplate->id;

            if (isset($slottemplate->name)) {
                $templateinsertdata->name = $slottemplate->name;
            }
            if (isset($slottemplate->guidance)) {
                $templateinsertdata->guidance = $slottemplate->guidance;
            }
            if (isset($slottemplate->permissions)) {
                $templateinsertdata->permissions = $slottemplate->permissions;
            } else {
                $templateinsertdata->permissions = 0;
            }
            if (isset($slottemplate->contentorder) && $slottemplate->contentorder > 0) {
                $templateinsertdata->contentorder = $slottemplate->contentorder;
            } else {
                // If no slot order is specified, default to the slot order of the last slot in the template,
                // plus 1.
                $templateslots = $DB->get_records('openstudio_content_templates', array('foldertemplateid' => $settemplate->id),
                                 'contentorder DESC', '*', 0, 1);
                if (empty($templateslots)) {
                    $templateinsertdata->contentorder = 1;
                } else {
                    $lastslot = array_pop($templateslots);
                    $templateinsertdata->contentorder = $lastslot->contentorder + 1;
                }
            }
            $templateinsertdata->status = levels::NORMAL;

            return $DB->insert_record('openstudio_content_templates', $templateinsertdata);
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Return the slot record for the set record requested by set id.
 *
 * @param int $setid set id.
 * @return object Return the set record.
 */
function studio_api_set_containing_slot_get_by_setid($setid) {
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

EOF;
    return $DB->get_record_sql($sql, array('id' => $setid));
}

/**
 * Return the set slot record and associated slot requested by set slot id.
 *
 * @param int $setitemid set item id.
 * @return object The slot record, with additional fields from the set slot record.
 */
function studio_api_set_slot_get_by_id($setslotid) {
    global $DB;

    $sql = <<<EOF
    SELECT s.*, ss.id AS foldercontentid, ss.folderid, ss.name AS setslotname,
           ss.description AS setslotdescription,
           ss.contentorder, ss.foldercontenttemplateid, ss.timemodified AS setslottimemodified
      FROM {openstudio_contents} s
      JOIN {openstudio_folder_contents} ss ON s.id = ss.contentid
     WHERE ss.id = :foldercontentid
  ORDER BY ss.contentorder ASC
EOF;

    return $DB->get_record_sql($sql, array('foldercontentid' => $setslotid));
}

/**
 * Return the set slot record and associated slot requested by slot id.
 *
 * @param int $setid set id.
 * @param int $slotid set slot id.
 * @return object The slot record, with additional fields from the set slot record.
 */
function studio_api_set_slot_get_by_slotid($setid, $slotid) {
    global $DB;

    $sql = <<<EOF
  SELECT s.*, ss.id AS foldercontentid, ss.folderid, ss.name AS setslotname,
         ss.description AS setslotdescription, ss.foldercontenttemplateid as setslottempalteid,
         ss.contentorder, ss.foldercontenttemplateid, ss.timemodified AS setslottimemodified,
         ss.provenanceid, ss.provenancestatus
    FROM {openstudio_contents} s
    JOIN {openstudio_folder_contents} ss ON s.id = ss.contentid
   WHERE ss.folderid = :folderid
     AND ss.contentid = :foldercontentid
     AND ss.status > :status
ORDER BY ss.contentorder ASC
EOF;

    $params = array(
        'folderid' => $setid,
        'foldercontentid' => $slotid,
        'status' => levels::SOFT_DELETED
    );

    return $DB->get_record_sql($sql, $params);
}

function studio_api_set_slot_get_first_containing_set($slotid) {
    global $DB;
    $sql = <<<EOF
  SELECT s1.*
    FROM {openstudio_contents} s
    JOIN {openstudio_folder_contents} ss ON s.id = ss.contentid
    JOIN {openstudio_contents} s1 ON ss.folderid = s1.id
   WHERE s.id = ?
ORDER BY ss.timemodified ASC
   LIMIT 1
EOF;

    $params = array($slotid);
    return $DB->get_record_sql($sql, $params);
}

/**
 * Return the set slot record and associated slot requested by slot id.
 *
 * @param int $setitemid set item id.
 * @return object The slot record, with additional fields from the set slot record.
 */
function studio_api_set_slot_get_by_slotorder($setid, $slotorder) {
    global $DB;

    return $DB->get_record('openstudio_folder_contents', array('folderid' => $setid, 'contentorder' => $slotorder));
}

/**
 * Return the slot records plus set slot data for each slot
 * within the set
 *
 * @param int $setid the set id
 * @return array The slot records, with additional fields from the set slot records, sorted by slotorder, keyed by ID
 */
function studio_api_set_slots_get($setid) {
    global $DB;

    $sql = <<<EOF
    SELECT s.*, ss.id AS foldercontentid, ss.folderid, ss.name AS setslotname,
           ss.description AS setslotdescription,
           ss.contentorder, ss.foldercontenttemplateid, ss.timemodified AS setslottimemodified,
           ss.provenancestatus, ss.provenanceid
      FROM {openstudio_contents} s
      JOIN {openstudio_folder_contents} ss ON s.id = ss.contentid
     WHERE ss.folderid = :folderid
       AND ss.status != :status
  ORDER BY ss.contentorder ASC
EOF;

    $slots = $DB->get_records_sql($sql, array('folderid' => $setid, 'status' => levels::SOFT_DELETED));
    foreach ($slots as $slot) {
        if (!empty($slot->provenanceid) && $slot->provenancestatus == folder::PROVENANCE_EDITED) {
            if (!empty($slot->setslotname)) {
                $slot->name = $slot->setslotname;
            }
            if (!empty($slot->setslotdescription)) {
                $slot->description = $slot->setslotdescription;
            }
        }
    }
    return $slots;
}

/**
 * Return the archived versions of soft-deleted set slots
 *
 * @param int $setid the set id
 * @return array The slot records, with additional fields from the set slot records, sorted by slotorder, keyed by ID
 */
function studio_api_set_slots_get_deleted($setid) {
    global $DB;

    $sql = <<<EOF
    SELECT sv.*, ss.id AS foldercontentid, ss.folderid, ss.name AS setslotname,
           ss.description AS setslotdescription,
           ss.contentorder, ss.foldercontenttemplateid, ss.timemodified AS setslottimemodified,
           ss.provenancestatus, ss.provenanceid
      FROM {openstudio_content_versions} sv
      JOIN {openstudio_folder_contents} ss ON sv.contentid = ss.contentid
     WHERE ss.folderid = :folderid
       AND ss.status = :status
  ORDER BY ss.contentorder ASC
EOF;

    $slots = $DB->get_records_sql($sql, array('folderid' => $setid, 'status' => levels::SOFT_DELETED));
    foreach ($slots as $slot) {
        if (!empty($slot->provenanceid) && $slot->provenancestatus == folder::PROVENANCE_EDITED) {
            if (!empty($slot->setslotname)) {
                $slot->name = $slot->setslotname;
            }
            if (!empty($slot->setslotdescription)) {
                $slot->description = $slot->setslotdescription;
            }
        }
    }
    return $slots;
}

/**
 * Return the slot records as with studio_api_get_contained_slots,
 * but also return any template records for preconfigured but as-yet
 * uninstantiated slots.
 *
 * @param int $setid The ID of the set to get the slots for
 * @return array The slot records with additional set slots fields, plus the openstudio_folder_slot_template
 *               records, keyed by slotorder
 */
function studio_api_set_slots_get_with_templates($setid) {
    global $DB;
    // Get slots.
    $realslots = studio_api_set_slots_get($setid);

    // Get slot templates.
    $sql = <<<EOF
    SELECT sst.*, sst.id AS foldercontenttemplateid, 1 AS isnotrealslot
      FROM {openstudio_content_templates} sst
      JOIN {openstudio_folder_templates} st ON sst.foldertemplateid = st.id
      JOIN {openstudio_level3} l ON st.levelid = l.id
      JOIN {openstudio_contents} s ON s.levelid = l.id
     WHERE s.id = ?
       AND sst.status >= ?
       AND st.status >= ?
  ORDER BY sst.contentorder
EOF;

    $templates = $DB->get_records_sql($sql, array($setid, levels::NORMAL, levels::NORMAL));
    $sortedslots = array();
    $scale = count($templates) + count($realslots);

    // Add real slots.
    foreach ($realslots as $realslot) {
        $sortedslots[$realslot->contentorder * $scale] = $realslot;
    }

    foreach ($templates as $template) {
        $isfound = false;
        foreach ($realslots as $realslot) {
            if ($realslot->foldercontenttemplateid == $template->id) {
                $isfound = true;
                $foundslot = $realslot;
            }
        }
        if (!$isfound) {
            if (isset($sortedslots[$template->contentorder * $scale])) {
                // See if there's a space to backfill.
                for ($i = 1; $i < $template->contentorder; $i++) {
                    if (!isset($sortedslots[$i * $scale])) {
                        $sortedslots[$i * $scale] = $template;
                    }
                }
                $tryposition = $template->contentorder;
                while (!in_array($template, $sortedslots)) {
                    // Find the next available position.
                    $tryposition++;
                    if (!isset($sortedslots[$tryposition * $scale])) {
                        $sortedslots[$tryposition * $scale] = $template;
                    }
                }
            } else {
                // Check we haven't got an empty position to backfill.
                for ($i = 1; $i < $template->contentorder; $i++) {
                    if (!isset($sortedslots[$i * $scale])) {
                        $sortedslots[$i * $scale] = $template;
                    }
                }
                if (!in_array($template, $sortedslots)) {
                    $sortedslots[$template->contentorder * $scale] = $template;
                }
            }
        }
    }

    ksort($sortedslots);
    return array_values($sortedslots);

}

/**
 * Return the first set slot in the set (or the template for the first slot
 * if it's unfilled)
 *
 * @param int $setid set id.
 * @param bool $realslotsonly True to show only real slots.
 * @return stdClass Return the set slot record.
 */
function studio_api_set_slot_get_first($setid, $realslotsonly = false) {
    $slots = studio_api_set_slots_get_with_templates($setid);

    if ($realslotsonly) {
        $slots = studio_api_set_slots_filter_real($slots);
    }

    if (empty($slots)) {
        return false;
    } else {
        return reset($slots);
    }
}

function studio_api_set_slots_filter_real($slots) {
    $filteredslots = array();
    foreach ($slots as $slot) {
        if (!isset($slot->isnotrealslot)) {
            $filteredslots[] = $slot;
        }
    }
    return $filteredslots;
}

/**
 * Get the template for a set, if there is one.
 *
 * @param int $setid The setid of the set.
 * @return stdClass The openstudio_folder_templates record
 */
function studio_api_set_template_get($setid) {
    global $DB;

    $sql = <<<EOF
    SELECT st.*
      FROM {openstudio_folder_templates} st
      JOIN {openstudio_level3} l ON l.id = st.levelid
      JOIN {openstudio_contents} s ON s.levelid = l.id
     WHERE s.id = :folderid
       AND st.status >= :status
EOF;

    return $DB->get_record_sql($sql, array('folderid' => $setid, 'status' => levels::NORMAL));
}

/**
 * Get the set template for a level record, if there is one.
 *
 * @param int $levelid The ID of the level.
 * @return stdClass The openstudio_folder_templates record
 */
function studio_api_set_template_get_by_levelid($levelid) {
    global $DB;

    $sql = <<<EOF
    SELECT st.*
      FROM {openstudio_folder_templates} st
      JOIN {openstudio_level3} l ON l.id = st.levelid
     WHERE l.id = :levelid
       AND st.status >= :status
EOF;

    return $DB->get_record_sql($sql, array('levelid' => $levelid, 'status' => levels::NORMAL));
}

/**
 * Get the template for a set, if there is one.
 *
 * @param int $templateid The id of the set template.
 * @return stdClass The openstudio_folder_templates record
 */
function studio_api_set_template_get_by_id($templateid) {
    global $DB;

    return $DB->get_record('openstudio_folder_templates', array('id' => $templateid, 'status' => levels::NORMAL));
}

/**
 * Get the slot templates for a set template if there are any.
 *
 * @param int $templateid The id of the set template.
 * @return array The openstudio_folder_slot_templates records
 */
function studio_api_set_template_slots_get($templateid) {
    global $DB;

    $sql = <<<EOF
    SELECT sst.*
      FROM {openstudio_content_templates} sst
      JOIN {openstudio_folder_templates} st ON st.id = sst.foldertemplateid
     WHERE st.id = ?
       AND st.status >= ?
       AND sst.status >= ?
  ORDER BY sst.contentorder ASC
EOF;

    return $DB->get_records_sql($sql, array($templateid, levels::NORMAL, levels::NORMAL));
}

/**
 * Get a slot template
 *
 * @param int $slotemplateid The id of the slot template
 * @return object The openstudio_folder_slot_templates record
 */
function studio_api_set_template_slot_get($slottemplateid) {
    global $DB;

    return $DB->get_record('openstudio_content_templates', array('id' => $slottemplateid, 'status' => levels::NORMAL));
}

/**
 * Get the template for a given slot, if it has one
 *
 * @param int $slotid The ID of the slot we want the template for
 * @return stdClass|bool the openstudio_folder_slot_templates record, or false if there isn't one
 */
function studio_api_set_template_slot_get_by_slotid($slotid) {
    global $DB;

    $sql = <<<EOL
    SELECT sst.*
      FROM {openstudio_content_templates} sst
      JOIN {openstudio_folder_contents} ss ON sst.id = ss.foldercontenttemplateid
     WHERE ss.contentid = ?
       AND sst.status >= ?
EOL;
    $params = array($slotid, levels::NORMAL, levels::NORMAL);
    return $DB->get_record_sql($sql, $params);
}

/**
 * Get a slot template by its slotorder within the set template
 *
 * @param int $settemplateid The ID of the set template the slot is part of
 * @param int $slotorder The slotorder of the slot template
 * @return stdClass|bool the openstudio_folder_slot_templates record, or false if there isn't one
 */
function studio_api_set_template_slot_get_by_slotorder($settemplateid, $slotorder) {
    global $DB;

    $sql = <<<EOL
    SELECT sst.*
      FROM {openstudio_content_templates} sst
     WHERE sst.foldertemplateid = ?
       AND sst.contentorder = ?
       AND sst.status >= ?
EOL;

    $params = array($settemplateid, $slotorder, levels::NORMAL);
    return $DB->get_record_sql($sql, $params);
}

/**
 * Update set slot record.
 *
 * @param stdClass $setslot set slot record.
 * @return bool True if record update was succesful.
 * @throws coding_exception If an invalid ID or no ID was passed in the $setslot object
 */
function studio_api_set_slot_update($setslot) {
    global $DB;

    if (!isset($setslot->id)) {
        throw new coding_exception('$setslot passed to studio_api_set_slot_update must contain an id');
    }
    $slotinsertdata = (object) array();

    $setslotrecord = $DB->get_record('openstudio_folder_contents', array('id' => $setslot->id));
    if ($setslotrecord) {
        try {

            $slotinsertdata->id = $setslotrecord->id;

            if (isset($setslot->contentid)) {
                $slotinsertdata->contentid = $setslot->contentid;
            }
            if (isset($setslot->name)) {
                $slotinsertdata->name = $setslot->name;
            }
            if (isset($setslot->description)) {
                $slotinsertdata->description = $setslot->description;
            }
            if (isset($setslot->contentorder)) {
                $slotinsertdata->contentorder = $setslot->contentorder;
            }
            if (isset($setslot->foldercontenttemplateid)) {
                $slotinsertdata->foldercontenttemplateid = $setslot->foldercontenttemplateid;
            }
            if (isset($setslot->provenancestatus)) {
                $slotinsertdata->provenancestatus = $setslot->provenancestatus;
            }
            $slotinsertdata->timemodified = time();

            $DB->update_record('openstudio_folder_contents', $slotinsertdata);

            // Update set slot last modified time.
            $DB->set_field('openstudio_contents', 'timemodified', time(), array('id' => $setslotrecord->contentid));

        } catch (Exception $e) {
            return false;
        }
    } else {
        throw new coding_exception('$setslot passed to studio_api_set_slot_update '.
                                       'must contain an id for a record that exists in openstudio_folder_contents');
    }

    return true;
}

/**
 * Set the slotorder for a slot within a set
 *
 * Convenience function for doing a quick update when we're just re-ordering slots.
 *
 * @param int $setid The ID of the set the slot belongs to
 * @param array $slotorders An array of new slotorders, keyed by current slotorders
 * @return bool True of the slotorder is updated successfully
 * @throws coding_exception If the $setid and $slotid don't match a record in openstudio_folder_contents
 */
function studio_api_set_slot_update_slotorders($setid, $slotorders) {
    global $DB;

    // Check that we're not trying to put 2 slots in the same position.
    $slotordercount = array_count_values($slotorders);
    arsort($slotordercount);
    foreach ($slotordercount as $slotordercount) {
        if ($slotordercount != 1) {
            throw new coding_exception('$slotorder array passed to studio_api_set_slot_update_slotorders '.
                    'cannot contain the same value more than once');
        }
    }

    // Remove any slots that aren't being updated.
    $updatedorders = array();
    foreach ($slotorders as $position => $neworder) {
        if ($position + 1 != $neworder) {
            $updatedorders[$position] = $neworder;
        }
    }
    if (empty($updatedorders)) {
        // Nothing to do.
        return true;
    }

    // Get slots and templates in their current order.
    $slotsandtemplates = studio_api_set_slots_get_with_templates($setid);

    // Update the slots to their new order.
    foreach ($updatedorders as $position => $neworder) {
        if (!array_key_exists($position, $slotsandtemplates)) {
            throw new coding_exception('new slotorders passed to studio_api_set_slot_update_slotorder '
                    . 'must be greater than 1 and less than the number of slots and '
                    . 'templates in the set.');
        }
        $setslot = $slotsandtemplates[$position];
        if (isset($setslot->isarealslot)) {
            throw new coding_exception('studio_api_set_slot_update_slotorder can only update the slotorder '
                    . 'of slots that have been filled, not templates.');
        }
        $updaterecord = (object) array(
            'id' => $setslot->foldercontentid,
            'contentorder' => $neworder,
            'timemodified' => time()
        );
        $DB->update_record('openstudio_folder_contents', $updaterecord, true);
    }

    $DB->set_field('openstudio_contents', 'timemodified', time(), array('id' => $setid));
    return true;
}

/**
 * Uodate a set template.
 *
 * @param object $settemplate Set template record for update.
 * @return boolean True if the update is successful, false on failure
 * @throws coding_exception If an invalid ID or no ID is set in $settemplate
 */
function studio_api_set_template_update($settemplate) {
    global $DB;

    if (!isset($settemplate->id)) {
        throw new coding_exception('$settemplate passed to studio_api_set_template_update must contain an id');
    }
    $templateinsertdata = new stdClass();

    $templaterecord = studio_api_set_template_get_by_id($settemplate->id);
    if ($templaterecord) {
        try {
            $templateinsertdata->id = $templaterecord->id;
            if (isset($settemplate->guidance)) {
                $templateinsertdata->guidance = $settemplate->guidance;
            }
            if (isset($settemplate->additionalcontents)) {
                $templateinsertdata->additionalcontents = $settemplate->additionalcontents;
            }
            if (isset($settemplate->status)) {
                $templateinsertdata->status = $settemplate->status;
            }

            return $DB->update_record('openstudio_folder_templates', $templateinsertdata);
        } catch (Exception $e) {
            return false;
        }
    } else {
        throw new coding_exception('$settemplate passed to studio_api_set_template_update '.
                                   'must contain an id for a record that exists in openstudio_folder_templates');
    }
    return false;
}

/**
 * Create a new slot template
 *
 * @param object $slottemplate The slot template record to create.
 * @return boolean True if the update is successful, false if not
 * @throws coding_exception if and invalid ID or no ID is set in $slottemplate
 */
function studio_api_set_template_slot_update($slottemplate) {
    global $DB;

    if (!isset($slottemplate->id)) {
        throw new coding_exception('$slottemplate passed to studio_api_set_template_slot_update must contain an id');
    }
    $templateinsertdata = (object) array();

    $templaterecord = studio_api_set_template_slot_get($slottemplate->id);
    if ($templaterecord) {

        try {
            $templateinsertdata->id = $templaterecord->id;

            if (isset($slottemplate->name)) {
                $templateinsertdata->name = $slottemplate->name;
            }
            if (isset($slottemplate->guidance)) {
                $templateinsertdata->guidance = $slottemplate->guidance;
            }
            if (isset($slottemplate->permissions)) {
                $templateinsertdata->permissions = $slottemplate->permissions;
            }
            if (isset($slottemplate->contentorder) && $slottemplate->contentorder > 0) {
                $templateinsertdata->contentorder = $slottemplate->contentorder;
            }
            if (isset($slottemplate->status)) {
                $templateinsertdata->status = $slottemplate->status;
            }

            return $DB->update_record('openstudio_content_templates', $templateinsertdata);
        } catch (Exception $e) {
            return false;
        }
    } else {
        throw new coding_exception('$slottemplate passed to studio_api_set_template_slot_update '.
                                    'must contain an id for a record that exists in openstudio_folder_slot_templates.');
    }
}

/**
 * Delete set slot.
 *
 * @param int $setid The ID of the set to remove the slot from.
 * @param int $slotid The ID of the slot to remove
 * @return bool True if the slot was removed from the set.
 * @throws coding_exception if the passed $setid and $slotid don't match any records in openstudio_folder_contents
 */
function studio_api_set_slot_remove($setid, $slotid, $userid) {
    global $DB;

    $params = array('folderid' => $setid, 'contentid' => $slotid, 'status' => levels::NORMAL);
    if (!$setslot = $DB->get_record('openstudio_folder_contents', $params)) {
        throw new coding_exception(
                '$setid and $slotid passed to studio_api_set_slot remove must exist in a record in openstudio_folder_contents');
    }
    try {
        $setslot->status = levels::SOFT_DELETED;
        if ($DB->update_record('openstudio_folder_contents', $setslot)) {
            $setslots = studio_api_set_slots_get_with_templates($setid);
            $counter = 0;
            foreach ($setslots as $setslotinstance) {
                $counter++;
                if (!isset($isnotrealslot)) {
                    $DB->set_field('openstudio_folder_contents', 'contentorder', $counter,
                            array('folderid' => $setid, 'contentid' => $setslotinstance->id));
                }
            }

            studio_api_tracking_log_action($setid, tracking1::MODIFY_FOLDER, $userid);
            return true;
        }
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Remove all slots from a set
 *
 * @param int $setid The ID of the slot containing the set to be emptied
 * @return int The number of openstudio_folder_contents records removed (0 if the set is already empty)
 * @throws coding_exception If $setid does not refer to a record in openstudio_contents
 */
function studio_api_set_empty($setid, $userid) {
    global $DB;

    if (!$DB->record_exists('openstudio_contents', array('id' => $setid))) {
        throw new coding_exception('$setid passed to studio_api_set_empty must refer to the id of a '.
                                    'record in openstudio_contents');
    }

    $params = array('folderid' => $setid, 'status' => levels::NORMAL);
    $setslots = $DB->get_records('openstudio_folder_contents', $params);
    if (count($setslots > 0)) {
        foreach ($setslots as $setslot) {
            $setslot->status = levels::SOFT_DELETED;
            $DB->update_record('openstudio_folder_contents', $setslot, true);
        }
    }

    studio_api_tracking_log_action($setid, tracking1::MODIFY_FOLDER, $userid);

    return count($setslots);
}

/**
 * Delete a set template, and all slots it contains.
 *
 * @param $templateid The ID of the template to remove
 * @param bool True of the template was marked deleted.
 */
function studio_api_set_template_delete($templateid) {
    global $DB;

    $params = array('foldertemplateid' => $templateid, 'status' => levels::NORMAL);
    $slottemplates = $DB->get_records('openstudio_content_templates', $params);
    foreach ($slottemplates as $slottemplate) {
        studio_api_set_template_slot_delete($slottemplate->id, false);
    }
    $settemplate = new stdClass();
    $settemplate->id = $templateid;
    $settemplate->status = levels::SOFT_DELETED;
    return studio_api_set_template_update($settemplate);
}

/**
 * Delete a slot set template
 *
 * @param $templateid The ID of the template to remove
 * @param bool True if the template was marked deleted.
 */
function studio_api_set_template_slot_delete($templateid, $fixorder=true) {
    global $DB;

    $setslots = $DB->get_records('openstudio_folder_contents', array('foldercontenttemplateid' => $templateid));
    foreach ($setslots as $setslot) {
        $setslot->foldercontenttemplateid = null;
        studio_api_set_slot_update($setslot);
    }
    $slottemplate = studio_api_set_template_slot_get($templateid);
    if ($slottemplate) {
        $slottemplate->status = levels::SOFT_DELETED;
        if ($fixorder) {
            $where = <<<EOF
            foldertemplateid = :foldertemplateid
        AND contentorder > :contentorder
        AND status >= :status
EOF;
            $params = array(
                'foldertemplateid' => $slottemplate->foldertemplateid,
                'contentorder'     => $slottemplate->contentorder,
                'status'        => levels::NORMAL
            );
            $followingslots = $DB->get_records_select('openstudio_content_templates', $where, $params);
            foreach ($followingslots as $followingslot) {
                $followingslot->contentorder--;
                $DB->update_record('openstudio_content_templates', $followingslot, true);
            }
        }
        return studio_api_set_template_slot_update($slottemplate);
    } else {
        throw new coding_exception('$templateid passed to studio_api_set_template_slot_delete must refer to the id '.
                'of a record in openstudio_folder_slot_templates');
    }
}

/**
 * Check if more slots can be added to a set.
 *
 * @param $setid The ID of the set
 * @param $permissions Permission object.
 * @param bool True if additinal slots can be added to the set.
 * @param $ascount Return result as count of remaining slots to add.
 */
function studio_api_set_can_add_more_slots($setid, $permissions, $lid, $ascount = false) {
    global $DB;

    $result = 0;

    if ($setid === 0) {
        $settemplatedata = studio_api_set_template_get_by_levelid($lid);
        if ($settemplatedata) {
            $result = ($settemplatedata->additionalslots > 0) ? $settemplatedata->additionalslots : 0;
        }
    } else {
        $setdata = studio_api_slot_get($setid);
        if ($setdata) {
            $slotcount = count(studio_api_set_slots_get_with_templates($setid));
            if ($setdata->levelid <= 0) {
                if ($slotcount < $permissions->pinboardsetlimit) {
                    $result = $permissions->pinboardsetlimit - $slotcount;
                }
            } else {
                $settemplatedata = studio_api_set_template_get($setid);
                if ($settemplatedata) {
                    $settemplateslotcount = $DB->count_records('openstudio_folder_slot_templates',
                            array('foldertemplateid' => $settemplatedata->id, 'status' => 0));
                    if (($slotcount - $settemplateslotcount) < $settemplatedata->additionalslots) {
                        $result = $settemplatedata->additionalslots - ($slotcount - $settemplateslotcount);
                    }
                } else {
                    if ($slotcount < defaults::FOLDERTEMPLATEADDITIONALCONTENTS) {
                        $result = defaults::FOLDERTEMPLATEADDITIONALCONTENTS - $slotcount;
                    }
                }
            }
        }
    }

    if ($result < 0) {
        $result = 0;
    }

    if ($ascount) {
        return $result;
    }

    if ($result > 0) {
        return true;
    }

    return false;
}

/**
 * Count the number of actual slots contained within a set.
 *
 * @param $setid int The ID of the set
 * @param $includetemplated bool Include all slots, or just non-templated "additional" slots?
 * @return int Return number of slots in a set.
 */
function studio_api_set_slot_count($setid, $includetemplated = true) {
    global $DB;

    $sql = <<<EOL
    SELECT count(ss.id)
      FROM {openstudio_folder_contents} ss
     WHERE ss.folderid = ?
       AND ss.status >= 0
EOL;

    if (!$includetemplated) {
        $sql .= ' AND ss.foldercontenttemplateid IS NULL';
    }

    return $DB->count_records_sql($sql, array($setid));
}

/**
 * Determine the provenance for a new copy based on the slot we're copying from
 *
 * If the source slot is not in a set, has no provenance of its own, is it's own provenance,
 * or has been edited since being copied, then use the source slot's ID for provenance.
 * If the source slot is an unedited copy of another slot, return it's own provenanceid for
 * the new copy's provenance.
 *
 * @param int $sourceslotid The ID of the slot we are copying from
 * @return int The ID of the slot to use for provenance
 * @throws coding_exception if we pass a slot ID that doesn't exist
 */
function studio_api_set_slot_determine_provenance($sourceslotid) {
    global $DB;

    if (!$DB->record_exists('openstudio_contents', array('id' => $sourceslotid, 'deletedtime' => null))) {
        throw new coding_exception('Source slot does not exist');
    }

    $params = array('contentid' => $sourceslotid, 'status' => levels::NORMAL);
    // If we get more than one result, then it's becuase we have multiple soft-links, which will
    // all have the same provenance so we can use INGORE_MULTIPLE safely.
    $sourceslot = $DB->get_record('openstudio_folder_contents', $params, '*', IGNORE_MULTIPLE);

    if (!$sourceslot || empty($sourceslot->provenanceid) || $sourceslot->provenanceid == $sourceslotid
            || $sourceslot->provenancestatus != folder::PROVENANCE_COPY
    ) {
        return $sourceslotid;
    } else {
        return $sourceslot->provenanceid;
    }
}

/**
 * Return the provenance data for a slot
 *
 * If a slot belongs to a set, returns the ID of the slot is was copied from,
 * the provenance status, and the owner of the original slot.
 *
 * @param int $setid
 * @param int $slotid
 * @param bool $includeunlinked Return data even if the slot's content has been edited?
 * @return stdClass|null Database record containing provenance data
 */
function studio_api_set_slot_get_provenance($setid, $slotid, $includeunlinked = false) {
    global $DB;

    $params = array();
    $where = '';
    if (!$includeunlinked) {
        list($usql, $params) = $DB->get_in_or_equal(array(folder::PROVENANCE_COPY, folder::PROVENANCE_EDITED));
        $where .= 'provenancestatus '. $usql . ' AND ';
    }
    $where .= 'contentid = ? AND status > ?';
    $params[] = $slotid;
    $params[] = levels::SOFT_DELETED;
    if (($setid != null) && ($setid > 0)) {
        $where .= ' AND folderid = ?';
        $params[] = $setid;
    }
    // If there's more than one record, then they're all soft links so will have the same provenance,
    // so we can safely IGNORE_MULTIPLE.
    $setslot = $DB->get_record_select('openstudio_folder_contents', $where, $params, '*', IGNORE_MULTIPLE);
    if ($setslot) {
        $sql = <<<EOF
            SELECT s.id, s.name, u.id as userid, u.firstname, u.lastname, u.middlename, u.firstnamephonetic,
                   u.lastnamephonetic, u.alternatename, ss.id as foldercontentid, ss.contentid as slotid,
                   ss.name as setslotname, ss.description as setslotdescription, ss.provenanceid, ss.provenancestatus
              FROM {openstudio_contents} s
              JOIN {openstudio_folder_contents} ss ON ss.provenanceid = s.id
              JOIN {user} u ON s.userid = u.id
             WHERE ss.id = ?
EOF;

        return $DB->get_record_sql($sql, array($setslot->id));
    }
    return null;
}

/**
 * Get set slot records for all copies of the given slot
 *
 * This function is only meant for internal use so that provenance status can be updated
 * on all copies when a provenance slot is edited.
 *
 * @param $slotid
 * @param bool $includeunlinked Also return slots which have had their content edited?
 * @return array
 * @throws coding_exception
 * @throws dml_exception
 */
function studio_api_set_slot_get_copies($slotid, $includeunlinked = false) {
    global $DB;

    $params = array();
    $where = '';
    if (!$includeunlinked) {
        list($usql, $params) = $DB->get_in_or_equal(array(folder::PROVENANCE_COPY, folder::PROVENANCE_EDITED));
        $where .= 'provenancestatus '. $usql . ' AND ';
    }
    $where .= 'provenanceid = ? AND contentid != ?';
    $params[] = $slotid;
    $params[] = $slotid;
    return $DB->get_records_select('openstudio_folder_contents', $where, $params);

}

/**
 * Get set slot records for all soft links of the given slot
 *
 * This function is only meant for internal use so that copies can be created
 * when a soft-linked slot is edited
 *
 * @param $slotid
 * @return array
 * @throws coding_exception
 * @throws dml_exception
 */
function studio_api_set_slot_get_softlinks($slotid) {
    global $DB;

    $params = array('provenanceid' => $slotid, 'contentid' => $slotid);
    return $DB->get_records('openstudio_folder_contents', $params);

}

/**
 * Convenience method to make the modifications needed to a slot record
 * to have it only appear in a set, without needing the full form
 * submission as required by studio_api_slot_update
 *
 * @param $slotid The ID of the slot to update
 * @param $name The name to override the current one with
 * @param $description The description to override the current one with
 */
function studio_api_set_move_slot($slotid, $name = null, $description = null) {
    global $DB;
    $slot = studio_api_slot_get($slotid);
    if (!$slot) {
        throw new coding_exception('Slot does not exist', $slotid);
    }
    $slot->visibility = tracking1::INFOLDERONLY;
    if (!empty($name)) {
        $slot->name = $name;
    }
    if (!empty($description)) {
        $slot->description = $description;
    }
    $DB->update_record('openstudio_contents', $slot);
}

/**
 * Calculate the number of slots allowed to be added to a given set
 *
 * Pinboard slots are limited by the pinboardsetlimit.  Pre-defined sets are limited
 * by their template's additonalslots setting, or the default value for that setting.
 * A pre-defined set slot is limited to 1.
 *
 * @param $permissions The permissions object for the current studio
 * @param $setid Optional, the ID of the set
 * @param $levelid Optional, the ID of the level for the pre-definted set
 * @param $setslottemplateid Optional, the ID of the template for the pre-defined set slot
 * @return int The maximum number of slots allowed to be added
 */
function studio_api_set_get_addition_limit($permissions, $setid = 0, $levelid = 0, $setslottemplateid = 0) {

    $limit = $permissions->pinboardfolderlimit;
    $currentslots = 0;
    if (empty($setslottemplateid)) {
        if (!empty($setid)) {
            $set = studio_api_set_containing_slot_get_by_setid($setid);
            $currentslots = studio_api_set_slot_count($setid, false);
            $levelid = $set->levelid;
        }
        if ($levelid) {
            $settemplate = studio_api_set_template_get_by_levelid($levelid);
            if ($settemplate) {
                $limit = $settemplate->additionalcontents;
            } else {
                $limit = defaults::FOLDERTEMPLATEADDITIONALCONTENTS;
            }
        }
    } else {
        $limit = 1;
    }

    return $limit - $currentslots;
}
