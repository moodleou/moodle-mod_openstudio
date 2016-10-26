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
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/openstudio/lib.php');

if ($ADMIN->fulltree) {
    $sitemaxbytes = isset($CFG->maxbytes) ? $CFG->maxbytes : OPENSTUDIO_DEFAULT_MAXBYTES;
    $settings->add(new admin_setting_configselect(
            'openstudio/maxbytes',
            get_string('configmaxattachmentsizename', 'openstudio'),
            get_string('configmaxattachmentsizedescription', 'openstudio'),
            OPENSTUDIO_DEFAULT_MAXBYTES,
            get_max_upload_sizes($sitemaxbytes)));

    $settings->add(new admin_setting_configselect(
            'openstudio/exportzipfilesize',
            get_string('configexportzipfilesize', 'openstudio'),
            get_string('configexportzipfilesizedescription', 'openstudio'),
            OPENSTUDIO_DEFAULT_MAXBYTES,
            get_max_upload_sizes($sitemaxbytes)));

    $settings->add(new admin_setting_configtext(
            'openstudio/streampagesize',
            get_string('configstreampagesize', 'openstudio'),
            get_string('configstreampagesizedescription', 'openstudio'),
            OPENSTUDIO_DEFAULT_STREAMPAGESIZE,
            PARAM_INT));

    $settings->add(new admin_setting_configtext(
            'openstudio/peoplepagesize',
            get_string('configpeoplepagesize', 'openstudio'),
            get_string('configpeoplepagesizedescription', 'openstudio'),
            OPENSTUDIO_DEFAULT_PEOPLEPAGESIZE,
            PARAM_INT));
}
