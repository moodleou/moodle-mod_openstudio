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
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\api\comments;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\api\template;
use mod_openstudio\local\api\flags;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

class mod_openstudio_generator extends testing_module_generator {

    /**
     * Populates array with content data to create content(s).
     */
    public function generate_single_data_array() {
        return [
            'name' => 'The GoT Vesica Timeline',
            'attachments' => '',
            'embedcode' => '',
            'weblink' => 'http://www.open.ac.uk/',
            'urltitle' => 'Vesica Timeline',
            'visibility' => content::VISIBILITY_MODULE,
            'description' => 'The Best YouTube Link Ever',
            'tags' => array('Stark', 'Lannister', 'Targereyen'),
            'ownership' => 0,
            'sid' => 0 // For a new content.
        ];
    }

    /**
     * Creates a new stdClass() with information for a file.
     *
     * @param object $user User to use as the file author.
     * @return object File data.
     */
    public function generate_file_data($user) {
        $file = new \stdClass();
        $file->filearea = 'content';
        $file->filename = 'Test file';
        $file->filepath = '/';
        $file->sortorder = 0;
        $file->author = fullname($user);
        $file->license = 'allrightsreserved';
        $file->datemodified = 1365590964;
        $file->datecreated = 1365590864;
        $file->component = 'mod_openstudio';
        $file->itemid = 0;

        return $file;
    }

    /**
     * Creates and returns a new content post using pre-geneated content data.
     *
     * @param object $studiolevels Studio level structure
     * @param int $userid User ID for content creation
     * @param array $contentdata Content data for content creation.
     *
     * @return object
     */
    public function generate_content_data($studiolevels, $userid, $contentdata) {
        current($studiolevels->leveldata['contentslevels']);
        $blockid = key($studiolevels->leveldata['contentslevels']);

        current($studiolevels->leveldata['contentslevels'][$blockid]);
        $activityid = key($studiolevels->leveldata['contentslevels'][$blockid]);

        current($studiolevels->leveldata['contentslevels'][$blockid][$activityid]);
        $contentid = key($studiolevels->leveldata['contentslevels'][$blockid][$activityid]);

        $contentid = content::create(
                $studiolevels->id, $userid, 3,
                $studiolevels->leveldata['contentslevels'][$blockid][$activityid][$contentid], $contentdata);
        return content::get_record($userid, $contentid);
    }



    public function create_mock_levels($studioid) {
        global $DB;
        $blocks = array();
        $blocks[] = $DB->insert_record('openstudio_level1',
                (object) array('openstudioid' => $studioid, 'name' => 'Block 1', 'sortorder' => 1));
        $blocks[] = $DB->insert_record('openstudio_level1',
                (object) array('openstudioid' => $studioid, 'name' => 'Block 2', 'sortorder' => 2));
        $blocks[] = $DB->insert_record('openstudio_level1',
                (object) array('openstudioid' => $studioid, 'name' => 'Block 3', 'sortorder' => 3));
        $blocks[] = $DB->insert_record('openstudio_level1',
                (object) array('openstudioid' => $studioid, 'name' => 'Block 4', 'sortorder' => 4));

        $activities = array();
        foreach ($blocks as $block) {
            $activities[$block] = array();
            $activities[$block][] = $DB->insert_record('openstudio_level2',
                    (object) array('level1id' => $block, 'name' => 'Activity 1', 'sortorder' => 1));
            $activities[$block][] = $DB->insert_record('openstudio_level2',
                    (object) array('level1id' => $block, 'name' => 'Activity 2', 'sortorder' => 2));
            $activities[$block][] = $DB->insert_record('openstudio_level2',
                    (object) array('level1id' => $block, 'name' => 'Activity 3', 'sortorder' => 3));
        }

        $contents = array();
        foreach ($blocks as $block) {
            foreach ($activities[$block] as $activity) {
                $contents[$block][$activity] = array();
                $contents[$block][$activity][] = $DB->insert_record('openstudio_level3',
                        (object) array('level2id' => $activity, 'name' => 'Slot 1', 'sortorder' => 1));
                $contents[$block][$activity][] = $DB->insert_record('openstudio_level3',
                        (object) array('level2id' => $activity, 'name' => 'Slot 2', 'sortorder' => 2));
            }
        }

        $leveldata = array('blockslevels' => $blocks,
                'activitieslevels' => $activities,
                'contentslevels' => $contents);
        return $leveldata;
    }

