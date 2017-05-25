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
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This page lists all the instances of studio in a particular course.
 *
 */

require_once(__DIR__ . '/../../config.php');

use mod_openstudio\local\util;
use mod_openstudio\local\api\honesty;
use mod_openstudio\local\forms\honesty_form;

$id = optional_param('id', 0, PARAM_INT); // Course module id.

// Page init and security checks.
$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$course = $coursedata->course;

$PAGE->set_course($course); // Sets up global $COURSE.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/openstudio/honesty.php', array('id' => $id));
$PAGE->set_title($course->fullname . ': '. get_string('openstudio', 'openstudio') . ': '
        . get_string('honestycrumb', 'openstudio'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('honestycrumb', 'openstudio'));

$renderer = $PAGE->get_renderer('mod_openstudio');

$urlparams = array('id' => $id);
$url = new moodle_url('/mod/openstudio/honesty.php', $urlparams);
$slotform = new honesty_form($url->out(false), array(),
        'post', '', array('class' => 'unresponsive'));

if ($slotform->is_cancelled()) {
    $url = new moodle_url('/course/view.php', array('id' => $course->id));
    return redirect($url->out(false));
} else if ($slotformdata = $slotform->get_data()) {
    $result = honesty::set($id, $USER->id, $seton = true);
    if ($result) {
        $url = new moodle_url('/mod/openstudio/view.php', $urlparams);
        return redirect($url->out(false));
    }
}

ob_start();
$slotform->display();
$honestyform = ob_get_contents();
ob_end_clean();

// Print page header.
echo $renderer->header();

echo $honestyform;

// Print page footer.
echo $renderer->footer();
