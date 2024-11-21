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

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\filter;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\api\stream;
use mod_openstudio\local\api\group;
use mod_openstudio\local\api\notifications;
use mod_openstudio\local\util;
use mod_openstudio\local\util\defaults;
use mod_openstudio\local\renderer_utils;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\api\user;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$groupid = optional_param('groupid', 0, PARAM_INT); // Group id to filter against.
$n  = optional_param('n', 0, PARAM_INT);  // ... openstudio instance ID - it should be named as the first character of the module.
$filteropen = optional_param('filteropen', 0, PARAM_INT);
$filteractive = optional_param('filteractive', 0, PARAM_INT);
$userid = optional_param('vuid', 0, PARAM_INT); // User id.
$pagedefault = 0;
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
$placeholdertext = '';
$renderer = util::get_renderer();

require_login($course, true, $cm);

// Terms and conditions check.
util::honesty_check($id);

// Need to have view or managecontent capabilities.
if (!$permissions->managecontent) {
    require_capability('mod/openstudio:view', $mcontext);
}

// Get user id that we need to show stream for.
$vuid = optional_param('vuid', $USER->id, PARAM_INT);
if ($vuid != $USER->id) {
    $contentowner = user::get_user_by_id($vuid);
    $viewuser = $USER;
} else {
    $contentowner = $viewuser = $USER;
}

// Get page url.
$pageurl = util::get_current_url();

// Set stream view.
$vid = optional_param('vid', 0, PARAM_INT);

// If pinboard mode is not on and activity is on, then redirect request to my activity workspace.
if (!$vid && !$permissions->feature_pinboard && $permissions->feature_studio) {
    $vid = content::VISIBILITY_PRIVATE;
} else {
    $vid = optional_param('vid', content::VISIBILITY_MODULE, PARAM_INT);
}

if (! in_array($vid, array(content::VISIBILITY_PRIVATE,
        content::VISIBILITY_PRIVATE_PINBOARD,
        content::VISIBILITY_GROUP,
        content::VISIBILITY_MODULE,
        content::VISIBILITY_WORKSPACE))) {
    $vid = $theme->homedefault;
}

if (! in_array($vid, array(content::VISIBILITY_PRIVATE,
        content::VISIBILITY_PRIVATE_PINBOARD,
        content::VISIBILITY_GROUP,
        content::VISIBILITY_MODULE,
        content::VISIBILITY_WORKSPACE))) {
    $vid = $theme->homedefault;
}

// If group mode is not on, then redirect request to module workspace.
if (!$permissions->feature_group && ($vid == content::VISIBILITY_GROUP)) {
    $vid = content::VISIBILITY_MODULE;
}

// If activity mode is not on, then redirect request to module workspace.
if (!$permissions->feature_studio && ($vid == content::VISIBILITY_PRIVATE)) {
    $vid = content::VISIBILITY_MODULE;
}

// If pinboard mode is not on, then redirect request to module workspace.
if (!$permissions->feature_pinboard && ($vid == content::VISIBILITY_PRIVATE_PINBOARD)) {
    $vid = content::VISIBILITY_MODULE;
}

// If module mode is not on, then redirect request to module first available workspace.
if (!$permissions->feature_module && ($vid == content::VISIBILITY_MODULE)) {
    $vid = $permissions->allow_visibilty_modes[0];
}

if ($vid == content::VISIBILITY_WORKSPACE) {
    $ismember = group::has_same_memberships($permissions->groupingid, $contentowner->id, $viewuser->id, true);
    if ($ismember) {
        $vidd = content::VISIBILITY_GROUP;
    } else {
        $vidd = content::VISIBILITY_MODULE;
    }
} else {
    $vidd = $vid;
}
switch ($vidd) {
    case content::VISIBILITY_GROUP:
        $pagetitle = $pageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->themegroupname));
        $vidviewname = 'group';
        break;

    case content::VISIBILITY_PRIVATE:
        $pagetitle = $pageheading = get_string('pageheader', 'openstudio',
            array('cname' => $course->shortname, 'cmname' => $cm->name,
                'title' => $theme->themestudioname));
        $vidviewname = 'work';
        break;

    case content::VISIBILITY_PRIVATE_PINBOARD:
        $pagetitle = $pageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->themepinboardname));
        $vidviewname = 'pinboard';
        break;

    case content::VISIBILITY_MODULE:
    default:
        $pagetitle = $pageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->thememodulename));
        $vidviewname = 'module';
        break;
}
if ($vuid != $USER->id) {
    $pagetitle .= ': ' . get_string('profileswork', 'openstudio', array('name' => $contentowner->firstname));;
}

// Render page header and crumb trail.
util::page_setup($PAGE, $pagetitle, $pageheading, $pageurl, $course, $cm);

$fblock = optional_param('fblock', 0, PARAM_TEXT);
if ($vid == content::VISIBILITY_PRIVATE_PINBOARD) {
    // If its a request to view pinboard, then fblock can only be -1.
    $fblock = -1;
}

$finalviewpermissioncheck = true;
if ((($vid == content::VISIBILITY_MODULE) || ($vid == content::VISIBILITY_GROUP) || ($vid == content::VISIBILITY_WORKSPACE))
    && !$permissions->managecontent) {
    $finalviewpermissioncheck = $permissions->viewothers;
}

