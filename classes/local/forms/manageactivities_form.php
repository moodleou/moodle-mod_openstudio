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
 * Open Studio manage activities structure form.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\forms;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\levels;

class manageactivities_form extends \moodleform {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;

        $context = \context_module::instance($this->_customdata['id']);
        $contextinfo = get_context_info_array($context->id);
        $studioid = $contextinfo[2]->instance;

        if (has_capability('mod/openstudio:deletelevels', $context)) {
            $sdactivities = levels::get_records(2, $this->_customdata['l1id'],
                    true, '', levels::SOFT_DELETED);
            if (count($sdactivities) > 0) {
                $mform->addElement('header', 'softdeletedactivitiesheader',
                        get_string('softdeletedactivitiesheader', 'openstudio'));
                $mform->addHelpButton('softdeletedactivitiesheader',
                        'softdeletedactivitiesheader', 'openstudio');
                $mform->setExpanded('softdeletedactivitiesheader', false);
            }
            $counter = 0;
            foreach ((array)$sdactivities as $activity) {
                $counter++;
                $activityname = array();
                $contentsinactivity = 0;

                // We have to run this here each time as moodle does not support
                // reset() or rewind() for iterators.
                $allcontentsinstudio = content::get_all_records($studioid);
                if ($allcontentsinstudio) {
                    $contentsinactivity = levels::count_contents_in_level(2, $activity->id, $allcontentsinstudio);
                }
                $activityname[] = $mform->createElement(
                        'static', 'activityname[' . $activity->id . ']', '', $activity->name);
                if ($contentsinactivity > 0) {
                    // Get block name to add to url.
                    $parentblock = levels::get_record(1, $this->_customdata['l1id']);
                    $activityurl = new \moodle_url('/mod/openstudio/search.php', array('id' => $this->_customdata['id']));
                    $searchstring = '&searchtext=' . urlencode('"' . $parentblock->name . '" "' .$activity->name . '"');
                    $activityname[] = $mform->createElement('static', 'contentcount[' . $activity->id . ']',
                            '', "(" . \html_writer::link($activityurl->out(false) . $searchstring,
                            get_string('viewcontents', 'openstudio') . ")"));
                }

                $activityname[] = $mform->createElement('image', 'reinstatebutton[' . $activity->id . ']',
                        $OUTPUT->image_url('undo', 'openstudio'),
                        array('title' => get_string('undeletelevel', 'openstudio')));
                $mform->addGroup($activityname, null, $counter . '. ', ' ', false);
            }
        }

        $activities = levels::get_records(2, $this->_customdata['l1id']);

