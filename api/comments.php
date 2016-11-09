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

/**
 * Create new comment.
 *
 * @param int $slotid Slot to associate comment with.
 * @param int $userid Creator of the comment.
 * @param string $comment Comment text.
 * @param int $setid The ID of the set the slot belongs to.
 * @param object $file File upload information.
 * @param object $context Moodle context during slot creation.
 * @param int $inreplyto The ID of the comment being replied to
 * @return int Returns ID of inserted comment record.
 */
function studio_api_comments_create($slotid, $userid, $comment, $setid = null,
                                    $file = null, $context = null, $inreplyto = null) {
    global $DB;

    try {
        if (strlen($comment) > STUDIO_DEFAULT_SLOTCOMMENTLENGTH) {
            $comment = substr($comment, 0, STUDIO_DEFAULT_SLOTCOMMENTLENGTH)
                    . get_string('slotcommenttruncatedmessage', 'openstudio');
        }

        // Populate data.
        $insertdata = array();
        $insertdata['contentid'] = $slotid;
        $insertdata['userid'] = $userid;
        $insertdata['commenttext'] = $comment;
        $insertdata['timemodified'] = time();
        if ($inreplyto) {
            if (!$parent = $DB->get_record('openstudio_comments', array('id' => $inreplyto))) {
                throw new coding_exception('The comment being replied to does not exist.');
            }
            if ($parent->contentid != $slotid) {
                throw new coding_exception('A comment cannot reply to a comment for another slot.');
            }
            if ($parent->inreplyto) {
                throw new coding_exception('A comment cannot reply to a comment which is alreay a reply');
            }
            $insertdata['inreplyto'] = $inreplyto;
        }

        $commentid = $DB->insert_record('openstudio_comments', $insertdata);

        $data = array();
        if (($file !== null) && ($context !== null) && $commentid) {
            file_save_draft_area_files($file['id'], $context->id, 'mod_studio', 'slotcomment', $commentid);
        }

        // Update slot flag.
        studio_api_flags_toggle($slotid, STUDIO_PARTICPATION_FLAG_COMMENT, 'on', $userid, $setid);
        if ($setid) {
            studio_api_tracking_log_action($setid, STUDIO_TRACKING_MODIFY_SET, $userid);
        }

        return $commentid;
    } catch (Exception $e) {
        // Defaults to returning false.
    }

    return false;
}

/**
 * Delete comment.
 *
 * @param int $commentid Comment to delete.
 * @param int $userid User deleting the comment.
 * @param bool $checkpermissions If truem, permission check will occur.
 * @return bool Return true if delete is successful, false otherwise.
 */
function studio_api_comments_delete($commentid, $userid, $checkpermissions = false) {
    global $DB;

    try {
        $commentdata = $DB->get_record('openstudio_comments',
                array('id' => $commentid), '*', MUST_EXIST);

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
            throw new Exception('Failed to soft delete comment.');
        }

        return true;
    } catch (Exception $e) {
        // Defaults to returning false.
    }

    return false;
}

/**
 * Deletes all comments associated with the supplied slot id.
 * Soft delete is used.
 *
 * @param int $slotid
 * @param int $userid User deleting the comments.
 * @return array Return true if comments are deleted.
 */
function studio_api_comments_delete_all($slotid, $userid) {
    global $DB;

    $sql = <<<EOF
UPDATE {openstudio_comments}
   SET deletedby = ?,
       deletedtime = ?
 WHERE contentid = ?

EOF;

    try {
        $DB->execute($sql, array($userid, time(), $slotid));

        return true;
    } catch (Exception $e) {
        // Defaults to returning false.
    }

    return false;
}

function studio_api_comments_get_total_by_slot($slotid) {
    global $DB;

    $sql = <<<EOF
SELECT count(c.*)
  FROM {openstudio_comments} c
 WHERE c.contentid = ?
   AND c.deletedby IS NULL

EOF;

    return (int) $DB->count_records_sql($sql, array($slotid));
}

/**
 * Return count of all comments posted by a user for a given studio.
 *
 * @param int $studioid Studio to check count against.
 * @param int $userid User to check count against.
 * @return int Return comment count.
 */
function studio_api_comments_get_total_by_user($studioid, $userid) {
    global $DB;

    $sql = <<<EOF
SELECT count(c.*)
  FROM {openstudio_comments} c
  JOIN {openstudio_contents} s ON s.id = c.contentid AND s.openstudioid = ?
 WHERE c.userid = ?
   AND c.deletedby IS NULL

EOF;

    return (int) $DB->count_records_sql($sql, array($studioid, $userid));
}

