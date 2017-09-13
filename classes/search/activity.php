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
 * Search area for mod_openstudio activities.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\search;

defined('MOODLE_INTERNAL') || die();

/**
 * Search area for mod_openstudio activities.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity extends \core_search\base_activity {

    /**
     * File area related to document
     */
    const FILEAREA = [
        'INTRO_AREA' => 'intro'
    ];

    /**
     * Returns true if this area uses file indexing.
     *
     * @return bool
     */
    public function uses_file_indexing() {
        return true;
    }

    /**
     * Add the attached description files.
     *
     * @param \core_search\document $document The current document
     * @return null
     */
    public function attach_files($document) {
        $fs = get_file_storage();
        $files = [];

        foreach (self::FILEAREA as $area) {
            $files = array_merge($files, $fs->get_area_files($document->get('contextid'), $this->componentname, $area,
                    0, 'sortorder DESC, id ASC', false));
        }

        foreach ($files as $file) {
            $document->add_stored_file($file);
        }
    }
}
