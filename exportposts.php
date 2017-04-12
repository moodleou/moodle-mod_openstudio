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
 * @package   mod_openstudio
 * @copyright 2017 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_openstudio\local\api\export;
use mod_openstudio\local\api\stream;
use mod_openstudio\local\util;
use mod_openstudio\local\api\content;
use mod_openstudio\local\api\folder;

$id = required_param('id', PARAM_INT); // Course_module ID.
$vid = required_param('vid', PARAM_INT); // Visibility.
$contentids = explode(',', required_param('contentids', PARAM_TEXT)); // Content IDs.

// Page init and security checks.
$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$theme = $coursedata->theme;
require_login($course, true, $cm);

// Set up page and breadcrumb.
$pagetitle = $pageheading = 'Export posts';
$pageurl = util::get_current_url();
$exportpageurl = new moodle_url('/mod/openstudio/view.php', array('id' => $id, 'vid' => $vid));
util::page_setup($PAGE, $pagetitle, $pageheading, $pageurl, $course, $cm);
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, array(
    get_string('menumycontent', 'mod_openstudio') => $exportpageurl,
    get_string('navmypinboard', 'mod_openstudio') => $exportpageurl));

// Get contents.
$contentdatatemp = stream::get_contents_by_ids($USER->id, $contentids);
$contentdata = (object) array('contents' => array());
$index = 1;
$folderitemfilesizes = array();
foreach ($contentdatatemp as $content) {
    $contentids[] = $content->id;

    // Calculate folder size.
    if ($content->contenttype == content::TYPE_FOLDER) {
        // Get content IDs inside folder.
        $foldercontentids = array();
        $foldercontenttemp = folder::get_contents($content->id);
        foreach($foldercontenttemp as $folderitem) {
            $foldercontentids[] = $folderitem->id;
        }

        // Calculate content size with content type is file.
        $rs = export::get_files($cminstance->id, $USER->id, null, $foldercontentids, 0, count($foldercontentids));
        $folderitemfilesizes[$content->id] = 0;
        foreach ($rs as $r) {
            $folderitemfilesizes[$content->id] += $r->filesize;
        }
    }

    $content->icon = util::get_content_file_icon($content->contenttype);

    if ($content->levelid == 0) {
        $placeholdertext = $theme->themepinboardname;
    } else {
        $placeholdertext = $theme->themestudioname;
    }

    $content->location = $placeholdertext;
    $content->index = $index ++;

    $contentdata->contents[] = $content;
}

// Calculate content size with content type is file.
$rs = export::get_files($cminstance->id, $USER->id, null, $contentids, 0, count($contentids));
$contentfilesizes = array();
foreach ($rs as $r) {
    $contentfilesizes[$r->id] = $r->filesize;
}

foreach ($contentdata->contents as $key => $content) {
    if (isset($contentfilesizes[$content->id])) {
        $contentdata->contents[$key]->size = util::human_filesize(@$contentfilesizes[$content->id]);
    } else if (isset($folderitemfilesizes[$content->id])) {
        $contentdata->contents[$key]->size = util::human_filesize(@$folderitemfilesizes[$content->id]);
    } else {
        $contentdata->contents[$key]->size = 0;
    }
}

// Render page.
$renderer = $PAGE->get_renderer('mod_openstudio');
$PAGE->set_button($renderer->searchform($theme, $vid));

echo $OUTPUT->header(); // Header.

echo $renderer->exportposts($contentdata); // Body.

$PAGE->requires->js_call_amd('mod_openstudio/export', 'init', [[
        "id" => $id,
        "vid" => $vid,
        "contentids" => $contentids]]);

echo $OUTPUT->footer();

util::trigger_event($cm->id, 'export_viewed', '', util::get_page_name_and_params(true));