    public function create_mock_contents($studioid, $leveldata, $userid, $visibility) {
        $pinboardcontents = array();
        $contents = array();

        $data2 = array(
                'name' => 'BBC iPlayer URL' . random_string(),
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.open.ac.uk/',
                'urltitle' => 'David Bowie',
                'visibility' => $visibility,
                'description' => 'BBC iPlayer link',
                'tags' => array(random_string(), random_string(), random_string()),
                'ownership' => 0,
                'sid' => 0 // For a new content.
        );

        foreach ($leveldata['contentslevels'] as $activitylevels) {
            foreach ($activitylevels as $contentlevels) {
                foreach ($contentlevels as $contentlevelid) {
                    $data = array(
                            'name' => 'YouTube URL' . random_string(),
                            'attachments' => '',
                            'embedcode' => '',
                            'weblink' => 'http://www.open.ac.uk/',
                            'urltitle' => 'Vesica Timeline',
                            'visibility' => $visibility,
                            'description' => 'YouTube link',
                            'tags' => array(random_string(), random_string(), random_string()),
                            'ownership' => 0,
                            'sid' => 0 // For a new content.
                    );
                    $contents[$contentlevelid][] = content::create(
                            $studioid, $userid, 3, $contentlevelid, $data); // Level 3 is for contents.
                }
            }
        }

        // Let's also create a couple of pinboard contents.
        $pinboardcontents[] = content::create_in_pinboard($studioid, $userid, $data2);
        $pinboardcontents[] = content::create_in_pinboard($studioid, $userid, $data2);
        $pinboardcontents[] = content::create_in_pinboard($studioid, $userid, $data2);

        return array('contents' => $contents,
                'pinboard_contents' => $pinboardcontents);
    }

    public function get_studio_by_idnumber($idnumber) {
        global $DB;

        $select = 'SELECT s.*, cm.id as cmid ';
        $from = 'FROM {openstudio} s
                 JOIN {course_modules} cm ON s.id = cm.instance AND s.course = cm.course
                 JOIN {modules} m ON cm.module = m.id ';
        $where = "WHERE m.name = 'openstudio'
                    AND cm.idnumber = ?";
        return $DB->get_record_sql($select . $from . $where, array($idnumber));
    }

    public function add_users_to_groups($comboarray) {
        global $DB;
        foreach ((array)$comboarray as $groupid => $users) {
            foreach ((array) $users as $userid) {
                // Create and prepare record.
                $record = new stdClass();
                $record->groupid = $groupid;
                $record->userid = $userid;
                $record->timeadded = time();

                // Write to groups_members table.
                $DB->insert_record('groups_members', $record, false);
            }
        }
    }

