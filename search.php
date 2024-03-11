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
require_once(__DIR__ . '/../../config.php');

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\stream;
use mod_openstudio\local\api\search;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\api\group;
use mod_openstudio\local\api\user;
use mod_openstudio\local\renderer_utils;
use mod_openstudio\local\api\notifications;
use mod_openstudio\local\util;
use mod_openstudio\local\util\defaults;
use mod_openstudio\local\api\folder;

$id = optional_param('id', 0, PARAM_INT); // Course module id.

// For OSEP search form, we need use input name=query
$searchtext = optional_param('query', '', PARAM_TEXT); // Search text.
if ($searchtext == '') {
    // Keep searchtext to do not break unit tests.
    $searchtext = optional_param('searchtext', '', PARAM_TEXT); // Search text.
}
$groupid = optional_param('groupid', 0, PARAM_INT); // Group ID.
$vid = optional_param('vid', content::VISIBILITY_MODULE, PARAM_INT); // Visibility ID.
$pagestart = optional_param('page', 0, PARAM_INT);
$nextstart = optional_param('nextstart', 0, PARAM_INT);

// Page inita and security checks.
$coursedata = util::render_page_init($id, array('mod/openstudio:view'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;
$strpageurl = util::get_current_url();

// For global search, we need use input name=q.
if ($searchtext == '' && util::global_search_enabled($cm)) {
    $searchtext = optional_param('q', '', PARAM_TEXT);
}

require_login($course, true, $cm);

// Terms and conditions check.
util::honesty_check($id);

switch ($vid) {
    case content::VISIBILITY_GROUP:
        $placeholdertext1 = get_string('menusharedcontent', 'openstudio');
        $placeholdertext2 = $theme->themegroupname;
        break;

    case content::VISIBILITY_WORKSPACE:
    case content::VISIBILITY_PRIVATE:
        $placeholdertext1 = get_string('menumycontent', 'openstudio');
        $placeholdertext2 = $theme->themestudioname;
        break;

    case content::VISIBILITY_PRIVATE_PINBOARD:
        $placeholdertext1 = get_string('menumycontent', 'openstudio');
        $placeholdertext2 = $theme->themepinboardname;
        break;
    case content::VISIBILITY_MODULE:
    default:
        $placeholdertext1 = get_string('menusharedcontent', 'openstudio');
        $placeholdertext2 = $theme->thememodulename;
        break;
}

// Render page header and crumb trail.
$pagetitle = $pageheading = get_string('pageheader', 'openstudio',
        array('cname' => $course->shortname, 'cmname' => $cm->name,
            'title' => $placeholdertext2)) . ': ' . get_string('navsearch', 'openstudio');

$vidcrumbarray = array();
$vidcrumbarray[$placeholdertext1] = new moodle_url('/mod/openstudio/view.php',
    array('id' => $cm->id, 'vid' => $vid));
$vidcrumbarray[$placeholdertext2] = new moodle_url('/mod/openstudio/search.php',
    array('id' => $cm->id, 'vid' => $vid));

util::page_setup($PAGE, $pagetitle, $pageheading, $strpageurl, $course, $cm);
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $vidcrumbarray);
$renderer = $PAGE->get_renderer('mod_openstudio');
// Set view mode.
if (! in_array($vid, array(
        content::VISIBILITY_PRIVATE,
        content::VISIBILITY_PRIVATE_PINBOARD,
        content::VISIBILITY_GROUP,
        content::VISIBILITY_MODULE,
        content::VISIBILITY_WORKSPACE))) {
    $vid = content::VISIBILITY_MODULE;
}

// If group mode is not on, then redirect request to module workspace.
if (!$permissions->feature_group && ($vid == content::VISIBILITY_GROUP)) {
    $vid = content::VISIBILITY_MODULE;
}

// If activity mode is not on, then redirect request to module workspace.
if (!$permissions->feature_studio && ($vid == content::VISIBILITY_PRIVATE)) {
    $vid = content::VISIBILITY_MODULE;
}

// Cache the view context and groupmode which will be used by the search engine query.
// This is done to reduce DB queries when doing search result permission filtering.
util::cache_put('search_view_context', $vid);
util::cache_put('search_view_groupmode', $permissions->groupmode);

// Cache the view capability which will be used by the search engine query.
util::cache_put('search_viewothers_capability',
        ($permissions->viewothers || $permissions->managecontent));

// Set stream listing page size.
$streamdatapagesize = defaults::STREAMPAGESIZE;
if (isset(get_config('openstudio')->streampagesize)) {
    if (get_config('openstudio')->streampagesize > 0) {
        $streamdatapagesize = get_config('openstudio')->streampagesize;
    }
}

// Pagination settings.
if ($pagestart < 0) {
    $pagestart = 0;
}

if (trim($searchtext) == '') {
    // No search term given, so return to main stream view.
    $moduleurl = new moodle_url('/mod/openstudio/view.php',
            array('id' => $cm->id, 'vid' => content::VISIBILITY_MODULE));
    redirect($moduleurl->out(false));
    exit();
} else {
    // Query the search engine.
    $searchresultdata = search::query(
            $cm, $searchtext, $pagestart, $streamdatapagesize, $nextstart, $vid);

    // Define object.
    // TODO: This object should be refactored to a templatable object.
    $contentdata = (object)array(
            'contents' => array(),
            'total' => 0,
            'previouspage' => '',
            'nextpage' => '',
            'nextstart' => '',
            'streamdatapagesize' => 0,
            'pagestart' => 0,
            'pageurl' => '');

    // Enrich the search result with additional content information.
    if (isset($searchresultdata->result)) {
        if ($searchresultdata->isglobal) {
            $streamdatapagesize = \core_search\manager::DISPLAY_RESULTS_PER_PAGE;
        }
        $contentdata = (object)array(
                'contents' => array(),
                'total' => count($searchresultdata->result),
                'previouspage' => $searchresultdata->previous,
                'nextpage' => $searchresultdata->next,
                'nextstart' => $searchresultdata->nextstart,
                'allresults' => $searchresultdata->total,
                'streamdatapagesize' => $streamdatapagesize,
                'pagestart' => $pagestart,
                'pageurl' => $strpageurl);
        $contentids = array();
        $contentidsfolder = array();
        $contentidsanchor = array();
        foreach ($searchresultdata->result as $searchresult) {
            $contentids[] = $searchresult->intref1;
            if (!empty($searchresult->anchor)) {
                $contentidsanchor[$searchresult->intref1] = $searchresult->anchor;
            }
            if (!empty($searchresult->folderid)) {
                $contentidsfolder[$searchresult->intref1] = $searchresult->folderid;
            }
        }
    }

    if (!empty($contentids)) {
        // Gather content social data.
        $contentsocialdata = notifications::get_activities($permissions->activeuserid, $contentids, $permissions->groupingid, $permissions->managecontent);
        if ($contentsocialdata) {
            foreach ($contentsocialdata as $key => $socialitem) {
                $socialdatatotal = 0;
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

                // Check if social item is double digit.
                $socialitem = util::check_item_double_digit($socialitem);

                $socialdatatotal = $socialitem->comments + $socialitem->inspired +
                        $socialitem->mademelaugh + $socialitem->favourite;

                $contentsocialdata[$key]->socialdatatotal = $socialdatatotal;
                $contentsocialdata[$key]->socialdata = $socialitem;
            }
        }

        // Process content data.
        $resultingcontents = stream::get_contents_by_ids(
                $USER->id, $contentids, $permissions->feature_contentreciprocalaccess);
        if ($resultingcontents !== false) {
            foreach ($resultingcontents as $content) {

                // Process content locking.
                if (($content->levelcontainer > 0) && ($content->userid == $permissions->activeuserid)) {
                    $content = lock::determine_lock_status($content);
                }

                $viewuser = $USER;
                $context = context_module::instance($cm->id);
                $content = renderer_utils::content_type_image($content, $context);

                $contentthumbnailfileurl = $content->contenttypeimage;

                $visibility = $visibilityid = (int)$content->visibility;
                if ($visibility < 0) {
                    $visibility = content::VISIBILITY_GROUP;
                }

                $itemsharewith = '';
                // Set icon for content.
                // Get icon content in folder only.
                if (!empty($contentidsfolder[$content->id]) && $visibility == content::VISIBILITY_INFOLDERONLY) {
                    $folderdata = content::get($contentidsfolder[$content->id]);
                    $visibility = $visibilityid = (int)$folderdata->visibility;
                    if ($visibility < 0) {
                        $visibility = content::VISIBILITY_GROUP;
                    }
                }

                switch ($visibility) {
                    case content::VISIBILITY_MODULE:
                        $contenticon = $OUTPUT->image_url('mymodule_rgb_32px', 'openstudio');
                        $itemsharewith = get_string('contentitemsharewithmymodule', 'openstudio');
                        break;

                    case content::VISIBILITY_GROUP:
                        $contenticon = $OUTPUT->image_url('share_with_my_group_rgb_32px', 'openstudio');
                        $itemsharewith = get_string('contentitemsharewithgroup', 'openstudio',
                                group::get_name(abs($visibilityid)));
                        break;

                    case content::VISIBILITY_ALLGROUPS:
                        $contenticon = $OUTPUT->image_url('share_with_my_group_rgb_32px', 'openstudio');
                        $itemsharewith = get_string('contentitemsharewithallmytutorgroups', 'openstudio');
                        break;

                    case content::VISIBILITY_WORKSPACE:
                    case content::VISIBILITY_PRIVATE:
                        $contenticon = $OUTPUT->image_url('onlyme_rgb_32px', 'openstudio');
                        $itemsharewith = get_string('contentitemsharewithonlyme', 'openstudio');
                        break;

                    case content::VISIBILITY_PRIVATE_PINBOARD:
                        $contenticon = $OUTPUT->image_url('onlyme_rgb_32px', 'openstudio');
                        $itemsharewith = get_string('contentitemsharewithonlyme', 'openstudio');
                        break;

                    case content::VISIBILITY_TUTOR:
                        $contenticon = $OUTPUT->image_url('share_with_tutor_rgb_32px', 'openstudio');
                        $itemsharewith = get_string('contentitemsharewithmytutor', 'openstudio');
                        break;

                    default:
                        $contenticon = $OUTPUT->image_url('onlyme_rgb_32px', 'openstudio');
                        $itemsharewith = get_string('contentitemsharewithonlyme', 'openstudio');
                        break;
                }

                // User picture.
                $user = user::get_user_by_id($content->userid);
                $content->userpicturehtml = util::render_user_avatar($renderer, $user);

                // View other user's work.
                if ($content->userid != $viewuser->id) {
                    $content->myworkview = true;
                } else {
                    $content->myworkview = false;
                }
                $content = renderer_utils::get_folder_data($id, $content, $vid);
                $content->viewuserworkurl = new moodle_url('/mod/openstudio/view.php',
                        array('id' => $id, 'vuid' => $content->userid, 'vid' => content::VISIBILITY_PRIVATE));

                $content->contenticon = $contenticon;
                $content->itemsharewith = $itemsharewith;
                $content->contentthumbnailurl = $contentthumbnailfileurl;
                $content->contentthumbnailalt = get_string('contentthumbnailalt', 'mod_openstudio',
                    renderer_utils::get_content_thumbnail_alt($content, $vid));
                $content->datetimeupdated = $content->timemodified ? userdate($content->timemodified, get_string('formattimedatetime', 'openstudio')) : null;

                $urlarray = ['id' => $id, 'sid' => $content->id, 'vuid' => $content->userid];
                if (!empty($contentidsfolder[$content->id])) {
                    $urlarray = ['id' => $id, 'sid' => $content->id, 'vuid' => $content->userid,
                            'folderid' => $contentidsfolder[$content->id]];
                }
                if (!empty($contentidsanchor[$content->id])) {
                    $content->contentlink = new moodle_url('/mod/openstudio/content.php',
                        $urlarray, $contentidsanchor[$content->id]);
                } else {
                    $content->contentlink = new moodle_url('/mod/openstudio/content.php', $urlarray);
                }

                if (isset($contentsocialdata[$content->id])) {
                    $content->socialdata = $contentsocialdata[$content->id]->socialdata;
                    $content->socialdatatotal = $contentsocialdata[$content->id]->socialdatatotal;
                }

                $contentdata->contents[] = $content;
            }
        }
    }
}

$contentdata->selectedgroupid = $groupid;

// Disable uneccessary functionalities.
$contentdata->adddisable = true;
$contentdata->filterdisable = true;
$contentdata->blockid = 0;

$contentdata->searchmessage = $searchtext;

if ($contentdata->total == 0) {
    $contentdata->searchmessage .= get_string('searchresultsummarynorcord', 'openstudio',
            array('name' => $placeholdertext2));
} else {
    if (($pagestart < $contentdata->nextpage) || ($pagestart > 0)) {
        // Need to paginate.
        if ($searchresultdata->isglobal) {
            $contentdata->searchmessage .= get_string('searchresultsummarycount', 'openstudio',
                    array('total' => $contentdata->allresults));
        } else {
            $contentdata->searchmessage .= get_string('searchresultsummarycount2', 'openstudio',
                    array('total' => $streamdatapagesize));
        }

        if ($pagestart > 0) {
            // Has previous page.
            $contentdata->prevurl = $CFG->wwwroot . '/mod/openstudio/';
            $contentdata->prevurl .= util::get_page_name_and_substitute_params(array(
                    'page' => ($pagestart - 1),
                    'nextstart' => 0));
        }

        if ($pagestart < $contentdata->nextpage) {
            // Has next page.
            $contentdata->nexturl = $CFG->wwwroot . '/mod/openstudio/';
            $contentdata->nexturl .= util::get_page_name_and_substitute_params(array(
                    'page' => ($pagestart + 1),
                    'nextstart' => $contentdata->nextstart));
        }
    } else {
        // No need to paginate.
        $contentdata->searchmessage .= get_string('searchresultsummarycount', 'openstudio',
                array('total' => $contentdata->total));
    }
}

// Generate stream html.
$PAGE->set_button($renderer->searchform($theme, $vid, $id, $groupid));

// Output stream html with wrapper for theme header and footer.
echo $renderer->header(); // Header.

echo $renderer->siteheader($coursedata, $permissions, $theme, $cm->name, '', $vid);

echo $renderer->body($cm->id, $cminstance, $theme, $vid, $permissions, $contentdata, $issearch = true); // Body.
$PAGE->requires->js_call_amd('mod_openstudio/viewhelper', 'init', [[
        'searchtext' => $searchtext]]);

echo $renderer->footer(); // Footer.

// Log page action.
util::trigger_event($cm->id, 'search_viewed', '',
        util::get_page_name_and_params(),
        util::format_log_info($searchtext));
