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
 * Openstudio Tags API
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Tags API functions
 */
class tags {

    /**
     * Set the tags for a given content post.
     *
     * @param int $contentid.
     * @param string|array $tags Tags to set as an array of word or string, comma-seperated word list.
     * @param bool $update Set true is updating tags (allows empty)
     */
    public static function set($contentid, $tags, $update = false) {
        global $DB;

        if (empty($tags) && !$update) {
            return;
        }

        if (is_string($tags)) {
            $tagsarray = explode(",", $tags);
            $tagsarray = array_map('trim', $tagsarray);
        } else {
            $tagsarray = $tags;
        }

        // The function tag_set now needs to specify the component and contextid.
        $content = $DB->get_record('openstudio_contents', ['id' => $contentid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('openstudio', $content->openstudioid);
        $modulecontext = \context_module::instance($cm->id);

        \core_tag_tag::set_item_tags('mod_openstudio', 'openstudio_contents', $contentid, $modulecontext, $tagsarray);
    }

    /**
     * Remove all tags associated with given content post.
     *
     * @param int $contentid
     */
    public static function remove($contentid) {
        global $DB;
        $DB->get_record('openstudio_contents', ['id' => $contentid], '*', MUST_EXIST);
        \core_tag_tag::remove_all_item_tags('mod_openstudio', 'openstudio_contents', $contentid);
    }

    /**
     * Get all tags associated with given slot.
     *
     * @param int $contentid
     * @return array Return tags.
     */
    public static function get($contentid) {
        global $DB;
        $DB->get_record('openstudio_contents', ['id' => $contentid], '*', MUST_EXIST);
        return \core_tag_tag::get_item_tags('mod_openstudio', 'openstudio_contents', $contentid);
    }
}
