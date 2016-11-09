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

require_once($CFG->dirroot . '/tag/lib.php');

/**
 * Set the tags for a given slot.
 *
 * @param int $slotid.
 * @param string|array $tags Tags to set as an array of word or string, comma-seperated word list.
 * @param string $type Type of tag.
 */
function studio_api_tags_tag_slot($slotid, $tags, $type = 'openstudio_contents') {
    global $DB;

    if (empty($tags)) {
        return;
    }

    if (is_string($tags)) {
        $tagsarray = explode(",", $tags);
        $tagsarray = array_map('trim', $tagsarray);
    } else {
        $tagsarray = $tags;
    }

    // The function tag_set now needs to specify the component and contextid.
    $slot = $DB->get_record('openstudio_contents', array('id' => $slotid));
    $cm = get_coursemodule_from_instance('openstudio', $slot->openstudioid);
    $modulecontext = context_module::instance($cm->id);

    core_tag_tag::set_item_tags('mod_openstudio', 'openstudio_contents', $slotid, $modulecontext, $tagsarray);
}

/**
 * Remove all tags associated with given slot.
 *
 * @param int $slotid
 * @param string $type Type of tag.
 */
function studio_api_tags_remove_slot_tag($slotid, $type = 'openstudio_contents') {
    core_tag_tag::remove_all_item_tags('mod_openstudio', $type, $slotid);
}

/**
 * Get all tags associated with given slot.
 *
 * @param int $slotid
 * @param bool $returnraw If true, return tags as provided by Moodle Tag API.
 * @param string $type Type of tag.
 * @return array Return tags.
 */
function studio_api_tags_get_slot_tags($slotid, $returnraw = false, $type = 'openstudio_contents') {
    $slottags = core_tag_tag::get_item_tags('mod_openstudio', $type, $slotid);
    if ($returnraw) {
        return $slottags;
    }

    $result = array();
    foreach ($slottags as $tag) {
        $result[] = $tag->name;
    }

    return $result;
}
