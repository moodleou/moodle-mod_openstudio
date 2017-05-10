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
}
