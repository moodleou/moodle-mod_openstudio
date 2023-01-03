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
 * Miscellaneous utility functions
 *
 * @package mod_openstudio
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local;

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\contentversion;
use mod_openstudio\local\api\comments;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\api\item;
use mod_openstudio\local\api\tags;
use mod_openstudio\local\util\defaults;

defined('MOODLE_INTERNAL') || die();

class util {

    private static $cacheseed;

    /**
     * Check if user has accepted terms and conditions for usng Studio.
     *
     * @param int $studioid Module instance id.
     * @return bool Return true or redirect user to terms and conditions page
     */
    public static function honesty_check($studioid) {
        global $USER;

        $result = api\honesty::get($studioid, $USER->id);
        if ($result || !get_config('openstudio', 'honestycheckrequired')) {
            return true;
        }

        $url = new \moodle_url('/mod/openstudio/honesty.php', array('id' => $studioid));
        \redirect($url->out(false));
        return false;
    }

    /**
     * This is a helper functiont to retrieve a colelction of data relevant to the
     * current page such as:
     *   course module record
     *   course module instance record
     *   course record
     *   module context record
     *   permissions record - calculated for the logged in user
     *
     * The helper function also checks for required capabilities if requested.
     *
     * @param int $id Module instance id.
     * @param array $requiredcapabilities List of capabilities to check for as being required.
     * @return object Return a collection of data about the current page request.
     */
    public static function render_page_init($id, $requiredcapabilities = array()) {
        global $DB;

        if (! ($cm = get_coursemodule_from_id('openstudio', $id))) {
            \print_error('invalidcoursemodule');
        }
        // Get course instance.
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        // NOTE: the additional third $cm parameter, automatically forces the
        // appearance of the LHS navigation bar.
        \require_login($course, true, $cm);

        // Get module context.
        $modulecontext = \context_module::instance($cm->id);

        // Security checks.
        foreach ($requiredcapabilities as $requiredcapability) {
            \require_capability($requiredcapability, $modulecontext);
        }

        // Get module instance.
        $instance = $DB->get_record('openstudio', array('id' => $cm->instance), '*', MUST_EXIST);

        // Get couse context.
        $permissions = self::check_permission($cm, $instance, $course);
        $permissions->pinboardfolderlimit = $instance->pinboardfolderlimit;

        $theme = (object) array(
                'homedefault' => $instance->themehomedefault,
                'sitename' => $instance->sitename,
                'pinboardname' => empty($instance->pinboardname) ? get_string('pinboard', 'openstudio') : $instance->pinboardname,
                'level1name' => empty($instance->level1name) ? get_string('block', 'openstudio') : $instance->level1name,
                'level2name' => empty($instance->level2name) ? get_string('activity', 'openstudio') : $instance->level2name,
                'level3name' => empty($instance->level3name) ? get_string('content', 'openstudio') : $instance->level3name
        );
        if (empty($instance->thememodulename)) {
            $theme->thememodulename = get_string('settingsthemehomesettingsmodule', 'openstudio');
        } else {
            $theme->thememodulename = $instance->thememodulename;
        }
        if (empty($instance->themegroupname)) {
            $theme->themegroupname = get_string('settingsthemehomesettingsgroup', 'openstudio');
        } else {
            $theme->themegroupname = $instance->themegroupname;
        }
        if (empty($instance->themestudioname)) {
            $theme->themestudioname = get_string('settingsthemehomesettingsstudio', 'openstudio');
        } else {
            $theme->themestudioname = $instance->themestudioname;
        }
        if (empty($instance->themepinboardname)) {
            $theme->themepinboardname = get_string('settingsthemehomesettingspinboard', 'openstudio');
        } else {
            $theme->themepinboardname = $instance->themepinboardname;
        }
        if (empty($instance->themehelplink)) {
            $theme->helplink = (new \moodle_url(get_docs_url('openstudio')))->out(false);
        } else {
            $theme->helplink = $instance->themehelplink;
        }
        if (empty($instance->themehelpname)) {
            $theme->helpname = (new \moodle_url(get_docs_url('openstudio')))->out(false);
        } else {
            $theme->helpname = get_string('helplink', 'openstudio');
        }

        // Init javascript.
        self::init_js();

        return (object) array(
                'cm' => $cm,
                'cminstance' => $instance,
                'course' => $course,
                'mcontext' => $modulecontext,
                'permissions' => $permissions,
                'theme' => $theme);
    }

