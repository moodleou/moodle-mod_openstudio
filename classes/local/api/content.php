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
 * API functions for content uploaded to OpenStudio.
 *
 * @package mod_openstudio
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

use mod_openstudio\local\util\defaults;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\api\tracking;
use mod_openstudio\local\util;

defined('MOODLE_INTERNAL') || die();


class content {
    const INFO_IMAGEDATA = 2;
    const INFO_GPSDATA = 1;

    const TYPE_NONE = 0;
    const TYPE_TEXT = 5;
    const TYPE_IMAGE = 10;
    const TYPE_IMAGE_EMBED = 11;
    const TYPE_VIDEO = 20;
    const TYPE_VIDEO_EMBED = 21;
    const TYPE_AUDIO = 30;
    const TYPE_AUDIO_EMBED = 31;
    const TYPE_DOCUMENT = 40;
    const TYPE_DOCUMENT_EMBED = 41;
    const TYPE_PRESENTATION = 50;
    const TYPE_PRESENTATION_EMBED = 51;
    const TYPE_SPREADSHEET = 60;
    const TYPE_SPREADSHEET_EMBED = 61;
    const TYPE_URL = 70;
    const TYPE_URL_IMAGE = 71;
    const TYPE_URL_VIDEO = 72;
    const TYPE_URL_AUDIO = 73;
    const TYPE_URL_DOCUMENT = 74;
    const TYPE_URL_DOCUMENT_PDF = 75;
    const TYPE_URL_DOCUMENT_DOC = 76;
    const TYPE_URL_PRESENTATION = 77;
    const TYPE_URL_PRESENTATION_PPT = 78;
    const TYPE_URL_SPREADSHEET = 79;
    const TYPE_URL_SPREADSHEET_XLS = 80;
    const TYPE_FOLDER = 100;
    const TYPE_FOLDER_CONTENT = 110;
    const TYPE_CAD = 120;
    const TYPE_ZIP = 130;

    const OWNERSHIP_MYOWNWORK = 0;
    const OWNERSHIP_FOUNDONLINE = 1;
    const OWNERSHIP_FOUNDELSEWHERE = 2;

    const VISIBILITY_PRIVATE = 1;
    const VISIBILITY_GROUP = 2;
    const VISIBILITY_MODULE = 3;
    const VISIBILITY_WORKSPACE = 4;
    const VISIBILITY_PRIVATE_PINBOARD = 5;
    const VISIBILITY_INFOLDERONLY = 6;
    const VISIBILITY_TUTOR = 7;
    const VISIBILITY_PEOPLE = 8;

    const UPDATEMODE_UPDATED = 1;
    const UPDATEMODE_CREATED = 2;
    const UPDATEMODE_BEINGEDIT = 3;
    const UPDATEMODE_NEWCONTENT = 4;
    const UPDATEMODE_FOLDER = 5;


    /**
     * Gets the total number of content items allowed on the user's studio work, and the number they have created.
     *
     * @param int $studioid Studio instance id.
     * @param int $userid Creator of the content.
     * @return object Returns studio work content totals
     */
    public static function get_total($studioid, $userid) {
        global $DB;

        $sql = <<<EOF
SELECT COUNT(l3.id)
  FROM {openstudio_level3} l3
  JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
  JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
 WHERE l1.openstudioid = ?
   AND l1.status >= 0

EOF;

        $contenttotal = $DB->count_records_sql($sql, array($studioid));

        $sql = <<<EOF
SELECT COUNT(c.id)
  FROM {openstudio_contents} c
  JOIN {openstudio_level3} l3 ON l3.id = c.levelid
  JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
  JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
 WHERE l1.openstudioid = ?
   AND c.openstudioid = l1.openstudioid
   AND c.levelcontainer = 3
   AND c.contenttype <> ?
   AND c.userid = ?

EOF;

        $contentused = $DB->count_records_sql($sql, array($studioid, self::TYPE_NONE, $userid));

        return (object) array(
                'used' => $contentused,
                'total' => $contenttotal);
    }

    /**
     * Gets the total number of content items allowed on the user's pinboard, and the number they have created.
     *
     * @param int $studioid Studio instance id.
     * @param int $userid Creator of the slot.
     * @return object Returns pinboard slot totals
     */
    public static function get_pinboard_total($studioid, $userid) {
        global $DB;

        $contentused = 0;
        $contentempty = 0;

        // Note: we do (deletedby - deletedby) so that the group by statement only has to group by
        // one value of 0 for all records that have the deletedby field populated.

        $sql = <<<EOF
  SELECT contenttype, deletedby AS deletestatus, count(id) AS contentcount
    FROM {openstudio_contents}
   WHERE openstudioid = ?
     AND userid = ?
     AND levelcontainer = 0
     AND levelid = 0
     AND visibility != ?
GROUP BY contenttype, deletedby, deletedtime
EOF;

        $countdata = $DB->get_recordset_sql($sql, array($studioid, $userid, self::VISIBILITY_INFOLDERONLY));
        if ($countdata->valid()) {
            foreach ($countdata as $data) {
                if ($data->contenttype != self::TYPE_NONE) {
                    $contentused = $contentused + ((int) $data->contentcount);
                    continue;
                }

                if (($data->contenttype == self::TYPE_NONE)
                        && (is_null($data->deletestatus) || ($data->deletestatus == 0))) {
                    $contentempty = $contentempty + ((int) $data->contentcount);
                }
            }
        }

        $contentavailable = 0;

        $contenttotal = (int) $DB->get_field('openstudio', 'pinboard', array('id' => $studioid));
        if ($contenttotal > 0) {
            $contentavailable = $contenttotal - $contentused - $contentempty;
            if ($contentavailable < 0) {
                $contentavailable = 0;
            }
        }

        return (object) array(
                'used' => $contentused,
                'empty' => $contentempty,
                'usedandempty' => $contentused + $contentempty,
                'total' => $contenttotal,
                'available' => $contentavailable);
    }

    /**
     * Check if content has a particular flag set.
     *
     * @param int $contentid Content item id.
     * @param int $flagid Flag id to check for.
     * @return bool Return true or false if flag is found for slot.
     */
    public static function has_flag($contentid, $flagid) {
        global $DB;

        return $DB->record_exists('openstudio_flags',
                array('slotid' => $contentid, 'flagid' => $flagid));
    }

    /**
     * Creates pinboard content populated with the data supplied.
     *
     * @param int $studioid Studio instance id.
     * @param int $userid Creator of the content.
     * @param object $data Content data.
     * @param object $cm Course module object - requred for search indexing.
     * @return int Returns newly created contentid
     */
    public static function create_in_pinboard($studioid, $userid, $data, $cm = null) {
        return self::create($studioid, $userid, 0, 0, $data, null, null, $cm);
    }

    /**
     * Return a single content record, by ID.
     *
     * @param int $id
     * @return object
     */
    public static function get($id) {
        global $DB;

        return $DB->get_record('openstudio_contents', array('id' => $id));
    }

