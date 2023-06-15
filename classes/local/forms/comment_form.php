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
 * Open Studio import upload form.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\forms;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

use \mod_openstudio\local\util\defaults;

require_once($CFG->libdir . '/formslib.php');

class comment_form extends \moodleform {

    protected function definition() {
        global $CFG;
        $mform = $this->_form;

        if ($this->_customdata['max_bytes']) {
            $maxbytes = $this->_customdata['max_bytes'];
        } else {
            $maxbytes = $CFG->maxbytes ?? defaults::MAXBYTES;
        }

        // Course module ID.
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->setDefault('cmid', $this->_customdata['id']);

        // Content ID.
        $mform->addElement('hidden', 'cid');
        $mform->setType('cid', PARAM_INT);
        $mform->setDefault('cid', $this->_customdata['cid']);

        // Comment parent ID.
        $mform->addElement('hidden', 'inreplyto');
        $mform->setType('inreplyto', PARAM_INT);
        $mform->setDefault('inreplyto', 0);

        // Comment text.
        $editoroptions = [
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'maxbytes' => $maxbytes,
        ];
        $mform->addElement('editor', 'commentext',
                get_string('contentcommentsformlabelcomment', 'openstudio'),
                null, $editoroptions);
        $mform->setType('commentext', PARAM_RAW);

        // Comment attachment.
        if ($this->_customdata['attachmentenable'] === true) {

            // Static text for comment attachment.
            $mform->addElement('static', 'commentheader', null,
                get_string('contentcommentsformheader2', 'mod_openstudio'));

            $mform->addElement('filepicker', 'commentattachment',
                get_string('contentcommentsformattachment', 'mod_openstudio'), null,
                array('maxbytes' => $maxbytes, 'accepted_types' => '.mp3'));
        }

        // Submit button.
        $mform->addElement('submit', 'postcomment', get_string('contentcommentsformpostcomment', 'openstudio'));
    }

}
