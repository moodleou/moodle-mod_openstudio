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
 * Library of interface functions and constants for module openstudio
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the openstudio specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_openstudio\local\api\content;

defined('MOODLE_INTERNAL') || die();

/* Moodle API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function openstudio_supports($feature) {

    switch ($feature) {
        case FEATURE_GROUPS:
            return true;

        case FEATURE_IDNUMBER:
            return true;

        case FEATURE_GROUPINGS:
            return true;

        case FEATURE_MOD_INTRO:
            return true;

        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;

        case FEATURE_COMPLETION_HAS_RULES:
            return false;

        case FEATURE_GRADE_HAS_GRADE:
            return false;

        case FEATURE_GRADE_OUTCOMES:
            return false;

        case FEATURE_RATE:
            return false;

        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            return null;
    }
}

/**
 * Saves a new instance of the openstudio into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $openstudio Submitted data from the form in mod_form.php
 * @param mod_openstudio_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted openstudio record
 */
function openstudio_add_instance(stdClass $studio, mod_openstudio_mod_form $mform = null) {
    global $DB;

    $studio->timemodified = time();

    if (isset($studio->enabledvisibility) && is_array($studio->enabledvisibility)) {
        $studio->allowedvisibility = implode(",", $studio->enabledvisibility);
    }

    if (isset($studio->enabledflags) && is_array($studio->enabledflags)) {
        $studio->flags = implode(",", $studio->enabledflags);
    }

    $studio->filetypes = '*';

    if (isset($studio->tutorrolesgroup) && is_array($studio->tutorrolesgroup)) {
        $tutorroles = array_keys(array_filter($studio->tutorrolesgroup));
        $studio->tutorroles = implode(",", $tutorroles);
    }

    if (isset($studio->enablelocking)) {
        $studio->locking = $studio->enablelocking;
    }

    $studio->themefeatures = openstudio_feature_settings($studio);

    return $DB->insert_record('openstudio', $studio);
}

