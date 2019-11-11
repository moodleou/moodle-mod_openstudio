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
 * API functions for content locking.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

use mod_openstudio\local\util;

/**
 * Lock API functions
 *
 * This code has been only minimally refactored from openstudio V1, so may still use terms like "slot".
 */
class lock {

    const NONE = 0;
    const ALL = 1; // Combination of 2, 4 and 8.
    const CRUD = 2; // Cannot edit/delete/archive content.
    const SOCIAL = 4; // Cannot do social flagging (favourite, inspire, etc.).
    const SOCIAL_CRUD = 6; // Combination of 2 and 4.
    const COMMENT = 8; // Cannot have new comments.
    const COMMENT_CRUD = 12; // Combination of 2 and 8.

    /**
     * This function locks and unlocks a slot.
     *
     * @param int $userid User who is requesting the lock.
     * @param int $slotid points to a slot that needs to be locked/unlocked.
     * @param int $locktype A range of values [0-7]
     * @return bool Return true or false depending on the operation success.
     */
    public static function lock_content($userid, $slotid, $locktype) {
        global $DB, $USER;
        $success = false;

        // Get course module id from $slotid.
        $slot = $DB->get_record('openstudio_contents', array('id' => $slotid));
        $cm = get_coursemodule_from_instance('openstudio', $slot->openstudioid);
        $context = \context_module::instance($cm->id);

        // Check if the user has permissions for this process.
        if ((has_capability('mod/openstudio:canlock', $context, $userid) && $slot->userid == $USER->id) ||
                has_capability('mod/openstudio:canlockothers', $context, $userid)) {
            $success = self::lock_content_internal($userid, $slotid, $locktype);
        }

        return $success ? true : false;
    }

    /**
     * This function checks whether a slot is locked or unlocked .
     *
     * @param int $slotid pointing to a slot that needs to be checked.
     * @return bool False if unlocked or the $userid of the person who has locked it.
     */
    public static function check($slotid) {
        $slotdata = content::get($slotid);
        if ($slotdata->locktype == self::NONE) {
            return false;
        } else {
            return $slotdata->lockedby;
        }
    }

    /**
     * This function locks and unlocks a slot.
     *
     * @param int $userid User who is requesting the lock.
     * @param int $slotid Slot id pointing to a slot that needs to be checked.
     * @param int $locktype A range of values [0-12].
     * @return bool Return true or false depending on the operation success.
     */
    private static function lock_content_internal($userid, $slotid, $locktype) {
        global $DB;
        $success = false;

        $slotlockdata = (object) array();
        $slotlockdata->id = $slotid;
        $slotlockdata->locktype = $locktype;
        $slotlockdata->lockedby = $userid;

        $timenow = time();
        if ($locktype != self::NONE) {
            $slotlockdata->lockedtime = $timenow;
        }
        $slotlockdata->lockprocessed = $timenow;

        $success = $DB->update_record('openstudio_contents', $slotlockdata);

        return $success;
    }

    /**
     * This function sets a lock level for a given slot level3 for a given date and also
     * an unlock time for a given date.
     *
     * @param int $userid User who has scheduled the lock.
     * @param int $level3id Studio_level3 recordid that the schedule lock/unlock is applied to.
     * @param int $locktype A range of values [0-7]
     * @param int $locktime date/time for locking a slot.
     * @param int $unlocktime date/time for unlocking a slot.
     * @return bool returns true on success.
     */
    public static function schedule($userid, $level3id, $locktype, $locktime, $unlocktime) {
        $success = false;

        // Get level data from levelid.
        $level = 3;
        $slotleveldata = levels::get_record($level, $level3id);

        $slotlevelupdatedata = (object) array();
        // Make comparisons.
        if ($slotleveldata->locktype != $locktype) {
            $slotlevelupdatedata->locktype = $locktype;
        }
        if ($slotleveldata->locktime != $locktime) {
            $slotlevelupdatedata->locktime = $locktime;
        }
        if ($slotleveldata->unlocktime != $unlocktime) {
            $slotlevelupdatedata->unlocktime = $unlocktime;
        }
        // If the record is to be changed, then the lockprocessed date is also updated now.
        if (isset($slotlevelupdatedata->locktype) || isset($slotlevelupdatedata->locktime)
                || isset($slotlevelupdatedata->unlocktime)) {
            $slotlevelupdatedata->lockprocessed = time();
            $success = levels::update($level, $level3id, $slotlevelupdatedata);

        }

        return $success;
    }