    public static function check_permission($cm, $cminstance, $course, $userid = null) {
        global $DB, $USER;
        if (is_null($userid)) {
            $userid = $USER->id;
        }
        $modulecontext = \context_module::instance($cm->id);
        $coursecontext = \context_course::instance($course->id);
        // Gather permissions.
        $permissions = (object) array(
                'managecontent' => has_capability('mod/openstudio:managecontent', $modulecontext, $userid),
                'viewdeleted' => has_capability('mod/openstudio:viewdeleted', $modulecontext, $userid),
                'expertcommenter' => has_capability('mod/openstudio:expertcommenter', $modulecontext, $userid),
                'viewparticipation' => has_capability('mod/openstudio:viewparticipation', $modulecontext, $userid),
                'view' => has_capability('mod/openstudio:view', $modulecontext, $userid),
                'viewothers' => has_capability('mod/openstudio:viewothers', $modulecontext, $userid),
                'addcontent' => has_capability('mod/openstudio:addcontent', $modulecontext, $userid),
                'addcomment' => has_capability('mod/openstudio:addcomment', $modulecontext, $userid),
                'sharewithothers' => has_capability('mod/openstudio:sharewithothers', $modulecontext, $userid),
                'import' => has_capability('mod/openstudio:import', $modulecontext, $userid),
                'export' => has_capability('mod/openstudio:export', $modulecontext, $userid),
                'canlock' => has_capability('mod/openstudio:canlock', $modulecontext, $userid),
                'canlockothers' => has_capability('mod/openstudio:canlockothers', $modulecontext, $userid),
                'ignoreenrolment' => self::is_ignore_enrol($modulecontext),
        );

        // Note: it is assumed that if a userid can addcomment, they can also set flags such as smile, favourite, etc.
        $permissions->addflags = $permissions->addcomment;
        $permissions->addinstance = has_capability('mod/openstudio:addinstance', $coursecontext, $userid);
        $permissions->managelevels = has_capability('mod/openstudio:managelevels', $coursecontext, $userid);
        $permissions->accessallgroups = has_capability('moodle/site:accessallgroups', $coursecontext, $userid);
        // Get the configured tutor roles.
        $permissions->tutorroles = array_filter(explode(',', $cminstance->tutorroles));
        // Does the user have any of the tutor roles on the course? E.g. for displaying "Shared with tutor" filter.
        if (empty($permissions->tutorroles)) {
            $permissions->istutor = false;
        } else {
            list($tutorsql, $tutorparams) = $DB->get_in_or_equal($permissions->tutorroles);
            $tutorwhere = 'roleid ' . $tutorsql . ' AND userid = ? AND contextid = ?';
            $tutorparams[] = $userid;
            $tutorparams[] = $coursecontext->id;
            $permissions->istutor = $DB->record_exists_select('role_assignments', $tutorwhere, $tutorparams);
        }
        $permissions->activeuserid = $userid;
        $permissions->activecid = $course->id;
        $permissions->activecmid = $cm->id;
        $permissions->activecminstanceid = $cminstance->id;
        $permissions->activecmcontextid = $modulecontext->id;
        $permissions->activeenrollment = is_enrolled($coursecontext, $permissions->activeuserid) ||
                                            $permissions->ignoreenrolment;
        $permissions->coursecontext = $coursecontext;

        $permissions->flags = $cminstance->flags;
        $permissions->filetypes = $cminstance->filetypes;

        $permissions->versioningon = ($cminstance->versioning > 0) ? true : false;
        $permissions->copyingon = ($cminstance->copying == 0) ? false : true;

        $permissions->feature_pinboard = ($cminstance->pinboard > 0) ? true : false;
        $permissions->pinboarddata = api\content::get_pinboard_total($cminstance->id, $userid);
        $permissions->feature_pinboard = $permissions->feature_pinboard ||
                (($permissions->pinboarddata->usedandempty > 0) ? true : false);

        $permissions->activitydata = api\content::get_total($cminstance->id, $userid);
        $permissions->feature_studio = ($permissions->activitydata->total > 0) ? true : false;
        if (!$permissions->feature_studio) {
            $permissions->feature_studio = ($permissions->activitydata->used > 0) ? true : false;
        }
        $permissions->feature_studio = $permissions->feature_studio
                || (($cminstance->themefeatures & util\feature::STUDIO) ? true : false);

        $permissions->feature_group = false;
        $permissions->groupingid = 0;
        $permissions->groupmode = 0;
        if (($cm->groupmode > 0) && ($cm->groupingid > 0)) {
            $permissions->feature_group = true;
            $permissions->groupingid = $cm->groupingid;
            $permissions->groupmode  = $cm->groupmode;
        }
        // If a user has accessallgroups, then for this user it is the same
        // as groupmode is on and set to visible all groups.
        if ($permissions->accessallgroups && ($cm->groupingid > 0)) {
            $permissions->feature_group = true;
            $permissions->groupingid = $cm->groupingid;
            $permissions->groupmode = 2;
        }

        $permissions->feature_tutor = in_array(content::VISIBILITY_TUTOR, explode(',', $cminstance->allowedvisibility));

        $permissions->feature_module = self::has_feature($cminstance, util\feature::MODULE);

        // Detemine studio features that have been turned on.
        $permissions->feature_contentcommentusesaudio = self::has_feature($cminstance, util\feature::CONTENTCOMMENTUSESAUDIO);
        $permissions->feature_contentusesfileupload = self::has_feature($cminstance, util\feature::CONTENTUSESFILEUPLOAD);
        $permissions->feature_enablefolders = self::has_feature($cminstance, util\feature::ENABLEFOLDERS);
        $permissions->feature_enablefoldersanycontent = self::has_feature($cminstance, util\feature::ENABLEFOLDERSANYCONTENT);
        $permissions->feature_enablerss = self::has_feature($cminstance, util\feature::ENABLERSS);
        // Feature Enable Subscription is obsolete, permission default is true.
        $permissions->feature_enablesubscription = true;
        $permissions->feature_enableexportimport = self::has_feature($cminstance, util\feature::ENABLEEXPORTIMPORT);
        $permissions->feature_contentusesweblink = self::has_feature($cminstance, util\feature::CONTENTUSESWEBLINK);
        $permissions->feature_contentusesembedcode = self::has_feature($cminstance, util\feature::CONTENTUSESEMBEDCODE);
        $permissions->feature_contentallownotebooks = self::has_feature($cminstance, util\feature::CONTENTALLOWNOTEBOOKS);
        $permissions->feature_contentreciprocalaccess = self::has_feature($cminstance, util\feature::CONTENTRECIPROCALACCESS);
        $permissions->feature_participationsmiley = self::has_feature($cminstance, util\feature::PARTICIPATIONSMILEY);
        $permissions->feature_enablelock = self::has_feature($cminstance, util\feature::ENABLELOCK);
        $permissions->feature_allowlatesubmissions = self::has_feature($cminstance, util\feature::LATESUBMISSIONS);
        if ($permissions->managecontent) {
            $permissions->feature_contentreciprocalaccess = false;
        }
        $permissions->allow_visibilty_modes = array();
        if ($permissions->feature_module) {
            $permissions->allow_visibilty_modes[] = content::VISIBILITY_MODULE;
        }
        if ($permissions->feature_group || $permissions->feature_tutor) {
            $permissions->allow_visibilty_modes[] = content::VISIBILITY_GROUP;
        }
        if ($permissions->feature_studio) {
            $permissions->allow_visibilty_modes[] = content::VISIBILITY_PRIVATE;
        }
        if ($permissions->feature_pinboard) {
            $permissions->allow_visibilty_modes[] = content::VISIBILITY_PRIVATE_PINBOARD;
        }
        if (empty($permissions->allow_visibilty_modes)) {
            // If no visibility mode is set, we force the module view to be on.
            $permissions->feature_module = true;
            $permissions->allow_visibilty_modes[] = content::VISIBILITY_MODULE;
        }

        return $permissions;
    }

    /**
     * Returns whether a particular feature is enabled for this studio instance.
     *
     * @param object $studio The OpenStudio instance.
     * @param int $feature The util\feature::* constant.
     * @return bool True if the feature flag is enabled, false otherwise.
     */
    public static function has_feature($studio, $feature) {
        return (bool) ($studio->themefeatures & $feature);
    }

    /**
     * Helper function to set up Moodle $PAGE variables.
     *
     * Any Studio page that renders UI pages will call this function.
     *
     * @param \moodle_page $PAGE Moodle $PAGE object.
     * @param string $title Title of the page.
     * @param string $heading Heading for the page.
     * @param string $url Page URL.
     * @param object $course Course record.
     * @param object $cm Course module record.
     * @param string $view View mode: admin pages or studio workspace pages.
     */
    public static function page_setup(
            \moodle_page $PAGE, $title, $heading, $url, $course, $cm, $view = 'work') {

        $PAGE->set_cm($cm, $course);
        $PAGE->set_title($title);
        $PAGE->set_heading($heading);
        $PAGE->set_url($url . '&');

        // If admin pages, the page is rendered like a standard Moodle module with a left-hand navigation bar.
        // Main studio workspace pages takes the full-page width with no sidebars.
        $PAGE->set_pagelayout('standard');
    }

    /**
     * Helper fucntion to render page crumb trail for Studio pages.
     *
     * @param \moodle_page $PAGE Moodle $PAGE object.
     * @param int $parentcrumbid
     * @param string $parentcrumbtype
     * @param array $crumbs
     */
    public static function add_breadcrumb(
            \moodle_page $PAGE, $parentcrumbid, $parentcrumbtype, $crumbs) {

        $navbaradditions = $PAGE->navigation->find($parentcrumbid, $parentcrumbtype);
        foreach ($crumbs as $title => $url) {
            $navbaradditions = $navbaradditions->add($title, $url);
        }
        $navbaradditions->make_active();
    }

    /**
     * Helper function to render date consistently on Studio pages.
     *
     * @param int $date Date to render.
     * @param bool $longformat Request short or long date format.
     * @return string Returns formatted date.
     */
    public static function format_date($date, $longformat = false) {
        if ($date == 'unknown') {
            return get_string('unknown', 'openstudio');
        }

        if ($longformat) {
            $dateformat = 'strftimedaydatetime';
        } else {
            $dateformat = 'strftimedatetime';
        }

        return userdate($date, get_string($dateformat, 'langconfig'));
    }

