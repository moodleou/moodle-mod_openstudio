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
use mod_openstudio\local\api\notifications;
use mod_openstudio\local\util;
use mod_openstudio\local\api\content;
use mod_openstudio\local\api\contentversion;
use mod_openstudio\local\renderer_utils;
use mod_openstudio\local\api\comments;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\api\user;

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
     * This function checks whether a content is locked or unlocked .
     *
     * @param int $cid Content ID
     * @param bool $errorthrown If true, throw error when content is locked
     * @return bool
     * @throws moodle_exception
     */
    public static function validate_locking_status($cid, $errorthrown = true) {
        $cid = (int)$cid;
        if (lock::check($cid) === false) {
            return true;
        }

        if ($errorthrown) {
            throw new \moodle_exception('event:contentlocked', 'openstudio');
        }

        return false;
    }

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
     *      subscriptionid: int
     *  ]
     * @throws moodle_exception
     */
    public static function subscribe($openstudioid, $emailformat, $frequency, $userid, $subscriptiontype = null) {
        require_login();

        $params = self::validate_parameters(self::subscribe_parameters(), array(
                'openstudioid' => $openstudioid,
                'emailformat' => $emailformat,
                'frequency' => $frequency,
                'userid' => $userid,
                'subscriptiontype' => $subscriptiontype));
        $result = array();

        $subscriptionid = subscription::create(
                $params['subscriptiontype'],
                $params['userid'],
                $params['openstudioid'],
                $params['emailformat'],
                0,
                $params['frequency']);

        if (!$subscriptionid) {
            throw new \moodle_exception('errorsubscribe', 'openstudio');
        }

        $result['subscriptionid'] = $subscriptionid;

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function subscribe_returns() {
        return new external_single_structure(array(
                'subscriptionid' => new external_value(PARAM_INT, 'Open Studio instance id'))
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
     *      subscriptionid: int
     *  ]
     * @throws moodle_exception
     */
    public static function unsubscribe($subscriptionid, $userid, $coursemoduleid) {
        require_login();

        $params = self::validate_parameters(self::unsubscribe_parameters(), array(
                'subscriptionid' => $subscriptionid,
                'userid' => $userid,
                'cmid' => $coursemoduleid));

        $checkpermissions = true;

        $coursedata = util::render_page_init($params['cmid'], array('mod/openstudio:view'));
        $permissions = $coursedata->permissions;

        if ($permissions->managecontent) {
            $checkpermissions = false;
        }

        $success = subscription::delete(
                $params['subscriptionid'], $params['userid'], $checkpermissions);

        if (!$success) {
            throw new \moodle_exception('errorunsubscribe', 'openstudio');
        }

        return [
            'subscriptionid' => $params['subscriptionid']
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unsubscribe_returns() {
        return new external_single_structure(array(
                'subscriptionid' => new external_value(PARAM_INT, 'Subscription ID'))
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function delete_version_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'cid' => new external_value(PARAM_INT, 'Content ID'),
                'cvid' => new external_value(PARAM_INT, 'Content version ID'))
        );
    }

    /**
     * Flag a content.
     *
     * @param int $cmid Course module ID
     * @param int $cid Content ID
     * @param int $cvid Content version ID
     * @return array
     *  [
     *      success: boolean,
     *      warning: external_warnings object,
     *  ]
     */
    public static function delete_version($cmid, $cid, $cvid) {
        global $USER;

        $context = context_module::instance($cmid);
        external_api::validate_context($context);

        $params = self::validate_parameters(self::delete_version_parameters(), array(
                'cmid' => $cmid,
                'cid' => $cid,
                'cvid' => $cvid));

        $results = array();
        $success = false;

        $coursedata = util::render_page_init($params['cmid'], array('mod/openstudio:view'));
        $cm = $coursedata->cm;
        $permissions = $coursedata->permissions;
        $userid = $USER->id;

        $contentdata = content::get($cid);
        $contetversiondata = contentversion::get($cvid, $userid, $cm);

        if ($contetversiondata && $contentdata) {
            $actionallowed = ($contentdata->userid == $userid) && $permissions->addcontent;
            $actionallowed = $actionallowed || $permissions->managecontent;
            if ($actionallowed) {
                $success = content::version_delete($userid, $cvid);
            }
        }

        $results['success'] = $success;

        return $results;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function delete_version_returns() {
        return new external_single_structure(array(
                'success' => new external_value(PARAM_BOOL, 'delete successfully'))
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

        $params = self::validate_parameters(self::flag_content_parameters(), array(
                'cmid' => $cmid,
                'cid' => $cid,
                'fid' => $fid,
                'mode' => $mode));
        $context = context_module::instance($cmid);
        external_api::validate_context($context);
        require_capability('mod/openstudio:addcontent', $context);

        $results = array();
        $warnings = array();
        $success = false;
        $flagtext = '';
        $flagremoveclass = '';
        $flagaddclass = '';
        $mode = trim($params['mode']);

        list($course, $cm) = get_course_and_cm_from_cmid($params['cmid'], 'openstudio');
        // Validate locking status.
        self::validate_locking_status($params['cid']);

        $coursedata = util::render_page_init($params['cmid'], array('mod/openstudio:view'));
        $cm = $coursedata->cm;

        $result = flags::toggle($params['cid'], $params['fid'], $mode, $USER->id);

        if ($result) {
            $success = true;
            // Log page action.
            if ($mode == 'on') {
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
                    case flags::NEEDHELP:
                        $logaction = 'content_helpme_flagged';
                        $logtext = 'content request feedback flagged';
                        break;
                }

                if ($logaction !== false) {
                    util::trigger_event(
                        $cm->id, $logaction, $cid, util::get_page_name_and_params(true), $logtext, $params['fid']);
                }

            } else {
                notifications::delete_unread_for_flag($params['cid'], $USER->id, $params['fid']);
            }

            $iscontentflagrequestfeedback = false;
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
                case flags::NEEDHELP:
                    $iscontentflagrequestfeedback = true;
                    switch ($mode) {
                        case 'on':
                            $flagremoveclass = 'openstudio-item-request-feedback-cancel';
                            $flagaddclass = 'openstudio-item-request-feedback';
                            $flagtext = get_string('contentflagaskforhelpcancel', 'openstudio');
                            break;
                        case 'off':
                            $flagremoveclass = 'openstudio-item-request-feedback';
                            $flagaddclass = 'openstudio-item-request-feedback-cancel';
                            $flagtext = get_string('contentflagaskforhelp', 'openstudio');
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
        $results['iscontentflagrequestfeedback'] = $iscontentflagrequestfeedback;

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
                'iscontentflagrequestfeedback' => new external_value(PARAM_BOOL, 'is content flag request feedback')
        ));
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function add_comment_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'cid' => new external_value(PARAM_INT, 'Content ID'),
                'commenttext' => new external_value(PARAM_RAW, 'Comment text'),
                'commentattachment' => new external_value(PARAM_INT, 'Comment attachment'),
                'inreplyto' => new external_value(PARAM_INT, 'Parent comment ID'))
        );
    }

    /**
     * Add new comment
     *
     * @param int $cmid Course module ID
     * @param int $cid Content ID
     * @param string $commentext Comment text
     * @param string $commentattachment Comment attachment
     * @param int $inreplyto Parent comment ID
     * @return array
     *  [
     *      commentid: int
     *      commenthtml: string
     *  ]
     * @throws moodle_exception
     */
    public static function add_comment($cmid, $cid, $commenttext = '',
            $commentattachment = 0, $inreplyto = 0) {
        global $USER, $PAGE, $CFG;
        $userid = $USER->id;

        // Init and check permission.
        $coursedata = util::render_page_init($cmid, array('mod/openstudio:addcomment'));
        $cm = $coursedata->cm;
        $mcontext = $coursedata->mcontext;
        $permissions = $coursedata->permissions;

        // Validate input parameters and context.
        self::validate_context($mcontext);
        $params = self::validate_parameters(self::add_comment_parameters(), array(
                'cmid' => $cmid,
                'cid' => $cid,
                'commenttext' => $commenttext,
                'commentattachment' => $commentattachment,
                'inreplyto' => $inreplyto));

        // Validate locking status.
        self::validate_locking_status($params['cid']);

        // Check if user has permission to add content.
        $actionallowed = $permissions->addcomment || $permissions->managecontent;

        if ($actionallowed) {
            try {
                // Standardize comment text.
                $commenttext = trim($params['commenttext']);

                // Check if comment content is not empty.
                $draftareafiles = file_get_drafarea_files($params['commentattachment'], $filepath = '/');
                if (($commenttext != '') || (is_object($draftareafiles) && !empty($draftareafiles->list))) {

                    // Set default comment text if user just upload comment attachment.
                    if ($commenttext == '') {
                        $commenttext = get_string('contentcommentsaudioattached', 'openstudio');
                    }

                    // Do add comment.
                    $folderid = null;
                    $containingfolder = folder::get_containing_folder($params['cid']);
                    if ($containingfolder) {
                        $folderid = $containingfolder->id;
                    }
                    $context = context_module::instance($cm->id);
                    $commentid = comments::create($params['cid'], $userid, $commenttext, $folderid,
                        ['id' => $params['commentattachment']], $context, $inreplyto);
                    $eventurl = new moodle_url('/mod/openstudio/content.php', ['id' => $params['cmid'], 'sid' => $params['cid']]);
                    if ($inreplyto) {
                        util::trigger_event(
                                $params['cmid'], 'content_commentreply_created', $params['cid'], $eventurl, '', null, $inreplyto);
                    } else {
                        util::trigger_event(
                                $params['cmid'], 'content_comment_created', $params['cid'], $eventurl, '', null, $commentid);
                    }
                    flags::comment_toggle($params['cid'], $commentid, $userid, 'on', false, flags::FOLLOW_CONTENT);

                    // Check if process is success.
                    if (!$commentid) {
                        throw new \moodle_exception('commenterror', 'openstudio');
                    }

                    // Render comment html and send to client.
                    $commentdata = comments::get($commentid, $userid);
                    $commentdata->timemodified = userdate($commentdata->timemodified,
                            get_string('formattimedatetime', 'openstudio'));

                    $user = user::get_user_by_id($commentdata->userid);
                    $commentdata->fullname = fullname($user);

                    // User picture.
                    $picture = new user_picture($user);
                    $commentdata->userpictureurl = $picture->get_url($PAGE)->out(false);

                    $renderer = $PAGE->get_renderer('mod_openstudio');
                    // Check comment attachment.
                    if ($file = comments::get_attachment($commentdata->id)) {
                        $commentdata->commenttext .= renderer_utils::get_media_filter_markup($file);
                    }

                    $commentdata->commenttext = format_text($commentdata->commenttext);

                    $commentdata->deleteenable = true;
                    $commentdata->reportenable = false;

                    $commenthtml = $renderer->content_comment($commentdata);

                } else {
                    // Comment empty error.
                    throw new \moodle_exception('emptycomment', 'openstudio');
                }
            } catch (Exception $e) {
                // Database error.
                throw new \moodle_exception('commenterror', 'openstudio');
            }
        } else {
            // No permision.
            throw new \moodle_exception('nocommentpermissions', 'openstudio');
        }

        return [
            'commentid' => $commentid,
            'commenthtml' => $commenthtml
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function add_comment_returns() {
        return new external_single_structure(array(
                'commentid' => new external_value(PARAM_INT, 'Added comment ID'),
                'commenthtml' => new external_value(PARAM_RAW, 'Comment content HTML'))
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function flag_comment_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'cid' => new external_value(PARAM_INT, 'Content ID'),
                'commentid' => new external_value(PARAM_INT, 'Comment ID'),
                'fid' => new external_value(PARAM_INT, 'Flag ID', flags::COMMENT_LIKE)
        ));
    }

    /**
     * Flag comment
     *
     * @param int $cmid Course module ID
     * @param int $cid Content ID
     * @param int $commentid Comment ID
     * @param int $flagid The flag being toggled.
     * @return array
     *  [
     *      count: int
     *  ]
     * @throws moodle_exception
     */
    public static function flag_comment($cmid, $cid, $commentid, $flagid = flags::COMMENT_LIKE) {
        global $USER;
        $userid = $USER->id;

        // Validate input parameters.
        $params = self::validate_parameters(self::flag_comment_parameters(), array(
                'cmid' => $cmid,
                'cid' => $cid,
                'commentid' => $commentid,
                'fid' => $flagid));

        // Validate locking status.
        self::validate_locking_status($params['cid']);

        // Init and check permission.
        $coursedata = util::render_page_init($params['cmid'], array('mod/openstudio:view'));
        $permissions = $coursedata->permissions;

        $actionallowed = $permissions->addcomment || $permissions->managecontent;
        $comment = comments::get($params['commentid']);

        if ($actionallowed) {
            try {
                if ($comment) {
                    if (flags::has_user_flagged_comment($params['commentid'], $userid, $params['fid'])) {
                        $toggle = 'off';
                    } else {
                        $toggle = 'on';
                    }
                    flags::comment_toggle($params['cid'], $params['commentid'], $userid, $toggle, false, $params['fid']);
                    $count = flags::count_for_comment($params['commentid'], $params['fid']);
                    if ($params['fid'] == flags::COMMENT_LIKE) {
                        if ($toggle === 'off') {
                            notifications::delete_unread_for_comment_flag($params['commentid'], $userid, flags::COMMENT_LIKE);
                        } else {
                            $eventurl = new moodle_url('/mod/openstudio/content.php',
                                    ['id' => $params['cmid'], 'sid' => $params['cid']]);
                            util::trigger_event(
                                    $params['cmid'], 'content_comment_flagged', $params['cid'],
                                    $eventurl, '', flags::COMMENT_LIKE, $commentid);
                        }
                    }
                } else {
                    throw new \moodle_exception('errorinvalidcomment', 'openstudio');
                }
            } catch (Exception $e) {
                // Database error.
                throw new \moodle_exception('commenterror', 'openstudio');
            }
        } else {
            // No permision.
            throw new \moodle_exception('nocommentpermissions', 'openstudio');
        }

        return [
            'count' => $count
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function flag_comment_returns() {
        return new external_single_structure(array(
                'count' => new external_value(PARAM_INT, 'Comment count'))
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function delete_comment_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'commentid' => new external_value(PARAM_INT, 'Comment ID'))
        );
    }

    /**
     * Flag comment
     *
     * @param int $cmid Course module ID
     * @param int $commentid Comment ID
     * @return array
     *  [
     *      commentid: int
     *  ]
     * @throws moodle_exception
     */
    public static function delete_comment($cmid, $commentid) {
        global $USER;
        $userid = $USER->id;

        // Validate input parameters.
        $params = self::validate_parameters(self::delete_comment_parameters(), array(
                'cmid' => $cmid,
                'commentid' => $commentid));

        // Init and check permission.
        $coursedata = util::render_page_init($params['cmid'], array('mod/openstudio:addcomment'));
        $permissions = $coursedata->permissions;

        $comment = comments::get($params['commentid']);
        $actionallowed = ($permissions->addcomment && $comment->userid == $USER->id)
                || $permissions->managecontent;

        // Validate locking status.
        self::validate_locking_status($comment->contentid);

        if ($actionallowed) {
            try {
                if ($comment) {
                    $success = comments::delete($params['commentid'], $userid);
                    notifications::delete_unread_for_comment($params['commentid']);
                    if (!$success) {
                        throw new \moodle_exception('commenterror', 'openstudio');
                    }
                } else {
                    throw new \moodle_exception('errorinvalidcomment', 'openstudio');
                }
            } catch (Exception $e) {
                // Database error.
                throw new \moodle_exception('commenterror', 'openstudio');
            }
        } else {
            // No permision.
            throw new \moodle_exception('nocommentpermissions', 'openstudio');
        }

        return [
            'commentid' => $params['commentid']
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function delete_comment_returns() {
        return new external_single_structure(array(
                'commentid' => new external_value(PARAM_INT, 'Deleted comment ID'))
        );
    }
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function delete_content_parameters() {
        return new external_function_parameters(array(
                'id' => new external_value(PARAM_INT, 'Open studio instance ID'),
                'cid' => new external_value(PARAM_INT, 'Content ID'),
                'containingfolder' => new external_value(PARAM_INT, 'Folder ID', VALUE_OPTIONAL, 0))
        );
    }

    /**
     * Delete content
     *
     * @param int $id Open studio instance ID
     * @param int $cid Content ID
     * @param int $containingfolder Folder ID
     * @return array
     *  [
     *      success: boolean,
     *      vid: int
     *  ]
     * @throws moodle_exception
     */
    public static function delete_content($id, $cid, $containingfolder = 0) {
        $params = self::validate_parameters(self::delete_content_parameters(), array(
            'id' => $id,
            'cid' => $cid,
            'containingfolder' => $containingfolder));

        $success = false;

        // Init and check permission.
        global $USER;
        $userid = $USER->id;
        $coursedata = util::render_page_init($id);
        $cm = $coursedata->cm;
        $cminstance = $coursedata->cminstance;
        $course = $coursedata->course;
        $permissions = $coursedata->permissions;
        require_login($course, true, $cm);

        // Get content and content version data.
        $showdeletedcontentversions = false;
        if ($permissions->viewdeleted || $permissions->managecontent) {
            $showdeletedcontentversions = true;
        }
        $contentandversions = contentversion::get_content_and_versions($params['cid'], $userid, $showdeletedcontentversions);
        $contentdata = $contentandversions->contentdata;

        // Delete content.
        $actionallowed = ($contentdata->userid == $userid) && $permissions->addcontent;
        $actionallowed = $actionallowed || $permissions->managecontent;
        if ($actionallowed) {
            if ($params['containingfolder']) {
                if ($contentdata) {
                    $provenance = folder::get_content_provenance($params['containingfolder'], $contentdata->id);
                    $success = folder::remove_content($params['containingfolder'], $contentdata->id, $userid);
                    if (empty($provenance->provenanceid) || $provenance->provenanceid != $contentdata->id) {
                        // If this isn't just a soft link, empty the actual slot as well.
                        content::empty_content($userid, $contentdata->id, true, $cminstance->versioning, $cm);
                    }
                }
            } else {
                if ($contentdata->levelid > 0 && $contentdata->levelcontainer > 0 && $permissions->versioningon) {
                    $success = content::delete($userid, $params['cid'], $cminstance->versioning, $cm);
                } else {
                    $success = content::empty_content($userid, $params['cid'], true, $cminstance->versioning, $cm);
                }
            }
            notifications::delete_unread_for_post($params['cid']);
        }

        if (!$success) {
            throw new \moodle_exception('errorcontentnotdeleted', 'openstudio');
        }

        $result['success'] = $success;
        if ($contentdata->levelid == 0) {
            $result['vid'] = content::VISIBILITY_PRIVATE_PINBOARD;
        } else {
            $result['vid'] = content::VISIBILITY_PRIVATE;
        }

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function delete_content_returns() {
        return new external_single_structure(array(
                'success' => new external_value(PARAM_BOOL, 'Unlock successfully'),
                'vid' => new external_value(PARAM_INT, 'Visibility ID'))
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function lock_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'cid' => new external_value(PARAM_INT, 'Content ID'),
                'locktype' => new external_value(PARAM_INT, 'Lock type'))
        );
    }

    /*
     * Lock content
     *
     * @param int $cmid Course module ID
     * @param int $cid Content ID
     * @param int $locktype Lock type
     * @return array
     *  [
     *      cid: int
     *  ]
     * @throws moodle_exception
     */
    public static function lock($cmid, $cid, $locktype) {

        $context = context_module::instance($cmid);
        self::validate_context($context);
        $params = self::validate_parameters(self::lock_parameters(), array(
                'cmid' => $cmid,
                'cid' => $cid,
                'locktype' => $locktype));

        global $USER;
        $userid = $USER->id;

        $success = false;

        if ($locktype == lock::ALL) {
            $success = lock::lock_content($userid, $params['cid'], $params['locktype']);
            util::trigger_event($params['cmid'], 'content_locked', "{$userid}/{$params['locktype']}",
                    "view.php?id={$params['cid']}", util::format_log_info($params['cid']));
        }

        if (!$success) {
            throw new moodle_exception('errorcontentlock', 'openstudio');
        }

        $result['cid'] = $params['cid'];

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function lock_returns() {
        return new external_single_structure(array(
                'cid' => new external_value(PARAM_INT, 'Locked content ID'))
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function unlock_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'cid' => new external_value(PARAM_INT, 'Content ID'),
                'locktype' => new external_value(PARAM_INT, 'Lock type'))
        );
    }

    /**
     * Unlock content
     *
     * @param int $cmid Course module ID
     * @param int $cid Content ID
     * @param int $locktype Lock type
     * @return array
     *  [
     *      cid: int
     *  ]
     * @throws moodle_exception
     */
    public static function unlock($cmid, $cid, $locktype) {
        $context = context_module::instance($cmid);
        self::validate_context($context);

        $params = self::validate_parameters(self::unlock_parameters(), array(
                'cmid' => $cmid,
                'cid' => $cid,
                'locktype' => $locktype));

        global $USER;
        $userid = $USER->id;

        $success = false;

        if ($locktype == lock::NONE) {
            $success = lock::lock_content($userid, $params['cid'], $params['locktype']);
            util::trigger_event($params['cmid'], 'content_unlocked', "{$userid}/{$params['locktype']}",
                    "view.php?id={$params['cid']}", util::format_log_info($params['cid']));
        }

        if (!$success) {
            throw new moodle_exception('errorcontentunlock', 'openstudio');
        }

        $result['cid'] = $params['cid'];

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unlock_returns() {
        return new external_single_structure(array(
                        'cid' => new external_value(PARAM_INT, 'Unlocked content ID'))
        );
    }

    /**
     * Parameters for read_notifications
     *
     * @return external_function_parameters
     */
    public static function read_notifications_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'ids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'notification ID'), 'notification IDs'
            )
        ]);
    }

    /**
     * Mark the specified notifications as read.
     *
     * @param int $cmid
     * @param int[] $ids
     */
    public static function read_notifications($cmid, array $ids) {
        global $USER;
        $params = external_api::validate_parameters(self::read_notifications_parameters(), [
            'cmid' => $cmid,
            'ids' => $ids
        ]);
        $context = context_module::instance($params['cmid']);
        external_api::validate_context($context);
        require_capability('mod/openstudio:addcontent', $context);

        foreach ($params['ids'] as $id) {
            if (notifications::is_for_user($id, $USER->id)) {
                notifications::mark_read($id);
            } else {
                throw new Exception('User can only mark their own notifications read');
            }
        }
    }

    /**
     * Return values for read_notifications.
     *
     * @return external_value
     */
    public static function read_notifications_returns() {
        return new external_value(PARAM_BOOL, 'Success');
    }
}
