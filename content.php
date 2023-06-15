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
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\util;
use mod_openstudio\local\api\contentversion;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\comments;
use mod_openstudio\local\api\user;
use mod_openstudio\local\api\tracking;
use mod_openstudio\local\forms\comment_form;
use mod_openstudio\local\renderer_utils;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

// Course_module ID.
$id = optional_param('id', 0, PARAM_INT);
// Folder id.
$folderid = optional_param('folderid', 0, PARAM_INT);
// Content id.
$contentid = optional_param('sid', 0, PARAM_INT);
// User id.
$iscontentversion = optional_param('contentversion', 0, PARAM_INT); // View content version.
$restoreversion = optional_param('restoreversion', 0, PARAM_INT); // Restore content version.
$archiveversion = optional_param('archiveversion', 0, PARAM_INT); // Archive content version.
$userid = optional_param('vuid', $USER->id, PARAM_INT);
$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$cmid = $cm->id;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;

require_login($course, true, $cm);

require_capability('mod/openstudio:view', $mcontext);

// Terms and conditions check.
util::honesty_check($id);

$returnurliferror = new moodle_url('/mod/openstudio/view.php', array('id' => $cm->id));

// Restore content version.
if ($restoreversion) {
    $contentversiondata = contentversion::get($contentid, $USER->id);
    if ($contentversiondata != false) {
        $contentrestoredata = content::get($contentversiondata->contentid);
    }

    if ($contentrestoredata) {
        $actionallowed = ($contentrestoredata->userid == $USER->id) && $permissions->addcontent && $permissions->versioningon;
        $actionallowed = $actionallowed || $permissions->managecontent;
        if ($actionallowed) {
            $restoredata = content::restore_version($userid, $contentid, $cm);

            // If restore success will return restore content id.
            // The process for view content detail will affect with content restore.
            // Else process for view content version detail.
            if ($restoredata) {
                $contentid = $restoredata->id;
                $redirectorurl = new moodle_url('/mod/openstudio/content.php',
                        array('id' => $cm->id, 'sid' => $contentid,  'vuid' => $restoredata->userid, 'folderid' => $folderid));
                return redirect($redirectorurl);
            }
        }
    }
}

// Get content and content version data.
$showdeletedcontentversions = false;

// Handle content and content version.
if ($iscontentversion) {
    $contentdata = contentversion::get($contentid, $USER->id, $showdeletedcontentversions);
} else {
    $contentandversions = contentversion::get_content_and_versions($contentid, $USER->id, $showdeletedcontentversions);
    $contentdata = lock::determine_lock_status($contentandversions->contentdata);
}

if ($contentdata === false) {
    return redirect($returnurliferror->out(false));
}

// Check the viewing user has permission to view content.
if (!util::can_read_content($cminstance, $permissions, $contentdata, $folderid)) {
    throw new \moodle_exception('errornopermissiontoviewcontent', 'openstudio', $returnurliferror->out(false));
}

// Archive content version.
if ($archiveversion) {
    $actionallowed = ($contentdata->userid == $userid) && $permissions->addcontent;
    $actionallowed = $actionallowed || $permissions->managecontent;
    if ($actionallowed) {
        if ($permissions->versioningon) {
            content::delete($userid, $contentid, $cminstance->versioning, $cm);
        } else {
            content::empty_content($userid, $contentid, true, $cminstance->versioning, $cm);
            if (($contentdata->levelid == 0) && ($contentdata->levelcontainer == 0)) {
                $redirectorurl = new moodle_url('/mod/openstudio/view.php',
                        array('id' => $cm->id, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1));
                return redirect($redirectorurl);
            }
        }
        $redirectorurl = new moodle_url('/mod/openstudio/content.php',
                array('id' => $cm->id, 'sid' => $contentdata->id,  'vuid' => $contentdata->userid, 'folderid' => $folderid));
        return redirect($redirectorurl);
    }
}

