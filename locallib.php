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
 * Internal library of functions for module openstudio
 *
 * All the openstudio specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
function openstudio_internal_render_page_init($id, $requiredcapabilities = array()) {
    global $DB, $USER;

    if (! ($cm = get_coursemodule_from_id('openstudio', $id))) {
        print_error('invalidcoursemodule');
    }

    // Get course instance.
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

    // NOTE: the additional third $cm parameter, automatically forces the
    // appearance of the LHS navigation bar.
    require_login($course, true, $cm);

    // Get module context.
    $modulecontext = context_module::instance($cm->id);

    // Security checks.
    foreach ($requiredcapabilities as $requiredcapability) {
        require_capability($requiredcapability, $modulecontext);
    }

    // Get module instance.
    $cminstance = $DB->get_record('openstudio', array('id' => $cm->instance), '*', MUST_EXIST);

    // Get couse context.
    $permissions = openstudio_internal_check_permission($cm, $cminstance, $course);

    $theme = (object) array(
        'homedefault' => $cminstance->themehomedefault,
        'sitename' => $cminstance->sitename,
        'pinboardname' => empty($cminstance->pinboardname) ? get_string('pinboard', 'studio') : $cminstance->pinboardname,
        'level1name' => empty($cminstance->level1name) ? get_string('block', 'studio') : $cminstance->level1name,
        'level2name' => empty($cminstance->level2name) ? get_string('activity', 'studio') : $cminstance->level2name,
        'level3name' => empty($cminstance->level3name) ? get_string('slot', 'studio') : $cminstance->level3name,
        'thememodulename' => empty($cminstance->thememodulename) ? get_string('settingsthemehomesettingsmodule',
        'studio') : $cminstance->thememodulename,
        'themegroupname' => empty($cminstance->themegroupname) ? get_string('settingsthemehomesettingsgroup',
        'studio') : $cminstance->themegroupname,
        'themestudioname' => empty($cminstance->themestudioname) ? get_string('settingsthemehomesettingsstudio',
        'studio') : $cminstance->themestudioname,
        'themepinboardname' => empty($cminstance->themepinboardname) ? get_string('settingsthemehomesettingspinboard',
        'studio') : $cminstance->themepinboardname,
        'helplink' => empty($cminstance->themehelplink) ? (new moodle_url(get_docs_url('openstudio'))
        )->out(false) : $cminstance->themehelplink,
        'helpname' => empty($cminstance->themehelpname) ? get_string('helplink',
        'studio') : $cminstance->themehelpname,
    );

    return (object) array(
            'cm' => $cm,
            'cminstance' => $cminstance,
            'course' => $course,
            'mcontext' => $modulecontext,
            'permissions' => $permissions,
            'theme' => $theme);
}

