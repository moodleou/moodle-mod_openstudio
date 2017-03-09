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
use mod_openstudio\local\api\stream;
use mod_openstudio\local\util;
use mod_openstudio\local\util\defaults;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/api/apiloader.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$groupid = optional_param('groupid', 0, PARAM_INT); // Group id to filter against.
$n  = optional_param('n', 0, PARAM_INT);  // ... openstudio instance ID - it should be named as the first character of the module.

// Page init and security checks.

$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;
$placeholdertext = '';

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
    $contentowner = studio_api_user_get_user_by_id($vuid);
    $viewuser = $USER;
} else {
    $contentowner = $viewuser = $USER;
}

// Get page url.
$pageurl = util::get_current_url();

// Set stream view.
$vid = optional_param('vid', -1, PARAM_INT);
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
    $ismember = studio_api_group_has_same_memberships
            ($permissions->groupingid, $contentowner->id, $viewuser->id, true);
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
    case content::VISIBILITY_PRIVATE_PINBOARD:
        $pagetitle = $pageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->themestudioname));
        $vidviewname = 'work';
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

$fblock = optional_param('fblock', 0, PARAM_TEXT);
if ($vid == content::VISIBILITY_PRIVATE_PINBOARD) {
    // If its a request to view pinboard, then fblock can only be -1.
    $fblock = -1;
}

$blockid = optional_param('blockid', 0, PARAM_INT); // Block id to filter against.
$fblockarray = optional_param('fblockarray', array(), PARAM_INT);

// Stream get contents need to pass block array if existed.
if ($blockid) {
    array_push($fblockarray, $blockid);
}

$finalviewpermissioncheck = true;
if ((($vid == content::VISIBILITY_MODULE) || ($vid == content::VISIBILITY_GROUP) || ($vid == content::VISIBILITY_WORKSPACE))
    && !$permissions->managecontent) {
    $finalviewpermissioncheck = $permissions->viewothers;
}

// Sort options.
$fsortdefault = defaults::OPENSTUDIO_SORT_FLAG_DATE;
$osortdefault = defaults::OPENSTUDIO_SORT_DESC;
$fsort = optional_param('fsort', $fsortdefault, PARAM_INT);
$osort = optional_param('osort', $osortdefault, PARAM_INT);
$sortflag = array('id' => $fsort, 'asc' => $osort);

// Pagination settings.
$pagedefault = 0;
if (isset($SESSION->studio_view_filters)) {
    if (isset($SESSION->studio_view_filters[$vid]->page)) {
        $pagedefault = $SESSION->studio_view_filters[$vid]->page;
    }
}
$pagestart = optional_param('page', $pagedefault, PARAM_INT);
if ($pagestart < 0) {
    $pagestart = 0;
}

$streamdatapagesize = defaults::STREAMPAGESIZE;
if (isset(get_config('openstudio')->streampagesize)) {
    if (get_config('openstudio')->streampagesize > 0) {
        $streamdatapagesize = get_config('openstudio')->streampagesize;
    }
}
if (isset($SESSION->studio_view_filters)) {
    if (isset($SESSION->studio_view_filters[$vid]->pagesize)) {
        $streamdatapagesize = $SESSION->studio_view_filters[$vid]->pagesize;
    }
}
$streamdatapagesize = optional_param('pagesize', $streamdatapagesize, PARAM_INT);