// Sort options.
// Sort by is a combination of fsort + osort. If sort by is invalid, it will return null.
$sortby = optional_param('sortby', null, PARAM_INT);
// If we don't have GET paramater, try to search inside session.
if ($sortby === null) {
    if (isset($SESSION->openstudio_view_filters)) {
        if (isset($SESSION->openstudio_view_filters[$vid]->sortby)) {
            $sortby = $SESSION->openstudio_view_filters[$vid]->sortby;
        }
    }
}
$fsortdefault = defaults::OPENSTUDIO_SORT_FLAG_DATE;
$osortdefault = defaults::OPENSTUDIO_SORT_DESC;
$fsort = optional_param('fsort', $fsortdefault, PARAM_INT);
$osort = optional_param('osort', $osortdefault, PARAM_INT);
$sortbyparams = filter::get_sort_by_params($sortby);
if ($sortbyparams === null) {
    $sortby = null;
} else {
    $fsort = $sortbyparams['fsort'];
    $osort = $sortbyparams['osort'];
}

$sortflag = [
    'id' => $fsort,
    'asc' => $osort,
    'sortby' => $sortby,
];


// If group mode is not on, then redirect request to module workspace.
if (!$permissions->feature_group && ($vid == content::VISIBILITY_GROUP)) {
    $vid = content::VISIBILITY_MODULE;
}

// If activity mode is not on, then redirect request to module workspace.
if (!$permissions->feature_studio && ($vid == content::VISIBILITY_PRIVATE)) {
    $vid = content::VISIBILITY_MODULE;
}

// If pinboard mode is not on, then redirect request to module workspace.
if (!$permissions->feature_pinboard && ($vid == content::VISIBILITY_PRIVATE_PINBOARD)) {
    $vid = content::VISIBILITY_MODULE;
}

// If module mode is not on, then redirect request to module first available workspace.
if (!$permissions->feature_module && ($vid == content::VISIBILITY_MODULE)) {
    $vid = $permissions->allow_visibilty_modes[0];
}

if ($vid == content::VISIBILITY_WORKSPACE) {
    $fsort = stream::SORT_BY_ACTIVITYTITLE;
    $osort = stream::SORT_ASC;
    $sortflag = [
        'id' => $fsort, 'asc' => $osort
    ];
}

// Currently,  we respect view page size on preference bar and ignore settings.
$streamdatapagesize = defaults::STREAMPAGESIZE;
if (isset($SESSION->openstudio_view_filters)) {
    if (isset($SESSION->openstudio_view_filters[$vid]->pagesize)) {
        $streamdatapagesize = $SESSION->openstudio_view_filters[$vid]->pagesize;
    }
}
$streamdatapagesize = optional_param('pagesize', $streamdatapagesize, PARAM_INT);

// Pagination settings.
if (isset($SESSION->openstudio_view_filters)) {
    if (isset($SESSION->openstudio_view_filters[$vid]->page)) {
        if ($streamdatapagesize != $SESSION->openstudio_view_filters[$vid]->pagesize) {
            unset($SESSION->openstudio_view_filters[$vid]->page);
        } else {
            $pagedefault = $SESSION->openstudio_view_filters[$vid]->page;
        }
    }
}


// Check if filter reset request givem, if so, then clear all the filters by redirecting the browser.
$resetfilter = optional_param('reset', 0, PARAM_INT);

// If all filter type checked as default, it same as reset applied.
$filteractive = optional_param('filteractive', 0, PARAM_INT);
$farea = optional_param('fblock', 0, PARAM_INT);
$fflagarray = optional_param_array('fflagarray', array(), PARAM_INT);
$ftypearray = optional_param_array('ftypearray', array(), PARAM_INT);
$fstatus = optional_param('fstatus', 0, PARAM_INT);
$fscope = optional_param('fscope', 0, PARAM_INT);
$fownerscope = null;

$quickselect = optional_param('quickselect', null, PARAM_INT);
// If we don't have GET paramater, try to search inside session.
if ($quickselect === null) {
    if (isset($SESSION->openstudio_view_filters)) {
        if (isset($SESSION->openstudio_view_filters[$vid]->quickselect)) {
            $quickselect = $SESSION->openstudio_view_filters[$vid]->quickselect;
        }
    }
}
if ($quickselect !== null) {
    $quickselectparams = filter::get_quick_select_params($quickselect);
    // If not having quick select params, fallback as default.
    if ($quickselectparams === null) {
        $quickselect = null;
        $quickselectparams = [];
    }
    if (array_key_exists('ftypearray', $quickselectparams)) {
        $ftypearray = $quickselectparams['ftypearray'];
    }
    if (array_key_exists('fflagarray', $quickselectparams)) {
        $fflagarray = $quickselectparams['fflagarray'];
    }
    if (array_key_exists('fstatus', $quickselectparams)) {
        $fstatus = $quickselectparams['fstatus'];
    }
    if (array_key_exists('fscope', $quickselectparams)) {
        $fscope = $quickselectparams['fscope'];
    }
    if (array_key_exists('fownerscope', $quickselectparams)) {
        $fownerscope = $quickselectparams['fownerscope'];
    }
}

