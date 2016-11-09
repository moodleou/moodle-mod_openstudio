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
 * API Functions for social paritication flags
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

class flags {

    const ALERT = 1;
    const FAVOURITE = 2;
    const NEEDHELP = 3;
    const MADEMELAUGH = 4;
    const INSPIREDME = 5;
    const READ_CONTENT = 6;
    const FOLLOW_CONTENT = 7;
    const FOLLOW_USER = 8;
    const COMMENT = 9;
    const COMMENT_LIKE = 10;
}