function openstudio_internal_check_permission($cm, $cminstance, $course) {
    global $DB, $USER;
    $modulecontext = context_module::instance($cm->id);
    $coursecontext = context_course::instance($course->id);
    // Gather permissions.
    $permissions = (object) array(
        'managecontent' => has_capability('mod/openstudio:managecontent', $modulecontext),
        'viewdeleted' => has_capability('mod/openstudio:viewdeleted', $modulecontext),
        'expertcommenter' => has_capability('mod/openstudio:expertcommenter', $modulecontext),
        'viewparticipation' => has_capability('mod/openstudio:viewparticipation', $modulecontext),
        'view' => has_capability('mod/openstudio:view', $modulecontext),
        'viewothers' => has_capability('mod/openstudio:viewothers', $modulecontext),
        'addcontent' => has_capability('mod/openstudio:addcontent', $modulecontext),
        'addcomment' => has_capability('mod/openstudio:addcomment', $modulecontext),
        'sharewithothers' => has_capability('mod/openstudio:sharewithothers', $modulecontext),
        'import' => has_capability('mod/openstudio:import', $modulecontext),
        'export' => has_capability('mod/openstudio:export', $modulecontext),
        'canlock' => has_capability('mod/openstudio:canlock', $modulecontext),
        'canlockothers' => has_capability('mod/openstudio:canlockothers', $modulecontext)
    );

    // Note: it is assumed that if a user can addcomment, they can also set flags such as smile, favourite, etc.
    $permissions->addflags = $permissions->addcomment;
    $permissions->addinstance = has_capability('mod/openstudio:addinstance', $coursecontext);
    $permissions->managelevels = has_capability('mod/openstudio:managelevels', $coursecontext);
    $permissions->accessallgroups = has_capability('moodle/site:accessallgroups', $coursecontext);
    // Get the configured tutor roles.
    $permissions->tutorroles = array_filter(explode(',', $cminstance->tutorroles));
    // Does the user have any of the tutor roles on the course? E.g. for displaying "Shared with tutor" filter.
    if (empty($permissions->tutorroles)) {
        $permissions->istutor = false;
    } else {
        list($tutorsql, $tutorparams) = $DB->get_in_or_equal($permissions->tutorroles);
        $tutorwhere = 'roleid ' . $tutorsql . ' AND userid = ? AND contextid = ?';
        $tutorparams[] = $USER->id;
        $tutorparams[] = $coursecontext->id;
        $permissions->istutor = $DB->record_exists_select('role_assignments', $tutorwhere, $tutorparams);
    }
    $permissions->activeuserid = $USER->id;
    $permissions->activecid = $course->id;
    $permissions->activecmid = $cm->id;
    $permissions->activecminstanceid = $cminstance->id;
    $permissions->activecmcontextid = $modulecontext->id;
    $permissions->activeenrollment = is_enrolled($coursecontext, $permissions->activeuserid);

    $permissions->coursecontext = $coursecontext;

    $permissions->flags = $cminstance->flags;
    $permissions->filetypes = $cminstance->filetypes;

    $permissions->versioningon = ($cminstance->versioning > 0) ? true : false;
    $permissions->copyingon = ($cminstance->copying == 0) ? false : true;

    $permissions->feature_pinboard = ($cminstance->pinboard > 0) ? true : false;
    $permissions->pinboarddata = openstudio_api_slot_get_total_pinboard_slots($cminstance->id, $USER->id);
    $permissions->feature_pinboard = $permissions->feature_pinboard ||
            (($permissions->pinboarddata->usedandempty > 0) ? true : false);

    $permissions->activitydata = openstudio_api_slot_get_total_slots($cminstance->id, $USER->id);
    $permissions->feature_studio = ($permissions->activitydata->total > 0) ? true : false;
    if (!$permissions->feature_studio) {
        $permissions->feature_studio = ($permissions->activitydata->used > 0) ? true : false;
    }
    $permissions->feature_studio = $permissions->feature_studio
        || (($cminstance->themefeatures & STUDIO_FEATURE_STUDIO) ? true : false);

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

    $permissions->feature_tutor = in_array(OPENSTUDIO_VISIBILITY_TUTOR, explode(',',
    $cminstance->allowedvisibility)) ? true : false;

    $permissions->feature_module = ($cminstance->themefeatures & OPENSTUDIO_FEATURE_MODULE) ? true : false;

    // Detemine studio features that have been turned on.
    $permissions->feature_slottextuseshtml = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_CONTENTTEXTUSESHTML) ? true : false;
    $permissions->feature_slotcommentuseshtml = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_CONTENTCOMMENTUSESHTML) ? true : false;
    $permissions->feature_slotcommentusesaudio = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_CONTENTCOMMENTUSESAUDIO) ? true : false;
    $permissions->feature_slotusesfileupload = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_CONTENTUSESFILEUPLOAD) ? true : false;
    $permissions->feature_enablecollections = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_ENABLECOLLECTIONS) ? true : false;
    $permissions->feature_enablesets = false;
    if (!$permissions->feature_enablecollections) {
        $permissions->feature_enablesets = (
            $cminstance->themefeatures & OPENSTUDIO_FEATURE_ENABLEFOLDERS) ? true : false;
    }
    $permissions->feature_enablesetsanyslot = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_ENABLEFOLDERSANYCONTENT) ? true : false;
    $permissions->feature_enablerss = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_ENABLERSS) ? true : false;
    $permissions->feature_enablesubscription = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_ENABLESUBSCRIPTION) ? true : false;
    $permissions->feature_enableexportimport = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_ENABLEEXPORTIMPORT) ? true : false;
    $permissions->feature_slotusesweblink = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_CONTENTUSESWEBLINK) ? true : false;
    $permissions->feature_slotusesembedcode = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_CONTENTUSESEMBEDCODE) ? true : false;
    $permissions->feature_slotallownotebooks = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_CONTENTALLOWNOTEBOOKS) ? true : false;
    $permissions->feature_slotreciprocalaccess = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_CONTENTRECIPROCALACCESS) ? true : false;
    $permissions->feature_participationsmiley = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_PARTICIPATIONSMILEY) ? true : false;
    $permissions->feature_enablelock = (
        $cminstance->themefeatures & OPENSTUDIO_FEATURE_ENABLELOCK) ? true : false;
    if ($permissions->managecontent) {
        $permissions->feature_slotreciprocalaccess = false;
    }
    $permissions->allow_visibilty_modes = array();
    if ($permissions->feature_module) {
        $permissions->allow_visibilty_modes[] = OPENSTUDIO_VISIBILITY_MODULE;
    }
    if ($permissions->feature_group || $permissions->feature_tutor) {
        $permissions->allow_visibilty_modes[] = OPENSTUDIO_VISIBILITY_GROUP;
    }
    if ($permissions->feature_studio) {
        $permissions->allow_visibilty_modes[] = OPENSTUDIO_VISIBILITY_PRIVATE;
    }
    if ($permissions->feature_pinboard) {
        $permissions->allow_visibilty_modes[] = OPENSTUDIO_VISIBILITY_PRIVATE_PINBOARD;
    }
    if (empty($permissions->allow_visibilty_modes)) {
        // If no visibility mode is set, we force the module view to be on.
        $permissions->feature_module = true;
        $permissions->allow_visibilty_modes[] = OPENSTUDIO_VISIBILITY_MODULE;
    }

    return $permissions;
}