    /**
     * Helper function to render user friendly date.
     *
     * @param int $datevalue Date value in unix epoch format.
     * @param bool $includetime Inlcude time component.
     * @return string Return date for didsplay.
     */
    public static function format_friendly_date($datevalue, $includetime = false) {
        $today = strtotime(date('Y-m-d'));
        $yesterday = strtotime(date('Y-m-d', strtotime('yesterday')));
        $lastweek = strtotime(date('Y-m-d', strtotime('-6 days')));
        $time = '';
        if ($includetime) {
            $time = date('H:i', $datevalue);
        }

        if (empty($datevalue)) {
            $datevalue = $today;
        }

        if ($datevalue >= $today ) {
            return get_string('dateformattoday', 'openstudio') . " {$time}";
        }

        if ($datevalue >= $yesterday ) {
            return get_string('dateformatyesterday', 'openstudio') . " {$time}";
        }

        if ($datevalue > $lastweek ) {
            if (date('Y', $today) == date('Y', $datevalue)) {
                $daysago = date('z', $today) - date('z', $datevalue);
                return get_string('dateformatdaysago', 'openstudio', array('daysago' => $daysago));
            }
        }

        if ($includetime) {
            return date('j M Y H:i', $datevalue);
        } else {
            return date('j M Y', $datevalue);
        }
    }

    /**
     * Helper function to render user avatar picture consistently.
     *
     * @param \mod_openstudio_renderer $renderer Moodle renderer class instance.
     * @param object $user Moodle user record.
     * @param int $size Size of avatar in pixels.
     * @param string $classname Class name.
     * @return string Return HTML that renders user avatar.
     */
    public static function render_user_avatar(
            \mod_openstudio_renderer $renderer, $user, $size = 16, $classname = '') {
        $context = \context_user::instance($user->userid, IGNORE_MISSING);
        if ($context) {
            $user->contextid = $context->id;
        }

        return $renderer->user_picture($user,
                array('class' => $classname, 'size' => (int) $size, 'link' => false));
    }

    /**
     * Helper function to check if a user can read a slot.
     *
     * @param object $studio Module instance for feature checks.
     * @param object $permissions Permission obejct that cotains users permission cnotext.
     * @param object $content Slot to check permissions against.
     * @param int $folderid Optional folder id - folder permissions take precedence.
     * @return bool Return true if user can read slot.
     */
    public static function can_read_content($studio, $permissions, $content, $folderid = 0) {
        global $DB;
        // If the slot is shared with the group, check permissions.
        if (($content->visibility == content::VISIBILITY_GROUP && $permissions->groupmode != NOGROUPS) || ($content->visibility < 0)) {
            if (!$permissions->accessallgroups) {
                $ismember = api\group::is_content_group_member(
                        $permissions->groupmode,
                        $content->visibility,
                        $permissions->groupingid,
                        $content->userid, $permissions->activeuserid);
                if (!$ismember) {
                    return false;
                }
            }
        }

        // If I have managecontent capability, then I can read any slot.
        if ($permissions->managecontent) {
            return true;
        }
        // If the slot is deleted, then neeed viewdeleted capability.
        if (trim($content->deletedby) != '') {
            if (!$permissions->viewdeleted) {
                return false;
            }
        }

        // If it is a set slot, then the set permission has priority, so check the set instead of slot.
        if ($folderid > 0) {
            $contentold = $content;
            // Check folder exists.
            $content = api\folder::get($folderid);
            if ($content == false) {
                return false;
            }
            // Check folder content exists.
            $foldercontentexists = api\folder::get_content($folderid, $contentold->id);
            if (!$foldercontentexists) {
                return false;
            }
        }
        // If the slot is deleted, then neeed viewdeleted capability.
        if (trim($content->deletedby) != '') {
            if (!$permissions->viewdeleted) {
                return false;
            }
        }

        $isslotowndbyuser = ($content->userid == $permissions->activeuserid) ? true : false;

        // If the slot belongs to the user, then just need view slot capability.
        if ($isslotowndbyuser) {
            if ($permissions->view) {
                return true;
            } else {
                return false;
            }
        }

        // Given the content does NOT belong to the user, then need viewothers capability.
        if (!$permissions->viewothers) {
            return false;
        }

        // If reciprocal access restriction is on, then we only show studio work contents
        // belonging to other users if the current user has created the same content themselves.
        if (self::has_feature($studio, util\feature::CONTENTRECIPROCALACCESS)) {
            if (($content->levelcontainer > 0) && ($content->levelid > 0)) {
                $contentreciprocalaccessqsl = <<<EOF
SELECT DISTINCT 1
  FROM {openstudio_contents} s
 WHERE s.openstudioid = ?
   AND s.levelcontainer = ?
   AND s.levelid = ?
   AND s.userid = ?

EOF;

                $params = array(
                        $content->openstudioid,
                        $content->levelcontainer,
                        $content->levelid,
                        $permissions->activeuserid
                );

                $result = $DB->record_exists_sql($contentreciprocalaccessqsl, $params);
                if ($result === false) {
                    return false;
                }
            }
        }

        // If the slot is shared with the module/group, check permissions.
        if ($content->visibility == content::VISIBILITY_MODULE) {
            return api\group::has_same_course(
                    $permissions->activecid,
                    $content->userid,
                    $permissions->activeuserid) || $permissions->ignoreenrolment;
        } else if (($content->visibility == content::VISIBILITY_GROUP) || ($content->visibility < 0)) {
            if ($permissions->accessallgroups) {
                return true;
            }
            $cm = self::get_coursemodule_from_studioid($studio->id);
            if ($cm->groupmode == VISIBLEGROUPS) {
                return true;
            }
            return api\group::is_content_group_member(
                    $permissions->groupmode,
                    $content->visibility,
                    $permissions->groupingid,
                    $content->userid, $permissions->activeuserid);
        } else if ($content->visibility == content::VISIBILITY_TUTOR) {
            return api\content::user_is_tutor($content->id, $permissions->activeuserid, $permissions->tutorroles);
        }

        return false;
    }

    /**
     * Helper function that adds additional data to a slot record.
     * Optionally, tag data associated with slot can also be added.
     *
     * @param object $contentdata Original slot data record.
     * @param bool $gettags Will inlcude tags assocaited with slot if flag is set to true.
     * @param bool $getversions No = 0, Yes = 1, Yes (include deleted versions) = 2.
     * @return object Returned enriched slot data.
     */
    public static function add_additional_content_data($contentdata, $gettags = false, $getversions = 1) {
        switch ($contentdata->contenttype) {
            case content::TYPE_IMAGE_EMBED:
            case content::TYPE_VIDEO_EMBED:
            case content::TYPE_AUDIO_EMBED:
            case content::TYPE_DOCUMENT_EMBED:
            case content::TYPE_PRESENTATION_EMBED:
            case content::TYPE_SPREADSHEET_EMBED:
                $contentdata->embedcode = $contentdata->content;
                break;

            case content::TYPE_URL:
            case content::TYPE_URL_IMAGE:
            case content::TYPE_URL_VIDEO:
            case content::TYPE_URL_AUDIO:
            case content::TYPE_URL_DOCUMENT:
            case content::TYPE_URL_PRESENTATION:
            case content::TYPE_URL_SPREADSHEET:
            case content::TYPE_URL_SPREADSHEET_XLS:
                $contentdata->weblink = $contentdata->content;
                break;

            default:
                break;
        }

        // Is it a pinbaord slot?
        if ($contentdata->levelid == 0) {
            $contentdata->ispinboardslot = true;
        } else {
            $contentdata->ispinboardslot = false;
        }

        // Include slot tags?
        if ($gettags) {
            $contentdata->tags = tags::get($contentdata->id);
        } else {
            $contentdata->tags = array();
        }

        $contentdata->comments = comments::total_for_content($contentdata->id);

        // Include slot versions?
        if ($getversions > 0) {
            $contentdata->versioncount = contentversion::count($contentdata->id, ($getversions == 1 ? false : true));
        }

        return $contentdata;
    }

