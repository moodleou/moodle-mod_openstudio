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
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use context;
use core_privacy\local\request\helper as request_helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
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
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

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
                'retainimagemetadata' => 'privacy:metadata:openstudio_contents:retainimagemetadata'
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
                    [get_string('privacy:subcontext:othercontent', 'openstudio')], $openstudio->id);
            // These flags are stored from content of another user.
            static::export_flags_data($user, $context, null,
                    [get_string('privacy:subcontext:othercontent', 'openstudio')], $openstudio->id);
            // These tracking are stored from content of another user.
            static::export_tracking_data($user, $context, null,
                    [get_string('privacy:subcontext:othercontent', 'openstudio')], $openstudio->id);
            // These contents version are deleted by current user.
            static::export_content_version($user, $context, null,
                    [get_string('privacy:subcontext:othercontent', 'openstudio')], $openstudio->id);
            // These notifications to another contents.
            static::export_notifications_data($user, $context, null,
                    [get_string('privacy:subcontext:othercontent', 'openstudio')], $openstudio->id);
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
            $folderpath = [get_string('privacy:subcontext:folders', 'openstudio'),
                    get_string('privacy:subcontext:folder', 'openstudio', $folder->foldersid)];

            // Export content within this folder.
            static::export_contents_data($user, $mappings, true, $folder);
            static::export_notifications_data($user, $context, $folder, $folderpath, null, true);

            // Define deleted time, deleted by, locked time, locked by.
            $deletedtime = !empty($folder->deletedtime) ? transform::datetime($folder->deletedtime) : '';
            $deletedby = !empty($folder->deletedby) ? static::you_or_somebody_else($folder->deletedby, $user) : '';

            $lockedtime = !empty($folder->lockedtime) ? transform::datetime($folder->lockedtime) : '';
            $lockedby = !empty($folder->lockedby) ? static::you_or_somebody_else($folder->lockedby, $user) : '';

            // Store all information about this folder.
            $resultfolder = (object) [
                    'user' => static::you_or_somebody_else($folder->userid, $user),
                    'name' => format_string($folder->name, true),
                    'contenttype' => get_string('privacy:contenttype:' . $folder->contenttype, 'mod_openstudio'),
                    'description' => format_text($folder->description, $folder->textformat, $context),
                    'visibility' => static::get_visibility_string($folder->visibility),
                    'deletedtime' => $deletedtime,
                    'deletedby' => $deletedby,
                    'locktype' => get_string('privacy:lock:' . $folder->locktype, 'mod_openstudio'),
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
                       oc.description, oc.locktype, oc.lockedtime, oc.content, oc.textformat, oc.name, oc.retainimagemetadata
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
                $contentpath = [get_string('privacy:subcontext:folders', 'openstudio'),
                        get_string('privacy:subcontext:folder', 'openstudio', $folder->foldersid),
                        get_string('privacy:subcontext:contents', 'openstudio'),
                        get_string('privacy:subcontext:content', 'openstudio', $content->contentsid)];
            } else {
                // Path of content if this content not in folder.
                $contentpath = [get_string('privacy:subcontext:contents', 'openstudio'),
                        get_string('privacy:subcontext:content', 'openstudio', $content->contentsid)];
            }
            // Export all data related to contents.
            static::export_tracking_data($user, $context, $content, $contentpath);
            static::export_comments_data($user, $context, $content, $contentpath);
            static::export_flags_data($user, $context, $content, $contentpath);
            static::export_content_version($user, $context, $content, $contentpath);
            static::export_file_user($context, $content, $contentpath);
            static::export_notifications_data($user, $context, $content, $contentpath);

            // Define deleted time, deleted by, locked time, locked by.
            $deletedtime = !empty($content->deletedtime) ? transform::datetime($content->deletedtime) : '';
            $deletedby = !empty($content->deletedby) ? static::you_or_somebody_else($content->deletedby, $user) : '';
            $lockedtime = !empty($content->lockedtime) ? transform::datetime($content->lockedtime) : '';
            $lockedby = !empty($content->lockedby) ? static::you_or_somebody_else($content->lockedby, $user) : '';
            $retainimagemetadata = !empty($content->retainimagemetadata) ? $content->retainimagemetadata : '';
            $resultcontent = (object) [
                    'user' => static::you_or_somebody_else($content->contentsuserid, $user),
                    'name' => format_string($content->name, true),
                    'contenttype' => get_string('privacy:contenttype:' . $content->contenttype, 'mod_openstudio'),
                    'content' => format_text($content->content, $content->textformat, $context),
                    'description' => format_text($content->description, $content->textformat, $context),
                    'visibility' => static::get_visibility_string($content->visibility),
                    'deletedtime' => $deletedtime,
                    'deletedby' => $deletedby,
                    'locktype' => get_string('privacy:lock:' . $content->locktype, 'mod_openstudio'),
                    'lockedtime' => $lockedtime,
                    'lockedby' => $lockedby,
                    'timeflagged' => transform::datetime($content->timeflagged),
                    'lockprocessed' => transform::datetime($content->lockprocessed),
                    'retainimagemetadata' => transform::yesno($retainimagemetadata)
            ];

            writer::with_context($context)->export_data($contentpath, $resultcontent);
            // Add more folder contain tags to content path.
            array_push($contentpath, get_string('privacy:subcontext:tags', 'openstudio'));
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
            $contentpath = array_merge($contentpath, [get_string('privacy:subcontext:comments', 'openstudio'),
                    get_string('privacy:subcontext:file', 'openstudio')]);
            writer::with_context($context)->export_area_files($contentpath, 'mod_openstudio',
                    'contentcomment', $data->commentsid);
        } else {
            // Define path of file if this is in old version or current version.
            if (!$oldversion) {
                array_push($contentpath, get_string('privacy:subcontext:file', 'openstudio'));
            } else {
                $contentpath = array_merge($contentpath, [get_string('privacy:subcontext:version', 'openstudio'),
                        get_string('privacy:subcontext:file', 'openstudio')]);
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
                    'subscription' => get_string('privacy:subscription:' . $sub->subscription, 'mod_openstudio'),
                    'timeprocessed' => transform::datetime($sub->timeprocessed),
                    'timemodified' => transform::datetime($sub->timemodified)
            ];

            writer::with_context($context)->export_data([get_string('privacy:subcontext:subscription', 'openstudio')],
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
            writer::with_context($context)->export_data([get_string('privacy:subcontext:honestycheck', 'openstudio')],
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
                    'contenttype' => get_string('privacy:contenttype:' . $version->contenttype, 'mod_openstudio'),
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
            array_push($contentpath, get_string('privacy:subcontext:version', 'openstudio'));
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
                        [get_string('privacy:subcontext:trashfile', 'openstudio')],
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
                    'action' => get_string('privacy:tracking:' . $tracking->trackingactionid, 'mod_openstudio'),
                    'timemodified' => transform::datetime($tracking->timemodified)
            ];
        }
        $trackings->close();
        if (!empty($resulttracking)) {
            $result = (object) $resulttracking;
            // Add more folder to path of tracking.
            array_push($contentpath, get_string('privacy:subcontext:tracking', 'openstudio'));
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
            $deletedtime = !empty($comment->deletedtime) ? transform::datetime($comment->deletedtime) : '';
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
            array_push($contentpath, get_string('privacy:subcontext:comments', 'openstudio'));
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
                    'flagname' => get_string('privacy:flag:' . $flag->flagid, 'mod_openstudio'),
                    'timemodified' => transform::datetime($flag->timemodified)
            ];
        }
        $flags->close();
        if (!empty($resultflag)) {
            $result = (object) $resultflag;
            // Add more folder to path of flags.
            array_push($contentpath, get_string('privacy:subcontext:flags', 'openstudio'));
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
            array_push($contentpath, get_string('privacy:subcontext:notifications', 'openstudio'));
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
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $params = [
                'instanceid' => $context->instanceid,
                'modname' => 'openstudio'
        ];

        $sql = "SELECT oc.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modname
                  JOIN {openstudio} o ON o.id = cm.instance
                  JOIN {openstudio_contents} oc ON oc.openstudioid = o.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT ot.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modname
                  JOIN {openstudio} o ON o.id = cm.instance
                  JOIN {openstudio_contents} oc ON oc.openstudioid = o.id
                  JOIN {openstudio_tracking} ot ON ot.contentid = oc.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT ono.userid, ono.userfrom
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modname
                  JOIN {openstudio} o ON o.id = cm.instance
                  JOIN {openstudio_contents} oc ON oc.openstudioid = o.id
                  JOIN {openstudio_notifications} ono ON ono.contentid = oc.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
        $userlist->add_from_sql('userfrom', $sql, $params);

        $sql = "SELECT ocm.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modname
                  JOIN {openstudio} o ON o.id = cm.instance
                  JOIN {openstudio_contents} oc ON oc.openstudioid = o.id
                  JOIN {openstudio_comments} ocm ON ocm.contentid = oc.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT ofs.id, ofs.userid, ofs.personid
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modname
                  JOIN {openstudio} o ON o.id = cm.instance
                  JOIN {openstudio_contents} oc ON oc.openstudioid = o.id
                  JOIN {openstudio_flags} ofs ON ofs.contentid = oc.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
        $userlist->add_from_sql('personid', $sql, $params);

        $sql = "SELECT ohc.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modname
                  JOIN {openstudio} o ON o.id = cm.instance
                  JOIN {openstudio_honesty_checks} ohc ON ohc.openstudioid = cm.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT os.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modname
                  JOIN {openstudio} o ON o.id = cm.instance
                  JOIN {openstudio_subscriptions} os ON os.openstudioid = o.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);

        if (empty($cm)) {
            return;
        }
        $adminid = get_admin()->id;
        $openstudioid = $cm->instance;
        $openstudioidparam = ['openstudioid' => $openstudioid];
        $contentids = array_keys($DB->get_records('openstudio_contents', $openstudioidparam));
        list($userinsql, $userinparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $params = array_merge($userinparams, $openstudioidparam);
        $contentssql = "$userinsql AND openstudioid = :openstudioid";
        list($userinsql, $userinparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        list($contentinsql, $contentinparams) = $DB->get_in_or_equal($contentids, SQL_PARAMS_NAMED);
        $fileareas = ['content', 'contentthumbnail', 'contentversion', 'contentthumbnailversion', 'notebook',
                'notebookversion', 'draft'];
        self::delete_subscriptions_for_users($openstudioid, $userinsql, $userinparams);
        self::delete_honestycheck_for_users($cm->id, $userinsql, $userinparams);

        self::delete_notification_for_users($userinsql, $userinparams, $contentinsql, $contentinparams);
        self::delete_tracking_for_users($userinsql, $userinparams, $contentinsql, $contentinparams);
        self::delete_flag_for_users($userinsql, $userinparams, $contentinsql, $contentinparams);
        self::delete_comments_for_users($userinsql, $userinparams, $contentinsql, $contentinparams, $context, $userids);
        self::delete_trash_files_for_users($userinsql, $userinparams, $openstudioid, $context, $fileareas, $userids);
        self::delete_content_for_users($userinsql, $userinparams, $openstudioid, $context, $fileareas);
        self::delete_tags_for_users($userinsql, $userinparams, $openstudioid, $context);

        $DB->delete_records_select('openstudio_contents', 'userid ' . $contentssql, $params);
        $DB->set_field_select('openstudio_contents', 'deletedby', $adminid,
                'deletedby ' . $contentssql, $params);
        $DB->set_field_select('openstudio_contents', 'lockedby', $adminid,
                'lockedby ' . $contentssql, $params);
    }

    /**
     * Delete subscriptions for users.
     *
     * @param int $openstudioid
     * @param array $userinsql
     * @param array $userinparams
     */
    private static function delete_subscriptions_for_users($openstudioid, $userinsql, $userinparams) {
        global $DB;
        $sql = "openstudioid = :openstudioid AND userid $userinsql";
        $params = array_merge(['openstudioid' => $openstudioid], $userinparams);
        $DB->delete_records_select('openstudio_subscriptions', $sql, $params);
    }

    /**
     * Delete honestycheck.
     *
     * @param int $openstudioid
     * @param array $userinsql
     * @param array $userinparams
     */
    private static function delete_honestycheck_for_users($openstudioid, $userinsql, $userinparams) {
        global $DB;
        $sql = "openstudioid = :openstudioid AND userid $userinsql";
        $params = array_merge(['openstudioid' => $openstudioid], $userinparams);
        $DB->delete_records_select('openstudio_honesty_checks', $sql, $params);
    }

    /**
     * Delete traciking.
     *
     * @param array $userinsql
     * @param array $userinparams
     * @param array $contentinsql
     * @param array $contentinparams
     */
    private static function delete_tracking_for_users($userinsql, $userinparams, $contentinsql, $contentinparams) {
        global $DB;
        $sql = "userid $userinsql AND contentid $contentinsql";
        $params = array_merge($userinparams, $contentinparams);
        $DB->delete_records_select('openstudio_tracking', $sql, $params);
    }

    /**
     * Delete notification.
     *
     * @param array $userinsql
     * @param array $userinparams
     * @param array $contentinsql
     * @param array $contentinparams
     */
    private static function delete_notification_for_users($userinsql, $userinparams, $contentinsql, $contentinparams) {
        global $DB;

        $sqlcontentin = " AND contentid $contentinsql";
        $params = array_merge($userinparams, $contentinparams);

        // For notification, delete records if deleting user belong to userid field.
        $DB->delete_records_select('openstudio_notifications', "userid $userinsql $sqlcontentin", $params);

        // And update notification content to empty and owner to admin user.
        $sql = "UPDATE {openstudio_notifications}
                   SET userfrom = :adminuserid, message = :deletedmessage
                 WHERE userfrom $userinsql $sqlcontentin";
        $params = array_merge([
            'adminuserid' => get_admin()->id,
            'deletedmessage' => get_string('deletedbyrequest', 'openstudio'),
        ], $userinparams, $contentinparams);
        $DB->execute($sql, $params);
    }

    /**
     * Delete flag.
     *
     * @param array $userinsql
     * @param array $userinparams
     * @param array $contentinsql
     * @param array $contentinparams
     */
    private static function delete_flag_for_users($userinsql, $userinparams, $contentinsql, $contentinparams) {
        global $DB;

        $sqlcontentin = " AND contentid $contentinsql";
        $params = array_merge($userinparams, $contentinparams);

        // For flag, if deleting user belong to userid field, remove the record.
        $DB->delete_records_select('openstudio_flags', "userid $userinsql $sqlcontentin", $params);

        // If deleting user belong to personid field, then change the owner to admin user.
        $DB->set_field_select('openstudio_flags', 'personid', get_admin()->id, "personid $userinsql $sqlcontentin",
            $params);
    }

    /**
     * Delete comments.
     *
     * @param array $userinsql
     * @param array $userinparams
     * @param array $contentinsql
     * @param array $contentinparams
     * @param \context_module $context
     * @param int $userids
     */
    private static function delete_comments_for_users($userinsql, $userinparams, $contentinsql,
            $contentinparams, $context, $userids) {
        global $DB;
        $fs = get_file_storage();
        $params = array_merge($userinparams, $contentinparams);
        $useridsin = implode(',', $userids);
        $adminid = get_admin()->id;
        // Update comments to empty if has reply.
        $sql = "SELECT c1.id
                  FROM {openstudio_comments} c1
                 WHERE c1.contentid $contentinsql
                       AND c1.userid $userinsql
                       AND c1.id IN (SELECT c2.inreplyto 
                                       FROM {openstudio_comments} c2
                                      WHERE c2.userid IN ($useridsin)
                                           AND c2.deletedby IS NULL 
                                           AND inreplyto IS NOT NULL)";
        $commentidshasreply = array_keys($DB->get_records_sql($sql, $params));

        // Select comment has self reply.
        $commentidshasselfreply = [];
        $commentidshasselfreplyin = implode(',', $commentidshasreply);
        if($commentidshasselfreplyin) {
            $sql = "SELECT c.id
                      FROM {openstudio_comments} c
                     WHERE c.id IN ($commentidshasselfreplyin)
                           AND (SELECT COUNT(*)
                                 FROM {openstudio_comments} c1
                                WHERE c1.inreplyto = c.id AND c1.userid != c.userid) = 0";
            $commentidshasselfreply = array_keys($DB->get_records_sql($sql));
        }
        // Select comments that can be deleted (another user didn't reply on it).
        $sql = "SELECT c1.id
                  FROM {openstudio_comments} c1
                 WHERE c1.contentid $contentinsql
                       AND c1.userid $userinsql
                       AND c1.id NOT IN (SELECT c2.inreplyto 
                                           FROM {openstudio_comments} c2
                                          WHERE c2.userid IN ($useridsin)
                                                   AND c2.deletedby IS NULL 
                                                   AND inreplyto IS NOT NULL)";
        $commentidsnothasreply = array_keys($DB->get_records_sql($sql, $params));
        if ($commentidshasreply) {
            $commenttext = get_string('deletedbyrequest', 'openstudio');
            foreach ($commentidshasreply as $c) {
                $defaultcomments = new \stdClass();
                $defaultcomments->id = $c;
                $defaultcomments->userid = $adminid;
                $defaultcomments->commenttext = $commenttext;
                $DB->update_record('openstudio_comments', $defaultcomments);
                $fs->delete_area_files($context->id, 'mod_openstudio', 'contentcomment', $c);
            }
        }
        $commentidsnothasreply = array_merge($commentidsnothasreply, $commentidshasselfreply);
        if ($commentidsnothasreply) {
            list($commentinsql, $commentinparams) = $DB->get_in_or_equal($commentidsnothasreply, SQL_PARAMS_NAMED);
            foreach ($commentidsnothasreply as $c) {
                $fs->delete_area_files($context->id, 'mod_openstudio', 'contentcomment', $c);
            }
            $DB->delete_records_select('openstudio_comments', "id $commentinsql", $commentinparams);
        }
        // Update field deleted by to adminid of comments of other user.
        $DB->set_field_select('openstudio_comments', 'deletedby', $adminid, 'deletedby ' . $userinsql,
                $userinparams);
        // Update filed deleted by to adminid of old version contents.
        $DB->set_field_select('openstudio_content_versions', 'deletedby', $adminid,
                'deletedby ' . $userinsql, $userinparams);
    }

    /**
     * Delete trash files.
     *
     * @param array $userinsql
     * @param array $userinparams
     * @param int $openstudioid
     * @param \context_module $context
     * @param array $fileareas
     * @param int $userids
     */
    private static function delete_trash_files_for_users($userinsql, $userinparams, $openstudioid, $context, $fileareas, $userids) {
        global $DB;
        $fs = get_file_storage();
        list($user2insql, $user2inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete trash file.
        // Trash files are the file in content and have not been removed yet after edited from user.
        $trashfilesql = "SELECT DISTINCT f.itemid
                                    FROM {files} f
                                   WHERE f.component = 'mod_openstudio' AND f.userid $userinsql AND f.contextid = :contextid
                                     AND f.itemid NOT IN (SELECT oc.fileid
                                                            FROM {openstudio_contents} oc
                                                            JOIN {files} f ON f.itemid = oc.fileid AND f.component = 'mod_openstudio'
                                                            JOIN {openstudio} o ON o.id = oc.openstudioid
                                                           WHERE o.id = :openstudioid AND oc.userid $user2insql)";
        $params = ['contextid' => $context->id, 'openstudioid' => $openstudioid];
        $trashfileparams = array_merge($params, $userinparams);
        $trashfileparams = array_merge($trashfileparams, $user2inparams);
        $trashfiles = $DB->get_recordset_sql($trashfilesql, $trashfileparams);
        foreach ($trashfiles as $file) {
            foreach ($fileareas as $area) {
                $fs->delete_area_files($context->id, 'mod_openstudio', $area, $file->itemid);
            }
        }
        $trashfiles->close();
    }

    /**
     * Delete contents.
     *
     * @param array $userinsql
     * @param array $userinparams
     * @param int $openstudioid
     * @param \context_module $context
     * @param array $fileareas
     */
    private static function delete_content_for_users($userinsql, $userinparams,
            $openstudioid, $context, $fileareas) {
        global $DB;
        $fs = get_file_storage();
        // Get contents.
        $contentssql = "SELECT oc.id, oc.fileid
                          FROM {openstudio_contents} oc
                         WHERE openstudioid = :openstudioid AND userid $userinsql";
        $params = ['openstudioid' => $openstudioid];
        $params = array_merge($params, $userinparams);
        $contents = $DB->get_recordset_sql($contentssql, $params);
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
            $commentparams = ['contentid' => $content->id];
            $commentparams = array_merge($commentparams, $userinparams);
            $comments = $DB->get_records_select('openstudio_comments', "contentid = :contentid AND userid $userinsql",
                    $commentparams
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

    }

    /**
     * Delete tags.
     *
     * @param array $userinsql
     * @param array $userinparams
     * @param int $openstudioid
     * @param \context_module $context
     */
    private static function delete_tags_for_users($userinsql, $userinparams, $openstudioid, $context) {
        // Get content for tags.
        $contentstagsql = "SELECT oc.id
                             FROM {openstudio_contents} oc
                            WHERE openstudioid = :openstudioid AND userid $userinsql";
        $params = ['openstudioid' => $openstudioid];
        $params = array_merge($params, $userinparams);

        // Delete all tags.
        \core_tag\privacy\provider::delete_item_tags_select($context, 'mod_openstudio',
                'openstudio_contents', "IN ($contentstagsql)", $params);
    }

    /**
     * Get the appropriate description based on the visibility value.
     *
     * Positive numbers are a value based on one of the visibility constants.
     * Negative numbers are the ID of the group, we will fetch the group's name.
     *
     * @param int $visibility Visibility constant, or negative group ID.
     * @return string The appropriate visibility description.
     */
    private static function get_visibility_string(int $visibility) {
        global $DB;
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'mod_openstudio', 'groupnames');
        if ($visibility < 0) {
            if (!$visiblegroup = $cache->get($visibility)) {
                $visiblegroup = $DB->get_field('groups', 'name', ['id' => (0 - $visibility)]);
                $cache->set($visibility, $visiblegroup);
            }
            return get_string('privacy:visibility:group', 'mod_openstudio', $visiblegroup);
        } else {
            return get_string('privacy:visibility:' . $visibility, 'mod_openstudio');
        }
    }
}
