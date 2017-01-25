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
 * API functions for folder and content templates.
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

class template {

    /**
     * Create a set template.
     *
     * @param int $levelid The ID of the level the template is attached to
     * @param object|array $settemplate Data to be added to the template
     * @return boolean The ID of the created template, or false if failure.
     */
    public static function create($levelid, $settemplate = []) {
        global $DB;

        $settemplate = (object) $settemplate;
        $templateinsertdata = new \stdClass();

        try {
            $level = levels::get_record(3, $levelid);
            if ($level) {
                $templateinsertdata->levelid = $level->id;
                $templateinsertdata->levelcontainer = 3;

                if (isset($settemplate->guidance)) {
                    $templateinsertdata->guidance = $settemplate->guidance;
                }
                if (isset($settemplate->additionalcontents)) {
                    $templateinsertdata->additionalcontents = $settemplate->additionalcontents;
                }
                $templateinsertdata->status = levels::ACTIVE;

                return $DB->insert_record('openstudio_folder_templates', $templateinsertdata);
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a new content template
     *
     * @param int $foldertemplateid The ID of the folder template containing the content template
     * @param object|array $contenttemplate The slot template record to create.
     * @return int|boolean The ID of the created slot template, or false if failure.
     */
    public static function create_content($foldertemplateid, $contenttemplate) {
        global $DB;

        $contenttemplate = (object) $contenttemplate; // Support arrays as well.
        $templateinsertdata = new \stdClass();

        try {
            $settemplate = self::get($foldertemplateid);
            if ($settemplate) {
                $templateinsertdata->foldertemplateid = $settemplate->id;

                if (isset($contenttemplate->name)) {
                    $templateinsertdata->name = $contenttemplate->name;
                }
                if (isset($contenttemplate->guidance)) {
                    $templateinsertdata->guidance = $contenttemplate->guidance;
                }
                if (isset($contenttemplate->permissions)) {
                    $templateinsertdata->permissions = $contenttemplate->permissions;
                } else {
                    $templateinsertdata->permissions = 0;
                }
                if (isset($contenttemplate->contentorder) && $contenttemplate->contentorder > 0) {
                    $templateinsertdata->contentorder = $contenttemplate->contentorder;
                } else {
                    // If no slot order is specified, default to the slot order of the last slot in the template,
                    // plus 1.
                    $templateslots = $DB->get_records('openstudio_content_templates', ['foldertemplateid' => $settemplate->id],
                            'contentorder DESC', '*', 0, 1);
                    if (empty($templateslots)) {
                        $templateinsertdata->contentorder = 1;
                    } else {
                        $lastslot = array_pop($templateslots);
                        $templateinsertdata->contentorder = $lastslot->contentorder + 1;
                    }
                }
                $templateinsertdata->status = levels::ACTIVE;

                return $DB->insert_record('openstudio_content_templates', $templateinsertdata);
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the template for a folder, if there is one.
     *
     * @param int $folderid The setid of the set.
     * @return object|false The openstudio_folder_templates record
     */
    public static function get_by_folderid($folderid) {
        global $DB;

        $sql = <<<EOF
    SELECT ft.*
      FROM {openstudio_folder_templates} ft
      JOIN {openstudio_level3} l ON l.id = ft.levelid
      JOIN {openstudio_contents} c ON c.levelid = l.id
     WHERE c.id = :folderid
       AND ft.status = :status
EOF;

        return $DB->get_record_sql($sql, ['folderid' => $folderid, 'status' => levels::ACTIVE]);
    }

    /**
     * Get the folder template for a level record, if there is one.
     *
     * @param int $levelid The ID of the level.
     * @return object|false The openstudio_folder_templates record
     */
    public static function get_by_levelid($levelid) {
        global $DB;

        $sql = <<<EOF
    SELECT ft.*
      FROM {openstudio_folder_templates} ft
      JOIN {openstudio_level3} l ON l.id = ft.levelid
     WHERE l.id = :levelid
       AND ft.status = :status
EOF;

        return $DB->get_record_sql($sql, ['levelid' => $levelid, 'status' => levels::ACTIVE]);
    }

    /**
     * Get the template for a folder, if there is one.
     *
     * @param int $templateid The id of the folder template.
     * @return object|false The openstudio_folder_templates record
     */
    public static function get($templateid) {
        global $DB;

        return $DB->get_record('openstudio_folder_templates', ['id' => $templateid, 'status' => levels::ACTIVE]);
    }

    /**
     * Get the content templates for a folder template if there are any.
     *
     * @param int $templateid The id of the folder template.
     * @return array The openstudio_content_templates records
     */
    public static function get_contents($templateid) {
        global $DB;

        $sql = <<<EOF
    SELECT ct.*
      FROM {openstudio_content_templates} ct
      JOIN {openstudio_folder_templates} ft ON ft.id = ct.foldertemplateid
     WHERE ft.id = ?
       AND ft.status = ?
       AND ct.status = ?
  ORDER BY ct.contentorder ASC
EOF;

        return $DB->get_records_sql($sql, [$templateid, levels::ACTIVE, levels::ACTIVE]);
    }

    /**
     * Get a content template
     *
     * @param int $contenttemplateid The id of the content template
     * @return object|false The openstudio_content_template record
     */
    public static function get_content($contenttemplateid) {
        global $DB;

        return $DB->get_record('openstudio_content_templates', ['id' => $contenttemplateid, 'status' => levels::ACTIVE]);
    }

    /**
     * Get a content template by its contentorder within the folder template
     *
     * @param int $foldertemplateid The ID of the folder template the content is part of
     * @param int $contentorder The contentorder of the content template
     * @return object|false the openstudio_content_template record, or false if there isn't one
     */
    public static function get_content_by_contentorder($foldertemplateid, $contentorder) {
        global $DB;

        $sql = <<<EOL
    SELECT ct.*
      FROM {openstudio_content_templates} ct
     WHERE ct.foldertemplateid = ?
       AND ct.contentorder = ?
       AND ct.status = ?
EOL;

        $params = [$foldertemplateid, $contentorder, levels::ACTIVE];
        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Update a folder template.
     *
     * @param object $foldertemplate Folder template record for update.
     * @return boolean True if the update is successful, false on failure
     * @throws \coding_exception If an invalid ID or no ID is set in $settemplate
     */
    public static function update($foldertemplate) {
        global $DB;

        if (!isset($foldertemplate->id)) {
            throw new \coding_exception('$foldertemplate passed to folder::update_template must contain an id');
        }
        $templateinsertdata = new \stdClass();

        $templaterecord = self::get($foldertemplate->id);
        if ($templaterecord) {
            try {
                $templateinsertdata->id = $templaterecord->id;
                if (isset($foldertemplate->guidance)) {
                    $templateinsertdata->guidance = $foldertemplate->guidance;
                }
                if (isset($foldertemplate->additionalcontents)) {
                    $templateinsertdata->additionalcontents = $foldertemplate->additionalcontents;
                }
                if (isset($foldertemplate->status)) {
                    $templateinsertdata->status = $foldertemplate->status;
                }

                return $DB->update_record('openstudio_folder_templates', $templateinsertdata);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            throw new \coding_exception('$foldertempalte passed to folder::update_template ' .
                    'must contain an id for a record that exists in openstudio_folder_templates');
        }
    }

    /**
     * Update a content template
     *
     * @param object $contenttemplate The updated content template
     * @return boolean True if the update is successful, false if not
     * @throws \coding_exception if and invalid ID or no ID is set in $contenttemplate
     */
    public static function update_content($contenttemplate) {
        global $DB;

        if (!isset($contenttemplate->id)) {
            throw new \coding_exception('$contenttemplate passed to folder::update_content_template must contain an id');
        }
        $templateinsertdata = new \stdClass();

        $templaterecord = self::get_content($contenttemplate->id);
        if ($templaterecord) {

            try {
                $templateinsertdata->id = $templaterecord->id;

                if (isset($contenttemplate->name)) {
                    $templateinsertdata->name = $contenttemplate->name;
                }
                if (isset($contenttemplate->guidance)) {
                    $templateinsertdata->guidance = $contenttemplate->guidance;
                }
                if (isset($contenttemplate->permissions)) {
                    $templateinsertdata->permissions = $contenttemplate->permissions;
                }
                if (isset($contenttemplate->contentorder) && $contenttemplate->contentorder > 0) {
                    $templateinsertdata->contentorder = $contenttemplate->contentorder;
                }
                if (isset($contenttemplate->status)) {
                    $templateinsertdata->status = $contenttemplate->status;
                }

                return $DB->update_record('openstudio_content_templates', $templateinsertdata);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            throw new \coding_exception('$contenttemplate passed to folder::update_content_template ' .
                    'must contain an id for a record that exists in openstudio_content_templates.');
        }
    }

    /**
     * Delete a folder template, and all content templates it contains.
     *
     * @param int $templateid The ID of the template to remove
     * @return  bool True of the template was marked deleted.
     */
    public static function delete($templateid) {
        global $DB;

        $params = ['foldertemplateid' => $templateid, 'status' => levels::ACTIVE];
        $contenttemplates = $DB->get_records('openstudio_content_templates', $params);
        foreach ($contenttemplates as $contenttemplate) {
            self::delete_content($contenttemplate->id, false);
        }
        $settemplate = new \stdClass();
        $settemplate->id = $templateid;
        $settemplate->status = levels::SOFT_DELETED;
        return self::update($settemplate);
    }

    /**
     * Delete a folder content template
     *
     * Any contents created for the deleted template will remain in place, but dissasociated from the template.
     *
     * @param int $templateid The ID of the template to remove
     * @param bool $fixorder Update the contentorder of templates following the deleted one?
     * @return bool True if the template was marked deleted.
     */
    public static function delete_content($templateid, $fixorder = true) {
        global $DB;

        $foldercontents = $DB->get_records('openstudio_folder_contents', ['foldercontenttemplateid' => $templateid]);
        foreach ($foldercontents as $foldercontent) {
            $foldercontent->foldercontenttemplateid = null;
            folder::update_content($foldercontent);
        }
        $contenttemplate = self::get_content($templateid);
        if ($contenttemplate) {
            $contenttemplate->status = levels::SOFT_DELETED;
            if ($fixorder) {
                $where = <<<EOF
            foldertemplateid = :foldertemplateid
        AND contentorder > :contentorder
        AND status >= :status
EOF;
                $params = [
                        'foldertemplateid' => $contenttemplate->foldertemplateid,
                        'contentorder' => $contenttemplate->contentorder,
                        'status' => levels::ACTIVE
                ];
                $followingcontents = $DB->get_records_select('openstudio_content_templates', $where, $params);
                foreach ($followingcontents as $followingcontent) {
                    $followingcontent->contentorder--;
                    $DB->update_record('openstudio_content_templates', $followingcontent, true);
                }
            }
            return self::update_content($contenttemplate);
        } else {
            throw new \coding_exception('$templateid passed to folder::delete_content_template must refer to the id ' .
                    'of a record in openstudio_content_templates');
        }
    }
}