if ($filteractive && $farea == stream::FILTER_AREA_ALL && isset($fflagarray[0]) && $fflagarray[0] == 0 &&
        isset($ftypearray[0]) && $ftypearray[0] == 0 && $fstatus == stream::FILTER_STATUS_ALL_POST &&
        $fscope == stream::SCOPE_EVERYONE) {
    $resetfilter = true;
}

if ($resetfilter) {
    if (!isset($SESSION->openstudio_view_filters[$vid])) {
        // Completely reset the filters.
        $SESSION->openstudio_view_filters[$vid] = new stdClass();
    }

    // Partial filter reset.
    $SESSION->openstudio_view_filters[$vid]->fblock = null;
    $SESSION->openstudio_view_filters[$vid]->fblockarray = null;
    $SESSION->openstudio_view_filters[$vid]->ftype = null;
    $SESSION->openstudio_view_filters[$vid]->ftypearray = null;
    $SESSION->openstudio_view_filters[$vid]->fscope = null;
    $SESSION->openstudio_view_filters[$vid]->fstatus = null;
    $SESSION->openstudio_view_filters[$vid]->fflag = null;
    $SESSION->openstudio_view_filters[$vid]->fflagarray = null;
    $SESSION->openstudio_view_filters[$vid]->ftags = null;
    $SESSION->openstudio_view_filters[$vid]->fblockdataarray = null;
    $SESSION->openstudio_view_filters[$vid]->fsort = defaults::OPENSTUDIO_SORT_FLAG_DATE;
    $SESSION->openstudio_view_filters[$vid]->osort = defaults::OPENSTUDIO_SORT_DESC;
    $SESSION->openstudio_view_filters[$vid]->page = $streamdatapagesize;
    $SESSION->openstudio_view_filters[$vid]->pagesize = defaults::STREAMPAGESIZE;
    $SESSION->openstudio_view_filters[$vid]->filteractive = 0;
    $SESSION->openstudio_view_filters[$vid]->sortby = null;
    $SESSION->openstudio_view_filters[$vid]->quickselect = null;

    $reseturl = new moodle_url('/mod/openstudio/view.php', array(
            'id' => $id, 'vid' => $vid, 'page' => 0, 'pagesize' => $streamdatapagesize));

    redirect($reseturl);
}
// Record whether filtering is active.
$isfilteringon = false;

$fblockarraydefault = array();
if (isset($SESSION->openstudio_view_filters)) {
    if (isset($SESSION->openstudio_view_filters[$vid]->fblockarray)) {
        $fblockarraydefault = $SESSION->openstudio_view_filters[$vid]->fblockarray;
    }
}
$fblockarray = optional_param_array('fblockarray', $fblockarraydefault, PARAM_INT);
$factivityarray = optional_param_array('factivityarray', null, PARAM_TEXT);
// If we don't have GET paramater, try to search inside session.
if ($factivityarray === null) {
    if (isset($SESSION->openstudio_view_filters)) {
        if (isset($SESSION->openstudio_view_filters[$vid]->factivityarray)) {
            $factivityarray = $SESSION->openstudio_view_filters[$vid]->factivityarray;
        }
    }
}
$pinboardonly = false;

// Stream get contents need to pass block array if existed.
switch ($fblock) {
    case stream::FILTER_AREA_ALL:
    case stream::FILTER_AREA_PINBOARD:
        $fblockarray = null;
        $factivityarray = null;
        break;
    case stream::FILTER_AREA_ACTIVITY:
        // Get parent blocks from activities.
        // In this case, we won't implode $fblock, keep it as stream::FILTER_AREA_ACTIVITY.
        [$tempblocks, ] = openstudio_extract_blocks_activities($factivityarray);
        $fblockarray = $tempblocks;
        break;
    default:
        if (empty($fblockarray)) {
            $fblockarray = explode(",", $fblock);
            $fblock = implode(",", $fblockarray);
        } else {
            $fblock = implode(",", $fblockarray);
        }
        break;
}

if (trim($fblock) == '') {
    // If fblock is not set, then set it to default.
    $fblock = stream::FILTER_AREA_ALL;
}

$fblockdataarray = levels::get_all_activities($cminstance->id);
if ($fblockdataarray === false) {
    $fblockdataarray = array();
} else {
    $blockslotcount = levels::l1s_count_l3s($cminstance->id);
    $factivityids = !empty($factivityarray) ? array_flip($factivityarray) : [];

    foreach ($fblockdataarray as $key => $blockdata) {
        // Dont show the level 1 block if it has no level slots associated with it.
        if (!array_key_exists($blockdata->id, $blockslotcount) || ($blockslotcount[$blockdata->id] <= 0)) {
            unset($fblockdataarray[$key]);
            continue;
        }

        // Check if the block being checked as been selected by the user for filtering.
        if ((is_array($fblockarray) && ($fblockarray !== null) && in_array((int) $blockdata->id, $fblockarray))) {
            $fblockdataarray[$key]->checked = true;
        } else {
            $fblockdataarray[$key]->checked = false;
        }

        if (property_exists($blockdata, 'activities') && !empty($blockdata->activities)) {

            $allcheck = true;

            foreach ($blockdata->activities as $activity) {
                $key = $blockdata->id . '_' . $activity->id;
                // If we select "Activities", and we don't have $_GET for activities then all should be checked by default.
                // Or else it will be checked if ids contains in $_GET.
                $activity->checked = $fblock == stream::FILTER_AREA_ACTIVITY
                        && ($factivityarray === null || isset($factivityids[$key]));
                $activity->checkedworkfilter = $fblock == stream::FILTER_AREA_ALL || $activity->checked;

                if (!$activity->checked) {
                    if ($allcheck) {
                        $allcheck = false;
                    }
                }
            }

            // If any activity is not checked, uncheck this block.
            $blockdata->checked = $allcheck;
        }
    }
}

