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
 * Unit tests for filesystem API
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class mod_openstudio_filesystem_testcase extends advanced_testcase {

    protected $users;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studios;
    protected $contents;
    protected $contexts;

    public function setUp() {
        $this->resetAfterTest(true);
        $studentroleid = 5;

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create user.
        $this->users = new stdClass();
        $this->users->students = new stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                array('email' => 'student1@ouunittest.com', 'username' => 'student1'));
        $this->users->students->two = $this->getDataGenerator()->create_user(
                array('email' => 'student2@ouunittest.com', 'username' => 'student2'));

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Enroll our student in the course.
        $this->getDataGenerator()->enrol_user($this->users->students->one->id, $this->course->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->students->two->id, $this->course->id,
                $studentroleid, 'manual');

        // Create generic studios.
        $this->studios = new stdClass();
        $this->studios->one = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS1'));
        $this->studios->two = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS2'));
        $this->contexts = new stdClass();
        $this->contexts->one = context_module::instance($this->studios->one->cmid);
        $this->contexts->two = context_module::instance($this->studios->two->cmid);

        $this->contents = new stdClass();
        $this->contents->one = $this->generator->create_contents([
            'openstudio' => 'OS1',
            'name' => 'active1',
            'description' => random_string(),
            'userid' => $this->users->students->one->id,
            'file' => 'mod/openstudio/tests/importfiles/test1.jpg'
        ]);
        $this->contents->two = $this->generator->create_contents([
            'openstudio' => 'OS1',
            'name' => 'active2',
            'description' => random_string(),
            'userid' => $this->users->students->two->id,
            'file' => 'mod/openstudio/tests/importfiles/test2.jpg'
        ]);
        $this->contents->three = $this->generator->create_contents([
            'openstudio' => 'OS1',
            'name' => 'deleted1',
            'description' => random_string(),
            'userid' => $this->users->students->one->id,
            'file' => 'mod/openstudio/tests/importfiles/test3.jpg',
            'deletedby' => $this->users->students->one->id,
            'deletedtime' => (new DateTime('8 days ago', core_date::get_server_timezone_object()))->getTimestamp()
        ]);
        $this->contents->four = $this->generator->create_contents([
            'openstudio' => 'OS1',
            'name' => 'deleted2',
            'description' => random_string(),
            'userid' => $this->users->students->two->id,
            'file' => 'mod/openstudio/tests/importfiles/test4.jpg',
            'deletedby' => $this->users->students->two->id,
            'deletedtime' => time()
        ]);
        $this->contents->five = $this->generator->create_contents([
            'openstudio' => 'OS2',
            'name' => 'active3',
            'description' => random_string(),
            'userid' => $this->users->students->one->id,
            'file' => 'mod/openstudio/tests/importfiles/test1.jpg'
        ]);
        $this->contents->six = $this->generator->create_contents([
            'openstudio' => 'OS2',
            'name' => 'deleted3',
            'description' => random_string(),
            'userid' => $this->users->students->one->id,
            'file' => 'mod/openstudio/tests/importfiles/test.ods',
            'deletedby' => $this->users->students->one->id,
            'deletedtime' => (new DateTime('8 days ago', core_date::get_server_timezone_object()))->getTimestamp()
        ]);
    }

    /**
     * Check that all files are removed from the specified studio instance.
     */
    public function test_remove_content_files() {
        $fs = get_file_storage();
        $this->assertCount(4, $fs->get_area_files($this->contexts->one->id, 'mod_openstudio', 'content', false, 'itemid', false));
        $this->assertCount(
                4, $fs->get_area_files($this->contexts->one->id, 'mod_openstudio', 'contentthumbnail', false, 'itemid', false));
        mod_openstudio\local\api\filesystem::remove_content_files($this->contexts->one->id, $this->studios->one->id);
        $this->assertCount(0, $fs->get_area_files($this->contexts->one->id, 'mod_openstudio', 'content', false, 'itemid', false));
        $this->assertCount(
                0, $fs->get_area_files($this->contexts->one->id, 'mod_openstudio', 'contentthumbnail', false, 'itemid', false));
        // Check we haven't deleted files from another studio instance.
        $this->assertCount(2, $fs->get_area_files($this->contexts->two->id, 'mod_openstudio', 'content', false, 'itemid', false));
        $this->assertCount(
                1, $fs->get_area_files($this->contexts->two->id, 'mod_openstudio', 'contentthumbnail', false, 'itemid', false));
    }

    /**
     * Check that all old files are removed from all studio instances.
     */
    public function test_remove_deleted_files() {
        $fs = get_file_storage();
        $this->assertCount(4, $fs->get_area_files($this->contexts->one->id, 'mod_openstudio', 'content', false, 'itemid', false));
        $this->assertCount(
                4, $fs->get_area_files($this->contexts->one->id, 'mod_openstudio', 'contentthumbnail', false, 'itemid', false));
        mod_openstudio\local\api\filesystem::remove_deleted_files();
        // Each studio instance has 1 deleted file older than the threshold.  All other files should remain.
        $this->assertCount(3, $fs->get_area_files($this->contexts->one->id, 'mod_openstudio', 'content', false, 'itemid', false));
        $this->assertCount(
                3, $fs->get_area_files($this->contexts->one->id, 'mod_openstudio', 'contentthumbnail', false, 'itemid', false));
        $this->assertCount(1, $fs->get_area_files($this->contexts->two->id, 'mod_openstudio', 'content', false, 'itemid', false));
        $this->assertCount(
                1, $fs->get_area_files($this->contexts->two->id, 'mod_openstudio', 'contentthumbnail', false, 'itemid', false));
    }

    public function test_rrmdir() {
        $tempdir = sys_get_temp_dir() . '/openstudiotest';
        mkdir($tempdir);
        // Make a load of nested directories and files.
        for ($i = 0; $i < 5; $i++) {
            mkdir($tempdir . '/' . $i);
            touch($tempdir . '/' . $i . '/' . random_string());
            for ($j = 0; $j < 5; $j++) {
                mkdir($tempdir . '/' . $i . '/' . $j);
                touch($tempdir . '/' . $i . '/' . $j . '/' . random_string());
            }
        }
        $this->assertTrue(check_dir_exists($tempdir, false));
        $this->assertTrue(check_dir_exists($tempdir . '/2/4', false));
        mod_openstudio\local\api\filesystem::rrmdir($tempdir);
        $this->assertFalse(check_dir_exists($tempdir, false));
    }
}
