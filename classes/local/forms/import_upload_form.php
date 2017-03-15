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

require_once($CFG->libdir . '/formslib.php');

class import_upload_form extends \moodleform {

    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        if ($this->_customdata['max_bytes']) {
            $maxbytes = $this->_customdata['max_bytes'];
        } else {
            $maxbytes = (isset($CFG->maxbytes) ? $CFG->maxbytes : \mod_openstudio\local\util\defaults::MAXBYTES);
        }

        $mform->addElement('filemanager', 'importfile', get_string('importzipfile', 'openstudio'), null,
                array('maxbytes' => $maxbytes, 'subdirs' => false,
                        'maxfiles' => 1, 'accepted_types' => array('.zip')));

        $mform->addHelpButton('importfile', 'importfile', 'openstudio');

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('submit', 'importcontents', get_string('importbutton', 'openstudio'));
    }

}