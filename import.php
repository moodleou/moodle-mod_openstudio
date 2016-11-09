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
 * @package mod_studio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_openstudio\local\util;
use mod_openstudio\local\api\content;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/studio/locallib.php');
require_once($CFG->dirroot . '/mod/studio/importexport_form.php');

$id = required_param('id', PARAM_INT); // Course module id.
$funct = optional_param('funct', '', PARAM_ALPHA);
$resetsession = optional_param('resetsession', false, PARAM_BOOL);

// Page init and security checks.
$coursedata = studio_internal_render_page_init($id, array('mod/studio:view', 'mod/studio:addcontent'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$context = $mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;
$studioid = $coursedata->cminstance->id;

// Set upload file size limit.
define('STUDIO_SLOT_MAXBYTES', $cminstance->slotmaxbytes);

// Terms and conditions check.
studio_internal_tandc_check($id);

if (!$permissions->feature_enableexportimport) {
    $returnurliferror = new moodle_url('/mod/studio/view.php', array('id' => $cm->id));
    print_error('errorinvalidrequest', 'studio', $returnurliferror->out(false));
}

if ($funct != '') {
    $result = array();

    if ($_SESSION['importfiles'] == '') {
        $pbslots = content::get_pinboard_total($studioid, $USER->id);
        $_SESSION['importfiles']['usedpbslots'] = $pbslots->used;
        $_SESSION['importfiles']['allowedpbslots'] = $pbslots->total;
        $_SESSION['importfiles']['remainingpbslots'] = $pbslots->available;
    }
    if ($funct == 'add') {
        if ($_SESSION['importfiles']['remainingpbslots'] > 0) {
            $_SESSION['importfiles']['usedpbslots'] = $_SESSION['importfiles']['usedpbslots'] + 1;
            $_SESSION['importfiles']['remainingpbslots'] = $_SESSION['importfiles']['remainingpbslots'] - 1;
            $result['status'] = true;
            $result['message'] = get_string('allowed_imports', 'studio', (object)$_SESSION['importfiles']);
            $result['remainingslots'] = $_SESSION['importfiles']['remainingpbslots'];
        } else {
            $result['status'] = false;
            $result['message'] = get_string('import_limit_exceeded', 'studio');
            $result['remainingslots'] = $_SESSION['importfiles']['remainingpbslots'];
        }
    }
    if ($funct == 'remove') {
        $_SESSION['importfiles']['usedpbslots'] = $_SESSION['importfiles']['usedpbslots'] - 1;
        $_SESSION['importfiles']['remainingpbslots'] = $_SESSION['importfiles']['remainingpbslots'] + 1;
        $result['status'] = true;
        $result['message'] = get_string('allowed_imports', 'studio', (object)$_SESSION['importfiles']);
        $result['remainingslots'] = $_SESSION['importfiles']['remainingpbslots'];
    }
    echo json_encode($result);

    // End processing here for ajax call.
    exit;
}

if ($resetsession == true) {
    $pbslots = content::get_pinboard_total($studioid, $USER->id);
    $_SESSION['importfiles']['usedpbslots'] = $pbslots->used;
    $_SESSION['importfiles']['allowedpbslots'] = $pbslots->total;
    $_SESSION['importfiles']['remainingpbslots'] = $pbslots->available;
    $result['status'] = true;
    $result['message'] = get_string('allowed_imports', 'studio', (object) $_SESSION['importfiles']);
    $result['remainingslots'] = $_SESSION['importfiles']['remainingpbslots'];
    echo json_encode($result);

    // End processing here for ajax call.
    exit;
}

$formoptions = array();
$formoptions['id'] = $id;

$_SESSION['importfiles'] = '';

// Page URL.
$url  = new moodle_url('/mod/studio/import.php', array('id' => $id));

studio_internal_render_page_defaults($PAGE, $course->shortname . ' - ' . $cm->name . ' -  ' .
        get_string('importheaderandtrail', 'studio'), $course->shortname . ' - ' .
        $cm->name . ' - '. get_string('importheaderandtrail', 'studio'),
        $url, $course, $cm);
$crumbarray = array();
studio_internal_render_page_crumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

// Start Processing.

// Upload Import form.
$uform = new mod_studio_import_upload_form($url->out(false), $formoptions,
        'post', '', array('class' => 'unresponsive'));
$displayu = false; // Display upload form.
$sform = new mod_studio_select_files_import_form($url->out(false), $formoptions,
        'post', '', array('class' => 'unresponsive'));
$displays = false; // Display selection form.

if (!$uform->is_submitted() && !$sform->is_submitted()) {
    $displayu = true;
    $PAGE->navbar->add('Import Slots');
}

if ($uform->is_submitted() && !$sform->is_submitted() && $uformdata = $uform->get_submitted_data()) {
    // Let's process the form.
    if ($uformdata->importfile > 0) {
        // Because we are dealing with potentially large file upoads,
        // we up memory limit when processing slot creation and updates.
        studio_raise_memory_limit();

        $PAGE->navbar->add(get_string('importheaderandtrail', 'studio'), $url->out(false));
        $PAGE->navbar->add(get_string('importtrail', 'studio'));
        $tempfilename = sha1(microtime().rand());
        // Check that our temp location exists.
        $exists = check_dir_exists(TEMPFOLDER, true, true);
        if ($exists && $uform->save_file('importfile', TEMPFOLDER . $tempfilename . '.zip')) {
            $zipcontents = studio_api_import_get_archive_contents($tempfilename . '.zip');
            // Let's list our files on the form with checkboxes.
            $formoptions['files'] = $zipcontents;
            $formoptions['filename'] = $tempfilename;
            $pbslots = content::get_pinboard_total($studioid, $USER->id);
            $formoptions['usedpbslots'] = $pbslots->used;
            $formoptions['allowedpbslots'] = $pbslots->total;
            $formoptions['remainingpbslots'] = $pbslots->available;
            $sform = new mod_studio_select_files_import_form($url->out(false), $formoptions,
                    'post', '', array('class' => 'unresponsive'));
            $displays = true;
        }
    }
}

if ($sform->is_submitted() && !$uform->is_submitted()) {
    // Because we are dealing with potentially large file upoads,
    // we up memory limit when processing slot creation and updates.
    studio_raise_memory_limit();

    // For some reason, get_data and get_submitted_data are empty, but the post as info,
    // so we will use that for now: $sformdata = $sform->get_submitted_data();.
    $files = required_param_array('file', PARAM_INT);
    $pathname = required_param_array('pathname', PARAM_RAW);
    $mtime = required_param_array('mtime', PARAM_INT);
    $isdirectory = required_param_array('is_directory', PARAM_RAW);
    $size = required_param_array('size', PARAM_INT);
    $location = required_param('location', PARAM_RAW);
    $filename = required_param('filename', PARAM_RAW);
    $keystoprocess = array();
    $allfiles = array();
    foreach ($files as $key => $selected) {
        if ($selected == 1) {
            $keystoprocess[] = $key;
        }
        $f = new stdClass();
        $f->index = $key;
        $f->pathname = $pathname[$key];
        $f->original_pathname = $pathname[$key];
        $f->mtime = $mtime[$key];
        $f->size = $size[$key];
        $f->is_directory = $isdirectory[$key];
        $allfiles['files'][] = $f;
    }
    $allfiles['location'] = $location;
    $imported = studio_api_import_files($allfiles, $keystoprocess, $studioid, $id);
    if ($imported) {
        // Write log.
        studio_internal_trigger_event($cm->id, 'content_imported', '',
                studio_internal_getpagenameandparams());

        // Delete the test file too.
        if (is_dir(TEMPFOLDER . $filename)) {
            studio_api_filesystem_rrmdir(TEMPFOLDER . $filename);
        }
        if (file_exists(TEMPFOLDER . $filename . '.zip')) {
            unlink(TEMPFOLDER . $filename . '.zip');
        }

        $rurl = new moodle_url('/mod/studio/view.php',
                array('id' => $id, 'vid' => STUDIO_VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1));
        return redirect($rurl->out(false));
    }
}

// Output Headers.
$renderer = $PAGE->get_renderer('mod_studio');
$htmltemp1 = $renderer->studio_render_siteheader(
        $cm->id, $permissions, $theme, $cminstance->sitename, '', STUDIO_VISIBILITY_PRIVATE);
$htmltemp1 .= $renderer->studio_render_navigation(
        $cm->id, $coursedata->permissions, $theme, 'work', 2, 'edit');
$htmltemp1 .= $renderer->studio_render_profile_bar(
        $course->id, $cm->id, $cminstance->id, $USER, $permissions);
$htmltemp1 .= $renderer->studio_render_blank_bar_top();
$htmltemp = $renderer->studio_render_header_wrapper($htmltemp1);

// Output main content.
$htmltemp .= html_writer::tag('h2', get_string('importheaderandtrail', 'studio'), array());

if ($displayu) {
    ob_start();
    $uform->display();
    $uformhtml = ob_get_contents();
    ob_end_clean();

    $htmltemp .= $uformhtml;
}

if ($displays) {
    ob_start();
    $sform->display();
    $sformhtml = ob_get_contents();
    ob_end_clean();

    $htmltemp .= $sformhtml;
}

// Output stream html with wrapper for theme header and footer.
$html = $renderer->studio_render_wrapper($htmltemp, 'work');

echo $renderer->studio_render_header(); // Header.
echo $html;
echo $renderer->footer(); // Footer.

studio_internal_trigger_event($cm->id, 'import_viewed', '',
        studio_internal_getpagenameandparams());
