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

use mod_openstudio\local\util;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/api/apiloader.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;

require_login($course, true, $cm);

require_capability('mod/openstudio:view', $mcontext);

// Get page url.
$pageurl = util::get_current_url();

// Render page header and crumb trail.
util::page_setup($PAGE, '', '', $pageurl, $course, $cm);

// Generate stream html.
$renderer = $PAGE->get_renderer('mod_openstudio');
$vid = optional_param('vid', -1, PARAM_INT);
$PAGE->set_button($renderer->searchform($theme, $vid));

$html = $renderer->siteheader(
    $coursedata, $permissions, $theme, $cm->name, '', $vid);

echo $OUTPUT->header(); // Header.

echo $html;

// Finish the page.
echo $OUTPUT->footer();