$contentdata->iscontentversion = false;
if ($iscontentversion) {
    $contentdata->iscontentversion = true;
    $contentcurrenteversionurl = new moodle_url('/mod/openstudio/content.php',
            array('id' => $cm->id, 'sid' => $contentdata->contentid, 'vuid' => $contentdata->userid, 'folderid' => $folderid));

    $contentdata->contentcurrenteversionurl = $contentcurrenteversionurl->out(false);

    $contentdata->contentpreviousversionlink = '';
    $contentdata->contentnextversionlink = '';
    $contentdata->contentallversionlink = '';
    if ($contentdata->numberofversions > 1) {
        if ($contentdata->versionnumber > 1) {
            $contentpreviousversionlink = new moodle_url('/mod/openstudio/content.php', array(
                    'id' => $cmid,
                    'sid' => $contentdata->previousversionid,
                    'vuid' => $contentdata->userid,
                    'folderid' => $folderid,
                    'contentversion' => 1
            ));

            $contentdata->contentpreviousversionlink = $contentpreviousversionlink->out(false);
        }

        if ($contentdata->versionnumber < $contentdata->numberofversions) {
            $contentnextversionlink = new moodle_url('/mod/openstudio/content.php', array(
                    'id' => $cmid,
                    'sid' => $contentdata->nextversionid,
                    'vuid' => $contentdata->userid,
                    'folderid' => $folderid,
                    'contentversion' => 1
            ));

            $contentdata->contentnextversionlink = $contentnextversionlink->out(false);
        }

        $contentallversionlink = new moodle_url('/mod/openstudio/view.php', array(
                'id' => $cmid,
                'sid' => $contentdata->contentid,
                'vid' => content::VISIBILITY_PRIVATE_PINBOARD,
                'contentversion' => 1
        ));

        $contentdata->contentallversionlink = $contentallversionlink->out(false);
    }

    $contentdataname = get_string('contentversiontitle', 'openstudio',
            array('versionnumber' => $contentdata->versionnumber, 'numberofversions' => $contentdata->numberofversions));

} else {
    // Returns all the values from the array and indexes the array numerically.
    // We need this because mustache requires it.
    $contentdata->contentversions = array_values($contentandversions->contentversions);

    $contentdata->iscontentversion = false;
    $contentdataname = $contentdata->name;
    if ($contentdata->l3name) {
        $contentdataname = $contentdata->l3name;
    }
}

$contentdata->contentdataname = $contentdataname;

// Get page url.
$pageurl = util::get_current_url();

// Render page header and crumb trail.
$pagetitle = $pageheading = get_string('pageheader', 'openstudio',
    array('cname' => $course->shortname, 'cmname' => $cm->name, 'title' => $contentdataname));
util::page_setup($PAGE, $pagetitle, $pageheading, $pageurl, $course, $cm);

// Is the content mine?
$permissions->contentismine = $contentdata->isownedbyviewer;

$contentisinpinboard = false;
if ($contentdata->levelid == 0) {
    $contentisinpinboard = true;
}

$contentdatavisibilitycontext = $contentdata->visibilitycontext;
$crumbarray = array();
switch ($contentdatavisibilitycontext) {
    case content::VISIBILITY_MODULE:
        $vid = content::VISIBILITY_MODULE;
        $areaurl = new moodle_url('/mod/openstudio/view.php',
                array('id' => $cm->id, 'vid' => content::VISIBILITY_MODULE));
        $areaurlname = get_string('navmymodule', 'openstudio');
        break;

    case content::VISIBILITY_GROUP:
        $vid = content::VISIBILITY_GROUP;
        $areaurl = new moodle_url('/mod/openstudio/view.php',
                array('id' => $cm->id, 'vid' => content::VISIBILITY_GROUP));
        $areaurlname = get_string('navmygroup', 'openstudio');
        break;

    case content::VISIBILITY_PRIVATE:
    default:
        $vid = content::VISIBILITY_PRIVATE;
        $areaurl = new moodle_url('/mod/openstudio/view.php',
                array('id' => $cm->id, 'vid' => content::VISIBILITY_PRIVATE));
        $areaurlname = get_string('navactivities', 'openstudio');
        break;
}

// Only show the pinboard link if the slot is mine.
if ($permissions->contentismine && $contentisinpinboard) {
    $vid = content::VISIBILITY_PRIVATE_PINBOARD;
    $areaurl = new moodle_url('/mod/openstudio/view.php',
            array('id' => $cm->id, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1));
    $areaurlname = get_string('navmypinboard', 'openstudio');
    $crumbarray[$areaurlname] = $areaurl->out(false);
} else {
    $pageview = 'activities';
    $crumbarray[$areaurlname] = $areaurl->out(false);
    if (!$permissions->contentismine) {
        $crumbkey = get_string('profileswork', 'openstudio', array('name' => $contentdata->firstname));
        $crumbarray[$crumbkey] = new moodle_url(
                '/mod/openstudio/view.php',
                array('id' => $cm->id,
                        'vid' => content::VISIBILITY_WORKSPACE,
                        'vuid' => $contentdata->userid));
    }
}
// Generate stream html.
$renderer = $PAGE->get_renderer('mod_openstudio');
$PAGE->set_button($renderer->searchform($theme, $vid, $id));
if (!$permissions->contentismine) {
    $contentdata->reportabuselink = util::render_report_abuse_link(
        'openstudio', $permissions->activecmcontextid, 'content', $contentdata->id,
        $pageurl, $pageurl, $permissions->activeuserid);
}

