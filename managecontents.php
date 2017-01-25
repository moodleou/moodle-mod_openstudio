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
 * Open Studio manage content.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/openstudio/api/apiloader.php');

use mod_openstudio\local\api\template;
use mod_openstudio\local\util;
use mod_openstudio\local\api\content;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\forms\getcontents_form;
use mod_openstudio\local\forms\managecontents_form;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$l1id = optional_param('l1id', 0, PARAM_INT); // Level1 / Block id.
$l2id = optional_param('l2id', 0, PARAM_INT);
$studioid = optional_param('studioid', 0, PARAM_INT);
$addcontent = optional_param('add_content', false, PARAM_BOOL);
$movedn = optional_param_array('movednbutton', array(), PARAM_INT);
$moveup = optional_param_array('moveupbutton', array(), PARAM_INT);
$editcontent = optional_param_array('editbutton', array(), PARAM_INT);
$deletecontent = optional_param_array('deletebutton', array(), PARAM_INT);
$contentname = optional_param('contentname', '', PARAM_TEXT);
$newcontent = optional_param_array('odsnewcontentname', array(), PARAM_TEXT);
$newcontentorder = optional_param_array('odsnewcontentsortorder', array(), PARAM_INT);
$odsnewcontentrequired = optional_param_array('odsnewcontentrequired', array(), PARAM_INT);
$odsnewcontentiscollection = optional_param_array('odsnewcontentiscollection', array(), PARAM_INT);
$reinstatecontent = optional_param_array('reinstatebutton', array(), PARAM_INT);

$editcontentid = optional_param('editcontentid', 0, PARAM_INT);

// Page init and security checks.
$coursedata = util::render_page_init($id);
$cm = $coursedata->cm;
$course = $coursedata->course;
$context = $coursedata->mcontext;

require_capability('mod/openstudio:managelevels', $context);

// Check if the cancel button was pressed. If so, reset the form.
if (isset($_POST['cancelbutton'])) {
    $contentsurl = new moodle_url('/mod/openstudio/managecontents.php',
            array('id' => $cm->id, 'l1id' => $l1id, 'l2id' => $l2id));
    return redirect($contentsurl->out(false));
}

// Process and Generate HTML.
$renderer = $PAGE->get_renderer('mod_openstudio');

// Setup page and theme settings.
$strpagetitle = $strpageheading = $course->shortname . ': ' . $cm->name
        . ' - ' . get_string('manageactivitycontents', 'openstudio');
$strpageurl = new moodle_url('/mod/openstudio/managecontents.php', array('id' => $cm->id, 'l1id' => $l1id, 'l2id' => $l2id));

// Render page header and crumb trail.
util::page_setup($PAGE, $strpagetitle, $strpageheading, $strpageurl, $course, $cm, 'manage');

// Setup page crumb trail.
$managelevelsurl = new moodle_url('/mod/openstudio/manageblocks.php', array('id' => $cm->id));
$managecontentsurl = new moodle_url('/mod/openstudio/managecontents.php', array('id' => $cm->id));
$crumbarray[get_string('openstudio:managelevels', 'openstudio')] = $managelevelsurl;
$crumbarray[get_string('contents', 'openstudio')] = $managecontentsurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

$data = array();
$options = array();
$options['l1id'] = $l1id;
$options['l2id'] = $l2id;
$options['id'] = $cm->id;

$studiofeatures = openstudio_feature_settings($cm->instance);
$setsorcollections = false;
if (($studiofeatures & util\feature::ENABLEFOLDERS) == util\feature::ENABLEFOLDERS) {
    $setsorcollections = util\feature::ENABLEFOLDERS;
}

$options['setsorcollections'] = $setsorcollections;

$islevelupdated = false;

