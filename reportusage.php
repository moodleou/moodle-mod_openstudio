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
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot . '/mod/openstudio/api/apiloader.php');

use mod_openstudio\local\util;
use mod_openstudio\local\api\levels;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$studioid = optional_param('studioid', 0, PARAM_INT);

// Page init and security checks.
$coursedata = util::render_page_init($id);
$cm = $coursedata->cm;
$course = $coursedata->course;
$context = $coursedata->mcontext;

require_capability('mod/openstudio:managecontent', $context);

// Process and Generate HTML.
$renderer = $PAGE->get_renderer('mod_openstudio');

// Setup page and theme settings.
$strpagetitle = $strpageheading = $course->shortname . ': ' . $cm->name
        . ' - ' . get_string('manageblocks', 'openstudio');
$strpageurl = new moodle_url('/mod/openstudio/reportusage.php', array('id' => $cm->id));

// Render page header and crumb trail.
util::page_setup($PAGE, $strpagetitle, $strpageheading, $strpageurl, $course, $cm);

// Setup page crumb trail.
$repotusageurl = new moodle_url('/mod/openstudio/reportusage.php', array('id' => $cm->id));
$crumbarray[get_string('navadminusagereport', 'openstudio')] = $repotusageurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

// Output HTML.
echo $OUTPUT->header(); // Header.

$studioid = util::get_studioid_from_coursemodule($cm->id);

$summarydata = studio_api_reports_get_activity_summary($course->id, $studioid);
$contentdata = studio_api_reports_get_total_slots($studioid);
$flagdata = studio_api_reports_get_total_flags($studioid);
$storage = studio_api_reports_get_total_storage($studioid);
$activitylog = studio_api_reports_get_activity_log($course->id, $cm->id);

$contentdataactivity = array();
foreach ($contentdata->slotactivity as $value) {
    $contentdataactivity[] = array(
            'levelname' => levels::get_name($value->levelcontainer, $value->levelid),
            'contenttypename' => studio_api_language_get_contenttype_name($value->contenttype),
            'totalusers' => $value->totalusers,
            'totalcontents' => $value->totalslots
    );
}

$contentdatavisbility = array();
foreach ($contentdata->slotvisbility as $value) {
    $contentdatavisbility[] = array(
            'levelname' => levels::get_name($value->levelcontainer, $value->levelid),
            'visibilitytypename' => studio_api_language_get_visibilitytype_name($value->visibility),
            'totalcontents' => $value->totalslots
    );
}

$flagdatacontentstop20 = array();
foreach ($flagdata->slotstop20 as $value) {
    $flagdatacontentstop20[] = array(
            'levelname' => levels::get_name($value->levelcontainer, $value->levelid),
            'contentname' => (trim($value->name) == '') ? 'No name' : $value->name,
            'ownername' => $value->firstname." ".$value->lastname,
            'totalcontents' => $value->totals
    );
}
$flagdata->flagdatacontentstop20 = $flagdatacontentstop20;

$flagdatacontents = array();
foreach ($flagdata->slots as $value) {
    $flagdatacontents[] = array(
            'flagtypename' => studio_api_language_get_flagtype_name($value->flagid ),
            'totalusers' => $value->totalusers,
            'totalcontents' => $value->totals
    );
}
$flagdata->flagdatacontents = $flagdatacontents;

$flagdatacontentstrackedtop20 = array();
foreach ($flagdata->slotstrackedtop20 as $value) {
    $flagdatacontentstrackedtop20[] = array(
            'levelname' => levels::get_name($value->levelcontainer, $value->levelid),
            'contentname' => (trim($value->name) == '') ? 'No name' : $value->name,
            'totalcontents' => $value->totals
    );
    $levelname = levels::get_name($value->levelcontainer, $value->levelid);
    $contentname = (trim($value->name) == '') ? 'No name' : $value->name;
}
$flagdata->flagdatacontentstrackedtop20 = $flagdatacontentstrackedtop20;

$storagebycontent = round($storage->storagebyslot / 1024 / 1024, 2);
$storagebycontentversion = round($storage->storagebyslotversion / 1024 / 1024, 2);
$storagebythumbnail = round($storage->storagebythumbnail / 1024 / 1024, 2);
$storagebycomment = round($storage->storagebycomment / 1024 / 1024, 2);
$storagetotal = $storagebythumbnail + $storagebycontent + $storagebycontentversion + $storagebycomment;

$summarydata->storagebycontentmb = $storagebycontent;
$summarydata->storagebycontentgb = round($storagebycontent / 1024, 2);

$summarydata->storagebycontentversionmb = $storagebycontentversion;
$summarydata->storagebycontentversiongb = round($storagebycontentversion / 1024, 2);

$summarydata->storagebythumbnailmb = $storagebythumbnail;
$summarydata->storagebythumbnailgb = round($storagebythumbnail / 1024, 2);

$summarydata->storagebycommentmb = $storagebycomment;
$summarydata->storagebycommentgb = round($storagebycomment / 1024, 2);

$summarydata->storagetotalmb = $storagetotal;
$summarydata->storagetotalgb = round($storagetotal / 1024, 2);

$storagecontentsbymimetype = array();
foreach ($storage->slotsbymimetype as $value) {
    $storagecontentsbymimetype[] = array(
            'storageusedmb' => round($value->storage / 1024 / 1024, 2),
            'storageusedgb' => round($value->storage / 1024 / 1024 / 1024, 2),
            'mimetype' => $value->mimetype,
            'totals' => $value->totals,
    );
}
$storage->storagecontentsbymimetype = $storagecontentsbymimetype;

$storagestoragebyuser = array();
foreach ($storage->storagebyuser as $value) {
    $storagestoragebyuser[] = array(
            'storageusedmb' => round($value->storage / 1024 / 1024, 2),
            'storageusedgb' => round($value->storage / 1024 / 1024 / 1024, 2),
            'owner' => $value->firstname. " ". $value->lastname,
            'totals' => $value->totals,
    );
}
$storage->storagestoragebyuser = $storagestoragebyuser;

$activitylog = studio_api_reports_get_activity_log($course->id, $cm->id);

$html = $renderer->reportusage($summarydata, $contentdataactivity, $contentdatavisbility, $flagdata, $storage, $activitylog);

echo $html;

// Finish the page.
echo $OUTPUT->footer();