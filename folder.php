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
 * Open studio folder overview
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_openstudio\local\api\content;
use mod_openstudio\local\util;
use mod_openstudio\local\api\contentversion;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\api\flags;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);
// Folder ID.
$folderid = optional_param('sid', 0, PARAM_INT);
// User id.
$userid = optional_param('vuid', $USER->id, PARAM_INT);
// Content level id.
$lid = optional_param('lid', 0, PARAM_INT);
$vid = optional_param('vid', -1, PARAM_INT);
$foldercontenttemplateid = optional_param('sstsid', null, PARAM_INT);
$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;

// Not setting page URL caused a debug warning.
$PAGE->set_url(new \moodle_url($FULLME));

require_login($course, true, $cm);

require_capability('mod/openstudio:view', $mcontext);

// Terms and conditions check.
util::honesty_check($id);

$returnurliferror = new moodle_url('/mod/openstudio/view.php', array('id' => $cm->id));

// Must prioritize folder ID from server rather than from GET, both for GET and POST method.
// When we submit this page again using method POST, we should get folder ID from this method.
// So the logic with $selectedposts below can have correct folder ID.
if ($folderid == 0) {
    $folderid = util::get_folder_id($cminstance, $userid, $lid);
}

// Save selected post to folder.
$selectedposts = optional_param('selectedposts', '', PARAM_RAW_TRIMMED);
if ($selectedposts && $folderid) {
    $contentdata = content::get($folderid);
    $contentrecords = array();
    $contents = explode(',', $selectedposts);
    $contentsinfolder = folder::get_contents($folderid);
    foreach ($contents as $itemid) {
        if (!array_key_exists($itemid, $contentsinfolder)) {
            $selectedcontentdata = content::get($itemid);
            if ($contentdata->userid == $selectedcontentdata->userid) {
                folder::collect_content($folderid, $itemid, $USER->id, null, true);
            } else {
                $setdataslotid = folder::collect_content($folderid, $itemid,
                    $USER->id, null);
            }
        }
    }
}

// Get content and content version data.
$showdeletedcontentversions = false;
if ($permissions->viewdeleted || $permissions->managecontent) {
    $showdeletedcontentversions = true;
}

// Get folder data.
$contentandversions = contentversion::get_content_and_versions($folderid, $USER->id, $showdeletedcontentversions);
$folderdata = lock::determine_lock_status($contentandversions->contentdata);

// Check the viewing user has permission to view content.
if (!util::can_read_content($cminstance, $permissions, $folderdata)) {
    throw new \moodle_exception('errornopermissiontoviewcontent', 'openstudio', $returnurliferror->out(false));
}

// Get page url.
$pageurl = util::get_current_url();

$folderdataname = $folderdata->name;

$folderisinpinboard = false;
if ($folderdata->levelid == 0) {
    $folderisinpinboard = true;
}

$folderdatavisibilitycontext = $folderdata->visibility;
$crumbarray = array();
switch ($folderdatavisibilitycontext) {
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
if (($folderdata->userid == $USER->id) && $showdeletedcontentversions) {
    $vid = content::VISIBILITY_PRIVATE_PINBOARD;
    $areaurl = new moodle_url('/mod/openstudio/view.php',
            array('id' => $cm->id, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1));
    $areaurlname = get_string('navmypinboard', 'openstudio');
    $crumbarray[$areaurlname] = $areaurl->out(false);
} else {
    $pageview = 'activities';
    $crumbarray[$areaurlname] = $areaurl->out(false);
    if ($folderdata->userid != $USER->id) {
        $crumbkey = get_string('profileswork', 'openstudio', array('name' => $folderdata->firstname));
        $crumbarray[$crumbkey] = new moodle_url(
                '/mod/openstudio/view.php',
                array('id' => $cm->id,
                        'vid' => content::VISIBILITY_WORKSPACE,
                        'vuid' => $folderdata->userid));
    }
}

$crumbarray[$folderdataname] = $pageurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);


// Get page url.
$pageurl = util::get_current_url();

// Render page header and crumb trail.
$pagetitle = $pageheading = get_string('pageheader', 'openstudio',
        array('cname' => $course->shortname, 'cmname' => $cm->name, 'title' => $folderdataname));
util::page_setup($PAGE, $pagetitle, $pageheading, $pageurl, $course, $cm);

flags::toggle($folderdata->id, flags::READ_CONTENT, 'on', $USER->id, $folderdata->id);

if (!empty($folderdata->levelid)) {
    $foldertemplate = \mod_openstudio\local\api\template::get_by_levelid($folderdata->levelid);
    if ($foldertemplate && empty($foldertemplate->additionalcontents)) {
        $folderdata->showaddsection = false;
    }
}
$config = [
    'paths' => [
        'leaflet' => (new moodle_url('/mod/openstudio/js/leaflet.min'))->out(false),
    ],
    'shim' => [
        'leaflet' => ['exports' => 'leaflet'],
    ],
];
$requirejs = 'require.config(' . json_encode($config) . ')';
$PAGE->requires->js_amd_inline($requirejs);
$PAGE->requires->js_call_amd('mod_openstudio/folderhelper', 'init');

// Require strings for folder browse posts.
$PAGE->requires->strings_for_js(
        array('folderbrowseposts', 'folderbrowsepostshint', 'folderbrowseremovepostfromselection'), 'mod_openstudio');

// Generate stream html.
$renderer = $PAGE->get_renderer('mod_openstudio');
$PAGE->set_button($renderer->searchform($theme, $vid, $id));

$html = $renderer->siteheader(
    $coursedata, $permissions, $theme, $cm->name, '', '');

echo $OUTPUT->header(); // Header.

echo $html;

echo $renderer->folder_page($cm, $permissions, $folderdata, $cminstance);

$PAGE->requires->js_call_amd('mod_openstudio/folderbrowseposts', 'init', [[
    'folderid' => $folderid,
    'cmid' => $cm->id]]);

$PAGE->requires->js_call_amd('mod_openstudio/contentpage', 'init');

// Finish the page.
echo $OUTPUT->footer();
