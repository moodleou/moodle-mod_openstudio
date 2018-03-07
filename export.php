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
 * Create a portfolio export link from id and contentids and forward that one.
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_openstudio\local\api\export;
use mod_openstudio\local\util;

require_once(__DIR__.'/../../config.php');
require_once("$CFG->libdir/portfoliolib.php");

$id = optional_param('id', 0, PARAM_INT); // Course_module ID
$contentids = explode(',', optional_param('contentids', '', PARAM_TEXT)); // Content ids seperated by comma.

// Page init and security checks.
$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;

require_login($course, true, $cm);
// Require capability export for current user.
require_capability('mod/openstudio:export', $mcontext, $USER->id);

// Need to have view or managecontent capabilities.
if (!$permissions->managecontent) {
    require_capability('mod/openstudio:export', $mcontext);
}

$encodedcontentids = export::encode_ids($contentids); // This should contain the IDs of the content posts we are exporting.
$button = new portfolio_add_button();
$button->set_callback_options('openstudio_portfolio_caller',
        ['studioid' => $cm->instance, 'contentids' => $encodedcontentids], 'mod_openstudio');

$url = $button->to_html(PORTFOLIO_ADD_FAKE_URL);

util::trigger_event($cm->id, 'content_exported', '', util::get_page_name_and_params(true));
redirect($url);
