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
 * Search area class for document folders
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\search;

use mod_openstudio\local\api\content;
use mod_openstudio\local\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Search area class for document folders.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class folders extends \core_search\base_mod {

    /** @var array Relevant context levels (module context) */
    protected static $levels = [CONTEXT_MODULE];

    /**
     * Returns recordset containing required data for indexing openstudio folders.
     *
     * @param int $modifiedfrom
     * @param \context|null $context
     * @return \moodle_recordset|null
     */
    public function get_document_recordset($modifiedfrom = 0, \context $context = null) {
        global $DB;

        list ($contextjoin, $contextparams) = $this->get_context_restriction_sql(
                $context, 'openstudio', 'os');
        if ($contextjoin === null) {
            return null;
        }

        // Get all folders.
        $openstudiofields = 'os.course';

        $levelfields = 'l3.name AS l3name, l2.name AS l2name, l1.name AS l1name';

        $contentfields = 'c.id, c.contenttype, c.mimetype, c.content, c.thumbnail, c.urltitle,
                c.name, c.description, c.ownership, c.ownershipdetail,
                c.visibility, c.userid, c.timemodified, c.timeflagged,
                c.levelid, c.levelcontainer, c.openstudioid, c.locktype, c.lockedby, c.fileid';

        $fields = $openstudiofields . ', ' . $levelfields . ', ' . $contentfields;

        $sql = "SELECT {$fields}
                  FROM {openstudio_contents} c
             LEFT JOIN {openstudio} os ON os.id = c.openstudioid
             LEFT JOIN {openstudio_level3} l3 ON l3.id = c.levelid
             LEFT JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
             LEFT JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.openstudioid = c.openstudioid
          $contextjoin
                 WHERE c.timemodified >= ?
                       AND c.contenttype = ?
                       AND c.deletedtime IS NULL
              ORDER BY c.timemodified ASC";
        return $DB->get_recordset_sql($sql, array_merge($contextparams, [$modifiedfrom, content::TYPE_FOLDER]));
    }

    /**
     * Returns the document associated with this folder id.
     *
     * @param \stdClass $record
     * @param array $options
     * @return bool|\core_search\document
     */
    public function get_document($record, $options = []) {

        try {
            $cm = get_coursemodule_from_instance('openstudio', $record->openstudioid);
            $context = \context_module::instance($cm->id);
        } catch (\dml_exception $ex) {
            // Don't throw an exception, apparently it might upset the search process.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->openstudioid .
                ' document: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($record->id, $this->componentname, $this->areaname);
        $doc->set('type', \core_search\manager::TYPE_TEXT);
        $doc->set('title', content_to_text($record->name, false));

        $content = content_to_text($record->description, false);
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, 'mod_openstudio',
                'description', $record->id);
        $doc->set('content', $content);
        $doc->set('contextid', $context->id);
        $doc->set('courseid', $record->course);
        $doc->set('itemid', $record->id);
        $doc->set('userid', $record->userid);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        $doc->set('modified', $record->timemodified);

        // Get levels and set document description.
        $strlevels = '';
        if (isset($record->levelid) &&
            isset($record->l1name) && isset($record->l2name) && isset($record->l3name)) {
            $strlevels = implode(" ", [$record->l1name, $record->l2name, $record->l3name]);
        }
        $doc->set('description1', content_to_text(trim($strlevels), false));

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @param int $id folder ID
     * @return int
     */
    public function check_access($id) {
        global $DB;

        try {
            $content = $DB->get_record('openstudio_contents', ['id' => $id], '*', IGNORE_MISSING);

            if ($content) {
                if ($content->deletedtime) {
                    return \core_search\manager::ACCESS_DELETED;
                }

                $cm = get_coursemodule_from_instance('openstudio', $content->openstudioid);

                // Get course instance.
                $course = get_course($cm->course);

                // Get module instance.
                $cminstance = $DB->get_record('openstudio', ['id' => $cm->instance], '*', MUST_EXIST);

                // Get permissions.
                $permissions = util::check_permission($cm, $cminstance, $course);

                // Determine if a user can view a folder.
                if (!util::can_read_content($cminstance, $permissions, $content)) {
                    return \core_search\manager::ACCESS_DENIED;
                }

                return \core_search\manager::ACCESS_GRANTED;
            } else {
                return \core_search\manager::ACCESS_DELETED;
            }
        } catch (\dml_exception $ex) {
            return \core_search\manager::ACCESS_DENIED;
        }

    }

    /**
     * Link to the openstudio view folder page.
     *
     * @param \core_search\document $doc Document instance returned by get_document function
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {

        $contextmodule = \context::instance_by_id($doc->get('contextid'));
        $params = ['id' => $contextmodule->instanceid, 'sid' => $doc->get('itemid')];
        return new \moodle_url('/mod/openstudio/folder.php', $params);

    }

    /**
     * Link to the openstudio view content page
     *
     * @param \core_search\document $doc Document instance returned by get_document function
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        return $this->get_doc_url($doc);
    }
}
