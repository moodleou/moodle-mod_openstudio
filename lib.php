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

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot . '/mod/openstudio/constants.php');

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

    if (isset($studio->allowedfiletypes) && is_array($studio->allowedfiletypes)) {
        $studio->filetypes = implode(",", $studio->allowedfiletypes);
    }

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

    if (isset($studio->allowedfiletypes) && is_array($studio->allowedfiletypes)) {
        $studio->filetypes = implode(",", $studio->allowedfiletypes);
    }

    if (isset($studio->tutorrolesgroup) && is_array($studio->tutorrolesgroup)) {
        $tutorroles = array_keys(array_filter($studio->tutorrolesgroup));
        $studio->tutorroles = implode(",", $tutorroles);
    }

    if (isset($studio->enablelocking)) {
        $studio->locking = $studio->enablelocking;
    }

    $studio->themefeatures = studio_feature_settings($studio);

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

    require_once($CFG->dirroot . '/mod/openstudio/locallib.php');

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
                        JOIN {openstudio_contents} s ON s.id = sv.contentid AND s.studioid = ?)

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

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $openstudio the module instance record
 */
function openstudio_user_complete($course, $user, $mod, $openstudio) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in openstudio activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function openstudio_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link openstudio_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function openstudio_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link openstudio_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function openstudio_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function openstudio_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function openstudio_get_extra_capabilities() {
    return array();
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function openstudio_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for openstudio file areas
 *
 * @package mod_openstudio
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function openstudio_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

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
 */
function openstudio_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding openstudio nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the openstudio module instance
 * @param stdClass $course current course record
 * @param stdClass $module current openstudio instance record
 * @param cm_info $cm course module information
 */
function openstudio_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the openstudio settings
 *
 * This function is called when the context for the page is a openstudio module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $openstudionode openstudio administration node
 */
function openstudio_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $openstudionode=null) {
    // TODO Delete this function and its docblock, or implement it.
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
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Sets a filter function which can run on results (after they have been
 * obtained) to exclude unwanted ones or make other changes.
 *
 * The filter below does a permission tocheck to make sure the logged in user
 * does have permission to view a given content.
 *
 * @param object $result Search result to check whether it should be filtered out.
 * @param bool $includeinsetcontents Set to true to include contents in set in the search result.
 * @return bool Returns true (acccept) or false (reject).
 */
function openstudio_ousearch_filter_permission($result, $includeinsetcontents = false) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Update all documents for ousearch.
 *
 * @param bool $feedback If true, prints feedback as HTML list items
 * @param int $courseid If specified, restricts to particular courseid
 */
function openstudio_ousearch_update_all($feedback = true, $courseid = 0) {
    // TODO Delete this function and its docblock, or implement it.
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

    require_once($CFG->dirroot . '/mod/openstudio/api/internal/levels.php');

    if (is_object($studioorid)) {
        $studio = $studioorid;
    } else {
        $studio = $DB->get_record('openstudio', array('id' => $studioorid));
        $module = $DB->get_record('modules', array('name' => 'openstudio'));
        $studiomodule = $DB->get_record('course_modules',
            array('course' => $studio->course, 'module' => $module->id, 'instance' => $studioorid));
        $studio->enablemodule = $studio->themefeatures & STUDIO_FEATURE_MODULE;
        $studio->groupmode = $studiomodule->groupmode;
        $studio->groupingid = $studiomodule->groupingid;
        $studio->pinboard = $studio->themefeatures & STUDIO_FEATURE_PINBOARD;
        $studio->enablecontenthtml = $studio->themefeatures & STUDIO_FEATURE_SLOTTEXTUSESHTML;
        $studio->enablecontentcommenthtml = $studio->themefeatures & STUDIO_FEATURE_SLOTCOMMENTUSESHTML;
        $studio->enablecontentcommentaudio = $studio->themefeatures & STUDIO_FEATURE_SLOTCOMMENTUSESAUDIO;
        $studio->enablecontentusesfileupload = $studio->themefeatures & STUDIO_FEATURE_SLOTUSESFILEUPLOAD;
        if ($studio->themefeatures & STUDIO_FEATURE_ENABLECOLLECTIONS) {
            $studio->enablefolders = 2;
        } else if ($studio->themefeatures & STUDIO_FEATURE_ENABLESETS) {
            $studio->enablefolders = 1;
        } else {
            $studio->enablefolders = 0;
        }
        $studio->enablefoldersanycontent = $studio->themefeatures & STUDIO_FEATURE_ENABLESETSANYSLOT;
        $studio->enablerss = $studio->themefeatures & STUDIO_FEATURE_ENABLERSS;
        $studio->enablesubscription = $studio->themefeatures & STUDIO_FEATURE_ENABLESUBSCRIPTION;
        $studio->enableexportimport = $studio->themefeatures & STUDIO_FEATURE_ENABLEEXPORTIMPORT;
        $studio->enablecontentusesweblink = $studio->themefeatures & STUDIO_FEATURE_SLOTUSESWEBLINK;
        $studio->enablecontentusesembedcode = $studio->themefeatures & STUDIO_FEATURE_SLOTUSESEMBEDCODE;
        $studio->enablecontentallownotebooks = $studio->themefeatures & STUDIO_FEATURE_SLOTALLOWNOTEBOOKS;
        $studio->enablecontentreciprocalaccess = $studio->themefeatures & STUDIO_FEATURE_SLOTRECIPROCALACCESS;
        $studio->enableparticipationsmiley = $studio->themefeatures & STUDIO_FEATURE_PARTICIPATIONSMILEY;
        $studio->enablelocking = $studio->themefeatures & STUDIO_FEATURE_ENABLELOCK;
    }

    $featuremodule = ($studio->enablemodule > 0) ? STUDIO_FEATURE_MODULE : 0;
    $featuregroup = 0;
    if ($studio->groupmode && $studio->groupingid) {
        $featuregroup = STUDIO_FEATURE_GROUP;
    }
    $featurestudio = 0;
    if (isset($studio->id) && studio_api_levels_is_defined($studio->id)) {
        $featurestudio = STUDIO_FEATURE_STUDIO;
    }
    $featurepinboard = 0;
    if ($studio->pinboard) {
        $featurepinboard = STUDIO_FEATURE_PINBOARD;
    }
    $featurecontenttextuseshtml = 0;
    if ($studio->enablecontenthtml) {
        $featurecontenttextuseshtml = STUDIO_FEATURE_SLOTTEXTUSESHTML;
    }
    $featurecontentcommentuseshtml = 0;
    if ($studio->enablecontentcommenthtml) {
        $featurecontentcommentuseshtml = STUDIO_FEATURE_SLOTCOMMENTUSESHTML;
    }
    $featurecontentcommentusesaudio = 0;
    if ($studio->enablecontentcommentaudio) {
        $featurecontentcommentusesaudio = STUDIO_FEATURE_SLOTCOMMENTUSESAUDIO;
    }
    $featurecontentusesfileupload = 0;
    if ($studio->enablecontentusesfileupload) {
        $featurecontentusesfileupload = STUDIO_FEATURE_SLOTUSESFILEUPLOAD;
    }
    $featureenablecollections = 0;
    if ($studio->enablefolders == 2) {
        $featureenablecollections = STUDIO_FEATURE_ENABLECOLLECTIONS;
    }
    $featureenablefolders = 0;
    $featureenablefoldersanycontent = 0;
    if ($studio->enablefolders == 1) {
        $featureenablefolders = STUDIO_FEATURE_ENABLESETS;
        if ($studio->enablefoldersanycontent) {
            $featureenablefoldersanycontent = STUDIO_FEATURE_ENABLESETSANYSLOT;
        }
    } else {
        if ($studio->themefeatures & STUDIO_FEATURE_ENABLECOLLECTIONS) {
            $featureenablecollections = STUDIO_FEATURE_ENABLECOLLECTIONS;
        }
    }
    $featureenablerss = 0;
    if ($studio->enablerss) {
        $featureenablerss = STUDIO_FEATURE_ENABLERSS;
    }
    $featureenablesubscription = 0;
    if ($studio->enablesubscription) {
        $featureenablesubscription = STUDIO_FEATURE_ENABLESUBSCRIPTION;
    }
    $featureenableexportimport = 0;
    if ($studio->enableexportimport) {
        $featureenableexportimport = STUDIO_FEATURE_ENABLEEXPORTIMPORT;
    }
    $featurecontentusesweblink = 0;
    if ($studio->enablecontentusesweblink) {
        $featurecontentusesweblink = STUDIO_FEATURE_SLOTUSESWEBLINK;
    }
    $featurecontentusesembedcode = 0;
    if ($studio->enablecontentusesembedcode) {
        $featurecontentusesembedcode = STUDIO_FEATURE_SLOTUSESEMBEDCODE;
    }
    $featurecontentallownotebooks = 0;
    if ($studio->enablecontentallownotebooks) {
        $featurecontentallownotebooks = STUDIO_FEATURE_SLOTALLOWNOTEBOOKS;
    }
    $featurecontentreciprocalaccess = 0;
    if ($studio->enablecontentreciprocalaccess) {
        $featurecontentreciprocalaccess = STUDIO_FEATURE_SLOTRECIPROCALACCESS;
    }

    $featureparticipationsmiley = 0;
    if ($studio->enableparticipationsmiley) {
        $featureparticipationsmiley = STUDIO_FEATURE_PARTICIPATIONSMILEY;
    }

    $featureenablelock = 0;
    if ($studio->enablelocking) {
        $featureenablelock = STUDIO_FEATURE_ENABLELOCK;
    }

    $themefeatures = $featuremodule + $featuregroup + $featurestudio + $featurepinboard +
        $featurecontenttextuseshtml + $featurecontentcommentuseshtml +
        $featurecontentcommentusesaudio + $featurecontentusesfileupload +
        $featureenablecollections + $featureenablefolders +
        $featureenablerss + $featureenablesubscription + $featureenableexportimport +
        $featurecontentusesweblink + $featurecontentusesembedcode + $featurecontentallownotebooks +
        $featurecontentreciprocalaccess + $featureenablelock + $featureenablefoldersanycontent +
        $featureparticipationsmiley;

    if (isset($studio->id) && $updatedb) {
        $DB->set_field('openstudio', 'themefeatures', $themefeatures, array('id' => $studio->id));
    }

    return $themefeatures;
}