    /**
     * Helper function to return content type as a name.
     *
     * @param object $content Original contnet data record.
     * @return string Return slot type.
     */
    public static function get_content_type_name($content) {
        switch ($content->contenttype) {
            case content::TYPE_IMAGE:
            case content::TYPE_IMAGE_EMBED:
            case content::TYPE_URL_IMAGE:
                $contenttype = 'image';
                break;

            case content::TYPE_VIDEO:
            case content::TYPE_VIDEO_EMBED:
            case content::TYPE_URL_VIDEO:
                $contenttype = 'video';
                break;

            case content::TYPE_AUDIO:
            case content::TYPE_AUDIO_EMBED:
            case content::TYPE_URL_AUDIO:
                $contenttype = 'audio';
                break;

            case content::TYPE_DOCUMENT:
            case content::TYPE_DOCUMENT_EMBED:
            case content::TYPE_URL_DOCUMENT:
            case content::TYPE_URL_DOCUMENT_PDF:
            case content::TYPE_URL_DOCUMENT_DOC:
                $contenttype = 'document';
                break;

            case content::TYPE_TEXT:
                $contenttype = 'text';
                break;

            case content::TYPE_PRESENTATION:
            case content::TYPE_PRESENTATION_EMBED:
            case content::TYPE_URL_PRESENTATION:
            case content::TYPE_URL_PRESENTATION_PPT:
                $contenttype = 'presentation';
                break;

            case content::TYPE_SPREADSHEET:
            case content::TYPE_SPREADSHEET_EMBED:
            case content::TYPE_URL_SPREADSHEET:
            case content::TYPE_URL_SPREADSHEET_XLS:
                $contenttype = 'spreadsheet';
                break;

            case content::TYPE_URL:
                $contenttype = 'web';
                break;

            case content::TYPE_FOLDER:
                $contenttype = 'set';
                break;

            case content::TYPE_NONE:
                if (trim($content->name) != '') {
                    $contenttype = 'text';
                    break;
                }
            default:
                $contenttype = 'none';
                break;
        }

        return $contenttype;
    }

    /**
     * Helper function to return course id given studio id.
     *
     * @param int $studioid Studio id.
     * @return int Return course id, or false if error.
     */
    public static function get_courseid_from_studioid($studioid) {
        global $DB;

        $getslotcoursesql = <<<EOF
SELECT DISTINCT cm.course
  FROM {course_modules} cm
  JOIN {modules} m ON m.id = cm.module
 WHERE m.name = 'openstudio'
   AND cm.instance = ?

EOF;

        $slocoursetdata = $DB->get_record_sql($getslotcoursesql, array($studioid));
        if ($slocoursetdata === false) {
            return false;
        }

        return (int) $slocoursetdata->course;
    }

    /**
     * Helper function to return course module data associated with given studio id.
     *
     * @param int $studioid Studio id.
     * @return int Return course module data, or false if error.
     */
    public static function get_coursemodule_from_studioid($studioid) {
        global $DB;

        $getcoursemodulesql = <<<EOF
SELECT DISTINCT cm.*
  FROM {course_modules} cm
  JOIN {modules} m ON m.id = cm.module
 WHERE m.name = 'openstudio'
   AND cm.instance = ?

EOF;

        $cm = $DB->get_record_sql($getcoursemodulesql, array($studioid));
        if ($cm === false) {
            return false;
        }

        return $cm;
    }

    /**
     * Helper function to return studio id associated with given course module id.
     *
     * @param int $cmid Course module id.
     * @return int Return Studio id, or false if error.
     */
    public static function get_studioid_from_coursemodule($cmid) {
        global $DB;

        $getstudioidsql = <<<EOF
SELECT DISTINCT cm.instance
  FROM {course_modules} cm
  JOIN {modules} m ON m.id = cm.module
 WHERE m.name = 'openstudio'
   AND cm.id = ?

EOF;

        $studiodata = $DB->get_record_sql($getstudioidsql, array($cmid));
        if ($studiodata === false) {
            return false;
        }

        return $studiodata->instance;
    }

    /**
     * Helper function to return file name extension from mimetype.
     *
     * @param string $mimetype Mimetype to extract info from.
     * @return array Return extract mimetype information.
     */
    public static function mimeinfo_from_type($mimetype) {
        $mimeinfo = get_mimetypes_array();
        foreach ($mimeinfo as $extension => $values) {
            if ($values['type'] == $mimetype) {
                if ($mimetype == 'application/zip') {
                    // Force zip extension as new h5p type uses zip mimetype also.
                    $extension = 'zip';
                }
                $values['extension'] = $extension;
                return $values;
            }
        }

        $values = $mimeinfo['xxx'];
        $values['extension'] = 'xxx';

        return $values;
    }

    /**
     * Returns 'official' slot name.
     *
     * @param object $contentdata Slot data record.
     * @param bool $iffolderincludecontentname Ronseal.
     * @return string Return formatted slot name.
     */
    public static function get_content_name($contentdata, $iffolderincludecontentname = true) {
        if ($contentdata->levelid == 0) {
            if (isset($contentdata->name)) {
                if (trim($contentdata->name) == '') {
                    if ($contentdata->contenttype == content::TYPE_FOLDER) {
                        $slotdataname = get_string('foldertitlepinboard', 'openstudio');
                    } else {
                        $slotdataname = get_string('contenttitlepinboard', 'openstudio');
                    }
                } else {
                    $slotdataname = $contentdata->name;
                }
            } else {
                $slotdataname = '';
            }
        } else {
            if (isset($contentdata->levelid) &&
                    (!isset($contentdata->l1name) || !isset($contentdata->l2name) || !isset($contentdata->l3name))) {
                $contentdata->l1name = '';
                $contentdata->l2name = '';
                $contentdata->l3name = '';

                $level3data = api\levels::get_record(defaults::CONTENTLEVELCONTAINER, $contentdata->levelid);
                if ($level3data) {
                    $contentdata->l3name = $level3data->name;
                }
                $level2data = api\levels::get_record(
                        defaults::ACTIVITYLEVELCONTAINER, $level3data->level2id);
                if ($level2data) {
                    $contentdata->l2name = $level2data->name;
                }
                $level1data = api\levels::get_record(
                        defaults::BLOCKLEVELCONTAINER, $level2data->level1id);
                if ($level1data) {
                    $contentdata->l1name = $level1data->name;
                }
            }

            $slotdataname = trim($contentdata->l1name);
            if (trim($contentdata->l2name) != '') {
                $slotdataname .= ' - ' . $contentdata->l2name;
            }
            if (trim($contentdata->l3name) != '') {
                $slotdataname .= ' - ' . $contentdata->l3name;
            }
            if ($iffolderincludecontentname && (trim($contentdata->name) != '')) {
                $slotdataname .= ' - ' . $contentdata->name;
            }
        }

        return $slotdataname;
    }

