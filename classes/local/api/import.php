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
 * OpenStudio Import API
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

use mod_openstudio\local\util;
use mod_openstudio\local\api\content;

defined('MOODLE_INTERNAL') || die();

class import {

    /**
     * Extracts the contents of a zip archive, filters what we accept and dumps them in temorary folder.
     *
     * @param \stored_file $importfile The stored file object for the uploaded Zip archive.
     * @return array List of allowed files from the archive.
     */
    public static function get_archive_contents(\stored_file $importfile) {
        $tempname = 'import' . random_string();
        $temppath = $importfile->copy_content_to_temp('openstudio', $tempname);

        $archive = new \zip_archive;

        if (!$archive->open($temppath, \zip_archive::OPEN)) {
            return null;
        }

        $files = [];

        // The location will be a folder with the archive name in TEMPFOLDER location.
        $files['location'] = make_temp_directory('openstudio/' . $tempname);

        // Make sure the folder exists and is writeable (without a trailing slash.
        make_writable_directory($files['location']);

        // We have to use packer for extraction as Moodle's zip_archive has no extraction functions!
        $packer = new \zip_packer;
        $packer->extract_to_pathname($temppath, $files['location']);
        $files['files'] = $archive->list_files();
        $archive->close();
        $acceptabletypes = ['jpeg', 'jpe', 'jpg', 'png', 'gif', 'avi', 'audio', 'aiff',
                'wav', 'mp3', 'text', 'txt', 'word', 'docx', 'doc', 'rtf', 'fdf', 'nbk',
                'pdf', 'odt', 'odm', 'writer', 'powerpoint', 'pptx', 'ppt',
                'ppsx', 'odp', 'impress', 'excel', 'xlsx', 'xls', 'csv',
                'ods', 'avi', 'mpg', 'mov', 'flv', 'm4v', 'mp4', 'm4a'];
        foreach ($files['files'] as $key => $file) {
            if ($file->pathname == 'contents.txt') {
                unset($files['files'][$key]);
            }

            // Get ext.
            $extension = strtolower(pathinfo($file->pathname, PATHINFO_EXTENSION));
            if (!in_array($extension, $acceptabletypes)) {
                // Remove.
                unset($files['files'][$key]);
            }
        }
        return $files;
    }

    /**
     * Checks that the number of files to be uploaded doesn't exceed the number the user is allowed to upload.
     *
     * @param int $studioid The ID of the openstudio instance
     * @param int $userid The ID of the user importing the files
     * @param array $files The files being imported (e.g. from import::get_archive_contents)
     * @return bool True if the user has enough space on their pinboard
     */
    public static function check_import_limit($studioid, $userid, array $files) {
        $pbcontents = content::get_pinboard_total($studioid, $userid);
        return ($pbcontents->available >= (count($files)));
    }

    /**
     * Imports chosen files and creates content accordingly
     *
     * @param array $files
     * @param object $cm
     * @param int $userid
     * @return boolean Return true if successful.
     */
    public static function import_files(array $files, $cm, $userid = null) {
        global $DB, $USER;

        // If user is undefined, get from global.
        if (is_null($userid)) {
            $userid = $USER->id;
        }

        // Before we proceed, let's check if we can import all of these files.
        if (!self::check_import_limit($cm->instance, $userid, $files['files'])) {
            throw new \moodle_exception('import_limit_hit', 'openstudio');
        }

        // Get the empty contents.
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

        $params = [$cm->instance, $userid, content::TYPE_NONE];
        $emptycontents = $DB->get_records_sql($sql, $params);

        $context = \context_module::instance($cm->id);

        // Now process as normal.
        // Count number of posts created/imported.
        $contentscount = 0;
        foreach ($files['files'] as $file) {
            if ($file->pathname != 'contents.txt') {
                $contentscount++;

                // Create content info.
                $fs = get_file_storage();

                // Get next itemid.
                $itemid = $DB->insert_record('openstudio_content_files', (object) ['refcount' => 1]);

                // Prepare file record object.
                $fileinfo = [
                        'contextid' => $context->id, // ID of context.
                        'userid' => $userid,
                        'author' => fullname($USER),
                        'component' => 'mod_openstudio', // Usually = table name.
                        'filearea' => 'content', // Usually = table name.
                        'itemid' => $itemid, // Usually = ID of row in table.
                        'filepath' => '/', // Any path beginning and ending in /.
                        'filename' => str_replace('/', '_', $file->pathname) // Any filename.
                ];
                $fs->create_file_from_pathname($fileinfo, $files['location'] . '/' . $file->pathname);
                $mdlfile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                        $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
                $ourfile = [];
                $ourfile['file'] = new \stdClass();
                $ourfile['file']->filename = $fileinfo['filename'];
                $ourfile['id'] = $fileinfo['itemid'];
                $ourfile['mimetype']['type'] = $mdlfile->get_mimetype();
                $ourfile['mimetype']['extension'] = strtolower(pathinfo($file->pathname, PATHINFO_EXTENSION));
                $ourfile['checksum'] = md5(rand(1, 50));

                $pbcontents = content::get_pinboard_total($cm->instance, $userid);
                if (($contentscount <= $pbcontents->empty) && $pbcontents->empty > 0) {
                    $emptycount = 0;
                    foreach ($emptycontents as $es) {
                        $emptycount++;
                        if ($emptycount == $contentscount) {
                            $contenttoupdate = $es;
                            break;
                        }
                    }

                    $pbcontentdata = [
                        'name' => $fileinfo['filename'],
                        'visibility' => content::VISIBILITY_PRIVATE,
                        'description' => $fileinfo['filename'],
                        'tags' => [],
                        'fileid' => $itemid,
                        'ownership' => 0,
                        'levelid' => 0,
                        'levelcontainer' => 0,
                        'checksum' => util::calculate_file_hash(0)
                    ];

                    // Update an existing content.
                    $x = content::update($userid, $contenttoupdate->id, $pbcontentdata,
                            $ourfile, $context, false, $cm, true);
                } else {
                    $pbcontentdata = [
                        'name' => $fileinfo['filename'],
                        'visibility' => content::VISIBILITY_PRIVATE,
                        'description' => $fileinfo['filename'],
                        'tags' => [],
                        'fileid' => $itemid,
                        'ownership' => 0,
                        'checksum' => util::calculate_file_hash(0),
                        'sid' => 0
                    ];

                    // Create a new pinboard content.
                    $x = content::create($cm->instance, $userid, 0, 0, $pbcontentdata,
                            $ourfile, $context, $cm, true);
                    // Update flag and tracking.
                    flags::toggle($x, flags::READ_CONTENT, 'on', $USER->id, $x);
                    tracking::log_action($x, flags::READ_CONTENT, $USER->id);
                }

                if ($x === false) {
                    print_error (get_string('contentimportfailed', 'openstudio'));
                }
            }
        }

        return true;
    }
}