// Filter by content type.
$ftype = '';
$ftypedefault = 0;
$ftypearraydefault = array();

if (isset($SESSION->openstudio_view_filters)) {
    if (isset($SESSION->openstudio_view_filters[$vid]->ftypearray)) {
        $ftypearraydefault = $SESSION->openstudio_view_filters[$vid]->ftypearray;
    }

    if (isset($SESSION->openstudio_view_filters[$vid]->filteractive) &&
        $SESSION->openstudio_view_filters[$vid]->filteractive) {
        $filteractive = $SESSION->openstudio_view_filters[$vid]->filteractive;
    }
}

$ftypearray = optional_param_array('ftypearray', $ftypearraydefault, PARAM_INT);
if (!is_array($ftypearray) || empty($ftypearray)) {
    $ftype = $ftypedefault;
} else {
    foreach ($ftypearray as $ftypearrayitem) {
        if ($ftypearrayitem == 0) {
            $ftype = 0;
            break;
        }
        if (in_array($ftypearrayitem, array(content::TYPE_IMAGE,
                content::TYPE_VIDEO,
                content::TYPE_AUDIO,
                content::TYPE_DOCUMENT,
                content::TYPE_PRESENTATION,
                content::TYPE_SPREADSHEET,
                content::TYPE_URL,
                content::TYPE_FOLDER))) {
            $ftype .= "{$ftypearrayitem},";
        }
    }
}

// Filter by status.
$fstatusdefault = stream::FILTER_STATUS_ALL_POST;
if (isset($SESSION->openstudio_view_filters)) {
    if (isset($SESSION->openstudio_view_filters[$vid]->fstatus)) {
        $fstatusdefault = $SESSION->openstudio_view_filters[$vid]->fstatus;
    }
}

$fstatus = optional_param('fstatus', $fstatusdefault, PARAM_INT);
if (! in_array($fstatus, array(stream::FILTER_LOCKED,
        stream::FILTER_EMPTYCONTENT,
        stream::FILTER_NOTREAD,
        stream::FILTER_READ))) {
    $fstatus = $fstatusdefault;
}

// Filter by scope.
$fscopedefault = stream::SCOPE_EVERYONE;
if (isset($SESSION->openstudio_view_filters)) {
    if (isset($SESSION->openstudio_view_filters[$vid]->fscope)) {
        $fscopedefault = $SESSION->openstudio_view_filters[$vid]->fscope;
    }
}

$fscope = optional_param('fscope', $fscopedefault, PARAM_INT);
if (! in_array($fscope, array(stream::SCOPE_EVERYONE,
        stream::SCOPE_MY,
        stream::SCOPE_THEIRS))) {
    $fscope = stream::SCOPE_MY;
}

// Filter by tags.
$ftagsdefault = '';
if (isset($SESSION->openstudio_view_filters)) {
    if (isset($SESSION->openstudio_view_filters[$vid]->ftags)) {
        $ftagsdefault = $SESSION->openstudio_view_filters[$vid]->ftags;
    }
}
$ftags = optional_param('ftags', $ftagsdefault, PARAM_TEXT);
if ($ftags != $ftagsdefault) {
    $isfilteringon = true;
}

// Filter by participation flag.
$fflag = '';
$fflagdefault = 0;
$fflagarraydefault = array();
if (isset($SESSION->openstudio_view_filters)) {
    if (isset($SESSION->openstudio_view_filters[$vid]->fflagarray)) {
        $fflagarraydefault = $SESSION->openstudio_view_filters[$vid]->fflagarray;
    }
}
$fflagarray = optional_param_array('fflagarray', $fflagarraydefault, PARAM_INT);
if (!is_array($fflagarray) || empty($fflagarray)) {
    $fflag = $fflagdefault;
} else {
    foreach ($fflagarray as $fflagarrayitem) {
        if ($fflagarrayitem == 0) {
            $fflag = $fflagdefault;
            break;
        }
        if (in_array($fflagarrayitem, array(stream::FILTER_FAVOURITES,
                stream::FILTER_HELPME,
                stream::FILTER_MOSTSMILES,
                stream::FILTER_MOSTINSPIRATION,
                stream::FILTER_COMMENTS,
                stream::FILTER_TUTOR))) {
            $fflag .= "{$fflagarrayitem},";
        }
    }
}

// Store the filter settings in session memory.
$SESSION->openstudio_view_vid = $vid;
if (!isset($SESSION->openstudio_view_filters)) {
    $SESSION->openstudio_view_filters = array();
}
if (!isset($SESSION->openstudio_view_filters[$vid])) {
    $SESSION->openstudio_view_filters[$vid] = new stdClass();
}

