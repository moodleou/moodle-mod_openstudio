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

use mod_openstudio\completion\custom_completion;
use mod_openstudio\local\util\defaults;
use mod_openstudio\local\api\search;
use mod_openstudio\local\api\stream;
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
use mod_openstudio\local\api\group;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\api\tracking;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_files;
use core_external\external_format_value;
use core_external\external_warnings;

require_once($CFG->libdir . '/formslib.php');

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
    public static function validate_locking_status($cid, $locktype = lock::ALL, $errorthrown = true) {
        $cid = (int)$cid;

        $contentdata = content::get($cid);

        if (!$contentdata) {
            throw new \moodle_exception('errorinvalidcontent', 'openstudio');
        }

        $contentdata = lock::determine_lock_status($contentdata);
        $islocked = ($contentdata->locktype == lock::ALL || $contentdata->locktype == $locktype);
        if ($islocked && $errorthrown) {
            throw new \moodle_exception('event:contentlocked', 'openstudio');
        }

        return $islocked;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function browse_posts_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'folderid' => new external_value(PARAM_INT, 'Folder ID'),
                'selectedposts' => new external_value(PARAM_TEXT, 'Selected posts ID'),
                'search' => new external_value(PARAM_TEXT, 'Data search post'))
        );
    }

    /**
     * Browse posts.
     *
     * @param int $cmid Course module ID
     * @param int $folderid Folder ID
     * @param text $selectedposts selected post ID.
     * @param text $search search value
     * @return array
     *  [
     *      result: object
     *  ]
     */
    public static function browse_posts($cmid, $folderid, $selectedposts, $search) {
        global $PAGE, $USER, $OUTPUT;

        $context = context_module::instance($cmid);
        external_api::validate_context($context);

        $userid = $USER->id;
        $coursedata = util::render_page_init($cmid);
        $cm = $coursedata->cm;
        $cminstance = $coursedata->cminstance;
        $permissions = $coursedata->permissions;

        $total = 0;
        $html = '';
        $result = array();
        $contents = array();
        $contentdata = array();
        $pagesize = defaults::FOLDERBROWSEPOSTPAGESIZE;

        if ($permissions->feature_enablefoldersanycontent) {
            $filter = 'openstudio_ousearch_filter_browseslots';
        } else {
            $filter = 'openstudio_ousearch_filter_browseslots_useronly';
        }

        // Filter types for browse post (exclude folder type).
        $filtertypes = array(content::TYPE_NONE, content::TYPE_TEXT, content::TYPE_IMAGE, content::TYPE_IMAGE_EMBED,
                content::TYPE_VIDEO, content::TYPE_VIDEO_EMBED, content::TYPE_AUDIO, content::TYPE_AUDIO_EMBED,
                content::TYPE_DOCUMENT, content::TYPE_DOCUMENT_EMBED, content::TYPE_PRESENTATION,
                content::TYPE_PRESENTATION_EMBED, content::TYPE_SPREADSHEET, content::TYPE_SPREADSHEET_EMBED,
                content::TYPE_URL, content::TYPE_URL_IMAGE, content::TYPE_URL_VIDEO, content::TYPE_URL_AUDIO,
                content::TYPE_URL_DOCUMENT, content::TYPE_URL_DOCUMENT_PDF, content::TYPE_URL_DOCUMENT_DOC,
                content::TYPE_URL_PRESENTATION, content::TYPE_URL_PRESENTATION_PPT, content::TYPE_URL_SPREADSHEET,
                content::TYPE_URL_SPREADSHEET_XLS, content::TYPE_CAD, content::TYPE_ZIP);

        if ($search) {
            $searchresultdata = search::query($cm, $search, 0, $pagesize, 0, content::VISIBILITY_MODULE, $filter);
            if (!util::global_search_enabled($cm)) {
                $contentdata = $searchresultdata->result;
            } else {
                // Use global search if activity search enabled.
                if (!empty($searchresultdata->result)) {
                    $contentids = [];
                    foreach ($searchresultdata->result as $searchresult) {
                        $contentids[] = $searchresult->intref1;
                    }
                    if (!empty($contentids)) {
                        // Process content data.
                        $contentdata = stream::get_contents_by_ids(
                                $USER->id, $contentids, $permissions->feature_contentreciprocalaccess);
                    }
                }
            }
        } else {
            $contentdatatemp = stream::get_contents(
                    $cminstance->id, $permissions->groupingid, $userid, $userid, content::VISIBILITY_PRIVATE_PINBOARD,
                    null, implode(',', $filtertypes), null, null, null, null,
                    array('id' => stream::SORT_BY_DATE, 'desc' => stream::SORT_DESC), null, $pagesize, true, true,
                    $permissions->managecontent, 0, $permissions->groupmode,
                    false,
                    $permissions->accessallgroups,
                    false,
                    $permissions->feature_contentreciprocalaccess, $permissions->tutorroles);

            if (isset($contentdatatemp->contents)) {
                $contentdata = $contentdatatemp->contents;
            }
        }

        if ($contentdata) {

            $contentsinfolder = folder::get_contents($folderid);

            $selectedpostsids = array();
            if ($selectedposts) {
                $selectedpostsids = explode(',', $selectedposts);
            }

            foreach ($contentdata as $content) {

                // Check item is folder or added to folder.
                if ($content->contenttype == content::TYPE_FOLDER ||
                        array_key_exists($content->id, $contentsinfolder) ||
                        ($selectedpostsids && in_array($content->id, $selectedpostsids))) {
                    continue;
                }

                // Make sure item belongs to the current user or adding other users' content is allowed
                if ($content->userid != $USER->id && !$permissions->feature_enablefoldersanycontent) {
                    continue;
                }

                if ($content->visibility == content::VISIBILITY_INFOLDERONLY ) {
                    $content->folder = folder::get_containing_folder($content->id);
                }

                $content = renderer_utils::content_type_image($content, $context);

                $visibility = (int)$content->visibility;
                if ($visibility < 0) {
                    $visibility = content::VISIBILITY_GROUP;
                }

                // Set icon for content.
                switch ($visibility) {
                    case content::VISIBILITY_MODULE:
                        $contenticon = $OUTPUT->image_url('mymodule_rgb_32px', 'openstudio');
                        $contentlocation = get_string('contentformvisibilitymodule', 'openstudio');
                        break;

                    case content::VISIBILITY_GROUP:
                    case content::VISIBILITY_ALLGROUPS:
                        $contenticon = $OUTPUT->image_url('share_with_my_group_rgb_32px', 'openstudio');
                        $contentlocation = group::get_name(abs($content->visibility));
                        break;

                    case content::VISIBILITY_WORKSPACE:
                    case content::VISIBILITY_PRIVATE:
                        $contenticon = $OUTPUT->image_url('onlyme_rgb_32px', 'openstudio');
                        $contentlocation = get_string('contentformvisibilityprivate', 'openstudio');
                        break;

                    case content::VISIBILITY_PRIVATE_PINBOARD:
                        $contenticon = $OUTPUT->image_url('onlyme_rgb_32px', 'openstudio');
                        $contentlocation = get_string('contentformvisibilityprivate', 'openstudio');
                        break;

                    case content::VISIBILITY_TUTOR:
                        $contenticon = $OUTPUT->image_url('share_with_tutor_rgb_32px', 'openstudio');
                        $contentlocation = get_string('contentitemsharewithmytutor', 'openstudio');
                        break;
                    case content::VISIBILITY_INFOLDERONLY:
                        $contenticon = $OUTPUT->image_url('folder_darkblue_rgb_32px', 'openstudio');
                        $folder = folder::get_containing_folder($content->id);
                        $contentlocation = $folder->name;
                        break;
                    default:
                        $contenticon = $OUTPUT->image_url('onlyme_rgb_32px', 'openstudio');
                        $contentlocation = get_string('contentformvisibilityprivate', 'openstudio');
                        break;
                }

                $content->contenticon = $contenticon;
                $content->contentlocation = $contentlocation;
                $content->datetime = userdate($content->timemodified, get_string('formattimedatetime', 'openstudio'));
                if ($content->contenttype == content::TYPE_URL) {
                    $content->content = shorten_text($content->content, 30, true);
                } else {
                    if (strlen($content->content) > 30) {
                        $pathinfo = pathinfo($content->content);
                        if (!empty($pathinfo['extension'])) {
                            $extensionlength = strlen($pathinfo['extension']);
                            $content->content = shorten_text($content->content, 30 - $extensionlength, true) .
                                    substr($content->content, $extensionlength * -1);
                        } else {
                            $content->content = shorten_text($content->content, 30, true);
                        }

                    }
                }

                // Process content locking.
                if (($content->levelcontainer > 0) && ($content->userid == $permissions->activeuserid)) {
                    $content = lock::determine_lock_status($content);
                }
                $content->locked = ($content->locktype == lock::ALL);

                // Content feedback requested.
                $content->isfeedbackrequested = false;
                $flagstatus = flags::get_for_content_by_user($content->id, $content->userid);
                if (in_array(flags::NEEDHELP, $flagstatus)) {
                    $content->isfeedbackrequested = true;
                }

                $contents[$content->id] = $content;
            }
        }

        if ($contents) {
            // Returns all the values from the array and indexes the array numerically.
            // We need this because mustache requires it.
            $contents = array_values($contents);
            $total = count($contents);

            $renderer = $PAGE->get_renderer('mod_openstudio');
            $html = $renderer->browse_posts($contents);
        }

        // Get folder limit.
        $folderdata = folder::get($folderid);
        $contentdatatemp = folder::get_contents($folderdata->id);
        $availableposts = renderer_utils::get_limit_add_content_folder($permissions->pinboardfolderlimit,
            $folderdata->id, $folderdata->levelid, count($contentdatatemp));

        $helpicon = $OUTPUT->help_icon('folderselectedpost', 'openstudio');
        $result['helpicon'] = $helpicon;
        $result['html'] = $html;
        $result['total'] = $availableposts;
        $result['foundnumberpostlabel'] = get_string('folderbrowsepostsfound', 'openstudio', array('number' => $total));

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function browse_posts_returns() {
        return new external_single_structure(array(
                'html' => new external_value(PARAM_RAW, 'Browse posts item list template'),
                'foundnumberpostlabel' => new external_value(PARAM_RAW, 'Found number post label'),
                'total' => new external_value(PARAM_INT, 'Total posts'),
                'helpicon' => new external_value(PARAM_RAW, 'Help icon'))
        );
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
     *      fid: int,
     *      accessiblemessage: text
     *  ]
     */
    public static function flag_content($cmid, $cid, $fid, $mode) {
        global $USER, $OUTPUT;

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
        $flagvalue = 0;
        $flagremoveclass = '';
        $flagaddclass = '';
        $flagiconimage = '';
        $mode = trim($params['mode']);

        list($course, $cm) = get_course_and_cm_from_cmid($params['cmid'], 'openstudio');
        // Validate locking status.
        self::validate_locking_status($params['cid'], lock::SOCIAL);

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
            // Update flag and tracking.
            flags::toggle($params['cid'], flags::READ_CONTENT, $mode, $USER->id, $params['cid']);
            tracking::log_action($params['cid'], flags::READ_CONTENT, $USER->id);

            $iscontentflagrequestfeedback = false;
            $total = flags::count_for_content($params['cid'], $params['fid']) + 0;
            $flagvalue = $total;
            $flagaccessiblemessage = '';
            switch ($params['fid']) {
                case flags::FAVOURITE:
                    $flagtext = get_string('contentflagxfavourites', 'openstudio',
                            array('number' => $total));
                    switch ($mode) {
                        case 'on':
                            $flagiconimage = $OUTPUT->image_url('favourite_rgb_32px', 'openstudio')->out(false);
                            $flagaccessiblemessage = get_string('contentclicktounfavourite', 'openstudio');
                        break;
                        case 'off':
                            $flagiconimage = $OUTPUT->image_url('favourite_grey_rgb_32px', 'openstudio')->out(false);
                            $flagaccessiblemessage = get_string('contentclicktofavourite', 'openstudio');
                            break;
                    }
                    break;
                case flags::MADEMELAUGH:
                    $flagtext = get_string('contentflagxsmiles', 'openstudio',
                            array('number' => $total));
                    switch ($mode) {
                        case 'on':
                            $flagiconimage = $OUTPUT->image_url('participation_rgb_32px', 'openstudio')->out(false);
                            $flagaccessiblemessage = get_string('contentclicktounsmile', 'openstudio');
                            break;
                        case 'off':
                            $flagiconimage = $OUTPUT->image_url('participation_grey_rgb_32px', 'openstudio')->out(false);
                            $flagaccessiblemessage = get_string('contentclicktosmile', 'openstudio');
                            break;
                    }
                    break;
                case flags::INSPIREDME:
                    $flagtext = get_string('contentflagxinspired', 'openstudio',
                            array('number' => $total));
                    switch ($mode) {
                        case 'on':
                            $flagiconimage = $OUTPUT->image_url('inspiration_rgb_32px', 'openstudio')->out(false);
                            $flagaccessiblemessage = get_string('contentclicktouninspire', 'openstudio');
                            break;
                        case 'off':
                            $flagiconimage = $OUTPUT->image_url('inspiration_grey_rgb_32px', 'openstudio')->out(false);
                            $flagaccessiblemessage = get_string('contentclicktoinspire', 'openstudio');
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
        $results['flagvalue'] = $flagvalue;
        $results['flagiconimage'] = $flagiconimage;
        $results['flagremoveclass'] = $flagremoveclass;
        $results['flagaddclass'] = $flagaddclass;
        $results['fid'] = $params['fid'];
        $results['iscontentflagrequestfeedback'] = $iscontentflagrequestfeedback;
        $results['accessiblemessage'] = $flagaccessiblemessage;

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
                'flagvalue' => new external_value(PARAM_INT, 'total flag value'),
                'flagiconimage' => new external_value(PARAM_TEXT, 'flag icon image value'),
                'flagremoveclass' => new external_value(PARAM_TEXT, 'flag remove class'),
                'flagaddclass' => new external_value(PARAM_TEXT, 'flag add new class'),
                'fid' => new external_value(PARAM_INT, 'flag ID'),
                'iscontentflagrequestfeedback' => new external_value(PARAM_BOOL, 'is content flag request feedback'),
                'accessiblemessage' => new external_value(PARAM_TEXT, 'Accessible message')
        ));
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function add_comment_parameters() {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'cid' => new external_value(PARAM_INT, 'Content ID'),
                'commenttext' => new external_value(PARAM_RAW, 'Comment text'),
                'commentattachment' => new external_value(PARAM_INT, 'Comment attachment'),
                'inreplyto' => new external_value(PARAM_INT, 'Parent comment ID'),
                'commenttextitemid' => new external_value(PARAM_INT, 'Comment text item ID'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_comments_by_contentid_parameters() {
        return new external_function_parameters(array(
            'cmid' => new external_value(PARAM_INT, 'Course Module ID'),
            'contentid' => new external_value(PARAM_INT, 'Content ID'),
        ));
    }

    /**
     * Add new comment
     *
     * @param int $cmid Course module ID
     * @param int $cid Content ID
     * @param string $commentext Comment text
     * @param string $commentattachment Comment attachment
     * @param int $inreplyto Parent comment ID
     * @param int $commenttextitemid Comment text item ID.
     * @return array
     *  [
     *      commentid: int
     *      commenthtml: string
     *  ]
     * @throws moodle_exception
     */
    public static function add_comment($cmid, $cid, $commenttext = '',
            $commentattachment = 0, $inreplyto = 0, $commenttextitemid = 0) {
        global $USER, $PAGE, $CFG, $DB;
        $userid = $USER->id;

        // Init and check permission.
        $coursedata = util::render_page_init($cmid, array('mod/openstudio:addcomment'));
        $cm = $coursedata->cm;
        $mcontext = $coursedata->mcontext;
        $permissions = $coursedata->permissions;

        // Validate input parameters and context.
        self::validate_context($mcontext);
        $params = self::validate_parameters(self::add_comment_parameters(), [
                'cmid' => $cmid,
                'cid' => $cid,
                'commenttext' => $commenttext,
                'commentattachment' => $commentattachment,
                'inreplyto' => $inreplyto,
                'commenttextitemid' => $commenttextitemid,
        ]);

        // Validate locking status.
        self::validate_locking_status($params['cid'], lock::COMMENT);

        // Check parent comment is existed.
        if ($inreplyto) {
            $parent = $DB->get_record('openstudio_comments', ['id' => $inreplyto], 'id, deletedby');
            if (!$parent || $parent->deletedby > 0) {
                throw new \moodle_exception('errorcommentdeleted', 'openstudio');
            }
        }

        // Check if user has permission to add content.
        $actionallowed = $permissions->addcomment || $permissions->managecontent;
        $flagsenabled = explode(',', $permissions->flags);
        $successcreated = false;

        if ($actionallowed) {
            $transaction = $DB->start_delegated_transaction();
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
                        ['id' => $params['commentattachment']], $context, $inreplyto, $cm, $params['commenttextitemid']);
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
                    $commentdata->donotexport = true;
                    $commentdata->timemodified = userdate($commentdata->timemodified,
                            get_string('formattimedatetime', 'openstudio'));

                    $user = user::get_user_by_id($commentdata->userid);
                    $commentdata->fullname = fullname($user);

                    // User picture.
                    $renderer = util::get_renderer();
                    $commentdata->userpicturehtml = util::render_user_avatar($renderer, $user);

                    // Check comment attachment.
                    if ($file = comments::get_attachment($commentdata->id)) {
                        $commentdata->commenttext .= renderer_utils::get_media_filter_markup($file);
                    }

                    // Filter comment text.
                    $commentdata->commenttext = comments::filter_comment_text($commentdata->commenttext, $commentid, $context);

                    $commentdata->deleteenable = true;
                    $commentdata->reportenable = false;

                    // Check comment like setting enabled.
                    $commentdata->contentcommentlikeenabled = in_array(flags::COMMENT_LIKE, $flagsenabled);

                    $commenthtml = $renderer->content_comment($commentdata);

                    $transaction->allow_commit();

                    $successcreated = true;
                } else {
                    // Comment empty error.
                    $transaction->rollback(new \moodle_exception('emptycomment', 'openstudio'));
                }
            } catch (Exception $e) {
                // Database error.
                $transaction->rollback($e);
            }
        } else {
            // No permision.
            throw new \moodle_exception('nocommentpermissions', 'openstudio');
        }

        // The lib/completionlib.php - internal_set_data used its own transaction.
        if ($successcreated) {
            custom_completion::update_completion($cm, $userid, COMPLETION_COMPLETE);
        }

        return [
            'commentid' => $commentid,
            'commenthtml' => $commenthtml
        ];
    }

    /**
     * Get list of comment by contentid.
     *
     * @param $cmid int course module id of course.
     * @param $contentid int content id of course
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_comments_by_contentid($cmid, $contentid) {
        $coursedata = util::render_page_init($cmid, array('mod/openstudio:view'));
        $mcontext = $coursedata->mcontext;
        $permissions = $coursedata->permissions;

        // Validate input parameters and context.
        self::validate_context($mcontext);
        self::validate_parameters(self::get_comments_by_contentid_parameters(), array(
            'cmid' => $cmid,
            'contentid' => $contentid,
        ));
        $listofcomment = [];
        if ($permissions) {
            $listofcomment = comments::get_comments_by_contentid($cmid, $contentid, $permissions);
        }
        return [
            'comments' => $listofcomment
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function get_comments_by_contentid_returns() {
        return new external_single_structure(
            array(
                'comments' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'The id of comment'),
                            'comment' => new external_value(PARAM_RAW, 'The comment of content'),
                            'contentid' => new external_value(PARAM_INT, 'The contentid of content'),
                            'userid' => new external_value(PARAM_INT, 'The userid of user'),
                            'userpicturehtml' => new external_value(PARAM_RAW, 'The userpicture of user'),
                            'fullname' => new external_value(PARAM_RAW, 'The fullname of user'),
                            'isnewcomment' => new external_value(PARAM_BOOL, 'The flag to check comment is new'),
                            'commenturl' => new external_value(PARAM_RAW, 'The comment url of user'),
                            'timemodified' => new external_value(PARAM_RAW, 'The comment of content'),
                        )
                    ),
                    'Comments data'
               )
            )
        );
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
                'fid' => new external_value(PARAM_INT, 'Flag ID')
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
        self::validate_locking_status($params['cid'], lock::COMMENT);

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
        global $USER, $DB;
        $userid = $USER->id;

        // Validate input parameters.
        $params = self::validate_parameters(self::delete_comment_parameters(), array(
                'cmid' => $cmid,
                'commentid' => $commentid));

        // Init and check permission.
        $coursedata = util::render_page_init($params['cmid'], array('mod/openstudio:addcomment'));
        $cm = $coursedata->cm;
        $permissions = $coursedata->permissions;

        $comment = comments::get($params['commentid']);
        $actionallowed = ($permissions->addcomment && $comment->userid == $USER->id)
                || $permissions->managecontent;

        // Validate locking status.
        self::validate_locking_status($comment->contentid, lock::COMMENT);

        if ($actionallowed) {
            try {
                if ($comment) {
                    $allreplyusers = !$comment->inreplyto
                            ? comments::get_all_users_from_root_comment_id($params['commentid'], COMPLETION_UNKNOWN)
                            : [];
                    $success = comments::delete($params['commentid'], $userid);
                    notifications::delete_unread_for_comment($params['commentid']);
                    if (!$success) {
                        throw new \moodle_exception('commenterror', 'openstudio');
                    }
                    custom_completion::update_completion($cm, $comment->userid, COMPLETION_INCOMPLETE, $allreplyusers);
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
                'containingfolder' => new external_value(PARAM_INT, 'Folder ID'))
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

        if (!$contentdata || $contentdata->deletedtime > 0) {
            throw new moodle_exception('errorinvalidcontent', 'openstudio');
        }

        // Delete content.
        $actionallowed = ($contentdata->userid == $userid) && $permissions->addcontent;
        $actionallowed = $actionallowed || $permissions->managecontent;
        if ($actionallowed) {
            if ($params['containingfolder']) { // This is a content in folder.
                if ($contentdata) {
                    $provenance = folder::get_content_provenance($params['containingfolder'], $contentdata->id);
                    $success = folder::remove_content($params['containingfolder'], $contentdata->id, $userid);
                    if (empty($provenance->provenanceid) || $provenance->provenanceid != $contentdata->id) {
                        // If this isn't just a soft link, empty the actual slot as well.
                        content::empty_content($userid, $contentdata->id, true, $cminstance->versioning, $cm);
                    }
                }
            } else {
                if ($contentdata->contenttype == content::TYPE_FOLDER) {
                    // If this is folder, remove contents inside it.
                    $removedslots = folder::remove_contents($contentdata->id, $userid);
                }

                if ($contentdata->levelid > 0 && $contentdata->levelcontainer > 0 && $permissions->versioningon) {
                    // If content is an activity, archive it.
                    $success = content::delete($userid, $params['cid'], $cminstance->versioning, $cm);
                } else {
                    // If content is not an activity, delete it completely.
                    $success = content::empty_content($userid, $params['cid'], true, $cminstance->versioning, $cm);
                }

                // If content is linked to other ones, then make copies from this.
                if ($contentdata->contenttype != content::TYPE_FOLDER) {
                    $copies = folder::get_content_softlinks($contentdata->id);
                    if (!empty($copies)) {
                        foreach ($copies as $copy) {
                            $copy->provenancestatus = folder::PROVENANCE_UNLINKED;
                            $newslot = folder::copy_content($contentdata->id, $userid, null, null, $cm);
                            $copy->contentid = $newslot->id;
                            folder::update_content($copy);
                        }
                    }
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

        util::trigger_event($cm->id, 'content_deleted', null, "view.php?id={$cm->id}", $contentdata->name);

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

    /**
     * Parameters for restore_content_in_folder
     *
     * @return external_function_parameters
     */
    public static function restore_content_in_folder_parameters() {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'cvid' => new external_value(PARAM_INT, 'Content version ID'),
                'folderid' => new external_value(PARAM_INT, 'Containing folder ID')
        ]);
    }

    /**
     * Restore deleted content in folder.
     *
     * @param int $cmid Course module ID
     * @param int $cvid Content version ID
     * @param int $folderid Containing folder ID
     * @return bool
     * @throws moodle_exception
     */
    public static function restore_content_in_folder($cmid, $cvid, $folderid) {
        $params = self::validate_parameters(self::restore_content_in_folder_parameters(), [
            'cmid' => $cmid,
            'cvid' => $cvid,
            'folderid' => $folderid
        ]);
        $context = context_module::instance($params['cmid']);
        external_api::validate_context($context);

        global $USER;
        $userid = $USER->id;
        $coursedata = util::render_page_init($params['cmid']);
        $permissions = $coursedata->permissions;

        $contetversiondata = contentversion::get($params['cvid'], $userid, $coursedata->cm);
        $contentdata = null;
        if ($contetversiondata != false) {
            $contentdata = content::get($contetversiondata->contentid);
        }

        if ($contetversiondata && $contentdata) {
            $actionallowed = ($permissions->viewdeleted && $contentdata->userid == $userid)
                || $permissions->managecontent;
            if ($actionallowed) {
                if (content::undelete_in_folder($contentdata->userid, $contentdata, $contetversiondata->id,
                        $params['folderid'], $coursedata->cm) === false) {
                    throw new moodle_exception('errorcontentrestoreinfolder', 'openstudio');
                }
            }
        } else {
            throw new moodle_exception('errorinvalidcontent', 'openstudio');
        }

        return true;
    }

    /**
     * Return values for restore_content_in_folder.
     *
     * @return external_value
     */
    public static function restore_content_in_folder_returns() {
        return new external_value(PARAM_BOOL, 'Success');
    }

    /**
     * Parameters for fetch_deleted_posts_in_folder
     *
     * @return external_function_parameters
     */
    public static function fetch_deleted_posts_in_folder_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'folderid' => new external_value(PARAM_INT, 'Folder ID')
        ]);
    }

    /**
     * Fetch deleted contents in folder and return html markup.
     *
     * @param int $cmid Course module ID
     * @param int $folderid Folder ID
     * @return string
     * @throws moodle_exception
     */
    public static function fetch_deleted_posts_in_folder($cmid, $folderid) {
        $params = self::validate_parameters(self::fetch_deleted_posts_in_folder_parameters(), [
            'cmid' => $cmid,
            'folderid' => $folderid
        ]);
        $context = context_module::instance($params['cmid']);
        external_api::validate_context($context);

        global $USER, $PAGE;
        $userid = $USER->id;
        $coursedata = util::render_page_init($params['cmid']);
        $permissions = $coursedata->permissions;

        $contentdata = folder::get($params['folderid']);

        if ($contentdata) {
            $actionallowed = ($permissions->viewdeleted && $contentdata->userid == $userid)
                || $permissions->managecontent;
            if ($actionallowed) {
                $deletedposttemp = folder::get_deleted_contents($contentdata->id);
                $deletedposts = [];
                foreach ($deletedposttemp as $post) {
                    $post = renderer_utils::content_type_image($post, $context, true);

                    $deletedposts[] = (object)array(
                        'pictureurl' => (string) $post->contenttypeimage,
                        'name' => $post->name,
                        'date' => $post->deletedtime ? userdate($post->deletedtime, get_string('formattimedatetime', 'openstudio')) : '',
                        'id' => $post->id
                    );
                }

                $renderer = $PAGE->get_renderer('mod_openstudio');
                $html = $renderer->view_deleted_posts($deletedposts);
                return $html;
            } else {
                throw new \moodle_exception('errornopermissiontoviewdeleted', 'openstudio');
            }
        } else {
            throw new moodle_exception('errorinvalidcontent', 'openstudio');
        }
    }

    /**
     * Return values for restore_content_in_folder.
     *
     * @return external_value
     */
    public static function fetch_deleted_posts_in_folder_returns() {
        return new external_value(PARAM_RAW, 'Deleted posts with html format');
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function order_posts_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'folderid' => new external_value(PARAM_INT, 'Folder ID'),
                'listorderporst' => new external_value(PARAM_TEXT, 'List order Posts Content'))
        );
    }

    /**
     * Order Posts content of folder
     *
     * @param int $folderid The open studio folder id
     * @param string $listordercontentid list of content id
     * @return array
     *  [
     *     success: boolean
     *  ]
     */
    public static function order_posts($cmid, $folderid, $listorderporst) {
        $result = [];
        $params = self::validate_parameters(self::order_posts_parameters(), array(
                'cmid' => $cmid,
                'folderid' => $folderid,
                'listorderporst' => $listorderporst));
        // Checks to ensure that the user is allowed to perform the requested operation.
        $context = context_module::instance($params['cmid']);
        external_api::validate_context($context);

        // Get list content.
        $orderlist = explode(",", $params['listorderporst']);
        $neworderlist = array();
        foreach ($orderlist as $key => $value) {
            list($index, $order) = explode('-', $value);
            $neworderlist[$index] = $order;
        }

        // Update content position.
        $success = folder::update_contentorders($params['folderid'], $neworderlist);
        $result['success'] = $success;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function order_posts_returns() {
        return new external_single_structure(array(
                'success' => new external_value(PARAM_BOOL, 'Save order posts success'))
        );
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_order_posts_content_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'folderid' => new external_value(PARAM_INT, 'Folder ID'))
        );
    }

    /**
     * Get Order Posts content of folder
     *
     * @param int $cmid Course module ID
     * @param int $folderid The open studio folder id
     * @return array
     *  [
     *     result: object
     *  ]
     */
    public static function get_order_posts_content($cmid, $folderid) {
        global $PAGE;
        $result = [];
        $params = self::validate_parameters(self::get_order_posts_content_parameters(), array(
                'cmid' => $cmid,
                'folderid' => $folderid));
        // Checks to ensure that the user is allowed to perform the requested operation.
        $context = context_module::instance($params['cmid']);
        external_api::validate_context($context);
        $contentemp = renderer_utils::get_all_folder_content($params['folderid']);
        $listcontent = renderer_utils::get_order_post_content($params['cmid'], $contentemp);
        $renderer = $PAGE->get_renderer('mod_openstudio');
        $result = array();
        $success = false;
        $html = '';
        if ($listcontent) {
            $success = true;
            $html = $renderer->order_posts($listcontent);
        }
        $result['success'] = $success;
        $result['html'] = $html;

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_order_posts_content_returns() {
        return new external_single_structure(array(
                'success' => new external_value(PARAM_BOOL, 'Get order posts success'),
                'html' => new external_value(PARAM_RAW, 'Order posts item list template'))
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function unlock_override_activity_parameters() {
        return new external_function_parameters(array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'level3id' => new external_value(PARAM_INT, 'Level 3 ID'),
                'vuid' => new external_value(PARAM_INT, 'View user ID'))
        );
    }

    /**
     * Unlock override activity. If An activity is locked and it's content has never been created.
     * Then unlocking will create an new empty for specific user's activity.
     *
     * @param int $cmid Course module ID
     * @param int $level3id
     * @param int $vuid View user ID
     * @return bool Successfull or not
     * @throws moodle_exception
     */
    public static function unlock_override_activity($cmid, $level3id, $vuid) {

        $params = self::validate_parameters(self::unlock_override_activity_parameters(), array(
                'cmid' => $cmid,
                'level3id' => $level3id,
                'vuid' => $vuid));
        $context = context_module::instance($params['cmid']);
        self::validate_context($context);

        global $USER;
        $success = false;
        $coursedata = util::render_page_init($params['cmid']);
        $permissions = $coursedata->permissions;
        $cminstance = $coursedata->cminstance;

        if (!$permissions->managecontent && $permissions->canlockothers) {
            throw new \moodle_exception('errornopermissiontounlockactivity', 'openstudio');
        }

        $contentcheck = content::get_record_via_levels(
                $permissions->activecminstanceid, $params['vuid'], 3, $params['level3id']);
        if ($contentcheck === false) {
            $contentcreatedid = false;
            $contentlevel = levels::get_record(3, $params['level3id']);
            if ($contentlevel !== false) {
                if ($contentlevel->contenttype == STUDIO_CONTENTTYPE_SET) {
                    $contentcreatedid = content::create(
                            $studioid = $permissions->activecminstanceid,
                            $userid = $params['vuid'],
                            $level = 3,
                            $levelid = $params['level3id'],
                            $data = array('contenttype' => content::TYPE_FOLDER,
                                    'visibility' => $cminstance->defaultvisibility,
                                    'embedcode' => '', 'urltitle' => '', 'weblink' => '',
                                    'name' => '', 'description' => ''));
                } else {
                    $contentcreatedid = content::create(
                            $studioid = $permissions->activecminstanceid,
                            $userid = $params['vuid'],
                            $level = 3,
                            $levelid = $params['level3id'],
                            $data = array('contenttype' => content::TYPE_NONE,
                                    'visibility' => $cminstance->defaultvisibility,
                                    'embedcode' => '', 'urltitle' => '', 'weblink' => '',
                                    'name' => '', 'description' => ''));
                }
            }
            if ($contentcreatedid !== false) {
                $success = lock::lock_content($USER->id, $contentcreatedid, lock::NONE);
            }
        }

        if (!$success) {
            throw new moodle_exception('errorcontentunlock', 'openstudio');
        }

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unlock_override_activity_returns() {
        return new external_value(PARAM_BOOL, 'Unlock override successfully');
    }
}
