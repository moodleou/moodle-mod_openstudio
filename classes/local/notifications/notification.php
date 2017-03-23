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
 * Notification class.
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\notifications;

use renderer_base;
use mod_openstudio\local\api\flags;

defined('MOODLE_INTERNAL') || die();

/**
 * Class representing a notification.
 *
 * This can be a new notifcation that's being generated, or an existing notification
 * that's been fetched from the database.
 *
 * @package mod_openstudio
 */
class notification implements \templatable {
    /** @var int The record ID from openstudio_notifications */
    public $id;
    /** @var int The user the notification is for */
    public $userid;
    /** @var int The user the who triggered the notification */
    public $userfrom;
    /** @var int The content the notification is for */
    public $contentid;
    /** @var int The comment the notification is for */
    public $commentid;
    /** @var int The flag that triggered the notification */
    public $flagid;
    /** @var string The message the notification will display */
    public $message;
    /** @var string Icon pix identifier to display with the notification */
    public $icon;
    /** @var \moodle_url The URL the notification will link to */
    public $url;
    /** @var int The time the notification was created */
    public $timecreated;
    /** @var int The time the notification was marked read */
    public $timeread;

    /**
     * Construct a new notification object.
     *
     * This either accepts a full database record from the openstudio_notifications table, or a minimal record containing the
     * userfrom, contentid, message and URL.
     *
     * @param object $record
     * @throws \coding_exception
     */
    public function __construct($record) {
        if (!isset($record->contentid) || !isset($record->message) || !isset($record->userfrom)) {
            throw new \coding_exception('An object used to construct a notification must contain at least
                    contentid, message and userfrom.');
        }
        if (!isset($record->url) && !isset($record->cmid)) {
            throw new \coding_exception('An object used to construct a notification must contain either a url or cmid.');
        }
        $this->contentid = $record->contentid;
        $this->message = $record->message;
        $this->userfrom = $record->userfrom;
        if (isset($record->url)) {
            $this->url = new \moodle_url($record->url);
        } else {
            $this->generate_url($record->cmid);
        }
        if (isset($record->id)) {
            $this->id = $record->id;
        }
        if (isset($record->userid)) {
            $this->userid = $record->userid;
        }
        if (isset($record->commentid)) {
            $this->commentid = $record->commentid;
        }
        if (isset($record->flagid)) {
            $this->flagid = $record->flagid;
        }
        if (isset($record->timecreated)) {
            $this->timecreated = $record->timecreated;
        }
        if (isset($record->icon)) {
            $this->icon = $record->icon;
        }
        if (isset($record->timeread)) {
            $this->timeread = $record->timeread;
        }
    }

    private function generate_url($cmid) {
        $this->url = new \moodle_url('/mod/openstudio/content.php', ['id' => $cmid, 'sid' => $this->contentid]);
        if (!empty($this->commentid)) {
            $this->url->set_anchor('commentid-' . $this->commentid);
        }
    }

    private function get_time_since() {
        $tz = \core_date::get_user_timezone_object($this->userid);
        $timecreated = new \DateTime('now', $tz);
        $timecreated->setTimestamp($this->timecreated);
        $interval = $timecreated->diff(new \DateTime('now', $tz));

        if ($interval->y > 0) {
            return get_string('notification_yearsago', 'mod_openstudio');
        } else if ($interval->m > 0) {
            return get_string('notification_monthsago', 'mod_openstudio', $interval->m);
        } else if ($interval->d > 0) {
            return get_string('notification_daysago', 'mod_openstudio', $interval->d);
        } else if ($interval->h > 0) {
            return get_string('notification_hoursago', 'mod_openstudio', $interval->h);
        } else if ($interval->i > 0) {
            return get_string('notification_minutesago', 'mod_openstudio', $interval->i);
        } else {
            return get_string('notification_secondsago', 'mod_openstudio');
        }
    }

    public function export_for_template(renderer_base $output) {
        global $DB, $OUTPUT;
        $userfrom = $DB->get_record('user', ['id' => $this->userfrom]);
        $userfrom->contextid = \context_user::instance($this->userfrom)->id;
        $picture = new \user_picture($userfrom);
        $picture->size = 48;
        // Action type icon, no alt text as it's supplementary to the message.
        $icon = new \pix_icon($this->icon . '_rgb_32px', '', 'mod_openstudio');
        $timesince = $this->get_time_since();
        if (empty($this->commentid)) {
            $isfollowing = !empty(flags::get_content_flags($this->contentid, flags::FOLLOW_CONTENT, $this->userid));
        } else {
            $isfollowing = !empty(flags::get_comment_flags($this->commentid, flags::FOLLOW_CONTENT, $this->userid));
        }
        return (object) [
            'id' => $this->id,
            'contentid' => $this->contentid,
            'commentid' => empty($this->commentid) ? null : $this->commentid,
            'picture' => $OUTPUT->render($picture),
            'message' => $this->message,
            'messageplain' => strip_tags($this->message),
            'icon' => $icon->export_for_template($output),
            'url' => $this->url,
            'timecreated' => $timesince,
            'isread' => !empty($this->timeread),
            'isfollowing' => $isfollowing
        ];
    }
}
