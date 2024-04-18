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
 * Class for Comments API functions.
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

use mod_openstudio\local\util;
use mod_openstudio\local\util\defaults;

defined('MOODLE_INTERNAL') || die();

class comments {

    public const COMMENT_TEXT_AREA = 'commenttext';

    /**
     * Create new comment.
     *
     * @param int $contentid Content to associate comment with.
     * @param int $userid Creator of the comment.
     * @param string $comment Comment text.
     * @param int $folderid The ID of the folder the content belongs to.
     * @param array $file File upload information.
     * @param object $context Moodle context during slot creation.
     * @param int $inreplyto The ID of the comment being replied to
     * @param int $commentitemid Comment text Item ID.
     * @return int Returns ID of inserted comment record.
     */
    public static function create($contentid, $userid, $comment, $folderid = null,
            $file = null, $context = null, $inreplyto = null, $cm = null, int $commentitemid = 0) {
        global $DB;

        try {
            if (strlen($comment) > util\defaults::CONTENTCOMMENTLENGTH) {
                $comment = substr($comment, 0, util\defaults::CONTENTCOMMENTLENGTH)
                        . get_string('contentcommenttruncatedmessage', 'openstudio');
            }

            // Populate data.
            $insertdata = array();
            $insertdata['contentid'] = $contentid;
            $insertdata['userid'] = $userid;
            $insertdata['commenttext'] = $comment;
            $insertdata['timemodified'] = time();
            if ($inreplyto) {
                if (!$parent = $DB->get_record('openstudio_comments', array('id' => $inreplyto))) {
                    throw new \coding_exception('The comment being replied to does not exist.');
                }
                if ($parent->contentid != $contentid) {
                    throw new \coding_exception('A comment cannot reply to a comment for another slot.');
                }
                if ($parent->inreplyto) {
                    throw new \coding_exception('A comment cannot reply to a comment which is alreay a reply');
                }
                $insertdata['inreplyto'] = $inreplyto;
            }

            $commentid = $DB->insert_record('openstudio_comments', (object) $insertdata);

            // Update comment text.
            if ($context instanceof \context_module && $commentid && $commentitemid > 0) {
                self::update_comment_text($commentid, $context->id, $insertdata['commenttext'], $commentitemid);
            }

            $isvalidattachment = is_array($file) && array_key_exists('id', $file) && $file['id'] > 0;
            if ($isvalidattachment && ($context !== null) && $commentid) {
                file_save_draft_area_files($file['id'], $context->id, 'mod_openstudio', 'contentcomment', $commentid);
                // Issue happened when user requests API, it does not clear the old files.
                // That issue causes multiple attachments, we only allow 1 audio attachment.
                self::clear_draft_area($file['id']);
            }

            // Update slot flag.
            flags::toggle($contentid, flags::COMMENT, 'on', $userid, $folderid);
            flags::comment_toggle($contentid, $commentid, $userid, 'on', false, flags::FOLLOW_CONTENT);
            if ($folderid) {
                tracking::log_action($folderid, tracking::MODIFY_FOLDER, $userid);
            }

            return $commentid;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * Delete comment.
     *
     * @param int $commentid Comment to delete.
     * @param int $userid User deleting the comment.
     * @param bool $checkpermissions If true, check that the user is the author of the comment.
     * @return bool Return true if delete is successful, false otherwise.
     */
    public static function delete($commentid, $userid, $checkpermissions = false) {
        global $DB;

        try {
            $commentdata = $DB->get_record('openstudio_comments', array('id' => $commentid), '*', MUST_EXIST);

            if ($checkpermissions) {
                if ($commentdata->userid != $userid) {
                    return false;
                }
            }

            // Populate data.
            $commentdata->deletedby = $userid;
            $commentdata->deletedtime = time();

            $result = $DB->update_record('openstudio_comments', $commentdata);
            if ($result === false) {
                throw new \Exception('Failed to soft delete comment.');
            }
            // Delete child comments with parent's ID.
            $DB->execute("UPDATE {openstudio_comments}
                            SET deletedby = ?, deletedtime = ?
                          WHERE inreplyto = ?
                 ", [$userid, time(), $commentid]);

            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * Deletes all comments associated with the supplied content id.
     * Soft delete is used.
     *
     * @param int $contentid
     * @param int $userid User deleting the comments.
     * @return bool Return true if comments are deleted.
     */
    public static function delete_all($contentid, $userid) {
        global $DB;

        $sql = <<<EOF
UPDATE {openstudio_comments}
   SET deletedby = ?,
       deletedtime = ?
 WHERE contentid = ?

EOF;

        try {
            $DB->execute($sql, array($userid, time(), $contentid));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the number of comments for a piece of content
     *
     * @param $contentid
     * @return int
     */
    public static function total_for_content($contentid) {
        global $DB;

        $sql = <<<EOF
SELECT count(c.id)
  FROM {openstudio_comments} c
 WHERE c.contentid = ?
   AND c.deletedby IS NULL

EOF;

        return (int) $DB->count_records_sql($sql, array($contentid));
    }

    /**
     * Return count of all comments posted by a user for a given studio.
     *
     * @param int $studioid Studio to check count against.
     * @param int $userid User to check count against.
     * @return int Return comment count.
     */
    public static function total_for_user($studioid, $userid, $excludeown = false) {
        global $DB;

        $params = [$studioid, $userid];
        $sql = <<<EOF
SELECT count(c.id)
  FROM {openstudio_comments} c
  JOIN {openstudio_contents} s ON s.id = c.contentid AND s.openstudioid = ?
 WHERE c.userid = ?
   AND c.deletedby IS NULL

EOF;
        if ($excludeown) {
            $sql .= ' AND s.userid != ?';
            $params[] = $userid;
        }

        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Get comment record, including number of likes, author details and deleting user's details if appropriate.
     *
     * @param int $commentid Comment to get.
     * @param int $userid If specified, also include the number of likes by this user.
     * @param bool $includedeleted If true, return the comment even if it's been deleted.
     * @return object|false Return comment record data with user details added.
     */
    public static function get($commentid, $userid = null, $includedeleted = false) {
        global $DB;

        $params = array();
        $params[] = flags::COMMENT_LIKE;

        // Subquery to count the number of times the comment has been "liked" overall.
        $subquery1 = <<<EOF
        SELECT COUNT(*)
          FROM {openstudio_flags} f
         WHERE f.commentid = c.id
           AND f.flagid = ?
EOF;
        if ($userid) {
            $params[] = $userid;
            $params[] = flags::COMMENT_LIKE;
            // Subquery to get whether the user has "liked" the comment. COUNT(*) will either be 0 or 1.
            $subquery2 = <<<EOF
            , (
            SELECT COUNT(*)
              FROM {openstudio_flags} f1
             WHERE f1.userid = ?
               AND f1.commentid = c.id
               AND f1.flagid = ?
            ) AS userhasflagged
EOF;
        } else {
            $subquery2 = '';
        }

        $sql = <<<EOF
    SELECT c.*, u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
           u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
           du.firstname AS delfirstname, du.lastname AS dellastname, du.middlename AS delmiddlename,
           du.firstnamephonetic AS delfirstnamephonetic, du.lastnamephonetic AS dellastnamephonetic,
           du.alternatename AS delalternatename,
           ({$subquery1}) as flagcount{$subquery2}
      FROM {openstudio_comments} c
      JOIN {user} u ON u.id = c.userid
 LEFT JOIN {user} du ON du.id = c.deletedby
     WHERE c.id = ?

EOF;
        if (!$includedeleted) {
            $sql .= ' AND c.deletedby IS NULL ';
        }

        $params[] = $commentid;

        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Get all comments associated with a given slot.
     * Request can be paginated from a given comment id up to limit number.
     *
     * @param int $contentid Slot to get comments from.
     * @param int|null $userid If specified, also return a flag indicating if this user has liked the comment.
     * @param int $limitnum Limit to this number of results.
     * @param bool $withdeleted If true, include deleted comments.
     * @param int $groupingid If there's a grouping ID, filter the comments by group.
     * @param int|null $visibility content::VISIBILITY_* constant respresenting the stream we are viewing.
     * @param bool $canmanagecontent User has permission manage content.
     * @return \Traversable|bool Return recordsert of comment record data with user details added.
     */
    public static function get_for_content(int $contentid, ?int $userid = null, int $limitnum = 0,
            bool $withdeleted = false, int $groupingid = 0, ?int $visibility = null, $canmanagecontent = true): \Traversable|bool {
        global $DB;

        $params = array();
        $params[] = flags::COMMENT_LIKE;

        $subquery1 = <<<EOF
        SELECT COUNT(*)
          FROM {openstudio_flags} f
         WHERE f.commentid = c.id
           AND f.flagid = ?
EOF;
        if ($userid) {
            $params[] = $userid;
            $params[] = flags::COMMENT_LIKE;
            $subquery2 = <<<EOF
            , (
            SELECT COUNT(*)
              FROM {openstudio_flags} f1
             WHERE f1.userid = ?
               AND f1.commentid = c.id
               AND f1.flagid = ?
            ) AS userhasflagged
EOF;
        } else {
            $subquery2 = '';
        }

        // Filter comments if the active user does not have content management permissions
        // and belongs to a different group than the comment creator within the same grouping.
        $groupquery = '';
        if ($userid && $groupingid > 0) {
            if ($visibility == content::VISIBILITY_ALLGROUPS) {
                if (!$canmanagecontent) {
                    $groupquery = self::get_sql_check_same_group('c');
                    $params[] = $groupingid;
                    $params[] = $userid;
                }
            }
        }

        $params[] = $contentid;

        $additionconditions = '';
        if (!$withdeleted) {
            $additionconditions .= ' AND deletedby IS NULL ';
        }
        $sql = <<<EOF
   SELECT c.id, c.*, u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
          u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
          du.firstname AS delfirstname, du.lastname AS dellastname, du.middlename AS delmiddlename,
          du.firstnamephonetic AS delfirstnamephonetic, du.lastnamephonetic AS dellastnamephonetic,
          du.alternatename AS delalternatename,
          ({$subquery1}) AS flagcount{$subquery2}
     FROM {openstudio_comments} c
     JOIN {user} u ON u.id = c.userid
LEFT JOIN {user} du ON du.id = c.deletedby
     {$groupquery}
    WHERE c.contentid = ?
         {$additionconditions}
ORDER BY timemodified ASC

EOF;

        $commentsdata = $DB->get_recordset_sql($sql, $params, 0, $limitnum);
        if ($commentsdata->valid()) {
            return $commentsdata;
        }

        return false;
    }

    /**
     * Return if any the file attached to a comment record.
     *
     * @param int $commentid Comment id.
     * @return object|bool Comment file attachment record data.
     */
    public static function get_attachment($commentid) {
        global $DB;

        $sql = <<<EOF
SELECT *
  FROM {files}
 WHERE component = ?
   AND filearea = ?
   AND itemid = ?
   AND filename <> '.'
   AND filesize > 0

EOF;

        return $DB->get_record_sql($sql, array('mod_openstudio', 'contentcomment', $commentid));
    }

    /**
     * Get all comments by content id.
     *
     * @param int $cmdid course module ID
     * @param int $contentid content ID
     * @param object $permissions The permission object for the given user/view.
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_comments_by_contentid(int $cmid, int $contentid, object $permissions): array {
        global $DB, $USER, $PAGE;
        $context = \context_module::instance($cmid);
        $userfields = \core_user\fields::get_picture_fields();
        $arrayuserfield = [];
        foreach ($userfields as $userfield) {
            $arrayuserfield[] = "u.{$userfield}";
        }
        $sqluser = implode(', ', $arrayuserfield);
        $params = [];
        $groupquery = '';
        // Filter comments if the active user does not have content management permissions
        // and belongs to a different group than the comment creator within the same grouping.
        if ($permissions->groupingid > 0 && !$permissions->managecontent) {
            $content = $DB->get_record('openstudio_contents', ['id' => $contentid]);
            if ($content->visibility == content::VISIBILITY_ALLGROUPS) {
                $groupquery = self::get_sql_check_same_group('oc');
                $params[] = $permissions->groupingid;
                $params[] = $permissions->activeuserid;
            }
        }
        $params[] = $contentid;
        $sql = "SELECT oc.id as commentid, oc.commenttext, oc.contentid, oc.userid, oc.timemodified, " . $sqluser .
                " FROM {openstudio_comments} oc
            INNER JOIN {user} u ON oc.userid = u.id
                {$groupquery}
                 WHERE oc.deletedby IS NULL
                       AND oc.contentid = ?
              ORDER BY oc.timemodified DESC";
        $comments = $DB->get_records_sql($sql, $params);
        $result = [];
        if ($comments && count($comments) > 0) {
            $lastestcomment = util::get_lastest_comment_by_contentid($USER->id, $contentid);
            foreach ($comments as $comment) {
                $commenturl = new \moodle_url('/mod/openstudio/content.php',
                    ['id' => $cmid, 'sid' => $contentid, 'vuid' => $comment->userid]);
                $data = new \stdClass();
                $data->id = $comment->commentid;
                $data->isnewcomment = false;
                if (isset($lastestcomment[$comment->commentid])) {
                    $data->isnewcomment = true;
                }
                $comment->commenttext = static::filter_comment_text($comment->commenttext, $comment->id, $context);
                $comment->commenttext = static::nice_shorten_text($comment->commenttext);
                $data->comment = "'" . strip_tags($comment->commenttext) . "'";
                $data->contentid = $comment->contentid;
                $data->userid = $comment->userid;
                $data->fullname = fullname($comment);
                $data->commenturl = $commenturl . "#openstudio-comment-" . $comment->commentid;
                $data->timemodified = util::get_time_since_readable($USER->id, $comment->timemodified);

                // User picture.
                $renderer = util::get_renderer();
                $data->userpicturehtml = util::render_user_avatar($renderer, $comment);

                $result[] = $data;
            }
        }
        return $result;
    }

    /**
     * Updates the message field of a comment entry. This is necessary in some cases where
     * the user includes images etc. in the message; these are initially included using
     * a draft URL which has to be changed to a special relative path on convert, and we
     * can't do that until the comment ID is known. Additionally, we don't have a comment object
     * at that point, hence use of static function.
     *
     * @param int $commentid ID of comment to update.
     * @param int $contextid ID of context.
     * @param string|null $commenttext Content of comment.
     * @param int $commentitemid Comment draft Item ID.
     */
    private static function update_comment_text(int $commentid, int $contextid, ?string $commenttext,
            int $commentitemid = 0): void {
        global $DB, $CFG;
        if ($commenttext === null) {
            return;
        }
        $fileoptions = [
                'subdirs' => false,
                'maxbytes' => $CFG->maxbytes ?? defaults::MAXBYTES,
                'maxfiles' => EDITOR_UNLIMITED_FILES,
        ];
        $newtext = file_save_draft_area_files($commentitemid, $contextid, 'mod_openstudio',
                self::COMMENT_TEXT_AREA, $commentid, $fileoptions, $commenttext);
        if ($commenttext !== $newtext) {
            $DB->set_field('openstudio_comments', self::COMMENT_TEXT_AREA, $newtext, [
                    'id' => $commentid,
            ]);
        }
        self::clear_draft_area($commentitemid);
    }

    /**
     * Shorten comment text (Refer from ForumNG).
     *
     * @param string|null $text
     * @param int $length Maximum length, if 0 then no need shorten.
     * @return string|null
     */
    public static function nice_shorten_text(?string $text, int $length = 0): ?string {
        $text = htmlentities($text, ENT_QUOTES, 'utf-8');
        $text = str_replace('&nbsp;', ' ', $text);
        $text = html_entity_decode($text);
        $text = trim($text);
        // Replace image tag by placeholder text.
        $text = preg_replace('/<img.*?>/', get_string('commentimageplaceholder', 'mod_openstudio'), $text);
        // Trim the multiple spaces to single space and multiple lines to one line.
        $text = preg_replace('!\s+!', ' ', $text);
        $summary = $text;
        if ($length > 0) {
            $summary = shorten_text($text, $length);
        }
        $summary = preg_replace('~\s*\.\.\.(<[^>]*>)*$~', '$1', $summary);
        $dots = $summary != $text ? '...' : '';
        return $summary . $dots;
    }

    /**
     * Filter comment text.
     *
     * @param string $commenttext Text of comment.
     * @param int $commentid ID of comment.
     * @param \context_module $context context module.
     * @param bool $isstriplink Strip link the text.
     * @return string
     */
    public static function filter_comment_text(string $commenttext, int $commentid,
            \context_module $context, bool $isstriplink = false): string {
        $commenttext = file_rewrite_pluginfile_urls($commenttext, 'pluginfile.php', $context->id,
                'mod_openstudio', self::COMMENT_TEXT_AREA, $commentid);
        $commenttext = format_text($commenttext, FORMAT_HTML, ['context' => $context->id]);
        if ($isstriplink) {
            $commenttext = format_string($commenttext, true);
        }
        return $commenttext;
    }

    /**
     * Delete all files from a particular draft file area for the current user.
     *
     * @param int $draftid The itemid for the draft area.
     */
    private static function clear_draft_area(int $draftid): void {
        global $USER;
        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftid);
    }

    /**
     * Get all users commented on a content.
     *
     * @param int $contentid
     * @param int $defaultvalue
     * @return array
     */
    public static function get_all_users_from_content_id(int $contentid, int $defaultvalue): array {
        global $DB;

        $sql = "
                SELECT DISTINCT oc.userid
                  FROM {openstudio_comments} oc
                 WHERE oc.contentid = ?
            ";

        $users = $DB->get_records_sql($sql, [$contentid]);

        $result = [];

        if (!empty($users)) {
            foreach ($users as $user) {
                if (isset($result[$user->userid])) {
                    continue;
                }
                $result[$user->userid] = $defaultvalue;
            }
        }

        return $result;
    }

    /**
     * Get all users replied on a root comment.
     *
     * @param int $commentid
     * @param int $defaultvalue
     * @return array
     */
    public static function get_all_users_from_root_comment_id(int $commentid, int $defaultvalue): array {
        global $DB;

        $sql = "
                SELECT DISTINCT oc.userid
                  FROM {openstudio_comments} oc
                 WHERE oc.inreplyto = ?
            ";

        $users = $DB->get_records_sql($sql, [$commentid]);

        $result = [];

        if (!empty($users)) {
            foreach ($users as $user) {
                if (isset($result[$user->userid])) {
                    continue;
                }
                $result[$user->userid] = $defaultvalue;
            }
        }

        return $result;
    }

    /**
     * Function return JOIN sql to check same group.
     *
     * @param string $alias
     * @return string
     */
    private static function get_sql_check_same_group(string $alias): string {
        $sql = <<<EOF
                JOIN {groups_members} gm1 on gm1.userid = %s.userid
                JOIN {groupings_groups} gg ON gg.groupid = gm1.groupid AND gg.groupingid = ?
                JOIN {groups_members} gm2 ON gm2.groupid = gm1.groupid AND gm2.userid = ?
EOF;
        return sprintf($sql, $alias) ;
    }
}