if ($studioid > 0) {

    if (!empty($movedn)) {
        // Get the content ids.
        $contentid = key($movedn);

        // Now update the content.
        $contents = levels::get_records(3, $l2id);

        $position = 0;
        $movedposition = 0;
        foreach ($contents as $a) {
            $position++;
            if ($a->id == $contentid) {
                $data = $a;
                $movedposition = $position;
            }
            if ($movedposition) {
                if (($movedposition + 1) == $position) {
                    // Immediately update the sortorder on this content to match the one
                    // on our moved block. Set the moved content to this sortorder.
                    $ournewsortorder = $a->sortorder;
                    $a->sortorder = (int) $data->sortorder;
                    levels::update(3, $a->id, $a);
                    $data->sortorder = (int) $ournewsortorder;
                }
            }
        }

        // Update.
        levels::update(3, $contentid, $data);
        $islevelupdated = true;
    }

    if (!empty($moveup)) {
        // Get the block ids.
        $contentid = key($moveup);

        // Now update the block.
        $activities = levels::get_records(3, $l2id, false, 'sortorder DESC');
        $position = 0;
        $movedposition = 0;
        foreach ($activities as $a) {
            $position++;
            if ($a->id == $contentid) {
                $data = $a;
                $movedposition = $position;
            }
            if ($movedposition) {
                if (($movedposition + 1) == $position) {
                    // Immediately update the sortorder on this block to match the one on
                    // our moved block. Set the moved content to this sortorder.
                    $ournewsortorder = $a->sortorder;
                    $a->sortorder = (int) $data->sortorder;
                    levels::update(3, $a->id, $a);
                    $data->sortorder = (int) $ournewsortorder;
                }
            }
        }

        // Update.
        levels::update(3, $contentid, $data);
        $islevelupdated = true;
    }

    if ($addcontent) {
        $options['add_content'] = true;
    }

    if (!empty($editcontentid)) {
        $contentid = $editcontentid;
        $editform = new managecontents_form('', array_merge($options, array('editcontent' => $contentid)),
            'post', '', array('class' => 'unresponsive'));
        if ($editform->is_submitted()) {
            $fromform = $editform->get_data();
            if (isset($fromform->submitbutton)) {
                // Create the update object.
                $data = levels::get_record(3, $contentid);
                $data->name = trim($fromform->name);
                $data->required = $fromform->required;
                if ($setsorcollections !== false && $fromform->iscollection > 0) {
                    $data->contenttype = content::TYPE_FOLDER;
                } else {
                    $data->contenttype = 0;
                }
                if (isset($fromform->locktime) && !empty($fromform->locktime)) {
                    $data->locktime = $fromform->locktime;

                    // Only save lock type if lock content from is enabled.
                    if (!isset($fromform->locktype)) {
                        $fromform->locktype = lock::NONE;
                    }
                    $data->locktype = $fromform->locktype;
                } else {
                    $data->locktime = 0;
                }
                if (isset($fromform->unlocktime) && !empty($fromform->unlocktime)) {
                    $data->unlocktime = $fromform->unlocktime;
                } else {
                    $data->unlocktime = 0;
                }
                // With no schedules enabled, any content lock is removed immediatley.
                if ($data->locktime == 0 && $data->unlocktime == 0) {
                    $data->locktype = lock::NONE;
                }
                $data->lockprocessed = time();

                // Update the DB.
                levels::update(3, $contentid, $data);
                $islevelupdated = true;
            }
        }
    }

    if (!empty($editcontent)) {
        // Get block to be edited.
        $editcontentid = key($editcontent);
        $options['editcontent'] = $editcontentid;
        // Prevent previously submitted values being re-filled in to the form.
        unset($_POST['name'], $_POST['required'], $_POST['iscollection'], $_POST['locktype'], $_POST['locktime'],
              $_POST['unlocktime'], $_POST['reapplyschedule'], $_POST['editcontentid']);
        $data = levels::get_record(3, $editcontentid);
        $data->iscollection = in_array($data->contenttype, array(content::TYPE_FOLDER));
        if ($setsorcollections !== false) {
            $data->contenttype = content::TYPE_FOLDER;
        } else {
            $data->contenttype = 0;
        }
        // For some reason the moodleform isn't accepting default values for the lock times,
        // so we'll pass them in manually.
        if (isset($data->locktime) && $data->locktime > 0) {
            $options['lockenabled'] = true;
            $options['lockday'] = date('d', $data->locktime);
            $options['lockmonth'] = date('m', $data->locktime);
            $options['lockyear'] = date('Y', $data->locktime);
            $options['lockhour'] = date('H', $data->locktime);
            $options['lockminute'] = date('i', $data->locktime);
        }
        if (isset($data->locktime) && $data->unlocktime > 0) {
            $options['unlockenabled'] = true;
            $options['unlockday'] = date('d', $data->unlocktime);
            $options['unlockmonth'] = date('m', $data->unlocktime);
            $options['unlockyear'] = date('Y', $data->unlocktime);
            $options['unlockhour'] = date('H', $data->unlocktime);
            $options['unlockminute'] = date('i', $data->unlocktime);
        }
    }

    if (!empty($deletecontent)) {
        // Get block to delete.
        $contentid = key($deletecontent);
        levels::delete(3, $contentid, $studioid);
        $islevelupdated = true;
        if ($template = template::get_by_levelid($contentid)) {
            template::delete($template->id);
        }

        // Clean up sortorder.
        levels::cleanup_sortorder(3, $l2id);
    }

    if (!empty($reinstatecontent)) {
        // Get block to delete.
        $contentid = key($reinstatecontent);
        levels::soft_delete(3, $contentid, true);
        $islevelupdated = true;

        // Clean up sortorder.
        levels::cleanup_sortorder(3, $l2id);
    }

    if (!empty($newcontent)) {
        $options['add_content'] = true;
        $mform = new managecontents_form($PAGE->url->out(false), $options,
                'post', '', array('class' => 'unresponsive'));
        if (($newcontentdata = $mform->get_data()) || $addcontent) {
            if (isset($newcontentdata->submitbutton) || $addcontent) {
                // Get block to be edited.
                foreach ($newcontent as $key => $nbname) {
                    $nbname = trim($nbname);
                    if (!empty($nbname)) {
                        $insertdata = new stdClass();
                        $insertdata->parentid = $l2id;
                        $insertdata->name = $nbname;
                        if (!empty($newcontentorder)) {
                            if (array_key_exists($key, $newcontentorder)) {
                                // We have a content passed, make sure it is not empty and process.
                                $proposedorder = $newcontentorder[$key];
                                $insertdata->sortorder = $proposedorder;
                            }
                        } else {
                            // Get sortorder.
                            $insertdata->sortorder = levels::get_latest_sortorder(3, $l2id);
                        }
                        if (!empty($odsnewcontentrequired)) {
                            if (array_key_exists($key, $odsnewcontentrequired) &&
                                    ( $odsnewcontentrequired[$key] > 0)) {
                                // We have a content passed, make sure it is not empty and process.
                                $proposederequired = $odsnewcontentrequired[$key];
                                $insertdata->required = $proposederequired;
                            } else {
                                $insertdata->required = 0;
                            }
                        }
                        if (!empty($odsnewcontentiscollection)) {
                            if ($setsorcollections !== false &&
                                    array_key_exists($key, $odsnewcontentiscollection) &&
                                    ($odsnewcontentiscollection[$key] > 0)) {
                                $insertdata->contenttype = content::TYPE_FOLDER;
                            } else {
                                $insertdata->contenttype = 0;
                            }
                        }
                        levels::create(3, $insertdata);
                        $islevelupdated = true;

                        // Cleanup sortorder again.
                        levels::cleanup_sortorder(3, $l2id);

                        // Reset the settings.
                        $_POST['odsnewcontentrequired'][0] = 0;
                        $_POST['odsnewcontentiscollection'][0] = 0;
                    }
                }
            }
            redirect(new moodle_url($PAGE->url, array('add_content' => $addcontent)));
        }
    }

    // Update studio feature setting given level configuration has changed.
    if ($islevelupdated) {
        openstudio_feature_settings($studioid, true);
    }
}

// Output HTML.
echo $OUTPUT->header(); // Header.

echo html_writer::tag('h2', get_string('manageactivitycontents', 'openstudio'), array('id' => 'openstudio-main-content'));

$mform = new getcontents_form($PAGE->url->out(false), $options,
        'post', '', array('class' => 'unresponsive'));

$mform->set_data($data);
$mform->display();
if ($l2id > 0) {
    if ($addcontent) {
        $options['add_content'] = true;
        // Unset value.
        if (isset($_POST['odsnewcontentname'])) {
            $_POST['odsnewcontentname'][0] = '';
        }
        if (isset($_POST['odsnewcontentsortorder'])) {
            $_POST['odsnewcontentsortorder'][0] = levels::get_latest_sortorder(3, $l2id);
        }
    }
    $mform2 = new managecontents_form($PAGE->url->out(false), $options,
            'post', '', array('class' => 'unresponsive'));
    $mform2->set_data($data);
    $mform2->display();
}

echo $renderer->footer(); // Footer.