    /**
     * Creates content populated with the data supplied.
     *
     * @param int $studioid Studio instance id.
     * @param int $userid Creator of the content.
     * @param int $level Content level; usually 0, 1, 2 or 3.
     * @param int $levelid Content level id.
     * @param array $data Content data.
     * @param array $file Content file attachment.
     * @param object $context Moodle context during Content creation.
     * @param object $cm Course module object - requred for search indexing.
     * @param bool $import Is this being created by import?
     * @return int Returns newly created content ID.
     */
    public static function create(
            $studioid, $userid, $level, $levelid, $data, $file = null, $context = null, $cm = null, $import = false) {
        global $DB;

        // Make sure $data is an array.
        $data = (array) $data;

        // We enforce $levelid, $level, $userid uniqueness if we are not
        // inseting into the the pinboard which has $level = 0.
        if ($level > 0) {
            $sql = <<<EOF
SELECT *
FROM {openstudio_contents}
WHERE levelid = ? AND levelcontainer = ? AND userid = ? AND deletedby IS NULL

EOF;

            $result = $DB->record_exists_sql($sql, array($levelid, $level, $userid));
            if ($result) {
                return false;
            }
        }

        if (($file !== null) && ($context !== null)) {
            $data = self::get_contenttype($data, $file);
        } else {
            $data = self::process_data($data);
        }

        $slotfileid = null;
        if (($file !== null) && ($context !== null)
                && ($data['contenttype'] !== self::TYPE_URL)) {
            // Get slot file id that will be associated the the slot's uploaded file.
            $slotfiledata = array('refcount' => 1);
            if ($import) {
                $slotfileid = $data['fileid'];
            } else if (pathinfo($file['file']->filename, PATHINFO_EXTENSION) == 'ipynb') {
                $slotfileid = $DB->insert_record('openstudio_content_files', $slotfiledata);
                file_save_draft_area_files($file['id'], $context->id, 'mod_openstudio', 'notebook', $slotfileid);
            } else {
                $slotfileid = $DB->insert_record('openstudio_content_files', $slotfiledata);
                file_save_draft_area_files($file['id'], $context->id, 'mod_openstudio', 'content', $slotfileid);
            }

            // Check if image type and create thumbnail if necessary.
            if (!empty($file['mimetype']['string']) && $file['mimetype']['string'] == 'image' && empty($file['retainimagemetadata'])) {
                self::strip_metadata_for_image($file, $context, $slotfileid);
            }
            self::create_thumbnail($data,
                    $context->id, 'mod_openstudio', 'content', $slotfileid, '/', $data['content']);
        }

        // Populate data.
        $insertdata = array();
        $insertdata['retainimagemetadata'] = isset($data['retainimagemetadata']) ? $data['retainimagemetadata'] : 0;
        $insertdata['openstudioid'] = $studioid;
        $insertdata['levelid'] = $levelid;
        $insertdata['levelcontainer'] = $level;
        $insertdata['contenttype'] = $data['contenttype'];
        $insertdata['fileid'] = $slotfileid;
        $insertdata['name'] = $data['name'];

        if (array_key_exists('mimetype', $data)) {
            $insertdata['mimetype'] = $data['mimetype'];
        }
        if (array_key_exists('content', $data)) {
            $insertdata['content'] = $data['content'];
        }
        if (array_key_exists('thumbnail', $data)) {
            $insertdata['thumbnail'] = $data['thumbnail'];
        }
        if (array_key_exists('urltitle', $data)) {
            $insertdata['urltitle'] = $data['urltitle'];
        }
        if (array_key_exists('description', $data)) {
            $insertdata['description'] = $data['description'];
        }
        $insertdata['showextradata'] = 0;
        if (array_key_exists('showextradata', $data)) {
            $insertdata['showextradata'] = $data['showextradata'];
        }
        if (array_key_exists('showgps', $data)) {
            $insertdata['showextradata'] = $insertdata['showextradata'] + $data['showgps'];
        }
        if (array_key_exists('showimagedata', $data)) {
            $insertdata['showextradata'] = $insertdata['showextradata'] + $data['showimagedata'];
        }
        if (array_key_exists('ownership', $data)) {
            $insertdata['ownership'] = $data['ownership'];
        }
        if (array_key_exists('ownershipdetail', $data)) {
            $insertdata['ownershipdetail'] = $data['ownershipdetail'];
        }
        if (array_key_exists('visibility', $data)) {
            $insertdata['visibility'] = $data['visibility'];
        }

        $insertdata['userid'] = $userid;
        $insertdata['timemodified'] = time();
        $insertdata['timeflagged'] = time();

        $contentid = $DB->insert_record('openstudio_contents', $insertdata);

        if ($contentid != false) {
            if (array_key_exists('tags', $data)) {
                tags::set($contentid, $data['tags']);
            }

            if (!isset($data['folderid'])) {
                if ($data['contenttype'] == self::TYPE_FOLDER) {
                    $data['folderid'] = $contentid;
                } else {
                    $data['folderid'] = null;
                }
            }
            tracking::log_action($contentid, tracking::CREATE_CONTENT, $userid, $data['folderid']);

            if (isset($data['visibility']) && ($data['visibility'] == self::VISIBILITY_TUTOR)) {
                tracking::log_action(
                        $contentid, tracking::UPDATE_CONTENT_VISIBILITY_TUTOR, $userid, $data['folderid']);
            }

            // Update search index for slot.
            $slotdata = self::get_record($userid, $contentid);
            if (($cm != null) && ($slotdata != false)) {
                search::update($cm, $slotdata);
            }

            // Log content hash for slot.
            item::log($contentid);
        }

        return $contentid;
    }

