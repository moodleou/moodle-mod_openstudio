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
 * Data provider.
 *
 * @package mod_openstudio
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\privacy;
defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use context;
use core_privacy\local\request\helper as request_helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\tracking;
use mod_openstudio\local\api\content;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\api\subscription;

/**
 * Data provider class.
 *
 * @package mod_openstudio
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        // The 'openstudio_contents' table stores the contents about each openstudio contents.
        $collection->add_database_table('openstudio_contents', [
                'userid' => 'privacy:metadata:openstudio_contents:userid',
                'openstudioid' => 'privacy:metadata:openstudio_contents:openstudioid',
                'content' => 'privacy:metadata:openstudio_contents:content',
                'thumbnail' => 'privacy:metadata:openstudio_contents:thumbnail',
                'name' => 'privacy:metadata:openstudio_contents:name',
                'description' => 'privacy:metadata:openstudio_contents:description',
                'deletedby' => 'privacy:metadata:openstudio_contents:deletedby',
                'deletedtime' => 'privacy:metadata:openstudio_contents:deletedtime',
                'timemodified' => 'privacy:metadata:openstudio_contents:timemodified',
                'timeflagged' => 'privacy:metadata:openstudio_contents:timeflagged',
                'lockedby' => 'privacy:metadata:openstudio_contents:lockedby',
                'locktype' => 'privacy:metadata:openstudio_contents:locktype',
                'lockedtime' => 'privacy:metadata:openstudio_contents:lockedtime',
                'lockprocessed' => 'privacy:metadata:openstudio_contents:lockprocessed',
        ], 'privacy:metadata:openstudio_contents');

        // The 'openstudio_flags' table, Flags assocaited with contents, people or comments.
        $collection->add_database_table('openstudio_flags', [
                'userid' => 'privacy:metadata:openstudio_flags:userid',
                'contentid' => 'privacy:metadata:openstudio_flags:contentid',
                'timemodified' => 'privacy:metadata:openstudio_flags:timemodified',
                'flagid' => 'privacy:metadata:openstudio_flags:flagid'
        ], 'privacy:metadata:openstudio_flags');

        // The 'openstudio_tracking' table, User usage of studio tracking table.
        $collection->add_database_table('openstudio_tracking', [
                'userid' => 'privacy:metadata:openstudio_tracking:userid',
                'contentid' => 'privacy:metadata:openstudio_tracking:contentid',
                'actionid' => 'privacy:metadata:openstudio_tracking:actionid',
                'timemodified' => 'privacy:metadata:openstudio_tracking:timemodified'
        ], 'privacy:metadata:openstudio_tracking');

        // The 'openstudio_comments' table, Content comments.
        $collection->add_database_table('openstudio_comments', [
                'userid' => 'privacy:metadata:openstudio_comments:userid',
                'commenttext' => 'privacy:metadata:openstudio_comments:commenttext',
                'deletedby' => 'privacy:metadata:openstudio_comments:deletedby',
                'deletedtime' => 'privacy:metadata:openstudio_comments:deletedtime',
                'timemodified' => 'privacy:metadata:openstudio_comments:timemodified',
        ], 'privacy:metadata:openstudio_comments');

        // The 'openstudio_subscriptions' table, Hold user email subscription request.
        $collection->add_database_table('openstudio_subscriptions', [
                'userid' => 'privacy:metadata:openstudio_subscriptions:userid',
                'subscription' => 'privacy:metadata:openstudio_subscriptions:subscription',
                'frequency' => 'privacy:metadata:openstudio_subscriptions:frequency',
                'timeprocessed' => 'privacy:metadata:openstudio_subscriptions:timeprocessed',
                'timemodified' => 'privacy:metadata:openstudio_subscriptions:timemodified',
        ], 'privacy:metadata:openstudio_subscriptions');

        // The 'openstudio_honesty_checks' table, Record to record user has accepted usage policy.
        $collection->add_database_table('openstudio_honesty_checks', [
                'userid' => 'privacy:metadata:openstudio_honesty_checks:userid',
                'openstudioid' => 'privacy:metadata:openstudio_honesty_checks:openstudioid',
                'timemodified' => 'privacy:metadata:openstudio_honesty_checks:timemodified',
        ], 'privacy:metadata:openstudio_honesty_checks');

        // The 'openstudio_notifications' table, Active notifications. These records are transient and are deleted,
        // after a short time.
        $collection->add_database_table('openstudio_notifications', [
                'userid' => 'privacy:metadata:openstudio_notifications:userid',
                'contentid' => 'privacy:metadata:openstudio_notifications:contentid',
                'commentid' => 'privacy:metadata:openstudio_notifications:commentid',
                'flagid' => 'privacy:metadata:openstudio_notifications:flagid',
                'message' => 'privacy:metadata:openstudio_notifications:message',
                'timecreated' => 'privacy:metadata:openstudio_notifications:timecreated',
                'timeread' => 'privacy:metadata:openstudio_notifications:timeread',
        ], 'privacy:metadata:openstudio_notifications');

        $collection->link_subsystem('core_files', 'privacy:metadata:core_files');
        $collection->link_subsystem('core_tag', 'privacy:metadata:core_tag');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Fetch all contents, and comments, notification, flags, tracking in contents of other user.
        $sql = 'SELECT DISTINCT ctx.id
                  FROM {modules} m
                  JOIN {course_modules} cm ON cm.module = m.id AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                  JOIN {openstudio} o ON cm.instance = o.id
             LEFT JOIN {openstudio_honesty_checks} ohc ON ohc.openstudioid = cm.id AND ohc.userid = :userid1
             LEFT JOIN {openstudio_subscriptions} os ON os.openstudioid = o.id AND os.userid = :userid2
             LEFT JOIN {openstudio_contents} oc ON oc.openstudioid = o.id
             LEFT JOIN {openstudio_tracking} ot ON ot.contentid = oc.id AND ot.userid = :userid3
             LEFT JOIN {openstudio_notifications} ono ON ono.contentid = oc.id AND ono.userid = :userid4
             LEFT JOIN {openstudio_comments} ocm ON ocm.contentid = oc.id AND ocm.userid = :userid5
             LEFT JOIN {openstudio_flags} ofs ON ofs.contentid = oc.id AND ofs.commentid = ocm.id AND ofs.userid = :userid6
                 WHERE (
                       ohc.id IS NOT NULL OR
                       os.id IS NOT NULL OR
                       ocm.id IS NOT NULL OR
                       ofs.id IS NOT NULL OR
                       ot.id IS NOT NULL OR
                       ono.id IS NOT NULL
                       )
              ORDER BY ctx.id';

        $contextlist->add_from_sql($sql, [
                'modname' => 'openstudio',
                'contextlevel' => CONTEXT_MODULE,
                'userid1' => $userid,
                'userid2' => $userid,
                'userid3' => $userid,
                'userid4' => $userid,
                'userid5' => $userid,
                'userid6' => $userid
        ]);
        return $contextlist;
    }

    /**
     * Removes personally-identifiable data from a user id for export.
     *
     * @param int $userid User id of a person
     * @param \stdClass $user Object representing current user being considered
     * @return string 'You' if the two users match, 'Somebody else' otherwise
     */
    public static function you_or_somebody_else(int $userid, \stdClass $user) {
        if ($userid == $user->id) {
            return get_string('privacy_you', 'openstudio');
        } else {
            return get_string('privacy_somebodyelse', 'openstudio');
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        if (empty($contextlist->get_contextids())) {
            return;
        }

        // Get user id.
        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT ctx.id AS contextid, o.id
                  FROM {modules} m
                  JOIN {course_modules} cm ON cm.module = m.id AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                  JOIN {openstudio} o ON cm.instance = o.id
                 WHERE (ctx.id {$contextsql})";

        $params = [
                'modname' => 'openstudio',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid,
        ];

        $params += $contextparams;
        // Keep a mapping of openstudioid to contextid.
        $mappings = [];
        $openstudios = $DB->get_recordset_sql($sql, $params);

        foreach ($openstudios as $openstudio) {
            $mappings[$openstudio->id] = $openstudio->contextid;
            $context = \context::instance_by_id($mappings[$openstudio->id]);
            // Store the main openstudio data.
            $data = request_helper::get_context_data($context, $user);
            writer::with_context($context)->export_data([], $data);

            // Export trash file.
            // Trash files are the file in content and have not been removed yet after edited from user.
            static::export_trash_file($user, $context, $openstudio->id);
            // These comments are commented on content of another user.
            static::export_comments_data($user, $context, null,
                    [get_string('othercontent', 'openstudio')], $openstudio->id);
            // These flags are stored from content of another user.
            static::export_flags_data($user, $context, null,
                    [get_string('othercontent', 'openstudio')], $openstudio->id);
            // These tracking are stored from content of another user.
            static::export_tracking_data($user, $context, null,
                    [get_string('othercontent', 'openstudio')], $openstudio->id);
            // These contents version are deleted by current user.
            static::export_content_version($user, $context, null,
                    [get_string('othercontent', 'openstudio')], $openstudio->id);
            // These notifications to another contents.
            static::export_notifications_data($user, $context, null,
                    [get_string('othercontent', 'openstudio')], $openstudio->id);
        }

        $openstudios->close();
        // Export all information within contents of user requested.
        if (!empty($mappings)) {
            static::export_folder_data($user, $mappings);
            static::export_contents_data($user, $mappings);
            static::export_subscriptions_data($user, $mappings);
            static::export_honesty_checks_data($user, $mappings);
        }
    }

    /**
     * Store all information about folders that we have detected this user to have access to.
     *
     * @param \stdClass $user The user of the user whose data is to be exported.
     * @param array $mappings A list of mappings from openstudio => contextid.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function export_folder_data(\stdClass $user, array $mappings) {
        global $DB;
        $userid = $user->id;

        list($folderinsql, $foldersparams) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);

        // Query folder data has been created from user.
        $sql = "SELECT oc.id AS foldersid, oc.userid, oc.deletedby, oc.deletedtime, oc.timemodified, oc.timeflagged,
                       oc.lockedby, oc.lockedtime, oc.lockprocessed, oc.openstudioid, oc.fileid, oc.visibility,
                       oc.contenttype, oc.description, oc.locktype, oc.lockedtime, oc.content, oc.textformat, oc.name
                  FROM {openstudio} o
                  JOIN {openstudio_contents} oc ON o.id = oc.openstudioid
                 WHERE o.id {$folderinsql}
                       AND oc.contenttype = :typefolder
                       AND oc.userid = :userid
                       OR oc.lockedby = :lockedby
                       OR oc.deletedby = :deletedby";
        $params = [
                'typefolder' => content::TYPE_FOLDER,
                'userid' => $userid,
                'lockedby' => $userid,
                'deletedby' => $userid
        ];

        $params += $foldersparams;
        $folders = $DB->get_recordset_sql($sql, $params);
        foreach ($folders as $folder) {
            $context = \context::instance_by_id($mappings[$folder->openstudioid]);
            // Folder path contain contents.
            $folderpath = [get_string('folders', 'openstudio'),
                    get_string('folder', 'openstudio', $folder->foldersid)];

            // Export content within this folder.
            static::export_contents_data($user, $mappings, true, $folder);
            static::export_notifications_data($user, $context, $folder, $folderpath, null, true);

            // Define deleted time, deleted by, locked time, locked by.
            $deletedtime = !empty($folder->deletedtime) ?
                    transform::datetime($folder->deletedtime) : '';
            $deletedby = !empty($folder->deletedby) ? static::you_or_somebody_else($folder->deletedby, $user) : '';

            $lockedtime = !empty($folder->lockedtime) ?
                    transform::datetime($folder->lockedtime) : '';
            $lockedby = !empty($folder->lockedby) ? static::you_or_somebody_else($folder->lockedby, $user) : '';

            // Store all information about this folder.
            $resultfolder = (object) [
                    'user' => static::you_or_somebody_else($folder->userid, $user),
                    'name' => format_string($folder->name, true),
                    'contenttype' => static::content_type($folder->contenttype),
                    'description' => format_text($folder->description, $folder->textformat, $context),
                    'visibility' => static::visibility_type($folder->visibility),
                    'deletedtime' => $deletedtime,
                    'deletedby' => $deletedby,
                    'locktype' => static::locked_type($folder->locktype),
                    'lockedtime' => $lockedtime,
                    'lockedby' => $lockedby,
                    'timeflagged' => transform::datetime($folder->timeflagged),
                    'lockprocessed' => transform::datetime($folder->lockprocessed)
            ];
            writer::with_context($context)->export_data($folderpath, $resultfolder);
        }
        $folders->close();
    }

    /**
     * Store all information about contents that we have detected this user to have access to.
     *
     * @param \stdClass $user The user of the user whose data is to be exported.
     * @param array $mappings A list of mappings from openstudio => contextid.
     * @param boolean $isinfolder if true, this content is in folder, vice versa.
     * @param \stdClass $folder The folder cover this content.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function export_contents_data(\stdClass $user, array $mappings, $isinfolder = false,
            \stdClass $folder = null) {
        global $DB;

        $userid = $user->id;

        list($contentsinsql, $contentsparams) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);

        $paramsfolder = [];
        if ($isinfolder) {
            // If this content is in folder.
            $where = 'AND oc.id IN(SELECT ofc.contentid
                                     FROM {openstudio_folder_contents} ofc
                                    WHERE ofc.folderid = :folderid)';

            $paramsfolder = ['folderid' => $folder->foldersid];
        } else {
            // If this content is not in folder.
            $where = 'AND oc.id NOT IN(SELECT ofc.contentid FROM {openstudio_folder_contents} ofc)
                      AND oc.id NOT IN(SELECT ofc.folderid FROM {openstudio_folder_contents} ofc)';

        }

        $sql = "SELECT oc.id AS contentsid, oc.userid AS contentsuserid,
                       oc.deletedby, oc.deletedtime, oc.timemodified, oc.timeflagged, oc.lockedby,
                       oc.lockedtime, oc.lockprocessed, oc.openstudioid, oc.fileid, oc.visibility, oc.contenttype,
                       oc.description, oc.locktype, oc.lockedtime, oc.content, oc.textformat, oc.name
                  FROM {openstudio} o
                  JOIN {openstudio_contents} oc ON o.id = oc.openstudioid
                 WHERE o.id {$contentsinsql}
                       {$where}
                       AND oc.userid = :userid
                       OR oc.lockedby = :lockedby
                       OR oc.deletedby = :deletedby";
        $params = [
                'userid' => $userid,
                'lockedby' => $userid,
                'deletedby' => $userid
        ];

        $params += $paramsfolder;
        $params += $contentsparams;

        $contents = $DB->get_recordset_sql($sql, $params);
        foreach ($contents as $content) {
            $context = \context::instance_by_id($mappings[$content->openstudioid]);
            if ($isinfolder) {
                // Path of content if this content in folder.
                $contentpath = [get_string('folders', 'openstudio'),
                        get_string('folder', 'openstudio', $folder->foldersid),
                        get_string('contents', 'openstudio'),
                        get_string('content', 'openstudio', $content->contentsid)];
            } else {
                // Path of content if this content not in folder.
                $contentpath = [get_string('contents', 'openstudio'),
                        get_string('content', 'openstudio', $content->contentsid)];
            }
            // Export all data related to contents.
            static::export_tracking_data($user, $context, $content, $contentpath);
            static::export_comments_data($user, $context, $content, $contentpath);
            static::export_flags_data($user, $context, $content, $contentpath);
            static::export_content_version($user, $context, $content, $contentpath);
            static::export_file_user($context, $content, $contentpath);
            static::export_notifications_data($user, $context, $content, $contentpath);

            // Define deleted time, deleted by, locked time, locked by.
            $deletedtime = !empty($content->deletedtime) ?
                    transform::datetime($content->deletedtime) : '';
            $deletedby = !empty($content->deletedby) ? static::you_or_somebody_else($content->deletedby, $user) : '';
            $lockedtime = !empty($content->lockedtime) ?
                    transform::datetime($content->lockedtime) : '';
            $lockedby = !empty($content->lockedby) ? static::you_or_somebody_else($content->lockedby, $user) : '';

            $resultcontent = (object) [
                    'user' => static::you_or_somebody_else($content->contentsuserid, $user),
                    'name' => format_string($content->name, true),
                    'contenttype' => static::content_type($content->contenttype),
                    'content' => format_text($content->content, $content->textformat, $context),
                    'description' => format_text($content->description, $content->textformat, $context),
                    'visibility' => static::visibility_type($content->visibility),
                    'deletedtime' => $deletedtime,
                    'deletedby' => $deletedby,
                    'locktype' => static::locked_type($content->locktype),
                    'lockedtime' => $lockedtime,
                    'lockedby' => $lockedby,
                    'timeflagged' => transform::datetime($content->timeflagged),
                    'lockprocessed' => transform::datetime($content->lockprocessed)
            ];

            writer::with_context($context)->export_data($contentpath, $resultcontent);
            // Add more folder contain tags to content path.
            array_push($contentpath, get_string('tags', 'openstudio'));
            // Export tags data related to this contents.
            \core_tag\privacy\provider::export_item_tags($userid, $context, $contentpath, 'mod_openstudio',
                    'openstudio_contents', $content->contentsid);
        }
        $contents->close();
    }

    /**
     * Store all file that we have detected this user to have access to.
     *
     * @param \context_module $context The instance of the forum context.
     * @param \stdClass $data The data want to export files.
     * @param array $contentpath The path cotain files.
     * @param boolean $iscomment If true, this data is comments, vice versa.
     * @param boolean $oldversion If true, this content is oldversion, vice versa.
     * @throws \coding_exception
     */
    public static function export_file_user(\context_module $context, \stdClass $data, $contentpath,
            $iscomment = false, $oldversion = false) {

        // All areas of openstudio.
        $fileareas = ['content', 'contentthumbnail', 'contentversion', 'contentthumbnailversion',
                'notebook', 'notebookversion'];

        if ($iscomment) {
            // Export file from comments.
            $contentpath = array_merge($contentpath, [get_string('comments', 'openstudio'),
                    get_string('file', 'openstudio')]);
            writer::with_context($context)->export_area_files($contentpath, 'mod_openstudio',
                    'contentcomment', $data->commentsid);
        } else {
            // Define path of file if this is in old version or current version.
            if (!$oldversion) {
                array_push($contentpath, get_string('file', 'openstudio'));
            } else {
                $contentpath = array_merge($contentpath, [get_string('version', 'openstudio'),
                        get_string('file', 'openstudio')]);
            }

            foreach ($fileareas as $filearea) {
                writer::with_context($context)->export_area_files($contentpath,
                        'mod_openstudio', $filearea, $data->fileid);
            }
        }
    }

    /**
     * Store all information about subscriptions that we have detected this user to have access to.
     *
     * @param \stdClass $user The user of the user whose data is to be exported.
     * @param array $mappings A list of mappings from openstudio => contextid.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function export_subscriptions_data(\stdClass $user, array $mappings) {
        global $DB;

        $userid = $user->id;

        list($subsinsql, $subsparams) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);

        $sql = "SELECT os.id AS subscriptionsid, os.userid AS subscriptionsuserid, os.subscription, os.frequency,
                       os.timeprocessed, os.timemodified, os.openstudioid, o.name AS nameopenstudio
                  FROM {openstudio} o
                  JOIN {openstudio_subscriptions} os ON o.id = os.openstudioid
                 WHERE o.id {$subsinsql} AND os.userid = :userid";

        $params = [
                'userid' => $userid,
        ];

        $params += $subsparams;
        $subscriptions = $DB->get_recordset_sql($sql, $params);
        foreach ($subscriptions as $sub) {
            $context = \context::instance_by_id($mappings[$sub->openstudioid]);

            // Information of subscription.
            $resultsubscription = (object) [
                    'user' => static::you_or_somebody_else($sub->subscriptionsuserid, $user),
                    'nameopenstudio' => format_string($sub->nameopenstudio, true),
                    'subscription' => static::subscription_type($sub->subscription),
                    'timeprocessed' => transform::datetime($sub->timeprocessed),
                    'timemodified' => transform::datetime($sub->timemodified)
            ];

            writer::with_context($context)->export_data([get_string('subscription', 'openstudio')],
                    $resultsubscription);
        }
        $subscriptions->close();
    }

    /**
     * Store all information about honesty check that we have detected this user to have access to.
     *
     * @param \stdClass $user The user of the user whose data is to be exported.
     * @param array $mappings A list of mappings from openstudio => contextid.
     * @throws \coding_exception
     */
    public static function export_honesty_checks_data(\stdClass $user, array $mappings) {
        global $DB;

        $userid = $user->id;

        list($honestycheckinsql, $honestycheckparams) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);

        $sql = "SELECT ohc.id AS honestycheckid, ohc.userid AS honestyuserid, ohc.timemodified,
                       cm.instance AS ostudioid, o.name AS nameopenstudio
                  FROM {openstudio_honesty_checks} ohc
                  JOIN {course_modules} cm ON cm.id = ohc.openstudioid
                  JOIN {openstudio} o ON cm.instance = o.id
                 WHERE o.id {$honestycheckinsql} AND ohc.openstudioid = cm.id AND ohc.userid = :userid";

        $params = [
                'modname' => 'openstudio',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid,
        ];
        $params += $honestycheckparams;

        $honestychecks = $DB->get_recordset_sql($sql, $params);

        foreach ($honestychecks as $hc) {
            $context = \context::instance_by_id($mappings[$hc->ostudioid]);
            // Information of honesty check.
            $resulthonestycheck = (object) [
                    'user' => static::you_or_somebody_else($hc->honestyuserid, $user),
                    'nameopenstudio' => $hc->nameopenstudio,
                    'honestychecked' => transform::yesno($hc->honestyuserid == $userid),
                    'timemodified' => transform::datetime($hc->timemodified)
            ];
            writer::with_context($context)->export_data([get_string('honestycheck', 'openstudio')],
                    $resulthonestycheck);
        }
        $honestychecks->close();
    }

    /**
     * Store all information about content version that we have detected this user to have access to.
     *
     * @param \stdClass $user The user of the user whose data is to be exported.
     * @param \context_module $context The instance of the forum context.
     * @param \stdClass $content The content related to user.
     * @param array $contentpath The path cotain files.
     * @param int $openstudioid openstudio id.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function export_content_version(\stdClass $user, \context_module $context, \stdClass $content = null,
            $contentpath, int $openstudioid = null) {
        global $DB;

        $userid = $user->id;

        if (!empty($content)) {
            // Content version related to current content.
            $where = 'ocv.contentid = :contentid';
            $params = ['contentid' => $content->contentsid];
        } else {
            // Content version related to deleted by current user.
            $where = 'ocv.deletedby = :deletedby AND
                    ocv.contentid IN (SELECT oc.id
                                        FROM {openstudio_contents} oc
                                        JOIN {openstudio} o ON o.id = oc.openstudioid
                                       WHERE o.id = :openstudioid)';

            $params = [
                    'deletedby' => $userid,
                    'openstudioid' => $openstudioid
            ];
        }

        $sql = "SELECT ocv.id AS contentversionid, ocv.contenttype, ocv.content, ocv.fileid, ocv.name, ocv.description,
                       ocv.deletedby, ocv.deletedtime, ocv.timemodified, ocv.textformat
                  FROM {openstudio_content_versions} ocv
                 WHERE {$where}";

        $contentversions = $DB->get_recordset_sql($sql, $params);
        foreach ($contentversions as $version) {
            static::export_file_user($context, $version, $contentpath, false, true);

            // Define deleted time and deleted by.
            $deletedtime = !empty($version->deletedtime) ? transform::datetime($version->deletedtime) : '';
            $deletedby = !empty($version->lockedby) ? static::you_or_somebody_else($version->deletedby, $user) : '';

            // Information of content version.
            $resultcontent[$version->contentversionid] = [
                    'name' => format_string($version->name, true),
                    'currentcontent' => $content->name,
                    'contenttype' => static::content_type($version->contenttype),
                    'content' => format_text($version->content, $version->textformat, $context),
                    'description' => format_text($version->description, $version->textformat, $context),
                    'deletedtime' => $deletedtime,
                    'deletedby' => $deletedby,
                    'timemodified' => transform::datetime($version->timemodified),
            ];
        }
        $contentversions->close();
        if (!empty($resultcontent)) {
            $result = (object) $resultcontent;
            // Add more folder to path of content version.
            array_push($contentpath, get_string('version', 'openstudio'));
            writer::with_context($context)->export_data($contentpath, $result);
        }
    }

    /**
     * Store all file trash that we have detected this user to have access to.
     * These files were stored after content has been edited, and have not been deleted yet.
     *
     * @param \stdClass $user The user of the user whose data is to be exported.
     * @param \context_module $context The instance of the forum context.
     * @param int $openstudioid Openstudio id.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function export_trash_file(\stdClass $user, \context_module $context, int $openstudioid) {
        global $DB;
        $userid = $user->id;

        $sql = "SELECT DISTINCT f.itemid
                  FROM {files} f
                 WHERE f.component = 'mod_openstudio' AND f.userid = :userid AND f.contextid = :contextid
                       AND f.itemid NOT IN (SELECT oc.fileid
                                              FROM {openstudio_contents} oc
                                              JOIN {files} f ON f.itemid = oc.fileid
                                              JOIN {openstudio} o ON o.id = oc.openstudioid
                                             WHERE o.id = :openstudioid AND oc.userid = :useridcontent)";

        $params = [
                'userid' => $userid,
                'contextid' => $context->id,
                'openstudioid' => $openstudioid,
                'useridcontent' => $userid
        ];

        $trashfiles = $DB->get_recordset_sql($sql, $params);
        // All areas of trash files.
        $fileareas = ['content', 'contentthumbnail', 'contentversion', 'contentthumbnailversion',
                'notebook', 'notebookversion'];

        // Export all trash files.
        foreach ($trashfiles as $file) {
            foreach ($fileareas as $area) {
                writer::with_context($context)->export_area_files(
                        [get_string('trashfile', 'openstudio')],
                        'mod_openstudio', $area, $file->itemid);
            }
        }
        $trashfiles->close();
    }

    /**
     * Store all information about tracking that we have detected this user to have access to.
     *
     * @param \stdClass $user The user of the user whose data is to be exported.
     * @param \context $context The instance of the forum context.
     * @param \stdClass $content The content related to user.
     * @param array $contentpath The path cotain files.
     * @param int $openstudioid Openstudio id.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function export_tracking_data(\stdClass $user, \context_module $context, \stdClass $content = null,
            $contentpath, int $openstudioid = null) {
        global $DB;

        $userid = $user->id;

        if (!empty($content)) {
            // Tracking data in content of this user.
            $where = 'oc.id = :contentid AND ot.userid = :userid';
            $paramsextra = ['contentid' => $content->contentsid];
        } else {
            // Tracking data in content of another user.
            $where = 'o.id = :openstudioid AND ot.userid = :userid AND oc.userid != :userincontent';
            $paramsextra = [
                    'openstudioid' => $openstudioid,
                    'userincontent' => $userid
            ];
        }

        $sql = "SELECT ot.id AS trackingid, ot.userid AS trackinguserid, ot.actionid AS trackingactionid,
                       ot.timemodified, ot.contentid, oc.name AS contentname
                  FROM {openstudio_tracking} ot
                  JOIN {openstudio_contents} oc ON ot.contentid = oc.id
                  JOIN {openstudio} o ON o.id = oc.openstudioid
                 WHERE {$where}";
        $params = [
                'userid' => $userid
        ];
        $params += $paramsextra;

        $trackings = $DB->get_recordset_sql($sql, $params);
        foreach ($trackings as $tracking) {
            // Store tracking information.
            $resulttracking[$tracking->trackingid] = [
                    'user' => static::you_or_somebody_else($tracking->trackinguserid, $user),
                    'contentname' => format_string($tracking->contentname, true),
                    'action' => static::action_name_tracking($tracking->trackingactionid),
                    'timemodified' => transform::datetime($tracking->timemodified)
            ];
        }
        $trackings->close();
        if (!empty($resulttracking)) {
            $result = (object) $resulttracking;
            // Add more folder to path of tracking.
            array_push($contentpath, get_string('tracking', 'openstudio'));
            writer::with_context($context)->export_data($contentpath, $result);
        }
    }

    /**
     * Store all infomation about comments that we have detected this user to have access to.
     *
     * @param \stdClass $user The user of the user whose data is to be exported.
     * @param \context $context The instance of the forum context.
     * @param \stdClass $content The content related to user.
     * @param array $contentpath The path cotain files.
     * @param int $openstudioid openstudio id.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_comments_data(\stdClass $user, \context_module $context, \stdClass $content = null,
            $contentpath, int $openstudioid = null) {
        global $DB;
        $userid = $user->id;

        if (!empty($content)) {
            // Comments in content of this user.
            $where = 'oc.id = :contentid AND (ocm.userid = :userid OR ocm.deletedby = :deletedby)';
            $paramsextra = ['contentid' => $content->contentsid];
        } else {
            // Comments in content of another user.
            $where = 'o.id = :openstudioid1 AND (ocm.userid = :userid OR ocm.deletedby = :deletedby)
                    AND oc.userid != :userincontent';
            $paramsextra = [
                    'userincontent' => $userid,
                    'openstudioid1' => $openstudioid
            ];
        }

        $sql = "SELECT ocm.id AS commentsid, ocm.userid AS commentsuserid, ocm.title, ocm.commenttext,
                       ocm.deletedby, ocm.deletedtime, ocm.timemodified, ocm.contentid, oc.name AS namecontent,
                       ocm.inreplyto
                  FROM {openstudio_comments} ocm
                  JOIN {openstudio_contents} oc ON ocm.contentid = oc.id
                  JOIN {openstudio} o ON o.id = oc.openstudioid
                 WHERE {$where}";

        $params = [
                'userid' => $userid,
                'deletedby' => $userid
        ];
        $params += $paramsextra;

        $comments = $DB->get_recordset_sql($sql, $params);
        foreach ($comments as $comment) {
            // Define time and user.
            $deletedtime = !empty($comment->deletedtime) ?
                    transform::datetime($comment->deletedtime) : '';
            $deletedby = !empty($comment->deletedby) ? static::you_or_somebody_else($comment->deletedby, $user) : '';
            $inreplyto = !empty($comment->inreplyto) ? $comment->inreplyto : 0;

            // Export files related to this comment.
            static::export_file_user($context, $comment, $contentpath, true);

            $resultcomment[$comment->commentsid] = [
                    'user' => static::you_or_somebody_else($comment->commentsuserid, $user),
                    'title' => format_string($comment->title, true),
                    'namecontent' => format_string($comment->namecontent, true),
                    'commenttext' => format_text($comment->commenttext, FORMAT_HTML, $context),
                    'timemodified' => transform::datetime($comment->timemodified),
                    'deletedby' => $deletedby,
                    'deletedtime' => $deletedtime,
                    'inreplyto' => $inreplyto
            ];
        }
        $comments->close();
        if (!empty($resultcomment)) {
            $result = (object) $resultcomment;
            // Add more folder to path of comments.
            array_push($contentpath, get_string('comments', 'openstudio'));
            writer::with_context($context)->export_data($contentpath, $result);
        }
    }

    /**
     * Store all infomation about flags that we have detected this user to have access to.
     *
     * @param \stdClass $user The user of the user whose data is to be exported.
     * @param \context_module $context The instance of the forum context.
     * @param \stdClass $content The content related to user.
     * @param array $contentpath The path contain files.
     * @param int $openstudioid openstudio id.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_flags_data(\stdClass $user, \context_module $context, \stdClass $content = null,
            $contentpath, int $openstudioid = null) {
        global $DB;
        $userid = $user->id;

        if (!empty($content)) {
            // Store flags of action in this content of this user.
            $where = 'oc.id = :contentid AND (ofs.userid = :userid OR ofs.personid = :personid)';
            $paramsextra = ['contentid' => $content->contentsid];
        } else {
            // Store flags of action in content of another user.
            $where = 'o.id = :openstudioid AND (ofs.userid = :userid OR ofs.personid = :personid)
            AND oc.userid != :userincontent';
            $paramsextra = [
                    'openstudioid' => $openstudioid,
                    'userincontent' => $userid
            ];
        }
        $sql = "SELECT ofs.id AS flagsid, ofs.commentid AS flagscommentid, ofs.userid AS flagsuserid,
                       ofs.folderid AS flagsfolderid, ofs.timemodified, ofs.contentid, ofs.flagid, ofs.personid,
                       oc.name AS contentname
                  FROM {openstudio_flags} ofs
                  JOIN {openstudio_contents} oc ON ofs.contentid = oc.id
                  JOIN {openstudio} o ON o.id = oc.openstudioid
                 WHERE {$where}";

        $params = [
                'userid' => $userid,
                'personid' => $userid,
        ];
        $params += $paramsextra;

        $flags = $DB->get_recordset_sql($sql, $params);
        foreach ($flags as $flag) {
            $person = !empty($flag->personid) ? static::you_or_somebody_else($flag->personid, $user) : '';

            $resultflag[$flag->flagsid] = [
                    'user' => static::you_or_somebody_else($flag->flagsuserid, $user),
                    'contentname' => format_string($flag->contentname),
                    'person' => $person,
                    'flagname' => static::flag_name($flag->flagid),
                    'timemodified' => transform::datetime($flag->timemodified)
            ];
        }
        $flags->close();
        if (!empty($resultflag)) {
            $result = (object) $resultflag;
            // Add more folder to path of flags.
            array_push($contentpath, get_string('flags', 'openstudio'));
            writer::with_context($context)->export_data($contentpath, $result);
        }
    }

    /**
     * Store all information about notification that we have detected this user to have access to.
     *
     * @param \stdClass $user The user of the user whose data is to be exported.
     * @param \context $context The instance of the forum context.
     * @param \stdClass $content The content related to user.
     * @param array $contentpath The path contain files.
     * @param int $openstudioid openstudio id.
     * @param boolean $isinfolder check this notification is in folder.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function export_notifications_data(\stdClass $user, \context_module $context, \stdClass $content = null,
            $contentpath, int $openstudioid = null, $isinfolder = false) {
        global $DB;

        $userid = $user->id;

        if (!empty($content)) {
            // Notifications received from another user.
            $where = 'ono.userid = :userid AND oc.id = :contentid';
            if ($isinfolder) {
                $paramsextra = [
                        'contentid' => $content->foldersid
                ];
            } else {
                $paramsextra = [
                        'contentid' => $content->contentsid
                ];
            }
        } else {
            // Notifications send to another user.
            $where = 'ono.userfrom = :userid AND ono.contentid IN (SELECT oc.id
                                                                     FROM {openstudio_contents} oc
                                                                     JOIN {openstudio} o ON o.id = oc.openstudioid
                                                                    WHERE o.id = :openstudioid)';
            $paramsextra = [
                    'openstudioid' => $openstudioid
            ];
        }

        $sql = "SELECT ono.id AS notificationsid, ono.userid AS notificationsuserid, ono.message, ono.timecreated,
                       ono.timeread, ono.contentid, ono.userfrom, oc.name AS namecontent
                  FROM {openstudio_notifications} ono
                  JOIN {openstudio_contents} oc ON ono.contentid = oc.id
                 WHERE {$where}";

        $params = [
                'userid' => $userid,
        ];
        $params += $paramsextra;

        $notifications = $DB->get_recordset_sql($sql, $params);

        foreach ($notifications as $notification) {
            // Information of notification.
            $resultnotifications[$notification->notificationsid] = [
                    'user' => static::you_or_somebody_else($notification->notificationsuserid, $user),
                    'message' => format_string($notification->message, true),
                    'fromuser' => static::you_or_somebody_else($notification->userfrom, $user),
                    'namecontent' => format_string($notification->namecontent, true),
                    'timecreated' => transform::datetime($notification->timecreated),
                    'timeread' => transform::datetime($notification->timeread)
            ];
        }
        $notifications->close();
        if (!empty($resultnotifications)) {
            $result = (object) $resultnotifications;
            array_push($contentpath, get_string('notifications', 'openstudio'));
            writer::with_context($context)->export_data($contentpath, $result);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // Check that this is a context_module.
        if (!$context instanceof \context_module) {
            return;
        }

        // Get the course module.
        if (!$cm = get_coursemodule_from_id('openstudio', $context->instanceid)) {
            return;
        }

        $openstudioid = $cm->instance;
        $cmid = $cm->id;
        $contentlistsql = '(SELECT id FROM {openstudio_contents} WHERE openstudioid = :openstudioid)';
        $folderlistsql = '(SELECT id FROM {openstudio_contents} WHERE openstudioid = :openstudioid2)';
        $contentlistparams = ['openstudioid' => $openstudioid];
        $folderlistparams = ['openstudioid' => $openstudioid, 'openstudioid2' => $openstudioid];

        $DB->delete_records('openstudio_subscriptions', ['openstudioid' => $openstudioid]);
        $DB->delete_records('openstudio_honesty_checks', ['openstudioid' => $cmid]);
        // Delete all discussion items.
        $DB->delete_records_select('openstudio_notifications', 'contentid IN ' . $contentlistsql, $contentlistparams);

        $DB->delete_records_select('openstudio_tracking', 'contentid IN ' . $contentlistsql, $contentlistparams);

        $DB->delete_records_select('openstudio_content_items', 'containerid IN ' . $contentlistsql, $contentlistparams);

        $DB->delete_records_select('openstudio_content_versions', 'contentid IN ' . $contentlistsql, $contentlistparams);

        $DB->delete_records_select(
                'openstudio_folder_contents',
                'contentid IN ' . $contentlistsql . 'OR folderid IN ' . $folderlistsql,
                $folderlistparams
        );

        $DB->delete_records_select('openstudio_flags', 'contentid IN ' . $contentlistsql, $contentlistparams);

        $DB->delete_records_select('openstudio_comments', 'contentid IN ' . $contentlistsql, $contentlistparams);

        $DB->delete_records('openstudio_contents', $contentlistparams);

        // Delete all files from the posts.
        $fs = get_file_storage();
        $fileareas = ['content', 'contentthumbnail', 'contentversion', 'contentthumbnailversion',
                'contentcomment', 'notebook', 'notebookversion'];

        foreach ($fileareas as $filearea) {
            $fs->delete_area_files($context->id, 'mod_openstudio', $filearea);
        }

        // Delete all Tags.
        \core_tag\privacy\provider::delete_item_tags($context, 'mod_openstudio', 'openstudio_contents');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $user->id;
        $adminid = get_admin()->id;
        $fileareas = ['content', 'contentthumbnail', 'contentversion', 'contentthumbnailversion', 'notebook',
                'notebookversion', 'draft'];
        $fs = get_file_storage();
        foreach ($contextlist as $context) {
            // Get the course module.
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            // Get openstudio id.
            $openstudioid = $cm->instance;

            $userlistsql = 'userid = :userid AND contentid IN (SELECT id FROM {openstudio_contents}
                    WHERE openstudioid = :openstudioid)';

            $setusersql = ':userid AND contentid IN (SELECT id FROM {openstudio_contents}
                    WHERE openstudioid = :openstudioid)';

            $usersparams = [
                    'userid' => $userid,
                    'openstudioid' => $openstudioid
            ];

            $DB->delete_records('openstudio_subscriptions', $usersparams);

            $DB->delete_records('openstudio_honesty_checks', ['openstudioid' => $cm->id, 'userid' => $userid]);

            // Delete all openstudio notifications.
            $DB->delete_records_select('openstudio_notifications', $userlistsql, $usersparams);

            // Update userid of notification related to this user.
            $DB->set_field_select('openstudio_notifications', 'message',
                    get_string('deletedbyrequest', 'openstudio'),
                    'userfrom = ' . $setusersql, $usersparams);

            $DB->set_field_select('openstudio_notifications', 'userfrom', $adminid, 'userfrom = ' .
                    $setusersql, $usersparams);

            // Delete all openstudio trackings.
            $DB->delete_records_select('openstudio_tracking', $userlistsql, $usersparams);

            // Delete all openstudio flags.
            $DB->delete_records_select('openstudio_flags', $userlistsql, $usersparams);

            $DB->set_field_select('openstudio_flags', 'personid', $adminid, 'personid = ' . $setusersql,
                    $usersparams);

            // Only update comments data if comment has reply, delete comments if don't have reply.
            $inreplytocommentsql = "SELECT DISTINCT ocm.inreplyto
                                      FROM {openstudio_comments} ocm
                                      JOIN {openstudio_contents} oc ON ocm.contentid = oc.id
                                      JOIN {openstudio} o ON o.id = oc.openstudioid
                                     WHERE o.id = :openstudioid AND ocm.inreplyto != 0
                                           AND oc.userid != :userid";

            $inreplytos = $DB->get_fieldset_sql($inreplytocommentsql, $usersparams);

            // Remove comments in content of other user.
            $commentsql = "SELECT ocm.id
                             FROM {openstudio_comments} ocm
                             JOIN {openstudio_contents} oc ON ocm.contentid = oc.id
                             JOIN {openstudio} o ON o.id = oc.openstudioid
                            WHERE o.id = :openstudioid AND ocm.userid = :userid
                                  AND oc.userid != :userincontent";

            $commentparrams = [
                    'userincontent' => $userid
            ];
            $commentparrams += $usersparams;

            $comments = $DB->get_recordset_sql($commentsql, $commentparrams);
            foreach ($comments as $comment) {
                // Update comments to empty if has reply.
                $fs->delete_area_files($context->id, 'mod_openstudio', 'contentcomment', $comment->id);

                if (in_array($comment->id, $inreplytos)) {
                    $defaultcomments = new \stdClass();
                    $defaultcomments->id = $comment->id;
                    $defaultcomments->userid = $adminid;
                    $defaultcomments->commenttext = get_string('deletedbyrequest', 'openstudio');
                    $DB->update_record('openstudio_comments', $defaultcomments);
                } else {
                    // Delete comments if it doesn't have reply.
                    $DB->delete_records_select(
                            'openstudio_comments',
                            'id = :id', ['id' => $comment->id]
                    );
                }
            }
            $comments->close();
            // Update field deleted by to adminid of comments of other user.
            $DB->set_field_select('openstudio_comments', 'deletedby', $adminid, 'deletedby = ' . $setusersql,
                    $usersparams);
            // Update filed deleted by to adminid of old version contents.
            $DB->set_field_select('openstudio_content_versions', 'deletedby', $adminid,
                    'deletedby = ' . $setusersql, $usersparams);

            // Delete trash file.
            // Trash files are the file in content and have not been removed yet after edited from user.
            $trashfilesql = "SELECT DISTINCT f.itemid
                               FROM {files} f
                              WHERE f.component = 'mod_openstudio' AND f.userid = :userid AND f.contextid = :contextid
                                    AND f.itemid NOT IN (SELECT oc.fileid
                                                           FROM {openstudio_contents} oc
                                                           JOIN {files} f ON f.itemid = oc.fileid
                                                           JOIN {openstudio} o ON o.id = oc.openstudioid
                                                          WHERE o.id = :openstudioid AND oc.userid = :useridcontent)";

            $trashfileparams = [
                    'userid' => $userid,
                    'contextid' => $context->id,
                    'openstudioid' => $openstudioid,
                    'useridcontent' => $userid
            ];
            $trashfiles = $DB->get_recordset_sql($trashfilesql, $trashfileparams);
            foreach ($trashfiles as $file) {
                foreach ($fileareas as $area) {
                    $fs->delete_area_files($context->id, 'mod_openstudio', $area, $file->itemid);
                }
            }
            $trashfiles->close();
            // Get contents.
            $contentssql = "SELECT oc.id, oc.fileid
                              FROM {openstudio_contents} oc
                             WHERE openstudioid = :openstudioid AND userid = :userid";

            $contents = $DB->get_recordset_sql($contentssql, $usersparams);
            // Deleted all data related to content of this user.
            foreach ($contents as $content) {
                $contentsparams = [
                        'contentid' => $content->id
                ];
                $DB->delete_records_select(
                        'openstudio_content_items',
                        'containerid = :containerid
                           OR containerid IN (
                                SELECT ocv.id
                                  FROM {openstudio_content_versions} ocv
                                 WHERE ocv.contentid = :contentid
                                )',
                        [
                                'containerid' => $content->id,
                                'contentid' => $content->id
                        ]
                );
                $DB->delete_records_select(
                        'openstudio_folder_contents',
                        'contentid = :contentid OR folderid = :folderid',
                        [
                                'contentid' => $content->id,
                                'folderid' => $content->id
                        ]
                );

                // Get contents in old version.
                $contentversions = $DB->get_records('openstudio_content_versions', $contentsparams);

                foreach ($contentversions as $cversion) {
                    foreach ($fileareas as $filearea) {
                        // Delete file of content old version.
                        $fs->delete_area_files($context->id, 'mod_openstudio', $filearea, $cversion->fileid);
                    }
                }

                // Delete contents old version.
                $DB->delete_records_select(
                        'openstudio_content_versions',
                        'contentid = :contentid', $contentsparams);

                // Get comments.
                $comments = $DB->get_records('openstudio_comments',
                        [
                                'contentid' => $content->id,
                                'userid' => $userid
                        ]
                );

                foreach ($comments as $comment) {
                    // Delete file of this comment.
                    $fs->delete_area_files($context->id, 'mod_openstudio', 'contentcomment', $comment->id);
                }
                // Delete comments.
                $DB->delete_records_select(
                        'openstudio_comments',
                        'contentid = :contentid', $contentsparams);

                // Delete all files from the posts.
                foreach ($fileareas as $filearea) {
                    $fs->delete_area_files($context->id, 'mod_openstudio', $filearea, $content->fileid);
                }
            }
            $contents->close();
            // Get content for tags.
            $contentstagsql = "SELECT oc.id
                                 FROM {openstudio_contents} oc
                                WHERE openstudioid = :openstudioid AND userid = :userid";

            // Delete all tags.
            \core_tag\privacy\provider::delete_item_tags_select($context, 'mod_openstudio',
                    'openstudio_contents', "IN ($contentstagsql)", $usersparams);

            $DB->delete_records('openstudio_contents', $usersparams);

            $contentssql = ':userid AND openstudioid = :openstudioid';

            $DB->set_field_select('openstudio_contents', 'deletedby', $adminid,
                    'deletedby = ' . $contentssql, $usersparams);
            $DB->set_field_select('openstudio_contents', 'lockedby', $adminid,
                    'lockedby = ' . $contentssql, $usersparams);
        }

    }

    /**
     * Export action name of tracking from actionid.
     *
     * @param int $actionid The id of action in tracking.
     */
    public static function action_name_tracking(int $actionid) {
        switch ($actionid) {
            case tracking::CREATE_CONTENT:
                $actionname = get_string('tracking_create', 'openstudio');
                break;
            case tracking::READ_CONTENT:
                $actionname = get_string('tracking_read', 'openstudio');
                break;
            case tracking::READ_CONTENT_VERSION:
                $actionname = get_string('tracking_read_version', 'openstudio');
                break;
            case tracking::DELETE_CONTENT:
                $actionname = get_string('tracking_delete_content', 'openstudio');
                break;
            case tracking::DELETE_CONTENT_VERSION:
                $actionname = get_string('tracking_delete_content_version', 'openstudio');
                break;
            case tracking::UPDATE_CONTENT:
                $actionname = get_string('tracking_update_content', 'openstudio');
                break;
            case tracking::UPDATE_CONTENT_VISIBILITY_PRIVATE:
                $actionname = get_string('tracking_update_content_visibility_private', 'openstudio');
                break;
            case tracking::UPDATE_CONTENT_VISIBILITY_GROUP:
                $actionname = get_string('tracking_update_content_visibility_group', 'openstudio');
                break;
            case tracking::UPDATE_CONTENT_VISIBILITY_MODULE:
                $actionname = get_string('tracking_update_content_visibility_module', 'openstudio');
                break;
            case tracking::ARCHIVE_CONTENT:
                $actionname = get_string('tracking_archive_content', 'openstudio');
                break;
            case tracking::MODIFY_FOLDER:
                $actionname = get_string('tracking_modify_folder', 'openstudio');
                break;
            case tracking::COPY_CONTENT:
                $actionname = get_string('tracking_copy_content', 'openstudio');
                break;
            case tracking::ADD_CONTENT_TO_FOLDER:
                $actionname = get_string('tracking_add_content_to_folder', 'openstudio');
                break;
            case tracking::LINK_CONTENT_TO_FOLDER:
                $actionname = get_string('tracking_link_content_to_folder', 'openstudio');
                break;
            case tracking::COPY_CONTENT_TO_FOLDER:
                $actionname = get_string('tracking_copy_content_to_folder', 'openstudio');
                break;
            case tracking::UPDATE_CONTENT_VISIBILITY_TUTOR:
                $actionname = get_string('tracking_update_content_visibility_tutor', 'openstudio');
                break;
        }
        return $actionname;
    }

    /**
     * Export type of flag from flagid.
     *
     * @param int $actionid The id of action in flagid.
     */
    public static function flag_name(int $flagid) {
        switch ($flagid) {
            case flags::ALERT:
                $flagname = get_string('flag_alert', 'openstudio');
                break;
            case flags::FAVOURITE:
                $flagname = get_string('flag_favourite', 'openstudio');
                break;
            case flags::NEEDHELP:
                $flagname = get_string('flag_needhelp', 'openstudio');
                break;
            case flags::MADEMELAUGH:
                $flagname = get_string('flag_mademelaugh', 'openstudio');
                break;
            case flags::INSPIREDME:
                $flagname = get_string('flag_inspiredme', 'openstudio');
                break;
            case flags::READ_CONTENT:
                $flagname = get_string('flag_read_content', 'openstudio');
                break;
            case flags::FOLLOW_CONTENT:
                $flagname = get_string('flag_follow_content', 'openstudio');
                break;
            case flags::FOLLOW_USER:
                $flagname = get_string('flag_follow_user', 'openstudio');
                break;
            case flags::COMMENT:
                $flagname = get_string('flag_comment', 'openstudio');
                break;
            case flags::COMMENT_LIKE:
                $flagname = get_string('flag_comment_like', 'openstudio');
                break;
            case flags::TUTOR:
                $flagname = get_string('flag_tutor', 'openstudio');
                break;
        }
        return $flagname;
    }

    /**
     * Export type of content type from contenttypeid.
     *
     * @param int $contenttypeid The id of type in contenttypeid.
     */
    public static function content_type(int $contenttypeid) {
        switch ($contenttypeid) {

            case content::TYPE_TEXT:
                $typename = get_string('content_type_text', 'openstudio');
                break;
            case content::TYPE_IMAGE:
                $typename = get_string('content_type_image', 'openstudio');
                break;
            case content::TYPE_IMAGE_EMBED:
                $typename = get_string('content_type_image_embed', 'openstudio');
                break;
            case content::TYPE_VIDEO:
                $typename = get_string('content_type_video', 'openstudio');
                break;
            case content::TYPE_VIDEO_EMBED:
                $typename = get_string('content_type_video_embed', 'openstudio');
                break;
            case content::TYPE_AUDIO:
                $typename = get_string('content_type_audio', 'openstudio');
                break;
            case content::TYPE_AUDIO_EMBED:
                $typename = get_string('content_type_audio_embed', 'openstudio');
                break;
            case content::TYPE_DOCUMENT:
                $typename = get_string('content_type_document', 'openstudio');
                break;
            case content::TYPE_DOCUMENT_EMBED:
                $typename = get_string('content_type_document_embed', 'openstudio');
                break;
            case content::TYPE_PRESENTATION:
                $typename = get_string('content_type_presentation', 'openstudio');
                break;
            case content::TYPE_PRESENTATION_EMBED:
                $typename = get_string('content_type_presentation_embed', 'openstudio');
                break;
            case content::TYPE_SPREADSHEET:
                $typename = get_string('content_type_spreadsheet', 'openstudio');
                break;
            case content::TYPE_SPREADSHEET_EMBED:
                $typename = get_string('content_type_spreadsheet_embed', 'openstudio');
                break;
            case content::TYPE_URL:
                $typename = get_string('content_type_url', 'openstudio');
                break;
            case content::TYPE_URL_IMAGE:
                $typename = get_string('content_type_url_image', 'openstudio');
                break;
            case content::TYPE_URL_VIDEO:
                $typename = get_string('content_type_url_video', 'openstudio');
                break;
            case content::TYPE_URL_AUDIO:
                $typename = get_string('content_type_url_audio', 'openstudio');
                break;
            case content::TYPE_URL_DOCUMENT:
                $typename = get_string('content_type_url_document', 'openstudio');
                break;
            case content::TYPE_URL_DOCUMENT_PDF:
                $typename = get_string('content_type_url_document_pdf', 'openstudio');
                break;
            case content::TYPE_URL_DOCUMENT_DOC:
                $typename = get_string('content_type_url_document_doc', 'openstudio');
                break;
            case content::TYPE_URL_PRESENTATION:
                $typename = get_string('content_type_url_presentation', 'openstudio');
                break;
            case content::TYPE_URL_PRESENTATION_PPT:
                $typename = get_string('content_type_url_presentation_ppt', 'openstudio');
                break;
            case content::TYPE_URL_SPREADSHEET:
                $typename = get_string('content_type_url_spreadsheet', 'openstudio');
                break;
            case content::TYPE_URL_SPREADSHEET_XLS:
                $typename = get_string('content_type_url_spreadsheet_xls', 'openstudio');
                break;
            case content::TYPE_FOLDER:
                $typename = get_string('content_type_folder', 'openstudio');
                break;
            case content::TYPE_FOLDER_CONTENT:
                $typename = get_string('content_type_folder_content', 'openstudio');
                break;
            case content::TYPE_CAD:
                $typename = get_string('content_type_CAD', 'openstudio');
                break;
            case content::TYPE_ZIP:
                $typename = get_string('content_type_ZIP', 'openstudio');
                break;
            default:
                $typename = get_string('content_type_none', 'openstudio');
                break;
        }
        return $typename;
    }

    /**
     * Export type of lock type from locktype.
     *
     * @param int $locktype The id of type in locktype.
     */
    public static function locked_type(int $locktype) {
        switch ($locktype) {

            case lock::NONE:
                $locktypename = get_string('locked_type_none', 'openstudio');
                break;
            case lock::ALL:
                $locktypename = get_string('locked_type_lockall', 'openstudio');
                break;
            case lock::CRUD:
                $locktypename = get_string('locked_type_CRUD', 'openstudio');
                break;
            case lock::SOCIAL:
                $locktypename = get_string('locked_type_social', 'openstudio');
                break;
            case lock::SOCIAL_CRUD:
                $locktypename = get_string('locked_type_social_CRUD', 'openstudio');
                break;
            case lock::COMMENT:
                $locktypename = get_string('locked_type_comment', 'openstudio');
                break;
            case lock::COMMENT_CRUD:
                $locktypename = get_string('locked_type_comment_CRUD', 'openstudio');
                break;
        }
        return $locktypename;
    }

    /**
     * Export type of visibility type from visibility.
     *
     * @param int $visibility The id of type in visibility.
     */
    public static function visibility_type(int $visibility) {
        switch ($visibility) {
            case content::VISIBILITY_PRIVATE:
                $visibility = get_string('visibility_private', 'openstudio');
                break;
            case content::VISIBILITY_GROUP:
                $visibility = get_string('visibility_group', 'openstudio');
                break;
            case content::VISIBILITY_MODULE:
                $visibility = get_string('visibility_module', 'openstudio');
                break;
            case content::VISIBILITY_WORKSPACE:
                $visibility = get_string('visibility_workspace', 'openstudio');
                break;
            case content::VISIBILITY_PRIVATE_PINBOARD:
                $visibility = get_string('visibility_private_pinboard', 'openstudio');
                break;
            case content::VISIBILITY_INFOLDERONLY:
                $visibility = get_string('visibility_infolderonly', 'openstudio');
                break;
            case content::VISIBILITY_TUTOR:
                $visibility = get_string('visibility_tutor', 'openstudio');
                break;
            case content::VISIBILITY_PEOPLE:
                $visibility = get_string('visibility_people', 'openstudio');
                break;

        }
        return $visibility;
    }

    /**
     * Export type of subscription type from $subscriptiontype.
     *
     * @param int $subscriptiontype The id of type in subscriptiontype.
     */
    public static function subscription_type(int $subscriptiontype) {
        switch ($subscriptiontype) {
            case subscription::FREQUENCY_HOURLY:
                $subscriptiontypename = get_string('subscription_type_hourly', 'openstudio');
                break;
            case subscription::FREQUENCY_DAILY:
                $subscriptiontypename = get_string('subscription_type_daily', 'openstudio');
                break;
        }
        return $subscriptiontypename;
    }

}