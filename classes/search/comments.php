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
 * Search area class for document comments
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\search;

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Search area class for document comments.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comments extends \core_search\base_mod {

    /**
     * File area relate to Moodle file table.
     */
    const FILEAREA = [
        'COMMENT' => 'contentcomment'
    ];

    /** @var array Relevant context levels (module context) */
    protected static $levels = [CONTEXT_MODULE];

    /**
     * Returns recordset containing required data for indexing openstudio comments.
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
        // Get all contents.
        $openstudiofields = 'os.course';

        $commentfields = 'sc.id, sc.contentid, sc.userid as commentuser, sc.title,
                sc.commenttext, sc.timemodified';

        $contentfields = 's.id as contentid, s.name, s.contenttype, s.ownership, s.ownershipdetail,
                s.userid, s.openstudioid, s.fileid';

        $fields = $openstudiofields . ', ' . $commentfields . ', ' . $contentfields;

        $sql = "SELECT {$fields}
                  FROM {openstudio_contents} s
                  JOIN {openstudio} os ON os.id = s.openstudioid
                  JOIN {openstudio_comments} sc ON s.id = sc.contentid
          $contextjoin
                 WHERE sc.timemodified >= ?
                       AND s.deletedtime IS NULL
                       AND sc.deletedtime IS NULL
                       AND s.contenttype <> ?
              ORDER BY sc.timemodified ASC";
        return $DB->get_recordset_sql($sql, array_merge($contextparams, [$modifiedfrom, content::TYPE_NONE]));
    }

    /**
     * Returns the document associated with this content id.
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
        $doc->set('content', content_to_text($record->commenttext, false));
        $doc->set('contextid', $context->id);
        $doc->set('courseid', $record->course);
        $doc->set('itemid', $record->id);
        $doc->set('userid', $record->commentuser);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        $doc->set('modified', $record->timemodified);

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @param int $id content ID
     * @return int
     */
    public function check_access($id) {
        global $DB;

        $comment = $DB->get_record('openstudio_comments', ['id' => $id]);

        if (empty($comment) || !empty($comment->deletedtime)) {
            return \core_search\manager::ACCESS_DELETED;
        }

        try {
            $content = $DB->get_record('openstudio_contents', ['id' => $comment->contentid], '*', IGNORE_MISSING);

            if ($content) {
                if ($content->deletedtime || $content->contenttype == content::TYPE_NONE) {
                    return \core_search\manager::ACCESS_DELETED;
                }

                $cm = get_coursemodule_from_instance('openstudio', $content->openstudioid);

                // Get course instance.
                $course = get_course($cm->course);

                // Get module instance.
                $cminstance = $DB->get_record('openstudio', ['id' => $cm->instance], '*', MUST_EXIST);

                // Get permissions.
                $permissions = util::check_permission($cm, $cminstance, $course);

                $folderid = 0;
                if ($content->visibility == content::VISIBILITY_INFOLDERONLY) {
                    $containingfolder = folder::get_containing_folder($content->id);
                    if ($containingfolder) {
                        $folderid = $containingfolder->id;
                    }
                }

                // Determine if a user can view a content.
                if (!util::can_read_content($cminstance, $permissions, $content, $folderid)) {
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
     * Link to the openstudio view content page
     *
     * @param \core_search\document $doc Document instance returned by get_document function
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        global $DB;

        $contextmodule = \context::instance_by_id($doc->get('contextid'));
        $comment = $DB->get_record('openstudio_comments', ['id' => $doc->get('itemid')]);
        $content = $DB->get_record('openstudio_contents', ['id' => $comment->contentid], 'id, contenttype, visibility');

        if ($content->contenttype == content::TYPE_FOLDER) {
            $redirecturl = new \moodle_url('/mod/openstudio/folder.php', [
                'id' => $contextmodule->instanceid, 'sid' => $comment->contentid]);
        } else {
            $linkparams = [
                'id' => $contextmodule->instanceid,
                'sid' => $comment->contentid
            ];

            if ($content->visibility == content::VISIBILITY_INFOLDERONLY) {
                $containingfolder = folder::get_containing_folder($content->id);
                if ($containingfolder) {
                    $linkparams['folderid'] = $containingfolder->id;
                }
            }

            $redirecturl = new \moodle_url('/mod/openstudio/content.php', $linkparams, 'openstudio-comment-' . $doc->get('itemid'));
        }

        return $redirecturl;
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

        $files = array_merge($files, $fs->get_area_files($document->get('contextid'), $this->componentname,
            self::FILEAREA['COMMENT'], $document->get('itemid')));

        foreach ($files as $file) {
            $document->add_stored_file($file);
        }
    }

}