/**
 * Updates an instance of the openstudio in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $openstudio An object from the form in mod_form.php
 * @param mod_openstudio_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function openstudio_update_instance(stdClass $studio, mod_openstudio_mod_form $mform = null) {
    global $DB;

    $studio->timemodified = time();
    $studio->id = $studio->instance;

    if (isset($studio->enabledvisibility) && is_array($studio->enabledvisibility)) {
        $studio->allowedvisibility = implode(",", $studio->enabledvisibility);
    }

    if (isset($studio->enabledflags) && is_array($studio->enabledflags)) {
        $studio->flags = implode(",", $studio->enabledflags);
    }

    $studio->filetypes = '*';

    if (isset($studio->tutorrolesgroup) && is_array($studio->tutorrolesgroup)) {
        $tutorroles = array_keys(array_filter($studio->tutorrolesgroup));
        $studio->tutorroles = implode(",", $tutorroles);
    }

    if (isset($studio->enablelocking)) {
        $studio->locking = $studio->enablelocking;
    }

    $studio->themefeatures = openstudio_feature_settings($studio);

    return $DB->update_record('openstudio', $studio);
}

/**
 * Removes an instance of the openstudio from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function openstudio_delete_instance($id) {
    global $CFG, $DB;

    if (! $studio = $DB->get_record('openstudio', array('id' => $id))) {
        return false;
    }

    $result = true;

    require_once(__DIR__ . '/api/filesystem.php');

    $cm = get_coursemodule_from_id('openstudio', $id);
    if ($cm) {
        // Delete search indexes.
        if (studio_search_installed()) {
            local_ousearch_document::delete_module_instance_data($cm);
        }

        // Delete content files from moodle file system.
        $modulecontext = context_module::instance($cm->id);
        if ($modulecontext) {
            studio_api_filesystem_remove_content_files_from_moodlefs($modulecontext->id, $studio->id, false);
        }
    }

    $sql = <<<EOF
DELETE FROM {openstudio_level3} l3
      WHERE l3.level2id IN (SELECT l2.id
                              FROM {openstudio_level2} l2
                              JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.openstudioid = ?)

EOF;
    if (! $DB->execute($sql, array($studio->id))) {
        return false;
    }

    $sql = <<<EOF
DELETE FROM {openstudio_level2} l2
      WHERE l2.level1id IN (SELECT l1.id
                              FROM {openstudio_level1} l1
                             WHERE l1.openstudioid = ?)

EOF;
    if (! $DB->execute($sql, array($studio->id))) {
        return false;
    }

    if (! $DB->delete_records('openstudio_level1', array('openstudioid' => $studio->id))) {
        return false;
    }

    $sql = <<<EOF
DELETE FROM {openstudio_comments} sc
      WHERE sc.contentid IN (SELECT s.id
                            FROM {openstudio_contents} s
                           WHERE s.openstudioid = ?)

EOF;
    if (! $DB->execute($sql, array($studio->id))) {
        return false;
    }

    $sql = <<<EOF
DELETE FROM {openstudio_flags} sf
      WHERE sf.contentid IN (SELECT s.id
                            FROM {openstudio_contents} s
                           WHERE s.openstudioid = ?)

EOF;
    if (! $DB->execute($sql, array($studio->id))) {
        return false;
    }

    if (! $DB->delete_records('openstudio_honesty_checks', array('openstudioid' => $studio->id))) {
        return false;
    }

    if (! $DB->delete_records('openstudio_subscriptions', array('openstudioid' => $studio->id))) {
        return false;
    }

    $sql = <<<EOF
DELETE FROM {openstudio_tracking} st
      WHERE st.contentid IN (SELECT s.id
                            FROM {openstudio_contents} s
                           WHERE s.openstudioid = ?)

EOF;
    if (! $DB->execute($sql, array($studio->id))) {
        return false;
    }

    $sql = <<<EOF
DELETE FROM {openstudio_content_files} sf
      WHERE sf.id IN (SELECT s.fileid
                        FROM {openstudio_contents} s
                       WHERE s.openstudioid = ?)
         OR sf.id IN (SELECT sv.fileid
                        FROM {openstudio_content_versions} sv
                        JOIN {openstudio_contents} s ON s.id = sv.contentid AND s.openstudioid = ?)

EOF;
    if (! $DB->execute($sql, array($studio->id, $studio->id))) {
        return false;
    }

    $sql = <<<EOF
DELETE FROM {openstudio_content_versions} sv
      WHERE sv.contentid IN (SELECT s.id
                            FROM {openstudio_contents} s
                           WHERE s.openstudioid = ?)

EOF;
    if (! $DB->execute($sql, array($studio->id))) {
        return false;
    }

    if (! $DB->delete_records('openstudio_contents', array('openstudioid' => $studio->id))) {
        return false;
    }

    if (! $DB->delete_records('openstudio', array('id' => $studio->id))) {
        return false;
    }

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $openstudio The openstudio instance record
 * @return stdClass|null
 */
function openstudio_user_outline($course, $user, $mod, $openstudio) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/* File API */

/**
 * Serves the files from the openstudio file areas
 *
 * @package mod_openstudio
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the openstudio's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return false on failure
 */