/**
 * Helper function to set up Moodle $PAGE variables.
 *
 * Any Studio page that renders UI pages will call this function.
 *
 * @param moodle_page $PAGE Moodle $PAGE object.
 * @param string $title Title of the page.
 * @param string $heading Heading for the page.
 * @param string $url Page URL.
 * @param object $course Course record.
 * @param object $cm Course module record.
 * @param string $view View mode: admin pages or studio workspace pages.
 */
function openstudio_internal_render_page_defaults(
        moodle_page $PAGE, $title, $heading, $url, $course, $cm, $view = 'work') {

    $PAGE->set_cm($cm, $course);
    $PAGE->set_title($title);
    $PAGE->set_heading($heading);
    $PAGE->set_url($url . '&');

    $PAGE->set_cm($cm, $course);
    $PAGE->set_title($title);
    $PAGE->set_heading($heading);
    $PAGE->set_url($url . '&');

    // If admin pages, the page is rendered like a standard Moodle module with a left-hand navigation bar.
    // Main studio workspace pages takes the full-page width with no sidebars.
    if ($view === 'manage') {
        $PAGE->set_pagelayout('admin');
    } else {
        $PAGE->set_pagelayout('base');
        if ($PAGE->theme->name === 'ou') {
            $PAGE->set_pagelayout('fullpage');
        } else {
            $PAGE->set_pagelayout('print');
        }
    }
}

/**
 * Helper fucntion to render page crumb trail for Studio pages.
 *
 * @param moodle_page $PAGE Moodle $PAGE object.
 * @param int $parentcrumbid
 * @param string $parentcrumbtype
 * @param array $crumbs
 */
function openstudio_internal_render_page_crumb(
        moodle_page $PAGE, $parentcrumbid, $parentcrumbtype, $crumbs) {

    $navbaradditions = $PAGE->navigation->find($parentcrumbid, $parentcrumbtype);
    foreach ($crumbs as $title => $url) {
        $navbaradditions = $navbaradditions->add($title, $url);
    }
    $navbaradditions->make_active();
}

