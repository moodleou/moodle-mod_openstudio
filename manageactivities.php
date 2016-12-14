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
 * Open Studio manage activities.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/openstudio/api/apiloader.php');

use mod_openstudio\local\util;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\forms\getactivities_form;
use mod_openstudio\local\forms\manageactivities_form;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$l1id = optional_param('l1id', 0, PARAM_INT); // Level1 / Block id.
$studioid = optional_param('studioid', 0, PARAM_INT);
$movedn = optional_param_array('movednbutton', array(), PARAM_INT);
$moveup = optional_param_array('moveupbutton', array(), PARAM_INT);
$editactivity = optional_param_array('editbutton', array(), PARAM_INT);
$deleteactivity = optional_param_array('deletebutton', array(), PARAM_INT);
$activityname = optional_param_array('activityname', array(), PARAM_TEXT);
$activityhide = optional_param_array('activityhide', array(), PARAM_INT);
$newactivity = optional_param_array('odsnewactivityname', array(), PARAM_TEXT);
$newactivityorder = optional_param_array('odsnewactivitysortorder', array(), PARAM_INT);
$newactivityhide = optional_param_array('odsnewactivityhide', array(), PARAM_INT);
$reinstateactivity = optional_param_array('reinstatebutton', array(), PARAM_INT);

// Page init and security checks.
$coursedata = util::render_page_init($id);
$cm = $coursedata->cm;
$course = $coursedata->course;
$context = $coursedata->mcontext;

require_capability('mod/openstudio:managelevels', $context);

// Process and Generate HTML.
$renderer = $PAGE->get_renderer('mod_openstudio');

// Setup page and theme settings.
$strpagetitle = $strpageheading = $course->shortname . ': ' . $cm->name
        . ' - ' . get_string('manageblockactivities', 'openstudio');
$strpageurl = new moodle_url('/mod/openstudio/manageactivities.php', array('id' => $cm->id));

// Render page header and crumb trail.
util::page_setup($PAGE, $strpagetitle, $strpageheading, $strpageurl, $course, $cm, 'manage');

// Setup page crumb trail.
$managelevelsurl = new moodle_url('/mod/openstudio/manageblocks.php', array('id' => $cm->id));
$manageactivitiesurl = new moodle_url('/mod/openstudio/manageactivities.php', array('id' => $cm->id));
$crumbarray[get_string('openstudio:managelevels', 'openstudio')] = $managelevelsurl;
$crumbarray[get_string('activities', 'openstudio')] = $manageactivitiesurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

$data = array();
$options = array();
$options['l1id'] = $l1id;
$options['id'] = $cm->id;

$islevelupdated = false;

