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
}