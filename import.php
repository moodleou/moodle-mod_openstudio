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
use mod_openstudio\local\api\content;
use mod_openstudio\local\api\import;
use mod_openstudio\local\forms\import_upload_form;

require_once(__DIR__ . '/../../config.php');

// Init page.
$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;
$vid = content::VISIBILITY_PRIVATE_PINBOARD;

require_login($course, true, $cm);
require_capability('mod/openstudio:view', $mcontext);
// Require capability import for current user.
require_capability('mod/openstudio:import', $mcontext, $USER->id);

// Terms and conditions check.
util::honesty_check($id);

// Render page header and crumb trail.
$url = new moodle_url('/mod/openstudio/import.php', array('id' => $id));
$pageurl = util::get_current_url();
$mypinboardurl = new moodle_url('/mod/openstudio/view.php', array('id' => $id, 'vid' => $vid));
$crumbarray = array(
        get_string('menumycontent', 'mod_openstudio') => $mypinboardurl,
        get_string('navmypinboard', 'mod_openstudio') => $mypinboardurl
);
$pagetitle = $course->shortname . ' - ' . $cm->name . ' -  ' .
        get_string('importheaderandtrail', 'mod_openstudio');
$pageheading = $course->shortname . ' - ' . $cm->name . ' - '. get_string('importheaderandtrail', 'openstudio');

util::page_setup($PAGE, $pagetitle, $pageheading, $pageurl, $course, $cm);
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

// Upload Import form.
$formerror = null;
$formoptions = array(
    'id' => $id,
    'max_bytes' => $cminstance->contentmaxbytes
);
$uform = new import_upload_form($url->out(false), $formoptions,
        'post', '', array('class' => 'unresponsive'));

if ($uform->is_submitted() && $uformdata = $uform->get_submitted_data()) {
    // Let's process the form.
    if ($uformdata->importfile > 0) {
        // Because we are dealing with potentially large file upoads,
        // we up memory limit when processing content creation and updates.
        raise_memory_limit(MEMORY_EXTRA);

        // Get draft files.
        $draftfiles = file_get_drafarea_files($uformdata->importfile);

        // Check if there is a file uploaded.
        if (count($draftfiles->list) > 0) {
            try {
                // Get stored file instance from draft file.
                $fs = get_file_storage();
                $storedfile = $fs->get_file(context_user::instance($USER->id)->id, 'user',
                        'draft', $uformdata->importfile, '/', $draftfiles->list[0]->filename);

                if ($storedfile) {
                    // Extract files.
                    $zipcontents = import::get_archive_contents($storedfile);

                    if (!is_null($zipcontents)) {
                        if (import::check_import_limit($cm->instance, $USER->id, $zipcontents['files'])) {
                            if (import::import_files($zipcontents, $cm)) {
                                // Delete draft file.
                                $storedfile->delete();
                                // Log action.
                                util::trigger_event($cm->id, 'content_imported', '', util::get_page_name_and_params(true));
                                // Redirect to view page.
                                redirect($mypinboardurl->out(false));
                            }
                        } else {
                            $formerror = get_string('import:erroroverload', 'openstudio');
                        }
                    } else {
                        $formerror = get_string('import:errorfile', 'openstudio');
                    }
                }
            } catch (Exception $e) {
                // Do nothing
                redirect($url->out(false));
            }
        }
    }
}

// Render page.
$renderer = $PAGE->get_renderer('mod_openstudio');
$PAGE->set_button($renderer->searchform($theme, $vid, $id));

$html = $renderer->siteheader(
    $coursedata, $permissions, $theme, $cm->name, '', $vid);

echo $OUTPUT->header(); // Header.

echo $html;
echo $renderer->import_page($uform, $formerror);

echo $OUTPUT->footer();

util::trigger_event($cm->id, 'import_viewed', '', util::get_page_name_and_params(true));