$ftags = null;
$SESSION->openstudio_view_filters[$vid]->fblock = $fblock;
$SESSION->openstudio_view_filters[$vid]->fblockarray = $fblockarray;
$SESSION->openstudio_view_filters[$vid]->factivityarray = $factivityarray;
$SESSION->openstudio_view_filters[$vid]->ftype = $ftype;
$SESSION->openstudio_view_filters[$vid]->ftypearray = $ftypearray;
$SESSION->openstudio_view_filters[$vid]->fscope = $fscope;
$SESSION->openstudio_view_filters[$vid]->fstatus = $fstatus;
$SESSION->openstudio_view_filters[$vid]->fflag = $fflag;
$SESSION->openstudio_view_filters[$vid]->fflagarray = $fflagarray;
$SESSION->openstudio_view_filters[$vid]->ftags = $ftags;
$SESSION->openstudio_view_filters[$vid]->fsort = $fsort;
$SESSION->openstudio_view_filters[$vid]->osort = $osort;
$SESSION->openstudio_view_filters[$vid]->page = $pagestart;
$SESSION->openstudio_view_filters[$vid]->pagesize = $streamdatapagesize;
$SESSION->openstudio_view_filters[$vid]->groupid = $groupid;
$SESSION->openstudio_view_filters[$vid]->filteropen = $filteropen;
$SESSION->openstudio_view_filters[$vid]->filteractive = $filteractive;
$SESSION->openstudio_view_filters[$vid]->fblockdataarray = $fblockdataarray;
$SESSION->openstudio_view_filters[$vid]->sortby = $sortby;
$SESSION->openstudio_view_filters[$vid]->quickselect = $quickselect;

// Get stream of contents.
$contentids = array(); // For export feature.
$contentdata = (object) array('contents' => array(), 'total' => 0);
$contentdata->openstudio_view_filters = $SESSION->openstudio_view_filters[$vid];

