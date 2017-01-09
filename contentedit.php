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

require_once(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot . '/mod/openstudio/content_form.php');

require_once($CFG->dirroot . '/mod/openstudio/api/apiloader.php');

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\util\defaults;
use mod_openstudio\local\util;

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Content Id.
// Value 0 = new content.
// Value X = id of content to edit.
$sid = optional_param('sid', 0, PARAM_INT);

// Content level id.
$lid = optional_param('lid', 0, PARAM_INT);

// Content type.
$type = optional_param('type', content::TYPE_NONE, PARAM_INT);
if (!in_array($type, array(content::TYPE_NONE,
                           content::TYPE_FOLDER,
                           content::TYPE_FOLDER_CONTENT))) {
    $type = content::TYPE_NONE;
}

// User id.  This is used by admin who needs to look at other user's content.
// In general, it is never used by non-adminusers.  The code has been
// written to prevent users from using this parameter to access other
// people's content if they dont have manage content permission.
$userid = optional_param('userid', $USER->id, PARAM_INT);

$folderid = optional_param('ssid', null, PARAM_INT);

// To set default visibility.
$visibilityid = optional_param('vid', null, PARAM_INT);

// Page inita and security checks.
$coursedata = util::render_page_init($id,
        array('mod/openstudio:view', 'mod/openstudio:addcontent'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;

// If folders feature is disabled, then we do not allow new pinboard folders to be created.
if ($sid == 0) {
    if ((!$permissions->feature_enablefolders && ($type === content::TYPE_FOLDER)) ||
        (!$permissions->feature_enablefolders && ($type === content::TYPE_FOLDER_CONTENT))) {
        if (($sid == 0) && ($lid == 0)) {
            $returnurl = new moodle_url('/mod/openstudio/view.php',
                    array('id' => $cm->id, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD));
            print_error('errornopermissiontoaddcontent', 'openstudio', $returnurl->out(false));
        }
    }
}

// If a levelid greater than 0 is given with no sid, then its an activity content.
// We check if the content has previously been created and if so acquire the content id
// and redirect the user to the correct URL.
if (($lid > 0) && ($sid <= 0)) {
    $contentdata = levels::get_record($cminstance->id, $userid, 3, $lid);

    // Process the level management locks.
    if ($contentdata !== false) {
        $contentdata = studio_api_lock_determine_lock_status($contentdata);
        $redirecturl = new moodle_url('/mod/openstudio/contentedit.php',
                array('id' => $cm->id, 'sid' => $contentdata->id, 'userid' => $userid));
        redirect($redirecturl->out(false));
        return;
    }
}

// Get user record.
if ($userid == $USER->id) {
    $userrecord = $USER;
} else {
    // We do this to confirm the given userid in the url is correct.
    $userrecord = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
}

// Set returnurl which will be used if error occurred and need to redirect user.
$returnurl = new moodle_url('/mod/openstudio/view.php',
        array('id' => $cm->id, 'vid' => content::VISIBILITY_PRIVATE));

$contentdata = new stdClass();
if ($sid > 0) {
    $contentdata = content::get_record($userid, $sid);
    if ($contentdata === false) {
        print_error('errorinvalidcontent', 'openstudio', $returnurl->out(false));
    }
    $lid = $contentdata->levelid;
}

$contentdataname = '';
$contentisinpinboard = false;

if ($lid > 0) {
    $level3data = levels::get_record(defaults::CONTENTLEVELCONTAINER, $lid);
    if ($level3data === false) {
        print_error('errorinvalidcontent', 'openstudio', $returnurl->out(false));
    }
    if ($type == content::TYPE_FOLDER_CONTENT) {
        $contentdata->contenttype = $type;
        $contentdata->levelid = 0;
        $contentdata->levelcontainer = 0;
    } else {
        $contentdata->contenttype = $level3data->contenttype;
        $contentdata->levelid = $lid;
        $contentdata->levelcontainer = defaults::CONTENTLEVELCONTAINER;
    }
} else {
    $contentisinpinboard = true;
    $contentdata->levelid = 0;
    $contentdata->levelcontainer = 0;
    $contentdata->contenttype = $type;
}

$folderlid = 0;
$foldertemplatecontentid = 0;

// Check if its a new folder content.
if (($type == content::TYPE_FOLDER_CONTENT) && ($sid == 0)) {
    if ($folderid === null) {
        print_error('errornofolder', 'openstudio', $returnurl->out(false));
    }
    $foldertemplatecontentid = optional_param('sstsid', 0, PARAM_INT);
    if ($folderid > 0) {
        $folderdata = content::get($folderid);
        if ($folderdata) {
            $folderlid = $folderdata->levelid;
            $contentisinpinboard = ($folderlid > 0) ? false : true;
        }
    }
}

// Check if we are processing a folder and get information if so.
if (isset($contentdata->contenttype) && ($contentdata->contenttype == content::TYPE_FOLDER)) {
    $type = content::TYPE_FOLDER;
    $folderid = $contentdata->id;
    $folderdata = content::get($folderid);
    if ($folderdata) {
        $folderlid = $folderdata->levelid;
        $contentisinpinboard = ($folderlid > 0) ? false : true;
    }
}

// Check if we are processing a folder content and get information if so.
if ($sid && $foldercontentdata = studio_api_set_slot_get_by_slotid($folderid, $contentdata->id)) {
    if ($foldercontentdata) {
        $folderid = $foldercontentdata->folderid;
        $type = content::TYPE_FOLDER_CONTENT;
        $folderdata = content::get($folderid);
        if ($folderdata) {
            $folderlid = $folderdata->levelid;
            $contentisinpinboard = ($folderlid > 0) ? false : true;
        }
    }
}

if (($lid > 0) || ($folderlid > 0)) {
    $lidtemp = ($lid > 0) ? $lid : $folderlid;
    $level3data = levels::get_record(defaults::CONTENTLEVELCONTAINER, $lidtemp);
    if ($level3data === false) {
        print_error('errorinvalidcontent', 'openstudio', $returnurl->out(false));
    }
    $level2data = levels::get_record(
        defaults::ACTIVITYLEVELCONTAINER, $level3data->level2id);
    if ($level2data === false) {
        print_error('errorinvalidcontent', 'openstudio', $returnurl->out(false));
    }
    $level1data = levels::get_record(
        defaults::BLOCKLEVELCONTAINER, $level2data->level1id);
    if ($level1data === false) {
        print_error('errorinvalidcontent', 'openstudio', $returnurl->out(false));
    }

    $contentdataname = trim($level1data->name);
    if (trim($level2data->name) != '') {
        $contentdataname .= ' - ' . $level2data->name;
    }
    if (trim($level3data->name) != '') {
        $contentdataname .= ' - ' . $level3data->name;
    }

} else {
    if (!in_array($type, array(content::TYPE_FOLDER, content::TYPE_FOLDER_CONTENT))) {
        $contentdataname = get_string('contenttitlepinboard', 'openstudio');
    }
}

if ($sid > 0) {
    $contentdata = content::get_record($userid, $sid);
    if ($contentdata === false) {
        print_error('errorinvalidcontent', 'openstudio', $returnurl->out(false));
    }

    if ($permissions->feature_enablelock) {
        if (isset($contentdata->locktype) &&
                (($contentdata->locktype == lock::ALL) ||
                 ($contentdata->locktype == lock::CRUD) ||
                 ($contentdata->locktype == lock::SOCIAL_CRUD) ||
                 ($contentdata->locktype == lock::COMMENT_CRUD))) {
            print_error('contentislocked', 'openstudio', $returnurl->out(false));
        }
    }

    // Given the content exists, get the content owner again to prevent user spoofing.
    $userid = $contentdata->userid;
    $userrecord = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

    // Is the content mine?
    $permissions->contentismine = ($contentdata->userid == $USER->id) ? true : false;

    // If content is not mine, and I dont have managecontent capability, then error.
    if (!$permissions->managecontent) {
        if (!$permissions->contentismine) {
            print_error('errornopermissiontoaddcontent', 'openstudio', $returnurl->out(false));
        }
    }

    $contentdata->sid = $sid;

    if (!in_array($type, array(content::TYPE_FOLDER, content::TYPE_FOLDER_CONTENT))) {
        $contentdataname = util::get_content_name($contentdata);
    }
    if (!in_array($contentdata->contenttype, array(content::TYPE_NONE,
                                                content::TYPE_FOLDER))) {
        $contentdata->contenttype = content::TYPE_NONE;
    } else {
        // Do nothing.
        $donothingforcodechecker = true;
    }

    $strcontenturl = new moodle_url('/mod/openstudio/content.php',
            array('id' => $cm->id, 'sid' => $sid));
    $strpageurl = new moodle_url('/mod/openstudio/contentedit.php',
            array('id' => $cm->id, 'sid' => $sid));
} else {
    // If its a blank new content, then make sure the owner of the content is the logged in user.
    $userid = $USER->id;
    $userrecord = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

    $contentdata->sid = 0;
    $contentleveldata = levels::get_record($contentdata->levelcontainer, $contentdata->levelid);
    // Check lock level management access.
    if ($permissions->feature_enablelock) {
        if (isset($contentleveldata->locktype) &&
                (($contentleveldata->locktype == lock::ALL) ||
                 ($contentleveldata->locktype == lock::CRUD) ||
                 ($contentleveldata->locktype == lock::SOCIAL_CRUD) ||
                 ($contentleveldata->locktype == lock::COMMENT_CRUD))) {
            $contentislock = false;
            $contentlocktime = isset($contentleveldata->locktime) ? $contentleveldata->locktime : 0;
            $contentunlocktime = isset($contentleveldata->unlocktime) ? $contentleveldata->unlocktime : 0;
            if ($contentlocktime > $contentunlocktime ) {
                if (($contentunlocktime > 0) && (time() > $contentunlocktime)) {
                    $contentislock = false;
                }
                if (($contentlocktime > 0) && (time() > $contentlocktime)) {
                    $contentislock = true;
                }
            } else {
                if (($contentlocktime > 0) && (time() > $contentlocktime)) {
                    $contentislock = true;
                }
                if (($contentunlocktime > 0) && (time() > $contentunlocktime)) {
                    $contentislock = false;
                }
            }
            if ($contentislock) {
                if ($contentleveldata->unlocktime > 0) {
                    $dtm = userdate($contentleveldata->unlocktime);
                    print_error(get_string('erroractivitynotavailable', 'openstudio', $dtm), 'openstudio', $returnurl->out(false));
                } else {
                    print_error(get_string('contentislocked', 'openstudio'), 'openstudio', $returnurl->out(false));
                }
            }
        }
    }
    $strcontenturl = '';
    $strpageurl = new moodle_url('/mod/openstudio/contentedit.php',
            array('id' => $cm->id, 'lid' => $contentdata->levelid, 'sid' => $sid));

    $permissions->contentismine = true;
}

$folderdatalevelid = 0;
if (isset($folderdata)) {
    if (trim($contentdataname) == '') {
        $contentdataname .= $folderdata->name;
    } else {
        $contentdataname .= ' - ' . $folderdata->name;
    }
    $folderdatalevelid = $folderdata->levelid;
} else if ($lid > 0 && $folderid === 0) {
    $folderdata = (object) array(
        'id' => $folderid,
        'name' => $contentdataname,
        'levelid' => $lid
    );
    $contentdataname = get_string('pinboardnewcontent', 'openstudio');
    $folderdatalevelid = $folderdata->levelid;
}

if (($folderdatalevelid == 0) && ($contentdata->sid == 0) && ($contentdata->levelid == 0) && ($contentdata->levelcontainer == 0)) {
    $returnurl = new moodle_url('/mod/openstudio/view.php',
            array('id' => $cm->id, 'vid' => content::VISIBILITY_MODULE));
    if (!$permissions->feature_pinboard) {
        print_error('errorpinboardisdisabled', 'openstudio', $returnurl->out(false));
    }

    $returnurl = new moodle_url('/mod/openstudio/view.php',
            array('id' => $cm->id, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1));
    if ($type === content::TYPE_FOLDER_CONTENT && $folderid > 0) {
        if ($sid == 0 && empty($foldertemplatecontentid) && !studio_api_set_can_add_more_slots($folderid, $permissions, $lid)) {
            print_error('errorpinboardfolderexceedlimit', 'openstudio', $returnurl->out(false));
        }
    } else {
        if ($permissions->feature_pinboard && ($permissions->pinboarddata->available <= 0)) {
            print_error('errorpinboardexceedlimit', 'openstudio', $returnurl->out(false));
        }
    }
}
if (($folderdatalevelid > 0) && ($contentdata->sid == 0) && ($contentdata->levelid == 0) && ($contentdata->levelcontainer == 0)) {
    $returnurl = new moodle_url('/mod/openstudio/view.php',
            array('id' => $cm->id, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1));
    if ($type === content::TYPE_FOLDER_CONTENT && $folderid > 0) {
        if ($sid == 0 && empty($foldertemplatecontentid) && !studio_api_set_can_add_more_slots($folderid, $permissions, $lid)) {
            print_error('errorpinboardfolderexceedlimit', 'openstudio', $returnurl->out(false));
        }
    }
}

if (trim($contentdataname) == '') {
    if ($type == content::TYPE_FOLDER) {
        if ($contentisinpinboard) {
            $contentdataname = get_string('foldertitlepinboard', 'openstudio');
        } else {
            $contentdataname = get_string('foldertitle', 'openstudio');
        }
    }
}

// Render page header and crumb trail.
$strpagetitle = $strpageheading = get_string('pageheadercontentedit', 'openstudio',
        array('cname' => $course->shortname, 'cmname' => $cm->name, 'title' => $contentdataname));
util::page_setup($PAGE, $strpagetitle, $strpageheading, $strpageurl, $course, $cm);
$crumbarray = array();
$strareaurl = new moodle_url('/mod/openstudio/view.php', array('id' => $cm->id, 'vid' => 1));
if ($contentisinpinboard) {
    $vid = content::VISIBILITY_PRIVATE_PINBOARD;
    $strareaurl = new moodle_url('/mod/openstudio/view.php',
            array('id' => $cm->id, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1));
    $crumbarray[get_string('navmypinboard', 'openstudio')] = $strareaurl->out(false);
} else {
    $vid = content::VISIBILITY_PRIVATE;
    $crumbarray[get_string('navmystudiowork', 'openstudio')] = $strareaurl->out(false);
}

$crumbarray[$contentdataname] = $strcontenturl;
$crumbarray[get_string('navedit', 'openstudio')] = $strpageurl;
util::add_breadcrumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

// This part of the code processes the content edit form.

$isfolderlock = false;
if (isset($folderdata) && $folderdata->id !== 0) {
    $isfolderlock = !studio_api_lock_slot_show_crud($folderdata, $permissions);
}

$urlparams = array('id' => $id, 'lid' => $lid, 'sid' => $sid,
                   'type' => $contentdata->contenttype);
if (!is_null($folderid)) {
    $urlparams['ssid'] = $folderid;
}
if (isset($foldertemplatecontentid)) {
    $urlparams['sstsid'] = $foldertemplatecontentid;
}
$url = new moodle_url('/mod/openstudio/contentedit.php', $urlparams);
if ($type === content::TYPE_FOLDER_CONTENT) {
    if ($sid == 0) {
        $formcontentname = 'Add content';
    } else {
        $formcontentname = 'Edit content';
    }
} else {
    $formcontentname = $contentdataname;
}

// Get user id that we need to show stream for.
$vuid = optional_param('vuid', $USER->id, PARAM_INT);
if ($vuid != $USER->id) {
    $slotowner = studio_api_user_get_user_by_id($vuid);
    $viewuser = $USER;
} else {
    $slotowner = $viewuser = $USER;
}

// Get page url.
$strpageurl = util::get_current_url();

// Set stream view.
$vid = optional_param('vid', -1, PARAM_INT);
if (! in_array($vid, array(content::VISIBILITY_PRIVATE,
                           content::VISIBILITY_PRIVATE_PINBOARD,
                           content::VISIBILITY_GROUP,
                           content::VISIBILITY_MODULE,
                           content::VISIBILITY_WORKSPACE))) {

    $vid = $theme->homedefault;
}

// If group mode is not on, then redirect request to module workspace.
if (!$permissions->feature_group && ($vid == content::VISIBILITY_GROUP)) {
    $vid = content::VISIBILITY_MODULE;
}

// If activity mode is not on, then redirect request to module workspace.
if (!$permissions->feature_studio && ($vid == content::VISIBILITY_PRIVATE)) {
    $vid = content::VISIBILITY_MODULE;
}

// If pinboard mode is not on, then redirect request to module workspace.
if (!$permissions->feature_pinboard && ($vid == content::VISIBILITY_PRIVATE_PINBOARD)) {
    $vid = content::VISIBILITY_MODULE;
}

// If module mode is not on, then redirect request to module first available workspace.
if (!$permissions->feature_module && ($vid == content::VISIBILITY_MODULE)) {
    $vid = $permissions->allow_visibilty_modes[0];
}

// Set default visibility.
$allowedvisibility = explode(",", $cminstance->allowedvisibility);

if (!in_array($visibilityid, $allowedvisibility)) {
    $visibilityid = $cminstance->defaultvisibility;
}

$options = array(
        'courseid' => $course->id,
        'feature_module' => $permissions->feature_module,
        'feature_enablefolders' => $permissions->feature_enablefolders,
        'feature_group' => $permissions->feature_group,
        'isenrolled' => $permissions->activeenrollment,
        'groupingid' => $permissions->groupingid,
        'groupmode' => $permissions->groupmode,
        'sharewithothers' => $permissions->sharewithothers,
        'feature_contentusesfileupload' => $permissions->feature_contentusesfileupload,
        'feature_contentusesweblink' => $permissions->feature_contentusesweblink,
        'feature_contentusesembedcode' => $permissions->feature_contentusesembedcode,
        'feature_contentallownotebooks' => $permissions->feature_contentallownotebooks,
        'defaultvisibility' => $visibilityid,
        'allowedvisibility' => $allowedvisibility,
        'allowedfiletypes' => explode(",", $cminstance->filetypes),
        'contentid' => $contentdata->sid,
        'contenttype' => $contentdata->contenttype,
        'contentname' => $formcontentname,
        'isfoldercontent' => ($type == content::TYPE_FOLDER_CONTENT) ? true : false,
        'isfolderlock' => $isfolderlock,
        'max_bytes' => $cminstance->contentmaxbytes
);
$contentform = new mod_openstudio_content_form($url->out(false), $options,
        'post', '', array('class' => 'unresponsive'));

if ($contentform->is_cancelled()) {

    $urlparams = array('id' => $id, 'sid' => $sid);
    if (!is_null($folderid)) {
        $urlparams['ssid'] = $folderid;
    }
    if ($sid > 0) {
        $url = new moodle_url('/mod/openstudio/content.php', $urlparams);
    } else {
        $urlparams['lid'] = $lid;
        $urlparams['type'] = $contentdata->contenttype;
        if (isset($foldertemplatecontentid)) {
            $urlparams['sstsid'] = $foldertemplatecontentid;
        }
        $url = new moodle_url('/mod/openstudio/contentedit.php', $urlparams);
    }
    return redirect($url->out(false));

} else if ($contentformdata = $contentform->get_data()) {

    // Because we are dealing with potentially large file upoads,
    // we up memory limit when processing content creation and updates.
    raise_memory_limit(MEMORY_EXTRA);

    $context = context_module::instance($cm->id);

    // If the submitted form contains a file upload, then we prepare
    // the $contentformfile variable which will hold information about the
    // uploaded file which is stored in the draft storage area.
    $contentformfile = null;
    $draftitemid = file_get_submitted_draft_itemid('attachments');
    $draftareafiles = file_get_drafarea_files($draftitemid, $filepath = '/');
    if (is_object($draftareafiles)) {
        if (count($files = file_get_drafarea_files($draftitemid, $filepath = '/')->list)) {
            if (count($files) > 1) {
                usort($files, function($a, $b) {
                    if ($a->sortorder == $b->sortorder) {
                        return 0;
                    }
                    return $a->sortorder < $b->sortorder ? -1 : 1;
                });
            }
            $contentformfile = array(
                'id' => $draftitemid,
                'file' => $files[0],
                'checksum' => util::calculate_file_hash($files[0])
            );
            $contentformfile['mimetype'] = util::mimeinfo_from_type(
                    mimeinfo('type', $contentformfile['file']->filename));
        }
    }

    // Save current form description field data before it gets changed.
    $contentformdatadescription = $contentformdata->description;
    if (is_array($contentformdata->description)) {
        $contentformdata->description = $contentformdata->description['text'];
        $contentformdata->textformat = 1;
    }

    $contentformdata->commentformat = 1;

    if ($contentformdata->sid > 0) {
        $contentupdatemode = content::UPDATEMODE_UPDATED;

        $contentid = content::update(
                $userid,
                $contentformdata->sid,
                $contentformdata,
                $contentformfile,
                $context,
                $cminstance->versioning,
                $cm,
                false,
                $folderid
        );
    } else {
        if ($type === content::TYPE_FOLDER_CONTENT) {
            $contentformdata->visibility = content::VISIBILITY_INFOLDERONLY;
        }
        $contentupdatemode = content::UPDATEMODE_CREATED;

        $contentid = content::create(
                $cminstance->id,
                $userid,
                $contentformdata->levelcontainer,
                $contentformdata->levelid,
                $contentformdata,
                $contentformfile,
                $context,
                $cm
        );

        if ($type === content::TYPE_FOLDER_CONTENT) {
            if ($foldertemplatecontentid > 0) {
                $foldercontenttemplate = studio_api_set_template_slot_get($foldertemplatecontentid);
                $foldercontenttemplate->foldercontenttemplateid = $foldercontenttemplate->id;
                $foldercontenttemplate = (array) $foldercontenttemplate;
                $foldertitle = '';
            } else {
                $foldercontenttemplate = array();
                $foldertitle = $contentformdata->name;
            }
            if ($folderid === 0) {
                if ($cminstance->defaultvisibility == content::VISIBILITY_GROUP) {
                    // Users can only share contents to groups that they are a member of.
                    // This applies to all users and admins.
                    if ($permissions->groupingid > 0) {
                        $tutorgroups = studio_api_group_list(
                                $course->id, $permissions->groupingid, $permissions->activeuserid, 1);
                    } else {
                        $tutorgroups = studio_api_group_list(
                                $course->id, 0, $permissions->activeuserid, 1);
                    }
                    $firsttutorgroupid = false;
                    if ($tutorgroups !== false) {
                        foreach ($tutorgroups as $tutorgroup) {
                            $tutorgroupid = 0 - $tutorgroup->groupid;
                            $firsttutorgroupid = $tutorgroupid;
                            break;
                        }
                    }
                    if ($firsttutorgroupid !== false) {
                        $folderdatavisibility = $firsttutorgroupid;
                    } else {
                        $folderdatavisibility = content::VISIBILITY_PRIVATE;
                    }
                } else {
                    $folderdatavisibility = $cminstance->defaultvisibility;
                }

                // If we don't have a folder yet, create one.
                $folderdata = (object) array(
                    'visibility' => $folderdatavisibility,
                    'contenttype' => content::TYPE_FOLDER,
                    'name' => $foldertitle
                );
                $levelcontainer = $lid > 0 ? 3 : 0;
                $folderid = content::create($cminstance->id, $userid, $levelcontainer,
                        $lid, $folderdata, null, $context, $cm);
            }
            if (!studio_api_set_slot_add($folderid, $contentid, $userid, $foldercontenttemplate)) {
                $returnurl = new moodle_url('/mod/openstudio/view.php',
                        array('id' => $cm->id, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD));
                print_error('errornopermissiontoaddcontent', 'openstudio', $returnurl->out(false));
            }
        }
    }

    if ($contentid !== false) {
        // Log page action.
        $loggingurl = new moodle_url('/mod/openstudio/contentedit.php',
                array('id' => $id, 'sid' => $contentid, 'userid' => $userid));

        if (isset($contentformdata->contenttype)) {
            switch ($contentformdata->contenttype) {
                case content::TYPE_FOLDER:
                    $contentupdatemode .= content::UPDATEMODE_FOLDER;
                    break;
            }
        }

        switch ($contentupdatemode) {
            case content::UPDATEMODE_CREATED:
                $contentactionevent = 'content_created';
                if (isset($contentformdata->visibility)
                        && ($contentformdata->visibility == content::VISIBILITY_INFOLDERONLY)) {
                    $contentactionevent = 'folder_content_created';
                }
                util::trigger_event($cm->id, $contentactionevent, '',
                        util::get_page_name_and_params(true, $loggingurl->out(false)),
                        util::format_log_info($contentdataname));
                break;

            case content::UPDATEMODE_CREATED.content::UPDATEMODE_FOLDER:
                util::trigger_event($cm->id, 'folder_created', '',
                        util::get_page_name_and_params(true, $loggingurl->out(false)),
                        util::format_log_info($contentdataname));
                break;

            case content::UPDATEMODE_UPDATED:
                util::trigger_event($cm->id, 'content_edited', '',
                        util::get_page_name_and_params(true, $loggingurl->out(false)),
                        util::format_log_info($contentdataname));
                break;

            case content::UPDATEMODE_UPDATED.content::UPDATEMODE_FOLDER:
                util::trigger_event($cm->id, 'folder_edited', '',
                        util::get_page_name_and_params(true, $loggingurl->out(false)),
                        util::format_log_info($contentdataname));
                break;

            case content::UPDATEMODE_BEINGEDIT:
            case content::UPDATEMODE_BEINGEDIT.content::UPDATEMODE_FOLDER:
                break;
        }

        if ($type === content::TYPE_FOLDER_CONTENT) {
            $url = new moodle_url('/mod/openstudio/content.php',
                    array('id' => $id, 'sid' => $contentid, 'ssid' => $folderid));
        } else {
            $url = new moodle_url('/mod/openstudio/content.php',
                    array('id' => $id, 'sid' => $contentid));
        }

        return redirect($url->out(false));
    }

    // Restore original form description field data so it can be displayed again.
    $contentformdata->description = $contentformdatadescription;

} else {
    if (!isset($contentdata->attachments) && isset($contentdata->contenttype)) {
        // Check if the content contains uploaded file, if so, then load
        // the uploaded file into the draft area for the moodle form.
        $context = context_module::instance($cm->id);
        $draftitemid = 0;
        switch ($contentdata->contenttype) {
            case content::TYPE_IMAGE:
            case content::TYPE_VIDEO:
            case content::TYPE_AUDIO:
            case content::TYPE_DOCUMENT:
                $fs = get_file_storage();
                // If we've got notebook files, handle them here and break. If not, fall through to below.
                $notebookfiles = $fs->get_area_files($context->id, 'mod_openstudio', 'notebook', $contentdata->fileid);
                if (!empty($notebookfiles)) {
                    file_prepare_draft_area($draftitemid, $context->id, 'mod_openstudio', 'notebook', $contentdata->fileid);
                    // We only want the original zip in the draft area, not the contents as well.
                    $usercontext = context_user::instance($USER->id);
                    $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid);
                    foreach ($draftfiles as $draftfile) {
                        $mimetype = $draftfile->get_mimetype();
                        if ($mimetype && $mimetype != 'application/x-smarttech-notebook') {
                            $draftfile->delete();
                        }
                    }
                    $contentdata->attachments = $draftitemid;
                    $contentdata->checksum = util::calculate_file_hash($draftitemid);
                    break; // Only if this is a notebook.
                }
            case content::TYPE_PRESENTATION:
            case content::TYPE_SPREADSHEET:
                file_prepare_draft_area(
                        $draftitemid, $context->id, 'mod_openstudio', 'content', $contentdata->fileid);
                $contentdata->attachments = $draftitemid;
                $contentdata->checksum = util::calculate_file_hash($draftitemid);

                break;
        }
    }

    if ($contentdata->sid > 0) {
        // Content always uses rich text editor.
        $contentdatadescription = array('text' => $contentdata->description, 'format' => 1);
        $contentdata->description = $contentdatadescription;
        $contentdata->textformat = 1;

        if (($permissions->feature_contentcommentuseshtml || ((int) $contentdata->commentformat === 1))) {
            $contentdata->commentformat = 1;
        }

        if ($folderid && $foldercontentdata) {
            if ($foldercontentdata->provenanceid != null && $foldercontentdata->provenancestatus == folder::PROVENANCE_EDITED) {
                if (!empty($foldercontentdata->foldercontentname)) {
                    $contentdata->name = $foldercontentdata->foldercontentname;
                }
                if (!empty($foldercontentdata->foldercontentdescription)) {
                    $contentdata->description = $foldercontentdata->foldercontentdescription;
                }
            }
        }

        $contentupdatemode = content::UPDATEMODE_BEINGEDIT;
    } else {
        $contentupdatemode = content::UPDATEMODE_NEWCONTENT;
    }

    if ($contentdata->contenttype == content::TYPE_FOLDER) {
        $contentupdatemode .= content::UPDATEMODE_FOLDER;
    }

    $contentform->set_data($contentdata);

    if (($contentupdatemode == content::UPDATEMODE_NEWCONTENT) && ($contentdata->levelid <= 0)) {
        // Dont log if it's a new pinboard content.
        // We dont because there is no sensible URL we can log in such case.
        // Line below to keep Moodle codechecker happy.
        $dummy = 0;
    } else {
        // Log page action.
        switch ($contentupdatemode) {
            case content::UPDATEMODE_CREATED:
                $contentactionevent = 'content_created';
                if ($contentdata->visibility == content::VISIBILITY_INFOLDERONLY) {
                    $contentactionevent = 'folder_content_created';
                }
                util::trigger_event($cm->id, $contentactionevent, '',
                        util::get_page_name_and_params() . "&userid={$userid}",
                        util::format_log_info($contentdataname));
                break;

            case content::UPDATEMODE_CREATED.content::UPDATEMODE_FOLDER:
                util::trigger_event($cm->id, 'folder_created', '',
                        util::get_page_name_and_params() . "&userid={$userid}",
                        util::format_log_info($contentdataname));
                break;

            case content::UPDATEMODE_UPDATED:
                util::trigger_event($cm->id, 'content_edited', '',
                        util::get_page_name_and_params() . "&userid={$userid}",
                        util::format_log_info($contentdataname));
                break;

            case content::UPDATEMODE_UPDATED.content::UPDATEMODE_FOLDER:
                util::trigger_event($cm->id, 'folder_edited', '',
                        util::get_page_name_and_params() . "&userid={$userid}",
                        util::format_log_info($contentdataname));
                break;

            case content::UPDATEMODE_BEINGEDIT:
            case content::UPDATEMODE_BEINGEDIT.content::UPDATEMODE_FOLDER:
                break;
        }
    }
}

ob_start();
$contentform->display();
$contenthtmlform = ob_get_contents();
ob_end_clean();

$PAGE->requires->js_call_amd('mod_openstudio/contentedit', 'init');

// Generate HTML.
$renderer = $PAGE->get_renderer('mod_openstudio');
$html = $renderer->siteheader(
        $coursedata, $permissions, $theme, $cm->name, '', $vid);

$html .= $renderer->content_edit($contenthtmlform, $options);

// Output HTML
echo $renderer->header(); // Header.
echo $html;
echo $renderer->footer(); // Footer.