    /**
     * Update content populated with the data supplied.
     *
     * @param int $userid User updating the content.
     * @param int $contentid Content id.
     * @param array $data Content data.
     * @param array $file Content file upload data.
     * @param object $context Moodle context during content update.
     * @param bool $addversion Specifies whether existing content is versioned prior to update.
     * @param object $cm Course module object - requred for search indexing.
     * @param bool $import Is this being updated via import?
     * @param int $folderid The ID of the folder this content is within.
     * @return mixed Returns newly created slotid or false if error.
     */
    public static function update(
            $userid, $contentid, $data, $file = null, $context = null, $addversion = false, $cm = null,
            $import = false, $folderid = null) {
        global $DB;

        // Make sure $data is an array.
        $data = (array) $data;

        // Init provenance data.
        $provenanceupdate = null;

        try {
            $transaction = $DB->start_delegated_transaction();

            $contentdata = $contentdataold = $DB->get_record('openstudio_contents', array('id' => $contentid), '*', MUST_EXIST);
            $contentdata->retainimagemetadata = isset($data['retainimagemetadata']) ? $data['retainimagemetadata'] : 0;
            $previousvisibilitysetting = $contentdata->visibility;
            $currentvisibilitysetting = $data['visibility'];

            /*
             * Check if actual new version should be created.
             *
             * Content can contain file atatchment, embed code or weblink,
             * and the code below checks the content change in that order.
             */
            $shouldversion = false;
            if ($contentdata->contenttype != self::TYPE_NONE) {
                if (($file !== null) && ($context !== null)) {
                    // If the uploaded file has changed, then we should version.
                    if ($file['checksum'] != $data['checksum']) {
                        $shouldversion = true;
                    }
                } else {
                    // If the uploaded file has changed, then we should version.
                    if ($contentdata->fileid != '') {
                        $shouldversion = true;
                    } else {
                        // If the weblink or embed code has changed, then we should version.
                        if ($data['weblink'] != '') {
                            $embeddata = embedcode::is_ouembed_installed() ? embedcode::parse(embedcode::get_ouembed_api(), $data['weblink']) : false;
                            if ($embeddata) {
                                if ($contentdata->content != $embeddata->url) {
                                    $shouldversion = true;
                                }
                            } else {
                                if ($contentdata->content != $data['weblink']) {
                                    $shouldversion = true;
                                }
                            }
                        }
                    }
                }

                if ($shouldversion) {
                    // Regardless of whether versioning is turned on, if the content has changed,
                    // update the provenance data.
                    if ($folderid && $provenance = folder::get_content_provenance($folderid, $contentdata->id)) {
                        $provenanceupdate = (object) array(
                                'id' => $provenance->foldercontentid,
                                'provenancestatus' => folder::PROVENANCE_UNLINKED
                        );
                        // If the content is part of a folder and has provenance, then we need to folder
                        // the status to unlinked.
                        if ($provenance->provenanceid == $provenance->slotid) {
                            // If the folder content is currently just a soft-link, make it a full copy.
                            $newcontent = folder::copy_content($contentid, $provenance->userid, null, null, $cm);
                            if (array_key_exists('visibility', $data)) {
                                unset($data['visibility']);
                            }
                            // Set the folder content's ID to the new copy.
                            $provenanceupdate->contentid = $newcontent->id;
                            // Change the actual content we're updating to the copy.
                            $contentdata = $DB->get_record('openstudio_contents',
                                    array('id' => $newcontent->id), '*', MUST_EXIST);
                        }
                        folder::update_content($provenanceupdate);

                    } else if ($softlinks = folder::get_content_softlinks($contentdata->id)) {
                        // The content is linked to any folders, switch the folder contents to copies that will
                        // keep the original content.

                        foreach ($softlinks as $softlink) {
                            $newcontent = folder::copy_content($contentid, $userid, $softlink->name, $softlink->description, $cm);
                            $softlink->contentid = $newcontent->id;
                            $softlink->provenancestatus = folder::PROVENANCE_UNLINKED;
                            folder::update_content($softlink);
                        }

                    } else if ($copies = folder::get_content_copies($contentdata->id)) {
                        // If the content has copies, then we need to folder the status of the copies to unlinked.
                        foreach ($copies as $copy) {
                            $copy->provenancestatus = folder::PROVENANCE_UNLINKED;
                            folder::update_content($copy);
                        }
                    }
                }

                // Only version if versioning is turned on and content is not part of a folder.
                if (((bool) $addversion) && $shouldversion &&
                        ($contentdata->contenttype != self::TYPE_FOLDER) &&
                        ($contentdata->visibility != self::VISIBILITY_INFOLDERONLY)) {
                    $insertdata = array();
                    $insertdata['contentid'] = $contentdata->id;
                    $insertdata['contenttype'] = $contentdata->contenttype;
                    $insertdata['content'] = $contentdata->content;
                    $insertdata['fileid'] = $contentdata->fileid;
                    $insertdata['thumbnail'] = $contentdata->thumbnail;
                    $insertdata['urltitle'] = $contentdata->urltitle;
                    $insertdata['name'] = $contentdata->name;
                    $insertdata['description'] = $contentdata->description;
                    $insertdata['timemodified'] = time();

                    switch ($contentdata->contenttype) {
                        case self::TYPE_IMAGE:
                        case self::TYPE_VIDEO:
                        case self::TYPE_AUDIO:
                        case self::TYPE_DOCUMENT:
                        case self::TYPE_PRESENTATION:
                        case self::TYPE_SPREADSHEET:
                            $insertdata['mimetype'] = $contentdata->mimetype;
                            break;
                    }

                    $contentversionid = $DB->insert_record('openstudio_content_versions', $insertdata);
                    if ($contentversionid === false) {
                        throw new \Exception('Failed to add content version during content update.');
                    }

                    item::toversion($contentdata->id, $contentversionid);

                    $existingversioncount = contentversion::count($contentdata->id);
                    if ($existingversioncount > $addversion) {
                        contentversion::delete_oldest($contentdata->id, $userid);
                    }
                }

            }

            if (($file !== null) && ($context !== null)) {
                // If the uploaded file has changed (as determined by checksum), then
                // process uploaded file.
                //
                if ($file['checksum'] != $data['checksum']) {
                    $data = self::get_contenttype($data, $file);

                    $fs = get_file_storage();
                    // Get content file id that will be associated the the content's uploaded file.
                    $contentfiledata = array('refcount' => 1);
                    if ($import) {
                        $contentfileid = $data['fileid'];
                    } else {
                        $contentfileid = $DB->insert_record('openstudio_content_files', $contentfiledata);
                        $data['fileid'] = $contentfileid = $DB->insert_record('openstudio_content_files', $contentfiledata);
                        if (pathinfo($file['file']->filename, PATHINFO_EXTENSION) == 'ipynb') {
                            file_save_draft_area_files($file['id'], $context->id, 'mod_openstudio', 'notebook', $contentfileid);
                        } else {
                            // Save the uploaded file to Moodle repository.
                            file_save_draft_area_files($file['id'], $context->id, 'mod_openstudio', 'content', $contentfileid);
                        }
                    }
                    // Nullify irrelevant record fields.
                    $data['thumbnail'] = null;
                    $data['urltitle'] = null;

                    // Check if image type and create thumbnail if necessary.
                    if (!empty($file['mimetype']['string']) && $file['mimetype']['string'] == 'image' && empty($file['retainimagemetadata']) && !empty($contentfileid)) {
                        self::strip_metadata_for_image($file, $context, $contentfileid);
                    }
                    self::create_thumbnail($data,
                            $context->id, 'mod_openstudio', 'content', $contentfileid, '/', $data['content']);
                } else {
                    $contentfileid = $contentdata->fileid;
                    if (!empty($file['mimetype']['string']) && $file['mimetype']['string'] == 'image' && empty($file['retainimagemetadata']) && !empty($contentfileid)) {
                        self::strip_metadata_for_image($file, $context, $contentfileid);
                    }
                }

            } else {
                // Nullify irrelevant record fields.
                $data['content'] = '';
                $data['fileid'] = null;
                $data['thumbnail'] = null;
                $data['mimetype'] = null;

                // Process the content form for URL and embedded content.
                $data = self::process_data($data);
            }

            // Populate data.
            array_key_exists('contenttype', $data) ? ($contentdata->contenttype = $data['contenttype']) : null;
            array_key_exists('mimetype', $data) ? ($contentdata->mimetype = $data['mimetype']) : null;
            array_key_exists('content', $data) ? ($contentdata->content = $data['content']) : null;
            array_key_exists('fileid', $data) ? ($contentdata->fileid = $data['fileid']) : null;
            array_key_exists('thumbnail', $data) ? ($contentdata->thumbnail = $data['thumbnail']) : null;
            array_key_exists('urltitle', $data) ? ($contentdata->urltitle = $data['urltitle']) : null;
            if (array_key_exists('name', $data) || array_key_exists('description', $data)) {
                if ($folderid
                        && ($provenance = folder::get_content_provenance($folderid, $contentdata->id)) && !$shouldversion) {
                    // If the content is part of a folder and has provenance, and the content hasn't been changed...
                    $provenanceupdate = (object) array(
                            'id' => $provenance->foldercontentid,
                            'provenancestatus' => $provenance->provenancestatus
                    );
                    if (array_key_exists('name', $data)) {
                        // If the name has been changed, update it in the folder content record.
                        if ($data['name'] !== $contentdata->name) {
                            $provenanceupdate->name = $data['name'];
                            $provenanceupdate->provenancestatus = folder::PROVENANCE_EDITED;
                        } else {
                            $contentdata->name = $data['name'];
                        }
                    }
                    if (array_key_exists('description', $data)) {
                        // If the description has been changed, update it in the folder content record.
                        if ($data['description'] !== $contentdata->description) {
                            $provenanceupdate->description = $data['description'];
                            $provenanceupdate->provenancestatus = folder::PROVENANCE_EDITED;
                        } else {
                            $contentdata->description = $data['description'];
                        }
                    }

                    if ($provenanceupdate->provenancestatus == folder::PROVENANCE_EDITED) {
                        folder::update_content($provenanceupdate);
                    }
                } else {
                    array_key_exists('name', $data) ? ($contentdata->name = $data['name']) : null;
                    array_key_exists('description', $data) ? ($contentdata->description = $data['description']) : null;
                }
            }

            $contentdata->showextradata = 0;
            if (array_key_exists('showgps', $data)) {
                $contentdata->showextradata = $contentdata->showextradata + $data['showgps'];
            }
            if (array_key_exists('showimagedata', $data)) {
                $contentdata->showextradata = $contentdata->showextradata + $data['showimagedata'];
            }
            array_key_exists('ownership', $data) ? ($contentdata->ownership = $data['ownership']) : null;
            array_key_exists('ownershipdetail', $data) ? ($contentdata->ownershipdetail = $data['ownershipdetail']) : null;
            array_key_exists('visibility', $data) ? ($contentdata->visibility = $data['visibility']) : null;
            $contentdata->timemodified = time();

            $result = $DB->update_record('openstudio_contents', $contentdata);
            if ($shouldversion) {
                item::delete($contentdata->id);
                item::log($contentdata->id);
            }
            if ($result === false) {
                throw new \Exception('Failed to update content.');
            }

            tags::set($contentid, $data['tags'], true);

            if ($previousvisibilitysetting != $currentvisibilitysetting) {
                $trackingvisibiltyflag = false;
                switch ($currentvisibilitysetting) {
                    case self::VISIBILITY_PRIVATE:
                        $trackingvisibiltyflag = tracking::UPDATE_CONTENT_VISIBILITY_PRIVATE;
                        break;

                    case self::VISIBILITY_GROUP:
                        $trackingvisibiltyflag = tracking::UPDATE_CONTENT_VISIBILITY_GROUP;
                        break;

                    case self::VISIBILITY_MODULE:
                        $trackingvisibiltyflag = tracking::UPDATE_CONTENT_VISIBILITY_MODULE;
                        break;

                    case self::VISIBILITY_TUTOR:
                        $trackingvisibiltyflag = tracking::UPDATE_CONTENT_VISIBILITY_TUTOR;
                        break;

                }
                if ($trackingvisibiltyflag) {
                    tracking::log_action($contentdata->id, $trackingvisibiltyflag, $userid);
                }
            }

            tracking::log_action($contentdata->id, tracking::UPDATE_CONTENT, $userid, $folderid);
            if ($folderid) {
                tracking::log_action($folderid, tracking::MODIFY_FOLDER, $userid);
            }

            $transaction->allow_commit();

            // Update search index for content.
            if (($provenanceupdate == null) || ($provenanceupdate->provenancestatus < folder::PROVENANCE_UNLINKED)) {
                // Delete search index for old record.
                if (($cm != null) && ($contentdataold != false)) {
                    search::delete($cm, $contentdataold);
                }
            }
            $contentdata = self::get_record($userid, $contentdata->id);
            if (($cm != null) && ($contentdata != false)) {
                search::update($cm, $contentdata);
            }

            return $contentdata->id;
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return false;
    }

    /**
     * Delete the Content.
     *
     * @param int $userid User deleting the content.
     * @param int $contentid Slot id.
     * @param int $versioncount The maximum number of versions to keep.
     * @param object $cm Course module object - requred for search indexing.
     * @return bool Returns true or false for success or failure respectively.
     */
    public static function delete($userid, $contentid, $versioncount = 0, $cm = null) {
        return self::empty_content($userid, $contentid, false, $versioncount, $cm);
    }

    /**
     * Delete a content version.
     *
     * @param int $userid User deleting the content version.
     * @param int $contentversionid Slot version id.
     * @return bool Returns true or false for success or failure respectively.
     */
    public static function version_delete($userid, $contentversionid) {
        global $DB;

        try {
            $contentversiondata = $DB->get_record('openstudio_content_versions',
                    array('id' => $contentversionid), '*', MUST_EXIST);

            // Populate data.
            $contentversiondata->deletedby = $userid;
            $contentversiondata->deletedtime = time();

            $result = $DB->update_record('openstudio_content_versions', $contentversiondata);
            if ($result === false) {
                throw new \Exception('Failed to soft delete content.');
            }

            // Update tracking.
            tracking::log_action($contentversionid, tracking::DELETE_CONTENT_VERSION, $userid);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Empty a content.
     *
     * The empty function deletes all content data and content version data
     * and resets it back to starting position.
     *
     * @param int $userid User deleting the content.
     * @param int $contentid Slot id.
     * @param bool $deleteversions Set true to delete all versions from content.
     * @param int $versioncount The maximum number of versions to keep
     * @param object $cm Course module object - requred for search indexing.
     * @return bool Returns true or false for success or failure respectively.
     */
    public static function empty_content($userid, $contentid, $deleteversions = true, $versioncount = 0, $cm = null) {
        global $DB;

        try {
            $transaction = $DB->start_delegated_transaction();

            $contentdata = $DB->get_record('openstudio_contents',
                    array('id' => $contentid), '*', MUST_EXIST);

            // Delete search index for old record.
            if (($cm != null) && ($contentdata != false)) {
                search::delete($cm, $contentdata);
            }

            // We version the current content, so that existing data is preserved.
            if ($contentdata->contenttype != self::TYPE_NONE) {

                $insertdata = array();
                $insertdata['contentid'] = $contentdata->id;
                $insertdata['contenttype'] = $contentdata->contenttype;
                $insertdata['content'] = $contentdata->content;
                $insertdata['fileid'] = $contentdata->fileid;
                $insertdata['thumbnail'] = $contentdata->thumbnail;
                $insertdata['urltitle'] = $contentdata->urltitle;
                $insertdata['name'] = $contentdata->name;
                $insertdata['description'] = $contentdata->description;
                $insertdata['timemodified'] = time();

                switch ($contentdata->contenttype) {
                    case self::TYPE_IMAGE:
                    case self::TYPE_VIDEO:
                    case self::TYPE_AUDIO:
                    case self::TYPE_DOCUMENT:
                    case self::TYPE_PRESENTATION:
                    case self::TYPE_SPREADSHEET:
                        $insertdata['mimetype'] = $contentdata->mimetype;
                        break;
                }

                $contentversionid = $DB->insert_record('openstudio_content_versions', $insertdata);
                if ($contentversionid === false) {
                    throw new \Exception('Failed to add content version during content delete.');
                }

                if ($cm !== null) {
                    $context = \context_module::instance($cm->id);
                    openstudio_move_area_files_to_new_area('descriptionversion', $contentversionid, $context->id, 'description',
                            $contentdata->id);
                }

                item::toversion($contentid, $contentversionid);

                $existingversioncount = contentversion::count($contentdata->id);
                if ($existingversioncount > $versioncount) {
                    contentversion::delete_oldest($contentdata->id, $userid);
                }
            }

            if ($contentdata->visibility != self::VISIBILITY_INFOLDERONLY) {
                if ($cm != null) {
                    // Get module instance.
                    $cminstance = $DB->get_record('openstudio', array('id' => $cm->instance), '*', MUST_EXIST);
                    $contentdata->visibility = $cminstance->defaultvisibility;
                } else {
                    $contentdata->visibility = 0;
                }
            }

            // Setp up the content into its blank/empty state.
            if (($contentdata->levelid == 0) && ($contentdata->levelcontainer == 0)) {
                $contentdata->contenttype = self::TYPE_NONE;
            } else if ($contentdata->contenttype == self::TYPE_FOLDER) {
                $contentdata->contenttype = self::TYPE_FOLDER;
            } else {
                $contentdata->contenttype = self::TYPE_NONE;
            }
            $contentdata->content = '';
            $contentdata->thumbnail = '';
            $contentdata->urltitle = '';
            $contentdata->name = '';
            $contentdata->description = '';
            $contentdata->ownership = 0;
            $contentdata->ownershipdetail = '';
            $contentdata->deletedby = null;
            $contentdata->deletedtime = null;
            $contentdata->timemodified = time();
            $contentdata->mimetype = null;
            $contentdata->fileid = null;

            if ($deleteversions) {
                // NOTE: we generally do not delete contents and instead set the
                // contenttype to type::NONE.
                //
                // The exception is pinboard contents where we do do a soft delete and only
                // do it when the action is empty or versioning is not turned on and delete
                // happens.
                //
                if (($contentdata->levelid == 0) && ($contentdata->levelcontainer == 0)) {
                    $contentdata->deletedby = $userid;
                    $contentdata->deletedtime = time();
                }
            }

            $result = $DB->update_record('openstudio_contents', $contentdata);
            if ($result === false) {
                throw new \Exception('Failed to soft delete content.');
            }

            // Search for all content versions and soft delete them, if requested.
            if ($deleteversions) {
                $rs = $DB->get_recordset('openstudio_content_versions', array('contentid' => $contentid));
                foreach ($rs as $record) {
                    $record->deletedby = $userid;
                    $record->deletedtime = time();
                    $result = $DB->update_record('openstudio_content_versions', $record);
                }
                $rs->close();

                // Delete all comments associated with content.
                comments::delete_all($contentid, $userid);

                // Delete all tags associated with content.
                tags::remove($contentid);
            }

            // Delete all flags associated with content.
            flags::clear($contentid);

            // Update tracking.
            tracking::log_action($contentid, tracking::DELETE_CONTENT, $userid);

            $transaction->allow_commit();

            return true;
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return false;
    }

    /**
     * Restore a content version.
     *
     * @param int $userid User deleting the content.
     * @param int $contentversionid Content version id being restored.
     * @param \cm_info $cm Course module object
     * @return object|bool Returns the restored version content data.
     */
    public static function restore_version($userid, $contentversionid, $cm = null) {
        global $DB;

        try {
            $contentversiondata = $DB->get_record('openstudio_content_versions',
                    array('id' => $contentversionid), '*', MUST_EXIST);

            // First archive the current content data.
            if (self::archive($userid, $contentversiondata->contentid, false, $cm)) {

                $contentdata = $DB->get_record('openstudio_contents',
                        array('id' => $contentversiondata->contentid), '*', MUST_EXIST);

                $contentdata->contenttype = $contentversiondata->contenttype;
                $contentdata->content = $contentversiondata->content;
                $contentdata->thumbnail = $contentversiondata->thumbnail;
                $contentdata->urltitle = $contentversiondata->urltitle;
                $contentdata->name = $contentversiondata->name;
                $contentdata->description = $contentversiondata->description;
                $contentdata->mimetype = $contentversiondata->mimetype;
                $contentdata->fileid = $contentversiondata->fileid;
                $contentdata->deletedby = null;
                $contentdata->deletedtime = null;
                $contentdata->timemodified = time();

                $result = $DB->update_record('openstudio_contents', $contentdata);
                if ($result === false) {
                    throw new \Exception('Failed to update content.');
                }

                if ($cm !== null) {
                    $context = \context_module::instance($cm->id);
                    openstudio_move_area_files_to_new_area('description', $contentversiondata->contentid, $context->id,
                            'descriptionversion', $contentversionid, false);
                }
                tracking::log_action($contentdata->id, tracking::UPDATE_CONTENT, $userid);

                // Update search index for content.
                $contentdata = self::get_record($userid, $contentversiondata->contentid);
                if (($cm != null) && ($contentdata != false)) {
                    search::update($cm, $contentdata);
                }

                return $contentdata;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get content record if given studioid and activity level ids.
     * Can only return activity contents, pinboard contents will cause the
     * function to return false.
     *
     * @param int $studioid Studio instance id.
     * @param int $userid Slot owner user id.
     * @param int $levelcontainer Level container.
     * @param int $levelid Level id.
     * @return mixed Return the content record data.
     */
    public static function get_record_via_levels($studioid, $userid, $levelcontainer, $levelid) {
        global $DB;

        if ($levelcontainer <= 0) {
            return false;
        }

        $contentdata = $DB->get_record('openstudio_contents',
                array('openstudioid' => $studioid,
                        'userid' => $userid,
                        'levelid' => $levelid,
                        'levelcontainer' => $levelcontainer));

        return $contentdata;
    }

    /**
     * Get content record.
     *
     * @param int $viewerid User reading the content.
     * @param int $contentid Slot id of the content to get.
     * @return object $content Return the content record data.
     */
    public static function get_record($viewerid, $contentid) {
        global $DB;

        $sql = <<<EOF
         SELECT l3.id AS l3id, l2.id AS l2id, l1.id AS l1id,
                l3.name AS l3name, l2.name AS l2name, l1.name AS l1name,
                u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
                u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                s.*
           FROM {openstudio_contents} s
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = s.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
     INNER JOIN {user} u ON u.id = s.userid
          WHERE s.id = ?

EOF;

        $contentdata = $DB->get_record_sql($sql, array($contentid));
        if ($contentdata === false) {
            return false;
        }

        $contentdata = util::add_additional_content_data($contentdata);
        if ($contentdata->userid == $viewerid) {
            $contentdata->isownedbyviewer = true;
        } else {
            $contentdata->isownedbyviewer = false;
        }
        $contentdata->visibilitycontext = self::VISIBILITY_PRIVATE;
        if (!$contentdata->isownedbyviewer) {
            // We need to get the course associated with the content.
            $cm = util::get_coursemodule_from_studioid($contentdata->openstudioid);
            if ($cm === false) {
                throw new \moodle_exception('errorunexpectedbehaviour', 'openstudio', '');
            }

            $ismember = group::is_content_group_member(
                    $cm->groupmode, $contentdata->visibility, $cm->groupingid, $contentdata->userid, $viewerid);
            if ($ismember) {
                $contentdata->visibilitycontext = self::VISIBILITY_GROUP;
            } else {
                $contentdata->visibilitycontext = self::VISIBILITY_MODULE;
            }
        }

        $contenttags = tags::get($contentid);
        if (!empty($contenttags)) {
            $contentdata->tagsraw = $contenttags;

            $contentdata->tags = array();
            foreach ($contenttags as $tag) {
                $contentdata->tags[] = $tag->name;
            }
        } else {
            $contentdata->tagsraw = array();
            $contentdata->tags = array();
        }

        return $contentdata;
    }

    /**
     * Get all content records, optionally limited by studio instance.
     *
     * @param int $studioid
     * @return \moodle_recordset|bool The content records, or false if there are none.
     */
    public static function get_all_records($studioid = 0) {
        global $DB;

        $wheresql = '';
        $params = array();
        if ($studioid > 0) {
            $wheresql = 'WHERE c.openstudioid = ?';
            $params[] = $studioid;
        }

        $sql = <<<EOF
         SELECT l3.id AS l3id, l2.id AS l2id, l1.id AS l1id,
                l3.name AS l3name, l2.name AS l2name, l1.name AS l1name,
                u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
                u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                c.*
           FROM {openstudio_contents} c
LEFT OUTER JOIN {openstudio_level3} l3 ON l3.id = c.levelid
LEFT OUTER JOIN {openstudio_level2} l2 ON l2.id = l3.level2id
LEFT OUTER JOIN {openstudio_level1} l1 ON l1.id = l2.level1id
     INNER JOIN {user} u ON u.id = c.userid

{$wheresql}

EOF;

        $contents = $DB->get_recordset_sql($sql, $params);
        if (!$contents->valid()) {
            return false;
        }

        return $contents;
    }


    /**
     * Check if a user has tutor view permissions for a slot
     *
     * @param $contentid int
     * @param $userid int
     * @param $tutorroles array
     * @return bool True if the user has the "tutor" role set for the studio instance.
     */
    public static function user_is_tutor($contentid, $userid, $tutorroles) {
        global $DB;
        if (empty($tutorroles)) {
            return false;
        }
        list($rolesql, $roleparams) = $DB->get_in_or_equal($tutorroles);
        $sql = <<<EOF
           SELECT *
             FROM {role_assignments} ra
             JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
             JOIN {course} c ON c.id = ctx.instanceid
             JOIN {openstudio} st ON c.id = st.course
             JOIN {openstudio_contents} s1 ON s1.openstudioid = st.id
             JOIN {groups_members} tgm1 ON tgm1.userid = s1.userid
             JOIN {groups} tg1 ON tg1.id = tgm1.groupid AND tg1.courseid = c.id
             JOIN {groups_members} tgm2 ON tg1.id = tgm2.groupid
             JOIN {course_modules} cm ON cm.instance = st.id
             JOIN {modules} m ON m.id = cm.module AND m.name = 'openstudio'
             JOIN {groupings_groups} gg ON gg.groupingid = cm.groupingid AND gg.groupid = tg1.id
            WHERE ra.roleid {$rolesql}
              AND ra.userid = ?
              AND s1.id = ?
              AND tgm2.userid = ?
EOF;
        $params = array(
                $userid,
                $contentid,
                $userid
        );
        return $DB->record_exists_sql($sql, array_merge($roleparams, $params));
    }

    /**
     * Extract GPS data from image file's EXIF info.
     *
     * @param int $contextid Image file context ID
     * @param string $component Image file component name
     * @param string $filearea Image file filearea name
     * @param int $itemid Image file item ID
     * @param string $path Image file path
     * @param string $filename Image file filename.
     * @return array GPS data.
     */
    public static function get_image_exif_data(
            $contextid, $component, $filearea, $itemid, $path, $filename) {

        global $CFG;

        $info = array();

        if (isset($CFG->filedir)) {
            $filedir = $CFG->filedir;
        } else {
            $filedir = $CFG->dataroot . '/filedir';
        }

        try {
            $fs = get_file_storage();
            $file = $fs->get_file($contextid, $component, $filearea, $itemid, $path, $filename);
            if ($file) {
                $contenthash = $file->get_contenthash();
                $l1 = $contenthash[0].$contenthash[1];
                $l2 = $contenthash[2].$contenthash[3];
                $pathfile = "{$filedir}/{$l1}/{$l2}/{$contenthash}";
                $info = @exif_read_data($pathfile);

                if (isset($info['GPSLatitude']) && isset($info['GPSLongitude']) &&
                        isset($info['GPSLatitudeRef']) && isset($info['GPSLongitudeRef']) &&
                        in_array($info['GPSLatitudeRef'], array('E', 'W', 'N', 'S'))
                        && in_array($info['GPSLongitudeRef'], array('E', 'W', 'N', 'S'))) {

                    $gpslatituderef  = strtolower(trim($info['GPSLatitudeRef']));
                    $gpslongituderef = strtolower(trim($info['GPSLongitudeRef']));

                    $latdegreesa = explode('/', $info['GPSLatitude'][0]);
                    $latminutesa = explode('/', $info['GPSLatitude'][1]);
                    $latsecondsa = explode('/', $info['GPSLatitude'][2]);
                    $lngdegreesa = explode('/', $info['GPSLongitude'][0]);
                    $lngminutesa = explode('/', $info['GPSLongitude'][1]);
                    $lngsecondsa = explode('/', $info['GPSLongitude'][2]);

                    $latdegrees = $latdegreesa[0] / $latdegreesa[1];
                    $latminutes = $latminutesa[0] / $latminutesa[1];
                    $latseconds = $latsecondsa[0] / $latsecondsa[1];
                    $lngdegrees = $lngdegreesa[0] / $lngdegreesa[1];
                    $lngminutes = $lngminutesa[0] / $lngminutesa[1];
                    $lngseconds = $lngsecondsa[0] / $lngsecondsa[1];

                    $lat = (float) $latdegrees + ((($latminutes * 60) + ($latseconds)) / 3600);
                    $lng = (float) $lngdegrees + ((($lngminutes * 60) + ($lngseconds)) / 3600);

                    // If the latitude is South, make it negative.
                    $gpslatituderef == 's' ? $lat *= -1 : '';

                    // If the longitude is west, make it negative.
                    $gpslongituderef == 'w' ? $lng *= -1 : '';

                    $info['GPSData'] = array(
                            'lat' => $lat,
                            'lng' => $lng
                    );
                } else {
                    $info['GPSData'] = array();
                }
            }
            return $info;
        } catch (\Exception $e) {
            return array();
        }

    }

    /**
     * Archives a content into content version.
     *
     * @param int $userid
     * @param int $contentid
     * @param bool $logaction
     * @param \cm_info $cm
     * @return bool Return true if successful.
     */
    private static function archive($userid, $contentid, $logaction = true, $cm = null) {
        global $DB;

        try {
            $contentdata = $DB->get_record('openstudio_contents',
                    array('id' => $contentid), '*', MUST_EXIST);

            // We version the current content, so that existing data
            // is preserved before being marked as soft deleted during
            // the EMPTY operation.
            $insertdata = array();
            $insertdata['contentid'] = $contentdata->id;
            $insertdata['contenttype'] = $contentdata->contenttype;
            $insertdata['content'] = $contentdata->content;
            $insertdata['fileid'] = $contentdata->fileid;
            $insertdata['thumbnail'] = $contentdata->thumbnail;
            $insertdata['urltitle'] = $contentdata->urltitle;
            $insertdata['name'] = $contentdata->name;
            $insertdata['description'] = $contentdata->description;
            $insertdata['timemodified'] = time();

            switch ($contentdata->contenttype) {
                case self::TYPE_IMAGE:
                case self::TYPE_VIDEO:
                case self::TYPE_AUDIO:
                case self::TYPE_DOCUMENT:
                case self::TYPE_PRESENTATION:
                case self::TYPE_SPREADSHEET:
                    $insertdata['mimetype'] = $contentdata->mimetype;
                    break;
            }

            $contentversionid = $DB->insert_record('openstudio_content_versions', $insertdata);
            if ($contentversionid === false) {
                throw new \Exception('Failed to add content version during content delete.');
            }

            if ($cm !== null) {
                $context = \context_module::instance($cm->id);
                openstudio_move_area_files_to_new_area('descriptionversion', $contentversionid, $context->id, 'description',
                        $contentdata->id);
            }
            item::toversion($contentid, $contentversionid);

            // Update tracking.
            if ($logaction) {
                tracking::log_action($contentid, tracking::ARCHIVE_CONTENT, $userid);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * Create content thumbnail picture.
     *
     * @param array $data Content data.
     * @param int $contextid
     * @param int $component
     * @param int $filearea
     * @param int $itemid
     * @param int $path
     * @param int $filename
     * @param string $newfilearea
     * @return array|bool Return the content record data, or false if generation failed.
     */
    private static function create_thumbnail($data,
            $contextid, $component, $filearea, $itemid, $path, $filename,
            $newfilearea = 'contentthumbnail') {

        $data = (array)$data;

        // Check content is a file upload that is of type image.
        if ($data['contenttype'] === self::TYPE_IMAGE && $data['mimetype'] != 'image/bmp') {
            try {
                $fs = get_file_storage();

                // Check content file exists.
                $contentfile = $fs->get_file(
                        $contextid, $component, $filearea, $itemid, $path, $filename);
                if ($contentfile) {
                    // Check if content file has thumbnail already, if so delete it.
                    $contenttumbnailfile = $fs->get_file(
                            $contextid, $component, $newfilearea, $itemid, $path, $filename);
                    if ($contenttumbnailfile) {
                        $contenttumbnailfile->delete();
                    }

                    // Check if uploaded image is larger than default thumbnail site,
                    // and if so, we will resize it.
                    $contentimageinfo = $contentfile->get_imageinfo();
                    if ($contentimageinfo['width'] > defaults::CONTENTTHUMBNAIL_WIDTH) {

                        // Note: when using File Storage API to resize images, it produces
                        // a grainy resized image.  The problem is because it uses the function
                        // imagecopyresized() rather than imagecopyresampled().  The latter
                        // produces better quality image, but takes longer to process.
                        //
                        // May need to post request to Moodle HQ to change this? For more details, see:
                        // http://www.sitepoint.com/forums/showthread.php?627228-Grainy-images-using-GD.

                        // Now create new thumbnail file.
                        $contenttumbnailfile = array(
                                'contextid' => $contentfile->get_contextid(),
                                'component' => $contentfile->get_component(),
                                'filearea'  => $newfilearea,
                                'itemid'    => $contentfile->get_itemid(),
                                'filepath'  => $contentfile->get_filepath(),
                                'filename'  => $contentfile->get_filename(),
                                'userid'    => $contentfile->get_userid()
                        );
                        $thumbnail = @$fs->convert_image(
                                $contenttumbnailfile, $contentfile, defaults::CONTENTTHUMBNAIL_WIDTH, null, true, null);
                        self::rotate_thumbnail_to_original($contentfile->get_id(), $thumbnail, $contenttumbnailfile);
                    } else {
                        $contenttumbnailfile = array(
                                'filearea' => $newfilearea, 'itemid' => $contentfile->get_itemid());
                        $fs->create_file_from_storedfile($contenttumbnailfile, $contentfile);
                    }

                    return $data;
                }
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;

    }


    /**
     * Determine the content type based on the content's mimetype.
     *
     * @param array $data Slot data.
     * @param array $file
     * @return array Return the content record data.
     */
    private static function get_contenttype($data, $file) {
        $contenttype = null;

        if (array_key_exists('mimetype', $file)) {
            if (array_key_exists('groups', $file['mimetype'])) {
                $filegroups = $file['mimetype']['groups'];
                if (in_array('image', $filegroups)) {
                    $contenttype = self::TYPE_IMAGE;
                } else if (in_array('video', $filegroups) || in_array('web_video', $filegroups)) {
                    $contenttype = self::TYPE_VIDEO;
                } else if (in_array('audio', $filegroups)) {
                    $contenttype = self::TYPE_AUDIO;
                } else if (in_array('document', $filegroups)) {
                    $contenttype = self::TYPE_DOCUMENT;
                } else if (in_array('presentation', $filegroups)) {
                    $contenttype = self::TYPE_PRESENTATION;
                } else if (in_array('spreadsheet', $filegroups)) {
                    $contenttype = self::TYPE_SPREADSHEET;
                }
            }

            // If we cant determine file type from file group, then try th file extension.
            if ($contenttype == null) {
                if (array_key_exists('extension', $file['mimetype'])) {
                    $fileextension = $file['mimetype']['extension'];
                    if (in_array($fileextension, array('jpg', 'jpe', 'jpeg', 'gif', 'png'))) {
                        $contenttype = self::TYPE_IMAGE;
                    } else if (in_array($fileextension, array('avi', 'mpg', 'mpeg', 'mov', 'mp4', 'm4v', 'flv', 'gif'))) {
                        $contenttype = self::TYPE_VIDEO;
                    } else if (in_array($fileextension, array('aiff', 'wav', 'mp3', 'm4a'))) {
                        $contenttype = self::TYPE_AUDIO;
                    } else if (in_array($fileextension, array('doc', 'docx', 'rtf', 'pdf', 'odt', 'fdf', 'nbk'))) {
                        $contenttype = self::TYPE_DOCUMENT;
                    } else if (in_array($fileextension, array('ppt', 'pptx', 'odp'))) {
                        $contenttype = self::TYPE_PRESENTATION;
                    } else if (in_array($fileextension, array('xls', 'xlsx', 'csv', 'ods'))) {
                        $contenttype = self::TYPE_SPREADSHEET;
                    } else if (in_array($fileextension, array('dwg', 'stl', 'stp', 'eps', 'dxf'))) {
                        $contenttype = self::TYPE_CAD;
                    } else if (in_array($fileextension, array('zip'))) {
                        $contenttype = self::TYPE_ZIP;
                    }
                }
            }
        }

        // If mime type is application/postscript force studio content type to cad (Moodle says image).
        if (array_key_exists('mimetype', $file)) {
            if ($file['mimetype']['type'] == 'application/postscript') {
                $contenttype = self::TYPE_CAD;
            }
        }

        if ($contenttype == null) {
            $contenttype = self::TYPE_DOCUMENT;
        }

        $data['contenttype'] = $contenttype;
        $data['content'] = $file['file']->filename;
        $data['mimetype'] = $file['mimetype']['type'];

        return $data;
    }

    /**
     * This function checks if the content contains a web link, validates is and sets the content type.
     *
     * @param array $data content data.
     * @return array Return the content record data.
     */
    private static function process_data($data) {

        // If content type is folder, then no further processing required.
        if (array_key_exists('contenttype', $data)) {
            if ($data['contenttype'] === self::TYPE_FOLDER) {
                return $data;
            }
        }

        // Set default content type.
        $data['contenttype'] = null;

        // Check if submitted data involves embedded code.
        if (!array_key_exists('content', $data) || (trim($data['content']) == '')) {
            // Execute logic to decipher embed code and extract key information to
            // store in slot record.
            $embeddata = false;
            if (embedcode::is_ouembed_installed()) {
                $embedapi = embedcode::get_ouembed_api();
                if (!empty($data['weblink'])) {
                    $embeddata = embedcode::parse($embedapi, $data['weblink']);
                }
                if (($embeddata === false) && !empty($data['embedcode'])) {
                    $embeddata = embedcode::parse($embedapi, $data['embedcode']);
                }
                if ($embeddata !== false) {
                    $data['weblink'] = $embeddata->url;
                    if (empty($data['urltitle'])) {
                        $data['urltitle'] = empty($embeddata->title) ? $data['name'] : $embeddata->title;
                    }
                    $data['thumbnail'] = $embeddata->thumbnailurl;
                    $data['contenttype'] = $embeddata->type;
                    $data['content'] = '';
                    $data['embedcode'] = '';
                }
            }
        }

        // Check if submitted data involves web link reference.
        if (array_key_exists('content', $data) && (trim($data['content']) != '')) {
            unset($data['urltitle']);
        } else {
            $data['urltitle'] = trim($data['urltitle']);
            $data['weblink'] = trim($data['weblink']);
            if ($data['weblink'] !== '') {
                if ((stripos($data['weblink'], 'http://') !== false)
                        || (stripos($data['weblink'], 'https://') !== false)
                        || (stripos($data['weblink'], 'ftp://') !== false)) {
                    $data['content'] = $data['weblink'];
                    if (!in_array($data['contenttype'], array(self::TYPE_URL,
                            self::TYPE_URL_IMAGE,
                            self::TYPE_URL_VIDEO,
                            self::TYPE_URL_AUDIO,
                            self::TYPE_URL_DOCUMENT,
                            self::TYPE_URL_DOCUMENT_PDF,
                            self::TYPE_URL_DOCUMENT_DOC,
                            self::TYPE_URL_PRESENTATION,
                            self::TYPE_URL_PRESENTATION_PPT,
                            self::TYPE_URL_SPREADSHEET,
                            self::TYPE_URL_SPREADSHEET_XLS))) {
                        $data['contenttype'] = self::TYPE_URL;
                    }
                } else {
                    unset($data['urltitle']);
                    unset($data['weblink']);
                }
            } else {
                unset($data['urltitle']);
                unset($data['weblink']);
            }
        }

        if ($data['contenttype'] === null) {
            if (trim($data['description']) !== '') {
                $data['contenttype'] = self::TYPE_URL;
            } else {
                $data['contenttype'] = self::TYPE_NONE;
            }
        }

        if ($data['contenttype'] == self::TYPE_URL) {
            if (!isset($data['weblink']) || ($data['weblink'] == '')) {
                $data['contenttype'] = self::TYPE_TEXT;
            }
        }

        return $data;
    }

    /**
     * Restore deleted content in folder.
     *
     * @param int $userid Content owner id.
     * @param int $contentdata Content data.
     * @param int $versionid Version id.
     * @param int $folderid Set id.
     * @throws \moodle_exception.
     * @return int|bool Returns the restored folder id.
     */
    public static function undelete_in_folder($userid, $contentdata, $versionid, $folderid, $cm = null) {
        global $DB;
        $newcontentid = 0;
        try {
            if ($contentdata->visibility == self::VISIBILITY_INFOLDERONLY) {
                self::undelete($userid, $contentdata->id, $versionid, $cm);
            } else {
                // Insert record, copy content version to new content.
                $contentversiondata = $DB->get_record('openstudio_content_versions',
                        array('contentid' => $contentdata->id, 'id' => $versionid), '*', MUST_EXIST);

                $insertdata = array(
                    'openstudioid' => $contentdata->openstudioid,
                    'levelid' => $contentdata->levelid,
                    'levelcontainer' => $contentdata->levelcontainer,
                    'contenttype' => $contentversiondata->contenttype,
                    'content' => $contentversiondata->content,
                    'thumbnail' => $contentversiondata->thumbnail,
                    'urltitle' => $contentversiondata->urltitle,
                    'name' => $contentversiondata->name,
                    'description' => $contentversiondata->description,
                    'textformat' => $contentversiondata->textformat,
                    'mimetype' => $contentversiondata->mimetype,
                    'fileid' => $contentversiondata->fileid,
                    'visibility' => self::VISIBILITY_INFOLDERONLY,
                    'userid' => $userid,
                    'deletedby' => null,
                    'deletedtime' => null,
                    'timemodified' => time(),
                    'timeflagged' => time()
                );

                $newcontentid = $DB->insert_record('openstudio_contents', (object)$insertdata);
                $DB->set_field('openstudio_folder_contents', 'contentid', $newcontentid,
                        array('folderid' => $folderid, 'contentid' => $contentdata->id, 'status' => levels::SOFT_DELETED));
                $DB->set_field('openstudio_folder_contents', 'provenancestatus', null,
                        array('folderid' => $folderid, 'contentid' => $newcontentid));
            }
            if ($newcontentid > 0) {
                $contentdata->id = $newcontentid;
            }

            $result = $DB->get_records('openstudio_folder_contents',
                    array('folderid' => $folderid, 'contentid' => $contentdata->id, 'status' => levels::SOFT_DELETED),
                    null, '*');

            if ($result) {
                $foldercontentsversion = reset($result);
                // Get all content in folder with status default.
                $foldercontents = $DB->get_records('openstudio_folder_contents',
                        array('folderid' => $folderid, 'status' => levels::ACTIVE), 'contentorder ASC', '*');
                // Update status content version to status default.
                $DB->set_field('openstudio_folder_contents', 'status', levels::ACTIVE,
                        array('id' => $foldercontentsversion->id));
                // Change content version to first order.
                array_unshift($foldercontents, $foldercontentsversion);
                $counter = 0;
                foreach ($foldercontents as $foldercontentinstance) {
                    $counter++;
                    $DB->set_field('openstudio_folder_contents', 'contentorder', $counter,
                            array('folderid' => $folderid, 'id' => $foldercontentinstance->id));
                }
            }
            return $folderid;

        } catch (\moodle_exception $e) {
            return false;
        }
    }

    /**
     * Restore deleted content.
     * @param int $userid User id.
     * @param int $contentversionid Content id.
     * @param int $versionid Version id.
     * @throws \moodle_exception.
     * @return object|bool Returns the restored content data.
     */
    public static function undelete($userid, $contentversionid, $versionid, $cm = null) {
        global $DB;
        try {
            $contentversiondata = $DB->get_record('openstudio_content_versions',
                    array('contentid' => $contentversionid, 'id' => $versionid), '*', MUST_EXIST);

            $contentdata = $DB->get_record('openstudio_contents',
                    array('id' => $contentversiondata->contentid), '*', MUST_EXIST);

            $contentdata->contenttype = $contentversiondata->contenttype;
            $contentdata->content = $contentversiondata->content;
            $contentdata->thumbnail = $contentversiondata->thumbnail;
            $contentdata->urltitle = $contentversiondata->urltitle;
            $contentdata->name = $contentversiondata->name;
            $contentdata->description = $contentversiondata->description;
            $contentdata->textformat = $contentversiondata->textformat;
            $contentdata->mimetype = $contentversiondata->mimetype;
            $contentdata->fileid = $contentversiondata->fileid;
            $contentdata->deletedby = null;
            $contentdata->deletedtime = null;
            $contentdata->timemodified = time();

            // Restore content from content version choose.
            $result = $DB->update_record('openstudio_contents', $contentdata);
            if ($result === false) {
                return false;
            } else {
                // Delete content version after restore.
                $resultdelete = $DB->delete_records('openstudio_content_versions', array('id' => $versionid));
                if ($resultdelete === false) {
                    return false;
                }
            }
            tracking::log_action($contentdata->id, tracking::UPDATE_CONTENT, $userid);

            // Update search index for content.
            $contentdata = self::get_record($userid, $contentversiondata->contentid);
            if (($cm != null) && ($contentdata != false)) {
                search::update($cm, $contentdata);
            }

            return $contentdata;
        } catch (\moodle_exception $e) {
            return false;
        }
    }

    /**
     * Strip metadata for image.
     *
     * @param array $file
     * @param \context $context
     * @param int $slotfileid itemid of file.
     * @throws \ImagickException
     * @throws \coding_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public static function strip_metadata_for_image($file, $context, $slotfileid) {
        global $PAGE;
        if (!extension_loaded('imagick') || !class_exists('Imagick')) {
            return;
        }
        $fs = new \file_storage();
        $realfile = $fs->get_file($context->id, 'mod_openstudio', 'content', $slotfileid,
                $file['file']->filepath, $file['file']->filename);
        $tmproot = make_temp_directory('tempimage');
        $tmpfilepath = $tmproot . '/' . $realfile->get_contenthash();
        $realfile->copy_content_to($tmpfilepath);
        try {
            $img = new \Imagick($tmpfilepath);
            $profiles = $img->getImageProfiles('icc', true);
            $img->stripImage();
            if (!empty($profiles)) {
                $img->profileImage('icc', $profiles['icc']);
            }
        } catch (\ImagickException $e) {
            // Throw a more helpful error if processing image fails.
            throw new \moodle_exception('errorimageprocess', 'openstudio', $PAGE->url, $e->getMessage());
        }
        $img->writeImage($tmpfilepath);
        $img->clear();
        $filerecord = new \stdClass();
        $filerecord->contextid = $context->id;
        $filerecord->component = 'mod_openstudio';
        $filerecord->filearea = 'contenttemp';
        $filerecord->filepath = $file['file']->filepath;
        $filerecord->filename = $file['file']->filename;
        $filerecord->itemid = $file['id'];
        // Create files.
        $newfile = $fs->create_file_from_pathname($filerecord, $tmpfilepath);
        // Replace new content to old content.
        $realfile->replace_file_with($newfile);
        // Delete the temp file.
        $newfile->delete();
        unlink($tmpfilepath);
    }

    /**
     * @param int|\stored_file $originalfileid original file id or stored file object
     * @param \stored_file $thumbnailfile thumbnail file object
     * @param \stdClass|array $filerecord object or array describing thumbnail file
     * @return \stored_file|bool
     * @throws \file_exception
     */
    public static function rotate_thumbnail_to_original($originalfileid, $thumbnailfile, $filerecord) {
        global $CFG;
        $fs = get_file_storage();

        if ($originalfileid instanceof \stored_file) {
            $originalfileid = $originalfileid->get_id();
        }

        if (!$originalfile = $fs->get_file_by_id($originalfileid)) {
            throw new \file_exception('storedfileproblem', 'File does not exist');
        }
        if (!$imageinfo = $originalfile->get_imageinfo()) {
            throw new \file_exception('storedfileproblem', 'File is not an image');
        }

        if (isset($CFG->filedir)) {
            $filedir = $CFG->filedir;
        } else {
            $filedir = $CFG->dataroot . '/filedir';
        }

        if (!isset($filerecord['mimetype'])) {
            $filerecord['mimetype'] = $imageinfo['mimetype'];
        }

        try {
            $contenthash = $originalfile->get_contenthash();
            $l1 = $contenthash[0] . $contenthash[1];
            $l2 = $contenthash[2] . $contenthash[3];
            $pathfile = "{$filedir}/{$l1}/{$l2}/{$contenthash}";
            $exif = @exif_read_data($pathfile);

            // Rotating image.
            $image = imagecreatefromstring($thumbnailfile->get_content());
            if (isset($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                }

                // Get image content.
                ob_start();
                switch ($filerecord['mimetype']) {
                    case 'image/gif':
                        imagegif($image);
                        break;

                    case 'image/jpeg':
                        imagejpeg($image);
                        break;

                    case 'image/png':
                        imagepng($image);
                        break;

                    default:
                        throw new file_exception('storedfileproblem', 'Unsupported mime type');
                }
                $content = ob_get_contents();
                ob_end_clean();
                imagedestroy($image);

                // Replace to rotated image.
                $thumbnailfile->delete();
                return $fs->create_file_from_string($filerecord, $content);
            }

        } catch (\Exception $ex) {
            return false;
        }
    }
}