function openstudio_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $USER, $DB;
    $extractfile = null;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if (! in_array($filearea,
            array('content', 'contentthumbnail', 'contentversion', 'contentthumbnailversion',
                    'contentcomment', 'notebook', 'notebookversion'))) {
        return false;
    }

    require_login($course, false, $cm);
    $itemid = (int) array_shift($args);
    if (($filearea === 'content') || ($filearea === 'contentthumbnail') || ($filearea == 'notebook')) {
        $record = $contentdata = $DB->get_record('openstudio_contents', array('id' => $itemid), '*', MUST_EXIST);
    } else if ($filearea === 'contentcomment') {
        $sql = <<<EOF
SELECT s.*
  FROM {openstudio_contents} s
  JOIN {openstudio_comments} sc ON sc.contentid = s.id
 WHERE sc.id = ?

EOF;
        $record = $contentdata = $DB->get_record_sql($sql, array($itemid));
    } else {
        $record = $DB->get_record('openstudio_content_versions', array('id' => $itemid), '*', MUST_EXIST);
        $contentdata = $DB->get_record('openstudio_contents', array('id' => $record->contentid), '*', MUST_EXIST);
        if ($filearea == 'contentversion') {
            $filearea = 'content';
        }
        if ($filearea == 'contentthumbnailversion') {
            $filearea = 'contentthumbnail';
        }
        if ($filearea == 'notebookversion') {
            $filearea = 'notebook';
        }
    }
    $visibility = $contentdata->visibility;
    $folderid = array_pop($args);
    if (is_numeric($folderid)) {
        if ($DB->record_exists('openstudio_folder_contents', array('folderid' => $folderid, 'contentid' => $contentdata->id))) {
            if ($folder = $DB->get_record('openstudio_contents', array('id' => $folderid))) {
                $visibility = $folder->visibility;
            }
        }
    } else {
        array_push($args, $folderid);
    }

    // Permission check.
    $modulecontext = context_module::instance($cm->id);
    if (!has_capability('mod/openstudio:managecontent', $modulecontext)) {
        if ($contentdata->userid == $USER->id) {
            if (!has_capability('mod/openstudio:view', $modulecontext)) {
                return false;
            }
        } else {
            if (!has_capability('mod/openstudio:viewothers', $modulecontext)) {
                return false;
            }

            // If the content is folder to private, then user cant see it.
            if ($visibility == content::VISIBILITY_PRIVATE) {
                return false;
            }

            // If the content is shared with the course users, then proceed.
            if ($visibility == content::VISIBILITY_PRIVATE) {
                $sql = <<<EOF
SELECT ue1.id
  FROM {user_enrolments} ue1
  JOIN {enrol} e1 ON e1.id = ue1.enrolid
 WHERE ue1.userid = ?
   AND e1.courseid IN (SELECT e2.courseid
                         FROM {enrol} e2
                         JOIN {user_enrolments} ue2 ON ue2.enrolid = e2.id AND ue2.userid = ?
                        WHERE e2.courseid = ?)

EOF;
                if (!$DB->record_exists_sql($sql, array($USER->id, $contentdata->userid, $course->id))) {
                    return false;
                }
            }

            if (($visibility == content::VISIBILITY_GROUP) || ($visibility < 0)) {
                $coursecontext = context_course::instance($course->id);
                $canaccessallgroups = has_capability('moodle/site:accessallgroups', $coursecontext);

                if (!$canaccessallgroups) {
                    if (($cm->groupmode == 2) || $visibility == content::VISIBILITY_GROUP) {
                        // If the content is shared with the user's course group,
                        // then check group membership to decide whether to proceed.
                        //
                        // If groupmode is all visible groups, then the check is as long as they
                        // are within the same group grouping id as the content owner.

                        $sql = <<<EOF
SELECT DISTINCT gm1.groupid
  FROM {groups_members} gm1
  JOIN {groupings_groups} gg ON gg.groupid = gm1.groupid
  JOIN {course_modules} cm ON cm.groupingid = gg.groupingid
  JOIN {openstudio_contents} s ON s.openstudioid = cm.instance
 WHERE gm1.userid = ?
   AND s.id = ?

EOF;

                        if (!$DB->record_exists_sql($sql, array($USER->id, $contentdata->id))) {
                            return false;
                        }
                    } else if ($visibility < 0) {
                        // If the content is shared with a specific group,
                        // then check group membership to decide whether to proceed.

                        $groupid = 0 - $visibility;
                        $sql = <<<EOF
SELECT DISTINCT gm1.groupid
  FROM {groups_members} gm1
  JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ?
 WHERE gm1.groupid = ?
   AND gm1.userid = ?

EOF;

                        if (!$DB->record_exists_sql($sql, array($USER->id, $groupid, $contentdata->userid))) {
                            return false;
                        }
                    }
                }
            }
        }
    }

    if ($filearea === 'contentcomment') {
        $relativepath = implode('/', $args);
        $fullpath = "/{$context->id}/mod_openstudio/$filearea/{$itemid}/$relativepath";
    } else {
        switch ($record->contenttype) {
            case content::TYPE_IMAGE:
                $sizecheck = optional_param('sizecheck', 0, PARAM_INT);
                if ($sizecheck == 1) {
                    /*
                     * Request has been made to check the image file size
                     * and if it is greater than STUDIO_DEFAULT_SLOTVIEWIMAGESIZELIMIT
                     * then we force the use of thumbnail size image.
                     *
                     */
                    $filerecord = $DB->get_record('files',
                            array('component' => 'mod_openstudio',
                                    'filearea' => 'content',
                                    'itemid' => $record->fileid,
                                    'filename' => $record->content),
                            'filesize', MUST_EXIST);
                    if ($filerecord->filesize > mod_openstudio\local\util\defaults::CONTENTVIEWIMAGESIZELIMIT) {
                        $filearea = 'contentthumbnail';
                    }
                }
                break;

            case content::TYPE_VIDEO:
            case content::TYPE_AUDIO:
            case content::TYPE_DOCUMENT:
                if ($filearea == 'notebook') {
                    $filename = 'openstudio_' . $record->id . '_notebook.' . pathinfo(implode('/', $args), PATHINFO_EXTENSION);
                    $options['filename'] = $filename;
                }
            case content::TYPE_PRESENTATION:
            case content::TYPE_SPREADSHEET:
                break;

            default:
                return false;
        }

        $relativepath = implode('/', $args);
        $fullpath = "/{$context->id}/mod_openstudio/$filearea/{$record->fileid}/$relativepath";
    }

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, (bool) $forcedownload, $options);
}

