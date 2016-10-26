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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/openstudio/lib.php');

$id = required_param('id', PARAM_INT); // Course id.

if (! ($course = $DB->get_record('course', array('id' => $id)))) {
    print_error('invalidcourseid');
}

require_login($course);

$context = context_course::instance($course->id);

// Get required strings.
$strstudios = get_string("modulenameplural", "openstudio");
$strstudio = get_string("modulename", "openstudio");

$PAGE->set_course($course); // Sets up global $COURSE.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/openstudio/index.php', array('id' => $id));
$PAGE->set_title($strstudios);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strstudios, "index.php?id=$course->id");

$renderer = $PAGE->get_renderer('mod_openstudio');

// Print page header.
echo $renderer->header();

// Get all the appropriate data.
if (!$studios = get_all_instances_in_course("openstudio", $course)) {
    notice("There are no studios", "../../course/view.php?id=$course->id");
    die;
}

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
}

// Print the list of instances.
$timenow = time();
$strname = get_string('name');
$strsectionname = get_string('sectionname', 'format_' . $course->format);
$strdescription = get_string('description');

$table = new html_table();

if ($usesections) {
    $table->head = array($strsectionname, $strname, $strdescription);
} else {
    $table->head = array($strname, $strdescription);
}

foreach ($studios as $studio) {
    $linkcss = null;
    if (!$studio->visible) {
        $linkcss = array('class' => 'dimmed');
    }
    $link = html_writer::link(
        new moodle_url('/mod/openstudio/view.php',
            array('id' => $studio->coursemodule)), $studio->name, $linkcss);

    if ($usesections) {
        $table->data[] = array(
            get_section_name($course, $sections[$studio->section]), $link, $studio->intro);
    } else {
        $table->data[] = array($link, $studio->intro);
    }
}

echo html_writer::table($table);

// Print page footer.
echo $renderer->footer();
