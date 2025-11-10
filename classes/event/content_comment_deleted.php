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

namespace mod_openstudio\event;

/**
 * Event for when a comment is deleted in OpenStudio.
 *
 * @package    mod_openstudio
 * @copyright  2025 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_comment_deleted extends \core\event\base {
    #[\Override]
    protected function init(): void {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'openstudio_comments';
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('event:contentcommentdeleted', 'mod_openstudio');
    }

    #[\Override]
    public function get_description(): string {
        return "The user with id '{$this->userid}' deleted the comment with id '{$this->objectid}' in OpenStudio.";
    }

    #[\Override]
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/openstudio/' . $this->other['url']);
    }
}