/**
 * Obtains a search document given the ousearch parameters.
 *
 * @param object $document Object containing fields from the ousearch documents table
 * @return mixed False if object can't be found, otherwise object containing the following
 *   fields: ->content, ->title, ->url, ->activityname, ->activityurl,
 *   and optionally ->extrastrings array and ->data
 */
function openstudio_ousearch_get_document($document) {
    global $CFG, $USER;

    $slotid = $document->intref1;
    $slot = mod_openstudio\local\api\content::get_record($USER->id, $slotid);
    if ($slot == false) {
        return false;
    }

    // Note: for quoted search term to work, the content fed to the indexered
    // must be the same when the search engine calls this function.  So the below
    // construction of title and name is the same as found in the search->update()
    // function call.
    //
    // This is becase when quoted search term is used, the search index is not enough
    // and so it calls upon the original text string below to match the quoted text term order.
    //
    // Various slot metadata is concatenated into the search document content field
    // so it can be found in a search text query.

    // Set search document title.
    $title = stripslashes($slot->name);

    $content  = $slot->l1name . ' . ';
    $content .= $slot->l2name . ' . ';
    $content .= $slot->l3name . ' . ';
    $content .= $slot->name . ' . ';
    $content .= $slot->description . ' . ';
    $content .= $slot->firstname . ' . ';
    $content .= $slot->lastname . ' . ';
    $content .= $slot->username . ' . ';
    $content .= $slot->urltitle . ' . ';
    $content .= $slot->imagealt . ' . ';
    $content .= $slot->thumbnail . ' . ';
    $content .= $slot->mimetype . ' . ';
    $content .= $slot->content . ' . ';

    // Add in tags data.
    foreach ($slot->tagsraw as $slottag) {
        // Store tag as is, so it can be found.
        $content .= $slottag->name . ' . ';

        // Store tag with prefix as is, so it can be found exactly if required.
        $content .= 'tag:' . str_replace(' ', '', $slottag->name) . ' . ';
    }

    // Add in comments.
    // To prevenr performance issue, we will only index the latest 25 comments.
    $commentsdata = studio_api_comments_get_all($slot->id, null, null, 25);
    if ($commentsdata != false) {
        $content .= ' . ';
        foreach ($commentsdata as $comment) {
            $content .= ' . ' . $comment->commenttext;
        }
    }

    $content = stripslashes($content);

    $result = new stdClass();
    $result->title = $title;
    $result->content = $content;
    $result->url  = (new moodle_url('/mod/openstudio/content.php',
                    array('id' => $document->coursemoduleid, 'sid' => $slot->id))) . '';
    $result->activityname = $result->title;
    $result->activityurl = $result->url;

    return $result;
}

/**
 * This is a helper function to call studio_ousearch_filter_permission() exlictly
 * requesting slots in sets to be included in search result.
 *
 * @param object $result Search result to check whether it should be filtered out.
 * @return bool Returns true (acccept) or false (reject).
 */
function openstudio_ousearch_filter_permission_include_setslots($result) {
    return openstudio_ousearch_filter_permission($result, true);
}