$contentdata->vid = $vid;
$contentdata->placeholdertext = $areaurlname;
$contentdata->folderid = $folderid;

$contentdata->folderid = $folderid;
$contentdata->isfoldercontent = false;
if ($folderid) {
    $folderdata = content::get($folderid);
    $folderdata = lock::determine_lock_status($folderdata);
    $contentdetails = renderer_utils::content_details($cmid, $permissions, $contentdata, $iscontentversion);
    if ($contentdetails->contentdataname) {
        $contentdataname = $contentdetails->contentdataname;
    }
    if ($folderdata) {
        $contentdata->name = $folderdata->name;
        $folderedit = new moodle_url('/mod/openstudio/contentedit.php',
                array('id' => $cm->id, 'lid' => 0, 'sid' => $folderdata->id, 'type' => content::TYPE_FOLDER));
        $contentdata->folderedit = $folderedit->out(false);
        $contentdata->isfoldercontent = true;
        $contentdata->folderid = $folderdata->id;
        $contentdata->containingfolderlocktype = ($folderdata->locktype);
        $folderoverview = new moodle_url('/mod/openstudio/folder.php',
                        array('id' => $id, 'sid' => $folderid, 'lid' => $contentdata->levelid,
                                 'vuid' => $contentdata->userid));
        $crumbarray[$contentdata->name] = $folderoverview;
        $contentdata->foldereditenable = ($permissions->addcontent && $USER->id == $folderdata->userid)
                || $permissions->managecontent;
        $contentdata->folderlinkoverview = $folderoverview;
    }
}

$crumbarray[$contentdataname] = $pageurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);
$config = [
        'paths' => [
                'ansi_up' => (new moodle_url('/mod/openstudio/js/ansi_up.min'))->out(false),
                'marked' => (new moodle_url('/mod/openstudio/js/marked.min'))->out(false),
                'es5-shim' => (new moodle_url('/mod/openstudio/js/es5-shim.min'))->out(false),
                'notebook' => (new moodle_url('/mod/openstudio/js/notebook.min'))->out(false),
                'prism' => (new moodle_url('/mod/openstudio/js/prism.min'))->out(false),
        ],
        'shim' => [
                'ansi_up' => ['exports' => 'ansi_up'],
                'marked' => ['exports' => 'marked'],
                'es5-shim' => ['exports' => 'es5-shim'],
                'notebook' => [
                        'deps' => ['marked'], 'exports' => 'notebook',
                ],
                'prism' => ['exports' => 'Prism'],
        ],
];
$requirejs = 'require.config(' . json_encode($config) . ')';
$PAGE->requires->js_amd_inline($requirejs);
$PAGE->requires->strings_for_js(
    array('contentactionarchivepost', 'modulejsdialogcancel', 'archivedialogheader',
        'modulejsdialogcontentarchiveconfirm', 'deletearchiveversionheader', 'deletearchiveversionheaderconfirm'),
    'mod_openstudio');
$PAGE->requires->js_call_amd('mod_openstudio/previewipynb', 'init');

// Update flag and tracking.
$tracking = tracking::READ_CONTENT;
$logaction = 'content_viewed';
if ($iscontentversion) {
    $tracking = tracking::READ_CONTENT_VERSION;
    $logaction = 'contentversion_viewed';
}

flags::toggle($contentdata->id, flags::READ_CONTENT, 'on', $USER->id, $contentdata->id);
tracking::log_action($contentdata->id, $tracking, $USER->id);

// Note: This header statement is needed because the slot form data contains
// object and script code and browsers like webkit thinks this is a cross-site
// scripting issue and refuses to loaded embbedded video players.
// Given we clean out all the user generated content, there should be no security
// risk of disabling the security check for this page.
header('X-XSS-Protection: 0');

// Update flag and tracking.
flags::toggle(
    $contentdata->id, flags::READ_CONTENT, 'on', $USER->id, $contentdata->id);
tracking::log_action($contentdata->id, flags::READ_CONTENT, $USER->id);

$html = $renderer->siteheader(
    $coursedata, $permissions, $theme, $cm->name, '', '');

echo $OUTPUT->header(); // Header.

echo $html;

echo $renderer->content_page($cm, $permissions, $contentdata, $cminstance); // Content detail.

$PAGE->requires->js_call_amd('mod_openstudio/contentpage', 'init');

// Finish the page.
echo $OUTPUT->footer();
