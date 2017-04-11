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
use mod_openstudio\local\api\notifications;
use mod_openstudio\local\renderer_utils;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\util\defaults;
use mod_openstudio\local\notifications\notification;

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
        global $OUTPUT, $PAGE, $USER;

        $cm = $coursedata->cm;
        $cmid = $cm->id;

        $data = new stdClass();
        $data->sitename = $sitename;

        // Check if enable Email subscriptions.
        $data->enablesubscription = $permissions->feature_enablesubscription;

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
                $placeholdertext = $theme->themestudioname;
                $menuhighlight->myactivity = true;
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
            $submenuitem = array(
                    'name' => get_string('settingsthemehomesettingsmodule', 'openstudio'),
                    'url' => $navigationurls->mymoduleurl,
                    'pix' => $OUTPUT->pix_url('mymodule_rgb_32px', 'openstudio'),
                    'active' => $menuhighlight->mymodule
            );
            $menuitem['hassubnavigation'] = true;
            $menuitem['subnavigation'][] = $submenuitem;
        }

        if ($permissions->feature_group) {
            $submenuitem = array(
                    'name' => get_string('settingsthemehomesettingsgroup', 'openstudio'),
                    'url' => $navigationurls->mygroupurl,
                    'pix' => $OUTPUT->pix_url('group_rgb_32px', 'openstudio'),
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
                $menuitem['pix'] = $OUTPUT->pix_url('shared_content_rgb_32px', 'openstudio');
                $menuitem['class'] = 'shared-content';
                $menuitem['active'] = $menuhighlight->mymodule || $menuhighlight->mygroup;
                $data->navigation[] = $menuitem;
            } else {
                $menuitem = array(
                    'hassubnavigation' => false,
                    'subnavigation' => array(),
                    'name' => get_string('menusharedcontent', 'openstudio'),
                    'url' => $submenuitem['url'],
                    'pix' => $OUTPUT->pix_url('shared_content_rgb_32px', 'openstudio'),
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
            $menuitem['pix'] = $OUTPUT->pix_url('people_rgb_32px', 'openstudio');
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
            $submenuitem = array(
                    'name' => get_string('settingsthemehomesettingsstudio', 'openstudio'),
                    'url' => $navigationurls->myworkurl,
                    'pix' => $OUTPUT->pix_url('activity_rgb_32px', 'openstudio'),
                    'active' => $menuhighlight->myactivity
            );
            $menuitem['hassubnavigation'] = true;
            $subnavigations[] = $submenuitem;
        }

        if ($permissions->feature_pinboard || ($permissions->pinboarddata->usedandempty > 0)) {
            $submenuitem = array(
                    'name' => get_string('settingsthemehomesettingspinboard', 'openstudio'),
                    'url' => $navigationurls->pinboardurl,
                    'pix' => $OUTPUT->pix_url('pinboard_rgb_32px', 'openstudio'),
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
                $menuitem['pix'] = $OUTPUT->pix_url('openstudio_rgb_32px', 'openstudio');
                $menuitem['class'] = 'my-content';
                $menuitem['active'] = $menuhighlight->myactivity || $menuhighlight->mypinboard;
                $data->navigation[] = $menuitem;
            } else {
                $menuitem = array(
                    'hassubnavigation' => false,
                    'subnavigation' => array(),
                    'name' => get_string('menumycontent', 'openstudio'),
                    'url' => $submenuitem['url'],
                    'pix' => $OUTPUT->pix_url('openstudio_rgb_32px', 'openstudio'),
                    'class' => 'my-content',
                    'active' => $menuhighlight->myactivity || $menuhighlight->mypinboard
                );
                $data->navigation[] = $menuitem;
            }
        }

        // Generate admin items.
        $adminmenuitem = $this->navigation_admin($coursedata, $permissions);
        if ($adminmenuitem['hassubnavigation']) {
            $data->navigation[] = $adminmenuitem;
        }

        $data->notificationicon = $OUTPUT->pix_url('notifications_rgb_32px', 'openstudio');
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

        $addtodashboard = block_externaldashboard_backend::render_favourites_button($PAGE->cm, false);
        $data->addtodashboard = $addtodashboard;

        // Subscription.
        $subscriptionconstant = array(
                "FORMAT_HTML" => subscription::FORMAT_HTML,
                "FORMAT_PLAIN" => subscription::FORMAT_PLAIN,
                "FREQUENCY_HOURLY" => subscription::FREQUENCY_HOURLY,
                "FREQUENCY_DAILY" => subscription::FREQUENCY_DAILY);

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

        return $this->render_from_template('mod_openstudio/header', $data);

    }

    /**
     * This function renders admin menu items for Open Studio module.
     *
     * @param int $cmid The course module id.
     * @param object $permissions The permission object for the given user/view.
     * @return menu items array.
     */
    public function navigation_admin($coursedata, $permissions) {
        global $OUTPUT, $CFG;

        $cm = $coursedata->cm;
        $course = $coursedata->course;
        $context = context_module::instance($cm->id);

        $menuitem = array(
                'hassubnavigation' => false,
                'subnavigation' => array()
        );

        if ($permissions->addinstance || $permissions->managelevels) {

            if ($permissions->addinstance) {
                $redirectorurl = new moodle_url('/course/modedit.php',
                    array('update' => $cm->id, 'return' => 0, 'sr' => ''));
                $submenuitem = array(
                        'name' => get_string('navadmineditsettings', 'openstudio'),
                        'url' => $redirectorurl
                );
                $menuitem['hassubnavigation'] = true;
                $menuitem['subnavigation'][] = $submenuitem;
            }
            if ($permissions->managelevels) {
                $redirectorurl = new moodle_url('/mod/openstudio/manageblocks.php',
                    array('id' => $cm->id));

                $submenuitem = array(
                        'name' => get_string('navadminmanagelevel', 'openstudio'),
                        'url' => $redirectorurl
                );
                $menuitem['hassubnavigation'] = true;
                $menuitem['subnavigation'][] = $submenuitem;
            }
            if (has_capability('moodle/role:assign', $permissions->coursecontext)) {
                $redirectorurl = new moodle_url('/admin/roles/assign.php',
                    array('contextid' => $context->id));

                $submenuitem = array(
                        'name' => get_string('navadminassignroles', 'openstudio'),
                        'url' => $redirectorurl
                );
                $menuitem['hassubnavigation'] = true;
                $menuitem['subnavigation'][] = $submenuitem;
            }
            if (has_capability('moodle/role:review', $permissions->coursecontext)) {
                $redirectorurl = new moodle_url('/admin/roles/permissions.php',
                array('contextid' => $context->id));

                $submenuitem = array(
                        'name' => get_string('navadminpermissions', 'openstudio'),
                        'url' => $redirectorurl
                );
                $menuitem['hassubnavigation'] = true;
                $menuitem['subnavigation'][] = $submenuitem;
            }
            if (has_any_capability(array('moodle/role:assign',
                    'moodle/role:safeoverride',
                    'moodle/role:override',
                    'moodle/role:manage'),
                    $permissions->coursecontext)) {

                $redirectorurl = new moodle_url('/admin/roles/check.php',
                    array('contextid' => $context->id));

                $submenuitem = array(
                        'name' => get_string('navadmincheckpermissions', 'openstudio'),
                        'url' => $redirectorurl
                );
                $menuitem['hassubnavigation'] = true;
                $menuitem['subnavigation'][] = $submenuitem;
            }
            if (has_capability('moodle/filter:manage', $permissions->coursecontext)) {
                $redirectorurl = new moodle_url('/filter/manage.php',
                    array('contextid' => $context->id));

                $submenuitem = array(
                        'name' => get_string('navadminfilters', 'openstudio'),
                        'url' => $redirectorurl
                );
                $menuitem['hassubnavigation'] = true;
                $menuitem['subnavigation'][] = $submenuitem;
            }
            if (has_capability('report/log:view', $permissions->coursecontext)) {
                $redirectorurl = new moodle_url('/report/log/index.php',
                    array('id' => $course->id, 'modid' => $cm->id, 'chooselog' => 1));

                $submenuitem = array(
                        'name' => get_string('navadminlogs', 'openstudio'),
                        'url' => $redirectorurl
                );
                $menuitem['hassubnavigation'] = true;
                $menuitem['subnavigation'][] = $submenuitem;
            }
            if (has_capability('mod/openstudio:managecontent', $permissions->coursecontext)) {
                $redirectorurl = new moodle_url('/mod/openstudio/reportusage.php',
                    array('id' => $cm->id));

                $submenuitem = array(
                        'name' => get_string('navadminusagereport', 'openstudio'),
                        'url' => $redirectorurl
                );
                $menuitem['hassubnavigation'] = true;
                $menuitem['subnavigation'][] = $submenuitem;
            }
            if (file_exists("{$CFG->dirroot}/report/restrictuser/lib.php") &&
                    has_any_capability(array('report/restrictuser:view',
                            'report/restrictuser:restrict',
                            'report/restrictuser:removerestrict'),
                            context_module::instance($cm->id))) {
                // Restrict user report available.
                require_once("{$CFG->dirroot}/report/restrictuser/lib.php");
                $redirectorurl = report_restrictuser_get_user_navurl($context);

                $submenuitem = array(
                        'name' => get_string('navlink', 'report_restrictuser'),
                        'url' => $redirectorurl
                );
                $menuitem['hassubnavigation'] = true;
                $menuitem['subnavigation'][] = $submenuitem;
            }

            if (!empty($menuitem['subnavigation'])) {
                $menuitem['name'] = get_string('menuadministration', 'openstudio');
                $menuitem['url'] = '#';
                $menuitem['pix'] = $OUTPUT->pix_url('administration_rgb_32px', 'openstudio');
                $menuitem['class'] = 'administration';
            }
        }

        return $menuitem;
    }

    /**
     * This function renders the HTML for search form.
     * @param object $theme The theme settings.
     * @param int $viewmode View mode: module, group, studio, pinboard or workspace.
     * @return string The rendered HTM search form.
     */
    public function searchform($theme, $viewmode) {
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
        $data->iconsearch = $OUTPUT->pix_url('i/search');

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
        $data->addcontenticon = $OUTPUT->pix_url('add_content_rgb_32px', 'openstudio');
        $data->editform = $contenteditform;

        return $this->render_from_template('mod_openstudio/content_edit', $data);
    }

    /**
     * This function renders the HTML fragment for the body content of Open Studio.
     *
     * @param int $cmid The course module id.
     * @param int $openstudioid The openstudio id.
     * @param object $theme The theme settings.
     * @param int $viewmode View mode: module, group, studio, pinboard or workspace.
     * @param object $permissions The permission object for the given user/view.
     * @param object $contentdata The content records to display.
     * @return string The rendered HTM fragment.
     */
    public function body($cmid, $openstudioid, $theme, $viewmode = content::VISIBILITY_MODULE, $permissions, $contentdata) {
        global $OUTPUT;

        $placeholdertext = '';
        $selectview = false;
        $myactivities = false;
        $blocksdata = array();
        $showprofilebarview = false;
        $contentdata->ismypinboard = false;
        $contentdata->ismyactivity = false;

        switch ($viewmode) {
            case content::VISIBILITY_MODULE:
                $placeholdertext = $theme->thememodulename;
                break;

            case content::VISIBILITY_GROUP:
                $placeholdertext = $theme->themegroupname;
                $selectview = true;
                break;

            case content::VISIBILITY_WORKSPACE:
            case content::VISIBILITY_PRIVATE:
                $placeholdertext = $theme->themestudioname;
                $myactivities = true;
                $blocksdata = levels::get_records(1, $permissions->activecminstanceid);
                $showprofilebarview = true;
                $contentdata->ismyactivity = true;

                // Set selected block.
                foreach ($blocksdata as $key => $block) {
                    $blocksdata[$key]->selected = false;
                    if ($contentdata->blockid == $block->id) {
                        $blocksdata[$key]->selected = true;
                    }
                }
                break;

            case content::VISIBILITY_PRIVATE_PINBOARD:
                $placeholdertext = $theme->themepinboardname;
                $showprofilebarview = true;
                $contentdata->ismypinboard = true;
                break;
        }

        $grouplist = studio_api_group_list(
                    $permissions->activecid, $permissions->groupingid,
                    $permissions->activeuserid, $permissions->groupmode);

        $showmultigroup = false;
        $groupitem = array();
        if ($grouplist) {
            $groupitem[0] = (object) [
                'groupid' => 0,
                'selectedgroup' => $contentdata->selectedgroupid == 0 ? true : false,
                'vid' => content::VISIBILITY_GROUP,
                'name' => get_string('filterall', 'openstudio')
            ];

            foreach ($grouplist as $group) {
                $groupitem[$group->groupid] = (object) [
                    'groupid' => $group->groupid,
                    'selectedgroup' => $contentdata->selectedgroupid == $group->groupid ? true : false,
                    'vid' => content::VISIBILITY_GROUP,
                    'name' => $group->name
                ];
            }

            $showmultigroup = (count($groupitem) > 1);
        }

        $contentdata->cmid = $cmid;
        $contentdata = renderer_utils::profile_bar($permissions, $openstudioid, $contentdata);

        $contentdata->groupitems = array_values($groupitem);
        $contentdata->showprofilebarview = $showprofilebarview;
        $contentdata->showmultigroup = $showmultigroup;
        $contentdata->placeholdertext = $placeholdertext;
        $contentdata->selectview = $selectview;
        $contentdata->myactivities = $myactivities;
        $contentdata->blocksdata = $contentdata->openstudio_view_filters->fblockdataarray;
        $contentdata->viewedicon = $OUTPUT->pix_url('viewed_rgb_32px', 'openstudio');
        $contentdata->commentsicon = $OUTPUT->pix_url('comments_rgb_32px', 'openstudio');
        $contentdata->inspirationicon = $OUTPUT->pix_url('inspiration_rgb_32px', 'openstudio');
        $contentdata->participationicon = $OUTPUT->pix_url('participation_rgb_32px', 'openstudio');
        $contentdata->favouriteicon = $OUTPUT->pix_url('favourite_rgb_32px', 'openstudio');
        $contentdata->vid = $viewmode;
        $contentdata->lockicon = $OUTPUT->pix_url('lock_grey_rgb_32px', 'openstudio');
        $contentdata->requestfeedbackicon = $OUTPUT->pix_url('request_feedback_white_rgb_32px', 'openstudio');
        $contentdata->createcontentthumbnail = $OUTPUT->pix_url('uploads_rgb_32px', 'openstudio');

        $contentdata->contentediturl = new moodle_url('/mod/openstudio/contentedit.php',
                   array('id' => $cmid, 'lid' => 0, 'sid' => 0, 'type' => 0, 'sstsid' => 0));
        if ($permissions->feature_enablefolders) {
            $folderlink = new moodle_url('/mod/openstudio/contentedit.php',
                    array('id' => $cmid, 'lid' => 0, 'sid' => 0, 'ssid' => 0, 'type' => content::TYPE_FOLDER_CONTENT));
            $contentdata->folderediturl = $folderlink;
            $contentdata->createfolderthumbnail = $OUTPUT->pix_url('create_folder_rgb_32px', 'openstudio');
        }
        $contentdata->feature_enablefolders = $permissions->feature_enablefolders;
        $contentdata->available = $permissions->pinboarddata->available;

        if ($contentdata->contents && !$myactivities) {
            $pb = renderer_utils::openstudio_render_paging_bar($contentdata);
            $paging = $this->render($pb);
            $contentdata->paging = $paging;
        }
        $contentdata->available = $permissions->pinboarddata->available;

        // Prepare select from (all/pinboard/blocks) filter.
        $contentdata = renderer_utils::filter_area($contentdata);

        // Prepare post types option for filter.
        $contentdata = renderer_utils::filter_post_types($contentdata);

        // Prepare user flags option for filter.
        $contentdata = renderer_utils::filter_user_flags($contentdata);

        // Prepare select status option for filter.
        $contentdata = renderer_utils::filter_select_status($contentdata);

        // Prepare scope option for filter.
        $contentdata = renderer_utils::filter_scope($contentdata);

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

        $grouplist = studio_api_group_list(
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
        if ($grouplist) {
            $groupitem[0] = (object) [
                    'groupid' => 0,
                    'selectedgroup' => $peopledata->selectedgroupid == 0 ? true : false,
                    'vid' => content::VISIBILITY_GROUP,
                    'name' => get_string('filterallgroup', 'openstudio')
            ];

            foreach ($grouplist as $group) {
                $groupitem[$group->groupid] = (object) [
                        'groupid' => $group->groupid,
                        'selectedgroup' => $peopledata->selectedgroupid == $group->groupid ? true : false,
                        'vid' => content::VISIBILITY_GROUP,
                        'name' => $group->name
                ];
            }
        }

        $showmultigroup = (count($groupitem) > 1);

        $peopledata->groupitems = array_values($groupitem);
        $peopledata->showmultigroup = $showmultigroup;
        $peopledata->commentsicon = $OUTPUT->pix_url('comments_rgb_32px', 'openstudio');
        $peopledata->viewedicon = $OUTPUT->pix_url('viewed_rgb_32px', 'openstudio');

        return $this->render_from_template('mod_openstudio/people_page', $peopledata);
    }

    /**
     * This function renders the HTML fragment for the content detail page of Open Studio.
     *
     * @param int $cmid The course module id.
     * @param object $permissions The permission object for the given user/view.
     * @param object $contentdata The content detail to display.
     * @param int $openstudioid The openstudio id.
     * @return string The rendered HTML fragment.
     */
    public function content_page($cmid, $permissions, $contentdata, $openstudioid = null) {
        global $CFG, $PAGE, $OUTPUT;

        $contentdata->cmid = $cmid;
        if (!property_exists($contentdata, 'profilebarenable')) {
            $contentdata->profilebarenable = true;
        }

        if ($contentdata->profilebarenable === true) {
            $contentdata = renderer_utils::profile_bar($permissions, $openstudioid, $contentdata);
        }

        $contentdata = renderer_utils::content_details($cmid, $permissions, $contentdata, false);

        $tagsraw = array();
        if (count($contentdata->tagsraw) > 0) {
            foreach ($contentdata->tagsraw as $contenttag) {
                $taglink = new moodle_url('/mod/openstudio/search.php',
                    array('id' => $cmid,
                        'searchtext' => 'tag:'
                            . str_replace(' ', '', $contenttag->name)));

                $tagsraw[] = (object) [
                        'taglink' => $taglink->out(false),
                        'tagname' => $contenttag->rawname
                    ];
            }
        }

        $contentdata->tagsraw = $tagsraw;

        // Generate content flags.
        $contentdata = renderer_utils::content_flags($cmid, $permissions, $contentdata);

        // Process delete.
        $deleteenable = renderer_utils::process_content_delete($contentdata, $permissions, $cmid);
        // Process lock.
        renderer_utils::process_content_lock($contentdata, $permissions, $cmid);

        // Check edit content permission.
        $contenteditenable = $deleteenable;
        $editparams = array('id' => $cmid, 'sid' => $contentdata->id);
        $contenteditlink = new moodle_url('/mod/openstudio/contentedit.php', $editparams);

        if (($contentdata->l1id > 0) || ($contentdata->l1id == 0) || $permissions->managecontent) {
            if (studio_api_lock_slot_show_crud($contentdata, $permissions)
                || $permissions->managecontent) {
                $contentdeleteenable = true;
            }
        }

        $contentdata->contentviewversionlink = new moodle_url('/mod/openstudio/content.php',
                array('id' => $cmid, 'sid' => $contentdata->id, 'vuid' => $contentdata->userid, 'version' => 1));
        $contentdata->contenteditenable = $contenteditenable;
        $contentdata->contenteditlink = $contenteditlink;
        $contentdata->actionenable = $contentdata->contentdeleteenable || $contentdata->contenteditenable
                || $contentdata->contentlockenable;

        // Check comment permission.
        $contentdata->contentcommentenable = $permissions->addcomment ? true : false;

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
                    $metadatamake = empty($contentexifinfo['UndefinedTag:0xA433']) ? '' : $contentexifinfo['UndefinedTag:0xA433'];
                }

                $metadatamodel = empty($contentexifinfo['Model']) ? '' : $contentexifinfo['Model'];

                if (strlen($metadatamodel) < 2) {
                    $metadatamodel = empty($contentexifinfo['UndefinedTag:0xA434']) ? '' : $contentexifinfo['UndefinedTag:0xA434'];
                }

                $metadatafocal = empty($contentexifinfo['FocalLengthIn35mmFilm']) ? '' : $contentexifinfo['FocalLengthIn35mmFilm'];

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
        }

        // Check if maximize feature is enable.
        $contentdata->maximizeenable = ($contentdata->contenttypeimage || $contentdata->contenttypeiframe);

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

        // Render content versions.
        $contentversions = array();
        $hascontentversions = false;

        if (!empty($contentdata->contentversions)) {
            foreach ($contentdata->contentversions as $contentversion) {
                $contentversion->vid = $contentdata->vid;
                $contentversions[] = renderer_utils::content_details($cmid, $permissions, $contentversion, true);
            }
            $hascontentversions = true;
        }

        $contentdata->hascontentversions = $hascontentversions;
        $contentdata->contentversions = $contentversions;

        if ($contentdata->contentcommentenable) {

            // Require strings for js.
            $PAGE->requires->strings_for_js(
                    array('contentcommentliked', 'contentcommentsdelete', 'modulejsdialogcommentdeleteconfirm',
                            'modulejsdialogcancel', 'modulejsdialogdelete'), 'mod_openstudio');

            $this->page->requires->js_call_amd('mod_openstudio/comment', 'init', [[
                    'cmid' => $cmid,
                    'cid' => $contentdata->id]]);

            // Init OUMP module (Media player).
            // We need to init oump here to make sure that oump is always loaded even when no comment loaded.
            // As current behaviour, filter just call oump AMD module when has media markups found in filter input.
            // So if no media found, we can not trigger oump feature after user added a new comment by ajax.

            if (file_exists($CFG->dirroot.'/local/oump/classes/filteroump.php')) {
                // OUMP installed.
                require_once($CFG->dirroot.'/local/oump/classes/filteroump.php');
                $PAGE->requires->js_call_amd('local_oump/mloader', 'initialise', array([
                    'wwwroot' => $CFG->wwwroot . '/local/oump',
                    'urlargs' => filter_oump::get_requirejs_urlargs(),
                    'jsdependency' => filter_oump::get_js_dependency()
                ]));
            }
        }
        $contentdata->viewuserworkurl = new \moodle_url('/mod/openstudio/view.php',
              array('id' => $cmid, 'vuid' => $contentdata->userid, 'vid' => content::VISIBILITY_PRIVATE));

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
     * @param array $contentdataactivity Summary of content activities.
     * @param array $contentdatavisbility Summary of content by sharing/visibility setting.
     * @param array $flagdata Summary of flagging activity data for a given studio instance.
     * @param array $storage Storage usage for a given studio instance.
     * @param array $activitylog Logged actions.
     * @return string The rendered HTM fragment.
     */
    public function reportusage($summarydata, $contentdataactivity, $contentdatavisbility, $flagdata, $storage, $activitylog) {
        global $OUTPUT;

        $data = new stdClass();

        $data->summarydata = $summarydata;
        $data->contentdataactivity = $contentdataactivity;
        $data->contentdatavisbility = $contentdatavisbility;
        $data->flagdata = $flagdata;
        $data->storage = $storage;
        $data->activitylog = $activitylog;

        return $this->render_from_template('mod_openstudio/reportusage', $data);
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
     * @param int $cmid The course module id.
     * @param object $permissions The permission object for the given user/view.
     * @param object $contentdata The content detail to display.
     * @return string The rendered HTML fragment.
     */
    public function folder_page($cmid, $permissions, $folderdata) {
        global $OUTPUT, $PAGE, $USER;

        $folderdata->cmid = $cmid;
        $folderdata = renderer_utils::folder_content($permissions->pinboardfolderlimit, $folderdata);
        $folderaddcontent = new moodle_url('/mod/openstudio/contentedit.php',
                    array('id' => $cmid, 'lid' => 0, 'sid' => 0,
                            'ssid' => $folderdata->id, 'type' => content::TYPE_FOLDER_CONTENT));
        $folderedit = new moodle_url('/mod/openstudio/contentedit.php',
                    array('id' => $cmid, 'lid' => 0, 'sid' => $folderdata->id, 'type' => content::TYPE_FOLDER_CONTENT));
        $folderoverview = new moodle_url('/mod/openstudio/folder.php',
                        array('id' => $cmid, 'sid' => $folderdata->id, 'lid' => $folderdata->levelid,
                                'vuid' => $folderdata->userid));
        $folderdata->myfolder = true;
        if ($folderdata->userid != $USER->id) {
            $folderdata->myfolder = false;
        }

        $user = studio_api_user_get_user_by_id($folderdata->userid);
        $folderdata->fullname = fullname($user);
        $folderdata->folderedit = $folderedit;
        $folderdata->folderlinkoverview = $folderoverview;
        $folderdata->viewuserworkurl = new moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vuid' => $folderdata->userid, 'vid' => content::VISIBILITY_PRIVATE));

        if ($folderdata->userid) {
            $user = studio_api_user_get_user_by_id($folderdata->userid);
            $picture = new user_picture($user);
            $folderdata->userpicturehtml = $OUTPUT->render($picture);
        }

        $folderdata->addcontentthumbnail = $OUTPUT->pix_url('uploads_rgb_32px', 'openstudio');
        $folderdata->selectcontentthumbnail = $OUTPUT->pix_url('browse_posts_rgb_32px', 'openstudio');

        // Generate content flags.
        $folderdata = renderer_utils::content_flags($cmid, $permissions, $folderdata);

        $folderdata->visibilityicon = renderer_utils::content_visibility_icon($folderdata);
        $folderdata->folderrequestfeedbackenable = $folderdata->isownedbyviewer;
        $folderdata->addcontent = $folderaddcontent->out(false);
        $folderdata->contentdatadate = userdate($folderdata->timemodified, get_string('formattimedatetime', 'openstudio'));

        // Process delete.
        renderer_utils::process_content_delete($folderdata, $permissions, $cmid);
        // Process lock.
        renderer_utils::process_content_lock($folderdata, $permissions, $cmid);

        return $this->render_from_template('mod_openstudio/folder_page', $folderdata);
    }
}