// Let's check if we have a studio id and for various actions.
if ($studioid > 0) {
    if (!empty($movedn)) {
        // Get the block ids.
        $activityid = key($movedn);

        // Now update the block.
        $activities = levels::get_records(2, $l1id);
        $position = 0;
        $movedposition = 0;
        foreach ($activities as $a) {
            $position++;
            if ($a->id == $activityid) {
                $data = $a;
                $movedposition = $position;
            }
            if ($movedposition) {
                if (($movedposition + 1) == $position) {
                    // Immediately update the sortorder on this activity to match the one on our moved block.
                    // Set the moved activity to this sortorder.
                    $ournewsortorder = $a->sortorder;
                    $a->sortorder = (int)$data->sortorder;
                    levels::update(2, $a->id, $a);
                    $data->sortorder = (int)$ournewsortorder;
                }
            }
        }

        // Update.
        levels::update(2, (int)$activityid, $data);
        $islevelupdated = true;
    }

    if (!empty($moveup)) {
        // Get the block ids.
        $activityid = key($moveup);

        // Now update the block.
        $activities = levels::get_records(2, $l1id, false, 'sortorder DESC');
        $position = 0;
        $movedposition = 0;
        foreach ($activities as $a) {
            $position++;
            if ($a->id == $activityid) {
                $data = $a;
                $movedposition = $position;
            }
            if ($movedposition) {
                if (($movedposition + 1) == $position) {
                    // Immediately update the sortorder on this activity to match the one on our moved block.
                    // Set the activity block to this sortorder.
                    $ournewsortorder = $a->sortorder;
                    $a->sortorder = (int)$data->sortorder;
                    levels::update(2, $a->id, $a);
                    $data->sortorder = (int)$ournewsortorder;
                }
            }
        }

        // Update.
        levels::update(2, $activityid, $data);
        $islevelupdated = true;
    }

    if (!empty($editactivity)) {
        // Get block to be edited.
        $editactivityid = key($editactivity);
        $options['editactivity'] = $editactivityid;
    }

    if (!empty($activityname)) {
        // Get activity to be edited.
        $activityid = key($activityname);
        $newname = trim($activityname[$activityid]);
        if (!empty($newname)) {
            if (array_key_exists($activityid, $activityhide) && ($activityhide[$activityid] > 0)) {
                $newactivityhidevalue = 1;
            } else {
                $newactivityhidevalue = 0;
            }

            // Update the DB.
            $data = levels::get_record(2, $activityid);
            $data->name = $newname;
            $data->hidelevel = $newactivityhidevalue;
            levels::update(2, $activityid, $data);
            $islevelupdated = true;
        }
    }

    if (!empty($deleteactivity)) {
        // Get activity to delete.
        $activityid = key($deleteactivity);
        levels::delete(2, $activityid, $studioid);
        $islevelupdated = true;

        // Clean up sortorder.
        levels::cleanup_sortorder(2, $l1id);
    }

    if (!empty($reinstateactivity)) {
        // Get block to delete.
        $activityid = key($reinstateactivity);
        levels::soft_delete(2, $activityid, true);
        $islevelupdated = true;

        // Clean up sortorder.
        levels::cleanup_sortorder(2, $l1id);
    }

    if (!empty($newactivity)) {
        $urlparams = array();
        $url = new moodle_url('/mod/openstudio/manageactivities.php', $urlparams);
        $mform = new manageactivities_form($url->out(false), $options,
                'post', '', array('class' => 'unresponsive'));
        if (($newactivitydata = $mform->get_data()) || isset($_POST['add_activity'])) {
            if (isset($newactivitydata->submitbutton) || isset($_POST['add_activity'])) {
                // Get activity to be edited.
                foreach ($newactivity as $key => $nbname) {
                    $nbname = trim($nbname);
                    if (!empty($nbname)) {
                        $insertdata = new stdClass();
                        $insertdata->parentid = $l1id;
                        $insertdata->name = $nbname;
                        if (!empty($newactivityorder)) {
                            if (array_key_exists($key, $newactivityorder)) {
                                // We have a sortorder passed, make sure it is not empty and process.
                                $proposedorder = $newactivityorder[$key];
                                $insertdata->sortorder = $proposedorder;
                            } else {
                                // Get sortorder.
                                $insertdata->sortorder = levels::get_latest_sortorder(2, $l1id);
                            }
                        } else {
                            // Get sortorder.
                            $insertdata->sortorder = levels::get_latest_sortorder(2, $l1id);
                        }
                        $insertdata->hidelevel = 0;
                        if (!empty($newactivityhide)) {
                            if (array_key_exists($key, $newactivityhide) && ($newactivityhide[$key] > 0)) {
                                $insertdata->hidelevel = 1;
                            }
                        }
                        levels::create(2, $insertdata);
                        $islevelupdated = true;

                        // Cleanup sortorder again.
                        levels::cleanup_sortorder(2, $l1id);
                    }
                }
            }
        }
    }

    // Update studio feature setting given level configuration has changed.
    if ($islevelupdated) {
        openstudio_feature_settings($studioid, true);
    }
}

// Output HTML.
echo $OUTPUT->header(); // Header.

echo html_writer::tag('h2', get_string('manageblockactivities', 'openstudio'), array('id' => 'openstudio-main-content'));

$url = new moodle_url('/mod/openstudio/manageactivities.php', array('id' => $cm->id));
$mform = new getactivities_form($url->out(false), $options,
        'post', '', array('class' => 'unresponsive'));
$mform->set_data($data);
$mform->display();
if ($l1id > 0) {
    if (isset($_POST['add_activity'])) {
        // Unset value.
        if (isset($_POST['odsnewactivityname'])) {
            $_POST['odsnewactivityname'][0] = '';
        }
        if (isset($_POST['odsnewactivitysortorder'])) {
            $_POST['odsnewactivitysortorder'][0] = levels::get_latest_sortorder(2, $l1id);
        }
    }
    $mform2 = new manageactivities_form($url->out(false), $options,
            'post', '', array('class' => 'unresponsive'));
    $mform2->set_data($data);
    $mform2->display();
}

echo $renderer->footer(); // Footer.