    /**
     * Returns 'official' slot name for levels data.
     *
     * @param string $blockname Block name.
     * @param string $activityname Activity name.
     * @param string $contentname Slot name.
     * @param bool $pinboard Is slot pinboard.
     * @param bool $l2hidelevel Should level 2 be hidden.
     * @return string Return formatted slot name.
     */
    public static function get_content_heading(
            $blockname, $activityname = null, $contentname = null, $pinboard = false, $l2hidelevel = false) {
        if ($pinboard == false) {
            $output = trim($blockname);

            if ($l2hidelevel) {
                $activityname = '';
            }

            if (trim($activityname) != '') {
                $output .= ' - ' . $activityname;
            }

            if (trim($contentname) != '') {
                $output .= ' - ' . $contentname;
            }
        } else {
            $output = 'Pinboard';
        }

        return $output;
    }

    /**
     * Call to check if alert plugin exists.  If so, includes
     * the library suppport, otherwise return false.
     *
     * @return bool True if OU alerts extension is installed.
     */
    public static function oualerts_installed() {
        global $CFG;

        if (file_exists("{$CFG->dirroot}/report/oualerts/locallib.php")) {
            @include_once("{$CFG->dirroot}/report/oualerts/locallib.php");
            return oualerts_enabled();
        }

        return false;
    }

    /**
     * Call to check if search plugin exists.  If so, includes
     * the library suppport, otherwise return false.
     *
     * @return bool True if OU search extension is installed.
     */
    public static function search_installed() {
        global $CFG;

        if (file_exists("{$CFG->dirroot}/local/ousearch/searchlib.php")) {
            @include_once("{$CFG->dirroot}/local/ousearch/searchlib.php");
            return true;
        }

        return false;
    }

    /**
     * Call to check if moodle global search plugin exists.
     *
     * @return bool True if OU moodle global search extension is installed.
     */
    public static function moodle_global_search_installed() {
        global $CFG;
        return file_exists($CFG->dirroot . '/local/moodleglobalsearch/classes/util.php');
    }

    /**
     * Call to check if moodle global search/core global search should be used.
     *
     * @param object $cm Course module object.
     * @return bool True if moodle global search is enabled or if it and OU Search are not installed and core global search is enabled
     */
    public static function global_search_enabled($cm) {
        global $CFG;

        // If moodle global search is installed and enabled that should be used.
        if(util::moodle_global_search_installed()) {
            $cminfo = \cm_info::create($cm);
            return \local_moodleglobalsearch\util::is_activity_search_enabled($cminfo);
        }

        // If OU Search is installed, use that instead of global search
        if(util::search_installed()){
            return false;
        }

        // If neither moodle global search nor OU search are installed then use core global search if enabled
        return !empty($CFG->enableglobalsearch);
    }

    /**
     * Hash function applied to uploaded files to generate a hash key that
     * can be used to compare if another uploaded file is the same.
     *
     * The algorithm is basic, but it's better than having to scan/open a large 10MB
     * file - which may produce a better hash key but would be slower.
     *
     * @param int|object $draftitemidorfile
     * @param string $filepath
     * @return mixed Return calculated hash key, or false if error.
     */
    public static function calculate_file_hash($draftitemidorfile, $filepath = '/') {
        $file = false;

        if (is_object($draftitemidorfile)) {
            $file = $draftitemidorfile;
        } else if (is_int($draftitemidorfile)) {
            if (count($files = file_get_drafarea_files($draftitemidorfile, $filepath = '/')->list)) {
                $file = $files[0];
            }
        }

        if ($file) {
            $imagewidth = '';
            if (isset($file->image_width)) {
                $imagewidth = $file->image_width;
            }

            $imageheight = '';
            if (isset($file->image_height)) {
                $imageheight = $file->image_height;
            }

            $data = $file->filename
                    . ':' . $file->fullname
                    . ':' . $file->size
                    . ':' . $file->mimetype
                    . ':' . $file->type
                    . ':' . $imagewidth
                    . ':' . $imageheight;

            return md5($data);
        }

        return false;
    }

    /**
     * Calculates the current page URL from the server environment variables.
     * This is mainly use to tell the Moodle crumb trail what the active URL is.
     *
     * @param bool $addport Should the server port be added.
     * @return string Returns current page URL or empty string if error.
     */
    public static function get_current_url($addport = false) {
        global $CFG;

        if (stripos($CFG->wwwroot, 'https') !== false) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }

        // Check if URL differs from httpswwwroot and if port number is the reason for
        // difference.
        $url = $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        if (strpos($url, "$CFG->wwwroot/") !== 0) {
            if (strpos($url, ":") !== 0) {
                $addport = true;
            }
        }

        $serverport = (int) trim($_SERVER['SERVER_PORT']);
        if ($addport && ($serverport > 0) && ($serverport != 80)) {
            $serverport = ":$serverport";
        } else {
            $serverport = '';
        }

        $url = $protocol . $_SERVER['SERVER_NAME'] . $serverport . $_SERVER['REQUEST_URI'];