/**
 * Return count of all comments posted by a user for a given studio.
 * Excluding user's own comments.
 *
 * @param int $studioid Studio to check count against.
 * @param int $userid User to check count against.
 * @return int Return comment count.
 */
function studio_api_comments_get_total_by_user_excluding_own_slots($studioid, $userid) {
    global $DB;

    $sql = <<<EOF
SELECT count(c.id)
  FROM {openstudio_comments} c
  JOIN {openstudio_contents} s ON s.id = c.contentid AND s.openstudioid = ?
 WHERE c.userid = ?
   AND c.deletedby IS NULL
   AND s.userid <> ?

EOF;

    return (int) $DB->count_records_sql($sql, array($studioid, $userid, $userid));
}

/**
 * Return count of all comments posted by a user for a given studio.
 * Excluding user's own comments.
 * Count is counted on a per slot basis.
 *
 * @param int $studioid Studio to check count against.
 * @param int $userid User to check count against.
 * @return int Return comment count.
 */
function studio_api_comments_get_total_distinct_by_user_excluding_own_slots($studioid, $userid) {
    global $DB;

    $sql = <<<EOF
SELECT count(DISTINCT s.id)
  FROM {openstudio_comments} c
  JOIN {openstudio_contents} s ON s.id = c.contentid AND s.openstudioid = ?
 WHERE c.userid = ?
   AND c.deletedby IS NULL
   AND s.userid <> ?

EOF;

    return (int) $DB->count_records_sql($sql, array($studioid, $userid, $userid));
}

/**
 * Get comment record.
 *
 * @param int $commentid Comment to get.
 * @return bool|object Return comment record data with user details added.
 */
function studio_api_comments_get($commentid, $userid = null, $includedeleted = false) {
    global $DB;

    $params = array();
    $params[] = STUDIO_PARTICPATION_FLAG_COMMENT_LIKE;

    $subquery1 = <<<EOF
        SELECT COUNT(*)
          FROM {openstudio_flags} f
         WHERE f.commentid = c.id
           AND f.flagid = ?
EOF;
    if ($userid) {
        $params[] = $userid;
        $params[] = STUDIO_PARTICPATION_FLAG_COMMENT_LIKE;
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
SELECT count(c.*)
  FROM {openstudio_comments} c
 WHERE c.contentid IN (SELECT cc.contentid
                      FROM {openstudio_comments} cc
                     WHERE cc.id = ?)

EOF;
    if (!$includedeleted) {
        $sql .= ' AND c.deletedby IS NULL ';
    }

    $commenttotals = (int) $DB->count_records_sql($sql, array($commentid));

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

    $commentsdata = $DB->get_records_sql($sql, $params);
    if ($commentsdata != false) {
        foreach ($commentsdata as $commentdata) {
            $commentdata->total = $commenttotals;
            return $commentdata;
        }
    }

    return false;
}

/**
 * Get all comments associated with a given slot.
 * Request can be paginated from a given comment id up to limit number.
 *
 * @param int $slotid Slot to get comments from.
 * @param int $fromcommentid Use for pagination.
 * @param int $limitnum Use for pagination.
 * @return object Return recordsert of comment record data with user details added.
 */
function studio_api_comments_get_all($slotid, $userid = null, $fromcommentid = null, $limitnum = 0, $withdeleted = false) {
    global $DB;

    $params = array();
    $params[] = STUDIO_PARTICPATION_FLAG_COMMENT_LIKE;

    $subquery1 = <<<EOF
        SELECT COUNT(*)
          FROM {openstudio_flags} f
         WHERE f.commentid = c.id
           AND f.flagid = ?
EOF;
    if ($userid) {
        $params[] = $userid;
        $params[] = STUDIO_PARTICPATION_FLAG_COMMENT_LIKE;
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

    $params[] = $slotid;

    $additionconditions = '';
    if (!$withdeleted) {
        $additionconditions .= ' AND deletedby IS NULL ';
    }
    if ($fromcommentid > 0) {
        $additionconditions .= ' AND c.id > ? ';
        $params[] = $fromcommentid;
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
 * Return if any the file attached to a slot comment record.
 *
 * @param int $commentid Slot comment id.
 * @return object Return comment file attachment record data.
 */
function studio_api_comments_get_attachment($commentid) {
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
