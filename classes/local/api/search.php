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
 * Openstudio Search API.
 *
 * @package mod_studio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

use mod_openstudio\local\util;

/**
 * Search API functions
 *
 * This code has been only minimally refactored from openstudio V1, so may still use terms like "slot".
 */
class search {
    /**
     * Queries the search engine for a given search term.
     * Supports pagination.
     *
     * @param object $cm Course module object.
     * @param string $searchtext
     * @param int $pagestart
     * @param int $pagesize
     * @param int $nextstart
     * @param string $filterfunction
     * @return object Return search result.
     * @throws \moodle_exception
     */
    public static function query($cm, $searchtext,
            $pagestart = 0, $pagesize = \mod_openstudio\local\util\defaults::STREAMPAGESIZE, $nextstart = 0,
            $filtercontext = content::VISIBILITY_MODULE,
            $filterfunction = 'openstudio_ousearch_filter_permission') {
        global $PAGE;

        $searchresults = new \stdClass();

        $globalsearch = false;
        if (util::global_search_enabled($cm)) {
            $globalsearch = true;
            $pagesize = \core_search\manager::DISPLAY_RESULTS_PER_PAGE;
        }
        $limitfrom = 0;
        $limitnum = 0;
        if (($pagestart >= 0) && ($pagesize > 0)) {
            $limitfrom = $pagestart * $pagesize;
            $limitnum = $pagesize;
        }
        if ($nextstart > 0) {
            $limitfrom = $nextstart;
        }

        if ($globalsearch) {
            $search = \core_search\manager::instance();

            $data = new \stdClass();
            $data->q = $searchtext;
            $data->course = $cm->course;
            $data->courseids = [$data->course];
            $data->areaids = [
                'mod_openstudio-activity',
                'mod_openstudio-comments',
                'mod_openstudio-folders',
                'mod_openstudio-posts'
            ];
            // Get contexts id.
            $context = \context_module::instance($cm->id);
            $data->contextids = [$context->id];

            if (defined('BEHAT_SITE_RUNNING')) {
                if ($fakeresult = get_config('core_search', 'behat_fakeresult')) {
                    // We have to get the total results for behat tests here.
                    $results = json_decode($fakeresult);
                    $searchresults->dbrows = count($results->results);
                }
            }

            // Get global search results.
            $results = $search->paged_search($data, $pagestart);

            $searchresults->dbstart = 1;
            $searchresults->results = [];
            foreach ($results->results as $result) {
                $data = self::global_search_result($result);
                // Only return 1 item, when it's return more result(when search comments).
                if (!isset($searchresults->results[$data->intref1]) || $data->areaid == 'mod_openstudio-posts') {
                    $searchresults->results[$data->intref1] = self::global_search_result($result);
                }
            }
            if (!defined('BEHAT_SITE_RUNNING')) {
                $searchresults->dbrows = $results->totalcount;
            }
        } else {
            // Do nothing if OU search is not installed.
            if (!util::search_installed()) {
                return $searchresults;
            }
            // Query the search engine.
            $query = new \local_ousearch_search($searchtext);
            $query->set_coursemodule($cm);
            $query->set_filter($filterfunction);
            $searchresults = $query->query($limitfrom, $limitnum);
        }

        $nextsearchresults = 0;
        $result = array();
        $pagenext = $pageprevious = 0;
        if (isset($searchresults->results)) {

            foreach ($searchresults->results as $key => $searchresult) {
                $result[$key] = $searchresult;
            }
            if (!$globalsearch) {
                $nextsearchresults = $searchresults->dbstart;
                if ($searchresults->dbrows >= $pagesize) {
                    $pagenext = $pagestart + 1;
                }
            } else {
                if (($pagestart + 1) * $pagesize < $searchresults->dbrows) {
                    $pagenext = $pagestart + 1;
                }
            }
            if ($pagestart > 0) {
                $pageprevious = $pagestart - 1;
            }
        }

        return (object) array('result' => $result,
                'next' => $pagenext, 'previous' => $pageprevious, 'nextstart' => $nextsearchresults,
                'total' => $searchresults->dbrows, 'isglobal' => $globalsearch);
    }

