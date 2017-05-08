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
 * Notification API classes
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

use mod_openstudio\local\notifications\notifiable;
use mod_openstudio\local\notifications\notification;
use mod_openstudio\local\notifications\notification_skeleton;
use mod_openstudio\local\util;

defined('MOODLE_INTERNAL') || die();

class notifications {

    /** The field to check for deleting unread notifications */
    const FIELD_UNREAD = 'timecreated';

    /** The field to check for deleting read notifications */
    const FIELD_READ = 'timeread';

    /**
     * This function returns the social data count for the requested list
     * of content ids.  The counts are in the context of the given user id.
     *
     * @param int $userid The user to get social data for.
     * @param array $contents List of content ids to get social data from.
     * @return array Return array of social data for list of contents.
     */
    public static function get_activities($userid, $contents) {

        // If there are no contents provided, exit now.
        if (empty($contents)) {
            return false;
        }

        global $DB;

        $sql = <<<EOF
SELECT sf.contentid AS contentid, sf.folderid,
       (        SELECT count(sc1.id)
                  FROM {openstudio_comments} sc1
                 WHERE sc1.contentid = sf.contentid
                   AND sc1.deletedtime IS NULL
                   AND sc1.userid != ?
        AND NOT EXISTS (SELECT 1
                          FROM {openstudio_flags} sc1f
                         WHERE sc1f.contentid = sc1.contentid
                           AND sc1f.flagid = 6
                           AND sc1f.userid = ?)) AS commentsnewcontent,
       (    SELECT count(sc2.id)
              FROM {openstudio_comments} sc2
             WHERE sc2.contentid = sf.contentid
               AND sc2.deletedtime IS NULL
               AND sc2.userid != ?
        AND EXISTS (SELECT 1
                      FROM {openstudio_flags} sc2f
                     WHERE sc2f.contentid = sc2.contentid
                       AND sc2f.flagid = 6
                       AND sc2f.userid = ?
                       AND sc2.timemodified > sc2f.timemodified)) AS commentsnew,
       (    SELECT count(sc3.id)
              FROM {openstudio_comments} sc3
             WHERE sc3.contentid = sf.contentid
               AND sc3.deletedtime IS NULL
        AND (EXISTS (SELECT 1
                      FROM {openstudio_flags} sc3f
                     WHERE sc3f.contentid = sc3.contentid
                       AND sc3f.flagid = 6
                       AND sc3f.userid = ?
                       AND sc3.timemodified <= sc3f.timemodified) OR sc3.userid = ?)) AS commentsold,
       (         SELECT count(sf5_1.id)
                   FROM {openstudio_flags} sf5_1
                  WHERE sf5_1.contentid = sf.contentid
                    AND sf5_1.flagid = 5
                    AND sf5_1.userid != ?
        AND NOT EXISTS (SELECT 1
                          FROM {openstudio_flags} sf5_1f
                         WHERE sf5_1f.contentid = sf5_1.contentid
                           AND sf5_1f.flagid = 6
                           AND sf5_1f.userid = ?)) AS inspirednewcontent,
       (    SELECT count(sf5_2.id)
              FROM {openstudio_flags} sf5_2
             WHERE sf5_2.contentid = sf.contentid
               AND sf5_2.flagid = 5
               AND sf5_2.userid != ?
        AND EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_2f
                     WHERE sf5_2f.contentid = sf5_2.contentid
                       AND sf5_2f.flagid = 6
                       AND sf5_2f.userid = ?
                       AND sf5_2.timemodified > sf5_2f.timemodified)) AS inspirednew,
       (    SELECT count(sf5_3.id)
              FROM {openstudio_flags} sf5_3
             WHERE sf5_3.contentid = sf.contentid
               AND sf5_3.flagid = 5
        AND (EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_3f
                     WHERE sf5_3f.contentid = sf5_3.contentid
                       AND sf5_3f.flagid = 6
                       AND sf5_3f.userid = ?
                       AND sf5_3.timemodified <= sf5_3f.timemodified) OR sf5_3.userid = ?)) AS inspiredold,
       (         SELECT count(sf5_1.id)
                   FROM {openstudio_flags} sf5_1
                  WHERE sf5_1.contentid = sf.contentid
                    AND sf5_1.flagid = 4
                    AND sf5_1.userid != ?
        AND NOT EXISTS (SELECT 1
                          FROM {openstudio_flags} sf5_1f
                         WHERE sf5_1f.contentid = sf5_1.contentid
                           AND sf5_1f.flagid = 6
                           AND sf5_1f.userid = ?)) AS mademelaughnewcontent,
       (    SELECT count(sf5_2.id)
              FROM {openstudio_flags} sf5_2
             WHERE sf5_2.contentid = sf.contentid
               AND sf5_2.flagid = 4
               AND sf5_2.userid != ?
        AND EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_2f
                     WHERE sf5_2f.contentid = sf5_2.contentid
                       AND sf5_2f.flagid = 6
                       AND sf5_2f.userid = ?
                       AND sf5_2.timemodified > sf5_2f.timemodified)) AS mademelaughnew,
       (    SELECT count(sf5_3.id)
              FROM {openstudio_flags} sf5_3
             WHERE sf5_3.contentid = sf.contentid
               AND sf5_3.flagid = 4
        AND (EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_3f
                     WHERE sf5_3f.contentid = sf5_3.contentid
                       AND sf5_3f.flagid = 6
                       AND sf5_3f.userid = ?
                       AND sf5_3.timemodified <= sf5_3f.timemodified) OR sf5_3.userid = ?)) AS mademelaughold,
       (         SELECT count(sf5_1.id)
                   FROM {openstudio_flags} sf5_1
                  WHERE sf5_1.contentid = sf.contentid
                    AND sf5_1.flagid = 2
                    AND sf5_1.userid != ?
        AND NOT EXISTS (SELECT 1
                          FROM {openstudio_flags} sf5_1f
                         WHERE sf5_1f.contentid = sf5_1.contentid
                           AND sf5_1f.flagid = 6
                           AND sf5_1f.userid = ?)) AS favouritenewcontent,
       (    SELECT count(sf5_2.id)
              FROM {openstudio_flags} sf5_2
             WHERE sf5_2.contentid = sf.contentid
               AND sf5_2.flagid = 2
               AND sf5_2.userid != ?
        AND EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_2f
                     WHERE sf5_2f.contentid = sf5_2.contentid
                       AND sf5_2f.flagid = 6
                       AND sf5_2f.userid = ?
                       AND sf5_2.timemodified > sf5_2f.timemodified)) AS favouritenew,
       (    SELECT count(sf5_3.id)
              FROM {openstudio_flags} sf5_3
             WHERE sf5_3.contentid = sf.contentid
               AND sf5_3.flagid = 2
        AND (EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_3f
                     WHERE sf5_3f.contentid = sf5_3.contentid
                       AND sf5_3f.flagid = 6
                       AND sf5_3f.userid = ?
                       AND sf5_3.timemodified <= sf5_3f.timemodified) OR sf5_3.userid = ?)) AS favouriteold
  FROM {openstudio_flags} sf

