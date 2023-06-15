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
 * Miscellaneous utility functions for testing.
 *
 * @package mod_openstudio
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

defined('MOODLE_INTERNAL') || die();

/**
 * Miscellaneous utility functions for testing.
 *
 * @package mod_openstudio
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_utils {
    /**
     * Mocks all flags so that each one has a different timing.
     *
     * @param $seconds
     * @throws dml_exception
     */
    public static function mock_flag_times($seconds = 60) {
        self::mock_field_times('openstudio_flags', 'timemodified', $seconds);
    }

    /**
     * Mocks all trackings so that each one has a different timing.
     *
     * @param $seconds
     * @throws dml_exception
     */
    public static function mock_tracking_times($seconds = 60) {
        self::mock_field_times('openstudio_tracking', 'timemodified', $seconds);
    }

    /**
     * Mocks the field values so that each one has a different timing.
     *
     * @param $table
     * @param $field
     * @param $seconds
     * @throws dml_exception
     */
    private static function mock_field_times($table, $field, $seconds = 60) {
        global $DB;

        $rows = $DB->get_records($table, null, '', "id,{$field}");
        foreach ($rows as $row) {
            $data = (object) [
                    'id' => $row->id,
                    "{$field}" => $row->{$field} + $row->id * $seconds
            ];
            $DB->update_record($table, $data);
        }
    }

    /**
     * Creates a user draft file in the provided draft area.
     *
     * @param string $filename The name of the file in the importfiles folder to copy.
     * @param int $itemid The draft Item Id to upload.
     * @param string $storefilename To store the file name under.
     */
    public static function create_draft_file(string $filename, int $itemid = 0, string $storefilename = null) {
        global $USER, $CFG;

        if ($itemid === 0) {
            $itemid = file_get_unused_draft_itemid();
        }

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);

        if ($storefilename === null) {
            $storefilename = $filename;
        }

        $fileinfo = (object) [
                'contextid' => $context->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => $storefilename,
                'author' => fullname($USER),
                'userid' => $USER->id,
        ];
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'importfiles' . DIRECTORY_SEPARATOR . $filename;
        $fs->create_file_from_pathname($fileinfo, $path);

        $filepath = $CFG->wwwroot . '/draftfile.php/' . $context->id . '/user/draft/' .
                $itemid . '/' . $storefilename;
        return [$itemid, $filepath];
    }
}
