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

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

class mod_openstudio_generator extends testing_module_generator {

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
            $contentdata['visibility'] = STUDIO_VISIBILITY_PRIVATE;
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
        return $contentid;
    }

    public function create_contents($contentdata) {
        return $this->generate_content($contentdata);
    }

    public function create_folders($folderdata) {
        $folderdata['contenttype'] = STUDIO_CONTENTTYPE_SET;
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

        return studio_api_set_slot_add($folder->id, $content->id, $foldercontentdata['userid'], $foldercontentdata);
    }

    public function create_collected_folder_contents($contentdata) {
        global $DB;
        $studio = $this->get_studio_by_idnumber($contentdata['openstudio']);
        $contentparams = array('openstudioid' => $studio->id, 'name' => $contentdata['content']);
        $content = $DB->get_record('openstudio_contents', $contentparams);

        $folderparams = array('openstudioid' => $studio->id, 'name' => $contentdata['folder']);
        $folder = $DB->get_record('openstudio_contents', $folderparams);
        if ($folder->userid == $content->userid) {
            studio_api_set_slot_collect($folder->id, $content->id, $folder->userid, null, true);
        } else {
            studio_api_set_slot_collect($folder->id, $content->id, $folder->userid);
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
        return studio_api_set_template_create(3, $templatedata['levelid'], $templatedata);
    }

    public function create_folder_content_template($templatedata) {
        return studio_api_set_template_slot_create($templatedata['foldertemplateid'], $templatedata);
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
        if (!isset($flagdata['contentid'])) {
            $flagdata['contentid'] = 0;
        }
        if (!isset($flagdata['personid'])) {
            $flagdata['personid'] = 0;
        }
        if (!isset($flagdata['commentid'])) {
            $flagdata['commentid'] = null;
        }
        return studio_api_flags_toggle_internal($flagdata['contentid'], $flagdata['personid'],
                $flagdata['commentid'], $flagdata['flagid'], 'on', $flagdata['userid'], true);
    }

}
