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
 * Interface for notifiable events.
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\notifications;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for notifable events.
 *
 * Defines method signatures for events that can be used by the notifications API.
 *
 * @package mod_openstudio
 */
interface notifiable {
    const CONTENT = 0;
    const COMMENT = 1;
    const TUTOR = 2;

    /**
     * Return the data required for a notification record.
     *
     * @return notification
     */
    function get_notification_data();

    /**
     * Return the notifiable:: constant representing the type of notification this event will use.
     *
     * CONTENT notifications will notify users who are following the content.
     * COMMENT notitications will notify users who are following the comment.
     * TUTOR notifications will notify users who are tutors of the user triggering the event.
     *
     * @return int
     */
    function get_notification_type();
}
