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
 *
 *
 * @package
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

class contentversion {

    /**
     * Gets content and content version data.
     *
     * @param int $viewerid
     * @param int $contentid
     * @param bool $includedeleted True to include deleted slot verisons.
     * @return object|false Return slot and slot version data.
     */
    public static function get_content_and_versions($viewerid, $contentid, $includedeleted = false) {
        global $DB;

        try {
            $result = (object) array();

            $result->slotdata = studio_api_slot_get_record($viewerid, $contentid);

            if ($includedeleted) {
                $result->slotversions = $DB->get_records('studio_slot_versions',
                        array('contentid' => $contentid), 'timemodified desc');
            } else {
                $sql = <<<EOF
  SELECT *
    FROM {openstudio_content_versions}
   WHERE contentid = ?
     AND deletedby IS NULL
ORDER BY timemodified desc

EOF;

                $result->slotversions = $DB->get_records_sql($sql, array($contentid));
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }

    }
}