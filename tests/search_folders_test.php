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
 * Unit tests for search folders code.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');

use mod_openstudio\local\api\content;
use mod_openstudio\search\folders;

/**
 * Test case for generic functions in classes/search/ where covered.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_folders_test extends \advanced_testcase {

    private $user;
    private $course;
    private $generator; // Contains mod_openstudio specific data generator functions.
    private $openstudiolevels; // Generic studio instance with no levels or contents.
    private $cm;
    private $studentroleid;
    private $emptycontent;
    private $sharedcontent;
    private $privatefoldercontent;
    private $sharedfoldercontent;

    /**
     * Sets up our fixtures.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
        $this->studentroleid = 5;

        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        \testable_core_search::instance();

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->user = $this->getDataGenerator()->create_user(
                ['email' => 'student1@ouunittest.com', 'username' => 'student1']);

        // Enroll our students in the course.
        $this->getDataGenerator()->enrol_user(
                $this->user->id, $this->course->id, $this->studentroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $openstudiodata = ['course' => $this->course->id, 'enablefolders' => 1, 'idnumber' => 'OS2'];
        $this->openstudiolevels = $this->generator->create_instance($openstudiodata);
        $this->openstudiolevels->leveldata = $this->generator->create_mock_levels($this->openstudiolevels->id);

        $blockid = key($this->openstudiolevels->leveldata['contentslevels']);
        $activityid = key($this->openstudiolevels->leveldata['contentslevels'][$blockid]);
        $content1id = key($this->openstudiolevels->leveldata['contentslevels'][$blockid][$activityid]);

        $this->cm = get_coursemodule_from_id('openstudio', $this->openstudiolevels->cmid);

        // Create content entry.
        $emptycontententry = (object)[
            'name' => 'Empty content entry',
            'visibility' => content::VISIBILITY_MODULE,
            'contenttype' => content::TYPE_NONE,
            'ownership' => 0,
            'sid' => 0
        ];

        $sharedcontententry = $this->generator->generate_single_data_array($this->user);
        $sharedcontententry['contenttype'] = content::TYPE_URL;

        $privatefolderentry = $this->generator->generate_single_data_array($this->user);
        $privatefolderentry['contenttype'] = content::TYPE_FOLDER;
        $privatefolderentry['visibility'] = content::VISIBILITY_PRIVATE;

        $foldercontententry = $this->generator->generate_single_data_array($this->user);
        $foldercontententry['contenttype'] = content::TYPE_FOLDER;

        // Create real content based on entry.
        $this->emptycontent = content::create_in_pinboard($this->openstudiolevels->id, $this->user->id,
                $emptycontententry, $this->cm);

        $this->sharedcontent = content::create_in_pinboard($this->openstudiolevels->id, $this->user->id,
                $sharedcontententry, $this->cm);

        $this->sharedfoldercontent = content::create(
                $this->openstudiolevels->id,  $this->user->id, 3,
                $this->openstudiolevels->leveldata['contentslevels'][$blockid][$activityid][$content1id], $foldercontententry,
                null, null, $this->cm);

        $this->privatefoldercontent = content::create_in_pinboard($this->openstudiolevels->id, $this->user->id,
                $privatefolderentry, $this->cm);

        require_once(__DIR__ . '/search_posts_test.php');
        search_posts_test::fix_timemodified_order();
    }

    /**
     * Test function not get any record belong to empty content or folder.
     */
    public function test_search_contents_index() {
        $folders = new folders();
        $results = self::recordset_to_array($folders->get_recordset_by_timestamp());

        // Check that function return 2 folders.
        $this->assertCount(2, $results);
    }

    /**
     * Test check get document.
     */
    public function test_check_get_document_function() {
        global $CFG;

        $folders = new folders();

        $context = context_module::instance($this->cm->id);

        $results = self::recordset_to_array($folders->get_recordset_by_timestamp());

        $doc = $folders->get_document($results[0], ['lastindexedtime' => 0]);
        $this->assertEquals('The GoT Vesica Timeline', $doc->get('title'));
        $this->assertEquals('The Best YouTube Link Ever', $doc->get('content'));
        $this->assertEquals('Block 1 Activity 1 Slot 1', $doc->get('description1'));
        $this->assertEquals($context->id, $doc->get('contextid'));
        $this->assertEquals(\core_search\manager::TYPE_TEXT, $doc->get('type'));
        $this->assertEquals($this->course->id, $doc->get('courseid'));
        $this->assertEquals($this->sharedfoldercontent, $doc->get('itemid'));
        $this->assertEquals(\core_search\manager::NO_OWNER_ID, $doc->get('owneruserid'));

        // Check search result url.
        $url = $folders->get_doc_url($doc)->out(false);
        $this->assertEquals($CFG->wwwroot . '/mod/openstudio/folder.php?id=' . $this->cm->id . '&sid=' . $this->sharedfoldercontent,
                $url);

    }

    /**
     * Test check get document.
     */
    public function test_check_get_permission() {
        $folders = new folders();

        // Check as admin.
        $this->setAdminUser();

        // Deleted if trying to get  belong to empty content since it will not indexed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $folders->check_access($this->emptycontent));
        // Deleted if trying to get  that not existed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $folders->check_access(0));
        // Check return granted when get url  and folder .
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $folders->check_access($this->sharedfoldercontent));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $folders->check_access($this->privatefoldercontent));

        // Check as owner user.
        $this->setUser($this->user);

        // Deleted if trying to get  belong to empty content since it will not indexed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $folders->check_access($this->emptycontent));
        // Deleted if trying to get  that not existed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $folders->check_access(0));
        // Check return granted when get url  and folder .
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $folders->check_access($this->sharedfoldercontent));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $folders->check_access($this->privatefoldercontent));

        // Check as non-owner user.
        // Create Users and enroll to course.
        $anotheruser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user(
            $anotheruser->id, $this->course->id, $this->studentroleid, 'manual');
        $this->setUser($anotheruser);

        // Deleted if trying to get  belong to empty content since it will not indexed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $folders->check_access($this->emptycontent));
        // Deleted if trying to get  that not existed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $folders->check_access(0));
        // Check return granted when get url , folder  and deny when get private .
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $folders->check_access($this->sharedfoldercontent));
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $folders->check_access($this->privatefoldercontent));
    }

    /**
     * Converts recordset to array, indexed numberically (0, 1, 2).
     *
     * @param \moodle_recordset $rs Record set to convert
     * @return \stdClass[] Array of converted records
     */
    protected static function recordset_to_array(moodle_recordset $rs) {
        $result = [];
        foreach ($rs as $rec) {
            $result[] = $rec;
        }
        $rs->close();
        return $result;
    }
}
