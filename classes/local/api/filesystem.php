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
 * Filesystem API.
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

/**
 * API functions for filesystem API.
 *
 * @package mod_openstudio
 */
class filesystem {

    const COMPONENT = 'mod_openstudio';

    /**
     * This function removes all uploaded files associated with slots.
     * It is called when a studio instance is deleted.
     *
     * Warning: this is a destructive call and should be used carefully.
     *
     * @param int $contextid Module context id
     * @param int $studioid Module Studio instance id
     * @return bool Return true or false for success/failure
     */
    static public function remove_content_files($contextid, $studioid) {

        global $DB;

        try {
            // Find all slot and slot version files.
            $sql = <<<EOF
SELECT c.fileid, c.content, f.filearea
  FROM {openstudio_contents} c
  JOIN {files} f ON f.itemid = c.fileid
 WHERE c.openstudioid = ?
   AND f.component = ?
   AND f.contextid = ?
   AND (f.filearea = ? OR f.filearea = ? OR filearea = ?)

 UNION

SELECT cv.fileid, cv.content, f.filearea
  FROM {openstudio_content_versions} cv
  JOIN {openstudio_contents} c1 ON c1.id = cv.contentid
  JOIN {files} f ON f.itemid = cv.fileid
 WHERE c1.openstudioid = ?
   AND f.component = ?
   AND f.contextid = ?
   AND (f.filearea = ? OR f.filearea = ? OR filearea = ?)

EOF;

            $params = [$studioid, self::COMPONENT, $contextid, 'content', 'notebook', 'contentthumbnail', $studioid, self::COMPONENT,
                    $contextid, 'content', 'notebook', 'contentthumbnail'];
            $result = $DB->get_recordset_sql($sql, $params);
            if (!$result->valid()) {
                return false;
            }

            $fs = get_file_storage();

            foreach ($result as $record) {
                $file = $fs->get_file($contextid, self::COMPONENT, $record->filearea, $record->fileid, '/', $record->content);
                if ($file) {
                    $file->delete();
                }
            }

            $result->close();

            return true;
        } catch (\Exception $e) {
            // Defaults to returning false.
            return false;
        }
    }

    /**
     * This function removes all uploaded files associated with deleted slots.
     * Only files that have refcount less than or equal to 1 and slots that have
     * been deleted after 7 days are deleted.
     *
     * If no studioid is provided, all instances of studios will be processed.
     *
     * @return bool Return true or false for success/failure
     */
    public static function remove_deleted_files() {
        global $DB;

        try {
            $sql = <<<EOF
SELECT cm.*
  FROM {openstudio} s
  JOIN {course_modules} cm ON cm.instance = s.id
  JOIN {modules} m ON m.id = cm.module
 WHERE m.name = 'openstudio'

EOF;

            $cms = $DB->get_recordset_sql($sql);

            if (!$cms->valid()) {
                return false;
            }

            foreach ($cms as $cm) {
                $studioid = $cm->instance;
                $modulecontext = \context_module::instance($cm->id);
                $contextid = $modulecontext->id;

                $sql = <<<EOF
SELECT c.fileid, c.content, f.filearea
  FROM {openstudio_contents} c
  JOIN {openstudio_content_files} cf1 ON cf1.id = c.fileid
  JOIN {files} f ON f.itemid = c.fileid
 WHERE c.openstudioid = ?
   AND f.component = ?
   AND f.contextid = ?
   AND c.deletedby IS NOT NULL
   AND c.deletedtime < ?
   AND (f.filearea = ? OR f.filearea = ? OR filearea = ?)

 UNION

SELECT cv.fileid, cv.content, f.filearea
  FROM {openstudio_content_versions} cv
  JOIN {openstudio_content_files} cf2 ON cf2.id = cv.fileid
  JOIN {openstudio_contents} c1 ON c1.id = cv.contentid
  JOIN {files} f ON f.itemid = cv.fileid
 WHERE c1.openstudioid = ?
   AND f.component = ?
   AND f.contextid = ?
   AND cv.deletedby IS NOT NULL
   AND cv.deletedtime < ?
   AND (f.filearea = ? OR f.filearea = ? OR filearea = ?)

EOF;

                // Find time which is 7 days before current time.
                $old = (new \DateTime('7 days ago', \core_date::get_server_timezone_object()))->getTimestamp();

                $params = [$studioid, self::COMPONENT, $contextid, $old, 'content', 'notebook', 'contentthumbnail', $studioid, self::COMPONENT,
                        $contextid, $old, 'content', 'notebook', 'contentthumbnail'];
                $result = $DB->get_recordset_sql($sql, $params);
                if (!$result->valid()) {
                    return false;
                }

                $fs = get_file_storage();

                foreach ($result as $record) {
                    $file = $fs->get_file($contextid, self::COMPONENT, $record->filearea, $record->fileid, '/', $record->content);
                    if ($file) {
                        $file->delete();
                    }
                    $DB->set_field('openstudio_content_files', 'refcount', 0, ['id' => $record->fileid]);
                }

                $result->close();
            }

            $cms->close();

            return true;
        } catch (\Exception $e) {
            // Defaults to returning false.
            return false;
        }
    }

    /**
     * Recursively remove directory from server.
     * Function taken from php.net on:
     * http://php.net/manual/en/function.rmdir.php
     *
     * @param string $dir Directory to remove
     */
    public static function rrmdir($dir) {
        if (file_exists($dir)) {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != '.' && $object != '..') {
                        if (filetype($dir . '/' . $object) == "dir") {
                            self::rrmdir($dir . '/' . $object);
                        } else {
                            @unlink($dir . '/' . $object);
                        }
                    }
                }
                reset($objects);

                @rmdir($dir);
            }
        }
    }
}
