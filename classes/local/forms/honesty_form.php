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
 * Open Studio honesty check form
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

/**
 * Studio terms and conditions acceptance form.
 *
 * @package mod_openstudio
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class honesty_form extends \moodleform {

    protected function definition() {

        $mform = $this->_form;

        $mform->addElement('header', 'attachment', get_string('honestycrumb', 'openstudio'));

        $honestyhtml = \html_writer::start_tag('div');
        $honestyhtml .= get_string('honestytext', 'openstudio');
        $honestyhtml .= \html_writer::end_tag('div');

        $mform->addElement('html', $honestyhtml);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton',
                get_string('honestyacccept', 'openstudio'),
                array('id' => 'id_submitbutton'));
        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton',
                '',
                array('id' => 'id_cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