        $mform->addElement('header', 'activeactivitiesheader', get_string('activeactivitiesheader', 'openstudio'));
        $mform->addHelpButton('activeactivitiesheader', 'activeactivitiesheader', 'openstudio');
        $total = count($activities);
        $counter = 0;
        foreach ((array)$activities as $activity) {
            $counter++;
            $activityname = array();
            $contentsinactivity = 0;
            $smallicon = 'icon smallicon';

            // We have to run this here each time as moodle does not support reset()
            // or rewind() for iterators.
            $allcontentsinstudio = content::get_all_records($studioid);
            if ($allcontentsinstudio) {
                $contentsinactivity = levels::count_contents_in_level(2, $activity->id, $allcontentsinstudio);
            }

            // Create URL to drill down.
            $l3url = new \moodle_url('/mod/openstudio/managecontents.php', array('id' => $this->_customdata['id'],
                    'l2id' => $activity->id, 'l1id' => $this->_customdata['l1id']));
            if (isset($this->_customdata['editactivity']) && $this->_customdata['editactivity'] == $activity->id) {
                $formelementedit = $mform->createElement('text',
                        'activityname[' . $activity->id . ']', '', array('value' => $activity->name));
                $formelementedit->setType('activityname[' . $activity->id . ']', PARAM_TEXT);
                $activityname[] = $formelementedit;

                $formelementhide = $mform->createElement('advcheckbox',
                        'activityhide[' . $activity->id . ']', '', get_string('levelhide', 'openstudio'));
                $formelementhide->setChecked(((int) $activity->hidelevel) == 1 ? true : false);
                $activityname[] = $formelementhide;
            } else {
                $activityname[] = $mform->createElement('static', 'activityname[' . $activity->id . ']', '',
                        \html_writer::link($l3url->out(false), $activity->name));
            }

            if ($total > 1) {
                if ($counter == 1) {
                    $activityname[] = $mform->createElement('image', 'movednbutton[' . $activity->id . ']',
                            $OUTPUT->image_url('t/down'), ['title' => get_string('movedown', 'openstudio'), 'class' => $smallicon]);
                } else if ($counter == $total) {
                    $activityname[] = $mform->createElement('image', 'moveupbutton[' . $activity->id . ']',
                            $OUTPUT->image_url('t/up'), ['title' => get_string('moveup', 'openstudio'), 'class' => $smallicon]);
                } else {
                    $activityname[] = $mform->createElement('image', 'movednbutton[' . $activity->id . ']',
                            $OUTPUT->image_url('t/down'), ['title' => get_string('movedown', 'openstudio'), 'class' => $smallicon]);
                    $activityname[] = $mform->createElement('image', 'moveupbutton[' . $activity->id . ']',
                            $OUTPUT->image_url('t/up'), ['title' => get_string('moveup', 'openstudio'), 'class' => $smallicon]);
                }
            }

            $activityname[] = $mform->createElement('image', 'editbutton[' . $activity->id . ']',
                    $OUTPUT->image_url('t/edit'), ['title' => get_string('editlevel', 'openstudio'), 'class' => $smallicon]);

            if ($contentsinactivity > 0) {
                // Get block name to add to url.
                $parentblock = levels::get_record(1, $this->_customdata['l1id']);
                $activityurl = new \moodle_url('/mod/openstudio/search.php', array('id' => $this->_customdata['id']));
                $searchstring = '&searchtext=' . urlencode('"' . $parentblock->name . '" "' . $activity->name . '"');
                $activityname[] = $mform->createElement('static', 'contentcount[' . $activity->id . ']', '',
                        "(" . \html_writer::link($activityurl->out(false).$searchstring,
                        get_string('viewcontents', 'openstudio') . ")"));
                if (has_capability('mod/openstudio:deletelevels', $context)) {
                    $activityname[] = $mform->createElement('image', 'deletebutton[' . $activity->id . ']',
                            $OUTPUT->image_url('t/delete'), array('title' => get_string('deletelevel', 'openstudio'), 'class' => $smallicon));
                }
            } else {
                $activityname[] = $mform->createElement('image', 'deletebutton[' . $activity->id . ']',
                        $OUTPUT->image_url('t/delete'), array('title' => get_string('deletelevel', 'openstudio'), 'class' => $smallicon));
            }

            $mform->addGroup($activityname, null, $counter . '. ', ' ', false);

        }
        $mform->addElement('hidden', 'numberofactivities', $total);
        $mform->setType('numberofactivities', PARAM_INT);

        // Add in CMID.
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'studioid', $studioid);
        $mform->setType('studioid', PARAM_INT);
        $mform->addElement('hidden', 'l1id', $this->_customdata['l1id']);
        $mform->setType('l1id', PARAM_INT);

        // Code below adds in the repeater function to add additional blocks.
        $repeatarray = array();
        $repeatarray[] = $mform->createElement('header', '',
                get_string('newactivityheader', 'openstudio'));
        $formelement1 = $mform->createElement('text', 'odsnewactivityname',
                get_string('newactivitylabel', 'openstudio'));
        $formelement1->setType('odsnewactivityname', PARAM_TEXT);
        $repeatarray[] = $formelement1;
        $formelement2 = $mform->createElement('text', 'odsnewactivitysortorder',
                get_string('newactivityposition', 'openstudio'));
        $formelement2->setType('odsnewactivitysortorder', PARAM_INT);
        $repeatarray[] = $formelement2;
        $formelement3 = $mform->createElement('checkbox',
                'odsnewactivityhide', '', get_string('levelhide', 'openstudio'));
        $repeatarray[] = $formelement3;
        $repeateloptions = array();
        $repeateloptions['odsnewactivitysortorder']['default'] = levels::get_latest_sortorder(
                2, $this->_customdata['l1id']);

        $mform->setType('odsnewactivitysortorder', PARAM_INT);
        $repeatno = 0;
        $this->repeat_elements($repeatarray, $repeatno,
                    $repeateloptions, get_string('addanactivity', 'openstudio'), 'add_activity', 1,
                    get_string('addanotheractivity', 'openstudio'));

        // Add save and cancel buttons.
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton',
                get_string('savechanges', 'openstudio'), array('id' => 'id_submitbutton'));
        $buttonarray[] = $mform->createElement('cancel', '',
                '', array('id' => 'id_cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
