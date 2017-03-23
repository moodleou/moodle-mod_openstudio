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
 *
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\tests;

defined('MOODLE_INTERNAL') || die();

use mod_openstudio\local\notifications\notifiable;
use mod_openstudio\local\notifications\notification;

class mock_content_notifiable extends mock_notifiable_event {

    function get_notification_type() {
        return notifiable::CONTENT;
    }

    function get_notification_data() {
        global $USER;
        return new notification((object) [
                'contentid' => $this->contentid,
                'message' => 'Mock content notification ' . random_string(),
                'url' => 'mod/openstudio/index.php',
                'userfrom' => $USER->id
        ]);
    }
}
