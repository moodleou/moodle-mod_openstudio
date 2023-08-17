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
 * The mod_openstudio view folder content event.
 *
 * @package    mod_openstudio
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_openstudio folder item viewed event class.
 *
 * @package    mod_openstudio
 * @since      Moodle 2.7
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class folder_item_viewed extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'studio_contents';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $folderid = 0;
        if (isset($this->other['folderid'])) {
            $folderid = $this->other['folderid'];
        }
        $folderitemid = 0;
        if (isset($this->other['folderitemid'])) {
            $folderitemid = $this->other['folderitemid'];
        }

        $description = <<<EOF
The user with id '$this->userid' viewed a set/item with
id '$folderid/$folderitemid'on course module id '$this->contextinstanceid'
EOF;

        return $description;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:setitemviewed', 'mod_openstudio');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/openstudio/' . $this->other['url']);
    }
}
