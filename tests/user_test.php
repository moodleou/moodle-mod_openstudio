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

defined('MOODLE_INTERNAL') || die();

class user_testcase extends \advanced_testcase {

    protected $users;
    protected $file;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or slots.
    protected $singleentrydata;
    protected $contentdata;
    protected $teacherroleid;
    protected $studentroleid;
    protected $pinboardcontents;

    /**
     * Sets up our fixtures.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        $this->teacherroleid = 3;
        $this->studentroleid = 5;

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
        $this->users->students->seven = $this->getDataGenerator()->create_user
        (array('email' => 'student7@ouunittest.com', 'username' => 'student7'));
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
                $this->users->students->one->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->two->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->three->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->four->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->five->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->six->id, $this->course->id, $this->studentroleid, 'manual');
        // Student 7 is NOT enrolled in any course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->eight->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->nine->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->ten->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->one->id, $this->course->id, $this->teacherroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->two->id, $this->course->id, $this->teacherroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS1'));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
    }

    protected function tearDown(): void {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
    }

    /**
     * Test get all filled activity contents of all users.
     */
    public function test_get_all_users_activity_status() {
        $this->resetAfterTest(true);

        // Create contents for all level data.
        $contents = array();
        $contentcount = 0;
        foreach ($this->studiolevels->leveldata['contentslevels'] as $activitylevels) {
            foreach ($activitylevels as $contentlevels) {
                foreach ($contentlevels as $contentlevelid) {
                    $contentcount++;
                    $data = array(
                            'name' => 'YouTube URL' . random_string(),
                            'attachments' => '',
                            'embedcode' => '',
                            'weblink' => 'http://www.open.ac.uk/',
                            'urltitle' => 'Vesica Timeline',
                            'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
                            'description' => 'YouTube link',
                            'tags' => array(random_string(), random_string(), random_string()),
                            'ownership' => 0,
                            'sid' => 0 // For a new content.
                    );
                    $contents[$contentlevelid][$contentcount] = \mod_openstudio\local\api\content::create(
                            $this->studiolevels->id,  $this->users->students->two->id,
                            3, $contentlevelid, $data); // Level 3 is for contents.
                    $contents[$contentlevelid][$contentcount] = \mod_openstudio\local\api\content::create(
                            $this->studiolevels->id,  $this->users->students->three->id,
                            3, $contentlevelid, $data); // Level 3 is for contents.
                    $contents[$contentlevelid][$contentcount] = \mod_openstudio\local\api\content::create(
                            $this->studiolevels->id,  $this->users->students->four->id,
                            3, $contentlevelid, $data); // Level 3 is for contents.
                    $contents[$contentlevelid][$contentcount] = \mod_openstudio\local\api\content::create(
                            $this->studiolevels->id,  $this->users->students->five->id,
                            3, $contentlevelid, $data); // Level 3 is for contents.
                }
            }
        }

        // Create normal contents and comments.
        $contentid1 = $this->generator->create_contents(array(
                'openstudio' => 'OS1',
                'userid' => $this->users->students->six->id,
                'name' => 'Content1',
                'description' => random_string()
        ));
        $contentid2 = $this->generator->create_contents(array(
                'openstudio' => 'OS1',
                'userid' => $this->users->students->eight->id,
                'name' => 'Content2',
                'description' => random_string()
        ));
        $this->generator->create_comment(array(
                'contentid' => $contentid1,
                'userid' => $this->users->students->six->id,
                'comment' => random_string()
        ));
        $this->generator->create_comment(array(
                'contentid' => $contentid2,
                'userid' => $this->users->students->six->id,
                'comment' => random_string()
        ));

        // Set Active User to student1.
        $this->setUser($this->users->students->one);
        $userids = [
                $this->users->students->two->id,
                $this->users->students->three->id,
                $this->users->students->four->id,
                $this->users->students->five->id,
                $this->users->students->six->id,
                $this->users->students->eight->id
        ];

        $userprogressdata = \mod_openstudio\local\api\user::get_all_users_activity_status($this->studiolevels->id, $userids);

        $this->assertEquals($contentcount, $userprogressdata[$this->users->students->two->id]['filledcontents']);
        $this->assertEquals($contentcount, $userprogressdata[$this->users->students->three->id]['filledcontents']);
        $this->assertEquals($contentcount, $userprogressdata[$this->users->students->four->id]['filledcontents']);
        $this->assertEquals($contentcount, $userprogressdata[$this->users->students->five->id]['filledcontents']);

        $this->assertEquals(0, $userprogressdata[$this->users->students->six->id]['filledcontents']);
        $this->assertEquals(2, $userprogressdata[$this->users->students->six->id]['totalpostedcomments']);
        $this->assertEquals(1, $userprogressdata[$this->users->students->six->id]['totalpostedcommentsexcludeown']);

        $this->assertTrue(!empty($userprogressdata[$this->users->students->two->id]['lastactivedate']));
        $this->assertTrue(!empty($userprogressdata[$this->users->students->three->id]['lastactivedate']));
        $this->assertTrue(!empty($userprogressdata[$this->users->students->four->id]['lastactivedate']));
        $this->assertTrue(!empty($userprogressdata[$this->users->students->five->id]['lastactivedate']));
        $this->assertTrue(!empty($userprogressdata[$this->users->students->six->id]['lastactivedate']));
        $this->assertTrue(!empty($userprogressdata[$this->users->students->eight->id]['lastactivedate']));

    }

}
