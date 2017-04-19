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
 * API public static functions for tracking unique content items.
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

class item {
    const CONTENT = 0;
    const VERSION = 1;

    /**
     * Generate a hash based on the contents's content type and content
     *
     * If the contents contains a file, the file's contenthash is used.
     * If the contents contains a link or embed code, the content field is used.
     * If the contents only contains text, a combination of the contents ID or provenance ID, title and description are used.
     * The resulting string is concatenated with the content type and SHA1 hashed.
     *
     * @param int $slotid The ID of the slot to generate a hash for
     * @return string The content hash, or empty string if there's no content.
     * @throws \coding_exception Thrown if we pass a non-existant slot ID.
     */
    public static function generate_hash($slotid) {
        global $DB;
        $sql = <<<EOF
        SELECT ss.*, c.id as contextid
          FROM {openstudio_contents} ss
          JOIN {openstudio} s ON ss.openstudioid = s.id
          JOIN {course_modules} cm ON cm.instance = s.id AND cm.course = s.course
          JOIN {modules} m ON cm.module = m.id AND m.name = 'openstudio'
          JOIN {context} c ON cm.id = c.instanceid
         WHERE c.contextlevel = ?
           AND ss.id = ?

EOF;
        $params = array(CONTEXT_MODULE, $slotid);
        // Ensure that the slot exists, and get required data for generating the hash.
        $slot = $DB->get_record_sql($sql, $params);
        if (!$slot) {
            throw new \coding_exception('Slot does not exist.');
        }
        $content = '';

        if ($slot->contenttype > content::TYPE_TEXT && $slot->contenttype < content::TYPE_FOLDER) {
            // If the slot contains actual content (rather than just text or other slots).
            if (empty($slot->fileid)) {
                $content = $slot->content;
            } else {
                $fs = get_file_storage();
                if ($file = $fs->get_file($slot->contextid, 'mod_openstudio', 'content', $slot->fileid, '/', $slot->content)) {
                    $content = $file->get_contenthash();
                } else if (
                        $file = $fs->get_file($slot->contextid, 'mod_openstudio', 'notebook', $slot->fileid, '/', $slot->content)) {
                    $content = $file->get_contenthash();
                }
            }
        } else {
            if ($provenance = folder::get_content_provenance(0, $slot->id)) {
                // If ths slot is a copy of another slot, hash with the provenance ID
                // so that we get the same hash as the original.
                $content = $provenance->id . ':' . $slot->name . ':' . $slot->description;
            } else {
                // If the slot is not a copy, hash with the slot ID and the text.
                $content = $slot->id . ':' . $slot->name . ':' . $slot->description;
            }
        }

        if (empty($slot->contenttype) || empty($content)) {
            // If there's no content, we dont record a hash for the slot.
            return '';
        }

        // To avoid hash collisions, the we also add the content type before hashing.
        return sha1($slot->contenttype . ':' . $content);

    }

    /**
     * Log a slot's content hash to the slot_items table
     *
     * @param int $slotid The ID of the slot to log
     * @return bool|int ID of the slot_items record, or false if no hash is generated,
     * @throws \coding_exception Thrown if there is already a hash for the specified slot.
     */
    public static function log($slotid) {
        global $DB;
        $contenthash = self::generate_hash($slotid);
        if (!empty($contenthash)) {
            $params = array('containerid' => $slotid, 'containertype' => self::CONTENT);
            if ($DB->record_exists('openstudio_content_items', $params)) {
                throw new \coding_exception('A slot item already exists for this slot. ' .
                        'Move it with item::toversion or remove it with item::delete'  .
                        'before logging a new one.');
            }
            $itemrecord = (object) array(
                    'contenthash' => $contenthash,
                    'containerid' => $slotid,
                    'containertype' => self::CONTENT,
                    'timeadded' => time()
            );

            return $DB->insert_record('openstudio_content_items', $itemrecord);
        }

        return false;
    }

    /**
     * Move a hash currently attached to a slot so it's attached to a slot version
     *
     * @param int $slotid The Slot the hash is currently attached to
     * @param int $versionid The version to move it to
     * @throws \coding_exception Thrown if the slot or version do not exist, or if there is already a hash for the version
     */
    public static function toversion($slotid, $versionid) {
        global $DB;
        if (!$DB->record_exists('openstudio_contents', array('id' => $slotid))) {
            throw new \coding_exception('Slot does not exist.');
        }
        if (!$DB->record_exists('openstudio_content_versions', array('id' => $versionid))) {
            throw new \coding_exception('Slot Version does not exist.');
        }
        $params = array('containerid' => $versionid, 'containertype' => self::VERSION);
        if ($DB->record_exists('openstudio_content_items', $params)) {
            throw new \coding_exception('Item already exists for slot version');
        }
        $params = array('containerid' => $slotid, 'containertype' => self::CONTENT);
        $itemrecord = $DB->get_record('openstudio_content_items', $params);
        if ($itemrecord) {
            $itemrecord->containerid = $versionid;
            $itemrecord->containertype = self::VERSION;
            $DB->update_record('openstudio_content_items', $itemrecord);
        }
    }

