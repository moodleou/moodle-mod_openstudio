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
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

class search_testcase extends \advanced_testcase {

    private $users;
    private $course;
    private $generator; // Contains mod_openstudio specific data generator functions.
    private $studiolevels; // Generic studio instance with no levels or contents.
    private $totalcontents;
    private $pinboardcontents;
    private $cm;
    private $content1;
    private $content2;
    private $content3;

    protected function setUp() {
        $this->resetAfterTest(true);
        // Use Legacy system for default.
        set_config('modulesitesearch', 2, 'local_moodleglobalsearch');
        set_config('activitysearch', 1, 'local_moodleglobalsearch');

        $teacherroleid = 3;
        $studentroleid = 5;
        $this->totalcontents = 24; // This is what the scripts below create for ONE CMID.
        $this->pinboardcontents = 3; // This is what the scripts below create for ONE CMID.

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                array('email' => 'student1@ouunittest.com', 'username' => 'student1'));
        $this->users->students->two = $this->getDataGenerator()->create_user(
                array('email' => 'student2@ouunittest.com', 'username' => 'student2'));
        $this->users->students->three = $this->getDataGenerator()->create_user(
                array('email' => 'student3@ouunittest.com', 'username' => 'student3'));
        $this->users->students->four = $this->getDataGenerator()->create_user(
                array('email' => 'student4@ouunittest.com', 'username' => 'student4'));
        $this->users->students->five = $this->getDataGenerator()->create_user(
                array('email' => 'student5@ouunittest.com', 'username' => 'student5'));
        $this->users->students->six = $this->getDataGenerator()->create_user(
                array('email' => 'student6@ouunittest.com', 'username' => 'student6'));
        $this->users->students->seven = $this->getDataGenerator()->create_user(
                array('email' => 'student7@ouunittest.com', 'username' => 'student7'));
        $this->users->students->eight = $this->getDataGenerator()->create_user(
                array('email' => 'student8@ouunittest.com', 'username' => 'student8'));
        $this->users->students->nine = $this->getDataGenerator()->create_user(
                array('email' => 'student9@ouunittest.com', 'username' => 'student9'));
        $this->users->students->ten = $this->getDataGenerator()->create_user(
                array('email' => 'student10@ouunittest.com', 'username' => 'student10'));
        $this->users->teachers = new \stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                array('email' => 'teacher1@ouunittest.com', 'username' => 'teacher1'));
        $this->users->teachers->two = $this->getDataGenerator()->create_user(
                array('email' => 'teacher2@ouunittest.com', 'username' => 'teacher2'));

        // Enroll our students and teacher (users) in the course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->one->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->two->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->three->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->four->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->five->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->six->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->eight->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->nine->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->ten->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->one->id, $this->course->id, $teacherroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->two->id, $this->course->id, $teacherroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance(array('course' => $this->course->id));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);

        $blockarray = current($this->studiolevels->leveldata['contentslevels']);
        $blockid = key($this->studiolevels->leveldata['contentslevels']);

        $activityarray = current($this->studiolevels->leveldata['contentslevels'][$blockid]);
        $activityid = key($this->studiolevels->leveldata['contentslevels'][$blockid]);

        $content1array = current($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);
        $content1id = key($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);
        $content2array = next($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);
        $content2id = key($this->studiolevels->leveldata['contentslevels'][$blockid][$activityid]);

        // Let's create some contents so we can search them here.
        $contententry1 = array(
                'name' => 'The First content',
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.youtube.com/watch?v=R4XSeW4B5Rg',
                'urltitle' => 'Vesica Timeline',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
                'description' => 'The Best YouTube Link Ever',
                'tags' => array('Stark', 'Lannister', 'Targereyen'),
                'ownership' => 0,
                'sid' => 0 // For a new content.
        );
        $contententry2 = array(
                'name' => 'The Second content',
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.youtube.com/watch?v=R4XSeW4B5Rg',
                'urltitle' => 'Vesica Timeline',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
                'description' => 'The Best YouTube Link Ever',
                'tags' => array('Communist', 'Socialist', 'Democrat'),
                'ownership' => 0,
                'sid' => 0 // For a new content.
        );
        $contententry3 = array(
                'name' => 'The Common content',
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://www.youtube.com/watch?v=R4XSeW4B5Rg',
                'urltitle' => 'Vesica Timeline',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
                'description' => 'The Best YouTube Link Ever',
                'tags' => array('Stark', 'Lannister', 'Targereyen', 'Communist'),
                'ownership' => 0,
                'sid' => 0 // For a new content.
        );
        $this->setUser($this->users->students->one);
        $this->cm = get_coursemodule_from_id('openstudio', $this->studiolevels->cmid);
        $this->content1 = \mod_openstudio\local\api\content::create(
                $this->studiolevels->id,  $this->users->students->one->id, 3,
                $this->studiolevels->leveldata['contentslevels'][$blockid][$activityid][$content1id], $contententry1,
                null, null, $this->cm);
        $this->content2 = \mod_openstudio\local\api\content::create(
                $this->studiolevels->id,  $this->users->students->one->id, 3,
                $this->studiolevels->leveldata['contentslevels'][$blockid][$activityid][$content2id], $contententry3,
                null, null, $this->cm);
        $this->content3 = \mod_openstudio\local\api\content::create_in_pinboard(
                $this->studiolevels->id, $this->users->students->one->id, $contententry2, $this->cm);
    }

    protected function tearDown() {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
        $this->cm = '';
        $this->content1 = '';
        $this->content2 = '';
        $this->content3 = '';
    }

    /**
     * Tests the search api
     */
    public function test_search() {
        $this->resetAfterTest(true);

        $searchres = \mod_openstudio\local\api\search::query($this->cm, 'The First Slot');
        $this->assertEquals(1, count($searchres->result));
        $searchres = \mod_openstudio\local\api\search::query($this->cm, 'Socialist');
        $this->assertEquals(1, count($searchres->result));
        $searchres = \mod_openstudio\local\api\search::query($this->cm, 'Lannister');
        $this->assertEquals(2, count($searchres->result));
    }

    /**
     * Tests the studio_api_search_get_slot_document() function
     */
    public function test_search_get_slot_document() {
        $this->resetAfterTest(true);
        $this->assertEquals(true, is_object(\mod_openstudio\local\api\search::get_content_document(
                $this->cm, \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->content1))));
        $this->assertEquals(true, is_object(\mod_openstudio\local\api\search::get_content_document(
                $this->cm, \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->content1), true)));
    }

    /**
     * Tests the studio_api_search_delete() function
     */
    public function test_searchdelete() {
        global $DB;
        $this->resetAfterTest(true);
        // This will NOT return true if the doc is deleted because of the function in searchlib.php.
        \mod_openstudio\local\api\search::delete(
                $this->cm, \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->content1));
        // Check if we can find slot 1 in search now, if we can't, this delete has worked.
        $searchres = \mod_openstudio\local\api\search::query($this->cm, 'The First Slot');
        $this->assertEquals(0, count($searchres->result));
    }

    /**
     * Tests the studio_api_search_update() function
     */
    public function test_search_update() {
        $this->resetAfterTest(true);
        // Delete slot 1 first from the system.
        \mod_openstudio\local\api\search::delete(
                $this->cm, \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->content1));
        // Check that is infact deleted.
        $searchres = \mod_openstudio\local\api\search::query($this->cm, 'The First Slot');
        $this->assertEquals(0, count($searchres->result));
        // Add it back.
        \mod_openstudio\local\api\search::update(
                $this->cm, \mod_openstudio\local\api\content::get_record($this->users->students->one->id, $this->content1));
        // See that it can be found again.
        $searchres = \mod_openstudio\local\api\search::query($this->cm, 'The First Slot');
        $this->assertEquals(1, count($searchres->result));
    }

}