EOF;

        $sqlparams = array();
        for ($counter = 0; $counter < 24; $counter++) {
            $sqlparams[] = $userid;
        }

        if (!empty($contents)) {
            list($filtercontentdatasql, $filtercontentdataparams) = $DB->get_in_or_equal($contents);
            $sqlparams = array_merge($sqlparams, $filtercontentdataparams);
            $sqlparams = array_merge($sqlparams, $filtercontentdataparams);
            $sql .= " WHERE (sf.contentid {$filtercontentdatasql}) OR (sf.folderid {$filtercontentdatasql}) ";
        }
        $sql .= " GROUP BY sf.contentid, sf.folderid ";

        $results = $DB->get_recordset_sql($sql, $sqlparams);
        if (!$results->valid()) {
            return false;
        }

        $contentdata = array();
        $folderdata = array();
        foreach ($results as $content) {
            $contentdata[$content->contentid] = $content;
            $contentdata[$content->contentid]->folder = false;

            if ($content->folderid > 0) {
                if (array_key_exists($content->folderid, $folderdata)) {
                    $folderupdate = $folderdata[$content->folderid];
                    $folderupdate->contents += 1;
                    $folderupdate->commentsnewcontent += $content->commentsnewcontent;
                    $folderupdate->commentsnew += $content->commentsnew;
                    $folderupdate->commentsold += $content->commentsold;
                    $folderupdate->inspirednewcontent += $content->inspirednewcontent;
                    $folderupdate->inspirednew += $content->inspirednew;
                    $folderupdate->inspiredold += $content->inspiredold;
                    $folderupdate->mademelaughnewcontent += $content->mademelaughnewcontent;
                    $folderupdate->mademelaughnew += $content->mademelaughnew;
                    $folderupdate->mademelaughold += $content->mademelaughold;
                    $folderupdate->favouritenewcontent += $content->favouritenewcontent;
                    $folderupdate->favouritenew += $content->favouritenew;
                    $folderupdate->favouriteold += $content->favouriteold;
                    $folderdata[$content->folderid] = $folderupdate;
                } else {
                    $folderdata[$content->folderid] = (object) [
                        'contentid' => $content->contentid,
                        'folderid' => $content->folderid,
                        'contents' => 1,
                        'commentsnewcontent' => $content->commentsnewcontent,
                        'commentsnew' => $content->commentsnew,
                        'commentsold' => $content->commentsold,
                        'inspirednewcontent' => $content->inspirednewcontent,
                        'inspirednew' => $content->inspirednew,
                        'inspiredold' => $content->inspiredold,
                        'mademelaughnewcontent' => $content->mademelaughnewcontent,
                        'mademelaughnew' => $content->mademelaughnew,
                        'mademelaughold' => $content->mademelaughold,
                        'favouritenewcontent' => $content->favouritenewcontent,
                        'favouritenew' => $content->favouritenew,
                        'favouriteold' => $content->favouriteold
                    ];
                }
            }
        }

        foreach ($folderdata as $folderdataid => $folderdataitem) {
            if (array_key_exists($folderdataid, $contentdata)) {
                $contentdata[$folderdataid]->folder = $folderdataitem;
            }
        }

        return $contentdata;
    }

    /**
     * Return a list of userids filtered for those who have permission to view the content.
     *
     * @param \moodle_recordset $users A recordset with a userid field, the users who we might notify.
     * @param \cm_info $cm The course module for the openstudio instance.
     * @param object $instance The openstudio record.
     * @param object $course The course record.
     * @param object $content The content record.
     * @return int[] The userids who should be notified.
     */
    private static function get_notified_users($users, $cm, $instance, $course, $content) {
        $notifyids = [];
        if ($users && $users->valid()) {
            foreach ($users as $user) {
                $permissions = util::check_permission($cm, $instance, $course, $user->userid);
                if (util::can_read_content($instance, $permissions, $content)) {
                    $notifyids[] = $user->userid;
                }
            }
            $users->close();
        }
        return $notifyids;
    }

    /**
     * Handle an event that requires a notification be generated.
     *
     * @param notifiable $event
     */
    public static function handle_event(notifiable $event) {
        global $DB;
        $skeleton = $event->get_notification_data();
        $type = $event->get_notification_type();
        $userids = [];
        $cmid = $event->get_context()->instanceid;
        $cminfo = get_fast_modinfo($event->other['courseid']);
        $cm = $cminfo->get_cm($cmid);
        $instance = $DB->get_record('openstudio', array('id' => $cm->instance), '*', MUST_EXIST);
        $content = content::get($skeleton->contentid);

        $userfrom = $DB->get_record('user', ['id' => $skeleton->userfrom]);
        $link = \html_writer::link($skeleton->url, $content->name);
        $skeleton->message = \html_writer::tag('strong', fullname($userfrom)) . ' ' . $skeleton->message . ' ' . $link;

        if ($type === notifiable::COMMENT || $type === notifiable::CONTENT) {

            if ($type === notifiable::COMMENT) {
                // Notify users who are following the specific comment.
                $followers = flags::get_comment_flags($skeleton->commentid, flags::FOLLOW_CONTENT);
                $userids = self::get_notified_users($followers, $cm, $instance, $cminfo->get_course(), $content);
            }

            // Notify users who are following the content post.
            $followers = flags::get_content_flags($skeleton->contentid, flags::FOLLOW_CONTENT);
            $userids = array_merge($userids, self::get_notified_users($followers, $cm, $instance, $cminfo->get_course(), $content));

        } else if ($type === notifiable::TUTOR) {
            if ($content->visibility == content::VISIBILITY_TUTOR) {
                // If the content was shared with tutor, notify the tutors.
                $tutorroles = explode(',', $DB->get_field('openstudio', 'tutorroles', ['id' => $content->openstudioid]));
                list($tutorsql, $tutorparams) = $DB->get_in_or_equal($tutorroles);
                // Select all users who have the tutor role assigned at course level, in the course contaning the studio,
                // that contains the content post that triggered this event, and who are in a group with the user, in a grouping
                // assigned to this studio instance.
                $sql = "SELECT ra.userid
                          FROM {role_assignments} ra
                          JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                          JOIN {course} c ON c.id = ctx.instanceid
                          JOIN {openstudio} os ON c.id = os.course
                          JOIN {openstudio_contents} osc ON osc.openstudioid = os.id
                          JOIN {groups_members} tgm1 ON tgm1.userid = osc.userid
                          JOIN {groups} tg1 ON tg1.id = tgm1.groupid AND tg1.courseid = c.id
                          JOIN {groups_members} tgm2 ON tg1.id = tgm2.groupid AND ra.userid = tgm2.userid
                          JOIN {course_modules} cm ON cm.instance = os.id
                          JOIN {modules} m ON m.id = cm.module AND m.name = 'openstudio'
                          JOIN {groupings_groups} gg ON gg.groupingid = cm.groupingid AND gg.groupid = tg1.id
                         WHERE ra.roleid $tutorsql
                           AND osc.id = ?
                           AND tgm1.userid = ?";
                $params = array_merge($tutorparams, [$skeleton->contentid, $event->userid]);
                $tutors = $DB->get_recordset_sql($sql, $params);
                $userids = self::get_notified_users($tutors, $cm, $instance, $cminfo->get_course(), $content);
            }
        }
        self::create($skeleton, array_unique($userids));
    }

    /**
     * Create new notifications for the provided list of users.
     *
     * This could potentially create a lot of new records, so uses the bulk insert_records method.
     *
     * @param notification $data The skeleton record containing the common data for the notifications
     * @param int[] $userids The list of userids to create records for
     * @throws \dml_exception
     * @throws \coding_exception
     */
    private static function create(notification $data, array $userids) {
        global $DB;
        $records = [];
        foreach ($userids as $userid) {
            $duplicatewhere = "
                    userid = ?
                    AND userfrom = ?
                    AND message = ?
                    AND timeread IS NULL";
            $duplicateparams = [$userid, $data->userfrom, $data->message];
            if ($userid == $data->userfrom
                    || $DB->record_exists_select('openstudio_notifications', $duplicatewhere, $duplicateparams)) {
                continue;
            }
            $record = clone $data;
            $record->url = $record->url->out(false);
            $record->timecreated = time();
            $record->userid = $userid;
            $records[] = $record;
        }
        $DB->insert_records('openstudio_notifications', $records);
    }

    /**
     * Get the list of current notifications for the user.
     *
     * @param int $studioid
     * @param int $userid
     * @param int $limit The number of notifications to return.
     * @return notification[] Array of notification objects constructued from the database records.
     * @throws \dml_exception
     */
    public static function get_current($studioid, $userid, $limit = 0) {
        global $DB;
        $where = "userid = ?
                AND contentid IN (SELECT id FROM {openstudio_contents} WHERE openstudioid = ?)";
        $records = $DB->get_recordset_select(
                'openstudio_notifications', $where, [$userid, $studioid], 'timecreated DESC', '*', 0, $limit);
        $notifications = [];
        if ($records->valid()) {
            foreach ($records as $record) {
                $notifications[] = new notification($record);
            }
            $records->close();
        }
        return $notifications;
    }

    /**
     * Get the list of notifications created for the user since the provided threshold.
     *
     * @param int $studioid
     * @param int $userid
     * @param int $timesince
     * @return notification[] Array of notification objects constructued from the database records.
     * @throws \dml_exception
     */
    public static function get_recent($studioid, $userid, $timesince) {
        global $DB;
        $where = "userid = ?
                AND timecreated > ?
                AND contentid IN (SELECT id FROM {openstudio_contents} WHERE openstudioid = ?)";
        $records = $DB->get_recordset_select('openstudio_notifications', $where, [$userid, $timesince, $studioid]);
        $notifications = [];
        if ($records->valid()) {
            foreach ($records as $record) {
                $notifications[] = new notification($record);
            }
            $records->close();
        }
        return $notifications;
    }

    /**
     * Check that the notification is for the specified user.
     *
     * @param int $id The ID of the notification
     * @param int $userid The ID of the user to verify.
     * @return bool True if the notification belongs to the user.
     */
    public static function is_for_user($id, $userid) {
        global $DB;
        return $DB->record_exists('openstudio_notifications', ['id' => $id, 'userid' => $userid]);
    }

    /**
     * Mark the specified notification as read.
     *
     * @param int $id
     * @throws \dml_exception
     */
    public static function mark_read($id) {
        global $DB;
        $DB->set_field('openstudio_notifications', 'timeread', time(), ['id' => $id]);
    }

    /**
     * Delete the specified notification
     *
     * @param $id
     * @throws \dml_exception
     */
    public static function delete($id) {
        global $DB;
        $DB->delete_records('openstudio_notifications', ['id' => $id]);
    }

    /**
     * Delete all notifications older than the set thresholds.
     *
     * This deletes old notifications for all users.  The threshold is defined in the openstudio config settings, depending on
     * whether we are deleting read or unread notifications.
     *
     * @param string $field The name of the field to limit by (FIELD_UNREAD or FIELD_READ)
     * @throws \dml_exception
     */
    public static function delete_old($field) {
        global $DB;
        if ($field === self::FIELD_UNREAD) {
            $days = get_config('openstudio', 'notificationlimitunread');
        } else if ($field === self::FIELD_READ) {
            $days = get_config('openstudio', 'notificationlimitread');
        } else {
            throw new \coding_exception('$field must be notifications::FIELD_READ or notifications::FIELD_UNREAD');
        }
        $time = new \DateTime($days . ' days ago', \core_date::get_server_timezone_object());
        $DB->delete_records_select('openstudio_notifications', $field . '< ?', [$time->getTimestamp()]);
    }

    /**
     * Delete all notifications over the maximum limit per user.
     *
     * This is just to keep the notifications table tidy if there's a user who gets spammed with lots of notifications.
     * This shouldn't have to do much as delete_old will take care of most of the auto deletion, it's really just a failsafe.
     */
    public static function delete_max() {
        global $DB;
        $limit = get_config('openstudio', 'notificationlimitmax');
        // Get the user/studio combinations with more notifications than the limit.
        $sql = "SELECT n.userid as userid, c.openstudioid as studioid
                  FROM {openstudio_notifications} n
                  JOIN {openstudio_contents} c ON c.id = n.contentid
              GROUP BY n.userid, c.openstudioid
                HAVING COUNT(n.id) > ?";
        $users = $DB->get_records_sql($sql, [$limit]);
        $ids = [];
        foreach ($users as $user) {
            // Get the excess notifications for each user/studio, oldest first.
            $sql = "SELECT n.id
                      FROM {openstudio_notifications} n
                      JOIN {openstudio_contents} c ON c.id = n.contentid
                     WHERE n.userid = ? AND c.openstudioid = ?
                  ORDER BY timecreated DESC
                    OFFSET ?";
            $ids = array_merge($ids, $DB->get_fieldset_sql($sql, [$user->userid, $user->studioid, $limit]));
        }
        if (!empty($ids)) {
            list($dsql, $params) = $DB->get_in_or_equal($ids);
            $DB->delete_records_select('openstudio_notifications', 'id ' . $dsql, $params);
        }
    }

    /**
     * Delete unread notifications matching the supplied conditions.
     *
     * @param $where
     * @param $params
     */
    private static function delete_unread($where, $params) {
        global $DB;
        $where .= " AND timeread IS NULL";
        $DB->delete_records_select('openstudio_notifications', $where, $params);
    }

    /**
     * Delete unread notfications for a content post
     *
     * Cleans up notifications when a post is removed
     *
     * @param int $contentid
     */
    public static function delete_unread_for_post($contentid) {
        $where = "contentid = ?";
        $params = [$contentid];
        self::delete_unread($where, $params);
    }

    /**
     * Delete unread notifications for a flag.
     *
     * Cleans up notifications when a user un-flags a post.
     *
     * @param int $contentid
     * @param int $userfrom
     * @param int $flagid flags::* constant.
     */
    public static function delete_unread_for_flag($contentid, $userfrom, $flagid) {
        $where = "contentid = ?
              AND userfrom = ?
              AND flagid = ?";
        $params = [$contentid, $userfrom, $flagid];
        self::delete_unread($where, $params);
    }

    /**
     * Delete unread notifications for a comment.
     *
     * Cleans up notifications when a user deletes a comment.
     *
     * @param $commentid
     */
    public static function delete_unread_for_comment($commentid) {
        $where = "commentid = ?";
        $params = [$commentid];
        self::delete_unread($where, $params);
    }

    /**
     * Delete unread notifications for a comment.
     *
     * Cleans up notifications when a user deletes a comment.
     *
     * @param $commentid
     * @param $userfrom
     * @param $flagid
     */
    public static function delete_unread_for_comment_flag($commentid, $userfrom, $flagid) {
        $where = "commentid = ?
              AND userfrom = ?
              AND flagid = ?";
        $params = [$commentid, $userfrom, $flagid];
        self::delete_unread($where, $params);
    }

}
