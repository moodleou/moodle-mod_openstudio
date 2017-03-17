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
        global $USER;

        $vuid = optional_param('vuid', $USER->id, PARAM_INT);
        $flagscontentread = 0;
        $showownfile = false;

        if ($vuid != $USER->id) {
            $contentowner = studio_api_user_get_user_by_id($vuid);
        } else {
            $contentowner = $USER;
        }

        $userprogressdata = studio_api_user_get_activity_status($openstudioid, $contentowner->id);

        $activedate = userdate($userprogressdata, get_string('strftimerecent', 'openstudio'));
        $flagsdata = flags::count_by_user($openstudioid, $contentowner->id);

        if (array_key_exists(flags::READ_CONTENT, $flagsdata)) {
            $flagscontentread = $flagsdata[flags::READ_CONTENT]->count;
        }

        if ($userprogressdata['totalslots'] > 0) {
            $userprogresspercentage = ceil(($userprogressdata['filledslots'] / $userprogressdata['totalslots']) * 100);
            $contentdata->percentcompleted = $userprogresspercentage;
        }

        if ($permissions->feature_studio || ($permissions->activitydata->used > 0)) {
            $showownfile = true;
        }

        $contentdata->showownfile = $showownfile;
        $contentdata->fullusername = $contentowner->firstname.' '.$contentowner->lastname;
        $contentdata->activedate = $activedate;
        $contentdata->flagscontentread = $flagscontentread;
        $contentdata->totalpostedcomments = $userprogressdata['totalpostedcomments'];
        $contentdata->userpictureurl = new \moodle_url('/user/pix.php/'.$contentowner->id.'/f1.jpg');
        $contentdata->viewuserworkurl = new \moodle_url('/mod/openstudio/view.php',
                    array('id' => $openstudioid, 'vuid' => $contentowner->id, 'vid' => content::VISIBILITY_PRIVATE));
        return $contentdata;
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
}
