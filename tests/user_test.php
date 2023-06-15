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

use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\stream;

global $CFG;
require_once($CFG->dirroot . '/mod/openstudio/tests/test_utils.php');

class user_testcase extends \advanced_testcase {

    protected $users;
    protected $file;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studio1;
    protected $studio2;
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
                array('email' => 'student1@ouunittest.com', 'username' => 'student1',
                        'firstname' => 'Student', 'lastname' => 'One'));
        $this->users->students->two = $this->getDataGenerator()->create_user(
                array('email' => 'student2@ouunittest.com', 'username' => 'student2',
                        'firstname' => 'Student', 'lastname' => 'Two'));
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
        $this->studio1 = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS1'));
        $this->studio1->leveldata = $this->generator->create_mock_levels($this->studio1->id);
        $this->studio2 = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS2'));
        $this->studio2->leveldata = $this->generator->create_mock_levels($this->studio2->id);
    }

    protected function tearDown(): void {
        $this->course = '';
        $this->generator = '';
        $this->studio1 = '';
        $this->studio2 = '';
    }

    /**
     * Test get all filled activity contents of all users.
     */
    public function test_get_all_users_activity_status() {
        $this->resetAfterTest(true);

        // Create contents for all level data.
        $contents = array();
        $contentcount = 0;
        foreach ($this->studio1->leveldata['contentslevels'] as $activitylevels) {
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
                            $this->studio1->id,  $this->users->students->two->id,
                            3, $contentlevelid, $data); // Level 3 is for contents.
                    $contents[$contentlevelid][$contentcount] = \mod_openstudio\local\api\content::create(
                            $this->studio1->id,  $this->users->students->three->id,
                            3, $contentlevelid, $data); // Level 3 is for contents.
                    $contents[$contentlevelid][$contentcount] = \mod_openstudio\local\api\content::create(
                            $this->studio1->id,  $this->users->students->four->id,
                            3, $contentlevelid, $data); // Level 3 is for contents.
                    $contents[$contentlevelid][$contentcount] = \mod_openstudio\local\api\content::create(
                            $this->studio1->id,  $this->users->students->five->id,
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
        $contentid3 = $this->generator->create_contents(array(
                'openstudio' => 'OS2',
                'userid' => $this->users->students->six->id,
                'name' => 'Content1',
                'description' => random_string()
        ));
        $comment1 = $this->generator->create_comment(array(
                'contentid' => $contentid1,
                'userid' => $this->users->students->five->id,
                'comment' => random_string()
        ));
        $comment2 = $this->generator->create_comment(array(
                'contentid' => $contentid1,
                'userid' => $this->users->students->six->id,
                'comment' => random_string()
        ));
        $comment3 = $this->generator->create_comment(array(
                'contentid' => $contentid2,
                'userid' => $this->users->students->six->id,
                'comment' => random_string()
        ));
        $this->generator->create_comment(array(
                'contentid' => $contentid3,
                'userid' => $this->users->students->six->id,
                'comment' => random_string()
        ));

        flags::comment_toggle($contentid1, $comment1, flags::INSPIREDME, $this->users->students->two->id, 'on');
        flags::comment_toggle($contentid1, $comment2, flags::MADEMELAUGH, $this->users->students->three->id, 'on');
        flags::comment_toggle($contentid2, $comment3, flags::READ_CONTENT, $this->users->students->four->id, 'on');

        // Set Active User to student1.
        $this->setUser($this->users->students->one);
        $userids = [
                $this->users->students->two->id,
                $this->users->students->three->id,
                $this->users->students->four->id,
                $this->users->students->five->id,
                $this->users->students->six->id,
                $this->users->students->seven->id,
                $this->users->students->eight->id
        ];

        // Mock flags and tracking.
        test_utils::mock_flag_times();
        test_utils::mock_tracking_times(30);

        $userprogressdata = \mod_openstudio\local\api\user::get_all_users_activity_status($this->studio1->id, $userids);

        $this->assertEquals($contentcount, $userprogressdata[$this->users->students->two->id]['filledcontents']);
        $this->assertEquals($contentcount, $userprogressdata[$this->users->students->three->id]['filledcontents']);
        $this->assertEquals($contentcount, $userprogressdata[$this->users->students->four->id]['filledcontents']);
        $this->assertEquals($contentcount, $userprogressdata[$this->users->students->five->id]['filledcontents']);

        $this->assertEquals(0, $userprogressdata[$this->users->students->six->id]['filledcontents']);
        $this->assertEquals(2, $userprogressdata[$this->users->students->six->id]['totalpostedcomments']);
        $this->assertEquals(1, $userprogressdata[$this->users->students->six->id]['totalpostedcommentsexcludeown']);

        $this->assertTrue(empty($userprogressdata[$this->users->students->seven->id]['lastactivedate']));

        $this->check_lastactivedate($this->studio1->id, $userprogressdata, $this->users->students->two->id);
        $this->check_lastactivedate($this->studio1->id, $userprogressdata, $this->users->students->three->id);
        $this->check_lastactivedate($this->studio1->id, $userprogressdata, $this->users->students->four->id);
        $this->check_lastactivedate($this->studio1->id, $userprogressdata, $this->users->students->five->id);
        $this->check_lastactivedate($this->studio1->id, $userprogressdata, $this->users->students->six->id);
        $this->check_lastactivedate($this->studio1->id, $userprogressdata, $this->users->students->eight->id);
    }

    /**
     * Check the last active date for the user.
     *
     * @param $studioid
     * @param $data
     * @param $userid
     * @throws dml_exception
     */
    private function check_lastactivedate($studioid, $data, $userid) {
        global $DB;

        $user = $data[$userid];
        $lastactivedate = $user['lastactivedate'];
        $this->assertTrue(!empty($lastactivedate));

        $fmodified = $DB->get_field_sql(
                "SELECT max(f.timemodified) fmodified
                FROM {openstudio_flags} f
                JOIN {openstudio_contents} fc ON fc.id = f.contentid AND fc.openstudioid = ?
                WHERE f.userid = ?", [$studioid, $userid]);

        $tmodified = $DB->get_field_sql(
                "SELECT max(t.timemodified) tmodified
                FROM {openstudio_tracking} t
                JOIN {openstudio_contents} tc ON tc.id = t.contentid AND tc.openstudioid = ?
                WHERE t.userid = ?", [$studioid, $userid]);

        $fmodified = $fmodified ?? 0;
        $tmodified = $tmodified ?? 0;
        $expected = $fmodified > $tmodified ? $fmodified : $tmodified;
        $this->assertTrue(!empty($expected));
        $this->assertEquals($expected, $lastactivedate);
    }

    /**
     * Test get all users.
     */
    public function test_get_all() {
        $this->resetAfterTest(true);

        $user1 = $this->users->students->one;
        $user2 = $this->users->students->two;

        // Create contents.
        $data = $this->generator->generate_single_data_array();
        $content1 = $this->generator->generate_content_data($this->studio1, $user1->id, $data);
        $data = $this->generator->generate_single_data_array();
        $content2 = $this->generator->generate_content_data($this->studio1, $user2->id, $data);
        $data = $this->generator->generate_single_data_array();
        $content3 = $this->generator->generate_content_data($this->studio2, $user1->id, $data);

        // Create comments.
        $comment2 = $this->generator->create_comment(array(
                'contentid' => $content2->id,
                'userid' => $user1->id,
                'comment' => random_string()
        ));
        $comment1 = $this->generator->create_comment(array(
                'contentid' => $content1->id,
                'userid' => $user2->id,
                'comment' => random_string()
        ));
        $comment3 = $this->generator->create_comment(array(
                'contentid' => $content3->id,
                'userid' => $user1->id,
                'comment' => random_string()
        ));

        // Create flags.
        flags::toggle($content2->id, flags::FAVOURITE, 'on', $user1->id);
        flags::toggle($content1->id, flags::NEEDHELP, 'on', $user2->id);
        flags::toggle($content3->id, flags::ALERT, 'on', $user1->id);
        flags::comment_toggle($content2->id, $comment2, flags::MADEMELAUGH, $user1->id, 'on');
        flags::comment_toggle($content1->id, $comment1, flags::INSPIREDME, $user2->id, 'on');
        flags::comment_toggle($content3->id, $comment3, flags::READ_CONTENT, $user1->id, 'on');

        // Mock flags and tracking.
        test_utils::mock_flag_times();
        test_utils::mock_tracking_times(30);

        // Run various tests.
        $this->do_test_get_all(stream::SORT_PEOPLE_ACTIVTY, stream::SORT_ASC, $user1, $user2);
        $this->do_test_get_all(stream::SORT_PEOPLE_ACTIVTY, stream::SORT_DESC, $user2, $user1);
        $this->do_test_get_all(stream::SORT_PEOPLE_NAME, stream::SORT_ASC, $user1, $user2);
        $this->do_test_get_all(stream::SORT_PEOPLE_NAME, stream::SORT_DESC, $user2, $user1);
    }

    /**
     * Runs get all users test.
     *
     * @param int $sorttype
     * @param int $sortorder
     * @param object $expecteduser1
     * @param object $expecteduser2
     */
    protected function do_test_get_all(int $sorttype, int $sortorder, $expecteduser1, $expecteduser2) {
        // Get data.
        $data = \mod_openstudio\local\api\user::get_all($this->studio1->id,
                NOGROUPS, 0, 0,
                stream::FILTER_PEOPLE_MODULE, $sorttype, $sortorder, 0, 0, true);

        // Check data.
        $this->assertFalse(empty($data));
        $this->assertNotNull($data->people);

        $users = array_values($data->people);
        $this->assertEquals(2, count($users));
        $this->assertEquals($expecteduser1->username, $users[0]->username);
        $this->assertEquals($expecteduser2->username, $users[1]->username);
    }
}