        return $url;
    }

    /**
     * Caclulates the current page name and URL request paramaters.
     *
     * This is mainly use to tell the Moodle crumb trail what the active URL is.
     *
     * @param bool $truncateforlogging Should the returned URL be truncated in length.
     * @param string $url URL to process, reads from $_SERVER if not given
     * @return string Returns current page name and URL request paramaters.
     */
    public static function get_page_name_and_params($truncateforlogging = true, $url = null) {
        if ($url == null) {
            $url = $_SERVER['REQUEST_URI'];
        }
        $modpath = '/mod/openstudio/';
        $pos = strpos($url, $modpath);
        $data = substr($url, $pos + strlen($modpath));

        if ($truncateforlogging) {
            if (strlen($data) >= 100) {
                $data = substr($data, 0, 96) . '...';
            }
        }

        return $data;
    }

    /**
     * Extracts URL query parameter/value pairs.
     *
     * @param string $url The URL to parse.
     * @return array Returns array of key/value pairs from the URL query string.
     */
    public static function extract_url_params($url) {
        $formurlparameters = array();

        if (trim($url) != '') {
            $formurl = parse_url($url);
            $formurlparams = explode('&', $formurl['query']);
            foreach ($formurlparams as $formurlparam) {
                $formurlparamdata = explode('=', $formurlparam);
                if (count($formurlparamdata) < 2) {
                    continue;
                }

                $formurlparamkey = clean_param($formurlparamdata[0], PARAM_TEXT);
                $formurlparamvalue = clean_param($formurlparamdata[1], PARAM_TEXT);
                $formurlparameters[$formurlparamkey] = $formurlparamvalue;
            }
        }

        return $formurlparameters;
    }

    /**
     * Helper function which gets the current URL and allows for the URL parameters to be substituted.
     *
     * @param array $params Paramters to substitute.
     * @param array $paramstoremove Paramters to remove.
     * @return string Returns URL with substituted parameters.
     */
    public static function get_page_name_and_substitute_params(
            $params = array(), $paramstoremove = array('reset')) {

        $urldata = parse_url(self::get_page_name_and_params(false));

        $urlparams = explode('&', $urldata['query']);
        $urlnewparams = array();

        // NOTE: if the key in the URL param is fblockarray[], then they cannot
        // be substituted.
        //
        // Also key name tz is not included.

        // Substitute existing key/values if necessary.
        foreach ($urlparams as $key => $value) {
            $keyvalue = explode('=', $value);
            if (count($keyvalue) > 1) {
                $tempkeyname = clean_param($keyvalue[0], PARAM_TEXT);
                $tempkeyvalue = clean_param($keyvalue[1], PARAM_TEXT);

                if (($tempkeyname == 'fblockarray%5B%5D') || ($tempkeyname == 'fblockarray[]') || ($tempkeyname == 'tz')) {
                    continue;
                }
                if (in_array($tempkeyname, $paramstoremove)) {
                    continue;
                }
                if (array_key_exists($tempkeyname, $params)) {
                    $urlnewparams[$tempkeyname] = $params[$tempkeyname];
                } else {
                    $urlnewparams[$tempkeyname] = $tempkeyvalue;
                }
            }
        }

        // Add new key/values if necessary.
        foreach ($params as $key => $value) {
            if ($key == 'tz') {
                continue;
            }
            if (!array_key_exists($key, $urlnewparams)) {
                $urlnewparams[$key] = $value;
            }
        }

        // Rebuild URL.
        $url = $urldata['path'] . '?';
        foreach ($urlnewparams as $key => $value) {
            if ($key == 'tz') {
                continue;
            }
            if ($value === null) {
                continue;
            }
            $url .= "{$key}={$value}&";
        }

        foreach ($urlparams as $key => $value) {
            $keyvalue = explode('=', $value);
            if (count($keyvalue) > 1) {
                if (($keyvalue[0] == 'fblockarray%5B%5D') || ($keyvalue[0] == 'fblockarray[]') || ($keyvalue[0] == 'tz')) {
                    $url .= "{$key}={$value}&";
                }
            }
        }

        return $url;
    }

    /**
     * Helper function to get stream filter parameters from SESSION memory.
     *
     * @param int $viewmode
     * @param bool $includepageparam
     * @return array Returns stream filter parameters.
     */
    public static function get_stream_url_params($viewmode, $includepageparam = false) {
        global $SESSION;

        if (!isset($SESSION->studio_view_filters)
                || !array_key_exists($viewmode, $SESSION->studio_view_filters)
                || !isset($SESSION->studio_view_filters[$viewmode]->fblock)) {
            return array();
        }

        $parms = array(
                'fblock' => $SESSION->studio_view_filters[$viewmode]->fblock,
                'ftype' => $SESSION->studio_view_filters[$viewmode]->ftype,
                'fflag' => $SESSION->studio_view_filters[$viewmode]->fflag,
                'ftags' => $SESSION->studio_view_filters[$viewmode]->ftags,
                'fsort' => $SESSION->studio_view_filters[$viewmode]->fsort,
                'osort' => $SESSION->studio_view_filters[$viewmode]->osort,
                'mode' => $SESSION->studio_view_filters[$viewmode]->mode,
                'groupid' => $SESSION->studio_view_filters[$viewmode]->groupid
        );

        if ($includepageparam) {
            $parms['page'] = $SESSION->studio_view_filters[$viewmode]->page;
        }

        return $parms;
    }

    /**
     * Add an entry to the log/event table.
     *
     * @param    int     $cmid      The course module id.
     * @param    string  $action    'view', 'update', 'add' or 'delete', possibly followed by another word to clarify.
     * @param    mixed   $objectid  Additional, optional object id.
     * @param    string  $url       The file and parameters used to see the results of the action.
     * @param    string  $info      Additional description information.
     * @param int $flagid The ID of the flag this event is associated with.
     * @param int $commentid The ID of the comment this event is associated with.
     * @return void
     */
    public static function trigger_event(
            $cmid, $action, $objectid = null, $url = '', $info = '', $flagid = null, $commentid = null) {

        $modulecontext = \context_module::instance($cmid);
        $coursecontext = $modulecontext->get_course_context();

        $legacyname = '';
        switch ($action) {
            case 'stream_viewed':
            case 'feed_viewed':
            case 'people_viewed':
                $legacyname = $info;
                break;
        }
        if ($url && ($url instanceof \moodle_url)) {
            $url = $url->out(false);
        }
        $params = array(
                'context' => $modulecontext,
                'other' => array(
                        'courseid' => $coursecontext->instanceid,
                        'module' => 'openstudio',
                        'action' => $legacyname,
                        'url' => $url,
                        'info' => $info,
                        'flagid' => $flagid,
                        'commentid' => $commentid
                )
        );
        if (isset($objectid)) {
            $params['objectid'] = $objectid;
        }

        $eventclass = "\\mod_openstudio\\event\\{$action}";
        switch ($action) {
            case 'content_viewed':
            case 'folder_viewed':
            case 'content_created':
            case 'content_edited':
            case 'folder_created':
            case 'folder_edited':
            case 'folder_content_created':
            case 'export_viewed':
            case 'content_exported':
            case 'import_viewed':
            case 'content_imported':
            case 'index_viewed':
            case 'manageactivities_viewed':
            case 'manageblocks_viewed':
            case 'managecontents_viewed':
            case 'manageexport_viewed':
            case 'manageimport_viewed':
            case 'levels_imported':
            case 'reportusage_viewed':
            case 'search_viewed':
            case 'contenthistory_viewed':
            case 'contentversion_viewed':
            case 'subscription_sent':
            case 'content_helpme_flagged':
            case 'folder_helpme_flagged':
            case 'content_favourite_flagged':
            case 'folder_favourite_flagged':
            case 'content_smile_flagged':
            case 'folder_smile_flagged':
            case 'content_inspire_flagged':
            case 'folder_inspire_flagged':
            case 'content_comment_created':
            case 'content_commentreply_created':
            case 'content_comment_flagged':
            case 'folder_comment_created':
            case 'folder_commentreply_created':
            case 'folder_comment_flagged':
                $event = $eventclass::create($params);
                break;

            case 'content_item_viewed':
                $objectids = explode('/', $params['objectid']);
                $params['other']['collectionid'] = (int) $objectids[0];
                $params['other']['collectionitemid'] = (int) $objectids[1];
                $params['objectid'] = $params['other']['collectionid'];
                $event = \mod_openstudio\event\folder_item_viewed::create($params);
                break;

            case 'stream_viewed':
                $objectids = explode('/', $params['objectid']);
                $params['other']['viewname'] = $objectids[0];
                $params['other']['layoutmode'] = (int) $objectids[1];
                unset($params['objectid']);
                $event = \mod_openstudio\event\stream_viewed::create($params);
                break;

            case 'feed_viewed':
                $objectids = explode('/', $params['objectid']);
                $params['other']['feedformat'] = $objectids[0];
                $params['other']['renderform'] = $objectids[1];
                unset($params['objectid']);
                $event = \mod_openstudio\event\feed_viewed::create($params);
                break;

            case 'people_viewed':
                $params['other']['vidviewname'] = $params['objectid'];
                unset($params['objectid']);
                $event = \mod_openstudio\event\people_viewed::create($params);
                break;

            case 'content_unlocked':
                $objectids = explode('/', $params['objectid']);
                $params['other']['userid'] = (int) $objectids[0];
                $params['other']['unlocked'] = (int) $objectids[1];
                unset($params['objectid']);
                $event = \mod_openstudio\event\content_unlocked::create($params);
                break;

            case 'content_locked':
                $objectids = explode('/', $params['objectid']);
                $params['other']['userid'] = (int) $objectids[0];
                $params['other']['locktype'] = (int) $objectids[1];
                unset($params['objectid']);
                $event = \mod_openstudio\event\content_locked::create($params);
                break;

            case 'content_scheduled_locked':
                $objectids = explode('/', $params['objectid']);
                $params['other']['userid'] = (int) $objectids[0];
                $params['other']['locktype'] = (int) $objectids[1];
                unset($params['objectid']);
                $event = \mod_openstudio\event\content_scheduled_locked::create($params);
                break;

            case 'content_scheduled_unlocked':
                $objectids = explode('/', $params['objectid']);
                $params['other']['userid'] = (int) $objectids[0];
                $params['other']['locktype'] = (int) $objectids[1];
                unset($params['objectid']);
                $event = \mod_openstudio\event\content_scheduled_unlocked::create($params);
                break;

            default:
                $event = false;
                break;
        }

        if ($event) {
            $event->trigger();
        }
    }

    /**
     * Helper function to format information string that will be fed to
     * add_to_log() function call.
     *
     * @param string $info The infomation string to format.
     * @return string Returns current page name and URL request paramaters.
     */
    public static function format_log_info($info) {
        if (strlen($info) >= 200) {
            $info = substr($info, 0, 196) . '...';
        }

        return $info;
    }

    /**
     * Helper function to proper escape value embedde in XML tags.
     *
     * @param string $s The string data to escape.
     * @return string Returns escaped string that can be safely inserted into an XML tag.
     */
    public static function escape_xml($s) {
        $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
        $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8', false);
        return $s;
    }

    /**
     * Helper function to call moodle url to link filter.
     *
     * @param string $text The text string to apply filter.
     * @return string Returns text string with filter applied.
     */
    public static function filter_urltolink($text) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/filter/urltolink/filter.php');
        $filterurltolink = new \filter_urltolink($PAGE->context, array());
        return $filterurltolink->filter($text, array('originalformat' => 0));
    }

    /**
     * In-memory request level cache support.
     *
     * This inits the cachce if it doesnt exists.
     *
     * @param array $studiocache Default cache array - optional.
     * @return object Return cache.
     */
    private static function cache_init($studiocache = null) {
        if (!isset($studiocache) || !is_array($studiocache)) {
            self::$cacheseed = \uniqid('mod_openstudio', true);
            $studiocache = array();
        }

        return $studiocache;
    }

    /**
     * In-memory request level cache support.
     *
     * This stores a value in cache associated with given key.
     *
     * @param mixed $key Cache key name.
     * @param mixed $value Value to set for given key.
     */
    public static function cache_put($key, $value) {
        global $studiocache;
        $studiocache = static::cache_init($studiocache);

        $key = self::$cacheseed . ":{$key}";
        $studiocache[$key] = $value;
    }

    /**
     * In-memory request level cache support.
     *
     * This retrieves value from cache associated with given key.
     *
     * To prevent the cache being used incorrectly, the function deliberately
     * throws a Moodle error to make sure the developer uses the caching
     * mechanism correctly.
     *
     * @param mixed $key Key of the data item in cache to retrieve.
     * @return mixed Value in cache for given key.
     */
    public static function cache_get($key) {
        global $studiocache;
        $studiocache = static::cache_init($studiocache);

        $key = self::$cacheseed . ":{$key}";
        if (array_key_exists($key, $studiocache)) {
            return $studiocache[$key];
        }

        print_error("Studio: developer error: cache key does not exists: $key");
    }

    /**
     * In-memory request level cache support.
     *
     * Checks if cache contains data for the given key.
     *
     * @param mixed $key Key in cache to check
     * @return bool Returns true or false if the key exists in cache.
     */
    public static function cache_check($key) {
        global $studiocache;
        $studiocache = static::cache_init($studiocache);

        $key = self::$cacheseed . ":{$key}";
        if (array_key_exists($key, $studiocache)) {
            return true;
        }

        return false;
    }

    /**
     * Add attributes to the objects in $content indicating the permissions they
     * allowed by the template
     *
     * @param array $content The slots in the set
     * @param array $contenttemplates The slot templates for the set
     * @return array Content array with template permissions added.
     */
    public static function folder_content_add_permissions($contents, $contenttemplates) {
        foreach ($contents as $content) {
            $reorder = true;
            if (!empty($content->foldercontenttemplateid) && array_key_exists($content->foldercontenttemplateid, $contenttemplates)) {
                $template = $contenttemplates[$content->foldercontenttemplateid];
                $permissions = $template->permissions;
                if (($permissions & folder::PERMISSION_REORDER) !== folder::PERMISSION_REORDER) {
                    $reorder = false;
                }
            }
            $content->canreorder = $reorder;
        }
        return $contents;
    }

    /**
     * Get last-modified time for studio, as it appears to this user. This takes into
     * account the user's groups/individual settings if required. (Does not check
     * that user can view the studio.)
     *
     * Info is static cached, so can be called in multiple places on page.
     *
     * @param object $cm Course-modules entry for studio
     * @param object $Course Course object
     * @param int $userid User ID or 0 = current
     * @return int Last-modified time for this user as seconds since epoch
     */
    public static function get_last_modified($cm, $course, $userid = 0) {
        global $USER, $DB;
        if (!$userid) {
            $userid = $USER->id;
        }
        static $results;
        if (!isset($results)) {
            $results = array();
        }
        if (!array_key_exists($userid, $results)) {
            $results[$userid] = array();
        } else if (array_key_exists($cm->id, $results[$userid])) {
            return $results[$userid][$cm->id];
        }
        static $studios;
        if (!isset($studios)) {
            $studios = array();
        }
        if (empty($studios[$course->id])) {
            $studios[$course->id] = $DB->get_records('openstudio', array('course' => $course->id));
        }
        // Get studio record.
        if (!isset($studios[$course->id][$cm->instance])) {
            return false;
        }
        $studio = $studios[$course->id][$cm->instance];
        $cminstance = $DB->get_record('openstudio', array('id' => $cm->instance), '*', MUST_EXIST);
        $permissions = self::check_permission($cm, $cminstance, $course);
        $limittimeadd = strtotime(date('Y-m-d', strtotime('-30 days')));
        $liststudio = $studio->id;
        $sql = "SELECT ss.*, ssi.timeadded
        FROM {openstudio_contents} ss
        INNER JOIN {openstudio_content_items} ssi ON ss.id = ssi.containerid
        WHERE ss.openstudioid =:openstudioid AND ss.deletedtime IS NULL
            AND ssi.timeadded >= :timelimit AND ssi.containertype =:stdcontenttype
        ORDER BY ssi.timeadded DESC";

        $slotparams = array(
                'openstudioid'     => $liststudio,
                'stdcontenttype' => item::CONTENT,
                'timelimit' => $limittimeadd
        );
        $slot = $DB->get_recordset_sql($sql, $slotparams);
        if ($slot->valid() === false) {
            return;
        }
        foreach ($slot as $record => $value) {
            // Check permission view last modify.
            $checkpermission = self::can_read_content($studio, $permissions, $value);
            if ($checkpermission) {
                $result = $value->timeadded;
                $results[$userid][$cm->id] = $result;
                return $result;
            }
        }
    }

    /**
     * Get human file size from byte
     *
     * @param int $bytes Bytes
     * @param int $decimals Decimals
     * @return string E.g: 2.3MB
     */
    public static function human_filesize($bytes, $decimals = 2) {
        $size = array('B', 'KB', 'MB', 'GB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    /**
     * Get content file icon
     *
     * @param int $contenttype Conntent type
     * @return array
     *  [
     *      iconurl: string,
     *      icontitle: string
     *  ]
     */
    public static function get_content_file_icon($contenttype) {
        global $OUTPUT;
        $icon = new \stdClass();
        switch ($contenttype) {
            case content::TYPE_FOLDER:
                $icon->fileicon = $OUTPUT->image_url('folder_rgb_32px', 'openstudio');
                $icon->filetype = get_string('filtertypesfolder', 'openstudio');
                break;
            case content::TYPE_IMAGE:
                $icon->fileicon = $OUTPUT->image_url('image_rgb_32px', 'openstudio');
                $icon->filetype = get_string('filtertypesimage', 'openstudio');
                break;
            case content::TYPE_VIDEO:
                $icon->fileicon = $OUTPUT->image_url('video_rgb_32px', 'openstudio');
                $icon->filetype = get_string('filtertypesvideo', 'openstudio');
                break;
            case content::TYPE_AUDIO:
                $icon->fileicon = $OUTPUT->image_url('audio_rgb_32px', 'openstudio');
                $icon->filetype = get_string('filtertypesaudio', 'openstudio');
                break;
            case content::TYPE_DOCUMENT:
                $icon->fileicon = $OUTPUT->image_url('text_doc_rgb_32px', 'openstudio');
                $icon->filetype = get_string('filtertypesdocuments', 'openstudio');
                break;
            case content::TYPE_PRESENTATION:
                $icon->fileicon = $OUTPUT->image_url('powerpoint_rgb_32px', 'openstudio');
                $icon->filetype = get_string('filtertypespresentation', 'openstudio');
                break;
            case content::TYPE_SPREADSHEET:
                $icon->fileicon = $OUTPUT->image_url('excel_spreadsheet_rgb_32px', 'openstudio');
                $icon->filetype = get_string('filtertypesspreadsheet', 'openstudio');
                break;
            case content::TYPE_URL:
                $icon->fileicon = $OUTPUT->image_url('html_markup_rgb_32px', 'openstudio');
                $icon->filetype = get_string('filtertypesweblink', 'openstudio');
                break;
            case content::TYPE_NONE:
            default:
                $icon->fileicon = $OUTPUT->image_url('unknown_rgb_32px', 'openstudio');
                $icon->filetype = get_string('unknown', 'openstudio');
                break;
        }

        return $icon;
    }

    /*
     * Helper function to generate the report abuse link.
     * The function below currently uses the oualerts plugin.
     * The function can be extended to support other installed alerting functions.
     *
     * @param string $modname Module/plugin name making the report.
     * @param int $modulecontextid Module instance id.
     * @param string $itemtype Item type being reported.
     * @param int $itemid Item id being reported.
     * @param string $itemurl Item URL being reported.
     * @param string $returnurl Return URL to send user to after submitting report.
     * @param int $userid Id of user making the report.
     * @return string Returns constructed URL.
     */
    public static function render_report_abuse_link(
            $modname, $modulecontextid, $itemtype, $itemid, $itemurl, $returnurl, $userid) {

        $reportabuselink = '';

        /*
         * Depending on which alert system is available, call it to get the
         * report abuse link.
         */
        if (self::oualerts_installed()) {
            $itemurl = urlencode($itemurl);
            $returnurl = urlencode($returnurl);
            $reportabuselink = oualerts_generate_alert_form_url(
                    $modname, $modulecontextid,
                    $itemtype, $itemid, $itemurl, $returnurl,
                    $userid, true, true);
        }

        return $reportabuselink;
    }

    /**
     * Init necessary javascript modules for every pages.
     */
    public static function init_js() {
        global $PAGE;
        $PAGE->requires->js_call_amd('mod_openstudio/accessibility', 'init');
    }

    /**
     * Check if social item is double digit.
     *
     * @param $socialitem Object social item data.
     * @return Object $socialitem
     */
    public static function check_item_double_digit($socialitem) {
        $socialitem->isdoubledigitcomments = $socialitem->comments > 9;
        $socialitem->isdoubledigitinspired = $socialitem->inspired > 9;
        $socialitem->isdoubledigitmademelaugh = $socialitem->mademelaugh > 9;
        $socialitem->isdoubledigitfavourite = $socialitem->favourite > 9;

        return $socialitem;
    }

    /**
     * Check capability ignoreenrolemnt.
     *
     * @param $modulecontext \context Module context.
     * return bool
     */
    public static function is_ignore_enrol($modulecontext) {
        return has_capability('mod/openstudio:ignoreenrolment', $modulecontext);
    }

    /**
     * Get list of comment user is comment by content id.
     *
     * @param array list of content id.
     * @return array list of comment of user.
     */
    public static function get_list_comment_of_user_by_contentid($listcontentid) {
        if (empty($listcontentid)) {
            return false;
        }
        global $USER, $DB;
        list($sql, $sqlparams) = $DB->get_in_or_equal($listcontentid, SQL_PARAMS_NAMED);
        $sql = "SELECT oc.contentid
                  FROM {openstudio_comments} oc
                 WHERE oc.contentid $sql
                   AND oc.userid = :userid
                   AND oc.deletedby IS NULL
              GROUP BY oc.contentid";
        $sqlparams['userid'] = $USER->id;
        return $DB->get_records_sql($sql, $sqlparams);
    }

    /**
     * Get lastest comment when user add new a comment.
     *
     * @param $userid userid
     * @param $contentid content ID
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_lastest_comment_by_contentid($userid, $contentid) {
        global $DB;
        $sql = "SELECT sc2.id as commentid, sf.contentid AS contentid
                  FROM {openstudio_flags} sf
                  JOIN {openstudio_comments} sc2
                    ON sc2.contentid = sf.contentid
                   AND sc2.deletedtime IS NULL
                   AND sc2.userid != ?
            AND EXISTS (SELECT 1
                          FROM {openstudio_flags} sc2f
                         WHERE sc2f.contentid = sc2.contentid
                           AND sc2f.flagid = 6
                           AND sc2f.userid = ?
                           AND sc2.timemodified > sc2f.timemodified)
                 WHERE sf.contentid = ?
              GROUP BY sc2.id, sf.contentid";
        $sqlparams = [$userid, $userid, $contentid];
        return $DB->get_records_sql($sql, $sqlparams);
    }

    /*
     * Get time readable for user.
     *
     * @param {int} id of user.
     * @param {int|timestamp} time convert to read.
     * @return {string|lang_string} time converted.
     * @throws coding_exception
     */
    public static function get_time_since_readable($userid, $timeread): string {
        $tz = \core_date::get_user_timezone_object($userid);
        $timecreated = new \DateTime('now', $tz);
        $timecreated->setTimestamp($timeread);
        $interval = $timecreated->diff(new \DateTime('now', $tz));

        if ($interval->y > 0) {
            return get_string('timereable_yearsago', 'mod_openstudio');
        } else if ($interval->m > 0) {
            if ($interval->m === 1) {
                return get_string('timereable_monthago', 'mod_openstudio', $interval->m);
            } else {
                return get_string('timereable_monthsago', 'mod_openstudio', $interval->m);
            }
        } else if ($interval->d > 0) {
            if ($interval->d === 1) {
                return get_string('timereable_dayago', 'mod_openstudio', $interval->d);
            } else {
                return get_string('timereable_daysago', 'mod_openstudio', $interval->d);
            }
        } else if ($interval->h > 0) {
            if ($interval->h === 1) {
                return get_string('timereable_hourago', 'mod_openstudio', $interval->h);
            } else {
                return get_string('timereable_hoursago', 'mod_openstudio', $interval->h);
            }
        } else if ($interval->i > 0) {
            if ($interval->i === 1) {
                return get_string('timereable_minuteago', 'mod_openstudio', $interval->i);
            } else {
                return get_string('timereable_minutesago', 'mod_openstudio', $interval->i);
            }
        } else {
            return get_string('timereable_secondsago', 'mod_openstudio');
        }
    }
}