/**
 * Sets a filter function which can run on results (after they have been
 * obtained) to exclude unwanted ones or make other changes.
 *
 * The filter below does a permission tocheck to make sure the logged in user
 * does have permission to view a given slot.
 *
 * @param object $result Search result to check whether it should be filtered out.
 * @param bool $includeinsetslots Set to true to include slots in set in the search result.
 * @return bool Returns true (acccept) or false (reject).
 */
function openstudio_ousearch_filter_permission($result, $includeinsetslots = false) {
    global $USER, $COURSE, $DB;

    // Check view context to filter by.
    $vid = content::VISIBILITY_MODULE;
    if (mod_openstudio\local\util::cache_check('search_view_context')) {
        $vid = mod_openstudio\local\util::cache_get('search_view_context');
    }

    // Check groupmode to filter by.
    $groupmode = 0;
    if (mod_openstudio\local\util::cache_check('search_view_groupmode')) {
        $groupmode = mod_openstudio\local\util::cache_get('search_view_groupmode');
    }

    // Check capability to see I can view other people's slots.
    if (mod_openstudio\local\util::cache_check('search_viewothers_capability')) {
        if (!mod_openstudio\local\util::cache_get('search_viewothers_capability')) {
            return false;
        }
    }

    if ($vid == content::VISIBILITY_PRIVATE_PINBOARD) {
        $sql = <<<EOF
SELECT DISTINCT s.id
  FROM {openstudio_contents} s
 WHERE s.id = ?
   AND s.userid = ?
   AND s.levelid = 0
   AND s.levelcontainer = 0

EOF;

        if ($includeinsetslots) {
            return $DB->record_exists_sql($sql, array($result->intref1, $USER->id));
        } else {
            $sql .= ' AND s.visibility != ?';
            return $DB->record_exists_sql($sql, array($result->intref1, $USER->id, content::VISIBILITY_INFOLDERONLY));
        }
    }

    if ($vid == content::VISIBILITY_PRIVATE) {
        $sql = <<<EOF
SELECT DISTINCT s.id
  FROM {openstudio_contents} s
 WHERE s.id = ?
   AND s.userid = ?
   AND s.levelid > 0
   AND s.levelcontainer > 0

EOF;

        if ($includeinsetslots) {
            return $DB->record_exists_sql($sql, array($result->intref1, $USER->id));
        } else {
            $sql .= ' AND s.visibility != ?';
            return $DB->record_exists_sql($sql, array($result->intref1, $USER->id, content::VISIBILITY_INFOLDERONLY));
        }
    }

    // If the slot is shared with the user's course group,
    // then check group membership to decide whether to show search result.
    if (($result->intref2 == content::VISIBILITY_GROUP) || ($result->intref2 < 0)) {
        $coursecontext = context_course::instance($COURSE->id);
        $canaccessallgroups = has_capability('moodle/site:accessallgroups', $coursecontext);
        if ($canaccessallgroups) {
            return true;
        }

        if (($groupmode == 2) || ($result->intref2 == content::VISIBILITY_GROUP)) {
            $sql = <<<EOF
SELECT DISTINCT gm1.groupid
  FROM {groups_members} gm1
  JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ?
  JOIN {groupings_groups} gg ON gg.groupid = gm2.groupid
  JOIN {course_modules} cm ON cm.groupingid = gg.groupingid
  JOIN {openstudio_contents} s ON s.studioid = cm.instance
 WHERE gm1.userid = ?
   AND s.id = ?

EOF;

            if ($includeinsetslots) {
                return $DB->record_exists_sql($sql, array($result->userid, $USER->id, $result->intref1));
            } else {
                $sql .= ' AND s.visibility != ?';
                return $DB->record_exists_sql($sql, array(
                        $result->userid, $USER->id, $result->intref1, content::VISIBILITY_INFOLDERONLY));
            }
        } else {
            if (!$includeinsetslots) {
                $slotdata = studio_api_slot_get($result->intref1);
                if ($slotdata->visibility == content::VISIBILITY_INFOLDERONLY) {
                    return false;
                }
            }

            $groupid = 0 - $result->intref2;
            $sql = <<<EOF
SELECT DISTINCT gm1.groupid
  FROM {groups_members} gm1
  JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ?
 WHERE gm1.groupid = ?
   AND gm1.userid = ?

EOF;
            return $DB->record_exists_sql($sql, array($USER->id, $groupid, $result->userid));
        }
    }

    if (!$includeinsetslots) {
        $slotdata = mod_openstudio\local\api\content::get($result->intref1);
        if ($slotdata->visibility == content::VISIBILITY_INFOLDERONLY) {
            return false;
        }
    }

    // If the slot belongs to me, then show search result.
    if ($result->userid == $USER->id) {
        return true;
    }

    // If the slot is shared with the course users, then show search result.
    // Note: assumption is that the search results are already retricted by
    // course module.
    if ($result->intref2 == content::VISIBILITY_MODULE) {
        $sql = <<<EOF
SELECT ue1.id
  FROM {user_enrolments} ue1
  JOIN {enrol} e1 ON e1.id = ue1.enrolid
 WHERE ue1.userid = ?
   AND e1.courseid IN (SELECT e2.courseid
                         FROM {enrol} e2
                         JOIN {user_enrolments} ue2 ON ue2.enrolid = e2.id AND ue2.userid = ?
                        WHERE e2.courseid = ?)

EOF;

        if ($vid == content::VISIBILITY_GROUP) {
            $sql .= <<<EOF
   AND EXISTS (SELECT DISTINCT gm1.groupid
                 FROM {groups_members} gm1
                 JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ?
                 JOIN {groupings_groups} gg ON gg.groupid = gm2.groupid
                 JOIN {course_modules} cm ON cm.groupingid = gg.groupingid
                 JOIN {openstudio_contents} s ON s.studioid = cm.instance
                WHERE gm1.userid = ?
                  AND s.id = ?)

EOF;

            return $DB->record_exists_sql($sql,
                    array($USER->id, $result->userid, $COURSE->id, $result->userid, $USER->id, $result->intref1));
        } else {
            return $DB->record_exists_sql($sql, array($USER->id, $result->userid, $COURSE->id));
        }
    }

    if ($result->intref2 == content::VISIBILITY_TUTOR) {
        $cm = get_coursemodule_from_id('studio', $result->coursemoduleid);
        $cminstance = $DB->get_record('studio', array('id' => $cm->instance), '*', MUST_EXIST);
        $tutorroles = array_filter(explode(',', $cminstance->tutorroles));
        return studio_api_slot_user_is_tutor($result->intref1, $USER->id, $tutorroles);
    }

    return false;
}

