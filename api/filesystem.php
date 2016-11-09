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
 * This function removes all uploaded files associated with slots.
 * It is called when a studio instance is deleted.
 *
 * Warning: this is a destructive call and should be used carefully.
 *
 * @param int $contextid Module context id
 * @param int $studioid Module Studio instance id
 * @param bool $resetrefcount True to update the studio file ref count
 * @return bool Return true or false for success/failure
 */
function studio_api_filesystem_remove_slot_files_from_moodlefs(
        $contextid, $studioid, $resetrefcount = true) {

    global $DB;

    try {
        // Find all slot and slot version files.
        $sql = <<<EOF
SELECT s.fileid, s.content
  FROM {openstudio_contents} s
 WHERE s.openstudioid = ?
   AND s.fileid IS NOT NULL

 UNION

SELECT sv.fileid, sv.content
  FROM {openstudio_content_versions} sv
  JOIN {openstudio_contents} ss ON ss.id = sv.contentid
 WHERE ss.openstudioid = ?
   AND sv.fileid IS NOT NULL

EOF;

        $result = $DB->get_recordset_sql($sql, array($studioid, $studioid));
        if (!$result->valid()) {
            return false;
        }

        $fs = get_file_storage();

        foreach ($result as $record) {
            $file = $fs->get_file($contextid, 'mod_studio', 'slot', $record->fileid, '/', $record->content);
            if ($file) {
                $file->delete();
            }

            $file = $fs->get_file($contextid, 'mod_studio', 'slotthumbnail', $record->fileid, '/', $record->content);
            if ($file) {
                $file->delete();
            }

            if ($resetrefcount) {
                $sql2 = 'UPDATE {openstudio_content_files} SET refcount = 0 WHERE id = ?';
                $DB->execute($sql2, array($record->fileid));
            }
        }

        $result->close();

        return true;
    } catch (Exception $e) {
        // Defaults to returning false.
    }

    return false;
}

/*
 * This function removes all uploaded files associated with deleted slots.
 * Only files that have refcount less than or equal to 1 and slots that have
 * been deleted after 7 days are deleted.
 *
 * If no studioid is provided, all instances of studios will be processed.
 *
 * @param int $studioid Module Studio instance id
 * @return bool Return true or false for success/failure
 */
function studio_api_filesystem_remove_deleted_slot_files_from_moodlefs($studioid = null) {
    global $DB;

    try {
        if ($studioid == null) {
            $sql = <<<EOF
SELECT cm.*
  FROM {openstudio} s
  JOIN {course_modules} cm ON cm.instance = s.id
  JOIN {modules} m ON m.id = cm.module
 WHERE m.name = 'studio'

EOF;

            $cms = $DB->get_recordset_sql($sql);
        } else {
            $sql = <<<EOF
SELECT cm.*
  FROM {course_modules} cm ON cm.instance = ?
  JOIN {modules} m ON m.id = cm.module
 WHERE m.name = 'studio'

EOF;

            $cms = $DB->get_recordset_sql($sql, array($studioid));
        }

        if (!$cms->valid()) {
            return false;
        }

        foreach ($cms as $cm) {
            $studioid = $cm->instance;
            $modulecontext = context_module::instance($cm->id);
            $contextid = $modulecontext->id;

            $sql = <<<EOF
SELECT s.fileid, s.content
  FROM {openstudio_contents} s
  JOIN {openstudio_content_files} sf1 ON sf1.id = s.fileid AND sf1.refcount <= 1
 WHERE s.openstudioid = ?
   AND s.fileid IS NOT NULL
   AND s.deletedby IS NOT NULL
   AND s.deletedtime < ?

 UNION

SELECT sv.fileid, sv.content
  FROM {openstudio_content_versions} sv
  JOIN {openstudio_content_files} sf2 ON sf2.id = sv.fileid AND sf2.refcount <= 1
  JOIN {openstudio_contents} ss ON ss.id = sv.contentid
 WHERE ss.openstudioid = ?
   AND sv.fileid IS NOT NULL
   AND sv.deletedby IS NOT NULL
   AND sv.deletedtime < ?

EOF;

            // Find time which is 7 days before current time.
            $old = time() - (60 * 60 * 24 * 7);

            $result = $DB->get_recordset_sql($sql, array($studioid, $old, $studioid, $old));
            if (!$result->valid()) {
                return false;
            }

            $fs = get_file_storage();

            foreach ($result as $record) {
                $file = $fs->get_file($contextid, 'mod_studio', 'slot', $record->fileid, '/', $record->content);
                if ($file) {
                    $file->delete();
                }

                $file = $fs->get_file($contextid, 'mod_studio', 'slotthumbnail', $record->fileid, '/', $record->content);
                if ($file) {
                    $file->delete();
                }

                $sql2 = 'UPDATE {openstudio_content_files} SET refcount = 0 WHERE id = ?';
                $DB->execute($sql2, array($record->fileid));
            }

            $result->close();
        }

        $cms->close();

        return true;
    } catch (Exception $e) {
        // Defaults to returning false.
    }

    return false;
}

/**
 * Delete all temporary studio files created for export, etc. last
 * accessed before TEMP_STORAGE_TIME.
 *
 */
function studio_api_filesystem_export_delete_temporary_files() {
    // Let's scan our temp directory.
    if (!file_exists(TEMPFOLDER)) {
        return;
    }

    $currenttime = time();

    $files = scandir(TEMPFOLDER);
    foreach ($files as $item) {
        if ($item != '.' && $item != '..') {
            $fileitem = TEMPFOLDER . $item;

            if (is_dir($fileitem)) {
                studio_api_filesystem_rrmdir($fileitem);

            } else {
                // If file was last accessed more than half an hour ago, delete it.
                $lastaccessed = fileatime($fileitem);

                if (($currenttime - $lastaccessed) >= TEMP_STORAGE_TIME) {
                    // File was accessed approx. an hour ago - Delete it.
                    @unlink($fileitem);
                }
            }
        }
    }

    // Also wipeout any temporary files sitting in temp_ods_dump.
    studio_api_filesystem_remove_temporary_filesystem_files();
}

/**
 * Delete files temporarily copied from the moodle file system to add to an archive.
 * If no files are specified, the directory is scanned and all files will be removed.
 *
 * @param array $files Optional list of files to remove
 */
function studio_api_filesystem_remove_temporary_filesystem_files($files = null) {
    if (!file_exists(TEMPFOLDER)) {
        return;
    }

    $currenttime = time();

    if ($files == null) {
        $dirpath = TEMPFOLDER . 'temp_ods_dump/';
        if (file_exists($dirpath)) {
            $allfiles = $files = scandir($dirpath);
            foreach ($allfiles as $f) {
                $fileitem = $dirpath . $f;

                // If file was last accessed more than half an hour ago, delete it.
                $lastaccessed = fileatime($fileitem);
                if (($currenttime - $lastaccessed) >= TEMP_STORAGE_TIME) {
                    // File was accessed approx. an hour ago - Delete it.
                    @unlink($fileitem);
                }
            }
        }
    } else {
        foreach ((array) $files as $f) {
            @unlink($f);
        }
    }
}

/**
 * Recursively remove directory from server.
 * Function taken from php.net on:
 * http://php.net/manual/en/function.rmdir.php
 *
 * @param string $dir Directory to remove
 */
function studio_api_filesystem_rrmdir($dir) {
    if (file_exists($dir)) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . '/' . $object) == "dir") {
                        studio_api_filesystem_rrmdir($dir . '/' . $object);
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
