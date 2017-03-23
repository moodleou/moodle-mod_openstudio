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

use mod_openstudio\local\util\defaults;

if ($ADMIN->fulltree) {
    $sitemaxbytes = isset($CFG->maxbytes) ? $CFG->maxbytes : defaults::MAXBYTES;
    $settings->add(new admin_setting_configselect(
            'openstudio/maxbytes',
            get_string('configmaxattachmentsizename', 'openstudio'),
            get_string('configmaxattachmentsizedescription', 'openstudio'),
            defaults::MAXBYTES,
            get_max_upload_sizes($sitemaxbytes)));

    $settings->add(new admin_setting_configselect(
            'openstudio/exportzipfilesize',
            get_string('configexportzipfilesize', 'openstudio'),
            get_string('configexportzipfilesizedescription', 'openstudio'),
            defaults::MAXBYTES,
            get_max_upload_sizes($sitemaxbytes)));

    $settings->add(new admin_setting_configtext(
            'openstudio/streampagesize',
            get_string('configstreampagesize', 'openstudio'),
            get_string('configstreampagesizedescription', 'openstudio'),
            defaults::STREAMPAGESIZE,
            PARAM_INT));

    $settings->add(new admin_setting_configtext(
            'openstudio/peoplepagesize',
            get_string('configpeoplepagesize', 'openstudio'),
            get_string('configpeoplepagesizedescription', 'openstudio'),
            defaults::PEOPLEPAGESIZE,
            PARAM_INT));

    $settings->add(new admin_setting_configtext(
            'openstudio/notificationlimitmax',
            get_string('confignotificationlimitmax', 'openstudio'),
            get_string('confignotificationlimitmaxdescription', 'openstudio'),
            defaults::NOTIFICATIONLIMITMAX,
            PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
            'openstudio/notificationlimitread',
            get_string('confignotificationlimitread', 'openstudio'),
            get_string('confignotificationlimitreaddescription', 'openstudio'),
            defaults::NOTIFICATIONLIMITREAD,
            PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
            'openstudio/notificationlimitunread',
            get_string('confignotificationlimitunread', 'openstudio'),
            get_string('confignotificationlimitunreaddescription', 'openstudio'),
            defaults::NOTIFICATIONLIMITUNREAD,
            PARAM_INT
    ));
}
