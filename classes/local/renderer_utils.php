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

        $navigationurls = (object) array();

        $navigationurls->myworkurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_PRIVATE, 'fblock' => false,
                          'ftype' => 0, 'fflag' => 0,
                          'fsort' => stream::SORT_BY_ACTIVITYTITLE, 'osort' => 1));

        $navigationurls->pinboardurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_PRIVATE_PINBOARD, 'fblock' => -1,
                          'ftype' => 0, 'fflag' => 0,
                          'ftags' => '', 'fsort' => stream::SORT_BY_DATE, 'osort' => 0));

        $navigationurls->mygroupurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_GROUP, 'fblock' => 0,
                          'ftype' => 0, 'fflag' => 0,
                          'ftags' => '', 'fsort' => stream::SORT_BY_DATE, 'osort' => 0));

        $navigationurls->mymoduleurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $cmid, 'vid' => content::VISIBILITY_MODULE, 'fblock' => 0,
                          'ftype' => 0, 'fflag' => 0,
                          'ftags' => '', 'fsort' => stream::SORT_BY_DATE, 'osort' => 0));

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
        global $USER, $OUTPUT;

        $vuid = optional_param('vuid', $USER->id, PARAM_INT);
        $flagscontentread = 0;
        $showownfile = false;
        $ismyprofile = true;

        if ($vuid && $vuid != $USER->id) {
            $contentowner = studio_api_user_get_user_by_id($vuid);
            $ismyprofile = false;
        } else {
            $contentowner = $USER;
        }

        $userprogressdata = studio_api_user_get_activity_status($openstudioid, $contentowner->id);

        $activedate = userdate($userprogressdata, get_string('strftimerecent', 'openstudio'));
        $flagsdata = flags::count_by_user($openstudioid, $contentowner->id);

        if (array_key_exists(flags::READ_CONTENT, $flagsdata)) {
            $flagscontentread = $flagsdata[flags::READ_CONTENT]->count;
        }

        $contentdata->percentcompleted = 0;
        if ($userprogressdata['totalslots'] > 0) {
            $userprogresspercentage = ceil(($userprogressdata['filledslots'] / $userprogressdata['totalslots']) * 100);
            $contentdata->percentcompleted = $userprogresspercentage;
        }

        if ($permissions->feature_studio || ($permissions->activitydata->used > 0)) {
            $showownfile = true;
        }

        $contentdata->showownfile = $showownfile;
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

        $contentdata->showprofileactivities = false;
        if (isset($userprogressdata['progressdetail']) && $userprogressdata['progressdetail']) {
            $contentdata->showprofileactivities = true;
            $profileactivityitems = [];

            if (!empty($userprogressdata['progressdetail'])) {
                foreach ($userprogressdata['progressdetail'] as $values) {
                    foreach ($values as $level2id => $activities) {
                        $activityname = '';
                        foreach ($activities as $key => $activity) {
                            $activityname = $activity->level2name;

                            $activities[$key]->isactive = false;
                            $activities[$key]->activityediturl = new \moodle_url('/mod/openstudio/contentedit.php',
                                array('id' => $contentdata->cmid, 'sid' => 0, 'lid' => $activity->level3id));

                            if ($activity->id) {
                                $activities[$key]->isactive = true;
                                $activities[$key]->activityediturl = new \moodle_url('/mod/openstudio/content.php',
                                    array('id' => $contentdata->cmid, 'sid' => $activity->id, 'vuid' => $contentowner->id));
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
                $contentfileurl = $CFG->wwwroot
                    . "/pluginfile.php/{$context->id}/mod_openstudio"
                    . "/{$contentarea}/{$contentdata->id}/" . rawurlencode($contentdata->content);

                break;

            case content::TYPE_VIDEO:
            case content::TYPE_AUDIO:
                $contenttypemedia = true;
                $contenttypedownloadfile = true;
                $contentfileurl = $CFG->wwwroot
                    . "/pluginfile.php/{$context->id}/mod_openstudio"
                    . "/{$contentarea}/{$contentdata->id}/" . rawurlencode($contentdata->content);

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
                            $contentfileurls[$extension] = $CFG->wwwroot
                                . "/pluginfile.php/{$context->id}/mod_openstudio"
                                . "/{$contentarea}/{$contentdata->id}/" . rawurlencode($filename);
                        }
                    }
                    $contentiframesrc = $contentfileurls['html'];
                    $contentfileurl = $contentfileurls['ipynb'] . '?forcedownload=true';
                    break; // Only if this is a notebook.
                }
            case content::TYPE_PRESENTATION:
            case content::TYPE_SPREADSHEET:
                $contenttypedownloadfile = true;
                $contentfileurl = $CFG->wwwroot
                    . "/pluginfile.php/{$context->id}/mod_openstudio"
                    . "/{$contentarea}/{$contentdata->id}/" . rawurlencode($contentdata->content);

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
                        $contenttypeuseimagedefault = true;
                        $contenttypeiconurl = $OUTPUT->pix_url('unknown_rgb_32px', 'openstudio');
                    break;
                case content::TYPE_IMAGE:
                    if ($contentdata->mimetype == 'image/bmp') {
                        $contenttypeiconurl = $OUTPUT->pix_url('image_rgb_32px', 'openstudio');
                    } else {
                        $contenttypeiconurl = $CFG->wwwroot
                            . "/pluginfile.php/{$context->id}/mod_openstudio"
                            . "/{$contentthumbnailarea}/{$contentdata->id}/" . rawurlencode($contentdata->content);
                    }
                    break;

                case content::TYPE_VIDEO:
                    $contenttypeiconurl = $OUTPUT->pix_url('video_rgb_32px', 'openstudio');
                    break;
                case content::TYPE_AUDIO:
                    $contenttypeiconurl = $OUTPUT->pix_url('audio_rgb_32px', 'openstudio');
                    break;
                case content::TYPE_DOCUMENT:
                case content::TYPE_URL_DOCUMENT:
                case content::TYPE_URL_DOCUMENT_DOC:
                    switch ($contentdata->mimetype) {
                        case 'application/vnd.oasis.opendocument.text':
                            $contenttypeiconurl = $OUTPUT->pix_url('word_rgb_32px', 'openstudio');
                            break;
                        case 'application/pdf':
                            $contenttypeiconurl = $OUTPUT->pix_url('pdf_rgb_32px', 'openstudio');
                            break;
                        default:
                            $contenttypeiconurl = $OUTPUT->pix_url('text_doc_rgb_32px', 'openstudio');
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

        $contentdata->contentvisibilityicon = self::content_visibility_icon($contentdata);

        return $contentdata;
    }

    /**
     * This function generate visibility icon for a content.
     *
     * @param object $contentdata The content records to display.
     * @return object $contentdata
     */
    public static function content_visibility_icon($contentdata) {
        $visibility = (int)$contentdata->vid;
        if ($visibility < 0) {
            $visibility = content::VISIBILITY_GROUP;
        }

        switch ($visibility) {
            case content::VISIBILITY_MODULE:
                $contentvisibilityicon = 'mymodule';
                break;
            case content::VISIBILITY_GROUP:
                $contentvisibilityicon = 'group';
                break;
            case content::VISIBILITY_WORKSPACE:
            case content::VISIBILITY_PRIVATE:
            case content::VISIBILITY_PRIVATE_PINBOARD:
                $contentvisibilityicon = 'onlyme';
                break;
            case content::VISIBILITY_TUTOR:
                $contentvisibilityicon = 'tutor';
                break;
            default:
                $contentvisibilityicon = 'onlyme';
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
    protected function content_lock_data($contentdata) {
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
     * @return object $contentdata
     */
    public static function content_type_image($contentdata, $context) {
        global $CFG, $OUTPUT;

        $contenttypedefaultimage = true;
        $contentthumbnailarea = 'contentthumbnail';
        switch ($contentdata->contenttype) {
            case content::TYPE_IMAGE:
                if ($contentdata->mimetype == 'image/bmp') {
                    $contenttypeimage = $OUTPUT->pix_url('unknown_rgb_32px', 'openstudio');
                } else {
                    if ($contentdata->content) {
                        $contenttypeimage = $CFG->wwwroot
                            . "/pluginfile.php/{$context->id}/mod_openstudio"
                            . "/{$contentthumbnailarea}/{$contentdata->id}/". rawurlencode($contentdata->content);
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
                    case 'application/vnd.oasis.opendocument.text':
                        $contenttypeimage = $OUTPUT->pix_url('word_rgb_32px', 'openstudio');
                        break;
                    case 'application/pdf':
                        $contenttypeimage = $OUTPUT->pix_url('pdf_rgb_32px', 'openstudio');
                        break;
                    default:
                        $contenttypeimage = $OUTPUT->pix_url('text_doc_rgb_32px', 'openstudio');
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
            default:
                $contenttypeimage = $OUTPUT->pix_url('unknown_rgb_32px', 'openstudio');
                break;
        }

        $contentdata->contenttypeimage = $contenttypeimage;
        $contentdata->contenttypedefaultimage = $contenttypedefaultimage;

        return $contentdata;

    }
}
