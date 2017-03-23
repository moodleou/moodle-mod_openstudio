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

namespace mod_openstudio\output;

use mod_openstudio\local\api\subscription;
use mod_openstudio\local\notifications\notification;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

class subscription_email implements \renderable, \templatable {

    /** @var int $format Email format, subscription::FORMAT_HTML or subscription::FORMAT_PLAIN. */
    private $format;
    /** @var string $name The name of the recipient */
    private $name;
    /** @var \mod_openstudio\local\notifications\notification $notifications The notifications to be sent */
    private $notifications;

    /**
     * Store the recipient's name, the list of notifications and the email format.
     *
     * @param object $user
     * @param notification[] $notifications
     * @param int $format
     */
    public function __construct($user, $notifications, $format) {
        $this->name = fullname($user);
        $this->notifications = $notifications;
        $this->format = $format;
    }

    public function include_html() {
        return $this->format == subscription::FORMAT_HTML;
    }

    public function export_for_template(renderer_base $output) {
        $context = (object) [
            'name' => $this->name,
            'total' => count($this->notifications),
            'notifications' => []
        ];
        $sequence = 1;
        foreach ($this->notifications as $notification) {
            $notificationdata = $notification->export_for_template($output);
            $notificationdata->sequence = $sequence;
            $sequence++;
            $context->notifications[] = $notificationdata;
        }
        return $context;
    }
}
