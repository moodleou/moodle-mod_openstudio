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
 * Open Studio support one click unsubscribe.
 *
 * @package mod_openstudio
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use mod_openstudio\local\api\rss;
use mod_openstudio\local\api\subscription;
use mod_openstudio\local\util;

$cmid = required_param('id', PARAM_INT);
$userid = required_param('user', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUM);
$subscriptionid = required_param('subscriptionid', PARAM_INT);

// Check one click key as per rss.
$isvalidated = rss::validate_key($key, $userid, rss::UNSUBSCRIBE);
if (!$isvalidated) {
    throw new moodle_exception('errorunsubsribeparams', 'openstudio');
}

if (!($cm = get_coursemodule_from_id('openstudio', $cmid))) {
    throw new \moodle_exception('invalidcoursemodule');
}

// Get course instance.
global $DB, $PAGE;
$course = get_course($cm->course);
// Get module instance.
$cminstance = $DB->get_record('openstudio', ['id' => $cm->instance], '*', MUST_EXIST);
// Get permissions.
$permissions = util::check_permission($cm, $cminstance, $course);
$success = subscription::delete($subscriptionid, $userid, !$permissions->managecontent);
$confirmtext = $success ? 'unsubscribeconfirm' : 'unsubscribealready';

// Not redirecting? OK, confirm.
$pageurl = util::get_current_url();
$exportpageurl = new moodle_url('/mod/openstudio/view.php', ['id' => $cmid]);
$pagetitle = $pageheading = get_string('pageheader', 'openstudio',
        ['cname' => $course->shortname, 'cmname' => $cm->name,
                'title' => get_string('unsubscribe', 'openstudio')]);
$backurl = new moodle_url('/mod/openstudio/view.php', ['id' => $cm->id]);
util::page_setup($PAGE, $pagetitle, $pageheading, $pageurl, $course, $cm);
$renderer = util::get_renderer();
print $renderer->header();
print $renderer->notification(get_string($confirmtext, 'openstudio'), 'success');
print $renderer->continue_button($backurl);
print $renderer->footer();
