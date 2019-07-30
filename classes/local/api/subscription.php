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
 * API functions for subscription emails.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

use mod_openstudio\output\subscription_email;
use mod_openstudio\output\email_renderer;
use mod_openstudio\local\util;

defined('MOODLE_INTERNAL') || die();

class subscription {

    const GROUP = 1;
    const MODULE = 2;
    const CONTENT = 3;

    const FORMAT_HTML = 1;
    const FORMAT_PLAIN = 2;

    const FREQUENCY_HOURLY = 1;
    const FREQUENCY_DAILY = 2;

    /**
     * Creates new subscription.
     *
     * @param int $subscriptiontype
     * @param int $userid
     * @param int $studioid
     * @param int $format
     * @param int $slotid
     * @param int $frequency
     * @return mixed Return id of created subscription, or false if error.
     */
    public static function create(
            $subscriptiontype, $userid, $studioid, $format, $slotid = 0, $frequency = null) {

        global $DB;

        try {
            if ($frequency == null) {
                $frequency = subscription::FREQUENCY_DAILY;
            }

            if ($slotid == '') {
                $slotid = 0;
            }

            $insertdata = array();
            $insertdata['subscription'] = $subscriptiontype;
            $insertdata['userid'] = $userid;
            $insertdata['openstudioid'] = $studioid;
            $insertdata['contentid'] = $slotid;

            // Before inserting, let's check if a duplicate entry already exists.
            $subscriptionexists = $DB->get_record('openstudio_subscriptions', $insertdata);

            // Already exists, just update and return true.
            if ($subscriptionexists != false) {
                return self::update($subscriptionexists->id, $format, $frequency);
            }

            // Otherwise, create the other fields and insert.
            $insertdata['frequency'] = $frequency;
            $insertdata['format'] = $format;
            $insertdata['timemodified'] = time();

            return $DB->insert_record('openstudio_subscriptions', $insertdata);
        } catch (\Exception $e) {
            // Default to returning false.
        }

        return false;
    }