if ($finalviewpermissioncheck) {
    // When view My Ativity or
    // In My Module/Group view, if block filter is on and set to one block only,
    // then pagination behaves differently.  In this state, we display all the activity/content rather limiting
    // it to the default stream page size.  To  achieve this, we remove the pagination limit
    // by setting it to 0.

    if ((($vid == content::VISIBILITY_MODULE || $vid == content::VISIBILITY_GROUP)
        && ($fblock > 0) && ($fblockarray !== null) && (count($fblockarray) == 1))
            || $vid == content::VISIBILITY_PRIVATE) {
        $streamdatapagesize = 0;
    }

    $contentdatatemp = stream::get_contents(
            $cminstance->id, $permissions->groupingid, $viewuser->id, $contentowner->id, $vid,
            $fblockarray, $ftype, $fscope,
            $fflag, $fstatus, $ftags,
            $sortflag, $pagestart, $streamdatapagesize, ($fblock == -1), true,
            $permissions->managecontent, $groupid, $permissions->groupmode,
            false,
            $permissions->accessallgroups,
            false,
            $permissions->feature_contentreciprocalaccess, $permissions->tutorroles,
            $vid,
            $factivityarray,
            $fownerscope
    );
    // Process the level management locks.
    if (isset($contentdatatemp->contents)) {
        $contentslist = array();
        $contentdata->total = $contentdatatemp->total;
        $activityitems = [];
        $normalshareditems = []; // Just have items if a user view another user's work.
        $storedexpand = get_user_preferences('mod_openstudio_expanded_' . $id);
        $storedexpand = $storedexpand ? json_decode($storedexpand, true) : null;
        foreach ($contentdatatemp->contents as $content) {
            // Process content locking.
            if (($content->levelcontainer > 0) && ($content->userid == $permissions->activeuserid)) {
                $content = lock::determine_lock_status($content);
            }

            $content->locked = ($content->locktype > lock::NONE) && ($content->locktype <= lock::CRUD);
            $contentid = (int) $content->id;

            if ($contentid == 0) {
                $contentexist = false;
                // Contentid is 0 if it's a blank uncreated content, so create a unique ID so it can be stored in th array.
                $contentid = uniqid('', true);
            } else {
                $contentexist = true;
                $contentslist[] = $contentid;
                // Content feedback requested.
                $content->isfeedbackrequested = false;
                $flagstatus = flags::get_for_content_by_user($contentid, $content->userid);
                if (in_array(flags::NEEDHELP, $flagstatus)) {
                    $content->isfeedbackrequested = true;
                }
            }

            $context = context_module::instance($cm->id);

            $contenticon = '';
            $folderthumbnailfileurl = '';

            $content = renderer_utils::content_type_image($content, $context);

            $contentthumbnailfileurl = $content->contenttypeimage;

            $visibility = (int)$content->visibility;
            if ($visibility < 0) {
                $visibility = content::VISIBILITY_GROUP;
            }

            $isonlyme = false;
            $itemsharewith = '';
            // Set icon for content.
            switch ($visibility) {
                case content::VISIBILITY_MODULE:
                    $contenticon = $OUTPUT->image_url('mymodule_rgb_32px', 'openstudio');
                    $itemsharewith = get_string('contentitemsharewithmymodule', 'openstudio');
                    break;

                case content::VISIBILITY_GROUP:
                    $contenticon = $OUTPUT->image_url('share_with_my_group_rgb_32px', 'openstudio');
                    $itemsharewith = get_string('contentitemsharewithgroup', 'openstudio',
                            group::get_name(abs($content->visibility)));
                    break;

                case content::VISIBILITY_ALLGROUPS:
                    $contenticon = $OUTPUT->image_url('share_with_my_group_rgb_32px', 'openstudio');
                    $itemsharewith = get_string('contentitemsharewithallmytutorgroups', 'openstudio');
                    break;

                case content::VISIBILITY_WORKSPACE:
                case content::VISIBILITY_PRIVATE:
                    $contenticon = $OUTPUT->image_url('onlyme_rgb_32px', 'openstudio');
                    $itemsharewith = get_string('contentitemsharewithonlyme', 'openstudio');
                    $isonlyme = true;
                    break;

                case content::VISIBILITY_PRIVATE_PINBOARD:
                    $contenticon = $OUTPUT->image_url('onlyme_rgb_32px', 'openstudio');
                    $itemsharewith = get_string('contentitemsharewithonlyme', 'openstudio');
                    $isonlyme = true;
                    break;

                case content::VISIBILITY_TUTOR:
                    $contenticon = $OUTPUT->image_url('share_with_tutor_rgb_32px', 'openstudio');
                    $itemsharewith = get_string('contentitemsharewithmytutor', 'openstudio');
                    break;
                default:
                    $contenticon = $OUTPUT->image_url('onlyme_rgb_32px', 'openstudio');
                    $itemsharewith = get_string('contentitemsharewithonlyme', 'openstudio');
                    $isonlyme = true;
                    break;
            }

            if ($content->userid != $viewuser->id) {
                $content->myworkview = true;
            } else {
                $content->myworkview = false;
            }

            // Check content is folder.
            $content = renderer_utils::get_folder_data($id, $content, $vid);

            // If content exist, add it to content list.
            if ($contentexist) {
                $contentids[] = $contentid;
            }

            $content->contenticon = $contenticon;
            $content->itemsharewith = $itemsharewith;
            $content->isonlyme = $isonlyme;
            $content->contentthumbnailurl = $contentthumbnailfileurl;
            $content->contentthumbnailalt = get_string('contentthumbnailalt', 'mod_openstudio',
                renderer_utils::get_content_thumbnail_alt($content, $vid));

            $content->datetimeupdated = null;
            if ($content->timemodified) {
                $content->datetimeupdated = userdate($content->timemodified, get_string('formattimedatetime', 'openstudio'));
            }
            $content->contentlink = new moodle_url('/mod/openstudio/content.php',
                    array('id' => $id, 'sid' => $content->id, 'vuid' => $content->userid));

            $content->viewuserworkurl = new moodle_url('/mod/openstudio/view.php',
                    array('id' => $id, 'vuid' => $content->userid, 'vid' => content::VISIBILITY_PRIVATE));

            if ($content->userid) {
                $user = user::get_user_by_id($content->userid);
                $content->userpicturehtml = util::render_user_avatar($renderer, $user);
            }

            if (!$content->timemodified) {
                $content->contentediturl = new moodle_url('/mod/openstudio/contentedit.php',
                    array('id' => $id, 'vid' => $vid, 'sid' => 0, 'lid' => $content->l3id));
            } else {
                $content->contentediturl = new moodle_url('/mod/openstudio/contentedit.php',
                    array('id' => $id, 'vid' => $vid, 'sid' => $content->id));
            }

            $contentdata->contents[$contentid] = $content;

            // Activity items.
            if ($vid == content::VISIBILITY_WORKSPACE || $vid == content::VISIBILITY_PRIVATE) {
                $activityid = $content->l2id;

                // Should only display a maximum of two lines for the activity title.
                if (strlen($content->name ?? '') > 55) {
                    $content->name = substr($content->name, 0, 55) . '...';
                }

                if ($content->l2id != '') {
                    // Activity content.

                    // Process lock for activity content.
                    $lockdata = renderer_utils::content_lock_data(
                        (object) array('l3id' => $content->l3id), $permissions, $cminstance);
                    $content->contentislocked = $lockdata->contentislock;
                    $content->contentislockmessage = $lockdata->contentislockmessage;

                    // Use showextradata field to indicate that this is an auto-generated folder.
                    $content->contentempty = ($content->contenttype == content::TYPE_NONE && $content->timemodified == 0)
                            || ($content->showextradata && $content->contenttype == content::TYPE_FOLDER);
                    $content->activitycontentempty = $content->contenttype == content::TYPE_NONE;
                    if (array_key_exists($activityid, $activityitems)) {
                        $activityitems[$activityid]->activities[] = (object)$content;
                    } else {
                        $activityitem = (object)[
                                'activities' => [(object)$content],
                                'activityname' => $content->l2name,
                                'activityid' => $activityid
                        ];

                        $activityitems[$activityid] = $activityitem;
                    }
                    if ($storedexpand && array_key_exists($activityid, $storedexpand)) {
                        $activityitems[$activityid]->isexpanded = $storedexpand[$activityid];
                    } else {
                        // Default is true.
                        $activityitems[$activityid]->isexpanded = true;
                    }
                } else {
                    $normalshareditems[] = $content;
                }
            }
        }

        // Returns all the values from the array and indexes the array numerically.
        // We need this because mustache requires it.
        $contentdata->hasactivityitems = !empty($activityitems) || !empty($normalshareditems);
        $contentdata->activityitems = array_values($activityitems);
        $contentdata->normalshareditems = array_values($normalshareditems);
        $contentdata->hasnormalshareditems = !empty($normalshareditems);

        if ($contentdata->hasnormalshareditems) {
            // Add some attributes to use the expand/collapse template with the same structure as My Activity.
            $sharecontentid = 'shared_content';
            $contentdata->activityid = $sharecontentid;
            $contentdata->activityname = get_string('menusharedcontent', 'mod_openstudio');
            if ($storedexpand && array_key_exists($sharecontentid, $storedexpand)) {
                $contentdata->isexpanded = $storedexpand[$sharecontentid];
            } else {
                // Default is true.
                $contentdata->isexpanded = true;
            }
        }

        $contentdata->pagestart = $pagestart;
        $contentdata->streamdatapagesize = $streamdatapagesize;
        $contentdata->pageurl = $pageurl;

        // Gather content social data.
        $socialdatatotal = 0;
        $contentsocialdata = notifications::get_activities($permissions->activeuserid, $contentslist, $permissions->groupingid,
                $permissions->managecontent);
        // List of user commented.
        $lsofusercommented = util::get_list_comment_of_user_by_contentid($contentslist);
        // List of flag content of user.
        $lsoflagcontentuser = flags::get_list_flag_content_by_user($contentslist, $permissions->activeuserid);

        if ($contentsocialdata) {
            foreach ($contentsocialdata as $socialitem) {
                if (array_key_exists($socialitem->contentid, $contentdata->contents)) {
                    if (($socialitem->commentsnewcontent > 0) || ($socialitem->commentsnew > 0) ||
                        ($socialitem->inspirednewcontent > 0) || ($socialitem->inspirednew > 0) ||
                        ($socialitem->mademelaughnewcontent > 0) || ($socialitem->mademelaughnew > 0) ||
                        ($socialitem->favouritenewcontent > 0) || ($socialitem->favouritenew > 0)) {

                        $socialitem->comments = $socialitem->commentsnewcontent + $socialitem->commentsnew;
                        $socialitem->inspired = $socialitem->inspirednewcontent + $socialitem->inspirednew;
                        $socialitem->mademelaugh = $socialitem->mademelaughnewcontent + $socialitem->mademelaughnew;
                        $socialitem->favourite = $socialitem->favouritenewcontent + $socialitem->favouritenew;
                    } else {
                        $socialitem->comments = $socialitem->commentsold;
                        $socialitem->inspired = $socialitem->inspiredold;
                        $socialitem->mademelaugh = $socialitem->mademelaughold;
                        $socialitem->favourite = $socialitem->favouriteold;
                    }
                    // Display total react of user in emoticons.
                    $socialitem->totalcomments = $socialitem->totalcomments > 0 ? $socialitem->totalcomments : "";
                    $socialitem->totalinspired = $socialitem->totalinspired > 0 ? $socialitem->totalinspired : "";
                    $socialitem->totalmademelaugh = $socialitem->totalmademelaugh > 0 ? $socialitem->totalmademelaugh : "";
                    $socialitem->totalfavourite = $socialitem->totalfavourite > 0 ? $socialitem->totalfavourite : "";

                    // Check permission to allow user react emoticons in content block social.
                    $isreactemoticon = true;
                    if (!util::can_read_content($cminstance, $permissions, $contentdata->contents[$socialitem->contentid])) {
                        $isreactemoticon = false;
                    }
                    // Check if social item is double digit.
                    $socialitem = util::check_item_double_digit($socialitem);
                    $contentdata->contents[$socialitem->contentid]->socialdata = $socialitem;
                    $contentdata->contents[$socialitem->contentid]->contentid = $socialitem->contentid;
                    $contentdata->contents[$socialitem->contentid]->isreactemoticon = $isreactemoticon;
                    // Generate content flags.
                    if (isset($lsoflagcontentuser[$socialitem->contentid])) {
                        $flagstatus = explode(',', $lsoflagcontentuser[$socialitem->contentid]->flagstatus);
                        if (in_array(flags::FAVOURITE, $flagstatus)) {
                            $contentdata->contents[$socialitem->contentid]->contentflagfavouriteactive = true;
                        }
                        if (in_array(flags::MADEMELAUGH, $flagstatus)) {
                            $contentdata->contents[$socialitem->contentid]->contentflagsmileactive = true;
                        }
                        if (in_array(flags::INSPIREDME, $flagstatus)) {
                            $contentdata->contents[$socialitem->contentid]->contentflaginspireactive = true;
                        }
                    }
                    if (isset($lsofusercommented[$socialitem->contentid])) {
                        $contentdata->contents[$socialitem->contentid]->iscurrentusercomment = true;
                    }
                    $socialdatatotal = $socialitem->comments + $socialitem->inspired
                        + $socialitem->mademelaugh + $socialitem->favourite;

                    $contentdata->contents[$socialitem->contentid]->socialdatatotal = $socialdatatotal;
                }
            }
        }

        // Returns all the values from the array and indexes the array numerically.
        // We need this because mustache requires it.
        $contentdata->contents = array_values($contentdata->contents);
    }
}

