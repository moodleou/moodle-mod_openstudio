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
 * Unit tests for mod/openstudio/classes/external.php.
 *
 * @package    mod_openstudio
 * @category   phpunit
 * @copyright  2022 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

use mod_openstudio_external;

defined('MOODLE_INTERNAL') || die();

class external_test extends \advanced_testcase {

    protected $users;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or slots.
    protected $singleentrydata;
    protected $contentdata;
    protected $teacherroleid = 3;
    protected $studentroleid = 5;

    /**
     * Sets up our fixtures.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user([
                'email' => 'student1@ouunittest.com',
                'username' => 'student1'
        ]);
        $this->users->students->two = $this->getDataGenerator()->create_user([
                'email' => 'student2@ouunittest.com',
                'username' => 'student2'
        ]);

        // Enroll our students in the course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->one->id, $this->course->id, $this->studentroleid);
        $this->getDataGenerator()->enrol_user(
                $this->users->students->two->id, $this->course->id, $this->studentroleid);

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1',
        ]);
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
    }

    protected function tearDown(): void {
        $this->course = null;
        $this->generator = null;
        $this->studiolevels = null;
    }

    /**
     * Tests mod_openstudio_external::add_comment success.
     */
    public function test_add_comment_success(): void {
        global $OUTPUT;
        $this->resetAfterTest(true);
        $this->singleentrydata = $this->generator->generate_single_data_array();
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);

        $this->setUser($this->users->students->one);
        // Test create a comment.
        $comment = mod_openstudio_external::add_comment($this->studiolevels->cmid, $this->contentdata->id,
                'Test');
        $this->assertArrayHasKey('commentid', $comment);
        $this->assertIsInt($comment['commentid']);
        $this->assertStringContainsString($OUTPUT->user_picture($this->users->students->one,
                ['size' => 100, 'link' => false]), $comment['commenthtml']);
        // User 2 login and tries to reply to comment of user 1.
        $this->setUser($this->users->students->two);
        $reply = mod_openstudio_external::add_comment($this->studiolevels->cmid, $this->contentdata->id,
                'Test', 0, $comment['commentid']);
        $this->assertArrayHasKey('commentid', $reply);
        $this->assertIsInt($reply['commentid']);
        $this->assertStringContainsString($OUTPUT->user_picture($this->users->students->two,
                ['size' => 100, 'link' => false]), $reply['commenthtml']);
    }

    /**
     * Tests mod_openstudio_external::add_comment with not existed/not found comment record.
     */
    public function test_add_comment_with_not_existed_comment(): void {
        $this->resetAfterTest(true);
        $this->singleentrydata = $this->generator->generate_single_data_array();
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);

        $this->setUser($this->users->students->one);
        $this->expectErrorMessage(get_string('errorcommentdeleted', 'openstudio'));
        mod_openstudio_external::add_comment($this->studiolevels->cmid, $this->contentdata->id,
                'Test', 0, -1);
    }

    /**
     * Tests mod_openstudio_external::add_comment with deleted comment.
     */
    public function test_add_comment_with_deleted_comment(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->singleentrydata = $this->generator->generate_single_data_array();
        $this->contentdata = $this->generator->generate_content_data(
                $this->studiolevels, $this->users->students->one->id, $this->singleentrydata);

        $this->setUser($this->users->students->one);
        // Test create a comment.
        $comment = mod_openstudio_external::add_comment($this->studiolevels->cmid, $this->contentdata->id,
                'Test');
        $this->assertArrayHasKey('commentid', $comment);
        $this->assertIsInt($comment['commentid']);

        // Delete comment.
        $DB->execute("UPDATE {openstudio_comments}
                            SET deletedby = ?, deletedtime = ?
                          WHERE id = ?
                 ", [$this->users->students->one->id, time(), $comment['commentid']]);

        // User 2 login and tries to reply to deleted comment of user 1.
        $this->setUser($this->users->students->two);
        $this->expectErrorMessage(get_string('errorcommentdeleted', 'openstudio'));
        mod_openstudio_external::add_comment($this->studiolevels->cmid, $this->contentdata->id,
                'Test', 0, $comment['commentid']);
    }
}
