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
 * A temporary home for some common set-up code.
 *
 * TODO: Replace usage of these functions with generator calls.
 *
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class openstudio_testcase extends advanced_testcase {

    protected $users;
    protected $file;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or slots.
    protected $totalslots;
    protected $pinboardslots;
    protected $singleentrydata;
    protected $contentdata;
    protected $contentid;

    /**
     * Populates array with content data to create content(s).
     */
    public function populate_single_data_array() {
        $this->singleentrydata = array(
                'name' => 'The GoT Vesica Timeline',
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.open.ac.uk/',
                'urltitle' => 'Vesica Timeline',
                'visibility' => STUDIO_VISIBILITY_MODULE,
                'description' => 'The Best YouTube Link Ever',
                'tags' => array('Stark', 'Lannister', 'Targereyen'),
                'ownership' => 0,
                'sid' => 0 // For a new content.
        );
    }

    /**
     * Creates a new content and stores the id in contentid.
     * Queries the new content and stores returned data in contentdata.
     */
    public function populate_content_data() {
        current($this->studiolevels->leveldata['contentslevels']);
        $blockid = key($this->studiolevels->leveldata['contentslevels']);

        current($this->studiolevels->leveldata['contentslevels'][$blockid]);
        $activityid = key($this->studiolevels->leveldata['contentslevels'][$blockid]);

        current($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);
        $contentid = key($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);

        $this->contentid = mod_openstudio\local\api\content::create(
                $this->studiolevels->id,
                $this->users->students->one->id, 3,
                $this->studiolevels->leveldata['contentslevels'][$blockid][$activityid][$contentid], $this->singleentrydata);
        $this->contentdata = mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->contentid);
    }

    /**
     * Creates a new stdClass() with information for a file.
     */
    public function populate_file_data() {
        $this->file = new stdClass();
        $this->file->filearea = 'content';
        $this->file->filename = 'Test file';
        $this->file->filepath = '/';
        $this->file->sortorder = 0;
        $this->file->author = $this->users->students->one->firstname . ' ' . $this->users->students->one->lastname;
        $this->file->license = 'allrightsreserved';
        $this->file->datemodified = 1365590964;
        $this->file->datecreated = 1365590864;
        $this->file->component = 'mod_openstudio';
        $this->file->itemid = 0;
    }
}