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

require_once($CFG->dirroot . '/lib/filestorage/zip_archive.php');
require_once($CFG->dirroot . '/lib/filestorage/zip_packer.php');

// Define constant that restricts exported Zipped filesize value in bytes.
define('ONE_MB', 1048576); // DO not change.
define('MAX_EXPORT_SIZE', (count(get_config('studio')) > 0) ? 10485760 : get_config('studio')->exportzipfilesize);
// All temporary export files accessed more than 59 mins ago will be deleted.
define('TEMP_STORAGE_TIME', 1 * 60 * 59); // In seconds. Set to 59 minutes.

/**
 * Get ALL files to be exported from a studio for a specific user.
 *
 * @param int $studioid
 * @param int $userid
 * @param int $limitfrom
 * @param int $limitnum
 * @return recordset
 */
function studio_api_export_get_files($studioid, $userid, array $fileids = null,
        array $slotids = null, $limitfrom = 0, $limitnum = 250, $returncountonly = false) {

    global $DB;

    if ($fileids != null) {
        list($filessql, $fileparams) = $DB->get_in_or_equal($fileids);
        $filessql = "AND s.fileid $filessql";
    }

    if ($slotids != null) {
        list($slotssql, $slotparams) = $DB->get_in_or_equal($slotids);
        $slotssql = "AND s.id $slotssql";
    }

    // Prepare actions SQL.
    $sql = <<<EOF
SELECT s.id,
       s.fileid,
       s.name,
       s.mimetype,
       s.content,
       f.itemid,
       f.contenthash,
       f.pathnamehash,
       f.component,
       f.filearea,
       f.itemid,
       f.filepath,
       f.filename,
       f.filesize
  FROM {openstudio_contents} s,
       {files} f
 WHERE s.openstudioid = ?
   AND s.userid  = ?
   AND s.fileid > ?
   AND f.itemid = s.fileid
   AND f.component = ?
   AND f.filearea = ?
   AND f.filesize > ?
   AND s.deletedby IS NULL
   AND s.deletedtime IS NULL

EOF;

    $params[] = $studioid;
    $params[] = $userid;
    $params[] = 0;
    $params[] = 'mod_studio';
    $params[] = 'slot';
    $params[] = 0;

    if (isset($filessql)) {
        $sql .= $filessql;
        foreach ($fileids as $fid) {
            $params[] = $fid;
        }
    }

    if (isset($slotssql)) {
        $sql .= $slotssql;
        foreach ($slotids as $sid) {
            $params[] = $sid;
        }
    }

    $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);

    if (!$returncountonly) {
        // Return the recordset.
        return $rs;
    } else {
        $count = 0;
        if ($rs->valid()) {
            foreach ($rs as $r) {
                $count++;
            }
            $rs->close();
        }

        return $count;
    }
}

/**
 * Prepares an array defining the number of zipped files and the slot files therein.
 * @global type $CFG
 * @param type $recordset (iterator object)
 * @return boolean|array Returns array if successful, false if the passed set is an invalid recordset.
 */
function studio_api_export_prepare_fileset($recordset) {
    global $CFG;
    $export = array();
    if ($recordset->valid()) {
        $filecounter = 1;
        $currentcountersize = 0;
        foreach ($recordset as $rs) {
            // Let's first check if this file actually exists, if it does,
            // then we'll add it to our export set, if it doesn't, we won't.

            // Check if adding the file to the current counter will go over the size limit.
            if (($currentcountersize + $rs->filesize) > MAX_EXPORT_SIZE) {
                // This means adding this file to the current archive will throw it over the limit.
                // So create another file / increase filecounter.
                $filecounter++;
                $currentcountersize = 0;
            }
            $currentcountersize = $currentcountersize + $rs->filesize;
            $export[$filecounter]['size_in_bytes'] = $currentcountersize;
            $export[$filecounter]['size_in_mb'] = $currentcountersize / ONE_MB;
            if ($rs->filesize > 0) {
                $export[$filecounter][] = $rs;
            }
        }
        $recordset->close();
        return $export;
    } else {
        return false;
    }
}

