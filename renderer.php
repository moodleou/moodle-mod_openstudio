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

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\api\subscription;
use mod_openstudio\local\api\group;
use mod_openstudio\local\api\item;
use mod_openstudio\local\renderer_utils;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\api\user;
use mod_openstudio\local\api\notifications;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\util;
use mod_openstudio\local\util\defaults;
use mod_openstudio\local\notifications\notification;
use mod_openstudio\local\api\template;

/**
 * OpenStudio renderer.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_openstudio_renderer extends plugin_renderer_base {
    /**
     * This function renders the HTML fragment for the primary and secnodary
     * navigation for the Open Studio module.
     *
     * @param int $coursedata Course module data.
     * @param object $permissions The permission object for the given user/view.
     * @param object $theme The theme settings.
     * @param string $sitename Site name to display.
     * @param string $searchtext Search text to display.
     * @param int $viewmode View mode: module, group, studio, pinboard or workspace.
     * @return string The rendered HTM fragment.
     */
    public function siteheader(
            $coursedata, $permissions, $theme, $sitename = 'Design', $searchtext = '',
            $viewmode = content::VISIBILITY_MODULE) {
        global $OUTPUT, $PAGE, $USER, $CFG;
        // This will force the setting navigation appear in boost theme.
        $PAGE->force_settings_menu();
        $cm = $coursedata->cm;
        $cmid = $cm->id;

        $data = new stdClass();
        $data->sitename = $sitename;

        // Check if enable Email subscriptions.
        $data->enablesubscription = $permissions->feature_enablesubscription && !isguestuser();

        $menuhighlight = new stdClass;
        $menuhighlight->mymodule = false;
        $menuhighlight->mygroup = false;
        $menuhighlight->myactivity = false;
        $menuhighlight->mypinboard = false;
        $menuhighlight->people = false;

        // Placeholder text.
        $placeholdertext = '';
        switch ($viewmode) {
            case content::VISIBILITY_MODULE:
                $placeholdertext = $theme->thememodulename;
                $menuhighlight->mymodule = true;
                break;

            case content::VISIBILITY_GROUP:
                $placeholdertext = $theme->themegroupname;
                $menuhighlight->mygroup = true;
                break;

            case content::VISIBILITY_WORKSPACE:
            case content::VISIBILITY_PRIVATE:
                // Get user id that we need to show stream for.
                $vuid = optional_param('vuid', $USER->id, PARAM_INT);
                $currentuserid = $USER->id;
                $placeholdertext = $theme->themestudioname;
                $menuhighlight->myactivity = $vuid === $currentuserid;
                break;

            case content::VISIBILITY_PRIVATE_PINBOARD:
                $placeholdertext = $theme->themepinboardname;
                $menuhighlight->mypinboard = true;
                break;

            case content::VISIBILITY_PEOPLE:
                $data->enablesubscription = false;
                $menuhighlight->people = true;
                break;
        }
        $data->placeholdertext = $placeholdertext;

        // Render navigation.
        $data->navigation = array();

        $navigationurls = renderer_utils::navigation_urls($cmid);

        $menuitem = array(
                'hassubnavigation' => false,
                'subnavigation' => array()
        );

        // Generate shared content items.
        if ($permissions->feature_module) {
            $modulename = get_string('settingsthemehomesettingsmodule', 'openstudio');
            if (!empty($theme->thememodulename)) {
                $modulename = $theme->thememodulename;
            }
            $submenuitem = array(
                    'name' => $modulename,
                    'url' => $navigationurls->mymoduleurl,
                    'pix' => $OUTPUT->image_url('mymodule_rgb_32px', 'openstudio'),
                    'active' => $menuhighlight->mymodule
            );
            $menuitem['hassubnavigation'] = true;
            $menuitem['subnavigation'][] = $submenuitem;
        }

        if ($permissions->feature_group) {
            $groupname = get_string('settingsthemehomesettingsgroup', 'openstudio');
            if (!empty($theme->themegroupname)) {
                $groupname = $theme->themegroupname;
            }
            $submenuitem = array(
                    'name' => $groupname,
                    'url' => $navigationurls->mygroupurl,
                    'pix' => $OUTPUT->image_url('share_with_my_group_rgb_32px', 'openstudio'),
                    'active' => $menuhighlight->mygroup
            );
            $menuitem['hassubnavigation'] = true;
            $menuitem['subnavigation'][] = $submenuitem;
        }

        if (!empty($menuitem['subnavigation'])) {
            // When has one sub menu item, display sub item as main menu.
            if (count($menuitem['subnavigation']) > 1) {
                $menuitem['name'] = get_string('menusharedcontent', 'openstudio');
                $menuitem['url'] = '#';
                $menuitem['pix'] = $OUTPUT->image_url('shared_content_rgb_32px', 'openstudio');
                $menuitem['class'] = 'shared-content';
                $menuitem['active'] = $menuhighlight->mymodule || $menuhighlight->mygroup;
                $data->navigation[] = $menuitem;
            } else {
                $menuitem = array(
                    'hassubnavigation' => false,
                    'subnavigation' => array(),
                    'name' => get_string('menusharedcontent', 'openstudio'),
                    'url' => $submenuitem['url'],
                    'pix' => $OUTPUT->image_url('shared_content_rgb_32px', 'openstudio'),
                    'class' => 'shared-content',
                    'active' => $menuhighlight->mymodule || $menuhighlight->mygroup
                );
                $data->navigation[] = $menuitem;
            }
        }

        if ($permissions->feature_module || $permissions->feature_group) {
            // Generate people items.
            $menuitem['name'] = get_string('menupeople', 'openstudio');
            $menuitem['url'] = $navigationurls->peoplemoduleurl;
            $menuitem['pix'] = $OUTPUT->image_url('people_rgb_32px', 'openstudio');
            $menuitem['class'] = 'people';
            $menuitem['active'] = $menuhighlight->people;
            $menuitem['hassubnavigation'] = false;
            $data->navigation[] = $menuitem;
        }

        // Generate my content items.
        $menuitem = array(
                'hassubnavigation' => false,
                'subnavigation' => array()
        );

        $subnavigations = array();
        if ($permissions->feature_studio || ($permissions->activitydata->used > 0)) {
            $studioname = get_string('settingsthemehomesettingsstudio', 'openstudio');
            if (!empty($theme->themestudioname)) {
                $studioname = $theme->themestudioname;
            }
            $submenuitem = array(
                    'name' => $studioname,
                    'url' => $navigationurls->myworkurl,
                    'pix' => $OUTPUT->image_url('activity_rgb_32px', 'openstudio'),
                    'active' => $menuhighlight->myactivity
            );
            $menuitem['hassubnavigation'] = true;
            $subnavigations[] = $submenuitem;
        }

        if (!isguestuser() && ($permissions->feature_pinboard || ($permissions->pinboarddata->usedandempty > 0))) {
            $pinboardname = get_string('settingsthemehomesettingspinboard', 'openstudio');
            if (!empty($theme->themepinboardname)) {
                $pinboardname = $theme->themepinboardname;
            }
            $submenuitem = array(
                    'name' => $pinboardname,
                    'url' => $navigationurls->pinboardurl,
                    'pix' => $OUTPUT->image_url('pinboard_rgb_32px', 'openstudio'),
                    'active' => $menuhighlight->mypinboard
            );
            $menuitem['hassubnavigation'] = true;
            $subnavigations[] = $submenuitem;
        }

        if (!empty($subnavigations)) {
            // When has one sub menu item, display sub item as main menu.
            if (count($subnavigations) > 1) {
                $menuitem['hassubnavigation'] = true;

                foreach ($subnavigations as $sub) {
                    $menuitem['subnavigation'][] = $sub;
                }

                $menuitem['name'] = get_string('menumycontent', 'openstudio');
                $menuitem['url'] = '#';
                $menuitem['pix'] = $OUTPUT->image_url('openstudio_rgb_32px', 'openstudio');
                $menuitem['class'] = 'my-content';
                $menuitem['active'] = $menuhighlight->myactivity || $menuhighlight->mypinboard;
                $data->navigation[] = $menuitem;
            } else {
                $menuitem = array(
                    'hassubnavigation' => false,
                    'subnavigation' => array(),
                    'name' => get_string('menumycontent', 'openstudio'),
                    'url' => $submenuitem['url'],
                    'pix' => $OUTPUT->image_url('openstudio_rgb_32px', 'openstudio'),
                    'class' => 'my-content',
                    'active' => $menuhighlight->myactivity || $menuhighlight->mypinboard
                );
                $data->navigation[] = $menuitem;
            }
        }

        if (!isguestuser()) {
            $data->notificationicon = $OUTPUT->image_url('notifications_rgb_32px', 'openstudio');
            $data->notifications = [];
            $notifications = notifications::get_current($cm->instance, $USER->id, defaults::NOTIFICATIONLIMITMAX);
            foreach ($notifications as $notification) {
                $data->notifications[] = $notification->export_for_template($this->output);
            }
            $data->notificationnumber = array_reduce($notifications, function($carry, notification $notification) {
                $carry += $notification->timeread ? 0 : 1;
                return $carry;
            });
            $stopstring = get_string('stopnotificationsforcontent', 'mod_openstudio');
            $stopicon = new \pix_icon('stop_notification', $stopstring, 'mod_openstudio', [
                    'width' => 16,
                    'height' => 16
            ]);
            $data->notificationstopicon = $stopicon->export_for_template($this->output);

            $addtodashboard = '';
            if (file_exists("{$CFG->dirroot}/blocks/externaldashboard/classes/backend.php")) {
                $addtodashboard = block_externaldashboard_backend::render_favourites_button($PAGE->cm, false);
            }
            $data->addtodashboard = $addtodashboard;

            // Subscription.
            $subscriptionconstant = array(
                    "FORMAT_HTML" => subscription::FORMAT_HTML,
                    "FORMAT_PLAIN" => subscription::FORMAT_PLAIN,
                    "FREQUENCY_HOURLY" => subscription::FREQUENCY_HOURLY,
                    "FREQUENCY_DAILY" => subscription::FREQUENCY_DAILY);

            $PAGE->requires->strings_for_js(array(
                    'subscriptiondialogheader', 'subscriptiondialogcancel', 'subscribe', 'unsubscribe',
                    'subscribetothisstudio'), 'mod_openstudio');

            $this->page->requires->js_call_amd('mod_openstudio/subscribe', 'init', [[
                    'constants' => $subscriptionconstant,
                    'openstudioid' => $coursedata->cminstance->id,
                    'userid' => $permissions->activeuserid,
                    'cmid' => $cmid]]);

            $this->page->requires->strings_for_js(['stopnotifications', 'stopnotificationsfor'], 'mod_openstudio');
            $this->page->requires->strings_for_js(['closebuttontitle', 'cancel'], 'moodle');

            $this->page->requires->js_call_amd('mod_openstudio/notificationlist', 'init', [
                    'cmid' => $cmid,
                    'followflag' => flags::FOLLOW_CONTENT]);

            $subscriptiondata = subscription::get(
                    $permissions->activeuserid,
                    $permissions->activecminstanceid);

            if ($subscriptiondata) {
                $data->subscriptionid = $subscriptiondata[subscription::MODULE]->id;
            } else {
                $data->subscriptionid = false;
            }
        }

        return $this->render_from_template('mod_openstudio/header', $data);

    }

    /**
     * This function renders the HTML for search form.
     * @param object $theme The theme settings.
     * @param int $viewmode View mode: module, group, studio, pinboard or workspace.
     * @param int $openstudioid Open studio instance ID
     * @param int $groupid Group ID
     * @return string The rendered HTM search form.
     */
    public function searchform($theme, $viewmode, $openstudioid, $groupid = 0) {
        global $OUTPUT, $CFG;
        $data = new stdClass();

        // Placeholder text.
        $placeholdertext = '';
        switch ($viewmode) {
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

        $data->placeholdertext = $placeholdertext;
        $data->searchlink = $CFG->wwwroot.'/mod/openstudio/search.php';
        $data->helplink = $CFG->wwwroot.'/help.php';
        $data->iconsearch = $OUTPUT->image_url('i/search');
        $data->id = $openstudioid;
        $data->vid = $viewmode;
        $data->groupid = $groupid;

        return $this->render_from_template('mod_openstudio/search_form', $data);
    }

    /**
     * This function renders the HTML fragment for the content edit form.
     *
     * @param string $contenteditform The content edit form fragment.
     * @param object $data The content data.
     * @return string The rendered HTM fragment.
     */
    public function content_edit($contenteditform, $data) {
        global $OUTPUT;
        $foldermode = false;
        $contentmode = false;
        $folderdetails = false;
        if (!isset($data) || empty($data)) {
            throw new coding_exception('Wrong data format');
        }

        $data = (object)$data;
        if (($data->contenttype == content::TYPE_FOLDER_CONTENT && !$data->contentfolder) ||
                ($data->folderdetails && $data->contenttype == content::TYPE_FOLDER)) {
            $foldermode = true;
            if ($data->folderdetails == true) {
                $folderdetails = true;
            }
        }
        if (($data->contentdetails && $data->folderdetails == false) ||
                ($data->contentfolder && $data->folderdetails && $data->contentdetails)) {
            $contentmode = true;
        }
        $data->contentmode = $contentmode;
        $data->foldermode = $foldermode;
        $data->folderdetails = $folderdetails;
        $createfolderlink = new moodle_url('/mod/openstudio/contentedit.php',
                array('id' => $data->cmid, 'lid' => 0, 'sid' => 0, 'ssid' => 0, 'type' => content::TYPE_FOLDER_CONTENT));
        $data->createfolderlink = $createfolderlink;
        $data->addcontenticon = $OUTPUT->image_url('add_content_rgb_32px', 'openstudio');
        $data->editform = $contenteditform;

        return $this->render_from_template('mod_openstudio/content_edit', $data);
    }

    /**
     * This function renders the HTML fragment for the body content of Open Studio.
     *
     * @param int $cmid The course module id.
     * @param object $cminstance The course module instance.
     * @param object $theme The theme settings.
     * @param int $viewmode View mode: module, group, studio, pinboard or workspace.
     * @param object $permissions The permission object for the given user/view.
     * @param object $contentdata The content records to display.
     * @param boolean $issearch Detect search behaviour
     * @return string The rendered HTM fragment.
     */
    public function body($cmid, $cminstance, $theme, $viewmode, $permissions, $contentdata, $issearch = false) {
        global $OUTPUT, $USER;

        $placeholdertext = '';
        $subplaceholdertext = '';
        $othersubtitle = '';
        $selectview = false;
        $myactivities = false;
        $showprofilebarview = false;
        $showsharetoviewbanner = true;
        $blocksdata = array();
        $contentdata->ismypinboard = false;
        $contentdata->ismyactivity = false;
        $contentdata->permissions = $permissions;

        switch ($viewmode) {
            case content::VISIBILITY_MODULE:
                $placeholdertext = $theme->thememodulename;
                $subplaceholdertext = get_string('subtitleofthememodulename', 'openstudio');
                $othersubtitle = 'mymodule-subttile';
                break;

            case content::VISIBILITY_GROUP:
                $placeholdertext = $theme->themegroupname;
                $othersubtitle = 'mygroup-subttile';
                $subplaceholdertext = get_string('subtitleofthemegroupname', 'openstudio');
                $selectview = true;
                break;

            case content::VISIBILITY_WORKSPACE:
            case content::VISIBILITY_PRIVATE:
                $vuid = optional_param('vuid', $USER->id, PARAM_INT);
                $showprofilebarview = true;
                $placeholdertext = $theme->themestudioname;
                $subplaceholdertext = get_string('subtitleofthemegroupprivatename', 'openstudio');

                if (!$issearch) {
                    $myactivities = true;
                }
                $blocksdata = levels::get_records(1, $permissions->activecminstanceid);
                $contentdata->ismyactivity = true;

                // Set selected block.
                foreach ($blocksdata as $key => $block) {
                    $blocksdata[$key]->selected = false;
                    if ($contentdata->blockid == $block->id) {
                        $blocksdata[$key]->selected = true;
                    }
                }
                $othersubtitle = 'activities-subttile';
                $showsharetoviewbanner = !($vuid === $USER->id);
                break;

            case content::VISIBILITY_PRIVATE_PINBOARD:
                $showprofilebarview = true;
                $placeholdertext = $theme->themepinboardname;
                $subplaceholdertext = get_string('subtitleofthemepinboardname', 'openstudio');
                $othersubtitle = 'pinboard-subttile';
                $contentdata->ismypinboard = true;
                $showsharetoviewbanner = false;
                break;
        }

        $grouplist = group::group_list(
                    $permissions->activecid, $permissions->groupingid,
                    $permissions->activeuserid, $permissions->groupmode);

        $showmultigroup = false;
        $groupitem = array();
        if ($grouplist) {
            foreach ($grouplist as $group) {
                $groupitem[$group->groupid] = (object) [
                    'groupid' => $group->groupid,
                    'selectedgroup' => $contentdata->selectedgroupid == $group->groupid ? true : false,
                    'vid' => content::VISIBILITY_GROUP,
                    'name' => $group->name
                ];
            }

            // Add All option to group select box when number of group > 1.
            if (count($groupitem) > 1) {
                $showmultigroup = true;
                $groupitem[0] = (object) [
                    'groupid' => 0,
                    'selectedgroup' => $contentdata->selectedgroupid == 0 ? true : false,
                    'vid' => content::VISIBILITY_GROUP,
                    'name' => get_string('filterall', 'openstudio')
                ];
            }

            // Sort group by key to make sure All option on the first.
            ksort($groupitem);
        }

        $contentdata->cmid = $cmid;
        $contentdata = renderer_utils::profile_bar($permissions, $cminstance, $contentdata);
        $contentdata->contentflagfavourite = flags::FAVOURITE;
        $contentdata->contentflagsmile = flags::MADEMELAUGH;
        $contentdata->contentflaginspire = flags::INSPIREDME;
        $contentdata->showprofilebarview = $showprofilebarview;
        $contentdata->groupitems = array_values($groupitem);
        $contentdata->showmultigroup = $showmultigroup;
        $contentdata->placeholdertext = $placeholdertext;
        $contentdata->subplaceholdertext = $subplaceholdertext;
        $contentdata->othersubtitle = $othersubtitle;
        $contentdata->selectview = $selectview;
        $contentdata->myactivities = $myactivities;
        $contentdata->blocksdata = property_exists($contentdata,
            'openstudio_view_filters') ? $contentdata->openstudio_view_filters->fblockdataarray : array();
        $contentdata->viewedicon = $OUTPUT->image_url('viewed_rgb_32px', 'openstudio');
        $contentdata->commentsicon = $OUTPUT->image_url('comments_rgb_32px', 'openstudio');
        $contentdata->inspirationicon = $OUTPUT->image_url('inspiration_rgb_32px', 'openstudio');
        $contentdata->participationicon = $OUTPUT->image_url('participation_rgb_32px', 'openstudio');
        $contentdata->favouriteicon = $OUTPUT->image_url('favourite_rgb_32px', 'openstudio');
        $contentdata->commentsgreyicon = $OUTPUT->image_url('comments_grey_rgb_32px', 'openstudio');
        $contentdata->inspirationgreyicon = $OUTPUT->image_url('inspiration_grey_rgb_32px', 'openstudio');
        $contentdata->participationgreyicon = $OUTPUT->image_url('participation_grey_rgb_32px', 'openstudio');
        $contentdata->favouritegreyicon = $OUTPUT->image_url('favourite_grey_rgb_32px', 'openstudio');
        $contentdata->vid = $viewmode;
        $contentdata->lockicon = $OUTPUT->image_url('lock_grey_rgb_32px', 'openstudio');
        $contentdata->requestfeedbackicon = $OUTPUT->image_url('request_feedback_white_rgb_32px', 'openstudio');
        $contentdata->createcontentthumbnail = $OUTPUT->image_url('uploads_rgb_32px', 'openstudio');

        $contentdata->contentediturl = new moodle_url('/mod/openstudio/contentedit.php', array(
                   'id' => $cmid, 'vid' => $viewmode, 'lid' => 0, 'sid' => 0, 'type' => 0, 'sstsid' => 0));

        $contentdata->hasoneblock = count($contentdata->blocksdata) == 1 ? true : false;

        if ($permissions->feature_enablefolders) {
            $folderlink = new moodle_url('/mod/openstudio/contentedit.php', array(
                    'id' => $cmid, 'vid' => $viewmode, 'lid' => 0, 'sid' => 0, 'ssid' => 0,
                    'type' => content::TYPE_FOLDER_CONTENT));

            $contentdata->folderediturl = $folderlink;
            $contentdata->createfolderthumbnail = $OUTPUT->image_url('create_folder_rgb_32px', 'openstudio');
        }
        $contentdata->feature_enablefolders = $permissions->feature_enablefolders;
        $contentdata->available = $permissions->pinboarddata->available;

        if ($contentdata->contents && !$myactivities && !empty($contentdata->streamdatapagesize)) {
            $pb = renderer_utils::openstudio_render_paging_bar($contentdata);
            $paging = $this->render($pb);
            $contentdata->paging = $paging;
            $contentdata->multiplepages = $contentdata->streamdatapagesize < $contentdata->total;
        }
        $contentdata->available = $permissions->pinboarddata->available;
        $contentdata->sharetoview = $permissions->feature_contentreciprocalaccess;
        $contentdata->showsharetoviewbanner = $showsharetoviewbanner;
        $contentdata->sharetoviewhelpicon = $OUTPUT->help_icon('sharetoviewbanner', 'openstudio');

        // Prepare select from (all/pinboard/blocks) filter.
        if (property_exists($contentdata, 'openstudio_view_filters')) {
            $contentdata = renderer_utils::filter_area($contentdata);

            // Prepare post types option for filter.
            $contentdata = renderer_utils::filter_post_types($contentdata);

            // Prepare user flags option for filter.
            $contentdata = renderer_utils::filter_user_flags($contentdata);

            // Prepare select status option for filter.
            $contentdata = renderer_utils::filter_select_status($contentdata);

            // Prepare scope option for filter.
            $contentdata = renderer_utils::filter_scope($contentdata);
        }

        $this->page->requires->js_call_amd('mod_openstudio/viewhelper', 'init', [[
                'hasselectfrom' => !$contentdata->ismypinboard,
                'searchtext' => '',
                'hasfilterform' => true,
        ]]);

        $contentdata->filterfromhelpicon = $this->output->help_icon('filterchoosefromhelpicon', 'mod_openstudio');

        return $this->render_from_template('mod_openstudio/body', $contentdata);
    }

    /**
     * This function renders the HTML fragment for the people page of Open Studio.
     *
     * @param object $permissions The permission object for the given user/view.
     * @param object $peopledata The people records to display.
     * @return string The rendered HTM fragment.
     */
    public function people_page($permissions, $peopledata) {
        global $OUTPUT;

        $grouplist = group::group_list(
                $permissions->activecid, $permissions->groupingid,
                $permissions->activeuserid, $permissions->groupmode);

        $groupitem = array();

        // We need to add My Module to group selection.
        if ($permissions->feature_module) {
            $groupitem[-1] = (object) [
                    'groupid' => -1,
                    'selectedgroup' => $peopledata->selectedgroupid == -1 ? true : false,
                    'vid' => content::VISIBILITY_MODULE,
                    'name' => get_string('contentformvisibilitymodule', 'openstudio')
            ];
        }

        $showmultigroup = false;
        if ($grouplist && $permissions->feature_group) {
            $groupcount = 0;
            foreach ($grouplist as $group) {
                $groupcount ++;
                $groupitem[$group->groupid] = (object) [
                        'groupid' => $group->groupid,
                        'selectedgroup' => $peopledata->selectedgroupid == $group->groupid ? true : false,
                        'vid' => content::VISIBILITY_GROUP,
                        'name' => $group->name
                ];
            }

            // Show all option when number of group > 1.
            if ($groupcount > 1) {
                $groupitem[0] = (object)[
                    'groupid' => 0,
                    'selectedgroup' => $peopledata->selectedgroupid == 0 ? true : false,
                    'vid' => content::VISIBILITY_GROUP,
                    'name' => get_string('filterallgroup', 'openstudio')
                ];
            }

            // Sort group by key to make sure All option on the first.
            ksort($groupitem);
        }

        $showmultigroup = (count($groupitem) > 1);

        $peopledata->groupitems = array_values($groupitem);
        $peopledata->showmultigroup = $showmultigroup;
        $peopledata->commentsicon = $OUTPUT->image_url('comments_rgb_32px', 'openstudio');
        $peopledata->viewedicon = $OUTPUT->image_url('viewed_rgb_32px', 'openstudio');
        if ($peopledata && !empty($peopledata->streamdatapagesize)) {
            $pb = renderer_utils::openstudio_render_paging_bar($peopledata);
            $paging = $this->render($pb);
            $peopledata->paging = $paging;
            $peopledata->multiplepages = $peopledata->streamdatapagesize < $peopledata->total;
        }

        $this->page->requires->js_call_amd('mod_openstudio/viewhelper', 'init', [
                'hasselectfrom' => false,
                'searchtext' => '',
                'hasfilterform' => false,
        ]);

        return $this->render_from_template('mod_openstudio/people_page', $peopledata);
    }

    /**
     * This function renders the HTML fragment for the content detail page of Open Studio.
     *
     * @param int $cm The course module object.
     * @param object $permissions The permission object for the given user/view.
     * @param object $contentdata The content detail to display.
     * @param int $cminstance The course module instance.
     * @param bool $donotexport check if this is exported.
     * @return string The rendered HTML fragment.
     */
    public function content_page($cm, $permissions, $contentdata, $cminstance, $donotexport = true) {
        global $CFG, $PAGE, $OUTPUT, $USER;
        $openstudioid = $cminstance->id;
        $contentdata->cmid = $cmid = $cm->id;
        $contentdata->donotexport = $donotexport;
        $context = context_module::instance($cm->id);
        if (!property_exists($contentdata, 'profilebarenable')) {
            $contentdata->profilebarenable = true;
        }

        if ($contentdata->profilebarenable === true) {
            $contentdata = renderer_utils::profile_bar($permissions, $cminstance, $contentdata, $donotexport, $contentdata->userid);
        }

        $contentdata = renderer_utils::content_details($cmid, $permissions, $contentdata, $contentdata->iscontentversion, $donotexport);
        $contentdata->contentfavouritetotaldonotexport = false;
        $contentdata->contentsmiletotaldonotexport = false;
        $contentdata->contentinspiretotaldonotexport = false;
        // Not need generate full data for a content version.
        if (!$contentdata->iscontentversion) {
            $contentdata->tagsraw = $this->get_tagsraw_for_template($cm, $contentdata->tagsraw);

            // Generate content flags.
            $contentdata = renderer_utils::content_flags($cmid, $permissions, $contentdata);
            if (!$donotexport) {
                $contentdata->contentfavouritetotaldonotexport = $contentdata->contentfavouritetotal > 0;
                $contentdata->contentsmiletotaldonotexport = $contentdata->contentsmiletotal > 0;
                $contentdata->contentinspiretotaldonotexport = $contentdata->contentinspiretotal > 0;
            }

            // Process lock.
            renderer_utils::process_content_lock($contentdata, $permissions, $cmid);
            // Process comment.
            renderer_utils::process_content_comment($contentdata, $permissions, $cmid, $cminstance, $donotexport);

            // Get copies count.
            $contenthash = item::generate_hash($contentdata->id);
            $contentdata->contentcopycount = item::count_occurences($contenthash, $cmid);
            $contentdata->contentcopyenable = $contentdata->contentcopycount > 1 ? true : false;

            $contentexifinfo = array();
            $contentmapenable = false;
            $contentmetadataenable = false;
            $metadatamake = '';
            $metadatamodel = '';
            $metadatafocal = '';
            $metadataexposure = '';
            $metadataaperture = '';
            $metadataiso = '';
            $contentgpslat = '';
            $contentgpslng = '';
            if ($contentdata->contenttype == content::TYPE_IMAGE) {
                $contentexifinfo = content::get_image_exif_data(
                        $permissions->activecmcontextid , 'mod_openstudio', 'content',
                        $contentdata->fileid, '/', $contentdata->content);

                // Show image GPS data if user requested it and it is available.
                if (($contentdata->showextradata & content::INFO_GPSDATA) && !empty($contentexifinfo['GPSData'])) {
                    $contentmapenable = true;
                    $contentgpslat = $contentexifinfo['GPSData']['lat'];
                    $contentgpslng = $contentexifinfo['GPSData']['lng'];
                }

                // Show image EXIF metadata if user requested it and it is available.
                if ($contentdata->showextradata & content::INFO_IMAGEDATA) {
                    $metadatamake = empty($contentexifinfo['Make']) ? '' : $contentexifinfo['Make'];

                    if (strlen($metadatamake) < 2) {
                        if (empty($contentexifinfo['UndefinedTag:0xA433'])) {
                            $metadatamake = '';
                        } else {
                            $metadatamake = $contentexifinfo['UndefinedTag:0xA433'];
                        }
                    }

                    $metadatamodel = empty($contentexifinfo['Model']) ? '' : $contentexifinfo['Model'];

                    if (empty($contentexifinfo['FocalLengthIn35mmFilm'])) {
                        $metadatafocal = '';
                    } else {
                        $metadatafocal = $contentexifinfo['FocalLengthIn35mmFilm'];
                    }

                    $metadataexposure = empty($contentexifinfo['ExposureTime']) ? '' : $contentexifinfo['ExposureTime'];

                    if (!empty($contentexifinfo['COMPUTED'])
                        && !empty($contentexifinfo['COMPUTED']['ApertureFNumber'])) {
                        $metadataaperture = $contentexifinfo['COMPUTED']['ApertureFNumber'];
                    } else {
                        $metadataaperture = empty($contentexifinfo['FNumber']) ? '' : $contentexifinfo['FNumber'];

                    }

                    $metadataiso = empty($contentexifinfo['ISOSpeedRatings']) ? '' : $contentexifinfo['ISOSpeedRatings'];

                    if (!empty($metadatamake)
                        || !empty($metadatamodel)
                        || !empty($metadatafocal)
                        || !empty($metadataexposure)
                        || !empty($metadataaperture)
                        || !empty($metadataiso)) {
                        $contentmetadataenable = true;
                    }
                }

                $contentdata->metadatamake = $metadatamake;
                $contentdata->metadatamodel = $metadatamodel;
                $contentdata->metadatafocal = $metadatafocal;
                $contentdata->metadataexposure = $metadataexposure;
                $contentdata->metadataaperture = $metadataaperture;
                $contentdata->metadataiso = $metadataiso;
                $contentdata->contentmetadataenable = $contentmetadataenable;
                $contentdata->contentgpslat = $contentgpslat;
                $contentdata->contentgpslng = $contentgpslng;
                $contentdata->contentmapenable = $contentmapenable;
            }
        } else {
            $contentdata->contentlockenable = false;
        }

        // Process delete.
        $deleteenable = renderer_utils::process_content_delete($contentdata, $permissions, $cmid);

        // Check edit content permission.
        $contenteditenable = $deleteenable;
        $editparams = array('id' => $cmid, 'sid' => $contentdata->id, 'ssid' => $contentdata->folderid);
        $contenteditlink = new moodle_url('/mod/openstudio/contentedit.php', $editparams);

        if (($contentdata->l1id > 0) || ($contentdata->l1id == 0) || $permissions->managecontent) {
            if (lock::content_show_crud($contentdata, $permissions) || $permissions->managecontent) {
                $contentdeleteenable = true;
            }
        }

        // Check owner content permission.
        $contentdata->mycontent = false;
        if ($contentdata->userid == $USER->id) {
            $contentdata->mycontent = true;
        }

        // Handle show/hide archive button.
        $contentdata->showarchivebutton = $contentdata->mycontent;
        $contentdata->contentviewversionlink = new moodle_url('/mod/openstudio/content.php',
                array('id' => $cmid, 'sid' => $contentdata->id, 'vuid' => $contentdata->userid, 'version' => 1));
        $contentdata->contenteditenable = $contenteditenable;
        $contentdata->contenteditlink = $contenteditlink;
        $contentdata->actionenable = $contentdata->contentdeleteenable || $contentdata->contenteditenable
                || $contentdata->contentlockenable;

        // Check if maximize feature is enable.
        $contentdata->maximizeenable = ($contentdata->contenttypeimage || $contentdata->contenttypeiframe);

        // Check archive post permission.
        $contentdata->contentarchivepostenable = empty($contentdata->isfoldercontent);
        if ($contentdata->contentarchivepostenable == false) {
            $contentdata->showarchivebutton = false;
        }

        // Render content versions.
        $contentversions = array();
        $hascontentversions = false;

        if (!empty($contentdata->contentversions)) {
            foreach ($contentdata->contentversions as $contentversion) {
                $contentviewversionlink = new moodle_url('/mod/openstudio/content.php', array(
                        'id' => $cmid, 'sid' => $contentversion->id, 'vuid' => $contentdata->userid,
                        'folderid' => $contentdata->folderid, 'contentversion' => 1));
                $contentversion->contentviewversionlink = $contentviewversionlink->out(false);
                $contentversions[] = renderer_utils::content_details($cmid, $permissions, $contentversion, true);
            }
            $hascontentversions = true;
        }

        $contentdata->hascontentversions = $hascontentversions;
        $contentdata->contentversions = $contentversions;
        $contentdata->viewuserworkurl = new \moodle_url('/mod/openstudio/view.php',
                array('id' => $cmid, 'vuid' => $contentdata->userid, 'vid' => content::VISIBILITY_PRIVATE));

        $contentrestoreversionurl = new moodle_url('/mod/openstudio/content.php', array(
                'id' => $cmid, 'sid' => $contentdata->id, 'vuid' => $contentdata->userid,
                'folderid' => $contentdata->folderid, 'restoreversion' => 1));
        $contentdata->contentrestoreversionurl = $contentrestoreversionurl->out(false);

        // Folder name not created yet.
        if ($contentdata->folderid) {
            $contentfolder = folder::get($contentdata->folderid);
            if (!$contentdata->name && $contentfolder->l3id) {
                $contentdata->name = $contentfolder->l1name . ' - ' . $contentfolder->l2name .
                        ' - ' . $contentfolder->l3name;
            }
        }

        $descriptionarea = $contentdata->iscontentversion ? 'descriptionversion' : 'description';
        $description = file_rewrite_pluginfile_urls($contentdata->description, 'pluginfile.php', $context->id, 'mod_openstudio',
                $descriptionarea, $contentdata->id);
        $contentdata->description = format_text($description, $contentdata->textformat);

        return $this->render_from_template('mod_openstudio/content_page', $contentdata);
    }

    /**
     * Output level data, either as HTML for display before importing, or as raw XML for export.
     *
     * @param \mod_openstudio\output\levelxml $levelxml
     * @param bool $html If true, format the data as an HTML list. Otherwise output as raw XML.
     * @return bool|string
     */
    public function output_level_xml(mod_openstudio\output\levelxml $levelxml, $html = false) {
        if ($html) {
            $template = 'format_level_xml';
        } else {
            $template = 'output_level_xml';
        }
        $context = $levelxml->export_for_template($this->output);
        return $this->render_from_template('mod_openstudio/' . $template, $context);
    }

    /**
     * This function renders the HTML fragment for the Import page of Open Studio.
     *
     * @param \moodleform $uform The import upload form
     * @param string $formerror Form error message
     * @return string The rendered HTML fragment.
     */
    public function import_page($uform, $formerror) {
        $data = new stdClass();
        $data->uformhtml = $uform->render();
        $data->formerror = $formerror;

        return $this->render_from_template('mod_openstudio/import', $data);
    }

    /**
     * This function renders the HTML fragment for the report usage content of Open Studio.
     *
     * @param array $summarydata Summary data of report usage.
     * @return bool|string
     * @throws moodle_exception
     */
    public function report_summary($summarydata) {
        $data = new stdClass();
        $data->summarydata = $summarydata;

        return $this->render_from_template('mod_openstudio/reportsummary', $data);
    }

    /**
     * This function renders the HTML fragment for the report usage content of Open Studio.
     *
     * @param array $contentdataactivity
     * @param array $contentdatavisbility
     * @return bool|string
     * @throws moodle_exception
     */
    public function report_contentactivity($contentdataactivity, $contentdatavisbility) {
        $data = new stdClass();
        $data->contentdataactivity = $contentdataactivity;
        $data->contentdatavisbility = $contentdatavisbility;

        return $this->render_from_template('mod_openstudio/reportcontentactivity', $data);
    }

    /**
     * This function renders the HTML fragment for the report usage content of Open Studio.
     *
     * @param array $flagdata
     * @return bool|string
     * @throws moodle_exception
     */
    public function report_flag($flagdata) {
        $data = new stdClass();
        $data->flagdata = $flagdata;

        return $this->render_from_template('mod_openstudio/reportflag', $data);
    }

    /**
     * This function renders the HTML fragment for the report usage content of Open Studio.
     *
     * @param array $summarydata
     * @param array $storage
     * @return bool|string
     * @throws moodle_exception
     */
    public function report_storage($summarydata, $storage) {
        $data = new stdClass();
        $data->summarydata = $summarydata;
        $data->storage = $storage;

        return $this->render_from_template('mod_openstudio/reportstorage', $data);
    }

    /**
     * This function renders the HTML fragment for the report usage content of Open Studio.
     *
     * @param array $activitylog
     * @return bool|string
     * @throws moodle_exception
     */
    public function report_activitylog($activitylog) {
        $data = new stdClass();
        $data->activitylog = $activitylog;

        return $this->render_from_template('mod_openstudio/reportactivitylog', $data);
    }

    /**
     * View for Export selected posts feature
     *
     * @param array $contentdata Array of content data
     * @return string The rendered HTML fragment.
     */
    public function exportposts($contentdata = array()) {
        return $this->render_from_template('mod_openstudio/exportposts', $contentdata);
    }

    /**
     * View for content comment
     *
     * @param object $commentdata Comment data
     * @return string The rendered HTML fragment.
     */
    public function content_comment($commentdata) {
        if ($commentdata->inreplyto) {
            // Added comment is to reply to parent comment.
            return $this->render_from_template('mod_openstudio/comment_item_block', $commentdata);
        } else {
            // Added comment is to open new comment stream.
            return $this->render_from_template('mod_openstudio/comment_thread_block', $commentdata);
        }
    }


    /**
     * This function renders the HTML fragment for the folder page of Open Studio.
     *
     * @param int $cm The course module object.
     * @param object $permissions The permission object for the given user/view.
     * @param object $contentdata The content detail to display.
     * @param int $cminstance The course module instance.
     * @return string The rendered HTML fragment.
     */
    public function folder_page($cm, $permissions, $folderdata, $cminstance) {
        global $OUTPUT, $PAGE, $USER;

        $folderdata->cmid = $cmid = $cm->id;
        $folderdata->contents = [];
        $folderdata = renderer_utils::folder_content($permissions->pinboardfolderlimit, $folderdata);
        $folderaddcontent = new moodle_url('/mod/openstudio/contentedit.php',
                    array('id' => $cmid, 'lid' => 0, 'sid' => 0, 'vid' => $folderdata->visibility,
                            'ssid' => $folderdata->id, 'type' => content::TYPE_FOLDER_CONTENT));
        $folderedit = new moodle_url('/mod/openstudio/contentedit.php',
                    array('id' => $cmid, 'lid' => 0, 'sid' => $folderdata->id, 'type' => content::TYPE_FOLDER));
        $folderoverview = new moodle_url('/mod/openstudio/folder.php',
                        array('id' => $cmid, 'sid' => $folderdata->id, 'lid' => $folderdata->levelid,
                                'vuid' => $folderdata->userid));
        $folderdata->myfolder = true;
        $folderdata->showorderpostbutton = true;
        $contenttemplates = template::get_by_folderid($folderdata->id);
        if ($contenttemplates) {
            $folderdata->showorderpostbutton = false;
            foreach ($folderdata->contents as $content) {
                if ($content->canreorder == 1) {
                    $folderdata->showorderpostbutton = true;
                    break;
                }
            }
        }
        if ($folderdata->userid != $USER->id) {
            $folderdata->myfolder = false;
        }

        $folderdata->tagsraw = $this->get_tagsraw_for_template($cm, $folderdata->tagsraw);

        $user = user::get_user_by_id($folderdata->userid);
        $folderdata->fullname = fullname($user);
        $folderdata->foldereditenable = ($permissions->addcontent && $USER->id == $folderdata->userid);
        $folderdata->folderedit = $folderedit;
        $folderdata->folderlinkoverview = $folderoverview;
        $folderdata->viewuserworkurl = new moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vuid' => $folderdata->userid, 'vid' => content::VISIBILITY_PRIVATE));

        if ($folderdata->userid) {
            $user = user::get_user_by_id($folderdata->userid);
            $renderer = util::get_renderer();
            $folderdata->userpicturehtml = util::render_user_avatar($renderer, $user);
        }

        if ($folderdata->description) {
            $context = \context_module::instance($cm->id);
            $folderdata->description = file_rewrite_pluginfile_urls($folderdata->description, 'pluginfile.php',
                    $context->id, 'mod_openstudio', 'description', $folderdata->id);
            $folderdata->description = format_text($folderdata->description, $folderdata->textformat);
        }

        $folderdata->addcontentthumbnail = $OUTPUT->image_url('uploads_rgb_32px', 'openstudio');
        $folderdata->selectcontentthumbnail = $OUTPUT->image_url('browse_posts_rgb_32px', 'openstudio');
        $folderdata->editicon = $OUTPUT->image_url('edit_rgb_32px', 'openstudio');

        // Generate content flags.
        $folderdata = renderer_utils::content_flags($cmid, $permissions, $folderdata);

        $folderdata->visibilityicon = renderer_utils::content_visibility_icon($folderdata);
        $folderdata->itemsharewith = renderer_utils::get_content_visibility_name($folderdata);
        $folderdata->folderrequestfeedbackenable = $folderdata->isownedbyviewer;
        $folderdata->addcontent = $folderaddcontent->out(false);
        $folderdata->contentdatadate = userdate($folderdata->timemodified, get_string('formattimedatetime', 'openstudio'));

        // Process delete.
        renderer_utils::process_content_delete($folderdata, $permissions, $cmid);
        // Process lock.
        renderer_utils::process_content_lock($folderdata, $permissions, $cmid);
        // Process comment.
        renderer_utils::process_content_comment($folderdata, $permissions, $cmid, $cminstance);
        // Process view deleted post.
        renderer_utils::process_view_deleted_post($folderdata, $permissions, $cmid);

        // Folder name not created yet.
        if (!$folderdata->name && $folderdata->l3id) {
            $folderdata->name = $folderdata->l1name . ' - ' . $folderdata->l2name .
                    ' - ' . $folderdata->l3name;
        }

        // Order-post functionality.
        $this->page->requires->strings_for_js(
            array('folderorderpost', 'folderreordercontentshint'), 'mod_openstudio');
        $this->page->requires->js_call_amd('mod_openstudio/orderposts', 'init', [[
            'cmid' => $folderdata->cmid,
            'folderid' => $folderdata->id,
            'total' => $folderdata->total
        ]]);

        // Check to add default value in case another script using this function without
        // specify variable which cause section to be hidden.
        if (!isset($folderdata->showaddsection)) {
            $folderdata->showaddsection = true;
        }

        // Check folder has activity guidance.
        $hasguidance = false;
        if ($folderdata->id === 0) {
            $foldertemplate = template::get_by_levelid($folderdata->levelid);
        } else {
            $foldertemplate = template::get_by_folderid($folderdata->id);
        }

        $contentguidances = [];
        $folderguidance = get_string('foldernoguidance', 'openstudio');
        if ($foldertemplate) {
            $trimmedguidance = trim($foldertemplate->guidance ?? '');
            if (!empty($trimmedguidance)) {
                $hasguidance = true;
                $folderguidance = format_text($trimmedguidance, FORMAT_HTML);
            }

            $contenttemplates = template::get_contents($foldertemplate->id);

            if (!empty($contenttemplates)) {
                foreach ($contenttemplates as $contenttemplate) {
                    $contentguidance = get_string('foldernocontentguidance', 'openstudio');
                    $contentnumber = sprintf('%02d', $contenttemplate->contentorder);
                    $contenttitle = $contenttemplate->name;

                    $trimmedcontentguidance = trim($contenttemplate->guidance ?? '');
                    if (!empty($trimmedcontentguidance)) {
                        $hasguidance = true;
                        $contentguidance = $trimmedcontentguidance;
                    }

                    $contentguidances[] = (object) [
                            'contentnumber' => $contentnumber,
                            'contenttitle' => format_text($contenttitle, FORMAT_PLAIN),
                            'contentguidance' => format_text($contentguidance, FORMAT_HTML)
                    ];
                }
            }
        }

        // Activity guidance functionality.
        if ($hasguidance) {
            $folderdata->hasguidance = $hasguidance;
            $folderdata->folderguidance = $folderguidance;
            $folderdata->contentguidances = $contentguidances;
            $folderdata->uploadicon = $OUTPUT->image_url('uploads_rgb_32px', 'openstudio');

            $this->page->requires->strings_for_js(['folderactivityguidance'], 'mod_openstudio');
            $this->page->requires->js_call_amd('mod_openstudio/folderactivityguidance', 'init', [[
                    'cmid' => $folderdata->cmid,
                    'folderid' => $folderdata->id,
                    'levelid' => $folderdata->levelid
            ]]);
        }

        return $this->render_from_template('mod_openstudio/folder_page', $folderdata);
    }

    /**
     * This function renders the HTML fragment for the  brose posts to add to folder of Open Studio.
     *
     * @param object $contents post items.
     * @return string The rendered HTML fragment.
     */
    public function browse_posts($contents) {
        global $OUTPUT;

        $data = new stdClass();

        $data->contents = $contents;
        $data->total = count($contents);
        $data->lockicon = $OUTPUT->image_url('lock_grey_rgb_32px', 'openstudio');
        $data->requestfeedbackicon = $OUTPUT->image_url('request_feedback_white_rgb_32px', 'openstudio');

        return $this->render_from_template('mod_openstudio/folder_browse_posts', $data);
    }

    /**
     * This function renders the HTML fragment for the view-deleted-posts dialogue body.
     *
     * @param $deletedposts array Deleted posts
     * @return bool|string
     */
    public function view_deleted_posts($deletedposts) {
        $data = new stdClass();

        $data->deletedposts = $deletedposts;
        return $this->render_from_template('mod_openstudio/viewdeleted_dialog', $data);
    }

    /**
     * This function renders the HTML fragment for the  order post of Open Studio.
     *
     * @param object $contents post items.
     * @return string The rendered HTML fragment.
     */
    public function order_posts($contents) {
        global $OUTPUT;

        $data = new stdClass();

        $data->contents = $contents;
        $data->total = count($contents);

        return $this->render_from_template('mod_openstudio/orderpost_dialog', $data);
    }

    /**
     * Override theme's render_paging_bar function
     * @see core_renderer::render_paging_bar()
     */
    public function render_paging_bar(paging_bar $pagingbar) {
        $pagingbar = clone($pagingbar);

        // Render for mobile.
        $pagingbar->prepare_for_mobile($this, $this->page, $this->target);
        $data = new stdClass();
        $data->currentpage = $pagingbar->currentpage;
        $data->firstlink = $pagingbar->firstlink;
        $data->previouslink = $pagingbar->previouslink;
        $data->nextlink = $pagingbar->nextlink;

        $mobilepagingbar = '';
        if ($pagingbar->totalcount > $pagingbar->perpage) {
            $mobilepagingbar = $this->render_from_template('mod_openstudio/paging_bar', $data);
        }

        // Render for desktop.
        $pagingbar->prepare($this, $this->page, $this->target);
        $output = '';
        if ($pagingbar->totalcount > $pagingbar->perpage) {

            if (!empty($pagingbar->previouslink)) {
                $output .= ' ' . $pagingbar->previouslink . ' ';
            }

            $output .= get_string('page') . ':';
            // If first link is text for mobile.
            // If first link is nummber for desktop.
            $firstlinktype = strip_tags($pagingbar->firstlink ?? '');
            if (!empty($firstlinktype) && is_numeric($firstlinktype)) {
                $output .= ' ' . $pagingbar->firstlink . ' ...';
            }

            foreach ($pagingbar->pagelinks as $link) {
                $output .= "  $link";
            }

            if (!empty($pagingbar->lastlink)) {
                $output .= ' ... ' . $pagingbar->lastlink . ' ';
            }

            if (!empty($pagingbar->nextlink)) {
                $output .= ' ' . $pagingbar->nextlink;
            }
        }

        $desktoppagingbar = html_writer::tag('div', $output, array(
                'class' => 'paging openstudio-desktop-paging'));

        return $mobilepagingbar . $desktoppagingbar;
    }

    /**
     * Adds the import button to a toolbar at bottom of the page.
     *
     * @param \moodle_url $url URL for button target (null if implemented only in JS)
     * @param string $text Optional text label (HTML) if not using default
     */
    public function add_import_button(\moodle_url $url = null, $text = null) {
        // This function is empty and for theme renderers to override.
    }

    /**
     * Adds the export button to a toolbar at bottom of the page.
     */
    public function add_export_button() {
        // This function is empty and for theme renderers to override.
    }

    /**
     * This function renders the HTML fragment for the import and export buttons.
     *
     * @param bool $importenable If true, the import button will be included
     * @param bool $exportenable If true, the export button will be included
     * @param int $id Course_module ID
     * @return string The rendered HTML fragment.
     */
    public function render_import_export_buttons($importenable, $exportenable, $id) {
        $data = new stdClass();

        if ($importenable) {
            $data->importlink = new moodle_url('/mod/openstudio/import.php', array('id' => $id));
        }

        if ($exportenable) {
            $data->exportlink = "#";
        }

        return $this->render_from_template('mod_openstudio/import_export_buttons', $data);
    }

    private function get_tagsraw_for_template($cm, $tagsraw){
        $newtagsraw = array();
        if (count($tagsraw) > 0) {
            foreach ($tagsraw as $contenttag) {
                if (util::global_search_enabled($cm)) {
                    $tagsearchtext = $contenttag->name;
                } else {
                    $tagsearchtext = 'tag:' . str_replace(' ', '', $contenttag->name);
                }

                $taglink = new moodle_url('/mod/openstudio/search.php',
                        array('id' => $cm->id,
                                'searchtext' => $tagsearchtext));

                $newtagsraw[] = (object) [
                        'taglink' => $taglink->out(false),
                        'tagname' => $contenttag->rawname
                ];
            }
        }

        return $newtagsraw;
    }
}
