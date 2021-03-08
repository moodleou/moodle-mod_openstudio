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

class tags_testcase extends \advanced_testcase {

    protected $users;
    protected $course;
    protected $generator; // Contains mod_openstudio specific data generator functions.
    protected $studiolevels; // Generic studio instance with no levels or slots.
    protected $singleentrydata;
    protected $contentid;
    private $tags;

    /**
     * Sets up our fixtures.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        $studentroleid = 5;

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                ['email' => 'student1@ouunittest.com', 'username' => 'student1']);

        // Enroll our students and teacher (users) in the course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->one->id, $this->course->id, $studentroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance(['course' => $this->course->id, 'idnumber' => 'OS1']);
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
        $this->contentid = $this->generator->create_contents([
            'openstudio' => 'OS1',
            'name' => random_string(),
            'description' => random_string(),
            'userid' => $this->users->students->one->id
        ]);
        $this->tags = [strtolower(random_string()), strtolower(random_string()), strtolower(random_string())];
    }

    protected function tearDown(): void {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';

    }

    /**
     * Test that tags are set correctly.
     */
    public function test_set() {
        $this->assertCount(0, \core_tag_tag::get_item_tags('mod_openstudio', 'openstudio_contents', $this->contentid));
        \mod_openstudio\local\api\tags::set($this->contentid, $this->tags);
        $settags = \core_tag_tag::get_item_tags('mod_openstudio', 'openstudio_contents', $this->contentid);
        $this->assertCount(count($this->tags), $settags);
        // Check that the returned tags are those set for the content, and no more.
        foreach ($settags as $settag) {
            $key = array_search($settag->name, $this->tags);
            $this->assertNotFalse($key);
            unset($this->tags[$key]);
        }
        $this->assertCount(0, $this->tags);
    }

    /**
     * Test that tags are set correctly when passed as a comma-separated string.
     */
    public function test_set_by_string() {
        $tagstring = implode(',', $this->tags);
        $this->assertCount(0, \core_tag_tag::get_item_tags('mod_openstudio', 'openstudio_contents', $this->contentid));
        \mod_openstudio\local\api\tags::set($this->contentid, $tagstring);
        $settags = \core_tag_tag::get_item_tags('mod_openstudio', 'openstudio_contents', $this->contentid);
        $this->assertCount(count($this->tags), $settags);
        // Check that the returned tags are those set for the content, and no more.
        foreach ($settags as $settag) {
            $key = array_search($settag->name, $this->tags);
            $this->assertNotFalse($key);
            unset($this->tags[$key]);
        }
        $this->assertCount(0, $this->tags);
    }

    /**
     * Test that passing an empty array of tags doesn't set any.
     */
    public function test_set_no_tags() {
        $this->assertCount(0, \core_tag_tag::get_item_tags('mod_openstudio', 'openstudio_contents', $this->contentid));
        \mod_openstudio\local\api\tags::set($this->contentid, []);
        $this->assertCount(0, \core_tag_tag::get_item_tags('mod_openstudio', 'openstudio_contents', $this->contentid));
    }

    /**
     * Test that we get an exception if we try to set tags on a non-existant content post.
     */
    public function test_set_no_content() {
        $this->expectException('dml_missing_record_exception');
        \mod_openstudio\local\api\tags::set($this->contentid + 1, [random_string()]);
    }

    /**
     * Test that tags are removed correctly.
     */
    public function test_remove() {
        $context = \context_module::instance($this->studiolevels->cmid);
        \core_tag_tag::set_item_tags('mod_openstudio', 'openstudio_contents', $this->contentid, $context, $this->tags);
        $this->assertCount(
                count($this->tags), \core_tag_tag::get_item_tags('mod_openstudio', 'openstudio_contents', $this->contentid));

        \mod_openstudio\local\api\tags::remove($this->contentid);
        $this->assertCount(0, \core_tag_tag::get_item_tags('mod_openstudio', 'openstudio_contents', $this->contentid));
    }

    public function test_remove_no_content() {
        $this->expectException('dml_missing_record_exception');
        \mod_openstudio\local\api\tags::remove($this->contentid + 1);
    }

    public function test_get() {
        $context = \context_module::instance($this->studiolevels->cmid);
        \core_tag_tag::set_item_tags('mod_openstudio', 'openstudio_contents', $this->contentid, $context, $this->tags);

        $settags = \mod_openstudio\local\api\tags::get($this->contentid);
        $this->assertCount(count($this->tags), $settags);

        foreach ($settags as $settag) {
            $key = array_search($settag->name, $this->tags);
            $this->assertNotFalse($key);
            unset($this->tags[$key]);
        }
        $this->assertCount(0, $this->tags);
    }

    public function test_get_no_tags() {
        $this->assertEmpty(\mod_openstudio\local\api\tags::get($this->contentid));
    }

    public function test_get_no_content() {
        $this->expectException('dml_missing_record_exception');
        \mod_openstudio\local\api\tags::get($this->contentid + 1);
    }

}
