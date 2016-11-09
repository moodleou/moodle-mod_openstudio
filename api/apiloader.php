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
 * @package mod_studio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* Make sure this isn't being directly accessed. */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/openstudio/api/language.php');
require_once($CFG->dirroot . '/mod/openstudio/api/levels.php');
require_once($CFG->dirroot . '/mod/openstudio/api/stream.php');
require_once($CFG->dirroot . '/mod/openstudio/api/slot.php');
require_once($CFG->dirroot . '/mod/openstudio/api/slotversion.php');
require_once($CFG->dirroot . '/mod/openstudio/api/collection.php');
require_once($CFG->dirroot . '/mod/openstudio/api/set.php');
require_once($CFG->dirroot . '/mod/openstudio/api/user.php');
require_once($CFG->dirroot . '/mod/openstudio/api/group.php');
require_once($CFG->dirroot . '/mod/openstudio/api/search.php');
require_once($CFG->dirroot . '/mod/openstudio/api/flags.php');
require_once($CFG->dirroot . '/mod/openstudio/api/tracking.php');
require_once($CFG->dirroot . '/mod/openstudio/api/follow.php');
require_once($CFG->dirroot . '/mod/openstudio/api/rss.php');
require_once($CFG->dirroot . '/mod/openstudio/api/subscription.php');
require_once($CFG->dirroot . '/mod/openstudio/api/comments.php');
require_once($CFG->dirroot . '/mod/openstudio/api/tags.php');
require_once($CFG->dirroot . '/mod/openstudio/api/importexport.php');
require_once($CFG->dirroot . '/mod/openstudio/api/filesystem.php');
require_once($CFG->dirroot . '/mod/openstudio/api/tandc.php');
require_once($CFG->dirroot . '/mod/openstudio/api/embedcode.php');
require_once($CFG->dirroot . '/mod/openstudio/api/reports.php');
require_once($CFG->dirroot . '/mod/openstudio/api/lock.php');
require_once($CFG->dirroot . '/mod/openstudio/api/item.php');
require_once($CFG->dirroot . '/mod/openstudio/api/notifications.php');