    /**
     * Get data about a user's current subscription
     *
     * @param int $userid
     * @param int $studioid
     * @return mixed return notification data or false if error.
     */
    public static function get($userid, $studioid) {
        global $DB;

        try {
            $subscriptions = $DB->get_records('openstudio_subscriptions', ['userid' => $userid, 'openstudioid' => $studioid]);
            $result = [];
            foreach ($subscriptions as $subscription) {
                $result[$subscription->subscription] = $subscription;
            }
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * Update a subscription.
     *
     * @param int $subscriptionid
     * @param int $format
     * @param int $frequency
     * @param int $timeprocessed
     * @param int $userid
     * @param bool $checkpermissions
     * @return bool Return true if subscription is deleted.
     */
    public static function update(
            $subscriptionid, $format = '', $frequency = '', $timeprocessed = '',
            $userid = null, $checkpermissions = false) {

        global $DB;

        $insertdata = array();
        $insertdata['id'] = $subscriptionid;

        if ($frequency != '') {
            $insertdata['frequency'] = $frequency;
        }

        if ($timeprocessed != '') {
            $insertdata['timeprocessed'] = time();
        }
        if ($format != '') {
            $insertdata['format'] = $format;
        }
        $insertdata['timemodified'] = time();

        try {
            $subscriptiondata = $DB->get_record('openstudio_subscriptions',
                    array('id' => $subscriptionid), '*', MUST_EXIST);

            if ($checkpermissions) {
                if ($subscriptiondata->userid != $userid) {
                    return false;
                }
            }

            if ($subscriptiondata != false) {
                // Update record.
                $result = $DB->update_record('openstudio_subscriptions', $insertdata);
            }

            if (!$result) {
                throw new \Exception('Failed to update subscription.');
            }

            return true;
        } catch (\Exception $e) {
            // Default to return false.
        }

        return false;
    }

    /**
     * Delete's a subscription.
     *
     * @param int $subscriptionid
     * @param int $userid
     * @param bool $checkpermissions
     * @return bool Return true if subscription is deleted.
     */
    public static function delete(
            $subscriptionid, $userid = null, $checkpermissions = false) {

        global $DB;

        try {
            $subscriptiondata = $DB->get_record('openstudio_subscriptions',
                    array('id' => $subscriptionid), '*', MUST_EXIST);

            if ($checkpermissions) {
                if ($subscriptiondata->userid != $userid) {
                    return false;
                }
            }

            // Remove record.
            $result = $DB->delete_records('openstudio_subscriptions', ['id' => $subscriptionid]);

            if ($result === false) {
                throw new \Exception(get_string('errorfailedsoftdeletesubscription', 'openstudio'));
            }

            return true;
        } catch (\Exception $e) {
            // Default to returning false.
        }

        return false;
    }

    /**
     * Processes subscription in subscription tables and send emails as needed.
     *
     * @param int $studioid Studio instance to restrict processing to.
     * @param int $frequency 0 is to process all subscriptions, otherwise subscription filter by frequency type.
     * @param int $processlimit 0 is unlimited, otherwise the number records that will be processed per studio, per execution.
     * @return bool Return true if processing is ok or false if no record was found for processing.
     */
    public static function process($studioid = 0, $frequency = 0, $processlimit = 0) {
        global $CFG, $DB, $PAGE;

        $renderer = new email_renderer($PAGE, RENDERER_TARGET_GENERAL);

        // Module context sql is defined here and is referenced in the for loop below.
        $modulecontextsql = <<<EOF
SELECT cm.id
  FROM {course_modules} cm
  JOIN {modules} m
    ON m.id = cm.module
 WHERE m.name = ?
   AND cm.instance = ?

EOF;

        // Variable to record a log of the studios that was processed in this execution run.
        $uniquestudios = array();

        // If requested, restrict the subscriptions to process by studio instance id or/and subscription frequency type.
        $conditions = array();
        if ($studioid > 0) {
            $conditions['openstudioid'] = $studioid;
        }
        if ($frequency > 0) {
            $conditions['frequency'] = $frequency;
        }
        if (empty($conditions)) {
            $conditions = null;
        }

        // Order the subscription processing by timeprocessed, prioritising on subscription
        // records which have not been processed or have not been last processed
        // for the longest period.

        // The number of subscrition records to retrieve for processing per run is also limited by
        // the constant mod_openstudio\local\defaults::SUBSCRIPTIONTOPROCESSPERCRONRUN so that the job doesnt
        // consume too much server processing.

        $sortoder = 'COALESCE(timeprocessed, 0) ASC';

        // Fetch the subscription records to process.
        $rs = $DB->get_recordset('openstudio_subscriptions', $conditions, $sortoder,
                '*', 0, \mod_openstudio\local\util\defaults::SUBSCRIPTIONTOPROCESSPERCRONRUN);

        if ($rs->valid()) {
            $processcounts = array();
            foreach ($rs as $subscription) {
                // If the studioid is not cached, cache it.
                if (!util::cache_check('subscription_processed_studioid_' . $subscription->openstudioid)) {
                    // Get the course data we need for the first time and cache it.
                    // Get Course ID from studio record.
                    $courseid = $DB->get_field("openstudio", 'course', array("id" => $subscription->openstudioid));

                    // Get Course Code to add to subject line.
                    $coursecode = $DB->get_field('course', 'shortname', array('id' => $courseid));
                    util::cache_put('subscription_coursecode_for_' . $subscription->openstudioid, $coursecode);
                    util::cache_put('subscription_processed_studioid_' . $subscription->openstudioid, true);
                } else {
                    // If it is cached, just get the coursecode value.
                    $coursecode = util::cache_get('subscription_coursecode_for_' . $subscription->openstudioid);
                }

                // Initialise processing count if necessary.
                if (!array_key_exists($subscription->openstudioid, $processcounts)) {
                    $processcounts[$subscription->openstudioid] = 0;
                }

                // Check if number of records for a given studio instance exceeds $processlimit,
                // and if so, we dont process any more records for the studio instance.
                if (($processlimit > 0) && ($processcounts[$subscription->openstudioid] >= $processlimit)) {
                    continue;
                }

                // Get and cache user info as needed.
                if (!util::cache_check('subscription_user_' . $subscription->userid)) {
                    // If user does not exist in cache, cache the info we need.
                    $userrecord = $DB->get_record("user", array("id" => $subscription->userid));
                    $userdetails = new \stdClass();
                    $userdetails->id = $userrecord->id;
                    $userdetails->username = $userrecord->username;
                    $userdetails->firstname = $userrecord->firstname;
                    $userdetails->lastname = $userrecord->lastname;
                    $userdetails->firstnamephonetic = $userrecord->firstnamephonetic;
                    $userdetails->lastnamephonetic = $userrecord->lastnamephonetic;
                    $userdetails->middlename = $userrecord->middlename;
                    $userdetails->alternatename = $userrecord->alternatename;
                    $userdetails->email = $userrecord->email;
                    $userdetails->mailformat = $userrecord->mailformat;
                    util::cache_put('subscription_user_' . $subscription->userid, $userdetails);
                } else {
                    // If user exists, just get data from the cache.
                    $userdetails = util::cache_get('subscription_user_' . $subscription->userid);
                }
                if (!util::cache_check('openstudio' . $subscription->openstudioid . 'context')) {
                    // Get Studio module class id. We'll need this for our context for capabilities.
                    $params = array('openstudio', $subscription->openstudioid);
                    $modulecontext = $DB->get_record_sql($modulecontextsql, $params);
                    $context = \context_module::instance($modulecontext->id);
                    util::cache_put('openstudio' . $subscription->openstudioid . 'context', $context);
                } else {
                    $context = util::cache_get('openstudio' . $subscription->openstudioid . 'context');
                }

                // Check if user has permissions for this to process.
                if (has_capability('mod/studio:viewothers', $context, $subscription->userid)) {
                    // Set up unqiue studios to write log.
                    $uniquestudios[$subscription->openstudioid] = $subscription->openstudioid;

                    // Check if this falls within the timescale we are after.
                    if (self::process_now($subscription->frequency, $subscription->timeprocessed)) {
                        // If the timeprocessed is empty, set to a low value for the query to run properly.
                        if ($subscription->timeprocessed == '' || $subscription->timeprocessed == null) {
                            $subscription->timeprocessed = 100;
                        }

                        $notifications = notifications::get_recent($subscription->openstudioid,
                                $subscription->userid, $subscription->timeprocessed);

                        if (count($notifications) > 0) {
                            $emailsubject = get_string('subscriptionemailsubject', 'openstudio', $coursecode);
                            $email = new subscription_email($userdetails, $notifications, $subscription->format);
                            $emailbody = $renderer->render($email);
                            // OK, We have everything, let's generate and send the email.
                            email_to_user($userdetails, $CFG->noreplyaddress,
                                $emailsubject, $emailbody['plain'], $emailbody['html']);

                            // Update the subscription entry.
                            self::update($subscription->id, '', '', time());
                        }
                    }
                } else {
                    // Even though we havent really processed the user because of lack of permission,
                    // we did process the record, so we update the subscription entry record to say it was processsed.
                    self::update($subscription->id, '', '', time());
                }
            }

            // If recordset is valid, we also need to write a log of all studios we have sent-out mails for.
            foreach ($uniquestudios as $stid => $stsent) {
                // Get course id from $stid.
                $cm = get_coursemodule_from_id('openstudio', $stid);
                if ($cm !== false) {
                    util::trigger_event($cm->id, 'subscription_sent', '',
                            "view.php?id={$stid}", util::format_log_info($stid));
                }
            }

            $rs->close();
        }

        // Return true if any studio had subscription records that was fetched and processed.
        if (count($uniquestudios) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Verifies if subscription should be processed in this round of the cron.
     *
     * @param string $frequency Hourly or daily.
     * @param int $timeprocessed last processed time in UNIX timestamp.
     * @return bool Return true if processing should happen.
     */
    private static function process_now($frequency, $timeprocessed) {
        if ($timeprocessed < 1) {
            // This has never been processed so certainly needs processing this round.
            return true;
        } else {
            if ($frequency == subscription::FREQUENCY_DAILY) {
                // Check if time processed was more than a day ago and if it was, return true, else return false.
                $cutofftime = time() - (1 * 60 * 60 * 24); // 24 hours.
                if ($timeprocessed < $cutofftime) {
                    return true;
                } else {
                    return false;
                }

            }

            if ($frequency == subscription::FREQUENCY_HOURLY) {
                // Check if time processed was more than an hour ago and if it was, return true, else return false.
                $cutofftime = time() - (1 * 60 * 60); // 1 hour.
                if ($timeprocessed < $cutofftime) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }


}
