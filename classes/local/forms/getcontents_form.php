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
 * Open Studio get content structure form.
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

use mod_openstudio\local\api\levels;

class getcontents_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        // Get list of all blocks to populate dropdown.
        $context = \context_module::instance($this->_customdata['id']);
        $contextinfo = get_context_info_array($context->id);
        $studioid = $contextinfo[2]->instance;
        $mform->addElement('header', 'blocks', 'Select Block');
        if ($this->_customdata['l1id'] < 1) {
            $blocksobj = levels::get_records(1, $studioid);
            if ($blocksobj == false) {
                throw new \moodle_exception('errornoblocks', 'openstudio');
            }

            $blocksarr = array();
            foreach ($blocksobj as $block) {
                $blocksarr[$block->id] = $block->name;
            }

            if (isset($this->_customdata['l1id']) && $this->_customdata['l1id'] < 1) {
                $blocksarr[0] = get_string('selectblock', 'openstudio');
            }

            $mform->addElement('select', 'l1id', get_string('selectblock', 'openstudio'), $blocksarr);
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'submitbutton',
                get_string('selectblockbutton', 'openstudio'), array('id' => 'id_submitbutton'));
        } else {
            $blockdet = levels::get_record(1, $this->_customdata['l1id']);
            $mform->addElement('static', 'blockname', '', $blockdet->name);
        }

        if (isset($this->_customdata['l1id']) && $this->_customdata['l1id'] > 0) {
            $mform->setDefault('l1id', $this->_customdata['l1id']);

            $activitiesobj = levels::get_records(2, $this->_customdata['l1id']);
            if ($activitiesobj == false) {
                throw new \moodle_exception('errornoactivities', 'openstudio');
            }

            $activitiesarr = array();
            foreach ($activitiesobj as $act) {
                $activitiesarr[$act->id] = $act->name;
            }

            if (isset($this->_customdata['l2id']) && $this->_customdata['l2id'] < 1) {
                $activitiesarr[0] = get_string('selectactivity', 'openstudio');
            }

            $mform->addElement('header', 'activities', get_string('selectactivity', 'openstudio'));
            $mform->addElement('select', 'l2id', get_string('selectactivity', 'openstudio'), $activitiesarr);
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'submitbutton',
                $activitiesarr[0] = get_string('selectactivitybutton', 'openstudio'),
                array('id' => 'id_submitbutton'));

            if (isset($this->_customdata['l2id']) && $this->_customdata['l2id'] > 0) {
                $mform->setDefault('l2id', $this->_customdata['l2id']);
            } else {
                $mform->setDefault('l2id', 0);
            }
        } else {
            $mform->setDefault('l1id', 0);
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
