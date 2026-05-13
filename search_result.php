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
 * Redirect endpoint for search result clicks. Logs the follow event then redirects.
 *
 * @package mod_openstudio
 * @copyright 2026 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_openstudio\event\search_result_followed;
use mod_openstudio\local\util;

$id = required_param('id', PARAM_INT);
$searchtoken = required_param('searchtoken', PARAM_ALPHANUMEXT);
$pos = required_param('pos', PARAM_INT);
$redirecturl = required_param('redirect', PARAM_URL);

$coursedata = util::render_page_init($id, ['mod/openstudio:view']);
$cm = $coursedata->cm;

// Reject redirects that don't belong to this Moodle install.
if (strpos($redirecturl, $CFG->wwwroot . '/') !== 0) {
    throw new moodle_exception('invalidparameter', 'debug');
}

$event = search_result_followed::create([
    'context' => context_module::instance($cm->id),
    'other' => [
        'searchtoken' => $searchtoken,
        'pos' => $pos,
        'url' => $redirecturl,
    ],
]);
$event->trigger();

redirect($redirecturl);