    /**
     * Get the occurrences of a hash in the slot_items table, with the owner's name
     *
     * @param string $contenthash The hash to look for
     * @param bool $liveonly If true, only return hashes attached to slots, ignoring slot versions
     * @return array
     */
    public static function get_occurences($contenthash, $liveonly = true) {
        global $DB;

        if (empty($contenthash)) {
            return array();
        } else {
            $params = array('contenthash' => $contenthash);
            $slotsql = <<<EOF
            SELECT i.id, i.containerid, i.containertype, i.timeadded, u.id as userid, u.firstname, u.lastname,
                   u.middlename, u.alternatename, u.firstnamephonetic, u.lastnamephonetic
              FROM {openstudio_content_items} i
              JOIN {openstudio_contents} s ON s.id = i.containerid
              JOIN {user} u ON u.id = s.userid
             WHERE i.contenthash = :contenthash
               AND i.containertype = :containertype
               AND s.deletedtime IS NULL
          ORDER BY i.timeadded ASC
EOF;
            $slotparams = array_merge($params, array('containertype' => self::CONTENT));
            $versionsql = <<<EOF
            SELECT i.id, i.containerid, i.containertype, i.timeadded, u.id as userid, u.firstname, u.lastname,
                   u.middlename, u.alternatename, u.firstnamephonetic, u.lastnamephonetic
              FROM {openstudio_content_items} i
              JOIN {openstudio_content_versions} v ON v.id = i.containerid
              JOIN {openstudio_contents} s ON s.id = v.contentid
              JOIN {user} u ON u.id = s.userid
             WHERE i.contenthash = :contenthash
               AND i.containertype = :containertype
               AND v.deletedtime IS NULL
          ORDER BY i.timeadded ASC
EOF;
            $versionparams = array_merge($params, array('containertype' => self::VERSION));

            $occurences = $DB->get_records_sql($slotsql, $slotparams);

            if (!$liveonly) {
                $occurences = $occurences + $DB->get_records_sql($versionsql, $versionparams);
                uasort($occurences, function($a, $b) {
                    if ($a->timeadded == $b->timeadded) {
                        return 0;
                    }
                    return ($a->timeadded < $b->timeadded) ? -1 : 1;
                });
            }

            return $occurences;
        }
    }

    /**
     * Return a count of the times a content hash appears in the studio_items table
     *
     * @param string $contenthash The hash to look for
     * @param int $cmid Course Module ID of the studio to restrict search to
     * @param bool $liveonly If true, only count hashes attached to slots, not slot versions
     * @return int The number of times the content hash appears
     */
    public static function count_occurences($contenthash, $cmid = 0, $liveonly = true) {
        global $DB;

        if (empty($contenthash)) {
            return 0;
        } else {
            $params = array(
                    'contenthash' => $contenthash,
                    'typecontent' => self::CONTENT
            );
            if ($cmid > 0) {
                $cm = get_coursemodule_from_id('openstudio', $cmid);
                $params['openstudioid'] = $cm->instance;
            }
            $select = 'SELECT COUNT(*) ';
            if ($liveonly) {
                $from = '     FROM {openstudio_content_items} ssi '
                        .'    JOIN {openstudio_contents} ss ON ssi.containerid = ss.id AND ssi.containertype = :typecontent ';
                $where = '   WHERE ss.deletedtime IS NULL '
                        .'     AND ssi.contenthash = :contenthash ';
            } else {
                $from = '      FROM {openstudio_content_items} ssi '
                        .'LEFT JOIN {openstudio_contents} ss ON ssi.containerid = ss.id AND ssi.containertype = :typecontent '
                        .'LEFT JOIN {openstudio_content_versions} sv
                                 ON ssi.containerid = sv.id AND ssi.containertype = :typeversion ';
                $where = '    WHERE (ss.id IS NOT NULL AND ss.deletedtime IS NULL '
                        .'       OR sv.id IS NOT NULL AND sv.deletedtime IS NULL) '
                        .'      AND ssi.contenthash = :contenthash ';
                $params['typeversion'] = self::VERSION;
            }
            if (isset($params['openstudioid'])) {
                $from .= 'JOIN {openstudio} s ON ss.openstudioid = s.id ';
                $where .= 'AND s.id = :openstudioid';
            }
            return $DB->count_records_sql($select.$from.$where, $params);
        }
    }

    /**
     * Delete the current slot_items record for a slot
     *
     * @param int $containerid
     * @param int $containertype
     * @return bool
     */
    public static function delete($containerid, $containertype = self::CONTENT) {
        global $DB;

        if ($DB->record_exists('openstudio_content_items',
                array('containerid' => $containerid, 'containertype' => $containertype))) {
            return $DB->delete_records('openstudio_content_items',
                    array('containerid' => $containerid, 'containertype' => $containertype));
        } else {
            return false;
        }
    }
}
