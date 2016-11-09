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
 * The mod_openstudio lock content event.
 *
 * @package    mod_openstudio
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_openstudio content locked event class.
 *
 * @package    mod_openstudio
 * @since      Moodle 2.8
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_locked extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns description of what happened.
     * For each logging action, the following are recoded:
     * User who did the action.
     * What lock type was used.
     *
     * @return string
     */
    public function get_description() {
        $userid = 0;
        if (isset($this->other['userid'])) {
            $userid = $this->other['userid'];
        }
        $locked = 0;
        if (isset($this->other['locktype'])) {
            $locktype = $this->other['locktype'];
        }

        $locktypename = studio_api_lock_type($locktype);

        $description = <<<EOF
The user with id '$userid' changed a content on course module id '$this->contextinstanceid'
so that the '$locktypename'
EOF;

        return $description;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:contentlocked', 'mod_openstudio');
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
        return 'content locked';
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

}
