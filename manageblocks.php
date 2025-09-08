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
 * Open Studio manage block.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_openstudio\local\util;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\forms\manageblocks_form;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$openstudioid = optional_param('openstudioid', 0, PARAM_INT);
$movedn = optional_param_array('movednbutton', array(), PARAM_INT);
$moveup = optional_param_array('moveupbutton', array(), PARAM_INT);
$editblock = optional_param_array('editbutton', array(), PARAM_INT);
$deleteblock = optional_param_array('deletebutton', array(), PARAM_INT);
$blockname = optional_param_array('blockname', array(), PARAM_TEXT);
$newblocks = optional_param_array('odsnewblockname', array(), PARAM_TEXT);
$newblocksorder = optional_param_array('odsnewblocksortorder', array(), PARAM_INT);
$reinstateblock = optional_param_array('reinstatebutton', array(), PARAM_INT);

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
        . ' - ' . get_string('manageblocks', 'openstudio');
$strpageurl = new moodle_url('/mod/openstudio/manageblocks.php', array('id' => $cm->id));

// Render page header and crumb trail.
util::page_setup($PAGE, $strpagetitle, $strpageheading, $strpageurl, $course, $cm, 'manage');

$openstudioid = util::get_studioid_from_coursemodule($cm->id);

$options = array();
$options['id'] = $cm->id;

$islevelupdated = false;

// Let's check if we have a studio id and for various actions.
if ($openstudioid > 0) {
    if (!empty($movedn)) {
        // Get the block ids.
        $blockid = key($movedn);

        // Now update the block.
        $blocks = levels::get_records(1, $openstudioid);
        $position = 0;
        $movedposition = 0;
        $data = array();
        foreach ($blocks as $block) {
            $position++;
            if ($block->id == $blockid) {
                $data = $block;
                $movedposition = $position;
            }
            if ($movedposition) {
                if (($movedposition + 1) == $position) {
                    // Immediately update the sortorder on this block to match the
                    // one on our moved block. Set the moved block to this sortorder.
                    $ournewsortorder = $block->sortorder;
                    $block->sortorder = (int)$data->sortorder;
                    levels::update(1, $block->id, $block);
                    $data->sortorder = (int)$ournewsortorder;
                }
            }
        }

        // Update.
        levels::update(1, $blockid, $data);
        $islevelupdated = true;
    }

    if (!empty($moveup)) {
        // Get the block ids.
        $blockid = key($moveup);

        // Now update the block.
        $blocks = levels::get_records(1, $openstudioid, false, 'sortorder DESC');
        $position = 0;
        $movedposition = 0;
        $data = array();
        foreach ($blocks as $block) {
            $position++;
            if ($block->id == $blockid) {
                $data = $block;
                $movedposition = $position;
            }
            if ($movedposition) {
                if (($movedposition + 1) == $position) {
                    // Immediately update the sortorder on this block to match the one on
                    // our moved block. Set the moved block to this sortorder.
                    $ournewsortorder = $block->sortorder;
                    $block->sortorder = (int)$data->sortorder;
                    levels::update(1, $block->id, $block);
                    $data->sortorder = (int)$ournewsortorder;
                }
            }
        }

        // Update.
        levels::update(1, $blockid, $data);
        $islevelupdated = true;
    }

    if (!empty($editblock)) {
        // Get block to be edited.
        $editblockid = key($editblock);
        $options['editblock'] = $editblockid;
    }

    if (!empty($blockname)) {
        // Get block to be edited.
        $blockid = key($blockname);
        $newname = trim($blockname[$blockid]);
        if (!empty($newname)) {
            // Update the DB.
            $data = levels::get_record(1, $blockid);
            $data->name = $newname;
            levels::update(1, $blockid, $data);
            $islevelupdated = true;
        }
    }

    if (!empty($deleteblock)) {
        // Get block to delete.
        $blockid = key($deleteblock);
        levels::delete(1, $blockid, $openstudioid);
        $islevelupdated = true;

        // Clean up sortorder.
        levels::cleanup_sortorder(1, $openstudioid);
    }

    if (!empty($reinstateblock)) {
        // Get block to delete.
        $blockid = key($reinstateblock);
        levels::soft_delete(1, $blockid, true);
        $islevelupdated = true;

        // Clean up sortorder.
        levels::cleanup_sortorder(1, $openstudioid);
    }

    if (!empty($newblocks)) {
        $urlparams = array();
        $url = new moodle_url('/mod/openstudio/manageblocks.php', $urlparams);
        $mform = new manageblocks_form($url->out(false), $options,
                'post', '', array('class' => 'unresponsive'));

        // Clean up the sortorder before proceeding.
        levels::cleanup_sortorder(1, $openstudioid);
        if (($newblocksdata = $mform->get_data()) || isset($_POST['add_block'])) {
            if (isset($newblocksdata->submitbutton) || isset($_POST['add_block'])) {
                // Get block to be edited.
                $currentblocks = levels::get_records(1, $openstudioid);
                $tmpsortorder = [];
                $newblocksdatatemp = [];
                foreach ($currentblocks as $block) {
                    $tmpsortorder[$block->sortorder] = clone $block;
                }
                foreach ($newblocks as $key => $nbname) {
                    $nbname = trim($nbname);
                    if (!empty($nbname)) {
                        $insertdata = new stdClass();
                        $insertdata->openstudioid = $openstudioid;
                        $insertdata->name = $nbname;
                        if (!empty($newblocksorder)) {
                            if (array_key_exists($key, $newblocksorder)) {
                                // We have a sortorder passed, make sure it is not empty and process.
                                $proposedorder = $newblocksorder[$key];
                                $insertdata->sortorder = $proposedorder;
                            }
                        } else {
                            // Get sortorder.
                            $insertdata->sortorder = levels::get_latest_sortorder(1, $openstudioid);
                        }
                        $newblocksdatatemp[] = $insertdata;
                        $islevelupdated = true;
                    }
                }

                // Merge, shift, build insert/update.
                levels::process_sortorder_changes(1, $newblocksdatatemp, $tmpsortorder, $currentblocks);
                // Cleanup sortorder again.
                levels::cleanup_sortorder(1, $openstudioid);
            }
        }
    }

    // Update studio feature setting given level configuration has changed.
    if ($islevelupdated) {
        openstudio_feature_settings($openstudioid, true);
    }
}

// Output HTML.
echo $OUTPUT->header(); // Header.

echo html_writer::tag('h2', get_string('manageblocksheader', 'openstudio'), array('id' => 'openstudio-main-content'));

$data = array();
$urlparams = array();
$url = new moodle_url('/mod/openstudio/manageblocks.php', $urlparams);
if (isset($_POST['add_block'])) {
    // Unset value.
    if (isset($_POST['odsnewblockname'])) {
        $_POST['odsnewblockname'][0] = '';
    }
    if (isset($_POST['odsnewblocksortorder'])) {
        $_POST['odsnewblocksortorder'][0] = levels::get_latest_sortorder(1, $openstudioid);
    }
}
$mform = new manageblocks_form($url->out(false), $options, 'post', '', array('class' => 'unresponsive'));

$mform->set_data($data);
$mform->display();

echo $OUTPUT->footer(); // Footer.