// Get stream of contents.
$contentdata = (object) array('contents' => array(), 'total' => 0);
if ($finalviewpermissioncheck) {
    // In My Module view, if block filter is on and set to one block only,
    // then pagination behaves differently.  In this state, we display all the activity/content rather limiting
    // it to the default stream page size.  To  achieve this, we remove the pagination limit
    // by setting it to 0.
    if (($vid == content::VISIBILITY_PRIVATE) && ($fblock > 0) && (count($fblockarray) == 1)) {
        $streamdatapagesize = 0;
    }

    $contentdatatemp = stream::get_contents(
            $cminstance->id, $permissions->groupingid, $viewuser->id, $contentowner->id, $vid,
            $fblockarray, null, null, null, null, null,
            $sortflag, $pagestart, $streamdatapagesize, ($fblock == -1), true,
            $permissions->managecontent, $groupid, $permissions->groupmode,
            false,
            $permissions->accessallgroups,
            false,
            $permissions->feature_contentreciprocalaccess, $permissions->tutorroles);
    // Process the level management locks.
    if (isset($contentdatatemp->contents)) {
        $contentslist = array();
        $contentdata->total = $contentdatatemp->total;
        $activityitems = [];
        foreach ($contentdatatemp->contents as $content) {
            // Process content locking.
            if (($content->levelcontainer > 0) && ($content->userid == $permissions->activeuserid)) {
                $content = studio_api_lock_determine_lock_status($content);
            }

            $contentid = (int) $content->id;
            if ($contentid == 0) {
                // Contentid is 0 if it's a blank uncreated content, so create a unique ID so it can be stored in th array.
                $contentid = uniqid('', true);
            } else {
                $contentslist[] = $contentid;
            }

            $context = context_module::instance($cm->id);
            $slotarea = 'content';
            $slotthumbnailarea = 'contentthumbnail';
            $contenticon = '';

            $contentfileurl = $CFG->wwwroot
                    . "/pluginfile.php/{$context->id}/mod_openstudio"
                    . "/{$slotarea}/{$content->id}/" . rawurlencode($content->content);
            $contentfilewithsizecheckurl = $CFG->wwwroot
                    . "/pluginfile.php/{$context->id}/mod_openstudio"
                    . "/{$slotarea}/{$content->id}/" . rawurlencode($content->content) . "?sizecheck=1";
            if ($content->mimetype == 'image/bmp') {
                $contentthumbnailfileurl = new moodle_url('/mod/openstudio/pix/openstudio_preview_image.png');
            } else {
                if ($content->content) {
                    $contentthumbnailfileurl = $CFG->wwwroot
                            . "/pluginfile.php/{$context->id}/mod_openstudio"
                            . "/{$slotthumbnailarea}/{$content->id}/". rawurlencode($content->content);
                    if ($content->thumbnail) {
                        $contentthumbnailfileurl = $content->thumbnail;
                    }
                } else {
                    $contentthumbnailfileurl = new moodle_url('/mod/openstudio/pix/openstudio_preview_image.png');
                }
            }

            $visibility = (int)$content->visibility;
            if ($visibility < 0) {
                $visibility = content::VISIBILITY_GROUP;
            }

            $itemsharewith = '';
            // Set icon for content.
            switch ($visibility) {
                case content::VISIBILITY_MODULE:
                    $contenticon = new moodle_url('/mod/openstudio/pix/mymodule_rgb_32px.svg');
                    $itemsharewith = get_string('contentitemsharewithmymodule', 'openstudio');
                    break;

                case content::VISIBILITY_GROUP:
                    $contenticon = new moodle_url('/mod/openstudio/pix/group_rgb_32px.svg');
                    $itemsharewith = get_string('slotvisibletogroup', 'studio',
                            studio_api_group_get_name(abs($content->visibility)));
                    break;

                case content::VISIBILITY_WORKSPACE:
                case content::VISIBILITY_PRIVATE:
                    $contenticon = new moodle_url('/mod/openstudio/pix/onlyme_rgb_32px.svg');
                    $itemsharewith = get_string('contentitemsharewithonlyme', 'openstudio');
                    break;

                case content::VISIBILITY_PRIVATE_PINBOARD:
                    $contenticon = new moodle_url('/mod/openstudio/pix/onlyme_rgb_32px.svg');
                    $itemsharewith = get_string('contentitemsharewithonlyme', 'openstudio');
                    break;

                case content::VISIBILITY_TUTOR:
                    $contenticon = new moodle_url('/mod/openstudio/pix/share_with_tutor_rgb_32px.svg');
                    $itemsharewith = get_string('contentitemsharewithmytutor', 'openstudio');
                    break;
            }

            if ($content->userid != $viewuser->id) {
                $content->myworkview = true;
            } else {
                $content->myworkview = false;
            }

            $content->contenticon = $contenticon;
            $content->itemsharewith = $itemsharewith;
            $content->contentfileurl = $contentfileurl;
            $content->contentthumbnailurl = $contentthumbnailfileurl;
            $content->datetimeupdated = $content->timemodified ? date('j/m/y h:i', $content->timemodified) : null;
            $content->contentlink = new moodle_url('/mod/openstudio/content.php',
                    array('id' => $id, 'sid' => $content->id));

            $content->viewuserworkurl = new moodle_url('/mod/openstudio/view.php',
                    array('id' => $id, 'vuid' => $content->userid, 'vid' => content::VISIBILITY_PRIVATE));

            if (!$content->timemodified) {
                $content->contentediturl = new moodle_url('/mod/openstudio/contentedit.php',
                    array('id' => $id, 'sid' => 0, 'lid' => $content->l3id));
            } else {
                $content->contentediturl = new moodle_url('/mod/openstudio/contentedit.php',
                    array('id' => $id, 'sid' => $content->id));
            }

            $contentdata->contents[] = $content;

            // Activity items.
            if ($vid == content::VISIBILITY_WORKSPACE || $vid == content::VISIBILITY_PRIVATE) {
                $activityid = $content->l2id;

                if (array_key_exists($activityid, $activityitems)) {
                    $activityitems[$activityid]->activities[] = (object) $content;
                } else {
                    $activityitem = (object) [
                            'activities' => [(object) $content],
                            'activityname' => $content->l2name,
                            'activityid' => $activityid
                    ];

                    $activityitems[$activityid] = $activityitem;
                }
            }
        }

        // Returns all the values from the array and indexes the array numerically.
        // We need this because mustache requires it.
        $contentdata->activityitems = array_values($activityitems);

        $contentdata->pagestart = $pagestart;
        $contentdata->streamdatapagesize = $streamdatapagesize;
        $contentdata->pageurl = $pageurl;

        // Gather content social data.
        $contentsocialdata = studio_api_notifications_get_activities($permissions->activeuserid, $contentslist);
        if ($contentsocialdata) {
            foreach ($contentsocialdata as $contentsocialdataitem) {
                if (array_key_exists($contentsocialdataitem->contentid, $contentdata->contents)) {
                    $contentdata->contents[$contentsocialdataitem->contentid]->socialdata = $contentsocialdataitem;
                }
            }
        }
    }
}