function openstudio_ousearch_filter_browseslots(&$result) {
    if (openstudio_ousearch_filter_permission($result, true)) {
        $result = mod_openstudio\local\api\content::get($result->intref1);
        if ($result->contenttype == content::TYPE_FOLDER) {
            return false;
        }
        return true;
    } else {
        return false;
    }
}

function openstudio_ousearch_filter_browseslots_useronly(&$result) {
    global $USER;
    if ($result->userid == $USER->id) {
        return openstudio_ousearch_filter_browseslots($result);
    } else {
        return false;
    }
}

/**
 * Update all documents for ousearch.
 *
 * @param bool $feedback If true, prints feedback as HTML list items
 * @param int $courseid If specified, restricts to particular courseid
 */
function openstudio_ousearch_update_all($feedback = true, $courseid = 0) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/studio/lib.php');

    raise_memory_limit(MEMORY_EXTRA);

    if ($courseid > 0) {
        $sql = <<<EOF
SELECT cm.*
  FROM {modules} m
  JOIN {course_modules} cm ON cm.module = m.id
 WHERE m.name = 'openstudio'
   AND cm.course = ?

EOF;

        $cm = $DB->get_record_sql($sql, array($courseid));
        if ($cm === false) {
            return false;
        }

        $slots = mod_openstudio\local\api\content::get_all_records($cm->instance);
        if ($slots != false) {
            $counter = 0;
            foreach ($slots as $slotdata) {
                $counter++;
                if ($slotdata->contenttype == content::TYPE_NONE) {
                    studio_api_search_delete($cm, $slotdata, true);
                } else {
                    studio_api_search_update($cm, $slotdata);
                }
            }

            if ($feedback) {
                print '<li>Studio instance: ' . $cm->instance
                        . '. No. of slots: ' . $counter . '</li>';
            }
        }
    } else {
        $sql = <<<EOF
SELECT cm.*
  FROM {modules} m
  JOIN {course_modules} cm ON cm.module = m.id
 WHERE m.name = 'openstudio'

EOF;

        $cms = $DB->get_recordset_sql($sql, array($courseid));
        if (!$cms->valid()) {
            return false;
        }

        foreach ($cms as $cm) {
            $slots = mod_openstudio\local\api\content::get_all_records($cm->instance);
            if ($slots != false) {
                $counter = 0;
                foreach ($slots as $slotdata) {
                    $counter++;
                    if ($slotdata->contenttype == content::TYPE_NONE) {
                        studio_api_search_delete($cm, $slotdata, true);
                    } else {
                        studio_api_search_update($cm, $slotdata);
                    }
                }

                if ($feedback) {
                    print '<li>Studio instance: ' . $cm->instance
                            . '. No. of slots: ' . $counter . '</li>';
                }
            }
        }
    }
}

