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
 * Prints a particular instance of openstudio
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace openstudio with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... openstudio instance ID - it should be named as the first character of the module.

// Page init and security checks.

$coursedata = openstudio_internal_render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;


require_login($course, true, $cm);

// Need to have view or managecontent capabilities.
if (!$permissions->managecontent) {
    require_capability('mod/openstudio:view', $mcontext);
}

// Get user id that we need to show stream for.
$vuid = optional_param('vuid', $USER->id, PARAM_INT);
if ($vuid != $USER->id) {
    $slotowner = studio_api_user_get_user_by_id($vuid);
    $viewuser = $USER;
} else {
    $slotowner = $viewuser = $USER;
}

// Get page url.
$strpageurl = openstudio_internal_getcurrenturl();

// Set stream view.
$vidworkspaceblockdefaultset = false;
$vid = optional_param('vid', -1, PARAM_INT);
if (! in_array($vid, array(OPENSTUDIO_VISIBILITY_PRIVATE,
                           OPENSTUDIO_VISIBILITY_PRIVATE_PINBOARD,
                           OPENSTUDIO_VISIBILITY_GROUP,
                           OPENSTUDIO_VISIBILITY_MODULE,
                           OPENSTUDIO_VISIBILITY_WORKSPACE))) {
    $vid = $theme->homedefault;
}

if (! in_array($vid, array(OPENSTUDIO_VISIBILITY_PRIVATE,
                           OPENSTUDIO_VISIBILITY_PRIVATE_PINBOARD,
                           OPENSTUDIO_VISIBILITY_GROUP,
                           OPENSTUDIO_VISIBILITY_MODULE,
                           OPENSTUDIO_VISIBILITY_WORKSPACE))) {
    $vid = $theme->homedefault;
}

// If group mode is not on, then redirect request to module workspace.
if (!$permissions->feature_group && ($vid == OPENSTUDIO_VISIBILITY_GROUP)) {
    $vid = OPENSTUDIO_VISIBILITY_MODULE;
}

// If activity mode is not on, then redirect request to module workspace.
if (!$permissions->feature_studio && ($vid == OPENSTUDIO_VISIBILITY_PRIVATE)) {
    $vid = OPENSTUDIO_VISIBILITY_MODULE;
}

// If pinboard mode is not on, then redirect request to module workspace.
if (!$permissions->feature_pinboard && ($vid == OPENSTUDIO_VISIBILITY_PRIVATE_PINBOARD)) {
    $vid = OPENSTUDIO_VISIBILITY_MODULE;
}

$pinboardonly = false;

// If module mode is not on, then redirect request to module first available workspace.
if (!$permissions->feature_module && ($vid == OPENSTUDIO_VISIBILITY_MODULE)) {
    $vid = $permissions->allow_visibilty_modes[0];
}


if ($vid == OPENSTUDIO_VISIBILITY_WORKSPACE) {
    $ismember = studio_api_group_has_same_memberships
            ($permissions->groupingid, $slotowner->id, $viewuser->id, true);
    if ($ismember) {
        $vidd = OPENSTUDIO_VISIBILITY_GROUP;
    } else {
        $vidd = OPENSTUDIO_VISIBILITY_MODULE;
    }
} else {
    $vidd = $vid;
}
switch ($vidd) {
    case OPENSTUDIO_VISIBILITY_GROUP:
        $strpagetitle = $strpageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->themegroupname));
        $vidcrumbarray = array($theme->themegroupname => $strpageurl);
        $vidviewname = 'group';
        $rssfeedtype = OPENSTUDIO_FEED_TYPE_GROUP;
        $subscriptiontype = OPENSTUDIO_SUBSCRIPTION_GROUP;
        break;

    case OPENSTUDIO_VISIBILITY_PRIVATE:
    case OPENSTUDIO_VISIBILITY_PRIVATE_PINBOARD:
        $strpagetitle = $strpageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->themestudioname));
        $vidcrumbarray = array($theme->themestudioname => $strpageurl);
        $vidviewname = 'work';
        $rssfeedtype = OPENSTUDIO_FEED_TYPE_ACTIVITY;
        $subscriptiontype = null;
        break;

    case OPENSTUDIO_VISIBILITY_MODULE:
    default:
        $strpagetitle = $strpageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->thememodulename));
        $vidcrumbarray = array($theme->thememodulename => $strpageurl);
        $vidviewname = 'module';
        $rssfeedtype = OPENSTUDIO_FEED_TYPE_MODULE;
        $subscriptiontype = OPENSTUDIO_SUBSCRIPTION_MODULE;
        break;
}
if ($vuid != $USER->id) {
    $crumbkey = get_string('profileswork', 'openstudio', array('name' => $slotowner->firstname));
    $vidcrumbarray[$crumbkey] = new moodle_url(
            '/mod/openstudio/view.php',
            array('id' => $cm->id,
                  'vid' => $vid,
                  'vuid' => $vuid));
    if (! $pinboardonly) {
        $strpagetitle .= ': ' . get_string('profileswork', 'openstudio', array('name' => $slotowner->firstname));;
    }
}
if ($pinboardonly) {
    $pageview = 'pinboard';
    if ($vuid != $USER->id) {
        $vidcrumbarray[$theme->themepinboardname] = $strpageurl;
        $strpagetitle .= ': ' . get_string('profilespinboard', 'openstudio', array('name' => $slotowner->firstname));;
        $rssfeedtype = OPENSTUDIO_FEED_TYPE_PINBOARD;
    } else {
        $vidcrumbarray = array($theme->themepinboardname => $strpageurl);
        $strpagetitle = $strpageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->themepinboardname));
        $rssfeedtype = OPENSTUDIO_FEED_TYPE_PINBOARD;
    }
} else {
    $pageview = 'activities';
}

// Generate user's unique RSS security key.
$rssfeedkey = '';

// Data for RSS.
$rssdata = array(
    'rssfeedtype' => $rssfeedtype,
    'rssfeedkey' => $rssfeedkey,
    'subscriptiontype' => $subscriptiontype,
    'viewuser' => $viewuser,
    'slotowner' => $slotowner,
);

// Render page header and crumb trail.
openstudio_internal_render_page_defaults(
        $PAGE, $strpagetitle, $strpageheading, $strpageurl, $course, $cm);

// Generate stream html.
$renderer = $PAGE->get_renderer('mod_openstudio');
$PAGE->set_button($renderer->searchform($theme, $vid));

$html = $renderer->siteheader(
        $coursedata, $permissions, $theme, $cm->name, '', $vid, $rssdata);

echo $OUTPUT->header(); // Header.

echo $html;

// Finish the page.
echo $OUTPUT->footer();
