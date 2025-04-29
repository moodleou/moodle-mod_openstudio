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
 * Open Studio manage block structure form.
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

class manageblocks_form extends \moodleform {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;

        $context = \context_module::instance($this->_customdata['id']);
        $contextinfo = get_context_info_array($context->id);
        $openstudioid = $contextinfo[2]->instance;

        if (has_capability('mod/openstudio:deletelevels', $context)) {
            $sdblocks = levels::get_records(1, $openstudioid, true, '', levels::SOFT_DELETED);
            if (count($sdblocks) > 0) {
                $mform->addElement('header', 'softdeletedblocksheader', get_string('softdeletedblocksheader', 'openstudio'));
                $mform->addHelpButton('softdeletedblocksheader', 'softdeletedblocksheader', 'openstudio');
                $mform->setExpanded('softdeletedblocksheader', false);
            }
            $counter = 0;
            foreach ((array)$sdblocks as $block) {
                $counter++;
                $blockname = array();
                $contentsinblock = 0;

                $allcontentsinstudio = content::get_all_records($openstudioid);
                if ($allcontentsinstudio) {
                    $contentsinblock = levels::count_contents_in_level(1, $block->id, $allcontentsinstudio);
                }
                $blockname[] = $mform->createElement('static', 'blockname[' . $block->id . ']', '', $block->name);
                if ($contentsinblock > 0) {
                    $blockurl = new \moodle_url('/mod/openstudio/search.php', array('id' => $this->_customdata['id']));
                    $searchstring = '&searchtext=' . urlencode('"' . $block->name . '"');
                    $blockname[] = $mform->createElement('static', 'contentcount[' . $block->id . ']', '',
                        "(" . \html_writer::link($blockurl->out(false).$searchstring,
                            get_string('viewcontents', 'openstudio').")"));
                }
                $blockname[] = $mform->createElement('image', 'reinstatebutton[' . $block->id . ']',
                    $OUTPUT->image_url('undo', 'openstudio'), array('title' => get_string('undeletelevel', 'openstudio')));
                $mform->addGroup($blockname, null, $counter . '. ', ' ', false);
            }
        }

        // Get all blocks.
        $blocks = levels::get_records(1, $openstudioid);
        $mform->addElement('header', 'activeblocksheader', get_string('activeblocksheader', 'openstudio'));
        $mform->addHelpButton('activeblocksheader', 'activeblocksheader', 'openstudio');
        $total = count($blocks);
        $counter = 0;
        foreach ((array)$blocks as $block) {
            $counter++;
            $blockname = array();
            $contentsinblock = 0;
            $smallicon = 'icon smallicon';

            $allcontentsinstudio = content::get_all_records($openstudioid);
            if ($allcontentsinstudio) {
                $contentsinblock = levels::count_contents_in_level(1, $block->id, $allcontentsinstudio);
            }
            // Create URL to drill down.
            $l2url = new \moodle_url('/mod/openstudio/manageactivities.php',
                array('id' => $this->_customdata['id'], 'l1id' => $block->id));
            if (isset($this->_customdata['editblock']) && $this->_customdata['editblock'] == $block->id) {
                $blocknameedit = $mform->createElement('text', 'blockname[' . $block->id . ']', '',
                    array('value' => $block->name));
                $blocknameedit->setType('blockname[' . $block->id . ']', PARAM_TEXT);
                $blockname[] = $blocknameedit;
            } else {
                $blockname[] = $mform->createElement('static', 'blockname[' . $block->id . ']', '',
                    \html_writer::link($l2url->out(false), $block->name));
            }
            if ($total > 1) {
                if ($counter == 1) {
                    $blockname[] = $mform->createElement('image', 'movednbutton[' . $block->id . ']',
                        $OUTPUT->image_url('t/down'), ['title' => get_string('movedown', 'openstudio'), 'class' => $smallicon]);
                } else if ($counter == $total) {
                    $blockname[] = $mform->createElement('image', 'moveupbutton[' . $block->id . ']',
                        $OUTPUT->image_url('t/up'), ['title' => get_string('moveup', 'openstudio'), 'class' => $smallicon]);
                } else {
                    $blockname[] = $mform->createElement('image', 'movednbutton[' . $block->id . ']',
                        $OUTPUT->image_url('t/down'), ['title' => get_string('movedown', 'openstudio'), 'class' => $smallicon]);
                    $blockname[] = $mform->createElement('image', 'moveupbutton[' . $block->id . ']',
                        $OUTPUT->image_url('t/up'), ['title' => get_string('moveup', 'openstudio'), 'class' => $smallicon]);
                }
            }
            $blockname[] = $mform->createElement('image', 'editbutton[' . $block->id . ']',
                $OUTPUT->image_url('t/edit'), ['title' => get_string('editlevel', 'openstudio'), 'class' => $smallicon]);
            if ($contentsinblock > 0) {
                $blockurl = new \moodle_url('/mod/openstudio/search.php', array('id' => $this->_customdata['id']));
                $searchstring = '&searchtext=' . urlencode('"' . $block->name . '"');
                $blockname[] = $mform->createElement('static', 'contentcount[' . $block->id . ']', '',
                    "(" . \html_writer::link($blockurl->out(false) . $searchstring,
                        get_string('viewcontents', 'openstudio') . ")"));
                if (has_capability('mod/openstudio:deletelevels', $context)) {
                    $blockname[] = $mform->createElement('image', 'deletebutton[' . $block->id . ']',
                        $OUTPUT->image_url('t/delete'), ['title' => get_string('deletelevel', 'openstudio'), 'class' => $smallicon]);
                }
            } else {
                $blockname[] = $mform->createElement('image', 'deletebutton[' . $block->id . ']',
                    $OUTPUT->image_url('t/delete'), ['title' => get_string('deletelevel', 'openstudio'), 'class' => $smallicon]);
            }
            $mform->addGroup($blockname, null, $counter . '. ', ' ', false);
        }

        $mform->addElement('hidden', 'numberofblocks', $total);
        $mform->setType('numberofblocks', PARAM_INT);
        $mform->addElement('hidden', 'id', $this->_customdata['id']); // CMID.
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'openstudioid', $openstudioid);
        $mform->setType('openstudioid', PARAM_INT);

        // Code below adds in the repeater function to add additional blocks.
        $repeatarray = array();
        $repeatarray[] = $mform->createElement('header', '',
            get_string('newblockheader', 'openstudio'));
        $formelement1 = $mform->createElement('text', 'odsnewblockname',
            get_string('newblocklabel', 'openstudio'));
        $formelement1->setType('odsnewblockname', PARAM_TEXT);
        $repeatarray[] = $formelement1;
        $formelement2 = $mform->createElement('text', 'odsnewblocksortorder',
            get_string('newblockposition', 'openstudio'));
        $formelement2->setType('odsnewblocksortorder', PARAM_INT);
        $repeatarray[] = $formelement2;
        $repeateloptions = array();
        $repeateloptions['odsnewblocksortorder']['default']
            = levels::get_latest_sortorder(1, $openstudioid);
        $mform->setType('odsnewblocksortorder', PARAM_INT);
        $repeatno = 0;
        $this->repeat_elements($repeatarray, $repeatno,
            $repeateloptions, get_string('addablock', 'openstudio'), 'add_block', 1,
            get_string('addanotherblock', 'openstudio'));

        // Add save and cancel buttons.
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton',
            get_string('savechanges', 'openstudio'), array('id' => 'id_submitbutton'));
        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton',
            '', array('id' => 'id_cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
