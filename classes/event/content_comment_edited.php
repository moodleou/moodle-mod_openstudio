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
 * The mod_openstudio comment edited event.
 *
 * @package    mod_openstudio
 * @copyright  2025 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\event;

use mod_openstudio\local\notifications\notifiable;
use mod_openstudio\local\notifications\notification;

/**
 * The mod_openstudio comment edited event class.
 *
 * @package    mod_openstudio
 * @copyright  2025 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_comment_edited extends \core\event\base implements notifiable {

    #[\Override]
    protected function init(): void {
        $this->data['objecttable'] = 'openstudio_comments';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    #[\Override]
    public function get_description(): string {
        $description = <<<EOF
The user with id '{$this->userid}' edited a comment on content in course module id '{$this->contextinstanceid}'
EOF;
        return $description;
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('event:contentcommentedited', 'mod_openstudio');
    }

    #[\Override]
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/openstudio/content.php',
            ['id' => $this->other['cmid'], 'sid' => $this->other['commentid']]);
    }

    #[\Override]
    public function get_notification_type(): int {
        return notifiable::COMMENT;
    }

    #[\Override]
    public function get_notification_data(): notification {
        return new notification((object) [
            'contentid' => $this->objectid,
            'commentid' => $this->other['commentid'],
            'userfrom' => $this->userid,
            'icon' => 'comments',
            'message' => get_string('notification_commentedited', 'openstudio'),
            'cmid' => $this->other['cmid'],
        ]);
    }
}
