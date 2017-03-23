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
 * The mod_openstudio folder smile flag event.
 *
 * @package    mod_openstudio
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\event;

use mod_openstudio\local\notifications\notifiable;
use mod_openstudio\local\notifications\notification;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_openstudio folder smile flagged event class.
 *
 * @package    mod_openstudio
 * @since      Moodle 2.7
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class folder_smile_flagged extends \core\event\base implements notifiable {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'openstudio_contents';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $description = <<<EOF
The user with id '$this->userid' flagged smiled on a set on course module id '$this->contextinstanceid'
EOF;

        return $description;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:setsmileflagged', 'mod_openstudio');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/openstudio/' . $this->other['url']);
    }

    public static function get_legacy_eventname() {
        return 'content smile flagged (set)';
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return array(
                $this->courseid,
                $this->other['module'],
                self::get_legacy_eventname(),
                $this->other['url'],
                $this->other['info'],
                $this->contextinstanceid
        );
    }

    public function get_notification_type() {
        return notifiable::CONTENT;
    }

    public function get_notification_data() {
        $type = get_string('notification_folder', 'openstudio');
        return new notification((object) [
            'contentid' => $this->objectid,
            'userfrom' => $this->userid,
            'icon' => 'participation',
            'message' => get_string('notification_liked', 'openstudio', $type),
            'cmid' => $this->context->instanceid,
            'flagid' => $this->other['flagid']
        ]);
    }
}
