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
 * Web services for openstudio
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// We defined the web service functions to install.
$functions = array(
    'mod_openstudio_external_delete_version' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'delete_version',
        'description' => 'Delete content version',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:view'
    ),
    'mod_openstudio_external_flag_content' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'flag_content',
        'description' => 'Flag a content',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:view'
    ),
    'mod_openstudio_external_browse_posts' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'browse_posts',
        'description' => 'Browse posts',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:view'
    ),
    'mod_openstudio_external_subscribe' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'subscribe',
        'description' => 'Subscribe to my Studio',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:view'
    ),
    'mod_openstudio_external_unsubscribe' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'unsubscribe',
        'description' => 'Unsubscribe to my Studio',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:view'
    ),
    'mod_openstudio_external_delete_content' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'delete_content',
        'description' => 'Delete content',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:view'
    ),
    'mod_openstudio_external_add_comment' => array(
            'classname' => 'mod_openstudio_external',
            'methodname' => 'add_comment',
            'description' => 'Add new comment',
            'type' => 'write',
            'ajax' => true,
            'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
            'capabilities' => 'mod/openstudio:addcomment'
    ),
    'mod_openstudio_external_flag_comment' => array(
            'classname' => 'mod_openstudio_external',
            'methodname' => 'flag_comment',
            'description' => 'Flag comment',
            'type' => 'write',
            'ajax' => true,
            'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
            'capabilities' => 'mod/openstudio:view'
    ),
    'mod_openstudio_external_delete_comment' => array(
            'classname' => 'mod_openstudio_external',
            'methodname' => 'delete_comment',
            'description' => 'Delete comment',
            'type' => 'write',
            'ajax' => true,
            'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
            'capabilities' => 'mod/openstudio:addcomment'
    ),
    'mod_openstudio_external_lock' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'lock',
        'description' => 'Lock content',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:view'
    ),
    'mod_openstudio_external_unlock' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'unlock',
        'description' => 'Unlock content',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:view'
    ),
    'mod_openstudio_external_read_notifications' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'read_notifications',
        'description' => 'Mark notifications as read',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:view'
    ),
    'mod_openstudio_external_restore_content_in_folder' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'restore_content_in_folder',
        'description' => 'Restore deleted content in folder',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:viewdeleted'
    ),
    'mod_openstudio_external_fetch_deleted_posts_in_folder' => array(
        'classname' => 'mod_openstudio_external',
        'methodname' => 'fetch_deleted_posts_in_folder',
        'description' => 'Fetch deleted posts in folder and return html markup',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => 'mod/openstudio:viewdeleted'
    )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Open Studio service' => array(
        'functions' => array(
            'mod_openstudio_external_flag_content',
            'mod_openstudio_external_browse_posts',
            'mod_openstudio_external_subscribe',
            'mod_openstudio_external_unsubscribe',
            'mod_openstudio_external_delete_content',
            'mod_openstudio_external_add_comment',
            'mod_openstudio_external_flag_comment',
            'mod_openstudio_external_delete_comment',
            'mod_openstudio_external_lock',
            'mod_openstudio_external_unlock',
            'mod_openstudio_external_read_notifications',
            'mod_openstudio_external_restore_content_in_folder',
            'mod_openstudio_external_fetch_deleted_posts_in_folder'),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'openstudio_service',
    )
);
