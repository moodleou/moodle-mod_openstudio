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

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

/*
 * Function to check if a user is following a slot.
 *
 * @param int $slotid The id of slot to check if the user is following.
 * @param int $userid The id of the user who may or may not be following the slot.
 * @return bool Returns true if user is following slot.
 */
function studio_api_follow_is_following_slot($slotid, $userid) {
    global $DB;

    return $DB->record_exists('studio_flags',
            array('contentid' => $slotid, 'userid' => $userid, 'flagid' => STUDIO_PARTICPATION_FLAG_FOLLOW_SLOT));

    return false;
}

/*
 * Function to check if a user is following another user.
 *
 * @param int $personid The id of person to check if the user is following.
 * @param int $userid The id of the user who may or may not be following the person.
 * @return bool Returns true if user is following person.
 */
function studio_api_follow_is_following_user($personid, $userid) {
    global $DB;

    return $DB->record_exists('studio_flags',
            array('personid' => $personid, 'userid' => $userid, 'flagid' => STUDIO_PARTICPATION_FLAG_FOLLOW_USER));

    return false;
}

/*
 * Function to record a user tracking (following) a slot.
 * This is a helper function whcih basically calls the flags api
 * to do the actual work.
 *
 * @param int $slotid The id of the slot to follow.
 * @param int $userid The id of the user who is following the slot.
 * @param bool $follow True to follow or false to unfollow slot.
 * @param bool $returnstudioflagid Set to true to return the flag record id.
 * @return bool|int Returns true/false or flag record id.
 */
function studio_api_follow_slot($slotid, $userid, $follow = true, $returnstudioflagid = false) {
    $toggle = $follow ? 'on' : 'off';

    return studio_api_flags_toggle(
            $slotid, STUDIO_PARTICPATION_FLAG_FOLLOW_SLOT, $toggle, $userid, null, $returnstudioflagid);
}

/*
 * Function to record a user following another user.
 * This is a helper functin which basically calls the flags api
 * to do the actual work.
 *
 * @param int $personid The id of the person the user will be following.
 * @param int $userid The id of the user who is following the person.
 * @param bool $follow True to follow or false to unfollow slot.
 * @param bool $returnstudioflagid Set to true to return the flag record id.
 * @return bool|int Returns true/false or flag record id.
 */
function studio_api_follow_user($personid, $userid, $follow = true, $returnstudioflagid = false) {
    $toggle = $follow ? 'on' : 'off';

    return studio_api_flags_user_toggle(
            $personid, STUDIO_PARTICPATION_FLAG_FOLLOW_USER, $toggle, $userid, $returnstudioflagid);
}
