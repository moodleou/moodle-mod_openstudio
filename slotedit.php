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
 * @package mod_studio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/studio/locallib.php');
require_once($CFG->dirroot . '/mod/studio/slot_form.php');
require_once($CFG->dirroot . '/mod/studio/collectionitem_form.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Slot Id.
// Value 0 = new slot.
// Value X = id of slot to edit.
$sid = optional_param('sid', 0, PARAM_INT);

// Slot level id.
$lid = optional_param('lid', 0, PARAM_INT);

// Slot collection item id.
$collectionitemid = optional_param('cid', 0, PARAM_INT);

// Slot type.
$type = optional_param('type', STUDIO_CONTENTTYPE_NONE, PARAM_INT);
if (!in_array($type, array(STUDIO_CONTENTTYPE_NONE,
                           STUDIO_CONTENTTYPE_COLLECTION,
                           STUDIO_CONTENTTYPE_SET,
                           STUDIO_CONTENTTYPE_SET_SLOT))) {
    $type = STUDIO_CONTENTTYPE_NONE;
}

// User id.  This is used by admin who needs to look at other user's slot.
// In general, it is never used by non-adminusers.  The code has been
// written to prevent users from using this parameter to access other
// people's content if they dont have manage content permission.
$userid = optional_param('userid', $USER->id, PARAM_INT);

$setid = optional_param('ssid', null, PARAM_INT);

// Page inita and security checks.
$coursedata = studio_internal_render_page_init($id,
        array('mod/studio:view', 'mod/studio:addcontent'));
$cm = $coursedata->cm;
$cminstance = $coursedata->cminstance;
$course = $coursedata->course;
$mcontext = $coursedata->mcontext;
$permissions = $coursedata->permissions;
$theme = $coursedata->theme;

// Terms and conditions check.
studio_internal_tandc_check($id);

// If sets/collections feature is disabled, then we do not allow new pinboard sets/collections to be created.
if ($sid == 0) {
    if ((!$permissions->feature_enablesets && ($type === STUDIO_CONTENTTYPE_SET)) ||
        (!$permissions->feature_enablesets && ($type === STUDIO_CONTENTTYPE_SET_SLOT)) ||
        (!$permissions->feature_enablecollections && ($type === STUDIO_CONTENTTYPE_COLLECTION))) {
        if (($sid == 0) && ($lid == 0)) {
            $returnurl = new moodle_url('/mod/studio/view.php',
                    array('id' => $cm->id, 'vid' => STUDIO_VISIBILITY_PRIVATE_PINBOARD));
            print_error('errornopermissiontoaddcontent', 'studio', $returnurl->out(false));
        }
    }
}

// If a levelid greater than 0 is given with no sid, then its an activity slot.
// We check if the slot has previously been created and if so acquire the slot id
// and redirect the user to the correct URL.
if (($lid > 0) && ($sid <= 0)) {
    $slotdata = mod_openstudio\local\api\content::get_record_via_levels($cminstance->id, $userid, 3, $lid);

    // Process the level management locks.
    if ($slotdata !== false) {
        $slotdata = studio_api_lock_determine_lock_status($slotdata);
        $redirecturl = new moodle_url('/mod/studio/slotedit.php',
            array('id' => $cm->id, 'sid' => $slotdata->id, 'userid' => $userid));
        return redirect($redirecturl->out(false));
    }
}

// Get user record.
if ($userid == $USER->id) {
    $userrecord = $USER;
} else {
    // We do this to confirm the given userid in the url is correct.
    $userrecord = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
}

// Set upload file size limit.
define('STUDIO_SLOT_MAXBYTES', $cminstance->slotmaxbytes);

// Set returnurl which will be used if error occurred and need to redirect user.
$returnurl = new moodle_url('/mod/studio/view.php',
        array('id' => $cm->id, 'vid' => STUDIO_VISIBILITY_PRIVATE));

$slotdata = new stdClass();
if ($sid > 0) {
    $slotdata = mod_openstudio\local\api\content::get_record($userid, $sid);
    if ($slotdata === false) {
        print_error('errorinvalidslot', 'studio', $returnurl->out(false));
    }
    $lid = $slotdata->levelid;
}

$slotdataname = '';
$slotisinpinboard = false;

if ($lid > 0) {
    $level3data = studio_api_levels_get_record(STUDIO_DEFAULT_SLOTLEVELCONTAINER, $lid);
    if ($level3data === false) {
        print_error('errorinvalidslot', 'studio', $returnurl->out(false));
    }
    if ($type == STUDIO_CONTENTTYPE_SET_SLOT) {
        $slotdata->slottype = $type;
        $slotdata->levelid = 0;
        $slotdata->levelcontainer = 0;
    } else {
        $slotdata->slottype = $level3data->contenttype;
        $slotdata->levelid = $lid;
        $slotdata->levelcontainer = STUDIO_DEFAULT_SLOTLEVELCONTAINER;
    }
} else {
    $slotisinpinboard = true;
    $slotdata->levelid = 0;
    $slotdata->levelcontainer = 0;
    $slotdata->slottype = $type;
}

$setlid = 0;
$settemplateslotid = 0;

// Check if its a new set slot.
if (($type == STUDIO_CONTENTTYPE_SET_SLOT) && ($sid == 0)) {
    if ($setid === null) {
        print_error('errornoset', 'studio', $returnurl->out(false));
    }
    $settemplateslotid = optional_param('sstsid', 0, PARAM_INT);
    if ($setid > 0) {
        $setdata = mod_openstudio\local\api\content::get($setid);
        if ($setdata) {
            $setlid = $setdata->levelid;
            $slotisinpinboard = ($setlid > 0) ? false : true;
        }
    }
}

// Check if we are processing a set and get information if so.
if (isset($slotdata->contenttype) && ($slotdata->contenttype == STUDIO_CONTENTTYPE_SET)) {
    $type = STUDIO_CONTENTTYPE_SET;
    $setid = $slotdata->id;
    $setdata = mod_openstudio\local\api\content::get($setid);
    if ($setdata) {
        $setlid = $setdata->levelid;
        $slotisinpinboard = ($setlid > 0) ? false : true;
    }
}

// Check if we are processing a set slot and get information if so.
if ($sid && $setslotdata = studio_api_set_slot_get_by_slotid($setid, $slotdata->id)) {
    if ($setslotdata) {
        $setid = $setslotdata->setid;
        $type = STUDIO_CONTENTTYPE_SET_SLOT;
        $setdata = mod_openstudio\local\api\content::get($setid);
        if ($setdata) {
            $setlid = $setdata->levelid;
            $slotisinpinboard = ($setlid > 0) ? false : true;
        }
    }
}

if (($lid > 0) || ($setlid > 0)) {
    $lidtemp = ($lid > 0) ? $lid : $setlid;
    $level3data = studio_api_levels_get_record(STUDIO_DEFAULT_SLOTLEVELCONTAINER, $lidtemp);
    if ($level3data === false) {
        print_error('errorinvalidslot', 'studio', $returnurl->out(false));
    }
    $level2data = studio_api_levels_get_record(
            STUDIO_DEFAULT_ACTIVITYLEVELCONTAINER, $level3data->level2id);
    if ($level2data === false) {
        print_error('errorinvalidslot', 'studio', $returnurl->out(false));
    }
    $level1data = studio_api_levels_get_record(
            STUDIO_DEFAULT_BLOCKLEVELCONTAINER, $level2data->level1id);
    if ($level1data === false) {
        print_error('errorinvalidslot', 'studio', $returnurl->out(false));
    }

    $slotdataname = trim($level1data->name);
    if (trim($level2data->name) != '') {
        $slotdataname .= ' - ' . $level2data->name;
    }
    if (trim($level3data->name) != '') {
        $slotdataname .= ' - ' . $level3data->name;
    }

} else {
    if ($slotdata->slottype == STUDIO_CONTENTTYPE_COLLECTION) {
        $slotdataname = get_string('collectiontitlepinboard', 'studio');
    } else {
        if (!in_array($type, array(STUDIO_CONTENTTYPE_SET, STUDIO_CONTENTTYPE_SET_SLOT))) {
            $slotdataname = get_string('slottitlepinboard', 'studio');
        }
    }
}

if ($sid > 0) {
    $slotdata = mod_openstudio\local\api\content::get_record($userid, $sid);
    if ($slotdata === false) {
        print_error('errorinvalidslot', 'studio', $returnurl->out(false));
    }

    if ($permissions->feature_enablelock) {
        if (isset($slotdata->locktype) &&
                (($slotdata->locktype == STUDIO_LOCKED_ALL) ||
                 ($slotdata->locktype == STUDIO_LOCKED_CRUD) ||
                 ($slotdata->locktype == STUDIO_LOCKED_SOCIAL_CRUD) ||
                 ($slotdata->locktype == STUDIO_LOCKED_COMMENT_CRUD))) {
            print_error('slotislocked', 'studio', $returnurl->out(false));
        }
    }

    // Given the slot exists, get the slot owner again to prevent user spoofing.
    $userid = $slotdata->userid;
    $userrecord = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

    // Is the slot mine?
    $permissions->slotismine = ($slotdata->userid == $USER->id) ? true : false;

    // If slot is not mine, and I dont have managecontent capability, then error.
    if (!$permissions->managecontent) {
        if (!$permissions->slotismine) {
            print_error('errornopermissiontoaddcontent', 'studio', $returnurl->out(false));
        }
    }

    $slotdata->sid = $sid;
    if (isset($slotdata->embedcode)) {
        $slotdata->embedcode = studio_internal_render_slot_translate_embedded_content($slotdata->embedcode);
    }
    if (!in_array($type, array(STUDIO_CONTENTTYPE_SET, STUDIO_CONTENTTYPE_SET_SLOT))) {
        $slotdataname = studio_internal_get_slot_name($slotdata);
    }
    if (!in_array($slotdata->contenttype, array(STUDIO_CONTENTTYPE_NONE,
                                                STUDIO_CONTENTTYPE_COLLECTION,
                                                STUDIO_CONTENTTYPE_SET))) {
        $slotdata->slottype = STUDIO_CONTENTTYPE_NONE;
    } else {
        $slotdata->slottype = $slotdata->contenttype;
    }

    $strsloturl = new moodle_url('/mod/studio/slot.php',
            array('id' => $cm->id, 'sid' => $sid));
    $strpageurl = new moodle_url('/mod/studio/slotedit.php',
            array('id' => $cm->id, 'sid' => $sid));
} else {
    // If its a blank new slot, then make sure the owner of the slot is the logged in user.
    $userid = $USER->id;
    $userrecord = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

    $slotdata->sid = 0;
    $slotleveldata = studio_api_levels_get_record($slotdata->levelcontainer, $slotdata->levelid);
    // Check lock level management access.
    if ($permissions->feature_enablelock) {
        if (isset($slotleveldata->locktype) &&
                (($slotleveldata->locktype == STUDIO_LOCKED_ALL) ||
                 ($slotleveldata->locktype == STUDIO_LOCKED_CRUD) ||
                 ($slotleveldata->locktype == STUDIO_LOCKED_SOCIAL_CRUD) ||
                 ($slotleveldata->locktype == STUDIO_LOCKED_COMMENT_CRUD))) {
            $slotislock = false;
            $slotlocktime = isset($slotleveldata->locktime) ? $slotleveldata->locktime : 0;
            $slotunlocktime = isset($slotleveldata->unlocktime) ? $slotleveldata->unlocktime : 0;
            if ($slotlocktime > $slotunlocktime ) {
                if (($slotunlocktime > 0) && (time() > $slotunlocktime)) {
                    $slotislock = false;
                }
                if (($slotlocktime > 0) && (time() > $slotlocktime)) {
                    $slotislock = true;
                }
            } else {
                if (($slotlocktime > 0) && (time() > $slotlocktime)) {
                    $slotislock = true;
                }
                if (($slotunlocktime > 0) && (time() > $slotunlocktime)) {
                    $slotislock = false;
                }
            }
            if ($slotislock) {
                if ($slotleveldata->unlocktime > 0) {
                    $dtm = userdate($slotleveldata->unlocktime);
                    print_error(get_string('erroractivitynotavailable', 'studio', $dtm), 'studio', $returnurl->out(false));
                } else {
                    print_error(get_string('slotislocked', 'studio'), 'studio', $returnurl->out(false));
                }
            }
        }
    }
    $strsloturl = '';
    $strpageurl = new moodle_url('/mod/studio/slotedit.php',
            array('id' => $cm->id, 'lid' => $slotdata->levelid, 'sid' => $sid));

    $permissions->slotismine = true;
}

$setdatalevelid = 0;
if (isset($setdata)) {
    if (trim($slotdataname) == '') {
        $slotdataname .= $setdata->name;
    } else {
        $slotdataname .= ' - ' . $setdata->name;
    }
    $setdatalevelid = $setdata->levelid;
} else if ($lid > 0 && $setid === 0) {
    $setdata = (object) array(
        'id' => $setid,
        'name' => $slotdataname,
        'levelid' => $lid
    );
    $slotdataname = get_string('pinboardnewslot', 'studio');
    $setdatalevelid = $setdata->levelid;
}

if (($setdatalevelid == 0) && ($slotdata->sid == 0) && ($slotdata->levelid == 0) && ($slotdata->levelcontainer == 0)) {
    $returnurl = new moodle_url('/mod/studio/view.php',
            array('id' => $cm->id, 'vid' => STUDIO_VISIBILITY_MODULE));
    if (!$permissions->feature_pinboard) {
        print_error('errorpinboardisdisabled', 'studio', $returnurl->out(false));
    }

    $returnurl = new moodle_url('/mod/studio/view.php',
            array('id' => $cm->id, 'vid' => STUDIO_VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1));
    if ($type === STUDIO_CONTENTTYPE_SET_SLOT && $setid > 0) {
        if ($sid == 0 && empty($settemplateslotid) && !studio_api_set_can_add_more_slots($setid, $permissions, $lid)) {
            print_error('errorpinboardsetexceedlimit', 'studio', $returnurl->out(false));
        }
    } else {
        if ($permissions->feature_pinboard && ($permissions->pinboarddata->available <= 0)) {
            print_error('errorpinboardexceedlimit', 'studio', $returnurl->out(false));
        }
    }
}
if (($setdatalevelid > 0) && ($slotdata->sid == 0) && ($slotdata->levelid == 0) && ($slotdata->levelcontainer == 0)) {
    $returnurl = new moodle_url('/mod/studio/view.php',
            array('id' => $cm->id, 'vid' => STUDIO_VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1));
    if ($type === STUDIO_CONTENTTYPE_SET_SLOT && $setid > 0) {
        if ($sid == 0 && empty($settemplateslotid) && !studio_api_set_can_add_more_slots($setid, $permissions, $lid)) {
            print_error('errorpinboardsetexceedlimit', 'studio', $returnurl->out(false));
        }
    }
}

if (trim($slotdataname) == '') {
    if ($type == STUDIO_CONTENTTYPE_SET) {
        if ($slotisinpinboard) {
            $slotdataname = get_string('settitlepinboard', 'studio');
        } else {
            $slotdataname = get_string('settitle', 'studio');
        }
    }
}

// Render page header and crumb trail.
$strpagetitle = $strpageheading =
        get_string('pageheaderslotedit', 'studio',
        array('cname' => $course->shortname, 'cmname' => $cm->name, 'title' => $slotdataname));
studio_internal_render_page_defaults($PAGE, $strpagetitle, $strpageheading, $strpageurl, $course, $cm);
$crumbarray = array();
$strareaurl = new moodle_url('/mod/studio/view.php', array('id' => $cm->id, 'vid' => 1));
if ($slotisinpinboard) {
    $viewmode = 'pinboard';
    $vid = STUDIO_VISIBILITY_PRIVATE_PINBOARD;
    $pageview = 'edit pinboard';
    $strareaurl = new moodle_url('/mod/studio/view.php',
            array('id' => $cm->id, 'vid' => STUDIO_VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1));
    $crumbarray[get_string('navmypinboard', 'studio')] = $strareaurl->out(false);
} else {
    $viewmode = 'work';
    $vid = STUDIO_VISIBILITY_PRIVATE;
    $pageview = 'edit';
    $crumbarray[get_string('navmystudiowork', 'studio')] = $strareaurl->out(false);
}
$crumbarray[$slotdataname] = $strsloturl;
$crumbarray[get_string('navedit', 'studio')] = $strpageurl;
studio_internal_render_page_crumb($PAGE, $cm->id, navigation_node::TYPE_ACTIVITY, $crumbarray);

// Generate and process form.
if (($slotdata->slottype == STUDIO_CONTENTTYPE_COLLECTION) && ($collectionitemid > 0)) {

    // This part of the code processes the collection item edit form.
    // This form is a subset of the main slot edit 'attachements' => $file1->get_id())form.

    // Security check to ensure collection item is in the given collection.
    $collectiondata = studio_api_collection_get_item($collectionitemid);
    if ($collectiondata->collectionid != $sid) {
        $urlparams = array('id' => $id, 'sid' => $slotdata->sid);
        $url = new moodle_url('/mod/studio/slotedit.php', $urlparams);
        return redirect($url->out(false) . '#collectionslotsheader');
    }

    $urlparams = array(
            'id' => $id, 'lid' => $lid, 'sid' => $sid,
            'cid' => $collectionitemid, 'type' => $slotdata->slottype);
    $url = new moodle_url('/mod/studio/slotedit.php', $urlparams);
    $options = array(
            'feature_slottextuseshtml' => $permissions->feature_slottextuseshtml,
            'slotid' => $slotdata->sid,
            'slotname' => $slotdataname,
            'collectionitemname' => $collectiondata->name
    );
    $slotform = new mod_studio_collectionitem_form($url->out(false), $options,
            'post', '', array('class' => 'unresponsive'));

    if ($slotform->is_cancelled()) {

        // Form cancelled, so return to main slot edit form.
        $slotexists = mod_openstudio\local\api\content::get($slotdata->sid);
        $urlparams = array('id' => $id, 'sid' => $slotdata->sid);
        if ($slotexists === false) {
            $url = new moodle_url('/mod/studio/slotedit.php', $urlparams);
        } else {
            $url = new moodle_url('/mod/studio/slot.php', $urlparams);
        }
        return redirect($url->out(false) . '#collectionslotsheader');

    } else if ($slotformdata = $slotform->get_data()) {

        // Save current form description field data before it gets changed.
        $slotformdatadescription = $slotformdata->description;
        if (($permissions->feature_slottextuseshtml || ((int) $slotdata->textformat === 1))
                && is_array($slotformdata->description)) {
            $slotformdata->description = $slotformdata->description['text'];
            $slotformdata->textformat = 1;
        }

        $slotformdata->id = $collectionitemid;
        studio_api_collection_update_item($slotformdata);

        $urlparams = array('id' => $id, 'sid' => $slotdata->sid);
        $url = new moodle_url('/mod/studio/slotedit.php', $urlparams);
        return redirect($url->out(false) . '#collectionslotsheader');

    } else {

        $collectionitemdata = studio_api_collection_get_item($collectionitemid);

        if ($slotdata->sid > 0) {
            // If slot uses rich text editor, then we change the data structure for the description field.
            if ($permissions->feature_slottextuseshtml || ((int) $slotdata->textformat === 1)) {
                $slotdatadescription = array('text' => $collectionitemdata->description, 'format' => 1);
                $collectionitemdata->description = $slotdatadescription;
            }
        }

        $slotform->set_data($collectionitemdata);

    }

} else {

    // This part of the code processes the slot edit form.

    $issetlock = false;
    if (isset($setdata) && $setdata->id !== 0) {
        $issetlock = !studio_api_lock_slot_show_crud($setdata, $permissions);
    }

    $urlparams = array('id' => $id, 'lid' => $lid, 'sid' => $sid,
                       'type' => $slotdata->slottype);
    if (!is_null($setid)) {
        $urlparams['ssid'] = $setid;
    }
    if (isset($settemplateslotid)) {
        $urlparams['sstsid'] = $settemplateslotid;
    }
    $url = new moodle_url('/mod/studio/slotedit.php', $urlparams);
    if ($type === STUDIO_CONTENTTYPE_SET_SLOT) {
        if ($sid == 0) {
            $formslotname = 'Add slot';
        } else {
            $formslotname = 'Edit slot';
        }
    } else {
        $formslotname = $slotdataname;
    }
    $options = array(
            'courseid' => $course->id,
            'feature_module' => $permissions->feature_module,
            'feature_group' => $permissions->feature_group,
            'isenrolled' => $permissions->activeenrollment,
            'groupingid' => $permissions->groupingid,
            'groupmode' => $permissions->groupmode,
            'sharewithothers' => $permissions->sharewithothers,
            'feature_slottextuseshtml' => $permissions->feature_slottextuseshtml,
            'feature_slotusesfileupload' => $permissions->feature_slotusesfileupload,
            'feature_slotusesweblink' => $permissions->feature_slotusesweblink,
            'feature_slotusesembedcode' => $permissions->feature_slotusesembedcode,
            'feature_slotallownotebooks' => $permissions->feature_slotallownotebooks,
            'defaultvisibility' => $cminstance->defaultvisibility,
            'allowedvisibility' => explode(",", $cminstance->allowedvisibility),
            'allowedfiletypes' => explode(",", $cminstance->filetypes),
            'slotid' => $slotdata->sid,
            'slottype' => $slotdata->slottype,
            'slotname' => $formslotname,
            'issetslot' => ($type == STUDIO_CONTENTTYPE_SET_SLOT) ? true : false,
            'issetlock' => $issetlock
    );
    $slotform = new mod_studio_slot_form($url->out(false), $options,
            'post', '', array('class' => 'unresponsive'));

    if ($slotform->is_cancelled()) {

        $urlparams = array('id' => $id, 'sid' => $sid);
        if (!is_null($setid)) {
            $urlparams['ssid'] = $setid;
        }
        if ($sid > 0) {
            $url = new moodle_url('/mod/studio/slot.php', $urlparams);
        } else {
            $urlparams['lid'] = $lid;
            $urlparams['type'] = $slotdata->slottype;
            if (isset($settemplateslotid)) {
                $urlparams['sstsid'] = $settemplateslotid;
            }
            $url = new moodle_url('/mod/studio/slotedit.php', $urlparams);
        }
        return redirect($url->out(false));

    } else if ($slotformdata = $slotform->get_data()) {

        // Get collection ordering of slot data.
        $collectionslotordering = true;
        $collectionslotmovedn = optional_param_array('movednbutton', array(), PARAM_INT);
        $collectionslotmoveup = optional_param_array('moveupbutton', array(), PARAM_INT);
        $collectionslotdeleteslot = optional_param_array('deletebutton', array(), PARAM_INT);
        $collectionsloteditslot = optional_param_array('editbutton', array(), PARAM_INT);
        if (empty($collectionslotmovedn) && empty($collectionslotmoveup) &&
                empty($collectionsloteditslot) && empty($collectionslotdeleteslot)) {
            $collectionslotordering = false;
        }

        // Because we are dealing with potentially large file upoads,
        // we up memory limit when processing slot creation and updates.
        studio_raise_memory_limit();

        $context = context_module::instance($cm->id);

        // If the submitted form contains a file upload, then we prepare
        // the $slotformfile variable which will hold information about the
        // uploaded file which is stored in the draft storage area.
        $slotformfile = null;
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
                $slotformfile = array(
                    'id' => $draftitemid,
                    'file' => $files[0],
                    'checksum' => studio_internal_calculate_file_hash($files[0])
                );
                $slotformfile['mimetype'] = studio_internal_mimeinfo_from_type(
                        mimeinfo('type', $slotformfile['file']->filename));
            }
        }

        // Save current form description field data before it gets changed.
        $slotformdatadescription = $slotformdata->description;
        if (($permissions->feature_slottextuseshtml || ((int) $slotformdata->textformat === 1))
                && is_array($slotformdata->description)) {
            $slotformdata->description = $slotformdata->description['text'];
            $slotformdata->textformat = 1;
        }

        if (($permissions->feature_slotcommentuseshtml || ((int) $slotformdata->commentformat === 1))) {
            $slotformdata->commentformat = 1;
        }

        if ($slotformdata->sid > 0) {
            $slotupdatemode = 'slot updated';

            $slotid = mod_openstudio\local\api\content::update(
                    $userid,
                    $slotformdata->sid,
                    $slotformdata,
                    $slotformfile,
                    $context,
                    $cminstance->versioning,
                    $cm,
                    false,
                    $setid
            );
        } else {
            if ($type === STUDIO_CONTENTTYPE_SET_SLOT) {
                $slotformdata->visibility = STUDIO_VISIBILITY_INSETONLY;
            }
            $slotupdatemode = 'slot created';

            $slotid = mod_openstudio\local\api\content::create(
                    $cminstance->id,
                    $userid,
                    $slotformdata->levelcontainer,
                    $slotformdata->levelid,
                    $slotformdata,
                    $slotformfile,
                    $context,
                    $cm
            );

            if ($type === STUDIO_CONTENTTYPE_SET_SLOT) {
                if ($settemplateslotid > 0) {
                    $setslottemplate = studio_api_set_template_slot_get($settemplateslotid);
                    $setslottemplate->setslottemplateid = $setslottemplate->id;
                    $setslottemplate = (array) $setslottemplate;
                    $settitle = '';
                } else {
                    $setslottemplate = array();
                    $settitle = $slotformdata->name;
                }
                if ($setid === 0) {
                    if ($cminstance->defaultvisibility == STUDIO_VISIBILITY_GROUP) {
                        // Users can only share slots to groups that they are a member of.
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
                            $setdatavisibility = $firsttutorgroupid;
                        } else {
                            $setdatavisibility = STUDIO_VISIBILITY_PRIVATE;
                        }
                    } else {
                        $setdatavisibility = $cminstance->defaultvisibility;
                    }

                    // If we don't have a set yet, create one.
                    $setdata = (object) array(
                        'visibility' => $setdatavisibility,
                        'contenttype' => STUDIO_CONTENTTYPE_SET,
                        'name' => $settitle
                    );
                    $levelcontainer = $lid > 0 ? 3 : 0;
                    $setid = mod_openstudio\local\api\content::create($cminstance->id, $userid, $levelcontainer,
                            $lid, $setdata, null, $context, $cm);
                }
                if (!studio_api_set_slot_add($setid, $slotid, $userid, $setslottemplate)) {
                    $returnurl = new moodle_url('/mod/studio/view.php',
                            array('id' => $cm->id, 'vid' => STUDIO_VISIBILITY_PRIVATE_PINBOARD));
                    print_error('errornopermissiontoaddcontent', 'studio', $returnurl->out(false));
                }
            }
        }

        if ($slotid !== false) {
            // Log page action.
            $loggingurl = new moodle_url('/mod/studio/slotedit.php',
                    array('id' => $id, 'sid' => $slotid, 'userid' => $userid));

            if (isset($slotformdata->contenttype)) {
                switch ($slotformdata->contenttype) {
                    case STUDIO_CONTENTTYPE_COLLECTION:
                        $slotupdatemode .= ' (collection)';
                        break;

                    case STUDIO_CONTENTTYPE_SET:
                        $slotupdatemode .= ' (set)';
                        break;
                }
            }

            switch ($slotupdatemode) {
                case 'slot created':
                    $slotactionevent = 'slot_created';
                    if (isset($slotformdata->visibility)
                            && ($slotformdata->visibility == STUDIO_VISIBILITY_INSETONLY)) {
                        $slotactionevent = 'set_slot_created';
                    }
                    studio_internal_trigger_event($cm->id, $slotactionevent, '',
                            studio_internal_getpagenameandparams(true, $loggingurl->out(false)),
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot created (collection)':
                    studio_internal_trigger_event($cm->id, 'collection_created', '',
                            studio_internal_getpagenameandparams(true, $loggingurl->out(false)),
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot created (set)':
                    studio_internal_trigger_event($cm->id, 'set_created', '',
                            studio_internal_getpagenameandparams(true, $loggingurl->out(false)),
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot updated':
                    studio_internal_trigger_event($cm->id, 'slot_edited', '',
                            studio_internal_getpagenameandparams(true, $loggingurl->out(false)),
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot updated (collection)':
                    studio_internal_trigger_event($cm->id, 'collection_edited', '',
                            studio_internal_getpagenameandparams(true, $loggingurl->out(false)),
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot updated (set)':
                    studio_internal_trigger_event($cm->id, 'set_edited', '',
                            studio_internal_getpagenameandparams(true, $loggingurl->out(false)),
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot being edited':
                case 'slot being edited (collection)':
                case 'slot being edited (set)':
                    break;
            }

            if ($collectionslotordering) {
                if (!empty($collectionsloteditslot)) {
                    $collectionitemid = key($collectionsloteditslot);
                    if ($collectionitemid) {
                        $url = new moodle_url('/mod/studio/slotedit.php',
                                array('id' => $id, 'sid' => $slotid, 'cid' => $collectionitemid));
                        return redirect($url->out(false));
                    }
                }

                if (!empty($collectionslotmovedn)) {
                    $collectionitemid = key($collectionslotmovedn);
                    if ($collectionitemid) {
                        $collectionitem = studio_api_collection_get_item($collectionitemid);
                        studio_api_collection_reorder_item($collectionitemid, false);
                    }
                }

                if (!empty($collectionslotmoveup)) {
                    $collectionitemid = key($collectionslotmoveup);
                    if ($collectionitemid) {
                        $collectionitem = studio_api_collection_get_item($collectionitemid);
                        studio_api_collection_reorder_item($collectionitemid, true);
                    }
                }

                if (!empty($collectionslotdeleteslot)) {
                    $collectionitemid = key($collectionslotdeleteslot);
                    if ($collectionitemid) {
                        studio_api_collection_delete_item($collectionitemid);
                    }
                }

                $url = new moodle_url('/mod/studio/slotedit.php', array('id' => $id, 'sid' => $slotid));
                return redirect($url->out(false) . '#collectionslotsheader');
            } else {
                if (isset($slotformdata->submitbutton) &&
                        ($slotformdata->submitbutton == get_string('slotformsaveandcollect', 'studio'))) {
                    studio_api_collection_harvest($cm->id, $slotid);
                }

                if ($type === STUDIO_CONTENTTYPE_SET_SLOT) {
                    $url = new moodle_url('/mod/studio/slot.php',
                            array('id' => $id, 'sid' => $slotid, 'ssid' => $setid));
                } else {
                    $url = new moodle_url('/mod/studio/slot.php',
                            array('id' => $id, 'sid' => $slotid));
                }
                return redirect($url->out(false));
            }
        }

        // Restore original form description field data so it can be displayed again.
        $slotformdata->description = $slotformdatadescription;

    } else {
        if (!isset($slotdata->attachments) && isset($slotdata->contenttype)) {
            // Check if the slot contains uploaded file, if so, then load
            // the uploaded file into the draft area for the moodle form.
            $context = context_module::instance($cm->id);
            $draftitemid = 0;
            switch ($slotdata->contenttype) {
                case STUDIO_CONTENTTYPE_IMAGE:
                case STUDIO_CONTENTTYPE_VIDEO:
                case STUDIO_CONTENTTYPE_AUDIO:
                case STUDIO_CONTENTTYPE_DOCUMENT:
                    $fs = get_file_storage();
                    // If we've got notebook files, handle them here and break. If not, fall through to below.
                    $notebookfiles = $fs->get_area_files($context->id, 'mod_studio', 'notebook', $slotdata->fileid);
                    if (!empty($notebookfiles)) {
                        file_prepare_draft_area($draftitemid, $context->id, 'mod_studio', 'notebook', $slotdata->fileid);
                        // We only want the original zip in the draft area, not the contents as well.
                        $usercontext = context_user::instance($USER->id);
                        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid);
                        foreach ($draftfiles as $draftfile) {
                            $mimetype = $draftfile->get_mimetype();
                            if ($mimetype && $mimetype != 'application/x-smarttech-notebook') {
                                $draftfile->delete();
                            }
                        }
                        $slotdata->attachments = $draftitemid;
                        $slotdata->checksum = studio_internal_calculate_file_hash($draftitemid);
                        break; // Only if this is a notebook.
                    }
                case STUDIO_CONTENTTYPE_PRESENTATION:
                case STUDIO_CONTENTTYPE_SPREADSHEET:
                    file_prepare_draft_area(
                            $draftitemid, $context->id, 'mod_studio', 'slot', $slotdata->fileid);
                    $slotdata->attachments = $draftitemid;
                    $slotdata->checksum = studio_internal_calculate_file_hash($draftitemid);

                    break;
            }
        }

        if ($slotdata->sid > 0) {
            // If slot uses rich text editor, then we change the data structure for the description field.
            if ($permissions->feature_slottextuseshtml || ((int) $slotdata->textformat === 1)) {
                $slotdatadescription = array('text' => $slotdata->description, 'format' => 1);
                $slotdata->description = $slotdatadescription;
                $slotdata->textformat = 1;
            }

            if (($permissions->feature_slotcommentuseshtml || ((int) $slotdata->commentformat === 1))) {
                $slotdata->commentformat = 1;
            }

            if ($setid && $setslotdata) {
                if ($setslotdata->provenanceid != null && $setslotdata->provenancestatus == STUDIO_SET_SLOT_PROVENANCE_EDITED) {
                    if (!empty($setslotdata->setslotname)) {
                        $slotdata->name = $setslotdata->setslotname;
                    }
                    if (!empty($setslotdata->setslotdescription)) {
                        $slotdata->description = $setslotdata->setslotdescription;
                    }
                }
            }

            $slotupdatemode = 'slot being edited';
        } else {
            $slotupdatemode = 'new slot';
        }
        if ($slotdata->slottype == STUDIO_CONTENTTYPE_COLLECTION) {
            $slotupdatemode .= ' (collection)';
        }
        if ($slotdata->slottype == STUDIO_CONTENTTYPE_SET) {
            $slotupdatemode .= ' (set)';
        }

        $slotform->set_data($slotdata);

        if (($slotupdatemode == 'new slot') && ($slotdata->levelid <= 0)) {
            // Dont log if it's a new pinboard slot.
            // We dont because there is no sensible URL we can log in such case.
            // Line below to keep Moodle codechecker happy.
            $dummy = 0;
        } else {
            // Log page action.
            switch ($slotupdatemode) {
                case 'slot created':
                    $slotactionevent = 'slot_created';
                    if ($slotdata->visibility == STUDIO_VISIBILITY_INSETONLY) {
                        $slotactionevent = 'set_slot_created';
                    }
                    studio_internal_trigger_event($cm->id, $slotactionevent, '',
                            studio_internal_getpagenameandparams() . "&userid={$userid}",
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot created (collection)':
                    studio_internal_trigger_event($cm->id, 'collection_created', '',
                            studio_internal_getpagenameandparams() . "&userid={$userid}",
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot created (set)':
                    studio_internal_trigger_event($cm->id, 'set_created', '',
                            studio_internal_getpagenameandparams() . "&userid={$userid}",
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot updated':
                    studio_internal_trigger_event($cm->id, 'slot_edited', '',
                            studio_internal_getpagenameandparams() . "&userid={$userid}",
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot updated (collection)':
                    studio_internal_trigger_event($cm->id, 'collection_edited', '',
                            studio_internal_getpagenameandparams() . "&userid={$userid}",
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot updated (set)':
                    studio_internal_trigger_event($cm->id, 'set_edited', '',
                            studio_internal_getpagenameandparams() . "&userid={$userid}",
                            studio_internal_formatloginfo($slotdataname));
                    break;

                case 'slot being edited':
                case 'slot being edited (collection)':
                case 'slot being edited (set)':
                    break;
            }
        }
    }

}

ob_start();
$slotform->display();
$slothtmlform = ob_get_contents();
ob_end_clean();

// Generate HTML.
$renderer = $PAGE->get_renderer('mod_studio');
$htmltemp1 = $renderer->studio_render_siteheader(
        $cm->id, $permissions, $theme, $cminstance->sitename, '', $vid);
$htmltemp1 .= $renderer->studio_render_navigation(
        $cm->id, $permissions, $theme, 'work', 2, $pageview);
$htmltemp1 .= $renderer->studio_render_profile_bar(
        $course->id, $cm->id, $cminstance->id, $userrecord, $permissions);
$htmltemp1 .= $renderer->studio_render_blank_bar_top();
$htmltemp1 .= $renderer->studio_render_grey_bar();
$htmltemp1 .= $renderer->studio_render_blank_bar_bottom();
$htmltemp = $renderer->studio_render_header_wrapper($htmltemp1);
if ($permissions->feature_enablesets &&
        (($type === STUDIO_CONTENTTYPE_SET) || ($type === STUDIO_CONTENTTYPE_SET_SLOT))) {
    $htmltemp1 = $renderer->studio_render_slot_edit($slothtmlform);
    if ($type === STUDIO_CONTENTTYPE_SET) {
        // New set.
        $htmltemp .= $renderer->studio_render_set_view($htmltemp1, $permissions, $cm->id, $slotdata->sid,
                                                       0, 0, 0,
                (isset($slotdata->id) && ($slotdata->id > 0) ? (($setid == $sid) ? 4 : 5) : 6),
                (isset($slotdata) ? studio_internal_get_slot_name($slotdata, false) : ''), '',
                                                       $lid > 0 ? true : false, $lid);
    } else {
        // Existing set.
        $setview = (isset($slotdata->id) && ($slotdata->id > 0)) ? (($setid == $sid) ? 4 : 7) : 8;
        if ($setview == 8) {
            $htmltemp1 = $renderer->studio_render_set_addbrowse_tabs($cm->id, $setid,
                    $setview, $lid, $settemplateslotid) . $htmltemp1;
        }
        $htmltemp .= $renderer->studio_render_set_view($htmltemp1, $permissions, $cm->id, $setid,
                (isset($slotdata->id) ? $slotdata->id : 0),
                $settemplateslotid, 0, $setview,
                (isset($setdata) ? studio_internal_get_slot_name($setdata, false) : ''),
                (isset($slotdata->name) ? $slotdata->name : ''), $lid > 0 ? true : false, $lid);
    }
} else {
    $htmltemp .= $renderer->studio_render_slot_edit($slothtmlform);
}
$html = $renderer->studio_render_wrapper($htmltemp, $viewmode);

// Output HTML
echo $renderer->studio_render_header(); // Header.
echo $html;
echo $renderer->footer(); // Footer.
