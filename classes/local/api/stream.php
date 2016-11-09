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
 * API functions for content streams.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

class stream {

    const FILTER_EMPTYCONTENT = 1;
    const FILTER_NOTREAD = 2;
    const FILTER_FAVOURITES = 3;
    const FILTER_MOSTPOPULAR = 4;
    const FILTER_HELPME = 5;
    const FILTER_MOSTSMILES = 6;
    const FILTER_MOSTINSPIRATION = 7;
    const FILTER_COMMENTS = 8;
    const FILTER_READ = 9;
    const FILTER_LOCKED = 10;
    const FILTER_TUTOR = 11;

    const FILTER_PEOPLE_GROUP = 0;
    const FILTER_PEOPLE_MODULE = 1;

    const SCOPE_EVERYONE = 1;
    const SCOPE_MY = 2;
    const SCOPE_THEIRS = 3;

    const SORT_ASC = 1;
    const SORT_DESC = 0;

    const SORT_BY_DATE = 1;
    const SORT_BY_ACTIVITYTITLE = 3;
    const SORT_BY_USERNAME = 2;
    const SORT_BY_COMMENTNUMBERS = 4;

    const SORT_PEOPLE_ACTIVTY = 1;
    const SORT_PEOPLE_NAME = 2;
    const SORT_PEOPLE_ASKINGFORHELP = 4;

}