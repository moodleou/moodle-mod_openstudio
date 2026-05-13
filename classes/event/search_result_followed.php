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
 * The mod_openstudio search result followed event.
 *
 * @package    mod_openstudio
 * @copyright  2026 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Fired when a user follows (clicks) a result from an OpenStudio search.
 *
 * @property-read array $other {
 *      - string searchtoken: (required) Unique identifier of the originating search.
 *      - int pos: (required) 1-based position of the result in the full result set.
 *      - string url: (required) Destination URL of the content item.
 * }
 *
 * @package    mod_openstudio
 * @copyright  2026 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_result_followed extends \core\event\base {

    /**
     * Init method.
     */
    #[\Override]
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    #[\Override]
    public static function get_name() {
        return get_string('event:searchresultfollowed', 'mod_openstudio');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    #[\Override]
    public function get_description() {
        return "The user with id '{$this->userid}' followed result no. {$this->other['pos']}" .
            " from search '{$this->other['searchtoken']}' ({$this->other['url']})";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    #[\Override]
    public function get_url() {
        return new \moodle_url($this->other['url']);
    }

    /**
     * Custom validations.
     *
     * @throws \coding_exception If not valid.
     */
    #[\Override]
    protected function validate_data() {
        parent::validate_data();

        foreach (['searchtoken', 'pos', 'url'] as $field) {
            if (!isset($this->other[$field])) {
                throw new \coding_exception("other['{$field}'] must be set.");
            }
        }
    }
}
