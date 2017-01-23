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
 * OpenStudio Export API
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Export API functions
 *
 * These functions support openstudio's integration with the core Portfolio API for exporting content.
 *
 * @package mod_openstudio\local\api
 */
class export {

    /**
     * @var array Map of encodings from the actual characters we want (0-9 and comma) to letters.
     */
    private static $encodemap = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'];

    /**
     * Encode a comma-separated list of IDs as an alpha string.
     *
     * This is based on a similar idea from forumngfeature_export, and is necessary as the Porfolio API cleans callback parameters
     * with PARAM_ALPHA, PARAM_FLOAT or PARAM_PATH.
     * Implodes the array to an 'x'-delimited string, then replaces each digit with a corresponding letter.
     *
     * @param array $ids Content IDs to encode
     * @return string IDs encded as letters.
     */
    public static function encode_ids(array $ids) {
        if (array_filter($ids, 'is_numeric') != $ids) {
            throw new \coding_exception('encode_ids only accepts an array of numeric content IDs.');
        }
        return strtr(implode('x', $ids), self::$encodemap);
    }

    /**
     * Decode a string of letters to a comma-separated list of IDs.
     *
     * The exact reverse of encode_ids. Replace each letter a-j with its correponding digit to get an 'x'-delimited string of
     * IDs, then explodes it into an array.
     *
     * @param string $ids Encoded with encode_ids
     * @return array Content IDs.
     */
    public static function decode_ids($ids) {
        if (preg_match('~[^a-jx]~', $ids)) {
            throw new \coding_exception('decode_ids only accepts an string encoded with encode_ids.');
        }
        return explode('x', strtr($ids, array_flip(self::$encodemap)));
    }
}
