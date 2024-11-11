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

namespace mod_openstudio;

/**
 * Implementation of file data masking for this plugin.
 *
 * The corresponding test script tool_datamasking_test.php checks every masked field.
 *
 * @package mod_openstudio
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class files_mask extends \tool_datamasking\files_mask {
    protected function replace_filename(\stdClass $rec, string $newfilename):void {
        global $DB;
        if ($rec->filearea == 'content') {
            // Update contents.
            $sql = "UPDATE {openstudio_contents}
                       SET content = REPLACE(content, ?, ?)
                     WHERE fileid = ?";
            $DB->execute($sql, [rawurlencode($rec->filename), rawurlencode($newfilename), $rec->itemid]);
            // Update versions.
            $sql = "UPDATE {openstudio_content_versions}
                       SET content = REPLACE(content, ?, ?)
                     WHERE fileid = ?";
            $DB->execute($sql, [rawurlencode($rec->filename), rawurlencode($newfilename), $rec->itemid]);
        }
    }
}
