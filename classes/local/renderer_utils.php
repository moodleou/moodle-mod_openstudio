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
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local;

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\stream;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\api\embedcode;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\api\item;
use mod_openstudio\local\util\defaults;
use mod_openstudio\local\api\user;
use mod_openstudio\local\api\comments;
use mod_openstudio\local\forms\comment_form;
use mod_openstudio\local\api\template;
use mod_openstudio\local\uti;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

/**
 * Open Studio renderer utils.
 *
 * Static Utility methods to support the Open Studio module.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer_utils {

    /**
     * This functions generate the urls data related to the navigation
     * to be rendered.
     *
     * @param int $cmid Course module id.
     * @return object urls used for navigation of Open Studio.
     */
    public static function navigation_urls($cmid) {
        global $SESSION;

        $navigationurls = (object) array();

        if (isset($SESSION->openstudio_view_filters)
            && isset($SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE])
            && isset($SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE]->ftype)) {
            $navigationurls->myworkurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_PRIVATE,
                            'fblock' => false,
                            'ftype' => $SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE]->ftype,
                            'fflag' => $SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE]->fflag,
                            'ftags' => $SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE]->ftags,
                            'fsort' => $SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE]->fsort,
                            'osort' => $SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE]->osort));
        } else {
            $navigationurls->myworkurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_PRIVATE, 'fblock' => false,
                            'ftype' => 0, 'fflag' => 0,
                            'fsort' => stream::SORT_BY_ACTIVITYTITLE, 'osort' => 1));
        }

        if (isset($SESSION->openstudio_view_filters)
            && isset($SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE_PINBOARD])
            && isset($SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE_PINBOARD]->ftype)) {
            $navigationurls->pinboardurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1,
                            'ftype' => $SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE_PINBOARD]->ftype,
                            'fflag' => $SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE_PINBOARD]->fflag,
                            'ftags' => $SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE_PINBOARD]->ftags,
                            'fsort' => $SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE_PINBOARD]->fsort,
                            'osort' => $SESSION->openstudio_view_filters[content::VISIBILITY_PRIVATE_PINBOARD]->osort));
        } else {
            $navigationurls->pinboardurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1,
                            'ftype' => 0, 'fflag' => 0,
                            'ftags' => '', 'fsort' => stream::SORT_BY_DATE, 'osort' => 0));
        }

        if (isset($SESSION->openstudio_view_filters)
            && isset($SESSION->openstudio_view_filters[content::VISIBILITY_GROUP])
            && isset($SESSION->openstudio_view_filters[content::VISIBILITY_GROUP]->fblock)) {
            $navigationurls->mygroupurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_GROUP,
                            'fblock' => $SESSION->openstudio_view_filters[content::VISIBILITY_GROUP]->fblock,
                            'ftype' => $SESSION->openstudio_view_filters[content::VISIBILITY_GROUP]->ftype,
                            'fflag' => $SESSION->openstudio_view_filters[content::VISIBILITY_GROUP]->fflag,
                            'ftags' => $SESSION->openstudio_view_filters[content::VISIBILITY_GROUP]->ftags,
                            'fsort' => $SESSION->openstudio_view_filters[content::VISIBILITY_GROUP]->fsort,
                            'osort' => $SESSION->openstudio_view_filters[content::VISIBILITY_GROUP]->osort,
                            'groupid' => $SESSION->openstudio_view_filters[content::VISIBILITY_GROUP]->groupid));
        } else {
            $navigationurls->mygroupurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_GROUP, 'fblock' => 0,
                            'ftype' => 0, 'fflag' => 0,
                            'ftags' => '', 'fsort' => stream::SORT_BY_DATE, 'osort' => 0));
        }

        if (isset($SESSION->openstudio_view_filters)
            && isset($SESSION->openstudio_view_filters[content::VISIBILITY_MODULE])
            && isset($SESSION->openstudio_view_filters[content::VISIBILITY_MODULE]->fblock)) {
            $navigationurls->mymoduleurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_MODULE,
                            'fblock' => $SESSION->openstudio_view_filters[content::VISIBILITY_MODULE]->fblock,
                            'ftype' => $SESSION->openstudio_view_filters[content::VISIBILITY_MODULE]->ftype,
                            'fflag' => $SESSION->openstudio_view_filters[content::VISIBILITY_MODULE]->fflag,
                            'ftags' => $SESSION->openstudio_view_filters[content::VISIBILITY_MODULE]->ftags,
                            'fsort' => $SESSION->openstudio_view_filters[content::VISIBILITY_MODULE]->fsort,
                            'osort' => $SESSION->openstudio_view_filters[content::VISIBILITY_MODULE]->osort));
        } else {
            $navigationurls->mymoduleurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_MODULE, 'fblock' => 0,
                            'ftype' => 0, 'fflag' => 0,
                            'ftags' => '', 'fsort' => stream::SORT_BY_DATE, 'osort' => 0));
        }

        $navigationurls->listpeopleurl = new \moodle_url('/mod/openstudio/people.php', array('id' => $cmid));
        $navigationurls->peoplegroupurl = new \moodle_url('/mod/openstudio/people.php',
                array('id' => $cmid, 'vid' => content::VISIBILITY_GROUP));
        $navigationurls->peoplemoduleurl = new \moodle_url('/mod/openstudio/people.php',
                array('id' => $cmid, 'vid' => content::VISIBILITY_MODULE));

        $navigationurls->myworkurl = $navigationurls->myworkurl->out(false);
        $navigationurls->pinboardurl = $navigationurls->pinboardurl->out(false);
        $navigationurls->mymoduleurl = $navigationurls->mymoduleurl->out(false);
        $navigationurls->peoplemoduleurl = $navigationurls->peoplemoduleurl->out(false);
        $navigationurls->mygroupurl = $navigationurls->mygroupurl->out(false);
        $navigationurls->peoplegroupurl = $navigationurls->peoplegroupurl->out(false);

        return $navigationurls;

    }

    /**
     * This function generate variables for profile bar of Open Studio.
     *
     * @param object $permissions The permission object for the given user/view.
     * @param int $openstudioid The open studio id.
     * @param object $contentdata The content records to display.
     * @return object $contentdata
     */
    public static function profile_bar($permissions, $openstudioid, $contentdata) {
        global $USER, $OUTPUT, $PAGE;

        $vuid = optional_param('vuid', $USER->id, PARAM_INT);
        $flagscontentread = 0;
        $ismyprofile = true;

        if ($vuid && $vuid != $USER->id) {
            $contentowner = api\user::get_user_by_id($vuid);
            $ismyprofile = false;
        } else {
            $contentowner = $USER;
        }

        $userprogressdata = api\user::get_activity_status($openstudioid, $contentowner->id);
        $activedate = $userprogressdata['lastactivedate'] > 0 ? date('j/m/y h:i', $userprogressdata['lastactivedate']) : null;
        $flagsdata = flags::count_by_user($openstudioid, $contentowner->id);

        if (array_key_exists(flags::READ_CONTENT, $flagsdata)) {
            $flagscontentread = $flagsdata[flags::READ_CONTENT]->count;
        }

        $contentdata->percentcompleted = 0;
        $contentdata->showprofileactivities = $userprogressdata['totalslots'] > 0;
        if ($contentdata->showprofileactivities) {
            $userprogresspercentage = ceil(($userprogressdata['filledslots'] / $userprogressdata['totalslots']) * 100);
            $contentdata->percentcompleted = $userprogresspercentage;
        }

        $contentdata->userprofileid = $vuid;
        $contentdata->ismyprofile = $ismyprofile;
        $contentdata->fullusername = $contentowner->firstname.' '.$contentowner->lastname;
        $contentdata->activedate = $activedate;
        $contentdata->flagscontentread = $flagscontentread;
        $contentdata->totalpostedcomments = $userprogressdata['totalpostedcomments'];
        $contentdata->userpictureurl = new \moodle_url('/user/pix.php/'.$contentowner->id.'/f1.jpg');
        $contentdata->viewuserworkurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $openstudioid, 'vuid' => $contentowner->id, 'vid' => content::VISIBILITY_PRIVATE));
        $contentdata->viewedicon = $OUTPUT->pix_url('viewed_rgb_32px', 'openstudio');
        $contentdata->commentsicon = $OUTPUT->pix_url('comments_rgb_32px', 'openstudio');
        $contentdata->participationenable = ($permissions->feature_participationsmiley &&
                $userprogressdata['totalslots'] > 0);
        $contentdata->participationlow = isset($userprogressdata['participationstatus'])
                && ($userprogressdata['participationstatus'] == 'low');

        if (isset($userprogressdata['progressdetail']) && $userprogressdata['progressdetail']) {
            $profileactivityitems = [];

            if (!empty($userprogressdata['progressdetail'])) {
                $contentdata->unlockactivityenable = $permissions->canlockothers ||  $permissions->managecontent;
                foreach ($userprogressdata['progressdetail'] as $values) {
                    foreach ($values as $level2id => $activities) {
                        $activityname = '';
                        foreach ($activities as $key => $activity) {
                            $activityname = $activity->level2name;

                            $activities[$key]->isactive = false;
                            if ($activities[$key]->slotcontenttype2 == content::TYPE_FOLDER) {
                                $activities[$key]->activityediturl = new \moodle_url('/mod/openstudio/contentedit.php',
                                        array('id' => $contentdata->cmid, 'sid' => 0, 'lid' => $activity->level3id,
                                                'ssid' => 0, 'type' => content::TYPE_FOLDER_CONTENT));

                                if ($activity->id) {
                                    $activities[$key]->isactive = true;
                                    $activities[$key]->activityediturl = new \moodle_url('/mod/openstudio/folder.php',
                                            array('id' => $contentdata->cmid, 'sid' => $activity->id,
                                                    'vuid' => $contentowner->id, 'lid' => $activity->level3id));
                                } else {
                                    $lockdata = self::content_lock_data((object) array('l3id' => $activities[$key]->level3id));
                                    $activities[$key]->contentislocked = $lockdata->contentislock;
                                }
                            } else {
                                $activities[$key]->activityediturl = new \moodle_url('/mod/openstudio/contentedit.php',
                                        array('id' => $contentdata->cmid, 'sid' => 0, 'lid' => $activity->level3id));

                                if ($activity->id) {
                                    if ($activity->slotcontenttype != content::TYPE_NONE) {
                                        $activities[$key]->isactive = true;
                                    }
                                    $activities[$key]->activityediturl = new \moodle_url('/mod/openstudio/content.php',
                                            array('id' => $contentdata->cmid, 'sid' => $activity->id, 'vuid' => $contentowner->id));
                                } else {
                                    $lockdata = self::content_lock_data((object) array(
                                            'l3id' => $activities[$key]->level3id));
                                    $activities[$key]->contentislocked = $lockdata->contentislock;
                                }
                            }

                            $activities[$key]->activitytitle = implode(" - ", array($activity->level1name,
                                $activity->level2name, $activity->level3name));
                        }

                        if (!empty($activities)) {
                            $profileactivityitems[$level2id] = (object) [
                                    'activityname' => $activityname,
                                    'activities' => array_values($activities)
                            ];
                        }
                    }
                }

                // Returns all the values from the array and indexes the array numerically.
                // We need this because mustache requires it.
                $contentdata->profileactivities = array_values($profileactivityitems);

                // Javascript module to handle activity lock.
                if ($contentdata->unlockactivityenable) {
                    $PAGE->requires->strings_for_js(
                            array('contentactionunlockname', 'modulejsdialogcancel', 'modulejsdialogcontentunlock'),
                            'mod_openstudio');

                    $PAGE->requires->js_call_amd('mod_openstudio/lockactivity', 'init', [[
                            'cmid' => $contentdata->cmid]]);
                }
            }
        }
        return $contentdata;
    }

    /**
     * This function generate variables for a content.
     *
     * @param int $cmid Course module id.
     * @param object $permissions The permission object for the given user/view.
     * @param object $contentdata The content records to display.
     * @param boolean $iscontentversion Indicate if content is a content version record.
     * @return object $contentdata
     */
    public static function content_details($cmid, $permissions, $contentdata, $iscontentversion) {
        global $OUTPUT, $CFG;

        if ($iscontentversion) {
            $contentarea = 'contentversion';
            $contentthumbnailarea = 'contentthumbnailversion';
        } else {
            $contentarea = 'content';
            $contentthumbnailarea = 'contentthumbnail';
        }

        $contenttypenone = false;
        $contenttypeimage = false;
        $contenttypemedia = false;
        $contenttypefileurl = false;
        $contenttypeembed = false;
        $contenttypedownloadfile = false;
        $contenttypeiframe = false;
        $contenttypeuseimagedefault = false;
        $contentdatahtml = '';
        $contentfileurl = '';
        $contentthumbnailfileurl = '';
        $contenttypeiconurl = '';
        $contentdatatitle = '';
        $contentiframesrc = '';
        $context = \context_module::instance($cmid);

        if (!empty($contentdata->folderid)) {
            $folderid = $contentdata->folderid;
        } else {
            $folderid = null;
        }

        // Get content file url.
        switch ($contentdata->contenttype) {
            case content::TYPE_NONE:
                if (!$iscontentversion && $contentdata->isownedbyviewer) {
                    $contentislock = false;
                    if ($permissions->feature_enablelock) {
                        $lockdata = self::content_lock_data($contentdata, $permissions);
                        $contentislock = $lockdata->contentislock;
                    }
                    if ($contentislock === false) {
                        $contenttypenone = true;
                        $contentfileurl = new \moodle_url('/mod/openstudio/contentedit.php',
                                array('id' => $cmid, 'sid' => $contentdata->id));
                    }
                }
                break;
            case content::TYPE_IMAGE:
                $contenttypeimage = true;

                // Add folder id to thumbnail url.
                // A post with visibility is Only me, thmbnail doesn't load although folder shared.
                $contentfileurl = self::make_plugin_file($context->id, $contentarea, $contentdata->id,
                        $contentdata->content, $folderid);

                break;

            case content::TYPE_VIDEO:
            case content::TYPE_AUDIO:
                $contenttypemedia = true;
                $contenttypedownloadfile = true;
                $contentfileurl = self::make_plugin_file($context->id, $contentarea, $contentdata->id,
                        $contentdata->content, $folderid);

                // This used for media filter.
                $contentdatahtml = \html_writer::start_tag('a',
                    array('href' => $contentfileurl, 'target' => '_top'));
                $contentdatahtml .= \html_writer::end_tag('a');
                $contentdatahtml = format_text($contentdatahtml);
                break;
            case content::TYPE_DOCUMENT:
                $contenttypedownloadfile = true;
                /*
                 * Note, this block of code utilises a case statement fallthrough.
                 * If the content has files in the notebook file area, we render it
                 * as a notebook, and break out of the case statement here.
                 * If not, we carry on treating this as a regular document slot.
                 */
                $fs = get_file_storage();
                if ($contentfiles = $fs->get_area_files($context->id, 'mod_openstudio', 'notebook', $contentdata->fileid)) {
                    if ($contentarea == 'contentversion') {
                        $contentarea = 'notebookversion';
                    } else {
                        $contentarea = 'notebook';
                    }
                    $contenttypeiframe = true;
                    foreach ($contentfiles as $contentfile) {
                        $filename = $contentfile->get_filename();
                        if ($filename != '.') {
                            $extension = pathinfo($filename, PATHINFO_EXTENSION);
                            $contentfileurls[$extension] = self::make_plugin_file($context->id, $contentarea,
                                    $contentdata->id, $filename, $folderid);
                        }
                    }
                    $contentiframesrc = $contentfileurls['html'];
                    $contentfileurl = $contentfileurls['ipynb'] . '?forcedownload=true';
                    break; // Only if this is a notebook.
                }
            case content::TYPE_PRESENTATION:
            case content::TYPE_SPREADSHEET:
            case content::TYPE_CAD:
            case content::TYPE_ZIP:
                $contenttypedownloadfile = true;
                $contentfileurl = self::make_plugin_file($context->id, $contentarea, $contentdata->id,
                        $contentdata->content, $folderid);

                break;
            case content::TYPE_URL:
            case content::TYPE_URL_DOCUMENT:
            case content::TYPE_URL_DOCUMENT_DOC:
            case content::TYPE_URL_DOCUMENT_PDF:
            case content::TYPE_URL_IMAGE:
            case content::TYPE_URL_PRESENTATION:
            case content::TYPE_URL_PRESENTATION_PPT:
            case content::TYPE_URL_SPREADSHEET:
            case content::TYPE_URL_SPREADSHEET_XLS:
            case content::TYPE_URL_VIDEO:
            case content::TYPE_URL_AUDIO:
                $contentfileurl = $contentdata->content;
                $embeddata = isset($contentdata->weblink) ? embedcode::parse(embedcode::get_ouembed_api(),
                        $contentdata->weblink) : false;
                if ($embeddata === false) {
                    $contenttypefileurl = true;
                    if ($contentdata->contenttype == content::TYPE_URL_IMAGE) {
                        $contenttypeimage = true;
                        $contentdatatitle = get_string('contentcontentweblinkimageuntitled', 'openstudio');

                        if (trim($contentdata->urltitle) != '') {
                            $contentdatatitle = $contentdata->urltitle;
                        }
                        if ($contentdatatitle == '') {
                            $contentdatatitle = $contentdata->name;
                        }
                        // Skip the rest of the case code.
                        break;
                    } else if (($contentdata->contenttype == content::TYPE_URL_VIDEO) ||
                        ($contentdata->contenttype == content::TYPE_URL_AUDIO)) {
                            $contenttypemedia = true;

                            // Skip the rest of the case code.
                        break;
                    } else if (($contentdata->contenttype == content::TYPE_URL) ||
                        ($contentdata->contenttype == content::TYPE_URL_DOCUMENT_PDF)) {
                            $contenttypeiframe = true;
                            $contenttypefileurl = true;

                            $contentfileurl = $contentiframesrc = $contentdata->content;

                            // Skip the rest of the case code.
                        break;
                    }
                } else {
                    $contenttypeembed = true;
                    $contentdatahtml = isset($embeddata->html) ? $embeddata->html : '';
                }

                break;
            default:
                break;
        }

        // Get content type icon.
        if (trim($contentdata->thumbnail) != '') {
            $contenttypeiconurl = $contentdata->thumbnail;
        } else {
            switch ($contentdata->contenttype) {
                case content::TYPE_NONE:
                        $contenttypeuseimagedefault = false;
                        $contenttypeiconurl = $OUTPUT->pix_url('unknown_rgb_32px', 'openstudio');
                    break;
                case content::TYPE_IMAGE:
                    if ($contentdata->mimetype == 'image/bmp') {
                        $contenttypeiconurl = $OUTPUT->pix_url('image_rgb_32px', 'openstudio');
                    } else {
                        $contentfileurl = self::make_plugin_file($context->id, $contentthumbnailarea, $contentdata->id,
                                $contentdata->content, $folderid);
                    }
                    break;

                case content::TYPE_VIDEO:
                    $contenttypeiconurl = $OUTPUT->pix_url('video_rgb_32px', 'openstudio');
                    break;
                case content::TYPE_AUDIO:
                    if ($contentdata->mimetype == 'audio/x-aiff') {
                        $contenttypeuseimagedefault = true;
                    }
                    $contenttypeiconurl = $OUTPUT->pix_url('audio_rgb_32px', 'openstudio');
                    break;
                case content::TYPE_DOCUMENT:
                case content::TYPE_URL_DOCUMENT:
                case content::TYPE_URL_DOCUMENT_DOC:
                    $contenttypeuseimagedefault = true;
                    switch ($contentdata->mimetype) {
                        case 'application/msword':
                        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                            $contenttypeiconurl = $OUTPUT->pix_url('word_rgb_32px', 'openstudio');
                            break;
                        case 'application/pdf':
                            $contenttypeiconurl = $OUTPUT->pix_url('pdf_rgb_32px', 'openstudio');
                            break;
                        default:
                            $contenttypeiconurl = $OUTPUT->pix_url('document_rgb_32px', 'openstudio');
                            break;
                    }
                    break;
                case content::TYPE_URL_DOCUMENT_PDF:
                    $contenttypeuseimagedefault = true;
                    $contenttypeiconurl = $OUTPUT->pix_url('pdf_rgb_32px', 'openstudio');
                    break;
                case content::TYPE_PRESENTATION:
                case content::TYPE_URL_PRESENTATION:
                case content::TYPE_URL_PRESENTATION_PPT:
                    $contenttypeuseimagedefault = true;
                    $contenttypeiconurl = $OUTPUT->pix_url('powerpoint_rgb_32px', 'openstudio');
                    break;
                case content::TYPE_SPREADSHEET:
                case content::TYPE_URL_SPREADSHEET:
                case content::TYPE_URL_SPREADSHEET_XLS:
                    $contenttypeuseimagedefault = true;
                    $contenttypeiconurl = $OUTPUT->pix_url('excel_spreadsheet_rgb_32px', 'openstudio');
                    break;
                case content::TYPE_URL_IMAGE:
                    $contenttypeiconurl = $contentdata->content;
                    break;
                case content::TYPE_TEXT:
                case content::TYPE_URL:
                case content::TYPE_URL_VIDEO:
                case content::TYPE_URL_AUDIO:
                    if ((trim($contentdata->content) == '') &&
                            isset($contentdata->description) && (trim($contentdata->description) != '')) {
                        $contenttypeiconurl = $OUTPUT->pix_url('text_doc_rgb_32px', 'openstudio');
                    } else {
                        $contenttypeiconurl = $OUTPUT->pix_url('online_rgb_32px', 'openstudio');
                    }
                    break;
                case content::TYPE_CAD:
                    $contenttypeuseimagedefault = true;
                    $contenttypeiconurl = $OUTPUT->pix_url('cad_rgb_32px', 'openstudio');
                    break;
                case content::TYPE_ZIP:
                    $contenttypeuseimagedefault = true;
                    $contenttypeiconurl = $OUTPUT->pix_url('zip_archive_rgb_32px', 'openstudio');
                    break;
                default:
                    $contenttypeiconurl = $OUTPUT->pix_url('unknown_rgb_32px', 'openstudio');
                    break;
            }
        }

        $contentdata->contenttypenone = $contenttypenone;
        $contentdata->contenttypeimage = $contenttypeimage;
        $contentdata->contenttypemedia = $contenttypemedia;
        $contentdata->contenttypefileurl = $contenttypefileurl;
        $contentdata->contenttypeembed = $contenttypeembed;
        $contentdata->contenttypedownloadfile = $contenttypedownloadfile;
        $contentdata->contenttypeiframe = $contenttypeiframe;
        $contentdata->contentiframesrc = $contentiframesrc;
        $contentdata->contentdatahtml = $contentdatahtml;
        $contentdata->contenttypeiconurl = $contenttypeiconurl;
        $contentdata->contenttypeuseimagedefault = $contenttypeuseimagedefault;

        $contentdata->contentfileurl = $contentfileurl;
        $contentdata->contentthumbnailfileurl = $contentthumbnailfileurl;
        $contentdata->contentdatatitle = $contentdatatitle;
        $contentdata->contentdatadate = userdate($contentdata->timemodified, get_string('formattimedatetime', 'openstudio'));

        if (property_exists($contentdata, 'isfoldercontent') && $contentdata->isfoldercontent) {
            $folder = folder::get($contentdata->folderid);
            $contentdata->visibility = $folder->visibility;
        }
        $contentdata->contentvisibilityicon = self::content_visibility_icon($contentdata);

        if (property_exists($contentdata, 'folderid')) {
            $foldercontent = folder::get_content($contentdata->folderid, $contentdata->id);
            if ($foldercontent) {
                if ($foldercontent->provenanceid != null &&
                    $foldercontent->provenancestatus == folder::PROVENANCE_EDITED
                ) {
                    if (!empty($foldercontent->fcname)) {
                        $contentdata->contentdataname = $foldercontent->fcname;
                    }
                    if (!empty($foldercontent->fcdescription)) {
                        $contentdata->description = $foldercontent->fcdescription;
                    }
                }
            }
        }

        return $contentdata;
    }

    /**
     * This function generate visibility icon for a content.
     *
     * @param object $contentdata The content records to display.
     * @return object $contentdata
     */
    public static function content_visibility_icon($contentdata) {
        global $OUTPUT;

        $visibility = content::VISIBILITY_PRIVATE_PINBOARD;
        if (isset($contentdata->visibility)) {
            $visibility = (int)$contentdata->visibility;
            if ($visibility < 0) {
                $visibility = content::VISIBILITY_GROUP;
            }
        }

        switch ($visibility) {
            case content::VISIBILITY_MODULE:
                $contentvisibilityicon = $OUTPUT->pix_url('mymodule_rgb_32px', 'openstudio');
                break;
            case content::VISIBILITY_GROUP:
                $contentvisibilityicon = $OUTPUT->pix_url('share_with_my_group_rgb_32px', 'openstudio');
                break;
            case content::VISIBILITY_WORKSPACE:
            case content::VISIBILITY_PRIVATE:
            case content::VISIBILITY_PRIVATE_PINBOARD:
                $contentvisibilityicon = $OUTPUT->pix_url('onlyme_rgb_32px', 'openstudio');
                break;
            case content::VISIBILITY_TUTOR:
                $contentvisibilityicon = $OUTPUT->pix_url('share_with_tutor_rgb_32px', 'openstudio');
                break;
            default:
                $contentvisibilityicon = $OUTPUT->pix_url('onlyme_rgb_32px', 'openstudio');
                break;
        }

        return $contentvisibilityicon;
    }

    /**
     * This function will return lock data for a content.
     *
     * @param object $contentdata The content records to display.
     * @return object $contentdata
     */
    public static function content_lock_data($contentdata) {
        $contentislock = false;
        $contentislockmessage = '';
        if ($contentdata->l3id > 0) {

            // Check lock level management access.
            $contentleveldata = levels::get_record(3, $contentdata->l3id);

            if (isset($contentleveldata->locktype) &&
                (($contentleveldata->locktype == lock::ALL) ||
                    ($contentleveldata->locktype == lock::CRUD) ||
                    ($contentleveldata->locktype == lock::SOCIAL_CRUD) ||
                    ($contentleveldata->locktype == lock::COMMENT_CRUD))) {

                $contentlocktime = isset($contentleveldata->locktime) ? $contentleveldata->locktime : 0;
                $contentunlocktime = isset($contentleveldata->unlocktime) ? $contentleveldata->unlocktime : 0;
                $contentlocktimetext = '';
                $contentunlocktimetext = '';

                if ($contentlocktime > $contentunlocktime ) {
                    if (($contentunlocktime > 0) && (time() >= $contentunlocktime)) {
                        $contentislock = false;
                    }
                    if (($contentlocktime > 0) && (time() >= $contentlocktime)) {
                        $contentislock = true;
                    }
                } else {
                    if (($contentlocktime > 0) && (time() >= $contentlocktime)) {
                        $contentislock = true;
                    }
                    if (($contentunlocktime > 0) && (time() >= $contentunlocktime)) {
                        $contentislock = false;
                    }
                }

                if ($contentlocktime > 0) {
                    $contentlocktimetext = userdate($contentlocktime);
                }
                if ($contentunlocktime > 0) {
                    $contentunlocktimetext = userdate($contentunlocktime);
                }
                if (($contentlocktime > 0) && ($contentunlocktime <= 0)) {
                    if ($contentislock) {
                        $contentislockmessage = get_string('contentislockedfrom', 'openstudio',
                                array('from' => $contentlocktimetext));
                    } else {
                        $contentislockmessage = get_string('slotwillbelockedfrom', 'openstudio',
                                array('from' => $contentlocktimetext));
                    }
                } else if (($contentlocktime <= 0) && ($contentunlocktime > 0)) {
                    if ($contentislock) {
                        $contentislockmessage = get_string('contentislockedtill', 'openstudio',
                                array('till' => $contentunlocktimetext));
                    }
                } else if (($contentlocktime > 0) && ($contentunlocktime > 0)) {
                    if ($contentunlocktime < $contentlocktime) {
                        if ($contentislock) {
                            $contentislockmessage = get_string('contentislockedfrom', 'openstudio',
                                    array('from' => $contentlocktimetext));
                        } else {
                            $contentislockmessage = get_string('slotwillbelockedfrom', 'openstudio',
                                    array('from' => $contentlocktimetext));
                        }
                    } else {
                        if ($contentislock) {
                            $contentislockmessage = get_string('contentislockedfromtill', 'openstudio',
                                    array('from' => $contentlocktimetext, 'till' => $contentunlocktimetext));
                        } else {
                            $contentislockmessage = get_string('slotwillbelockedfromtill', 'openstudio',
                                    array('from' => $contentlocktimetext, 'till' => $contentunlocktimetext));
                        }
                    }
                }

            }
        }
        return (object) array('contentislock' => $contentislock, 'contentislockmessage' => $contentislockmessage);
    }


    /**
     * Renders a paging bar.
     * As design we should display 7 items for pagination. Example: Page: 1...4 5 6 7 8...20
     * maxdisplay: The maximum number of pagelinks to display.
     * It count number, text, and space (refer lib\outputcomponents.php).
     *
     * Example:
     * When current page 20, display 7 item maxdisplay item 17.
     * When current page in 19, display 7 item maxdisplay item 14.
     * When current page in 18, display 7 item maxdisplay item 11.
     * $totalpage = 20 , $page = 20, $maxdisplay = 20 - 3 * (20 - 20 + 1 ); $maxdisplay = 17;
     *
     * The calculation should be in general:
     * $maxdisplay = $totalpage - 3 * ($totalpage - $page + 1 );
     *
     * @param object $contentdata
     * @return object paging_bar
     */
    public static function openstudio_render_paging_bar ($contentdata) {
        $pb = new \paging_bar($contentdata->total, $contentdata->pagestart,
                    $contentdata->streamdatapagesize, $contentdata->pageurl);
        $page = optional_param('page', 0, PARAM_INT);
        if ($contentdata->streamdatapagesize > 0) {
            $totalpage = ceil($contentdata->total / $contentdata->streamdatapagesize);
            if ($totalpage <= 7) {
                  return $pb;
            }

            if ($page < 5) {
                  $pb->maxdisplay = 6;
            } else {
                  $maxdisplay = $totalpage - 3 * ($totalpage - $page + 1 );
                  $pb->maxdisplay = $maxdisplay > 5 ? $maxdisplay : 5;
            }
        }
        return $pb;
    }
    /**
     * This function will return media markup as oumedia filter input.
     *
     * @param object $file File record
     * @return string Media markup
     */
    public static function get_media_filter_markup($file) {
        $href = \moodle_url::make_pluginfile_url(
            $file->contextid,
            $file->component,
            $file->filearea,
            $file->itemid,
            $file->filepath,
            $file->filename)->out(false);
        $markup = \html_writer::link($href, '');
        return $markup;
    }

    /**
     * This function generate default icon for a content type.
     *
     * @param object $contentdata The content records to display.
     * @param object $context Moodle context object.
     * @param bool $iscontentversion Is content version
     * @return object $contentdata
     */
    public static function content_type_image($contentdata, $context, $iscontentversion = false) {
        global $CFG, $OUTPUT;

        if (!empty($contentdata->folderid)) {
            $folderid = $contentdata->folderid;
        } else {
            $folderid = null;
        }

        $contenttypedefaultimage = true;
        $contentthumbnailarea = $iscontentversion ? 'contentthumbnailversion' : 'contentthumbnail';
        switch ($contentdata->contenttype) {
            case content::TYPE_IMAGE:
                if ($contentdata->mimetype == 'image/bmp') {
                    $contenttypeimage = $OUTPUT->pix_url('unknown_rgb_32px', 'openstudio');
                } else {
                    if ($contentdata->content) {
                        $contenttypeimage = self::make_plugin_file($context->id, $contentthumbnailarea, $contentdata->id,
                                $contentdata->content, $folderid);
                        if ($contentdata->thumbnail) {
                            $contenttypeimage = $contentdata->thumbnail;
                        }

                        $contenttypedefaultimage = false;
                    }
                }
                break;
            case content::TYPE_VIDEO:
                $contenttypeimage = $OUTPUT->pix_url('video_rgb_32px', 'openstudio');
                break;
            case content::TYPE_AUDIO:
                $contenttypeimage = $OUTPUT->pix_url('audio_rgb_32px', 'openstudio');
                break;
            case content::TYPE_DOCUMENT:
            case content::TYPE_URL_DOCUMENT:
            case content::TYPE_URL_DOCUMENT_DOC:
                switch ($contentdata->mimetype) {
                    case 'application/msword':
                    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                        $contenttypeimage = $OUTPUT->pix_url('word_rgb_32px', 'openstudio');
                        break;
                    case 'application/pdf':
                        $contenttypeimage = $OUTPUT->pix_url('pdf_rgb_32px', 'openstudio');
                        break;
                    default:
                        $contenttypeimage = $OUTPUT->pix_url('document_rgb_32px', 'openstudio');
                        break;
                }
                break;
            case content::TYPE_URL_DOCUMENT_PDF:
                $contenttypeimage = $OUTPUT->pix_url('pdf_rgb_32px', 'openstudio');
                break;
            case content::TYPE_PRESENTATION:
            case content::TYPE_URL_PRESENTATION:
            case content::TYPE_URL_PRESENTATION_PPT:
                $contenttypeimage = $OUTPUT->pix_url('powerpoint_rgb_32px', 'openstudio');
                break;

            case content::TYPE_SPREADSHEET:
            case content::TYPE_URL_SPREADSHEET:
            case content::TYPE_URL_SPREADSHEET_XLS:
                $contenttypeimage = $OUTPUT->pix_url('excel_spreadsheet_rgb_32px', 'openstudio');
                break;
            case content::TYPE_TEXT:
            case content::TYPE_URL:
            case content::TYPE_URL_VIDEO:
            case content::TYPE_URL_AUDIO:
                $contenttypeimage = $OUTPUT->pix_url('online_rgb_32px', 'openstudio');
                break;
            case content::TYPE_CAD:
                $contenttypeimage = $OUTPUT->pix_url('cad_rgb_32px', 'openstudio');
                break;
            case content::TYPE_ZIP:
                $contenttypeimage = $OUTPUT->pix_url('zip_archive_rgb_32px', 'openstudio');
                break;
            default:
                $contenttypeimage = $OUTPUT->pix_url('unknown_rgb_32px', 'openstudio');
                break;
        }

        $contentdata->contenttypeimage = $contenttypeimage;
        $contentdata->contenttypedefaultimage = $contenttypedefaultimage;

        return $contentdata;

    }

    /**
     * This function generate variables for post types filter option of Open Studio.
     *
     * @param object $contentdata The content records to display.
     * @return object $contentdata
     */
    public static function filter_area($contentdata) {
        $filters = $contentdata->openstudio_view_filters;

        $area = array();
        $area[] = (object) [
            'checked' => $filters->fblock == stream::FILTER_AREA_ALL,
            'value' => stream::FILTER_AREA_ALL,
            'icon' => '',
            'label' => get_string('filterall', 'openstudio')
        ];

        // Will not include Pinboad when view activites page.
        if (!$contentdata->ismyactivity) {
            $area[] = (object)[
                'checked' => $filters->fblock == stream::FILTER_AREA_PINBOARD,
                'value' => stream::FILTER_AREA_PINBOARD,
                'icon' => '',
                'label' => get_string('filterpinboard', 'openstudio')
            ];
        }

        $area[] = (object) [
            'checked' => count($filters->fblockarray) > 0,
            'value' => stream::FILTER_AREA_ACTIVITY,
            'icon' => '',
            'label' => get_string('filterblocks', 'openstudio')
        ];

        $contentdata->filter_area_activity_value = stream::FILTER_AREA_ACTIVITY;
        $contentdata->area = $area;

        return $contentdata;
    }

    /**
     * This function generate variables for post types filter option of Open Studio.
     *
     * @param object $contentdata The content records to display.
     * @return object $contentdata
     */
    public static function filter_post_types($contentdata) {
        global $OUTPUT;

        $filters = $contentdata->openstudio_view_filters;

        $posttypes = array();
        $posttypes[] = (object) [
            'checked' => !$filters->ftype,
            'value' => 0,
            'icon' => '',
            'label' => get_string('filtertypesall', 'openstudio')
        ];

        $posttypes[] = (object) [
            'checked' => in_array(content::TYPE_IMAGE, $filters->ftypearray),
            'value' => content::TYPE_IMAGE,
            'icon' => $OUTPUT->pix_url('image_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filtertypesimage', 'openstudio')
        ];

        $posttypes[] = (object) [
            'checked' => in_array(content::TYPE_VIDEO, $filters->ftypearray),
            'value' => content::TYPE_VIDEO,
            'icon' => $OUTPUT->pix_url('video_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filtertypesvideo', 'openstudio')
        ];

        $posttypes[] = (object) [
            'checked' => in_array(content::TYPE_AUDIO, $filters->ftypearray),
            'value' => content::TYPE_AUDIO,
            'icon' => $OUTPUT->pix_url('audio_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filtertypesaudio', 'openstudio')
        ];

        $posttypes[] = (object) [
            'checked' => in_array(content::TYPE_DOCUMENT, $filters->ftypearray),
            'value' => content::TYPE_DOCUMENT,
            'icon' => $OUTPUT->pix_url('documents_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filtertypesdocuments', 'openstudio')
        ];

        $posttypes[] = (object) [
            'checked' => in_array(content::TYPE_PRESENTATION, $filters->ftypearray),
            'value' => content::TYPE_PRESENTATION,
            'icon' => $OUTPUT->pix_url('powerpoint_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filtertypespresentation', 'openstudio')
        ];

        $posttypes[] = (object) [
            'checked' => in_array(content::TYPE_SPREADSHEET, $filters->ftypearray),
            'value' => content::TYPE_SPREADSHEET,
            'icon' => $OUTPUT->pix_url('spreadsheet_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filtertypesspreadsheet', 'openstudio')
        ];

        $posttypes[] = (object) [
            'checked' => in_array(content::TYPE_URL, $filters->ftypearray),
            'value' => content::TYPE_URL,
            'icon' => $OUTPUT->pix_url('online_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filtertypesweblink', 'openstudio')
        ];

        $posttypes[] = (object) [
            'checked' => in_array(content::TYPE_FOLDER, $filters->ftypearray),
            'value' => content::TYPE_FOLDER,
            'icon' => $OUTPUT->pix_url('folder_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filtertypesfolder', 'openstudio')
        ];

        $contentdata->posttypes = $posttypes;

        return $contentdata;
    }

    /**
     * This function generate variables for user flags filter option of Open Studio.
     *
     * @param object $contentdata The content records to display.
     * @return object $contentdata
     */
    public static function filter_user_flags($contentdata) {
        global $OUTPUT;

        $filters = $contentdata->openstudio_view_filters;

        $userflags = array();
        $userflags[] = (object) [
            'checked' => !$filters->fflag,
            'value' => 0,
            'icon' => '',
            'label' => get_string('filterflagallcontents', 'openstudio')
        ];

        $userflags[] = (object) [
            'checked' => in_array(stream::FILTER_FAVOURITES, $filters->fflagarray),
            'value' => stream::FILTER_FAVOURITES,
            'icon' => $OUTPUT->pix_url('favourite_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterflagfavourite', 'openstudio')
        ];

        $userflags[] = (object) [
            'checked' => in_array(stream::FILTER_MOSTSMILES, $filters->fflagarray),
            'value' => stream::FILTER_MOSTSMILES,
            'icon' => $OUTPUT->pix_url('participation_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterflagsmile', 'openstudio')
        ];

        $userflags[] = (object) [
            'checked' => in_array(stream::FILTER_MOSTINSPIRATION, $filters->fflagarray),
            'value' => stream::FILTER_MOSTINSPIRATION,
            'icon' => $OUTPUT->pix_url('inspiration_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterflaginspiration', 'openstudio')
        ];

        $userflags[] = (object) [
            'checked' => in_array(stream::FILTER_HELPME, $filters->fflagarray),
            'value' => stream::FILTER_HELPME,
            'icon' => $OUTPUT->pix_url('request_feedback_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterflagfeedbackrequested', 'openstudio')
        ];

        $userflags[] = (object) [
            'checked' => in_array(stream::FILTER_COMMENTS, $filters->fflagarray),
            'value' => stream::FILTER_COMMENTS,
            'icon' => $OUTPUT->pix_url('comments_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterflagcomments', 'openstudio')
        ];

        $contentdata->userflags = $userflags;

        return $contentdata;
    }

    /**
     * This function generate variables for select status filter option of Open Studio.
     *
     * @param object $contentdata The content records to display.
     * @return object $contentdata
     */
    public static function filter_select_status($contentdata) {
        global $OUTPUT;

        $filters = $contentdata->openstudio_view_filters;
        $selectstatus = array();
        $selectstatus[] = (object) [
            'checked' => !$filters->fstatus,
            'value' => 0,
            'icon' => '',
            'label' => get_string('filterallpost', 'openstudio')
        ];

        $selectstatus[] = (object) [
            'checked' => $filters->fstatus == stream::FILTER_READ ,
            'value' => stream::FILTER_READ,
            'icon' => $OUTPUT->pix_url('viewed_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterflagviewed', 'openstudio')
        ];

        $selectstatus[] = (object) [
            'checked' => $filters->fstatus == stream::FILTER_NOTREAD,
            'value' => stream::FILTER_NOTREAD,
            'icon' => $OUTPUT->pix_url('not_viewed_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterflagnotviewed', 'openstudio')
        ];

        $selectstatus[] = (object) [
            'checked' => $filters->fstatus == stream::FILTER_EMPTYCONTENT,
            'value' => stream::FILTER_EMPTYCONTENT,
            'icon' => $OUTPUT->pix_url('empty_posts_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterflagemptycontents', 'openstudio')
        ];

        $selectstatus[] = (object) [
            'checked' => $filters->fstatus == stream::FILTER_LOCKED,
            'value' => stream::FILTER_LOCKED,
            'icon' => $OUTPUT->pix_url('lock_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterflaglocked', 'openstudio')
        ];

        $contentdata->selectstatus = $selectstatus;

        return $contentdata;
    }

    /**
     * This function generate variables for select from filter option of Open Studio.
     *
     * @param object $contentdata The content records to display.
     * @return object $contentdata
     */
    public static function filter_scope($contentdata) {
        global $OUTPUT;

        $filters = $contentdata->openstudio_view_filters;

        $selectfrom = array();
        $selectfrom[] = (object) [
            'checked' => $filters->fscope == stream::SCOPE_EVERYONE,
            'value' => stream::SCOPE_EVERYONE,
            'icon' => $OUTPUT->pix_url('everyone_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterscopeeveryone', 'openstudio')
        ];

        $selectfrom[] = (object) [
            'checked' => $filters->fscope == stream::SCOPE_MY,
            'value' => stream::SCOPE_MY,
            'icon' => $OUTPUT->pix_url('mine_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterscopemy', 'openstudio')
        ];

        $selectfrom[] = (object) [
            'checked' => $filters->fscope == stream::SCOPE_THEIRS,
            'value' => stream::SCOPE_THEIRS,
            'icon' => $OUTPUT->pix_url('theirs_filters_rgb_32px', 'openstudio'),
            'label' => get_string('filterscopetheirs', 'openstudio')
        ];

        $contentdata->selectfrom = $selectfrom;

        return $contentdata;
    }

    /**
     * This function will return flag data for a content.
     *
     * @param int $cmid The course module id.
     * @param object $permissions The permission object for the given user/view.
     * @param object $contentdata The content records to display.
     * @return object $contentdata
     */
    public static function content_flags($cmid, $permissions, $contentdata) {

        $flagtotals = flags::count_by_content($contentdata->id, $permissions->activeuserid);
        $flagstatus = flags::get_for_content_by_user($contentdata->id, $permissions->activeuserid);
        $flagfeedbackstatus = flags::get_for_content_by_user($contentdata->id, $contentdata->userid);

        $contentdata->contentflagfavourite = flags::FAVOURITE;
        $contentdata->contentflagsmile = flags::MADEMELAUGH;
        $contentdata->contentflaginspire = flags::INSPIREDME;
        $contentdata->contentflagrequestfeedback = flags::NEEDHELP;
        $contentdata->contentfavouritetotal = 0;
        $contentdata->contentsmiletotal = 0;
        $contentdata->contentinspiretotal = 0;
        $contentdata->contentrequestfeedbacktotal = 0;
        $contentdata->contentviewtotal = 0;
        $contentdata->contentflagfavouriteactive = false;
        $contentdata->contentflagsmileactive = false;
        $contentdata->contentflaginspireaction = false;
        $contentdata->contentflagrequestfeedbackaction = false;
        $contentdata->contentflagrequestfeedbackactive = false;

        if (array_key_exists(flags::FAVOURITE, $flagtotals)) {
            $contentdata->contentfavouritetotal = $flagtotals[flags::FAVOURITE]->count;
        }
        if (array_key_exists(flags::MADEMELAUGH, $flagtotals)) {
            $contentdata->contentsmiletotal = $flagtotals[flags::MADEMELAUGH]->count;
        }
        if (array_key_exists(flags::INSPIREDME, $flagtotals)) {
            $contentdata->contentinspiretotal = $flagtotals[flags::INSPIREDME]->count;
        }
        if (array_key_exists(flags::NEEDHELP, $flagtotals)) {
            $contentdata->contentrequestfeedbacktotal = $flagtotals[flags::NEEDHELP]->count;
        }
        if (array_key_exists(flags::READ_CONTENT, $flagtotals)) {
            // Note: we deduct the content owner's own view from the count.
            $contentdata->contentviewtotal = $flagtotals[flags::READ_CONTENT]->count - 1;
        }
        if (in_array(flags::FAVOURITE, $flagstatus)) {
            $contentdata->contentflagfavouriteactive = true;
        }
        if (in_array(flags::MADEMELAUGH, $flagstatus)) {
            $contentdata->contentflagsmileactive = true;
        }
        if (in_array(flags::INSPIREDME, $flagstatus)) {
            $contentdata->contentflaginspireactive = true;
        }
        if (in_array(flags::NEEDHELP, $flagfeedbackstatus)) {
            $contentdata->contentflagrequestfeedbackaction = 0;
            $contentdata->contentflagrequestfeedbackactive = true;
        }

        // Get copies count.
        $contenthash = item::generate_hash($contentdata->id);
        $contentdata->contentcopycount = item::count_occurences($contenthash, $cmid);
        $contentdata->contentcopyenable = $contentdata->contentcopycount > 1 ? true : false;

        // Check Request feedback permission.
        $contentdata->contentrequestfeedbackenable = $contentdata->isownedbyviewer;

        return $contentdata;
    }
    /**
     * This function will return content data for a folder.
     *
     * @param int $pinboardfolderlimit The pinboard folder limit.
     * @param int $openstudioid The openstudio id.
     * @param object $folderdata The folder records to display.
     * @return object $folderdata
     */
    public static function folder_content($pinboardfolderlimit, $folderdata) {
        global $OUTPUT;
        $context = \context_module::instance($folderdata->cmid);
        $context = \context_module::instance($folderdata->cmid);
        $contentdatatemp = self::get_all_folder_content($folderdata->id);

        $folderdata->total = count($contentdatatemp);

        // Get folder limit.
        $limitadd = self::get_limit_add_content_folder($pinboardfolderlimit,
                    $folderdata->id, $folderdata->levelid, $folderdata->total);
        $folderdata->additionlimit = $limitadd;
        foreach ($contentdatatemp as $content) {
            if (isset($content->foldertemplateid)) {
                $content->thumbnailimg = false;
                $contentthumbnailfileurl = $OUTPUT->pix_url('uploads_rgb_32px', 'openstudio');
                $content->contentthumbnailurl = $contentthumbnailfileurl;
                $contentdetail = new \moodle_url('/mod/openstudio/contentedit.php',
                            array('id' => $folderdata->cmid, 'sid' => 0, 'ssid' => $folderdata->id, 'type' => 110,
                                'sstsid' => $content->foldercontenttemplateid));
                $content->contentdetailurl = $contentdetail;
            } else {
                $content = self::content_type_image($content, $context);
                $content->thumbnailimg = true;
                $contentthumbnailfileurl = $content->contenttypeimage;
                if ($content->contenttype != content::TYPE_IMAGE) {
                    $content->thumbnailimg = false;
                }
                $contentdetail = new \moodle_url('/mod/openstudio/content.php', array(
                        'id' => $folderdata->cmid, 'sid' => $content->id, 'vuid' => $content->userid,
                        'folderid' => $folderdata->id));
                $content->contentdetailurl = $contentdetail;
                $content->contentthumbnailurl = $contentthumbnailfileurl;
                $content->datetimeupdated = $content->timemodified ? date('j/m/y h:i', $content->timemodified) : null;
            }
            $folderdata->contents[] = $content;
        }

        return $folderdata;
    }

     /**
      * This function will return folder limit add content
      *
      * @param int $limit The pinboardfolderlimit setting for the studio instance.
      * @param int $folderid Optional, the ID of the folder
      * @param int $levelid Optional, the ID of the level for the pre-definted folder
      * @return int The maximum number of contents allowed to be added
      */
    public static function get_limit_add_content_folder($pinboardfolderlimit, $folderid, $levelid, $contentexist) {
        $limitadd = 0;
        if ($levelid) {
            $limitadd = folder::get_addition_limit($pinboardfolderlimit, $folderid, $levelid);
        } else {
            $limitadd = defaults::MAXPINBOARDFOLDERSCONTENTS - $contentexist;
        }
        return $limitadd;
    }

    /**
     * This function will return all content of folder,
     *
     * @param int $folderid Optional, the ID of the folder
     * @return object $contentdatatemp
     */

    public static function get_all_folder_content($folderid) {
        $folderdata = content::get($folderid);
        if ($folderdata->levelid) {
            $contenttemplates = template::get_by_folderid($folderid);
            if ($contenttemplates) {
                $contenttemplates = template::get_contents($contenttemplates->id);
            } else {
                $contenttemplates = array();
            }
            $contentsbook = folder::get_contents_with_templates($folderid);
            $contentsbook = util::folder_content_add_permissions($contentsbook, $contenttemplates);

        } else {
            $contentsbook = folder::get_contents($folderid);
        }

        return $contentsbook;
    }

    /**
     * This function will return content for order post.
     *
     * @param int $cmid Course module ID
     * @return object $contentdatatemp Content of Folder
     * @return object ordercontent
     */

    public static function get_order_post_content($cmid, $contentdatatemp) {
        global $OUTPUT;
        $context = \context_module::instance($cmid);
        $total = count($contentdatatemp);
        $ordercontent = [];
        $orderpos = 1;
        foreach ($contentdatatemp as $key => $content) {
            $folderitem = new \stdClass;
            switch ($key) {
                case 0:
                    $isfirstcontent = true;
                    $islastcontent = false;
                    break;
                case ($total - 1):
                    $isfirstcontent = false;
                    $islastcontent = true;
                    break;
                default:
                    $isfirstcontent = false;
                    $islastcontent = false;
                    break;
            }
            if (!isset($content->canreorder)) {
                $content->canreorder = true;
            }
            $folderitem->firstcontent = $isfirstcontent;
            $folderitem->lastcontent = $islastcontent;
            $folderitem->canreorder = $content->canreorder;
            $folderitem->order = $orderpos;
            $orderpos++;
            $content->contentorder = $folderitem->order;
            $folderitem->contentsbook = false;
            if (isset($content->foldertemplateid)) {
                $contentthumbnailfileurl = $OUTPUT->pix_url('uploads_rgb_32px', 'openstudio');
                $folderitem->id = $content->id;
                $folderitem->name = $content->name;
                $folderitem->orderstring = str_pad($content->contentorder, 2, '0', STR_PAD_LEFT);
                $folderitem->pictureurl = (string) $contentthumbnailfileurl;
                $folderitem->contentsbook = true;
            } else {
                $content = self::content_type_image($content, $context);
                $contentthumbnailfileurl = $content->contenttypeimage;
                if ($content->contenttype != content::TYPE_IMAGE) {
                    $contentthumbnailfileurl = (string) $contentthumbnailfileurl;
                }
                $folderitem->id = $content->id;
                $folderitem->name = $content->name;
                $folderitem->date = $content->timemodified ? date('j/m/y h:i', $content->timemodified) : null;

                $folderitem->moveuporder = $content->contentorder - 1;
                $folderitem->movedownorder = $content->contentorder + 1;
                $folderitem->orderstring = str_pad($content->contentorder, 2, '0', STR_PAD_LEFT);
                $folderitem->pictureurl = $contentthumbnailfileurl;
            }
            $ordercontent [] = $folderitem;
        }
        return $ordercontent;
    }

    /**
     * Check locking status and set up data and include dependencies.
     *
     * @param $contentdata Object
     * @param $permissions Object
     * @param $cmid int Course module ID
     */
    public static function process_content_lock(&$contentdata, $permissions, $cmid) {
        global $PAGE;

        // Check lock permission.
        if (property_exists($contentdata, 'containingfolderlocktype')) {
            $contentdata->locktype = $contentdata->containingfolderlocktype;
        }

        $lockconst = array(
            'NONE' => lock::NONE,
            'ALL' => lock::ALL);

        // Check permission for processing lock.
        if ($contentdata->isownedbyviewer) {
            if ($contentdata->levelid) {
                // Activity content is only locked by teacher/manager.
                $contentlockenable = $permissions->managecontent;
            } else {
                // Only normal content is locked by owner who is student.
                $contentlockenable = $permissions->canlock;
            }
        } else {
            $contentlockenable = $permissions->canlockothers && $permissions->managecontent;
        }

        $contentdata->contentlockenable = $contentlockenable;

        $contentdata->contentcommentlocked = ($contentdata->locktype == lock::COMMENT
                || $contentdata->locktype == lock::ALL);
        $contentdata->contentflaglocked = ($contentdata->locktype == lock::SOCIAL
                || $contentdata->locktype == lock::ALL);
        $contentdata->contentcrudlocked = ($contentdata->locktype == lock::CRUD
                || $contentdata->locktype == lock::ALL);

        $contentdata->locked = $contentdata->contentcommentlocked || $contentdata->contentflaglocked
                || $contentdata->contentcrudlocked;

        if ($contentlockenable) {
            $PAGE->requires->strings_for_js(
                    array('contentactionunlockname', 'contentactionlockname'), 'mod_openstudio');

            $PAGE->requires->js_call_amd('mod_openstudio/lock', 'init', [[
                    'cmid' => $cmid,
                    'cid' => $contentdata->id,
                    'isfolder' => $contentdata->contenttype == content::TYPE_FOLDER,
                    'CONST' => $lockconst]]);
        }
    }

    /**
     * Check delete capability and set up data and include dependencies.
     *
     * @param $contentdata Object
     * @param $permissions Object
     * @param $cmid int Course module ID
     * @return bool
     */
    public static function process_content_delete(&$contentdata, $permissions, $cmid) {
        global $PAGE;

        $deleteenable = false;
        if (($contentdata->isownedbyviewer || $permissions->managecontent)) {
            if (($contentdata->l1id > 0) || ($contentdata->l1id == 0) || $permissions->managecontent) {
                if (lock::content_show_crud($contentdata, $permissions)
                    || $permissions->managecontent) {
                    $deleteenable = true;
                }
            }
        }
        $contentdata->contentdeleteenable = $deleteenable;

        if ($deleteenable) {
            $isfolder = $contentdata->contenttype == content::TYPE_FOLDER;

            // Require strings for js.
            if ($isfolder) {
                $PAGE->requires->strings_for_js(
                    array('folderdeletedfolder', 'deleteconfirmfolder', 'modulejsdialogcancel', 'deletelevel'),
                    'mod_openstudio');
            } else {
                $PAGE->requires->strings_for_js(
                    array('contentdeledialogueteheader', 'deleteconfirmcontent', 'modulejsdialogcancel', 'deletelevel'),
                    'mod_openstudio');
            }

            $PAGE->requires->js_call_amd('mod_openstudio/delete', 'init', [[
                'id' => $cmid,
                'cid' => $contentdata->id,
                'folderid' => property_exists($contentdata, 'folderid') ? $contentdata->folderid : null,
                'isfolder' => $isfolder,
                'isactivitycontent' => ($contentdata->levelid > 0)]]);
        }

        return $deleteenable;
    }

    /**
     * Check comment capability and set up data and include dependencies.
     *
     * @param $contentdata Object
     * @param $permissions Object
     * @param $cmid int Course module ID
     * @param $cminstance object Course module instance
     */
    public static function process_content_comment(&$contentdata, $permissions, $cmid, $cminstance) {
        global $PAGE, $CFG, $USER;

        // Check comment permission.
        $contentdata->contentcommentenable = $permissions->addcomment ? true : false;

        if ($contentdata->contentcommentenable) {

            // Add form after page_setup.
            $commentform = new comment_form(null, array(
                'id' => $cmid,
                'cid' => $contentdata->id,
                'max_bytes' => $cminstance->contentmaxbytes,
                'attachmentenable' => $permissions->feature_contentcommentusesaudio));
            $contentdata->commentform = $commentform->render();

            // Get content comments in order.
            $commenttemp = comments::get_for_content($contentdata->id, $USER->id);
            $comments = [];
            $commentthreads = [];
            $contentdata->comments = [];
            $pageurl = util::get_current_url();

            if ($commenttemp) {
                foreach ($commenttemp as $key => $comment) {

                    // Check comment attachment.
                    if ($file = comments::get_attachment($comment->id)) {
                        $comment->commenttext .= self::get_media_filter_markup($file);
                    }

                    // Filter comment text.
                    $comment->commenttext = format_text($comment->commenttext);

                    $user = user::get_user_by_id($comment->userid);
                    $comment->fullname = fullname($user);

                    // User picture.
                    $picture = new \user_picture($user);
                    $comment->userpictureurl = $picture->get_url($PAGE)->out(false);

                    // Check delete capability.
                    $comment->deleteenable = ($permissions->activeuserid == $comment->userid && $permissions->addcomment) ||
                        $permissions->managecontent;

                    // Check report capability.
                    $comment->reportenable = ($permissions->activeuserid != $comment->userid) && !$permissions->managecontent;

                    $comment->reportabuselink = util::render_report_abuse_link('openstudio', $permissions->activecmcontextid,
                        'content', $comment->id, $pageurl, $pageurl, $permissions->activeuserid);
                    $comment->timemodified = userdate($comment->timemodified, get_string('formattimedatetime', 'openstudio'));

                    if (is_null($comment->inreplyto)) { // This is a new comment.

                        $comments[$key] = $comment;

                    } else { // This is a reply.

                        $parentid = $comment->inreplyto;
                        if (!isset($commentthreads[$parentid])) {
                            $commentthreads[$parentid] = [];
                        }
                        $commentthreads[$parentid][] = $comment;
                    }
                }
                // Returns all the values from the array and indexes the array numerically.
                // We need this because mustache requires it.
                $contentdata->comments = array_values($comments);
            }

            // Attach replies to comments.
            foreach ($contentdata->comments as $key => $value) {
                // There is a comment stream for this comment.
                if (isset($commentthreads[$value->id])) {
                    $contentdata->comments[$key]->replies = $commentthreads[$value->id];
                }
            }

            $contentdata->emptycomment = (empty($contentdata->comments));

            // Require strings for js.
            $PAGE->requires->strings_for_js(
                array('contentcommentliked', 'contentcommentsdelete', 'modulejsdialogcommentdeleteconfirm',
                    'modulejsdialogcancel', 'modulejsdialogdelete'), 'mod_openstudio');

            $PAGE->requires->js_call_amd('mod_openstudio/comment', 'init', [[
                'cmid' => $cmid,
                'cid' => $contentdata->id]]);

            // Init OUMP module (Media player).
            // We need to init oump here to make sure that oump is always loaded even when no comment loaded.
            // As current behaviour, filter just call oump AMD module when has media markups found in filter input.
            // So if no media found, we can not trigger oump feature after user added a new comment by ajax.

            if (file_exists($CFG->dirroot.'/local/oump/classes/filteroump.php')) {
                // OUMP installed.
                require_once($CFG->dirroot.'/local/oump/classes/filteroump.php');
                $PAGE->requires->js_call_amd('local_oump/mloader', 'initialise', array([
                    'wwwroot' => $CFG->wwwroot . '/local/oump',
                    'urlargs' => \filter_oump::get_requirejs_urlargs(),
                    'jsdependency' => \filter_oump::get_js_dependency()
                ]));
            }
        }
    }

    /**
     * Check view-deleted-post capability and set up data and include dependencies.
     *
     * @param $contentdata Object
     * @param $permissions Object
     * @param $cmid int Course module ID
     */
    public static function process_view_deleted_post(&$contentdata, $permissions, $cmid) {
        global $PAGE;

        if ($contentdata->contenttype != content::TYPE_FOLDER) {
            return;
        }

        $context = \context_module::instance($cmid);

        // Check comment permission.
        $contentdata->viewdeletedpostenable = ($permissions->viewdeleted && $contentdata->isownedbyviewer)
                || $permissions->managecontent;

        if ($contentdata->viewdeletedpostenable) {

            $deletedposttemp = folder::get_deleted_contents($contentdata->id);
            $deletedposts = [];
            foreach ($deletedposttemp as $post) {
                $post = self::content_type_image($post, $context, true);

                $deletedposts[] = (object)array(
                    'pictureurl' => (string) $post->contenttypeimage,
                    'name' => $post->name,
                    'date' => $post->deletedtime ? date('j/m/y h:i', $post->deletedtime) : '',
                    'id' => $post->id
                );
            }

            // Require strings for js.
            $PAGE->requires->strings_for_js(array('folderdeletedposts'), 'mod_openstudio');

            $PAGE->requires->js_call_amd('mod_openstudio/viewdeleted', 'init', [[
                'cmid' => $cmid,
                'folderid' => $contentdata->id]]);
        }
    }

    /**
     * Return plugin file url.
     *
     * @param $contextid
     * @param $area
     * @param $itemid
     * @param $filename
     * @param null $folderid
     * @return string
     */
    public static function make_plugin_file($contextid, $area, $itemid, $filename, $folderid = null) {
        global $CFG;

        $pluginfileurl = $CFG->wwwroot
            . "/pluginfile.php/{$contextid}/mod_openstudio"
            . "/{$area}/{$itemid}/";

        if ($folderid) {
            $pluginfileurl .= "{$folderid}/";
        }

        $pluginfileurl .= rawurlencode($filename);

        return $pluginfileurl;
    }
}