/**
 * Check if HTTPS is enabled for the moodle instance.
 *
 * @return bool Returns true if HTTPS is enabled.
 */
function openstudio_internal_is_https_enabled() {
    global $CFG;

    if (stripos($CFG->wwwroot, 'https') !== false) {
        return true;
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
function openstudio_internal_getcurrenturl($addport = false) {
    global $CFG;

    if (openstudio_internal_is_https_enabled()) {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }

    // Check if URL differs from httpswwwroot and if port number is the reason for
    // difference.
    $url = $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    if (strpos($url, "$CFG->httpswwwroot/") !== 0) {
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
 * Checks the user's available pinoard slots and and also total
 * used pinboard slots.
 *
 * @param int $studioid Studio instance id.
 * @param $userid Creator of the slot.
 * @return object Returns pinboard slot totals
 */
function openstudio_api_slot_get_total_pinboard_slots($studioid, $userid) {
    global $DB;

    $slotsused = 0;
    $slotsempty = 0;

    // Note: we do (deletedby - deletedby) so that the group by statement only has to group by
    // one value of 0 for all records that have the deletedby field populated.

    $sql = <<<EOF
  SELECT contenttype, deletedby AS deletestatus, count(id) AS slotcount
    FROM {studio_slots}
   WHERE studioid = ?
     AND userid = ?
     AND levelcontainer = 0
     AND levelid = 0
     AND visibility != ?
GROUP BY contenttype, deletedby, deletedtime
EOF;

    $countdata = $DB->get_recordset_sql($sql, array($studioid, $userid, STUDIO_VISIBILITY_INSETONLY));
    if ($countdata->valid()) {
        foreach ($countdata as $data) {
            if ($data->contenttype != STUDIO_CONTENTTYPE_NONE) {
                $slotsused = $slotsused + ((int) $data->slotcount);
                continue;
            }

            if (($data->contenttype == STUDIO_CONTENTTYPE_NONE)
                    && (is_null($data->deletestatus) || ($data->deletestatus == 0))) {
                $slotsempty = $slotsempty + ((int) $data->slotcount);
            }
        }
    }

    $slotsavailable = 0;

    $slotstotal = (int) $DB->get_field('studio', 'pinboard', array('id' => $studioid));
    if ($slotstotal > 0) {
        $slotsavailable = $slotstotal - $slotsused - $slotsempty;
        if ($slotsavailable < 0) {
            $slotsavailable = 0;
        }
    }

    return (object) array(
            'used' => $slotsused,
            'empty' => $slotsempty,
            'usedandempty' => $slotsused + $slotsempty,
            'total' => $slotstotal,
            'available' => $slotsavailable);
}

/**
 * Checks the user's available studio slots and and also total
 * used studio slots.
 *
 * @param int $studioid Studio instance id.
 * @param $userid Creator of the slot.
 * @return object Returns pinboard slot totals
 */
function openstudio_api_slot_get_total_slots($studioid, $userid) {
    global $DB;

    $sql = <<<EOF
SELECT COUNT(l3.*)
  FROM {studio_level3} l3
  JOIN {studio_level2} l2 ON l2.id = l3.level2id
  JOIN {studio_level1} l1 ON l1.id = l2.level1id
 WHERE l1.studioid = ?
   AND l1.status >= 0

EOF;

    $slotstotal = $DB->count_records_sql($sql, array($studioid));

    $sql = <<<EOF
SELECT COUNT(s.id)
  FROM {studio_slots} s
  JOIN {studio_level3} l3 ON l3.id = s.levelid
  JOIN {studio_level2} l2 ON l2.id = l3.level2id
  JOIN {studio_level1} l1 ON l1.id = l2.level1id
 WHERE l1.studioid = ?
   AND s.studioid = l1.studioid
   AND s.levelcontainer = 3
   AND s.contenttype <> ?
   AND s.userid = ?

EOF;

    $slotsused = $DB->count_records_sql($sql, array($studioid, STUDIO_CONTENTTYPE_NONE, $userid));

    return (object) array(
            'used' => $slotsused,
            'total' => $slotstotal);
}