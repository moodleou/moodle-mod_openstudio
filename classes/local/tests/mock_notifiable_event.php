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
 *
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\tests;

use mod_openstudio\local\notifications\notifiable;

defined('MOODLE_INTERNAL') || die();

abstract class mock_notifiable_event implements notifiable {
    public $commentid;
    public $contentid;
    public $context;
    public $other;

    public function get_context() {
        return $this->context;
    }

    public function set_courseid($id) {
        $this->other['courseid'] = $id;
    }
}