// Breadcrumb.
$importenable = false;
$exportenable = false;
switch ($vid) {
    case content::VISIBILITY_MODULE:
        $placeholdertext = $theme->thememodulename;
        break;

    case content::VISIBILITY_GROUP:
        $placeholdertext = $theme->themegroupname;
        break;

    case content::VISIBILITY_WORKSPACE:
    case content::VISIBILITY_PRIVATE:
        $placeholdertext = $theme->themestudioname;
        $exportenable = true;
        break;

    case content::VISIBILITY_PRIVATE_PINBOARD:
        $importenable = true;
        $placeholdertext = $theme->themepinboardname;
        $exportenable = true;
        break;
}
$viewpageurl = new moodle_url('/mod/openstudio/view.php',
        array('id' => $cm->id, 'vid' => $vid));
$crumbarray[$placeholdertext] = $viewpageurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

// Only content owner can import.
$importenable = $importenable && ($vuid == $USER->id) && has_capability('mod/openstudio:import', $mcontext);
if ($importenable) {
    $importurl = new moodle_url('/mod/openstudio/import.php', array('id' => $id));
    $importstr = get_string('openstudio:import', 'mod_openstudio');

    $renderer->add_import_button($importurl, $importstr);
}

// Only content owner can export.
$exportenable = $exportenable && ($vuid == $USER->id) && has_capability('mod/openstudio:export', $mcontext);
if ($exportenable) {
    $renderer->add_export_button();

    // Require strings.
    $PAGE->requires->strings_for_js(
            array('exportdialogheader', 'exportdialogcontent', 'export:emptycontent', 'exportall',
                    'exportselectedpost', 'modulejsdialogcancel'), 'openstudio');

    $PAGE->requires->js_call_amd('mod_openstudio/export', 'init', [[
        "id" => $id,
        "vid" => $vid,
        "contentids" => $contentids]]);
}

