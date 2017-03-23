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
 * Delete notficiations task.
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\task;

use Horde\Socket\Client\Exception;
use mod_openstudio\local\api\notifications;

defined('MOODLE_INTERNAL') || die();

class delete_notifications extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('cron_deletenotifications', 'openstudio');
    }

    public function execute() {
        try {
            notifications::delete_old(notifications::FIELD_READ);
            notifications::delete_old(notifications::FIELD_UNREAD);
            notifications::delete_max();
        } catch (Exception $e) {
            mtrace('There was an error when deleting old notifications ' . $e->getMessage());
        }
    }
}