    /**
     * This function resets the schedule lock/unlock action for a given slot.
     *
     * @param int $userid User who has scheduled the lock.
     * @param int $level3id Studio_level3 recordid that the schedule lock/unlock is applied to.
     * @return bool returns true on success.
     */
    public static function reset_schedule($userid, $level3id) {
        global $DB;
        $slotlevelresetdata = (object) array();
        $slotlevelresetdata->id = $level3id;
        $slotlevelresetdata->locktype = self::NONE;
        $slotlevelresetdata->locktime = 0;
        $slotlevelresetdata->unlocktime = 0;
        $slotlevelresetdata->lockprocessed = time();
        $success = $DB->update_record('openstudio_level3', $slotlevelresetdata);

        return $success;
    }

    /**
     * This function exposes the lock type name used on a slot.
     *
     * @param int $locktype A range of values [0-12].
     * @return string $locktypename Language string.
     */
    public static function type($locktype) {
        switch ($locktype) {
            case self::NONE:
                // Slot is not locked, there are no restrictions.
                $locktypename = get_string('locktypename0', 'openstudio');
                break;

            case self::ALL:
                // Slot cannot be edited, deleted, archived or flagged and does not allow new comments.
                $locktypename = get_string('locktypename1', 'openstudio');
                break;

            case self::CRUD:
                // Slot cannot be edited, deleted or archived.
                $locktypename = get_string('locktypename2', 'openstudio');
                break;

            case self::SOCIAL:
                // Slot cannot be flagged.
                $locktypename = get_string('locktypename4', 'openstudio');
                break;

            case self::COMMENT:
                // Slot does not allow new comments.
                $locktypename = get_string('locktypename8', 'openstudio');
                break;

            case self::SOCIAL_CRUD:
                // Slot cannot be edited, deleted, archived or flagged.
                $locktypename = get_string('locktypename6', 'openstudio');
                break;

            case self::COMMENT_CRUD:
                // Slot cannot be edited, deleted or archived and does not allow new comments.
                $locktypename = get_string('locktypename12', 'openstudio');
                break;
        }
        return $locktypename;
    }

    /**
     * System helper function to process the locks on slots from the schedule.
     * return the updated slot
     */
    private static function processing($slot) {
        global $DB, $USER;

        // Find related info for slot where lockprocessed is less than level3 lockprocessed.
        $sql = <<<EOF
SELECT ss.id, ss.openstudioid, ss.name, ss.locktype AS contentlocktype, ss.lockedby, ss.lockedtime,
       ss.lockprocessed AS contentlockprocessed, l3.id AS l3id, l3.name AS l3name,
       l3.locktype, l3.locktime, l3.unlocktime,
       l3.lockprocessed
  FROM {openstudio_contents} ss
  JOIN {openstudio_level3} l3 ON l3.id = ss.levelid
  JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
  JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
 WHERE ss.id = ?;
EOF;

        // For this slot find enabled scheduled times.
        if ($schedule = $DB->get_record_sql($sql, array($slot->id))) {
            $userid = 0;// System operation so userid = 0.
            // If there are no schedules enabled.
            if (($schedule->locktime == 0) && ($schedule->unlocktime == 0)) {
                if ((($slot->locktype > self::NONE) && ($slot->lockedby == 0))
                        || ($schedule->contentlockprocessed < $schedule->lockprocessed)) {
                    // The slot must be unlocked.
                    self::lock_content_internal($userid, $schedule->id, self::NONE);
                    $slot->locktype = self::NONE;
                    $slot->lockedby = $userid;
                    $slot->schedlocktime = $schedule->locktime;
                    $slot->schedunlocktime = $schedule->unlocktime;
                }
                return $slot;
            }

            $currenttime = time();
            $scheduleislock = false;
            $scheduletime = null;

            // Pick the right schedule to continue...
            if (($schedule->locktime == 0) && ($schedule->unlocktime > 0)) {
                if ($currenttime <= $schedule->unlocktime) {
                    return $slot;
                }

                $scheduletime = $schedule->unlocktime;
            } else if (($schedule->locktime > 0) && ($schedule->unlocktime == 0)) {
                if ($currenttime <= $schedule->locktime) {
                    return $slot;
                }

                $scheduleislock = true;
                $scheduletime = $schedule->locktime;
            } else {
                $scheduletimes = array();
                if ($schedule->unlocktime > $schedule->locktime) {
                    $scheduletimes[] = array('scheduletime' => $schedule->locktime, 'type' => 'lock');
                    $scheduletimes[] = array('scheduletime' => $schedule->unlocktime, 'type' => 'unlock');
                } else {
                    $scheduletimes[] = array('scheduletime' => $schedule->unlocktime, 'type' => 'unlock');
                    $scheduletimes[] = array('scheduletime' => $schedule->locktime, 'type' => 'lock');
                }

                foreach ($scheduletimes as $scheduletimeentry) {
                    if ($currenttime > $scheduletimeentry['scheduletime']) {
                        $scheduletime = $scheduletimeentry['scheduletime'];
                        $scheduleislock = ($scheduletimeentry['type'] == 'lock') ? true : false;
                    }
                }

                if (empty($scheduletime)) {
                    return $slot;
                }
            }

            if (($schedule->contentlocktype == self::NONE) && !$scheduleislock) {
                return $slot;
            }

            if (($schedule->contentlocktype == $schedule->locktype) && $scheduleislock) {
                return $slot;
            }

            if (($schedule->contentlockprocessed < $scheduletime)
                    || ($schedule->contentlockprocessed < $schedule->lockprocessed)) {

                $success = false;
                if ($scheduleislock) {
                    $success = self::lock_content_internal($userid, $schedule->id, $schedule->locktype);
                } else {
                    $success = self::lock_content_internal($userid, $schedule->id, self::NONE);
                }

                if ($success) {
                    $cm = get_coursemodule_from_instance('openstudio', $slot->openstudioid);
                    if ($scheduleislock) {
                        $slot->locktype = $schedule->locktype;
                        $slot->lockedby = $userid;
                        $slot->lockprocessed = $schedule->lockprocessed;

                        util::trigger_event($cm->id, 'content_locked', "{$slot->userid}/{$slot->locktype}",
                                "view.php?id={$slot->id}",
                                util::format_log_info($slot->id));
                    } else {
                        $slot->locktype = self::NONE;
                        $slot->lockedby = $userid;
                        $slot->lockprocessed = $schedule->lockprocessed;

                        util::trigger_event($cm->id, 'content_unlocked', "{$slot->userid}/{$slot->locktype}",
                                "view.php?id={$slot->id}",
                                util::format_log_info($slot->id));
                    }
                }

            }

        }

        return $slot;
    }