/**
 * Returns the features enabled for the Studio instance.  Given this value changes and may not
 * be refelected in the database, the optional fflag $updatedb exists to save the calculated
 * feature to database.
 *
 * @param mixed $studioorid The studio object or id that needs to be checked for features.
 * @param bool $updatedb Set to true if the feature calculation should be stored in the database.
 * @return int Return a bitmask value of what features are enabled.
 */
function openstudio_feature_settings($studioorid, $updatedb = false) {
    global $CFG, $DB;

    if (is_object($studioorid)) {
        $studio = $studioorid;
    } else {
        $studio = $DB->get_record('openstudio', array('id' => $studioorid));
        $module = $DB->get_record('modules', array('name' => 'openstudio'));
        $studiomodule = $DB->get_record('course_modules',
            array('course' => $studio->course, 'module' => $module->id, 'instance' => $studioorid));
        $studio->enablemodule = $studio->themefeatures & \mod_openstudio\local\util\feature::MODULE;
        $studio->groupmode = $studiomodule->groupmode;
        $studio->groupingid = $studiomodule->groupingid;
        $studio->pinboard = $studio->themefeatures & \mod_openstudio\local\util\feature::PINBOARD;
        $studio->enablecontenthtml = 1;
        $studio->enablecontentcommenthtml = 1;
        $studio->enablecontentcommentaudio = $studio->themefeatures & \mod_openstudio\local\util\feature::CONTENTCOMMENTUSESAUDIO;
        $studio->enablecontentusesfileupload = $studio->themefeatures & \mod_openstudio\local\util\feature::CONTENTUSESFILEUPLOAD;
        if ($studio->themefeatures & \mod_openstudio\local\util\feature::ENABLEFOLDERS) {
            $studio->enablefolders = 1;
        } else {
            $studio->enablefolders = 0;
        }
        $studio->enablefoldersanycontent = $studio->themefeatures & \mod_openstudio\local\util\feature::ENABLEFOLDERSANYCONTENT;
        $studio->enablerss = $studio->themefeatures & \mod_openstudio\local\util\feature::ENABLERSS;
        $studio->enablesubscription = $studio->themefeatures & \mod_openstudio\local\util\feature::ENABLESUBSCRIPTION;
        $studio->enableexportimport = $studio->themefeatures & \mod_openstudio\local\util\feature::ENABLEEXPORTIMPORT;
        $studio->enablecontentusesweblink = $studio->themefeatures & \mod_openstudio\local\util\feature::CONTENTUSESWEBLINK;
        $studio->enablecontentusesembedcode = $studio->themefeatures & \mod_openstudio\local\util\feature::CONTENTUSESEMBEDCODE;
        $studio->enablecontentallownotebooks = $studio->themefeatures & \mod_openstudio\local\util\feature::CONTENTALLOWNOTEBOOKS;
        $studio->enablecontentreciprocalaccess = \mod_openstudio\local\util::has_feature(
                $studio, \mod_openstudio\local\util\feature::CONTENTRECIPROCALACCESS
        );
        $studio->enableparticipationsmiley = $studio->themefeatures & \mod_openstudio\local\util\feature::PARTICIPATIONSMILEY;
        $studio->enablelocking = $studio->themefeatures & \mod_openstudio\local\util\feature::ENABLELOCK;
    }

    $featuremodule = ($studio->enablemodule > 0) ? \mod_openstudio\local\util\feature::MODULE : 0;
    $featuregroup = 0;
    if ($studio->groupmode && $studio->groupingid) {
        $featuregroup = \mod_openstudio\local\util\feature::GROUP;
    }
    $featurestudio = 0;
    if (isset($studio->id) && mod_openstudio\local\api\levels::defined_for_studio($studio->id)) {
        $featurestudio = \mod_openstudio\local\util\feature::STUDIO;
    }
    $featurepinboard = 0;
    if ($studio->pinboard) {
        $featurepinboard = \mod_openstudio\local\util\feature::PINBOARD;
    }
    $featurecontenttextuseshtml = 1;
    $featurecontentcommentuseshtml = 1;
    $featurecontentcommentusesaudio = 0;
    if ($studio->enablecontentcommentaudio) {
        $featurecontentcommentusesaudio = \mod_openstudio\local\util\feature::CONTENTCOMMENTUSESAUDIO;
    }
    $featurecontentusesfileupload = 0;
    if ($studio->enablecontentusesfileupload) {
        $featurecontentusesfileupload = \mod_openstudio\local\util\feature::CONTENTUSESFILEUPLOAD;
    }
    $featureenablefolders = 0;
    $featureenablefoldersanycontent = 0;
    if ($studio->enablefolders == 1) {
        $featureenablefolders = \mod_openstudio\local\util\feature::ENABLEFOLDERS;
        if ($studio->enablefoldersanycontent) {
            $featureenablefoldersanycontent = \mod_openstudio\local\util\feature::ENABLEFOLDERSANYCONTENT;
        }
    }
    $featureenablerss = 0;
    if ($studio->enablerss) {
        $featureenablerss = \mod_openstudio\local\util\feature::ENABLERSS;
    }
    $featureenablesubscription = 0;
    if ($studio->enablesubscription) {
        $featureenablesubscription = \mod_openstudio\local\util\feature::ENABLESUBSCRIPTION;
    }
    $featureenableexportimport = 0;
    if ($studio->enableexportimport) {
        $featureenableexportimport = \mod_openstudio\local\util\feature::ENABLEEXPORTIMPORT;
    }
    $featurecontentusesweblink = 0;
    if ($studio->enablecontentusesweblink) {
        $featurecontentusesweblink = \mod_openstudio\local\util\feature::CONTENTUSESWEBLINK;
    }
    $featurecontentusesembedcode = 0;
    if ($studio->enablecontentusesembedcode) {
        $featurecontentusesembedcode = \mod_openstudio\local\util\feature::CONTENTUSESEMBEDCODE;
    }
    $featurecontentallownotebooks = 0;
    if ($studio->enablecontentallownotebooks) {
        $featurecontentallownotebooks = \mod_openstudio\local\util\feature::CONTENTALLOWNOTEBOOKS;
    }
    $featurecontentreciprocalaccess = 0;
    if ($studio->enablecontentreciprocalaccess) {
        $featurecontentreciprocalaccess = \mod_openstudio\local\util\feature::CONTENTRECIPROCALACCESS;
    }

    $featureparticipationsmiley = 0;
    if ($studio->enableparticipationsmiley) {
        $featureparticipationsmiley = \mod_openstudio\local\util\feature::PARTICIPATIONSMILEY;
    }

    $featureenablelock = 0;
    if ($studio->enablelocking) {
        $featureenablelock = \mod_openstudio\local\util\feature::ENABLELOCK;
    }

    $themefeatures = $featuremodule + $featuregroup + $featurestudio + $featurepinboard;
    $themefeatures += $featurecontenttextuseshtml + $featurecontentcommentuseshtml;
    $themefeatures += $featurecontentcommentusesaudio + $featurecontentusesfileupload + $featureenablefolders;
    $themefeatures += $featureenablerss + $featureenablesubscription + $featureenableexportimport;
    $themefeatures += $featurecontentusesweblink + $featurecontentusesembedcode + $featurecontentallownotebooks;
    $themefeatures += $featurecontentreciprocalaccess + $featureenablelock + $featureenablefoldersanycontent;
    $themefeatures += $featureparticipationsmiley;

    if (isset($studio->id) && $updatedb) {
        $DB->set_field('openstudio', 'themefeatures', $themefeatures, array('id' => $studio->id));
    }

    return $themefeatures;
}