    /**
     * Return search document record associated with a given slot.
     *
     * @param object $cm Course module object.
     * @param object $slot
     * @param bool $useidonly
     * @return object Return search document.
     */
    public static function get_content_document($cm, $slot, $useidonly = false) {
        // Do nothing if OU search is not installed.
        if (!util::search_installed()) {
            return true;
        }

        $doc = new \local_ousearch_document();
        $doc->init_module_instance('openstudio', $cm);
        $doc->set_user_id($slot->userid);
        if ($useidonly) {
            $doc->set_int_refs($slot->id, null);
        } else {
            $doc->set_int_refs($slot->id, $slot->visibility);
        }

        return $doc;
    }

    /**
     * Updates the fulltext search information for a slot which is being
     * added or updated.
     *
     * @param object $cm Course module object.
     * @param object $slot
     * @return bool If search update was successful
     */
    public static function update($cm, $slot) {
        // Do nothing if OU search is not installed.
        if (!util::search_installed()) {
            return true;
        }

        // Get search document.
        $doc = self::get_content_document($cm, $slot);

        // Set search document title.
        $title = stripslashes($slot->name);

        /*
         * Various slot metadata is concatenated into the search
         * document content field so it can be found in a search
         * text query.
         *
         */
        $content  = $slot->l1name . ' . ';
        $content .= $slot->l2name . ' . ';
        $content .= $slot->l3name . ' . ';
        $content .= $slot->name . ' . ';
        $content .= $slot->description . ' . ';
        $content .= $slot->firstname . ' . ';
        $content .= $slot->lastname . ' . ';
        $content .= $slot->username . ' . ';
        $content .= $slot->urltitle . ' . ';
        $content .= $slot->imagealt . ' . ';
        $content .= $slot->thumbnail . ' . ';
        $content .= $slot->mimetype . ' . ';
        $content .= $slot->content . ' . ';

        // Add in tags data.
        if (isset($slot->tagsraw) && is_array($slot->tagsraw)) {
            foreach ($slot->tagsraw as $slottag) {
                // Store tag as is, so it can be found.
                $content .= $slottag->name . ' . ';

                // Store tag with prefix as is, so it can be found exactly if required.
                $content .= 'tag:' . str_replace(' ', '', $slottag->name) . ' . ';
            }
        }

        // Add in comments.
        // To prevenr performance issue, we will only index the latest 25 comments.
        $commentsdata = comments::get_for_content($slot->id, null, 25);
        if ($commentsdata != false) {
            $content .= ' . ';
            foreach ($commentsdata as $comment) {
                $content .= ' . ' . $comment->commenttext;
            }
        }

        $content  = stripslashes($content);

        // Update information about this post (works ok for add or edit).
        return $doc->update($title, $content);
    }

    /**
     * Delete search record associated with slot.
     *
     * @param object $cm Course module object.
     * @param object $slot
     * @param bool $useidonly
     * @return bool Reurn true if delete was successful
     */
    public static function delete($cm, $slot, $useidonly = false) {
        // Do nothing if OU search is not installed.
        if (!util::search_installed()) {
            return true;
        }

        // Get search document.
        $doc = self::get_content_document($cm, $slot, $useidonly);
        if ($doc->find()) {
            return $doc->delete();
        }

        return true;
    }

    /**
     * Global search result.
     *
     * @param \core_search\document Containing a single search response to be displayed.
     * @return object data.
     */
    public static function global_search_result(\core_search\document $doc) {
        global $CFG, $PAGE;

        $docdata = $doc->export_for_template($PAGE->get_renderer('core'));
        $result = new \stdClass();
        // Get sid & folderid.
        $relativeurl = new \moodle_url($docdata['docurl']);
        $sid = $relativeurl->param('sid');
        $folderid = $relativeurl->param('folderid');

        $areaid = $doc->get('areaid');
        $itemid = $doc->get('itemid');
        $anchor = '';
        switch ($areaid) {
            case 'mod_openstudio-comments' :
                $itemid = $sid;
                $anchor = 'openstudio-comment-'.$doc->get('itemid');
                break;
        }

        $result->intref1 = $itemid;
        $result->areaid = $areaid;
        $result->anchor = $anchor;
        $result->folderid = $folderid;

        return $result;
    }
}
