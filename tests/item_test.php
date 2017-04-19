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
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

class mod_openstudio_item_testcase extends advanced_testcase  {

    private $course;
    private $generator; // Contains mod_openstudio specific data generator functions.
    private $studiolevels; // Generic studio instance with no levels or slots.
    private $totalcontents;
    private $pinboardcontents;
    private $file;
    private $users;
    private $contents;
    private $contentversions;
    private $items;

    protected function setUp() {
        global $DB, $CFG;
        $this->resetAfterTest(true);
        $teacherroleid = 3;
        $studentroleid = 5;
        $this->totalcontents = 24; // This is what the scripts below create for ONE CMID.
        $this->pinboardcontents = 3; // This is what the scripts below create for ONE CMID.

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new stdClass();
        $this->users->students = new stdClass();
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
        $this->users->teachers = new stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                array('email' => 'teacher1@ouunittest.com', 'username' => 'teacher1'));

        // Enroll our students and teacher (users) in the course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->one->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->one->id, $this->course->id, $teacherroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->two->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->three->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->four->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->five->id, $this->course->id, $studentroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance(array('course' => $this->course->id, 'enablefolders' => 1));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);

        $this->contents = new stdClass();

        // 2 contents containing files.
        $file1 = $this->create_file($CFG->dirroot . '/mod/openstudio/tests/importfiles/test1.jpg');
        $this->contents->file1 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_IMAGE,
                'test1.jpg',
                $this->users->students->one->id,
                $file1->get_itemid());
        $this->contents->file1->id = $DB->insert_record('openstudio_contents', $this->contents->file1);
        $file2 = $this->create_file($CFG->dirroot . '/mod/openstudio/tests/importfiles/test2.jpg');
        $this->contents->file2 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_IMAGE,
                'test2.jpg',
                $this->users->students->one->id,
                $file2->get_itemid());
        $this->contents->file2->id = $DB->insert_record('openstudio_contents', $this->contents->file2);

        // 2 contents containing web links.
        $this->contents->web1 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_URL,
                'http://bbc.co.uk',
                $this->users->students->one->id);
        $this->contents->web1->id = $DB->insert_record('openstudio_contents', $this->contents->web1);

        $this->contents->web2 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_URL,
                'http://open.ac.uk',
                $this->users->students->one->id);
        $this->contents->web2->id = $DB->insert_record('openstudio_contents', $this->contents->web2);

        // 2 contents containing embed code.
        $this->contents->embed1 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_URL_VIDEO,
                'https://www.youtube.com/watch?v=9bZkp7q19f0',
                $this->users->students->one->id);
        $this->contents->embed1->id = $DB->insert_record('openstudio_contents', $this->contents->embed1);

        $this->contents->embed2 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_URL_VIDEO,
                'https://www.youtube.com/watch?v=8U_GEa4bM1M',
                $this->users->students->one->id);
        $this->contents->embed2->id = $DB->insert_record('openstudio_contents', $this->contents->embed2);

        // 2 empty contents.
        $this->contents->empty1 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_NONE, '',
                $this->users->students->one->id);
        $this->contents->empty1->id = $DB->insert_record('openstudio_contents', $this->contents->empty1);

        $this->contents->empty2 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_NONE, '',
                $this->users->students->one->id);
        $this->contents->empty2->id = $DB->insert_record('openstudio_contents', $this->contents->empty2);

        // A content containing duplicate content.
        $this->contents->dup1 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_URL_VIDEO,
                'https://www.youtube.com/watch?v=9bZkp7q19f0',
                $this->users->students->two->id);
        $this->contents->dup1->id = $DB->insert_record('openstudio_contents', $this->contents->dup1);

        $this->contents->web3 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_URL,
                'http://example.com',
                $this->users->students->one->id);
        $this->contents->web3->id = $DB->insert_record('openstudio_contents', $this->contents->web3);

        $this->contents->web4 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_URL,
                'http://example.co.uk',
                $this->users->students->one->id);
        $this->contents->web4->id = $DB->insert_record('openstudio_contents', $this->contents->web4);

        $this->contentversions = new stdClass();
        // Old version of a content, with item record attached to current content.
        $contentrecord = mod_openstudio\local\api\content::get($this->contents->web3->id);
        $this->contentversions->web3 = $this->generate_version_data($contentrecord, 'http://example.ac.uk');
        $this->contentversions->web3->id = $DB->insert_record('openstudio_content_versions', $this->contentversions->web3);
        $contentitem = (object) array(
                'contenthash' => sha1($this->contentversions->web3->contenttype . ':' . $this->contentversions->web3->content),
                'containertype' => mod_openstudio\local\api\item::CONTENT,
                'containerid' => $this->contents->web3->id,
                'timeadded' => time()
        );
        $DB->insert_record('openstudio_content_items', $contentitem);

        // Old version of a content, with no item record attached to current content.
        $contentrecord = mod_openstudio\local\api\content::get($this->contents->web4->id);
        $this->contentversions->web4 = $this->generate_version_data($contentrecord, 'http://example.net');
        $this->contentversions->web4->id = $DB->insert_record('openstudio_content_versions', $this->contentversions->web4);

        // Some additional items for testing occurrence count.
        $this->items = new stdClass();
        $this->items->contents = (object) array(
                'one' => $this->generate_content_data(mod_openstudio\local\api\content::TYPE_TEXT, 'foo',
                        $this->users->students->one->id),
                'two' => $this->generate_content_data(mod_openstudio\local\api\content::TYPE_TEXT, 'foo',
                        $this->users->students->one->id),
                'three' => $this->generate_content_data(mod_openstudio\local\api\content::TYPE_TEXT, 'oof',
                        $this->users->students->one->id),
                'four' => $this->generate_content_data(mod_openstudio\local\api\content::TYPE_TEXT, 'bar',
                        $this->users->students->one->id),
                'five' => $this->generate_content_data(mod_openstudio\local\api\content::TYPE_TEXT, 'bad',
                        $this->users->students->one->id)
        );
        $this->items->contents->one->id = $DB->insert_record('openstudio_contents', $this->items->contents->one);
        $this->items->contents->two->id = $DB->insert_record('openstudio_contents', $this->items->contents->two);
        $this->items->contents->three->id = $DB->insert_record('openstudio_contents', $this->items->contents->three);
        $this->items->contents->four->id = $DB->insert_record('openstudio_contents', $this->items->contents->four);
        $this->items->contents->five->id = $DB->insert_record('openstudio_contents', $this->items->contents->five);

        $this->items->versions = (object) array(
                'three' => $this->generate_version_data($this->items->contents->three, 'foo'),
                'five' => $this->generate_version_data($this->items->contents->five, 'baz')
        );
        $this->items->versions->three->id = $DB->insert_record('openstudio_content_versions', $this->items->versions->three);
        $this->items->versions->five->id = $DB->insert_record('openstudio_content_versions', $this->items->versions->five);

        $this->items->one = (object) array(
                'containerid' => $this->items->contents->one->id,
                'containertype' => mod_openstudio\local\api\item::CONTENT,
                'contenthash' => sha1('foo'),
                'timeadded' => time(),
        );
        $this->items->two = (object) array(
                'containerid' => $this->items->contents->two->id,
                'containertype' => mod_openstudio\local\api\item::CONTENT,
                'contenthash' => sha1('foo'),
                'timeadded' => time(),
        );
        $this->items->three = (object) array(
                'containerid' => $this->items->versions->three->id,
                'containertype' => mod_openstudio\local\api\item::VERSION,
                'contenthash' => sha1('foo'),
                'timeadded' => time(),
        );
        $this->items->four = (object) array(
                'containerid' => $this->items->contents->four->id,
                'containertype' => mod_openstudio\local\api\item::CONTENT,
                'contenthash' => sha1('bar'),
                'timeadded' => time(),
        );
        $this->items->five = (object) array(
                'containerid' => $this->items->versions->five->id,
                'containertype' => mod_openstudio\local\api\item::VERSION,
                'contenthash' => sha1('baz'),
                'timeadded' => time(),
        );
        $this->items->one->id = $DB->insert_record('openstudio_content_items', $this->items->one);
        $this->items->two->id = $DB->insert_record('openstudio_content_items', $this->items->two);
        $this->items->three->id = $DB->insert_record('openstudio_content_items', $this->items->three);
        $this->items->four->id = $DB->insert_record('openstudio_content_items', $this->items->four);
        $this->items->five->id = $DB->insert_record('openstudio_content_items', $this->items->five);

        // Some additional contents with attached items for testing occurences with user data.
        $this->contents->web5 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_URL,
                'http://example.org',
                $this->users->students->one->id);
        $this->contents->web5->id = $DB->insert_record('openstudio_contents', $this->contents->web5);
        $contentrecord = mod_openstudio\local\api\content::get($this->contents->web5->id);
        $contentitem = (object) array(
                'contenthash' => sha1($contentrecord->contenttype . ':' . $contentrecord->content),
                'containertype' => mod_openstudio\local\api\item::CONTENT,
                'containerid' => $this->contents->web5->id,
                'timeadded' => time()
        );
        $DB->insert_record('openstudio_content_items', $contentitem);

        $this->contents->web6 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_URL,
                'http://example.org',
                $this->users->students->two->id);
        $this->contents->web6->id = $DB->insert_record('openstudio_contents', $this->contents->web6);
        $contentrecord = mod_openstudio\local\api\content::get($this->contents->web6->id);
        $contentitem = (object) array(
                'contenthash' => sha1($contentrecord->contenttype . ':' . $contentrecord->content),
                'containertype' => mod_openstudio\local\api\item::CONTENT,
                'containerid' => $this->contents->web6->id,
                'timeadded' => time() + 1
        );

        $DB->insert_record('openstudio_content_items', $contentitem);
        $this->contents->web7 = $this->generate_content_data(mod_openstudio\local\api\content::TYPE_URL,
                'http://example.org.uk',
                $this->users->students->three->id);
        $this->contents->web7->id = $DB->insert_record('openstudio_contents', $this->contents->web7);
        $contentrecord = mod_openstudio\local\api\content::get($this->contents->web7->id);
        $contentitem = (object) array(
                'contenthash' => sha1($contentrecord->contenttype . ':' . $contentrecord->content),
                'containertype' => mod_openstudio\local\api\item::CONTENT,
                'containerid' => $this->contents->web7->id,
                'timeadded' => time() + 2
        );
        $DB->insert_record('openstudio_content_items', $contentitem);
        $this->contentversions->web7 = $this->generate_version_data($contentrecord, 'http://example.org');
        $this->contentversions->web7->id = $DB->insert_record('openstudio_content_versions', $this->contentversions->web7);
        $contentitem = (object) array(
                'contenthash' => sha1($this->contentversions->web7->contenttype . ':' . $this->contentversions->web7->content),
                'containertype' => mod_openstudio\local\api\item::VERSION,
                'containerid' => $this->contentversions->web7->id,
                'timeadded' => time() - 10
        );
        $DB->insert_record('openstudio_content_items', $contentitem);

    }

    protected function generate_content_data($contenttype, $content, $userid, $fileid = null) {
        return (object) array(
                'openstudioid' => $this->studiolevels->id,
                'levelid' => 0,
                'levelcontainer' => 0,
                'contenttype' => $contenttype,
                'mimetype' => '',
                'content' => $content,
                'fileid' => $fileid,
                'name' => 'Lorem Ipsum ' . random_string(),
                'description' => random_string(),
                'textformat' => 0,
                'commentformat' => 0,
                'ownership' => mod_openstudio\local\api\content::OWNERSHIP_MYOWNWORK,
                'ownershipdetail' => '',
                'showextradata' => false,
                'visibility' => mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $userid,
                'timemodified' => time(),
        );
    }

    protected function generate_version_data($contentrecord, $content) {
        return (object) array(
                'contentid' => $contentrecord->id,
                'contenttype' => $contentrecord->contenttype,
                'mimetype' => $contentrecord->mimetype,
                'content' => $content,
                'fileid' => $contentrecord->fileid,
                'name' => $contentrecord->name . random_string(),
                'description' => $contentrecord->description,
                'textformat' => $contentrecord->textformat,
                'deletedby' => null,
                'deletedtime' => null,
                'timemodified' => time()
        );
    }

    protected function create_file($path) {
        static $itemid;
        $itemid++;
        $this->file = new stdClass();
        $this->file->filearea = 'content';
        $this->file->filename = basename($path);
        $this->file->filepath = '/';
        $this->file->sortorder = 0;
        $this->file->author = $this->users->students->one->firstname . ' ' . $this->users->students->one->lastname;
        $this->file->license = 'allrightsreserved';
        $this->file->datemodified = time();
        $this->file->datecreated = time();
        $this->file->component = 'mod_openstudio';
        $this->file->itemid = $itemid;
        $context = context_module::instance($this->studiolevels->cmid);
        $this->file->contextid = $context->id;
        $fs = get_file_storage();
        return $fs->create_file_from_pathname($this->file, $path);

    }

    protected function tearDown() {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
    }

    protected function get_nonexistant_id($table) {
        global $DB;
        $randomid = mt_rand();
        while ($DB->record_exists($table, array('id' => $randomid))) {
            $randomid = mt_rand();
        }
        return $randomid;
    }

    public function test_studio_api_item_log() {
        global $DB;

        $ids = array(
            $this->contents->file1->id,
            $this->contents->file2->id,
            $this->contents->web1->id,
            $this->contents->web2->id,
            $this->contents->embed1->id,
            $this->contents->embed2->id
        );
        // Hash all the unique contents.
        foreach ($ids as $id) {
            $this->assertNotEquals(mod_openstudio\local\api\item::log($id), false);
        }

        // Verify that all items are created with unique hashes.
        list($usql, $params) = $DB->get_in_or_equal($ids);
        $params = array_merge($params, array('containertype' => mod_openstudio\local\api\item::CONTENT));
        $items = $DB->get_records_select('openstudio_content_items', 'containerid ' . $usql, $params);
        $this->assertEquals(6, count($items));

        $foundhashes = array();
        foreach ($items as $item) {
            $this->assertNotContains($item->contenthash, $foundhashes);
            $foundhashes[] = $item->contenthash;
        }

        // Hash empty contents.
        $emptyids = array(
            $this->contents->empty1->id,
            $this->contents->empty2->id
        );

        foreach ($emptyids as $id) {
            $this->assertFalse(mod_openstudio\local\api\item::log($id));
        }

        // Verify that item records where not created.
        list($usql, $params) = $DB->get_in_or_equal($emptyids);
        $params = array_merge($params, array('containertype' => mod_openstudio\local\api\item::CONTENT));
        $this->assertFalse($DB->record_exists_select('openstudio_content_items', 'containerid ' . $usql, $params));

        // Create a duplicate hash.
        $dupid = $this->contents->dup1->id;

        $this->assertNotEquals(mod_openstudio\local\api\item::log($dupid), false);

        $params = array('containerid' => $dupid, 'containertype' => mod_openstudio\local\api\item::CONTENT);
        $dupitem = $DB->get_record('openstudio_content_items', $params);
        $this->assertContains($dupitem->contenthash, $foundhashes);

        try {
            // Attempt to log an item for non-existant content.
            mod_openstudio\local\api\item::log($this->get_nonexistant_id('openstudio_contents'));
        } catch (coding_exception $expected1) {
            try {
                // Attempt to log a second item for a content.
                mod_openstudio\local\api\item::log($this->contents->file1->id);
            } catch (coding_exception $expected2) {
                return;
            }
        }

        $this->fail('Did not catch expected exception');
    }

    public function test_studio_api_item_toversion() {
        global $DB;
        $contentitemparams = array(
            'containerid' => $this->contents->web3->id,
            'containertype' => mod_openstudio\local\api\item::CONTENT
        );
        $versionitemparams = array(
            'containerid' => $this->contentversions->web3->id,
            'containertype' => mod_openstudio\local\api\item::VERSION
        );
        $contentitem = $DB->get_record('openstudio_content_items', $contentitemparams);
        $this->assertFalse($DB->record_exists('openstudio_content_items', $versionitemparams));

        // Move content item to version item.
        mod_openstudio\local\api\item::toversion($this->contents->web3->id, $this->contentversions->web3->id);

        $versionitemparams['contenthash'] = $contentitem->contenthash;
        $this->assertTrue($DB->record_exists('openstudio_content_items', $versionitemparams));
        $this->assertFalse($DB->record_exists('openstudio_content_items', $contentitemparams));

        // Try to move an item that doesn't exist, should fail silently.
        mod_openstudio\local\api\item::toversion($this->contents->web4->id, $this->contentversions->web4->id);

        try {
            // Try to move item for non-existant content.
            mod_openstudio\local\api\item::toversion(
                    $this->get_nonexistant_id('openstudio_contents'), $this->contentversions->web3->id);
        } catch (coding_exception $expected1) {
            try {
                // Try to move item to non-existant version.
                mod_openstudio\local\api\item::toversion(
                        $this->contents->web3->id, $this->get_nonexistant_id('openstudio_content_versions'));
            } catch (coding_exception $expected2) {
                try {
                    // Try to create a duplicate version item.
                    mod_openstudio\local\api\item::toversion($this->contents->web3->id, $this->contentversions->web3->id);
                } catch (coding_exception $expected3) {
                    return;
                }
            }
        }

        $this->fail('Did not catch expected exception');

    }

    public function test_studio_api_item_get_occurrences() {
        $hash = sha1(mod_openstudio\local\api\content::TYPE_URL . ':' . $this->contents->web5->content);
        $occurences = mod_openstudio\local\api\item::get_occurences($hash);

        // NOTE: we use base64_encode as the hash value may contain funny characters which
        // causes PHPUnit to complain when running which results in a false error report.

        $this->assertEquals(2, count($occurences));
        $foundusernames = array();
        foreach ($occurences as $occurence) {
            $foundusernames[] = base64_encode(fullname($occurence));
        }
        $this->assertContains(base64_encode(fullname($this->users->students->one)), $foundusernames);
        $this->assertContains(base64_encode(fullname($this->users->students->two)), $foundusernames);
        $this->assertNotContains(base64_encode(fullname($this->users->students->three)), $foundusernames);
        $firstoccurence = reset($occurences);
        $this->assertEquals($firstoccurence->containertype, mod_openstudio\local\api\item::CONTENT);
        $this->assertEquals($firstoccurence->containerid, $this->contents->web5->id);

        $occurences = mod_openstudio\local\api\item::get_occurences($hash, false);
        $this->assertEquals(3, count($occurences));
        $foundusernames = array();
        foreach ($occurences as $occurence) {
            $foundusernames[] = base64_encode(fullname($occurence));
        }
        $this->assertContains(base64_encode(fullname($this->users->students->one)), $foundusernames);
        $this->assertContains(base64_encode(fullname($this->users->students->two)), $foundusernames);
        $this->assertContains(base64_encode(fullname($this->users->students->three)), $foundusernames);

        $firstoccurence = reset($occurences);
        $this->assertEquals($firstoccurence->containertype, mod_openstudio\local\api\item::VERSION);
        $this->assertEquals($firstoccurence->containerid, $this->contentversions->web7->id);
    }

    public function test_studio_api_item_count_occurrences() {
        $this->assertEquals(2, mod_openstudio\local\api\item::count_occurences($this->items->one->contenthash));
        $this->assertEquals(3, mod_openstudio\local\api\item::count_occurences($this->items->one->contenthash, 0, false));
        $this->assertEquals(1, mod_openstudio\local\api\item::count_occurences($this->items->four->contenthash));
        $this->assertEquals(1, mod_openstudio\local\api\item::count_occurences($this->items->four->contenthash, 0, false));
        $this->assertEquals(0, mod_openstudio\local\api\item::count_occurences($this->items->five->contenthash));
        $this->assertEquals(1, mod_openstudio\local\api\item::count_occurences($this->items->five->contenthash, 0, false));
    }


}