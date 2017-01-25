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
 * Open Studio manage folders.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/openstudio/api/apiloader.php');

use mod_openstudio\local\api\template;
use mod_openstudio\local\util;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\forms\managefolders_form;

$id = required_param('id', PARAM_INT);
$l3id = required_param('l3id', PARAM_INT);
$l2id = required_param('l2id', PARAM_INT);
$l1id = required_param('l1id', PARAM_INT);

$contentmoveup = optional_param_array('contentmoveup', array(), PARAM_INT);
$contentmovedown = optional_param_array('contentmovedown', array(), PARAM_INT);
$contentdelete = optional_param_array('contentdelete', array(), PARAM_INT);

// Page init and security checks.
$coursedata = util::render_page_init($id);
$cm = $coursedata->cm;
$course = $coursedata->course;
$context = $coursedata->mcontext;

require_capability('mod/openstudio:managelevels', $context);

// Process and Generate HTML.
$renderer = $PAGE->get_renderer('mod_openstudio');

$settemplate = template::get_by_levelid($l3id);
$contenttemplates = array();
if ($settemplate) {
    $contenttemplates = template::get_contents($settemplate->id);
}

$options = array('contentcount' => count($contenttemplates));
$url = new \moodle_url('/mod/openstudio/managefolders.php',
        array('id' => $cm->id, 'l1id' => $l1id, 'l2id' => $l2id, 'l3id' => $l3id));
$PAGE->set_url($url);
$mform = new managefolders_form($url->out(false), $options,
        'post', '', array('class' => 'unresponsive'));

if ($mform->is_cancelled()) {
    return redirect($PAGE->url->out(false));
}

if ($mform->is_submitted()) {
    // Process the form.
    if ($data = $mform->get_data()) {
        if ($settemplate && (!empty($contentmoveup) || !empty($contentmovedown))) {
            // If we're moving a content, just do the move.
            if (!empty($contentmoveup)) {
                $contenttemplate = $contenttemplates[array_keys($contenttemplates)[key($contentmoveup)]];
                $firsttemplate = reset($contenttemplates);
                if ($contenttemplate->contentorder > $firsttemplate->contentorder) {
                    $prevorder = $contenttemplate->contentorder - 1;
                    if ($prevcontenttemplate = template::get_content_by_contentorder($settemplate->id, $prevorder)) {
                        $tempcontentorder = $contenttemplate->contentorder;
                        $contenttemplate->contentorder = $prevcontenttemplate->contentorder;
                        $prevcontenttemplate->contentorder = $tempcontentorder;
                        template::update_content($contenttemplate);
                        template::update_content($prevcontenttemplate);
                    }
                }
            } else {
                $contenttemplate = $contenttemplates[array_keys($contenttemplates)[key($contentmovedown)]];
                $lasttemplate = end($contenttemplates);
                if ($contenttemplate->contentorder < $lasttemplate->contentorder) {
                    $nextorder = $contenttemplate->contentorder + 1;
                    if ($nextcontenttemplate = template::get_content_by_contentorder($settemplate->id, $nextorder)) {
                        $tempcontentorder = $contenttemplate->contentorder;
                        $contenttemplate->contentorder = $nextcontenttemplate->contentorder;
                        $nextcontenttemplate->contentorder = $tempcontentorder;
                        template::update_content($contenttemplate);
                        template::update_content($nextcontenttemplate);
                    }
                }
            }
            redirect($PAGE->url);
        } else if ($settemplate && (!empty($contentdelete))) {
            $contenttemplate = $contenttemplates[array_keys($contenttemplates)[key($contentdelete)]];
            template::delete_content($contenttemplate->id);
            redirect($PAGE->url);
        } else {
            try {
                if ($settemplate) {
                    // Update the existing template.
                    $settemplate->guidance = $data->setguidance['text'];
                    $settemplate->additionalcontents = $data->additionalcontents;
                    template::update($settemplate);
                } else {
                    // Create a new template.
                    $settemplate = (object) array(
                        'guidance'        => $data->setguidance['text'],
                        'additionalcontents' => $data->additionalcontents
                    );
                    $settemplate->id = template::create($l3id, $settemplate);
                }
                if (!empty($data->contentcount)) {
                    foreach ($data->contentid as $key => $contentid) {
                        $permissions = 0;
                        if (!$data->contentpreventreorder[$key]) {
                            $permissions = $permissions | folder::PERMISSION_REORDER;
                        }
                        if (!empty($contentid) && array_key_exists($contentid, $contenttemplates)) {
                            $contenttemplate = $contenttemplates[$contentid];
                            $contenttemplate->name = $data->contentname[$key];
                            $contenttemplate->guidance = $data->contentguidance[$key]['text'];
                            $contenttemplate->permissions = $permissions;
                            template::update_content($contenttemplate);
                            // Update the content template.
                        } else {
                            // Create a new content template.
                            $contenttemplate = (object) array(
                                'name'          => $data->contentname[$key],
                                'guidance'      => $data->contentguidance[$key]['text'],
                                'permissions'   => $permissions,
                            );
                            template::create_content($settemplate->id, $contenttemplate);
                        }
                    }
                }
                $message = get_string('templateupdated', 'openstudio');
            } catch (Exception $e) {
                $message = get_string('templateupdatefail', 'openstudio') . $e->getMessage();
            }
            redirect($url, $message);
        }
    }
}

