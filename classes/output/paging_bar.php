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

namespace mod_openstudio\output;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

/**
 * Open Studio paging bar.
 *
 * Representing a customized paging bar for Open Studio.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class paging_bar extends \paging_bar {

    /**
     * Set max display for paging bar.
     */
    const MAX_DISPLAY = 7;

    /**
     * Number of pages before and after the current page.
     */
    const BEFORE_AFTER_VALUE = 2;

    public $currentpage;

    /**
     * Prepares the paging bar for output on mobile devices.
     *
     * This method validates the arguments set up for the paging bar and generate navigation links.
     *
     * @param \renderer_base $output
     * @param \moodle_page $page
     * @param string $target
     * @throws \coding_exception
     */
    public function prepare_for_mobile(\renderer_base $output, \moodle_page $page, $target) {
        if (!isset($this->totalcount) || is_null($this->totalcount)) {
            throw new \coding_exception('paging_bar requires a totalcount value.');
        }
        if (!isset($this->page) || is_null($this->page)) {
            throw new \coding_exception('paging_bar requires a page value.');
        }
        if (empty($this->perpage)) {
            throw new \coding_exception('paging_bar requires a perpage value.');
        }
        if (empty($this->baseurl)) {
            throw new \coding_exception('paging_bar requires a baseurl value.');
        }

        if ($this->totalcount > $this->perpage) {
            if ($this->page > 0) {
                $this->previouslink = (new \moodle_url($this->baseurl,
                        array($this->pagevar => $this->page - 1)))->out(false);
            }

            if ($this->page >= 2) {
                $this->firstlink = (new \moodle_url($this->baseurl, array($this->pagevar => 0)))->out(false);
            }

            if ($this->perpage > 0) {
                $lastpage = ceil($this->totalcount / $this->perpage);
            } else {
                $lastpage = 1;
            }

            $pagenum = $this->page + 1;

            if ($pagenum != $lastpage) {
                $this->nextlink = (new \moodle_url($this->baseurl, array($this->pagevar=>$pagenum)))->out(false);
            }

            if ($this->page > 0) {
                $this->currentpage = $pagenum;
            }
        }
    }

    /**
     * Overwrite prepare function of paging_bar.
     *
     * The Paging bar is divided into 3 parts: the first part, the middle and the end.
     * The first part was working as expected (ex: Page: 1 2 3 4 5 6 ... 100 Next >).
     * The middle and the end of paging bar didn't working as expected.
     * Expected:
     *      The middle: < Previous Page: 1 ... 30 31 32 33 34 ... 100 Next >
     *      The end: < Previous Page: 1 ... 95 96 97 98 99 100
     * The function make sure paging bar works as expected without having to edit moodle core.
     * Parameters can be changed to match the requirements.
     *
     * @param \renderer_base $output
     * @param \moodle_page $page
     * @param string $target
     * @throws \coding_exception
     */
    public function prepare(\renderer_base $output, \moodle_page $page, $target) {
        parent::prepare($output, $page, $target);
        $firstarrangepage = self::MAX_DISPLAY - 1;
        if (count($this->pagelinks) > $firstarrangepage) {
            $arrpaging = [];
            $totalpage = ceil($this->totalcount / $this->perpage);
            $element = '<span class="current-page">'.$this->currentpage.'</span>';
            $currentpageindex = array_search($element, $this->pagelinks);
            /**
             * $lastarrangepage: page used for arrange paging bar when current page is between it and last page.
             *
             * Example: $totalpage = 100, $lastarrangepage = $totalpage - 5 = 95
             * 1. current page: 94, display paging: < Previous Page: 1 ... 92 93 94 95 96 ... 100 Next >
             * 2. current page: 95, display paging: < Previous Page: 1 ... 93 94 95 96 97 ... 100 Next >
             * 3. current page: 96, display paging: < Previous Page: 1 ... 95 96 97 98 99 100
             */
            $lastarrangepage = $totalpage - (self::MAX_DISPLAY - 2);
            if ($this->currentpage <= $lastarrangepage) {
                $index = max($currentpageindex - self::BEFORE_AFTER_VALUE, 0);
                for ($i =  $index; $i <= $currentpageindex + self::BEFORE_AFTER_VALUE; $i++) {
                    if ($this->pagelinks[$i]) {
                        array_push($arrpaging, $this->pagelinks[$i]);
                    }
                }
                if (is_null($this->lastlink)) {
                    $lastlinkurl = new \moodle_url($this->baseurl, ['page' => $totalpage - 1]);
                    $this->lastlink = \html_writer::link($lastlinkurl->out(false), $totalpage);
                } else {
                    $this->lastlink = \html_writer::link(str_replace($this->page, $totalpage - 1, $this->baseurl), $totalpage);
                }
            } else {
                $arrpaging = array_slice($this->pagelinks, 1 - self::MAX_DISPLAY);
            }
            $this->maxdisplay = self::MAX_DISPLAY;
            if ($arrpaging) {
                $this->pagelinks = $arrpaging;
            }
        }
    }
}
