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
 * Definition of log events
 *
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

global $DB;

$logs = array(
    array('module' => 'openstudio', 'action' => 'add', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'update', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'edit content', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'processed subscription', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'search', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view all', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view group stream', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view module stream', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view work stream', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view atom content', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view atom stream', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view people group', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view people module', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view rss content', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view rss stream', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view content', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view content history', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view content version', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'exported content', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view export', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view import', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'imported content', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'index', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'manage activities', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'manage blocks', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'manage level export', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'manage level imported successfully', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'manage level import', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'manage contents', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'report usage', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'content updated', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'content created', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'content being edited', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'new content', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view group stream (1)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view group stream (2)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view group stream (3)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view group stream (4)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view pinboard stream (1)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view pinboard stream (2)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view pinboard stream (3)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view pinboard stream (4)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view work stream (1)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view work stream (2)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view work stream (3)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view work stream (4)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view module stream (1)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view module stream (2)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view module stream (3)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'view module stream (4)', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'content locked', 'mtable' => 'openstudio', 'field' => 'name'),
    array('module' => 'openstudio', 'action' => 'content unlocked', 'mtable' => 'openstudio', 'field' => 'name'),
);