$formdata = null;
if ($settemplate) {
    $formdata = (object) array(
        'setguidance' => array('text' => $settemplate->guidance, 'format' => editors_get_preferred_format()),
        'additionalcontents' => $settemplate->additionalcontents,
        'contentid' => array(),
        'contentname' => array(),
        'contentguidance' => array(),
        'contentinitialposition' => array(),
        'contentpreventreorder' => array(),
        'contentcount' => count($contenttemplates)
    );
    if (!empty($contenttemplates)) {
        $i = 0;
        foreach ($contenttemplates as $contenttemplate) {
            $preventreorder = ($contenttemplate->permissions & folder::PERMISSION_REORDER) != folder::PERMISSION_REORDER;
            $formdata->contentid[$i] = $contenttemplate->id;
            $formdata->contentname[$i] = $contenttemplate->name;
            $formdata->contentguidance[$i]['text'] = $contenttemplate->guidance;
            $formdata->contentinitialposition[$i] = $contenttemplate->contentorder;
            $formdata->contentpreventreorder[$i] = $preventreorder;
            $i++;
        }
    }
}

// Setup page and theme settings.
$strpagetitle = $strpageheading = $course->shortname . ': ' . $cm->name
        . ' - ' . get_string('configurefolder', 'openstudio');
$strpageurl = new moodle_url('/mod/openstudio/managefolders.php',
        array('id' => $cm->id, 'l1id' => $l1id, 'l2id' => $l2id, 'l3id' => $l3id));

// Render page header and crumb trail.
util::page_setup($PAGE, $strpagetitle, $strpageheading, $strpageurl, $course, $cm, 'manage');

// Setup page crumb trail.
$managelevelsurl = new moodle_url('/mod/openstudio/manageblocks.php', array('id' => $cm->id));
$managecontentsurl = new moodle_url('/mod/openstudio/managecontents.php', array('id' => $cm->id));
$crumbarray[get_string('openstudio:managelevels', 'openstudio')] = $managelevelsurl;
$crumbarray[get_string('contents', 'openstudio')] = $managecontentsurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

$content = levels::get_record(3, $l3id);

$PAGE->requires->js_call_amd('mod_openstudio/managefolders', 'init');

// Output HTML.
echo $OUTPUT->header(); // Header.
echo html_writer::tag('h2', get_string('configurefolder', 'openstudio') . ' - ' .
    $content->name, array('id' => 'openstudio-main-content'));

$data = (object) array();
$mform->set_data($formdata);
$mform->display();

echo $OUTPUT->footer(); // Footer.
