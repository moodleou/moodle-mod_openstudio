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
use mod_openstudio\local\renderer_utils;
use mod_openstudio\local\api\contentversion;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\comments;
use mod_openstudio\local\forms\comment_form;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/api/apiloader.php');

// Course_module ID.
$id = optional_param('id', 0, PARAM_INT);
// Folder id.
$folderid = optional_param('folderid', 0, PARAM_INT);
// Content id.
$contentid = optional_param('sid', 0, PARAM_INT);
// User id.
$userid = optional_param('vuid', $USER->id, PARAM_INT);
$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;
$folderid = null;

require_login($course, true, $cm);

require_capability('mod/openstudio:view', $mcontext);

// Terms and conditions check.
util::honesty_check($id);

$returnurliferror = new moodle_url('/mod/openstudio/view.php', array('id' => $cm->id));

// Get content and content version data.
$showdeletedcontentversions = false;
if ($permissions->viewdeleted || $permissions->managecontent) {
    $showdeletedcontentversions = true;
}
$contentandversions = contentversion::get_content_and_versions($contentid, $USER->id, $showdeletedcontentversions);

$contentdata = studio_api_lock_determine_lock_status($contentandversions->contentdata);

// Returns all the values from the array and indexes the array numerically.
// We need this because mustache requires it.
$contentdata->contentversions = array_values($contentandversions->contentversions);

if ($contentdata === false) {
    return redirect($returnurliferror->out(false));
}

// Check the viewing user has permission to view content.
if (!util::can_read_content($cminstance, $permissions, $contentdata, $folderid)) {
    print_error('errornopermissiontoviewcontent', 'openstudio', $returnurliferror->out(false));
}

$contentdataname = $contentdata->name;
if ($contentdata->l3name) {
    $contentdataname = $contentdata->l3name;
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

// Get content comments in order.
$commenttemp = comments::get_for_content($contentdata->id, $USER->id);
$comments = [];
$commentthreads = [];
$contentdata->comments = [];

if ($commenttemp) {
    foreach ($commenttemp as $key => $comment) {

        // Check comment attachment.
        if ($file = comments::get_attachment($comment->id)) {
            $comment->commenttext .= renderer_utils::get_media_filter_markup($file);
        }

        // Filter comment text.
        $comment->commenttext = format_text($comment->commenttext);

        $user = studio_api_user_get_user_by_id($comment->userid);
        $comment->fullname = fullname($user);

        // User picture.
        $picture = new user_picture($user);
        $comment->userpictureurl = $picture->get_url($PAGE)->out(false);

        // Check delete capability.
        $comment->deleteenable = ($permissions->activeuserid == $comment->userid && $permissions->addcomment) ||
            $permissions->managecontent;

        // Check report capability.
        $comment->reportenable = ($permissions->activeuserid != $comment->userid) && !$permissions->managecontent;

        $comment->timemodified = userdate($comment->timemodified, get_string('formattimedatetime', 'openstudio'));

        if (is_null($comment->inreplyto)) { // This is a new comment.

            $comments[$key] = $comment;

        } else { // This is a reply.

            $parentid = $comment->inreplyto;
            if (!isset($commentthreads[$parentid])) {
                $commentthreads[$parentid] = [];
            }
            $commentthreads[$parentid][] = $comment;
        }
    }
    // Returns all the values from the array and indexes the array numerically.
    // We need this because mustache requires it.
    $contentdata->comments = array_values($comments);
}

// Attach replies to comments.
foreach ($contentdata->comments as $key => $value) {
    // There is a comment stream for this comment.
    if (isset($commentthreads[$value->id])) {
        $contentdata->comments[$key]->replies = $commentthreads[$value->id];
    }
}

$contentdata->emptycomment = (empty($contentdata->comments));

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
$crumbarray[$contentdataname] = $pageurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);
// Generate stream html.
$renderer = $PAGE->get_renderer('mod_openstudio');
$PAGE->set_button($renderer->searchform($theme, $vid));

$contentdata->vid = $vid;
$contentdata->placeholdertext = $areaurlname;

$PAGE->requires->js_call_amd('mod_openstudio/contentpage', 'init');

$contentdata->isfoldercontent = false;
if ($folderid) {
    $folderdata = content::get($folderid);
    if ($folderdata) {
        $contentdata->isinlockedfolder = ($folderdata->locktype == lock::ALL);
    }
}

// Note: This header statement is needed because the slot form data contains
// object and script code and browsers like webkit thinks this is a cross-site
// scripting issue and refuses to loaded embbedded video players.
// Given we clean out all the user generated content, there should be no security
// risk of disabling the security check for this page.
header('X-XSS-Protection: 0');

// Add form after page_setup.
$commentform = new comment_form(null, array(
        'id' => $id,
        'cid' => $contentdata->id,
        'folderid' => property_exists($contentdata, 'ssid') ? $contentdata->ssid : 0,
        'max_bytes' => $cminstance->contentmaxbytes,
        'attachmentenable' => $permissions->feature_contentcommentusesaudio));
$contentdata->commentform = $commentform->render();

// Update flag and tracking.
flags::toggle(
    $contentdata->id, flags::READ_CONTENT, 'on', $USER->id, $contentdata->id);
studio_api_tracking_log_action($contentdata->id, flags::READ_CONTENT, $USER->id);

$html = $renderer->siteheader(
    $coursedata, $permissions, $theme, $cm->name, '', $vid);

echo $OUTPUT->header(); // Header.

echo $html;

echo $renderer->content_page($cm->id, $permissions, $contentdata, $cminstance->id); // Content detail.

// Finish the page.
echo $OUTPUT->footer();