    /**
     * Create new studio module instance.
     *
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/course/modlib.php');

        $this->instancecount++;
        $i = $this->instancecount;

        $record = (object) $record;
        $options = (array) $options;

        if (empty($record->course)) {
            throw new coding_exception('module generator requires $record->course');
        }
        if (!isset($record->name)) {
            $record->name = get_string('pluginname', 'openstudio') . ' ' . $i;
        }
        if (!isset($record->intro)) {
            $record->intro = 'Test Studio for phpunit test '.$i;
        }
        if (!isset($record->introformat)) {
            $record->introformat = FORMAT_MOODLE;
        }
        if (!isset($record->contentmaxbytes)) {
            $record->contentmaxbytes = 10485760; // 10 MB.
        }
        if (!isset($record->reportingemail)) {
            $record->reportingemail = '';
        }
        if (!isset($record->defaultvisibility)) {
            $record->defaultvisibility = 2;
        }
        if (!isset($record->allowedvisibility)) {
            $record->allowedvisibility = '1,2,3,7';
        }
        if (!isset($record->versioning)) {
            $record->versioning = '5';
        }
        if (!isset($record->flags)) {
            $record->flags = '1,6,5,4,3,2,10';
        }
        if (!isset($record->filetypes)) {
            $record->filetypes = 'images,videos,audio,documents,presentations,spreadsheets';
        }
        if (!isset($record->sitename)) {
            $record->sitename = 'Test Design for Studio';
        }
        if (!isset($record->pinboard)) {
            $record->pinboard = 1;
        }
        if (!isset($record->pinboardname)) {
            $record->pinboardname = 'Pinboard';
        }
        if (!isset($record->level1name)) {
            $record->level1name = 'Blocks';
        }
        if (!isset($record->level2name)) {
            $record->level2name = 'Activities';
        }
        if (!isset($record->level3name)) {
            $record->level3name = 'contents';
        }
        if (!isset($record->exportfilezipsize)) {
            $record->exportzipfilesize = 10485760; // 10 MB.
        }
        if (!isset($record->enablemodule)) {
            $record->enablemodule = 1;
        }
        // Checkbox field enablelocking in the table Studio.
        if (!isset($record->enablelocking)) {
            $record->enablelocking = 1;
        }
        $record->enablecontenthtml = 1;
        $record->enablecontentcommenthtml = 1;
        if (!isset($record->enablecontentcommentaudio)) {
            $record->enablecontentcommentaudio = 1;
        }
        if (!isset($record->enablecontentusesfileupload)) {
            $record->enablecontentusesfileupload = 1;
        }
        if (!isset($record->enablefolders)) {
            $record->enablefolders = 0;
        }
        if (!isset($record->enablefoldersanycontent)) {
            $record->enablefoldersanycontent = 0;
        }
        if (!isset($record->pinboardfolderlimit)) {
            $record->pinboardfolderlimit = 10;
        }
        if (!isset($record->enablerss)) {
            $record->enablerss = 1;
        }
        if (!isset($record->enablesubscription)) {
            $record->enablesubscription = 1;
        }
        if (!isset($record->enableexportimport)) {
            $record->enableexportimport = 1;
        }
        if (!isset($record->enablecontentusesweblink)) {
            $record->enablecontentusesweblink = 1;
        }
        if (!isset($record->enablecontentusesembedcode)) {
            $record->enablecontentusesembedcode = 1;
        }
        if (!isset($record->enablecontentallownotebooks)) {
            $record->enablecontentallownotebooks = 1;
        }
        if (!isset($record->enablecontentreciprocalaccess)) {
            $record->enablecontentreciprocalaccess = 0;
        }
        if (!isset($record->themehomedefault)) {
            $record->themehomedefault = 3;
        }
        if (!isset($record->enableparticipationsmiley)) {
            $record->enableparticipationsmiley = 0;
        }
        if (array_key_exists('groupmode', $options) && array_key_exists('groupingid', $options)) {
            if (!isset($record->groupmode)) {
                $record->groupmode = $options['groupmode'];
            }
            if (!isset($record->groupingid)) {
                $record->groupingid = $options['groupingid'];
            }
        } else {
            if (!isset($record->groupmode)) {
                $record->groupmode = 0;
            }
            if (!isset($record->groupingid)) {
                $record->groupingid = 0;
            }
        }
        if (!isset($record->section)) {
            $record->section = 0;
        }
        if (!isset($record->visible)) {
            $record->visible = 1;
            $record->visibleold = 1;
        }
        if (!isset($record->tutorroles)) {
            $record->tutorroles = '';
        }
        if (!isset($record->themefeatures)) {
            $record->themefeatures = 0;
        }

        if (isset($record->idnumber)) {
            $record->cmidnumber = $record->idnumber;
        }

        $record->modulename = 'openstudio';
        $record->module = $DB->get_field('modules', 'id', array('name' => $record->modulename));
        $courserecord = get_course($record->course);
        $moduleinfo = add_moduleinfo($record, $courserecord);
        // Prepare object to return with additional field cmid.
        $instance = $DB->get_record($this->get_modulename(), array('id' => $moduleinfo->instance), '*', MUST_EXIST);
        $instance->cmid = $moduleinfo->coursemodule;
        return $instance;
    }

    private function generate_content($contentdata) {
        global $DB, $CFG, $USER;

        $contentdata = (array) $contentdata;

        // Switch $USER for file creation.
        $realuser = $USER;
        $USER = $DB->get_record('user', array('id' => $contentdata['userid']));

        $studio = $this->get_studio_by_idnumber($contentdata['openstudio']);
        $context = context_module::instance($studio->cmid);

        if (isset($contentdata['levelid'])) {
            $levelid = $contentdata['levelid'];
        } else {
            $levelid = 0;
        }

        if (isset($contentdata['levelcontainer'])) {
            $levelcontainer = $contentdata['levelcontainer'];
        } else {
            $levelcontainer = 0;
        }

        if (!isset($contentdata['content'])) {
            $contentdata['content'] = '';
        }
        if (!isset($contentdata['embedcode'])) {
            $contentdata['embedcode'] = '';
        }
        if (!isset($contentdata['weblink'])) {
            $contentdata['weblink'] = '';
        }
        if (!isset($contentdata['urltitle'])) {
            $contentdata['urltitle'] = '';
        }
        if (!isset($contentdata['visibility'])) {
            $contentdata['visibility'] = mod_openstudio\local\api\content::VISIBILITY_PRIVATE;
        }

        $file = null;
        if (isset($contentdata['file'])) {
            $usercontext = context_user::instance($contentdata['userid']);
            $fs = get_file_storage();
            $filerecord = (object) array(
                    'userid' => $contentdata['userid'],
                    'component' => 'user',
                    'filearea' => 'draft',
                    'filepath' => '/',
                    'filename' => basename($contentdata['file']),
                    'contextid' => $usercontext->id,
                    'itemid' => file_get_unused_draft_itemid()
            );
            $storedfile = $fs->create_file_from_pathname($filerecord, $CFG->dirroot . '/' . $contentdata['file']);
            $file = array(
                'id' => $storedfile->get_itemid(),
                'filename' => $storedfile->get_filename(),
                'file' => $filerecord,
                'mimetype' => array(
                    'extension' => pathinfo($contentdata['file'], PATHINFO_EXTENSION),
                    'type' => $storedfile->get_mimetype()
                )
            );
            unset($contentdata['file']);
            $contentdata['fileid'] = $storedfile->get_itemid();
        }

        $cm = $DB->get_record('course_modules', array('id' => $studio->cmid));
        // Switch the user global so we acces the correct user's draft files.
        $contentid = content::create($studio->id, $contentdata['userid'],
                $levelcontainer, $levelid, $contentdata, $file, $context, $cm);
        $USER = $realuser;

        if (isset($contentdata['deletedby']) && isset($contentdata['deletedtime'])) {
            $updaterecord = (object) [
                'id' => $contentid,
                'deletedby' => $contentdata['deletedby'],
                'deletedtime' => $contentdata['deletedtime']
            ];
            $DB->update_record('openstudio_contents', $updaterecord);
        }
        return $contentid;
    }

    public function create_contents($contentdata) {
        return $this->generate_content($contentdata);
    }

    public function create_contentversions($versiondata) {
        global $CFG, $DB, $USER;
        $versiondata = (array) $versiondata;
        if (!isset($versiondata['contentid'])) {
            throw new coding_exception('$versiondata passed to create_contentversion must include contentid');
        }
        $content = $DB->get_record('openstudio_contents', ['id' => $versiondata['contentid']]);
        list($course, $cm) = get_course_and_cm_from_instance($content->openstudioid, 'openstudio');
        if (!isset($versiondata['content'])) {
            $versiondata['content'] = '';
        }
        if (!isset($versiondata['mimetype'])) {
            $versiondata['mimetype'] = '';
        }
        if (!isset($versiondata['contenttype'])) {
            $versiondata['contenttype'] = content::TYPE_TEXT;
        }
        if (!isset($versiondata['urltitle'])) {
            $versiondata['urltitle'] = '';
        }
        if (!isset($versiondata['name'])) {
            $versiondata['name'] = random_string();
        }
        if (!isset($versiondata['description'])) {
            $versiondata['description'] = random_string();
        }
        if (!isset($versiondata['deletedby'])) {
            $versiondata['deletedby'] = null;
        }
        if (!isset($versiondata['deletedtime'])) {
            $versiondata['deletedtime'] = null;
        }

        $file = null;
        if (isset($versiondata['file'])) {
            // Switch $USER for file creation.
            $realuser = $USER;
            $USER = $DB->get_record('user', array('id' => $versiondata['userid']));

            $fs = get_file_storage();
            $filerecord = (object) array(
                'userid' => $versiondata['userid'],
                'component' => 'mod_openstudio',
                'filearea' => 'content',
                'filepath' => '/',
                'filename' => basename($versiondata['file']),
                'contextid' => $cm->context->id,
                'itemid' => $DB->insert_record('openstudio_content_files', (object) ['refcount' => 1])
            );
            $storedfile = $fs->create_file_from_pathname($filerecord, $CFG->dirroot . '/' . $versiondata['file']);
            unset($versiondata['file']);
            $versiondata['fileid'] = $storedfile->get_itemid();
            $USER = $realuser;
        }
        return $DB->insert_record('openstudio_content_versions', (object) $versiondata);
    }

    public function create_folders($folderdata) {
        $folderdata['contenttype'] = content::TYPE_FOLDER;
        return $this->generate_content($folderdata);
    }

    public function create_folder_contents($foldercontentdata) {
        global $DB;

        $studio = $this->get_studio_by_idnumber($foldercontentdata['openstudio']);
        $contentparams = array('openstudioid' => $studio->id, 'name' => $foldercontentdata['content']);
        $content = $DB->get_record('openstudio_contents', $contentparams);

        $folderparams = array('openstudioid' => $studio->id, 'name' => $foldercontentdata['folder']);
        $folder = $DB->get_record('openstudio_contents', $folderparams);

        if (isset($foldercontentdata['provenance'])) {
            $provparams = array('openstudioid' => $studio->id, 'name' => $foldercontentdata['provenance']);
            $foldercontentdata['provenanceid'] = $DB->get_field('openstudio_contents', 'id', $provparams);
        }

        return folder::add_content($folder->id, $content->id, $foldercontentdata['userid'], $foldercontentdata);
    }

    public function create_collected_folder_contents($contentdata) {
        global $DB;
        $studio = $this->get_studio_by_idnumber($contentdata['openstudio']);
        $contentparams = array('openstudioid' => $studio->id, 'name' => $contentdata['content']);
        $content = $DB->get_record('openstudio_contents', $contentparams);

        $folderparams = array('openstudioid' => $studio->id, 'name' => $contentdata['folder']);
        $folder = $DB->get_record('openstudio_contents', $folderparams);
        if ($folder->userid == $content->userid) {
            folder::collect_content($folder->id, $content->id, $folder->userid, null, true);
        } else {
            folder::collect_content($folder->id, $content->id, $folder->userid);
        }
    }

    public function create_levels($leveldata) {
        global $DB;
        if ($leveldata['level'] == 1) {
            $studio = $this->get_studio_by_idnumber($leveldata['openstudio']);
            $leveldata['openstudioid'] = $studio->id;
            $leveldata['parentid'] = $studio->id;
        } else if ($leveldata['level'] == 2 && !isset($leveldata['level1id'])) {
            $leveldata['level1id'] = $leveldata['parentid'];
        } else if ($leveldata['level'] == 3 && !isset($leveldata['level2id'])) {
            $leveldata['level2id'] = $leveldata['parentid'];
        }
        if (!isset($leveldata['sortorder'])) {
            $leveldata['sortorder'] = levels::get_latest_sortorder(
                    $leveldata['level'], $leveldata['parentid']);
        }
        if (!isset($leveldata['hidelevel'])) {
            $leveldata['hidelevel'] = 0;
        }
        if (!isset($leveldata['contenttype'])) {
            $leveldata['contenttype'] = 0;
        }
        $leveldata['id'] = $DB->insert_record('openstudio_level' . $leveldata['level'], $leveldata);

        if ($leveldata['level'] == 3) {
            $lockdata = array();
            if (isset($leveldata['lockprocessed'])) {
                $lockdata['lockprocessed'] = $leveldata['lockprocessed'];
            }
            if (isset($leveldata['locktype'])) {
                $lockdata['locktype'] = $leveldata['locktype'];
            }
            if (isset($leveldata['locktime'])) {
                $lockdata['locktime'] = $leveldata['locktime'];
            }
            if (isset($leveldata['unlocktime'])) {
                $lockdata['unlocktime'] = $leveldata['unlocktime'];
            }
            if (!empty($lockdata)) {
                $leveldata = array_merge($leveldata, $lockdata);
                $DB->update_record('openstudio_level3', $leveldata);
            }
        }
        return $leveldata['id'];
    }

    public function create_folder_template($templatedata) {
        return template::create($templatedata['levelid'], $templatedata);
    }

    public function create_folder_content_template($templatedata) {
        return template::create_content($templatedata['foldertemplateid'], $templatedata);
    }

    public function create_comment($commentdata) {
        global $CFG, $USER, $DB;
        $realuser = $USER;
        if (is_object($commentdata)) {
            $commentdata = (array) $commentdata;
        }
        $replyid = array_key_exists('inreplyto', $commentdata) ? $commentdata['inreplyto'] : null;
        $context = null;
        $file = null;
        if (array_key_exists('filepath', $commentdata) && array_key_exists('filecontext', $commentdata)) {
            // Switch $USER for file creation.
            $USER = $DB->get_record('user', array('id' => $commentdata['userid']));
            $usercontext = context_user::instance($commentdata['userid']);
            $fs = get_file_storage();
            $filerecord = (object) array(
                    'userid' => $commentdata['userid'],
                    'component' => 'user',
                    'filearea' => 'draft',
                    'filepath' => '/',
                    'filename' => basename($commentdata['filepath']),
                    'contextid' => $usercontext->id,
                    'itemid' => file_get_unused_draft_itemid()
            );
            $storedfile = $fs->create_file_from_pathname($filerecord, $CFG->dirroot . '/' . $commentdata['filepath']);
            $file = ['id' => $storedfile->get_itemid()];
            $context = $commentdata['filecontext'];
        }
        $id = comments::create($commentdata['contentid'], $commentdata['userid'], $commentdata['comment'],
                null, $file, $context, $replyid);
        if (!empty($commentdata['deleted'])) {
            comments::delete($id, $commentdata['userid']);
        }
        $USER = $realuser;
        return $id;
    }

    public function create_flag($flagdata) {
        if (isset($flagdata['personid'])) {
            return flags::user_toggle($flagdata['personid'], $flagdata['flagid'], 'on', $flagdata['userid'], true);
        }
        if (isset($flagdata['commentid'])) {
            if (!isset($flagdata['contentid'])) {
                $flagdata['contentid'] = 0;
            }
            if (!isset($flagdata['flagid'])) {
                $flagdata['flagid'] = flags::COMMENT_LIKE;
            }
            return flags::comment_toggle($flagdata['contentid'], $flagdata['commentid'], $flagdata['userid'], 'on',
                    true, $flagdata['flagid']);
        }
        return flags::toggle($flagdata['contentid'], $flagdata['flagid'], 'on', $flagdata['userid'], true);
    }

    public function create_notification($notificationdata) {
        global $DB;
        $record = (object) [
            'userid' => $notificationdata['userid'],
            'userfrom' => $notificationdata['userfrom'],
            'contentid' => $notificationdata['contentid'],
            'commentid' => !empty($notificationdata['commentid']) ? $notificationdata['commentid'] : null,
            'flagid' => !empty($notificationdata['flagid']) ? $notificationdata['flagid'] : null,
            'message' => !empty($notificationdata['message']) ? $notificationdata['message'] : random_string(),
            'icon' => !empty($notificationdata['icon']) ? $notificationdata['icon'] : 'participation',
            'url' => !empty($notificationdata['url']) ? $notificationdata['url'] : 'http://localhost',
            'timecreated' => !empty($notificationdata['timecreated']) ? $notificationdata['timecreated'] : time(),
            'timeread' => !empty($notificationdata['timeread']) ? $notificationdata['timeread'] : null
        ];
        $record->id = $DB->insert_record('openstudio_notifications', $record);
        return $record;
    }
}
