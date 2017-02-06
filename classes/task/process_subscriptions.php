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
 * A scheduled task for Studio.
 *
 * @package mod_openstudio
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_openstudio\task;

use mod_openstudio\local\api\subscription;
use mod_openstudio\local\util\defaults;

defined('MOODLE_INTERNAL') || die();

class process_subscriptions extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cron_subscriptions', 'mod_openstudio');
    }

    /**
     * Function to be run periodically according to the moodle cron
     * This function searches for things that need to be done, such
     * as sending out mail, toggling flags etc ...
     *
     * To process user email subscription and send out hourly or daily emails.
     *
     * @return boolean
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/openstudio/api/subscription.php');

        /*
         * We wrap the code in exception blocks to prevent one job from
         * killing other jobs.
         */

        try {
            /*
             * Calulcate the number of subscription records to process for daily and hourly frequency.
             * We do this by dividing SUBSCRIPTIONTOPROCESSPERCRONRUN by 2.
             *
             * The division ensure that at least a portion of the daily and hourly subscriptions
             * are processed per CRON run rather than letting for example the hourly subscription
             * taking all the processing time.
             *
             * To be more accurate, if there is less daily subscriptions than the processing limit,
             * then we process more hourly subscriptions.
             */
            $numberofsubscriptionstoprocess = ceil(defaults::SUBSCRIPTIONTOPROCESSPERCRONRUN / 2);
            $numberofdailysubscriptions = $DB->count_records(
                    'openstudio_subscriptions', array('frequency' => subscription::FREQUENCY_DAILY));
            if ($numberofdailysubscriptions > $numberofsubscriptionstoprocess) {
                $numberofdailysubscriptions = $numberofsubscriptionstoprocess;
                $numberofhourlysubscriptions = $numberofsubscriptionstoprocess;
            } else {
                $numberofhourlysubscriptions = defaults::SUBSCRIPTIONTOPROCESSPERCRONRUN - $numberofdailysubscriptions;
            }

            // Process hourly subscriptions.
            studio_api_notification_process_subscriptions(
                    0, subscription::FREQUENCY_HOURLY, $numberofhourlysubscriptions);

            // Process daily subscriptions.
            studio_api_notification_process_subscriptions(
                    0, subscription::FREQUENCY_DAILY, $numberofdailysubscriptions);
        } catch (\Exception $e) {
            mtrace("Open Studio exception occurred processing subscriptions: " .
                    $e->getMessage() . "\n\n" .
                    $e->debuginfo . "\n\n" .
                    $e->getTraceAsString()."\n\n");
        }
    }
}
