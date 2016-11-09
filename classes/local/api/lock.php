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
 * API functions for content locking.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

class lock {

    const NONE = 0;
    const ALL = 1; // Combination of 2, 4 and 8.
    const CRUD = 2; // Cannot edit/delete/archive content.
    const SOCIAL = 4; // Cannot do social flagging (favourite, inspire, etc.).
    const SOCIAL_CRUD = 6; // Cannot have new comments.
    const COMMENT = 8; // Combination of 2 and 4.
    const COMMENT_CRUD = 12; // Combination of 2 and 8.
}