/**
 * Adds files to a zip archive.
 * @param array $fileset
 * @param type $filesetarraykey
 * @param type $userid
 * @param type $studioid
 * @return boolean|string Returns filename is files added successfully or false if something went wrong.
 */
function studio_api_export_generate_archive(array $fileset, $filesetarraykey, $userid, $studioid, $contextid) {
    global $DB, $USER;

    $studio = $DB->get_record('studio', array('id' => $studioid));

    // Get microtime to append to filename to avoid duplicates.
    $t = microtime(true);
    $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
    $date = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));
    $filename = str_replace(' ', '-', $studio->name) . '_'
            . str_replace(' ', '-', fullname($USER)) . '_'
            . $date->format('d-m-Y_u') . '_'
            . $filesetarraykey . '.zip';

    $archive = new zip_archive;

    // Create archive file.
    if ($archive->open(TEMPFOLDER . $filename, zip_archive::CREATE) !== true) {
        // Unable to create archive.
        return false;
    }

    unset($fileset[$filesetarraykey]['size_in_bytes']);
    unset($fileset[$filesetarraykey]['size_in_mb']);
    $count = 0;
    $string = '';
    $tempfiles = array();
    foreach ($fileset[$filesetarraykey] as $file) {
        $count++;

        // Move file out of moodle filesystem and add to archive.
        $fs = get_file_storage();
        $sf = $fs->get_file($contextid, 'mod_studio', $file->filearea,
                $file->itemid, $file->filepath, $file->filename);

        if ($sf->get_filesize() > 0 && $flocation = studio_repository_get_file_templocation($sf, $contextid)) {
            if (file_exists($flocation)) {
                $archive->add_file_from_pathname($count . '_' . $file->content, $flocation);

                // Create string to write to file.
                $string .= $count . '. '. $file->name . ' => ' . $file->content . "\n";

                // Add all these temp files to a new array to wipe them clean after the zip archive is created.
                $tempfiles[] = $flocation;
            } else {
                $string .= $count . '. Failed => '. $file->name . ' => ' . $file->content
                        . " => Unable to find file on server.\n";
            }
        }
    }

    $archive->add_file_from_string("contents.txt", $string);

    if ($archive->count() == ($count + 1)) {
        $archive->close();

        // The archive is ready, wipe out the temporary files.
        studio_api_filesystem_remove_temporary_filesystem_files($tempfiles);
        return $filename;
    } else {
        return false;
    }
}

/**
 * Extracts the contents of a zip archive, filters what we accept and
 * dumps them in temorary folder in TEMPFOLDER .
 * @param type $archiveloc
 * @return type
 */
function studio_api_import_get_archive_contents($archiveloc, $tempdir = '') {
    if ($tempdir == '') {
        $tempdir = TEMPFOLDER;
    }
    $archive = new zip_archive;
    $archive->open($tempdir . $archiveloc, zip_archive::OPEN);
    $ext = pathinfo($archiveloc, PATHINFO_EXTENSION); // Should always be .zip.
    $files = array();

    // The location will be a folder with the archive name in TEMPFOLDER location.
    $files['location'] = $tempdir . str_replace('.' . $ext, '', $archiveloc . '/'); // Without trailing slash.

    // Make sure the folder exists and is writeable (without a trailing slash.
    make_writable_directory($files['location']);

    // We have to use packer for extraction as Moodle's zip_archive has no extraction functions!
    $packer = new zip_packer;
    $packer->extract_to_pathname($tempdir . $archiveloc, $files['location']);
    $files['files'] = $archive->list_files();
    $archive->close();
    $acceptabletypes = array('jpeg', 'jpg', 'png', 'gif', 'avi', 'audio', 'aiff',
                             'wav', 'mp3', 'text', 'txt', 'word', 'docx', 'doc',
                             'pdf', 'odt', 'odm', 'writer', 'powerpoint', 'pptx',
                             'ppsx', 'odp', 'impress', 'excel', 'xlsx', 'xls', 'csv',
                             'ods', 'avi', 'mpg', 'mov', 'flv', 'mp4');
    foreach ($files['files'] as $key => $file) {
        if ($file->pathname == 'contents.txt') {
            unset($files['files'][$key]);
        }

        // Get ext.
        $extension = pathinfo($file->pathname, PATHINFO_EXTENSION); // Should always be .zip.
        if (!in_array($extension, $acceptabletypes)) {
            // Remove.
            unset($files['files'][$key]);
        }
    }
    return $files;
}

