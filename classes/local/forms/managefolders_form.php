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
 * Open Studio manage folder form.
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

use mod_openstudio\local\util\defaults;

class managefolders_form extends \moodleform {

    public function definition() {
        global $OUTPUT;

        $mform = $this->_form;

        $mform->addElement('header', 'setheader', get_string('configurefolder', 'openstudio'));
        // Guidance text.
        $mform->addElement('editor', 'setguidance', get_string('folderguidance', 'openstudio'));
        $mform->setType('setguidance', PARAM_RAW);
        // Number of additional contents allowed.
        $mform->addElement('text', 'additionalcontents', get_string('additionalcontents', 'openstudio'));
        $mform->setType('additionalcontents', PARAM_INT);
        $mform->addRule('additionalcontents', get_string('additionalcontentsnumeric', 'openstudio'),
            'numeric', '', 'client');
        $mform->addRule('additionalcontents', get_string('additionalcontentslength', 'openstudio',
            defaults::MAXPINBOARDFOLDERSCONTENTSLENGTH),
            'maxlength', defaults::MAXPINBOARDFOLDERSCONTENTSLENGTH, 'client');
        $mform->setDefault('additionalcontents', defaults::FOLDERTEMPLATEADDITIONALCONTENTS);

        $mform->addElement('header', 'contentheader', get_string('configurecontents', 'openstudio'));
        // For each content...
        $contentfields = array();
        $contentfieldsoptions = array();
        // Subheading.
        $contentfields[] = $mform->createElement('static', 'contentsubheader', '');
        $contentfieldsoptions['contentsubheader']['default'] = \html_writer::tag('h3',
            get_string('content', 'openstudio') . ' {no}');
        $contentfields[] = $mform->createElement('image', 'contentmovedown',
            $OUTPUT->image_url('t/down'),
            array('title' => get_string('movedown', 'openstudio'), 'class' => 'movedown'));
        $contentfields[] = $mform->createElement('image', 'contentmoveup',
            $OUTPUT->image_url('t/up'),
            array('title' => get_string('moveup', 'openstudio'), 'class' => 'moveup'));
        $contentfields[] = $mform->createElement('image', 'contentdelete',
            $OUTPUT->image_url('t/delete'),
            array('title' => get_string('deletelevel', 'openstudio'), 'class' => 'delete'));

        // Name.
        $contentelement1 = $mform->createElement('text', 'contentname', get_string('contentname', 'openstudio'));
        $contentfields[] = $contentelement1;
        $mform->setType('contentname', PARAM_TEXT);

        $contentfieldsoptions['contentname']['rule'] = array(get_string('errorcontenttemplatename', 'openstudio'),
            'required');

        // Guidance.
        $contentelement2 = $mform->createElement('editor', 'contentguidance', get_string('contentguidance', 'openstudio'));
        $contentfields[] = $contentelement2;
        $mform->setType('contentguidance', PARAM_RAW);

        // Allow user to re-order content? (with help explaining how this works).
        $contentelement3 = $mform->createElement('advcheckbox', 'contentpreventreorder',
            get_string('contentpreventreorder', 'openstudio'));
        $contentfields[] = $contentelement3;
        $contentfieldsoptions['contentpreventreorder']['helpbutton'] = array('contentpreventreorder', 'openstudio');
        $mform->setType('contentpreventreorder', PARAM_BOOL);

        $contentelement4 = $mform->createElement('hidden', 'contentid');
        $contentfields[] = $contentelement4;
        $mform->setType('contentid', PARAM_INT);

        $this->repeat_elements($contentfields, $this->_customdata['contentcount'], $contentfieldsoptions, 'contentcount',
            'addcontent', 1, get_string('addanothercontent', 'openstudio'));

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton',
            get_string('savechanges', 'openstudio'), array('id' => 'id_submitbutton'));
        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton',
            '', array('id' => 'id_cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
