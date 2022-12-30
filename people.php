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
use mod_openstudio\local\api\filter;
use mod_openstudio\local\api\stream;
use mod_openstudio\local\api\group;
use mod_openstudio\local\util;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\user;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$groupid = optional_param('groupid', 0, PARAM_INT); // Group id to filter against.
$pagesize = mod_openstudio\local\util\defaults::STREAMPAGESIZE;
$pagedefault = 0;
$pagesize = optional_param('pagesize', $pagesize, PARAM_INT);
$pagestart = optional_param('page', $pagedefault, PARAM_INT);
if ($pagestart < 0) {
    $pagestart = 0;
}

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

if (!$permissions->feature_module && !$groupid) {
    $vid = content::VISIBILITY_GROUP;
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

// Sort options.
// Sort by is a combination of fsort + osort. If sort by is invalid, it will return null.
$sortby = optional_param('sortby', null, PARAM_INT);
$fsort = optional_param('fsort', stream::SORT_PEOPLE_ACTIVTY, PARAM_INT);
$osort = optional_param('osort', stream::SORT_DESC, PARAM_INT);
$sortbyparams = filter::get_sort_by_params($sortby);
if ($sortbyparams === null) {
    $sortby = null;
} else {
    $fsort = $sortbyparams['fsort'];
    $osort = $sortbyparams['osort'];
}
$sortflag = array('id' => $fsort, 'asc' => $osort);

$pagetitle = $pageheading = get_string('pageheader', 'openstudio',
        array('cname' => $course->shortname, 'cmname' => $cm->name,
                'title' => get_string('menupeople', 'openstudio')));

// Render page header and crumb trail.
util::page_setup($PAGE, $pagetitle, $pageheading, $pageurl, $course, $cm);
$renderer = util::get_renderer();

$peopledata = (object) array('people' => array(), 'total' => 0);

$peopledatatemp = user::get_all($cminstance->id,
    $permissions->groupmode, $permissions->groupingid, $groupid,
    $filterflag, $fsort, $osort, $pagestart, $pagesize, true);

if (!empty($peopledatatemp)) {
    $peopledata->total = $peopledatatemp->total;

    $personarray = $peopledatatemp->people;
    $personidsarray = array_keys($personarray);

    $usersactivitydata = user::get_all_users_activity_status($cminstance->id, $personidsarray);

    foreach ($personarray as $personid => $person) {
        $person->userpicturehtml = util::render_user_avatar($renderer, $person);
        $person->userprogressdata = $usersactivitydata[$person->id];
        $person->userprogressdata['lastactivedate'] = userdate($person->userprogressdata['lastactivedate'],
                get_string('formattimedatetime', 'openstudio'));
        $person->viewuserworkurl = new moodle_url('/mod/openstudio/view.php',
                    array('id' => $id, 'vuid' => $person->id, 'vid' => content::VISIBILITY_PRIVATE));

        $flagsdata = flags::count_by_user($cminstance->id, $person->id);
        $flagscontentread = 0;

        if (array_key_exists(flags::READ_CONTENT, $flagsdata)) {
            $flagscontentread = $flagsdata[flags::READ_CONTENT]->count;
        }

        $person->flagscontentread = $flagscontentread;

        $person->progressenable = $person->userprogressdata['totalcontents'] > 0;
        $person->participationenable = $permissions->feature_participationsmiley;
        $person->participationlow = isset($person->userprogressdata['participationstatus'])
                && ($person->userprogressdata['participationstatus'] == 'low');

        if ($person->progressenable) {
            $percentcompleted = round($person->userprogressdata['filledcontents'] / $person->userprogressdata['totalcontents'] * 100);
            $person->percentcompleted = $percentcompleted;
        }

        $peopledata->people[] = $person;
    }
}

// Sort action url.
$sortactionurl = new moodle_url('/mod/openstudio/people.php', ['id' => $id]);
$sortactionurl = $sortactionurl->out(false);
$peopledata->sortactionurl = $sortactionurl;
$peopledata->vid = $vid;

$viewsizes[0] = (object) ['size' => 50, 'selected' => false];
$viewsizes[1] = (object) ['size' => 100, 'selected' => false];
$viewsizes[2] = (object) ['size' => 150, 'selected' => false];
$viewsizes[3] = (object) ['size' => 250, 'selected' => false];

foreach ($viewsizes as $key => $value) {
    if ($value->size == $pagesize) {
        $viewsizes[$key]->selected = true;
    }
}
$peopledata->pageurl = $pageurl;
$peopledata->streamdatapagesize = $pagesize;
$peopledata->pagestart = $pagestart;
$peopledata->viewsizes = $viewsizes;
$peopledata->selectedgroupid = $groupid;

// Breadcrumb.
$peoplepageurl = new moodle_url('/mod/openstudio/people.php',
        ['id' => $cm->id, 'vid' => content::VISIBILITY_MODULE]);
$crumbarray[get_string('menupeople', 'openstudio')] = $peoplepageurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

$PAGE->requires->js_call_amd('mod_openstudio/peoplepage', 'init');

// Generate stream html.
$PAGE->set_button($renderer->searchform($theme, $vid, $id));

$html = $renderer->siteheader(
    $coursedata, $permissions, $theme, $cm->name, '', content::VISIBILITY_PEOPLE);

$peopledata->sortlist = filter::build_sort_by_filter(filter::PAGE_PEOPLE, $sortby);

echo $OUTPUT->header(); // Header.

echo $html;

echo $renderer->people_page($permissions, $peopledata); // Body.

// Finish the page.
echo $OUTPUT->footer();
