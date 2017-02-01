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
 * Unit tests for contentversion API.
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class mod_openstudio_contentversion_testcase extends advanced_testcase {

    private $users;
    private $studio;
    private $generator;
    private $content;
    private $versions;

    public function setUp() {
        $this->resetAfterTest(true);
        $studentroleid = 5;

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

        $this->versions = (object) [
            'one' => (object) [
                'name' => 'version1',
                'description' => 'this is version1',
                'content' => 'http://example.com/1',
                'contenttype' => mod_openstudio\local\api\content::TYPE_URL,
                'timemodified' => time() - 1000
            ],
            'deleted' => (object) [
                'name' => 'versiondeleted',
                'description' => 'this is versiondeleted',
                'content' => 'http://example.com/deleted',
                'contenttype' => mod_openstudio\local\api\content::TYPE_URL,
                'timemodified' => time() - 500,
                'deletedtime' => time() - 500,
                'deletedby' => $this->users->students->one->id
            ],
            'two' => (object) [
                'name' => 'version2',
                'description' => 'this is version2',
                'content' => 'http://example.com/2',
                'contenttype' => mod_openstudio\local\api\content::TYPE_URL,
                'timemodified' => time() - 300
            ],
            'three' => (object) [
                'name' => 'version3',
                'description' => 'this is version3',
                'content' => 'http://example.com/3',
                'contenttype' => mod_openstudio\local\api\content::TYPE_URL,
                'timemodified' => time() - 100
            ]
        ];

        $this->content = (object) [
            'openstudio' => 'OS1',
            'userid' => $this->users->students->one->id,
            'name' => 'version4',
            'description' => 'this is version4',
            'weblink' => 'http://example.com/4',
            'contenttype' => mod_openstudio\local\api\content::TYPE_URL
        ];

        $this->studio = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS1'));

        $this->content->id = $this->generator->create_contents($this->content);
        $this->versions->one->contentid = $this->content->id;
        $this->versions->deleted->contentid = $this->content->id;
        $this->versions->two->contentid = $this->content->id;
        $this->versions->three->contentid = $this->content->id;
        $this->versions->one->id = $this->generator->create_contentversions($this->versions->one);
        $this->versions->deleted->id = $this->generator->create_contentversions($this->versions->deleted);
        $this->versions->two->id = $this->generator->create_contentversions($this->versions->two);
        $this->versions->three->id = $this->generator->create_contentversions($this->versions->three);
    }

    public function test_get() {
        $version = mod_openstudio\local\api\contentversion::get($this->versions->two->id, $this->users->students->one->id);
        $this->assertEquals($this->versions->two->name, $version->name);
        $this->assertEquals(2, $version->versionnumber);
        $this->assertEquals(3, $version->numberofversions);
        $this->assertEquals(4, $version->numberofversionsincludingdeleted);
        $this->assertEquals(3, $version->numberofversionsnotdeleted);
        $this->assertEquals($this->versions->one->id, $version->previousversionid);
        $this->assertEquals($this->versions->three->id, $version->nextversionid);
    }

    public function test_get_including_deleted() {
        $version = mod_openstudio\local\api\contentversion::get(
                $this->versions->two->id, $this->users->students->one->id, true);
        $this->assertEquals($this->versions->two->name, $version->name);
        $this->assertEquals(3, $version->versionnumber);
        $this->assertEquals(4, $version->numberofversions);
        $this->assertEquals(4, $version->numberofversionsincludingdeleted);
        $this->assertEquals(3, $version->numberofversionsnotdeleted);
        $this->assertEquals($this->versions->deleted->id, $version->previousversionid);
        $this->assertEquals($this->versions->three->id, $version->nextversionid);
    }

    public function test_count() {
        $this->assertEquals(3, mod_openstudio\local\api\contentversion::count($this->content->id));
    }

    public function test_count_including_deleted() {
        $this->assertEquals(4, mod_openstudio\local\api\contentversion::count($this->content->id, true));
    }

    public function test_delete_oldest() {
        $this->assertTrue(mod_openstudio\local\api\contentversion::delete_oldest(
                $this->content->id, $this->users->students->one->id));

        $version = mod_openstudio\local\api\contentversion::get($this->versions->one->id, $this->users->students->one->id);
        $this->assertNotEmpty($version->deletedtime);
        $this->assertEquals($this->users->students->one->id, $version->deletedby);
        $this->assertEquals(2, $version->numberofversionsnotdeleted);
        $this->assertEquals(4, $version->numberofversionsincludingdeleted);
    }

    public function test_get_content_and_versions() {
        $content = mod_openstudio\local\api\contentversion::get_content_and_versions(
                $this->content->id, $this->users->students->one->id);
        $this->assertEquals($this->content->name, $content->contentdata->name);
        $this->assertCount(3, $content->contentversions);
    }

    public function test_get_content_and_versions_including_deleted() {
        $content = mod_openstudio\local\api\contentversion::get_content_and_versions(
                $this->content->id, $this->users->students->one->id, true);
        $this->assertEquals($this->content->name, $content->contentdata->name);
        $this->assertCount(4, $content->contentversions);
    }
}

