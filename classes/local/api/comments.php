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

defined('MOODLE_INTERNAL') || die();

class comments {

    /**
     * Create new comment.
     *
     * @param int $contentid Content to associate comment with.
     * @param int $userid Creator of the comment.
     * @param string $comment Comment text.
     * @param int $folderid The ID of the folder the content belongs to.
     * @param object $file File upload information.
     * @param object $context Moodle context during slot creation.
     * @param int $inreplyto The ID of the comment being replied to
     * @return int Returns ID of inserted comment record.
     */
    public static function create($contentid, $userid, $comment, $folderid = null,
            $file = null, $context = null, $inreplyto = null, $cm = null) {
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

            if (($file !== null) && ($context !== null) && $commentid) {
                file_save_draft_area_files($file['id'], $context->id, 'mod_openstudio', 'contentcomment', $commentid);
            }

            // Index new comments for search.
            if ($cm != null) {
                $newcontentdata = content::get_record($userid, $contentid);
                search::update($cm, $newcontentdata);
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
     * @param int $userid If specified, also return a flag indicating if this user has liked the comment.
     * @param int $limitnum Limit to this number of results.
     * @param bool $withdeleted If true, include deleted comments.
     * @return \Traversable|bool Return recordsert of comment record data with user details added.
     */
    static public function get_for_content($contentid, $userid = null, $limitnum = 0, $withdeleted = false) {
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
}
