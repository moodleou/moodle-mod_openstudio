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
 * Group instance of openstudio
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\stream;
use mod_openstudio\local\util;
use mod_openstudio\local\api\flags;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/api/apiloader.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$groupid = optional_param('groupid', 0, PARAM_INT); // Group id to filter against.

// Page init and security checks.
$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;

require_login($course, true, $cm);

// Terms and conditions check.
util::honesty_check($id);

// Need to have view or managecontent capabilities.
if (!$permissions->managecontent) {
    require_capability('mod/openstudio:viewothers', $mcontext);
    require_capability('mod/openstudio:view', $mcontext);
}

// Get page url.
$pageurl = util::get_current_url();

$vid = optional_param('vid', content::VISIBILITY_MODULE, PARAM_INT);
if (! in_array($vid, array(content::VISIBILITY_GROUP, content::VISIBILITY_MODULE))) {
    $vid = content::VISIBILITY_MODULE;
}

// Get first group when my module disabled.
if (!$permissions->feature_module && !$groupid) {
    $vid = content::VISIBILITY_GROUP;

    $grouplist = studio_api_group_list(
            $permissions->activecid, $permissions->groupingid,
            $permissions->activeuserid, $permissions->groupmode);

    if (!empty($grouplist)) {
        $cnt = 0;
        foreach ($grouplist as $group) {
            if ($cnt == 0) {
                $groupid = $group->groupid;
                break;
            }
        }
    }
}

switch($vid) {
    case content::VISIBILITY_GROUP:
        $vid = content::VISIBILITY_GROUP;
        $filterflag = stream::FILTER_PEOPLE_GROUP;
        break;
    case content::VISIBILITY_MODULE:
    default:
        $vid = content::VISIBILITY_MODULE;
        $filterflag = stream::FILTER_PEOPLE_MODULE;
        break;
}

$fsort = optional_param('fsort', stream::SORT_PEOPLE_ACTIVTY, PARAM_INT);
$osort = optional_param('osort', stream::SORT_DESC, PARAM_INT);
$sortflag = array('id' => $fsort, 'asc' => $osort);

$pagetitle = $pageheading = get_string('pageheader', 'openstudio',
        array('cname' => $course->shortname, 'cmname' => $cm->name,
                'title' => get_string('menupeople', 'openstudio')));

$peopledata = (object) array('people' => array(), 'total' => 0);

$peopledatatemp = studio_api_user_get_all($cminstance->id,
    $permissions->groupmode, $permissions->groupingid, $groupid,
    $filterflag, $fsort, $osort, null, 0, true);

if (!empty($peopledatatemp)) {
    $peopledata->total = $peopledatatemp->total;

    foreach ($peopledatatemp->people as $person) {

        $person->userpictureurl = new moodle_url('/user/pix.php/'.$person->id.'/f1.jpg');
        $person->userprogressdata = studio_api_user_get_activity_status($cminstance->id, $person->id);
        $person->userprogressdata['lastactivedate'] = date('j/m/y, h:i', $person->userprogressdata['lastactivedate']);
        $person->viewuserworkurl = '#';

        $flagsdata = studio_api_flags_get_user_flag_total($cminstance->id, $person->id);
        $flagscontentread = 0;

        if (array_key_exists(flags::READ_CONTENT, $flagsdata)) {
            $flagscontentread = $flagsdata[flags::READ_CONTENT]->count;
        }

        $person->flagscontentread = $flagscontentread;
        if ($person->userprogressdata['totalslots'] > 0) {
            $percentcompleted = round($person->userprogressdata['filledslots'] / $person->userprogressdata['totalslots'] * 100);
            $person->percentcompleted = $percentcompleted;
        }

        $peopledata->people[] = $person;
    }
}

// Sort action url.
$sortactionurl = new moodle_url('/mod/openstudio/people.php');
$sortactionurl = $sortactionurl->out(false);
$sortactionurl .= "?id={$id}";
$peopledata->sortactionurl = $sortactionurl ."&osort={$osort}" ."&fsort={$fsort}";

$nextosort = 1 - $osort;
$sortactionurl .= "&vid={$vid}";
$sortactionurl .= "&groupid={$groupid}";
$peopledata->nextsortactionurl = $sortactionurl ."&osort={$nextosort}";

$sortbydate = false;
$sortbyusername = false;
switch ($fsort) {
    case 2:
        $sortbyusername = true;
        break;
    case 1:
    default:
        $sortbydate = true;
        break;
}

$sortasc = false;
$sortdesc = false;
switch ($osort) {
    case 1:
        $sortasc = true;
        break;
    case 0:
    default:
        $sortdesc = true;
        break;
}

$peopledata->sortbydate = $sortbydate;
$peopledata->sortbyusername = $sortbyusername;
$peopledata->sortasc = $sortasc;
$peopledata->sortdesc = $sortdesc;
$peopledata->selectedgroupid = $groupid;

// Render page header and crumb trail.
util::page_setup($PAGE, $pagetitle, $pageheading, $pageurl, $course, $cm);

$PAGE->requires->js_call_amd('mod_openstudio/peoplepage', 'init');

// Generate stream html.
$renderer = $PAGE->get_renderer('mod_openstudio');
$PAGE->set_button($renderer->searchform($theme, $vid));

$html = $renderer->siteheader(
    $coursedata, $permissions, $theme, $cm->name, '', content::VISIBILITY_PEOPLE);

echo $OUTPUT->header(); // Header.

echo $html;

echo $renderer->people_page($permissions, $peopledata); // Body.

// Finish the page.
echo $OUTPUT->footer();