// Render page header and crumb trail.
util::page_setup($PAGE, $pagetitle, $pageheading, $pageurl, $course, $cm);

// Breadcrumb.
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
        break;

    case content::VISIBILITY_PRIVATE_PINBOARD:
        $placeholdertext = $theme->themepinboardname;
        break;
}
$viewpageurl = new moodle_url('/mod/openstudio/view.php',
        array('id' => $cm->id, 'vid' => $vid));
$crumbarray[$placeholdertext] = $viewpageurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

$PAGE->requires->js_call_amd('mod_openstudio/viewhelper', 'init');

// Sort action url.
$sortactionurl = new moodle_url('/mod/openstudio/view.php', ['id' => $id, 'osort' => $osort, 'fsort' => $fsort]);
$sortactionurl = $sortactionurl->out(false);
$contentdata->sortactionurl = $sortactionurl;

$nextosort = 1 - $osort;
$sortbydateurl = new moodle_url('/mod/openstudio/view.php',
        ['id' => $id, 'vid' => $vid, 'groupid' => $groupid, 'pagesize' => $streamdatapagesize,
                'blockid' => $blockid, 'osort' => $nextosort, 'fsort' => stream::SORT_BY_DATE]);

$contentdata->sortbydateurl = $sortbydateurl->out(false);

$sortbytitleurl = new moodle_url('/mod/openstudio/view.php',
        ['id' => $id, 'vid' => $vid, 'groupid' => $groupid, 'pagesize' => $streamdatapagesize,
                'blockid' => $blockid, 'osort' => $nextosort, 'fsort' => stream::SORT_BY_ACTIVITYTITLE]);

$contentdata->sortbytitleurl = $sortbytitleurl->out(false);

$sortbydate = false;
$sortbytitle = false;
switch ($fsort) {
    case stream::SORT_BY_ACTIVITYTITLE:
        $sortbytitle = true;
        break;
    case stream::SORT_BY_DATE:
    default:
        $sortbydate = true;
        break;
}

$sortasc = false;
$sortdesc = false;
switch ($osort) {
    case stream::SORT_ASC:
        $sortasc = true;
        break;
    case stream::SORT_DESC:
    default:
        $sortdesc = true;
        break;
}

$contentdata->sortbydate = $sortbydate;
$contentdata->sortbytitle = $sortbytitle;
$contentdata->sortasc = $sortasc;
$contentdata->sortdesc = $sortdesc;
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
$contentdata->blockid = $blockid;

// Generate stream html.
$renderer = $PAGE->get_renderer('mod_openstudio');
$PAGE->set_button($renderer->searchform($theme, $vid));

$html = $renderer->siteheader(
        $coursedata, $permissions, $theme, $cm->name, '', $vid);

echo $OUTPUT->header(); // Header.

echo $html;

echo $renderer->body($cm->id, $cminstance->id, $theme, $vid, $permissions, $contentdata); // Body.

// Finish the page.
echo $OUTPUT->footer();

// Log page action.
util::trigger_event($cm->id, 'stream_viewed', "{$vidviewname}/1",
        util::get_page_name_and_params(true),
        "view {$vidviewname} stream");