/**
 * Imports chosen files and creates slots accordingly
 *
 * @param array $files
 * @param array $filekeys
 * @param int $studioid
 * @param int $cmid
 * @param int $userid
 * @return boolean Return true if successful.
 */
function studio_api_import_files(array $files, array $filekeys = null, $studioid, $cmid, $userid = '') {
    global $DB;

    // If user is undefined, get from global.
    if ($userid == '') {
        global $USER;
        $userid = $USER->id;
    }

    if ($filekeys != null) {
        // Just filter the files array to only process what we need.
        foreach ($files['files'] as $key => $file) {
            // If the $key does not exist in the chosen filekeys, remove it from files.
            if (!in_array($key, $filekeys)) {
                unset($files['files'][$key]);
            }
        }
    }

    // Before we proceed, let's check if we can import all of these files.
    $pbslots = studio_api_slot_get_total_pinboard_slots($studioid, $userid);
    if ($pbslots->available < (count($files['files']))) {
        print_error(get_string('import_limit_hit', 'studio'));
    }

    // Get the empty slots.
    $sql = <<<EOF
SELECT s.id
  FROM {openstudio_contents} s
 WHERE s.openstudioid = ?
   AND s.userid = ?
   AND s.levelcontainer = 0
   AND s.levelid = 0
   AND s.contenttype = ?
   AND s.deletedby IS NULL
   AND s.deletedtime IS NULL

EOF;

    $params = array($studioid, $userid, STUDIO_CONTENTTYPE_NONE);
    $emptyslots = $DB->get_records_sql($sql, $params);

    $context = context_module::instance($cmid);
    $coursedata = studio_internal_render_page_init($cmid);
    $cm = $coursedata->cm;

    // Now process as normal.
    // Count number of slots created/imported.
    $slotscount = 0;
    foreach ($files['files'] as $file) {
        if ($file->pathname != 'contents.txt') {
            $slotscount++;

            // Create slot info.
            $fs = get_file_storage();

            // Get next itemid.
            $itemid = $DB->insert_record('studio_slot_files', array('refcount' => 1));

            // Prepare file record object.
            $fileinfo = array(
                'contextid' => $context->id, // ID of context.
                'userid' => $userid,
                'author' => fullname($USER),
                'component' => 'mod_studio', // Usually = table name.
                'filearea' => 'slot', // Usually = table name.
                'itemid' => $itemid, // Usually = ID of row in table.
                'filepath' => '/', // Any path beginning and ending in /.
                'filename' => str_replace('/', '_', $file->pathname)); // Any filename.

            $fileobject = $fs->create_file_from_pathname($fileinfo, $files['location'].$file->pathname);
            $mdlfile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                          $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
            $ourfile = array();
            $ourfile['file'] = new stdClass();
            $ourfile['file']->filename = $fileinfo['filename'];
            $ourfile['id'] = $fileinfo['itemid'];
            $ourfile['mimetype']['type'] = $mdlfile->get_mimetype();
            $ourfile['mimetype']['extension'] = pathinfo($file->pathname, PATHINFO_EXTENSION);
            $ourfile['checksum'] = md5(rand(1, 50));

            if (($slotscount <= $pbslots->empty) && $pbslots->empty > 0) {
                $emptycount = 0;
                foreach ($emptyslots as $es) {
                    $emptycount++;
                    if ($emptycount == $slotscount) {
                        $slottoupdate = $es;
                        break;
                    }
                }

                $pbslotdata = array(
                        'name' => $fileinfo['filename'],
                        'visibility' => STUDIO_VISIBILITY_PRIVATE,
                        'description' => $fileinfo['filename'],
                        'tags' => array(),
                        'fileid' => $itemid,
                        'ownership' => 0,
                        'levelid' => 0,
                        'levelcontainer' => 0,
                        'checksum' => studio_internal_calculate_file_hash(0));

                // Update an existing slot.
                $x = studio_api_slot_update($userid, $slottoupdate->id, $pbslotdata,
                        $ourfile, $context, false, $cm, true);
            } else {
                $pbslotdata = array(
                        'name' => $fileinfo['filename'],
                        'visibility' => STUDIO_VISIBILITY_PRIVATE,
                        'description' => $fileinfo['filename'],
                        'tags' => array(),
                        'fileid' => $itemid,
                        'ownership' => 0,
                        'checksum' => studio_internal_calculate_file_hash(0),
                        'sid' => 0);

                // Create a new pinboard slot.
                $x = studio_api_slot_create($studioid, $userid, 0, 0, $pbslotdata,
                        $ourfile, $context, $cm, true);
            }

            if ($x === false) {
                print_error (get_string('slotimportfailed', 'studio'));
            }
        }
    }

    return true;
}

