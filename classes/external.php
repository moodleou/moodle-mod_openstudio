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
 * OpenStudio external API
 *
 * @package    mod_openstudio
 * @category   external
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use mod_openstudio\local\api\subscription;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\util;

require_once($CFG->dirroot . '/mod/openstudio/api/subscription.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * OpenStudio external functions
 *
 * @package    mod_openstudio
 * @category   external
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_openstudio_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function subscribe_parameters() {
        return new external_function_parameters(array(
                'openstudioid' => new external_value(PARAM_INT, 'Open Studio instance id'),
                'emailformat' => new external_value(PARAM_INT, 'Email format'),
                'frequency' => new external_value(PARAM_INT, 'Email frequency'),
                'userid' => new external_value(PARAM_TEXT, 'User ID'),
                'subscriptiontype' => new external_value(PARAM_INT, 'Subscription type',
                        VALUE_DEFAULT, subscription::MODULE))
        );
    }

    /**
     * Subscribe to my Studio
     *
     * @param int $openstudioid The open studio instance id
     * @param int $emailformat Email format
     * @param int $frequency Email frequency
     * @param int $userid User does the subscription
     * @param int $subscriptiontype Subscription type
     * @return array
     *  [
     *      success: boolean
     *      warning: external_warnings object,
     *      subscriptionid: int
     *  ]
     */
    public static function subscribe($openstudioid, $emailformat, $frequency, $userid, $subscriptiontype = null) {
        require_login();

        $params = self::validate_parameters(self::subscribe_parameters(), array(
                'openstudioid' => $openstudioid,
                'emailformat' => $emailformat,
                'frequency' => $frequency,
                'userid' => $userid,
                'subscriptiontype' => $subscriptiontype));
        $warnings = array();
        $result = array();

        $subscriptionid = studio_api_notification_create_subscription(
                $params['subscriptiontype'],
                $params['userid'],
                $params['openstudioid'],
                $params['emailformat'],
                0,
                $params['frequency']);

        if (!$subscriptionid) {
            $warnings[] = array(
                    'item' => 'module',
                    'itemid' => $openstudioid,
                    'warningcode' => 'cannotsubscribemodule',
                    'message' => 'Subscription create error!');
            $success = false;
        } else {
            $success = true;
        }

        $result['subscriptionid'] = $subscriptionid;
        $result['success'] = $success;
        $result['warnings'] = $warnings;

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function subscribe_returns() {
        return new external_single_structure(array(
                'subscriptionid' => new external_value(PARAM_INT, 'Open Studio instance id'),
                'success' => new external_value(PARAM_BOOL, 'Subscribe successfully'),
                'warnings' => new external_warnings())
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function unsubscribe_parameters() {
        return new external_function_parameters(array(
                'subscriptionid' => new external_value(PARAM_INT, 'Open Studio subscription id'),
                'userid' => new external_value(PARAM_TEXT, 'User ID'),
                'cmid' => new external_value(PARAM_INT, 'Course module ID'))
        );
    }

    /**
     * Unsubscribe to my Studio
     *
     * @param int $subscriptionid The open studio subscription id
     * @param int $userid User does the subscription
     * @param int $coursemoduleid Course module instance ID
     * @return array
     *  [
     *      success: boolean
     *      warning: external_warnings object,
     *  ]
     */
    public static function unsubscribe($subscriptionid, $userid, $coursemoduleid) {
        require_login();

        $params = self::validate_parameters(self::unsubscribe_parameters(), array(
                'subscriptionid' => $subscriptionid,
                'userid' => $userid,
                'cmid' => $coursemoduleid));

        $warnings = array();
        $result = array();
        $checkpermissions = true;

        $coursedata = util::render_page_init($params['cmid'], array('mod/openstudio:view'));
        $permissions = $coursedata->permissions;

        if ($permissions->managecontent) {
            $checkpermissions = false;
        }

        $success = studio_api_notification_delete_subscription(
                $params['subscriptionid'], $params['userid'], $checkpermissions);

        if (!$success) {
            $warnings[] = array(
                    'item' => 'module',
                    'itemid' => $params['subscriptionid'],
                    'warningcode' => 'cannotunsubscribemodule',
                    'message' => 'Subscription delete error!');
        }

        $result['success'] = $success;
        $result['warnings'] = $warnings;

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unsubscribe_returns() {
        return new external_single_structure(array(
                'success' => new external_value(PARAM_BOOL, 'unsubscribe successfully'),
                'warnings' => new external_warnings())
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function flag_content_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'cid' => new external_value(PARAM_INT, 'Content ID'),
                'fid' => new external_value(PARAM_INT, 'Flag ID'),
                'mode' => new external_value(PARAM_TEXT, 'Flag mode'))
        );
    }

    /**
     * Flag a content.
     *
     * @param int $cmid Course module ID
     * @param int $cid Content ID
     * @param int $fid Flag ID
     * @param text $mode Flag mode
     * @return array
     *  [
     *      success: boolean,
     *      warning: external_warnings object,
     *      mode: text,
     *      flagtext: text,
     *      flagremoveclass: text,
     *      flagaddclass: text,
     *      fid: int
     *  ]
     */
    public static function flag_content($cmid, $cid, $fid, $mode) {
        global $USER;

        $context = context_module::instance($cmid);
        external_api::validate_context($context);

        $results = array();
        $warnings = array();
        $success = false;
        $flagtext = '';
        $flagremoveclass = '';
        $flagaddclass = '';
        $mode = trim($mode);
        $params = self::validate_parameters(self::flag_content_parameters(), array(
            'cmid' => $cmid,
            'cid' => $cid,
            'fid' => $fid,
            'mode' => $mode));

        $coursedata = util::render_page_init($params['cmid'], array('mod/openstudio:view'));
        $cm = $coursedata->cm;

        $result = flags::toggle($params['cid'], $params['fid'], $mode, $USER->id);

        if ($result) {
            $success = true;
            // Log page action.
            if ($params['fid'] == 'on') {
                $logaction = false;
                switch ($params['fid']) {
                    case flags::FAVOURITE:
                        $logaction = 'content_favourite_flagged';
                        $logtext = 'content favourite flagged';
                        break;

                    case flags::MADEMELAUGH:
                        $logaction = 'content_smile_flagged';
                        $logtext = 'content smile flagged';
                        break;

                    case flags::INSPIREDME:
                        $logaction = 'content_inspire_flagged';
                        $logtext = 'content inspire flagged';
                        break;
                }

                if ($logaction !== false) {
                    util::trigger_event(
                        $cm->id, $logaction, '', util::get_page_name_and_params(true), $logtext);
                }

            }

            $total = flags::count_for_content($params['cid'], $params['fid']) + 0;
            switch ($params['fid']) {
                case flags::FAVOURITE:
                    $flagtext = get_string('contentflagxfavourites', 'openstudio',
                            array('number' => $total));
                    switch ($mode) {
                        case 'on':
                            $flagremoveclass = 'openstudio-content-view-icon-favourite';
                            $flagaddclass = 'openstudio-content-view-icon-favourite-active';
                        break;
                        case 'off':
                            $flagremoveclass = 'openstudio-content-view-icon-favourite-active';
                            $flagaddclass = 'openstudio-content-view-icon-favourite';
                            break;
                    }
                    break;
                case flags::MADEMELAUGH:
                    $flagtext = get_string('contentflagxsmiles', 'openstudio',
                            array('number' => $total));
                    switch ($mode) {
                        case 'on':
                            $flagremoveclass = 'openstudio-content-view-icon-participation';
                            $flagaddclass = 'openstudio-content-view-icon-participation-active';
                            break;
                        case 'off':
                            $flagremoveclass = 'openstudio-content-view-icon-participation-active';
                            $flagaddclass = 'openstudio-content-view-icon-participation';
                            break;
                    }
                    break;
                case flags::INSPIREDME:
                    $flagtext = get_string('contentflagxinspired', 'openstudio',
                            array('number' => $total));
                    switch ($mode) {
                        case 'on':
                            $flagremoveclass = 'openstudio-content-view-icon-inspiration';
                            $flagaddclass = 'openstudio-content-view-icon-inspiration-active';
                            break;
                        case 'off':
                            $flagremoveclass = 'openstudio-content-view-icon-inspiration-active';
                            $flagaddclass = 'openstudio-content-view-icon-inspiration';
                            break;
                    }
                    break;
            }
        } else {
            $warnings[] = array(
                'item' => 'module',
                'itemid' => $params['cmid'],
                'warningcode' => 'cannotflagcontent',
                'message' => 'Flag content error!');
        }

        $results['success'] = $success;
        $results['warnings'] = $warnings;
        $results['mode'] = $mode == 'on' ? 'off' : 'on';
        $results['flagtext'] = $flagtext;
        $results['flagremoveclass'] = $flagremoveclass;
        $results['flagaddclass'] = $flagaddclass;
        $results['fid'] = $params['fid'];

        return $results;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function flag_content_returns() {
        return new external_single_structure(array(
                'success' => new external_value(PARAM_BOOL, 'flag successfully'),
                'mode' => new external_value(PARAM_TEXT, 'flag mode'),
                'flagtext' => new external_value(PARAM_TEXT, 'flag text'),
                'flagremoveclass' => new external_value(PARAM_TEXT, 'flag remove class'),
                'flagaddclass' => new external_value(PARAM_TEXT, 'flag add new class'),
                'fid' => new external_value(PARAM_INT, 'flag ID'),
                'warnings' => new external_warnings())
        );
    }

}
