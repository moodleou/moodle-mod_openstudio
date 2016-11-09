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
 * API functions for activity tracking.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

class tracking {

    const CREATE_CONTENT = 1;
    const READ_CONTENT = 2;
    const READ_CONTENT_VERSION = 3;
    const DELETE_CONTENT = 4;
    const DELETE_CONTENT_VERSION = 5;
    const UPDATE_CONTENT = 6;
    const UPDATE_CONTENT_VISIBILITY_PRIVATE = 7;
    const UPDATE_CONTENT_VISIBILITY_GROUP = 8;
    const UPDATE_CONTENT_VISIBILITY_MODULE = 9;
    const ARCHIVE_CONTENT = 10;
    const MODIFY_FOLDER = 11;
    const COPY_CONTENT = 12;
    const ADD_CONTENT_TO_FOLDER = 13;
    const LINK_CONTENT_TO_FOLDER = 14;
    const COPY_CONTENT_TO_FOLDER = 15;
    const UPDATE_CONTENT_VISIBILITY_TUTOR = 16;
}
