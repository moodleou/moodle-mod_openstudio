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

    /**
     * Get ALL files to be exported from a studio for a specific user.
     *
     * @param int $studioid
     * @param int $userid
     * @param int $limitfrom
     * @param int $limitnum
     * @return recordset
     */
    public static function get_files($studioid, $userid, array $fileids = null,
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
  FROM {openstudio_contents} s
  JOIN {files} f ON f.itemid = s.fileid
 WHERE s.openstudioid = ?
   AND s.userid  = ?
   AND s.fileid > ?
   AND f.component = ?
   AND (f.filearea = ? OR f.filearea = ?)
   AND f.filesize > ?
   AND s.deletedby IS NULL
   AND s.deletedtime IS NULL

EOF;

        $params[] = $studioid;
        $params[] = $userid;
        $params[] = 0;
        $params[] = 'mod_openstudio';
        $params[] = 'content';
        $params[] = 'notebook';
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
}
