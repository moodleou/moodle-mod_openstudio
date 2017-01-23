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

require_once(__DIR__.'/language.php');
require_once(__DIR__.'/slotversion.php');
require_once(__DIR__.'/set.php');
require_once(__DIR__.'/user.php');
require_once(__DIR__.'/group.php');
require_once(__DIR__.'/search.php');
require_once(__DIR__.'/flags.php');
require_once(__DIR__.'/tracking.php');
require_once(__DIR__.'/follow.php');
require_once(__DIR__.'/rss.php');
require_once(__DIR__.'/subscription.php');
require_once(__DIR__.'/tags.php');
require_once(__DIR__.'/filesystem.php');
require_once(__DIR__.'/tandc.php');
require_once(__DIR__.'/embedcode.php');
require_once(__DIR__.'/reports.php');
require_once(__DIR__.'/lock.php');
require_once(__DIR__.'/item.php');
require_once(__DIR__.'/notifications.php');
require_once(__DIR__.'/../../studio/constants.php'); // Use old constants if new ones is not available.