$contentdata->selectedgroupid = $groupid;

$viewsizes[0] = (object) ['size' => 50, 'selected' => false];
$viewsizes[1] = (object) ['size' => 100, 'selected' => false];
$viewsizes[2] = (object) ['size' => 150, 'selected' => false];
$viewsizes[3] = (object) ['size' => 250, 'selected' => false];

foreach ($viewsizes as $key => $value) {
    if ($value->size == $streamdatapagesize) {
        $viewsizes[$key]->selected = true;
    }
}

$contentdata->viewsizes = $viewsizes;
$contentdata->blockid = 0;

// Set params used for preferences filter.
$contentdata->id = $id;
$contentdata->vid = $vid;
$contentdata->vuid = $vuid;
$contentdata->fsort = $fsort;
$contentdata->osort = $osort;
$contentdata->page = $streamdatapagesize;
$contentdata->filteropen = $filteropen;
$contentdata->filteractive = $filteractive;
if (!has_capability('mod/openstudio:addcontent', $mcontext)) {
    $contentdata->adddisable = true;
}
if (isguestuser()) {
    $url = get_login_url();
    $SESSION->wantsurl = $PAGE->url->out();
    $contentdata->guestmessage = format_text(get_string('guestmessage', 'mod_openstudio', $url), FORMAT_MARKDOWN);
}
// Generate stream html.
$PAGE->set_button($renderer->searchform($theme, $vid, $id, $groupid));

$html = $renderer->siteheader(
        $coursedata, $permissions, $theme, $cm->name, '', $vid);

$defaultparams = [
        'id' => $id,
        'vuid' => $vuid,
        'fblock' => $fblock,
        'fstatus' => $fstatus,
        'fscope' => $fscope,
];

if (!empty($ftypearray)) {
    foreach ($ftypearray as $k => $ftypevalue) {
        $defaultparams["ftypearray[$k]"] = $ftypevalue;
    }
}

if (!empty($fflagarray)) {
    foreach ($fflagarray as $kfflag => $fflagvalue) {
        $defaultparams["fflagarray[$kfflag]"] = $fflagvalue;
    }
}

if ($factivityarray !== null && !empty($factivityarray)) {
    foreach ($factivityarray as $kactivity => $factivityvalue) {
        $defaultparams["factivityarray[$kactivity]"] = $factivityvalue;
    }
}

// Sort action url.
$sortactionurl = new moodle_url('/mod/openstudio/view.php', $defaultparams);
$sortactionurl = $sortactionurl->out(false);
$contentdata->sortactionurl = $sortactionurl;

$contentdata->sortlist = filter::build_sort_by_filter(filter::PAGE_VIEW, $sortby);
// Set params used for preferences filter.
$contentdata->sortby = $sortby;
$contentdata->quickselectlist = filter::build_quick_select_filter($vid, $quickselect);
// Set params used for preferences filter.
$contentdata->quickselect = $quickselect;

// Update completion 'viewed' flag if in use.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header(); // Header.

echo $html;

echo $renderer->body($cm->id, $cminstance, $theme, $vid, $permissions, $contentdata); // Body.
$PAGE->requires->js_call_amd('mod_openstudio/content_block_social', 'init');

echo $renderer->render_import_export_buttons($importenable, $exportenable, $id);

// Finish the page.
echo $OUTPUT->footer();

// Log page action.
util::trigger_event($cm->id, 'stream_viewed', "{$vidviewname}/1",
        util::get_page_name_and_params(true),
        "view {$vidviewname} stream");