/**
 * Spits out icon with img tag for specified mimetype
 * @param type $mimetype string
 * @return type
 */
function get_icon_url_from_mimetype($mimetype) {
    switch ($mimetype) {
        case 'audio/basic':
        case 'audio/mid':
        case 'audio/mpeg':
        case 'audio/x-aiff':
        case 'audio/x-mpegurl':
        case 'audio/x-wav':
            $url = '/mod/studio/pix/openstudio_icon_audio.png';
            break;

        case 'video/mpeg':
        case 'video/mp4':
        case 'video/quicktime':
        case 'video/x-la-asf':
        case 'video/msvideo':
        case 'video/x-sgi-movie':
            $url = '/mod/studio/pix/openstudio_icon_video.png';
            break;

        case 'image/gif':
        case 'image/jpg':
        case 'image/jpeg':
        case 'image/svg+xml':
        case 'image/tiff':
        case 'image/bmp':
            $url = '/mod/studio/pix/openstudio_icon_image.png';
            break;

        case 'application/vnd.ms-excel':
        case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
        case 'application/vnd.openxmlformats-officedocument.spreadsheetml.template':
            $url = '/mod/studio/pix/openstudio_icon_spreadsheet.png';
            break;

        case 'application/vnd.oasis.opendocument.spreadsheet':
            $url = '/mod/studio/pix/openstudio_icon_spreadsheet.png';
            break;

        case 'application/vnd.oasis.opendocument.text':
            $url = '/mod/studio/pix/openstudio_icon_document.png';
            break;

        case 'application/msword':
        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
            $url = '/mod/studio/pix/openstudio_icon_document.png';
            break;

        case 'application/pdf':
            $url = '/mod/studio/pix/openstudio_icon_document.png';
            break;

        case 'application/vnd.oasis.opendocument.presentation':
            $url = '/mod/studio/pix/openstudio_icon_presentation.png';
            break;

        case 'application/vnd.ms-powerpoint':
        case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
        case 'application/vnd.openxmlformats-officedocument.presentationml.slideshow':
        case 'application/vnd.openxmlformats-officedocument.presentationml.template':
            $url = '/mod/studio/pix/openstudio_icon_presentation.png';
            break;

        default:
            $url = '/mod/studio/pix/openstudio_icon_web.png';
            break;
    }

    $iconurl = new moodle_url($url);
    return html_writer::tag('img', '', array('src' => $iconurl->out(false), 'class' => '', 'alt' => ''));
}