    public static function content_show_locker($slot, $permissions) {
        if ($slot->locktype > self::NONE && $slot->lockedby == 0) {
            if ($slot->locktype <= self::CRUD) {
                return \html_writer::span(
                        " " . get_string('lockedbyusername', 'openstudio', get_string('pluginadministration', 'openstudio')));
            }

            // The other locks we will show no message.
            return '';
        } else if ($slot->locktype > self::NONE && $slot->lockedby !== $permissions->activeuserid) {
            $slotlocker = user::get_user_by_id($slot->lockedby);
            return \html_writer::span(" " . get_string('lockedbyusername', 'openstudio', fullname($slotlocker)));
        } else if ($slot->locktype > self::NONE && $slot->lockedby !== $slot->userid) {
            $slotlocker = user::get_user_by_id($slot->lockedby);
            return \html_writer::span(" " . get_string('lockedbyusername', 'openstudio', fullname($slotlocker)));
        }
    }

    public static function content_show_unlock($slot, $permissions) {
        if ((!$permissions->feature_enablelock && $slot->locktype > self::NONE &&
                        ($permissions->contentismine || $permissions->canlockothers || $permissions->managecontent))
                OR
                ($slot->locktype > self::NONE &&
                        ($permissions->contentismine && $slot->lockedby == $permissions->activeuserid ||
                                $slot->lockedby > 0 && ($permissions->canlockothers || $permissions->managecontent)))) {
            // Show UNLOCK button.
            return true;
        } else {
            // Hide UNLOCK button.
            return false;
        }
    }

    public static function content_show_crud($slot, $permissions) {
        if (!isset($slot) || !isset($slot->locktype)) {
            return false;
        }

        if ($permissions->feature_enablelock && (
                        $slot->locktype == self::ALL ||
                        $slot->locktype == self::CRUD ||
                        $slot->locktype == self::SOCIAL_CRUD ||
                        $slot->locktype == self::COMMENT_CRUD)) {
            return false;
        }

        return true;
    }

    public static function determine_lock_status($slot) {
        if (is_object($slot)) {
            return self::processing($slot);
        } else {
            return $slot;
        }
    }
}
