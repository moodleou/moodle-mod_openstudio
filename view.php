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
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\rss;
use mod_openstudio\local\api\subscription;
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

require_login($course, true, $cm);

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
$strpageurl = util::get_current_url();

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
        $strpagetitle = $strpageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->themegroupname));
        break;

    case content::VISIBILITY_PRIVATE:
    case content::VISIBILITY_PRIVATE_PINBOARD:
        $strpagetitle = $strpageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->themestudioname));
        break;

    case content::VISIBILITY_MODULE:
    default:
        $strpagetitle = $strpageheading = get_string('pageheader', 'openstudio',
                array('cname' => $course->shortname, 'cmname' => $cm->name,
                      'title' => $theme->thememodulename));
        break;
}
if ($vuid != $USER->id) {
    $strpagetitle .= ': ' . get_string('profileswork', 'openstudio', array('name' => $contentowner->firstname));;
}

$fblock = -1;
$fblockarray = array();

$finalviewpermissioncheck = true;
if ((($vid == content::VISIBILITY_MODULE) || ($vid == content::VISIBILITY_GROUP) || ($vid == content::VISIBILITY_WORKSPACE))
    && !$permissions->managecontent) {
    $finalviewpermissioncheck = $permissions->viewothers;
}

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
if ($streamdatapagesize > 100) {
    $streamdatapagesize = 100;
}

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

    $contentdatatemp = studio_api_stream_get_slots(
            $cminstance->id, $permissions->groupingid, $viewuser->id, $contentowner->id, $vid,
            null, null, null, null, null, null,
            null, $pagestart, $streamdatapagesize, ($fblock == -1), true,
            $permissions->managecontent, $groupid, $permissions->groupmode,
            false,
            $permissions->accessallgroups,
            false,
            $permissions->feature_contentreciprocalaccess, $permissions->tutorroles);
    // Process the level management locks.
    if (isset($contentdatatemp->contents)) {
        $contentslist = array();
        $contentdata->total = $contentdatatemp->total;
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

            // Set icon for content.
            switch(abs((int) $content->visibility)) {
                case content::VISIBILITY_MODULE:
                    $contenticon = new moodle_url('/mod/openstudio/pix/mymodule_rgb_32px.svg');
                    break;

                case content::VISIBILITY_GROUP:
                    $contenticon = new moodle_url('/mod/openstudio/pix/shared_content_rgb_32px.svg');
                    break;

                case content::VISIBILITY_WORKSPACE:
                case content::VISIBILITY_PRIVATE:
                    $contenticon = new moodle_url('/mod/openstudio/pix/onlyme_rgb_32px.svg');
                    break;

                case content::VISIBILITY_PRIVATE_PINBOARD:
                    $contenticon = new moodle_url('/mod/openstudio/pix/onlyme_rgb_32px.svg');
                    break;

                case content::VISIBILITY_TUTOR:
                    $contenticon = new moodle_url('/mod/openstudio/pix/share_with_tutor_rgb_32px.svg');
                    break;
            }

            $content->contenticon = $contenticon;
            $content->contentfileurl = $contentfileurl;
            $content->contentthumbnailurl = $contentthumbnailfileurl;
            $content->datetimeupdated = date('j/m/y h:i', $content->timemodified);
            $content->contentlink = "#";

            $content->userpictureurl = new moodle_url('/user/pix.php/'.$content->userid.'/f1.jpg');
            $contentdata->contents[] = $content;
        }

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
util::page_setup($PAGE, $strpagetitle, $strpageheading, $strpageurl, $course, $cm);

// Generate stream html.
$renderer = $PAGE->get_renderer('mod_openstudio');
$PAGE->set_button($renderer->searchform($theme, $vid));

$html = $renderer->siteheader(
        $coursedata, $permissions, $theme, $cm->name, '', $vid);

echo $OUTPUT->header(); // Header.

echo $html;

echo $renderer->body($cm->id, $theme, $vid, $contentdata); // Body.

// Finish the page.
echo $OUTPUT->footer();
