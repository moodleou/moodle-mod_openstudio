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

class folder_testcase extends \advanced_testcase {

    private $users;
    private $permissions;
    private $course;
    private $generator;
    private $studiolevels;
    private $contents;
    private $folders;
    private $foldercontents;
    private $contenttemplatecount = 0;
    private $foldertemplate;
    private $contentlevelid;
    private $templatecontents;
    private $othertemplate;
    private $templatedfolders;
    private $templatedcontents;
    private $templatedfoldercontents;
    private $collectables;
    private $altstudio;
    private $provenance;
    private $provenancefolder;
    private $provenancefolder2;

    /**
     * Sets up our fixtures.
     */
    protected function setUp() {
        global $DB;
        $this->resetAfterTest(true);
        $teacherroleid = 3;
        $studentroleid = 5;
        $this->permissions = (object) [
                'pinboardfolderlimit' => \mod_openstudio\local\util\defaults::MAXPINBOARDFOLDERSCONTENTS
        ];

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                ['email' => 'student1@ouunittest.com', 'username' => 'student1']);
        $this->users->students->two = $this->getDataGenerator()->create_user(
                ['email' => 'student2@ouunittest.com', 'username' => 'student2']);
        $this->users->students->three = $this->getDataGenerator()->create_user(
                ['email' => 'student3@ouunittest.com', 'username' => 'student3']);
        $this->users->students->four = $this->getDataGenerator()->create_user(
                ['email' => 'student4@ouunittest.com', 'username' => 'student4']);
        $this->users->students->five = $this->getDataGenerator()->create_user(
                ['email' => 'student5@ouunittest.com', 'username' => 'student5']);
        $this->users->teachers = new \stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                ['email' => 'teacher1@ouunittest.com', 'username' => 'teacher1']);

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
        $studiodata = ['course' => $this->course->id, 'enablefolders' => 1, 'idnumber' => 'OS1'];
        $this->studiolevels = $this->generator->create_instance($studiodata);
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);

        // Create slots containing sets.
        $this->folders = [];
        for ($i = 0; $i < 2; $i++) {
            $this->folders[$i] = $this->generate_set_data();
            $this->folders[$i]->id = \mod_openstudio\local\api\content::create_in_pinboard($this->studiolevels->id,
                    $this->users->students->one->id,
                    $this->folders[$i]);
            $this->folders[$i] = $this->generate_set_data();
            $this->folders[$i]->id = \mod_openstudio\local\api\content::create_in_pinboard($this->studiolevels->id,
                    $this->users->students->one->id,
                    $this->folders[$i]);
        }

        // Add 2 slots to the set.
        $this->contents = [];
        $this->foldercontents = [];
        for ($i = 0; $i < 2; $i++) {
            $this->contents[$i] = $this->generate_slot_data();
            $this->contents[$i]->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                    $this->users->students->one->id,
                    0, 0, $this->contents[$i]);
            $this->foldercontents[$i] = (object) [
                    'folderid'        => $this->folders[0]->id,
                    'contentid'       => $this->contents[$i]->id,
                    'timemodified' => time(),
                    'contentorder'    => $i + 1,
                    'status'       => \mod_openstudio\local\api\levels::ACTIVE
            ];
            $this->foldercontents[$i]->id = $DB->insert_record('openstudio_folder_contents', $this->foldercontents[$i]);
        }

        // Add a slot that should be in the set, but hasn't been added to it.
        $extraslot = $this->generate_slot_data();
        $extraslot->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $extraslot);
        $this->contents[] = $extraslot;

        // Add another slot that's not in the set.
        $outsideslot = $this->generate_slot_data();
        $outsideslot->visibility = \mod_openstudio\local\api\content::VISIBILITY_MODULE;
        $outsideslot->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $outsideslot);
        $this->contents[] = $outsideslot;

        // Create set templates.
        $activitylevels = end($this->studiolevels->leveldata['contentslevels']);
        $slotlevels = end($activitylevels);
        $this->contentlevelid = end($slotlevels);

        $settemplates = [
                $this->generate_set_template_data(),
                $this->generate_set_template_data(),
                $this->generate_set_template_data()
        ];

        $settemplates[0]->levelid = $this->contentlevelid;
        // This is active and in the level where we'll create slots.
        $this->foldertemplate = $settemplates[0];
        $this->foldertemplate->id = $DB->insert_record('openstudio_folder_templates', $settemplates[0]);

        // This is in the level, but has been deleted.
        $settemplates[1]->levelid = $this->contentlevelid;
        $settemplates[1]->status = \mod_openstudio\local\api\levels::SOFT_DELETED;
        $DB->insert_record('openstudio_folder_templates', $settemplates[1]);

        // This is active, but in a different level.
        $otherlevelid = prev($slotlevels);
        $settemplate[2] = (object) ['levelid' => $otherlevelid];
        $this->othertemplate = $settemplates[2];
        $this->othertemplate->id = $DB->insert_record('openstudio_folder_templates', $settemplates[2]);

        // Create template slots.
        $this->templatecontents = [
                $this->generate_slot_template_data(),
                $this->generate_slot_template_data(),
                $this->generate_slot_template_data(),
                $this->generate_slot_template_data()
        ];

        // 2 template slots in the template and active.
        $this->templatecontents[0]->foldertemplateid = $this->foldertemplate->id;
        $this->templatecontents[1]->foldertemplateid = $this->foldertemplate->id;
        // 1 in the template but deleted.
        $this->templatecontents[2]->foldertemplateid = $this->foldertemplate->id;
        $this->templatecontents[2]->status = \mod_openstudio\local\api\levels::SOFT_DELETED;
        // 1 active but in another template.
        $this->templatecontents[3]->foldertemplateid = $this->othertemplate->id;

        $this->templatecontents[0]->id = $DB->insert_record('openstudio_content_templates', $this->templatecontents[0]);
        $this->templatecontents[1]->id = $DB->insert_record('openstudio_content_templates', $this->templatecontents[1]);
        $this->templatecontents[2]->id = $DB->insert_record('openstudio_content_templates', $this->templatecontents[2]);
        $this->templatecontents[3]->id = $DB->insert_record('openstudio_content_templates', $this->templatecontents[3]);

        $this->templatedfolders = [];
        $this->templatedfolders['full'] = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                3, $this->contentlevelid, $this->generate_set_data());

        $this->templatedfolders['under'] = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->two->id,
                3, $this->contentlevelid, $this->generate_set_data());

        $this->templatedfolders['over'] = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->three->id,
                3, $this->contentlevelid, $this->generate_set_data());

        $this->templatedfolders['empty'] = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->four->id,
                3, $this->contentlevelid, $this->generate_set_data());
        $this->templatedfolders['offset'] = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->five->id,
                3, $this->contentlevelid, $this->generate_set_data());

        // Add 2 slots to 1 templated set.
        for ($i = 0; $i < 2; $i++) {
            $this->templatedcontents[$i] = $this->generate_slot_data();
            $this->templatedcontents[$i]->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                    $this->users->students->one->id,
                    0, 0, $this->templatedcontents[$i]);
            $this->templatedfoldercontents[$i] = (object) [
                    'folderid'             => $this->templatedfolders['full'],
                    'contentid'            => $this->templatedcontents[$i]->id,
                    'foldercontenttemplateid' => $this->templatecontents[$i]->id,
                    'contentorder'         => $this->templatecontents[$i]->contentorder,
                    'timemodified'      => time()
            ];
            $this->templatedfoldercontents[$i]->id = $DB->insert_record(
                    'openstudio_folder_contents', $this->templatedfoldercontents[$i]);
        }

        // Add 1 slot to another.
        $i = count($this->templatedcontents);
        $this->templatedcontents[$i] = $this->generate_slot_data();
        $this->templatedcontents[$i]->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->templatedcontents[$i]);
        $this->templatedfoldercontents[] = (object) [
                'folderid'             => $this->templatedfolders['under'],
                'contentid'            => $this->templatedcontents[$i]->id,
                'foldercontenttemplateid' => $this->templatecontents[0]->id,
                'contentorder'         => $this->templatecontents[0]->contentorder,
                'timemodified'      => time()
        ];
        $this->templatedfoldercontents[$i]->id = $DB->insert_record(
                'openstudio_folder_contents', $this->templatedfoldercontents[$i]);

        // Add 3 slots to another.
        for ($i = 0; $i < 3; $i++) {
            $j = $i + count($this->templatedcontents);
            $this->templatedcontents[$j] = $this->generate_slot_data();
            $this->templatedcontents[$j]->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                    $this->users->students->one->id,
                    0, 0, $this->templatedcontents[$j]);
            $this->templatedfoldercontents[$j] = (object) [
                    'folderid'             => $this->templatedfolders['over'],
                    'contentid'            => $this->templatedcontents[$j]->id,
                    'timemodified'      => time()
            ];
            if ($i < 2) {
                $this->templatedfoldercontents[$j]->foldercontenttemplateid = $this->templatecontents[$i]->id;
                $this->templatedfoldercontents[$j]->contentorder = $this->templatecontents[$i]->contentorder;
            } else {
                $this->templatedfoldercontents[$j]->contentorder = $i + 1;
            }
            $this->templatedfoldercontents[$j]->id = $DB->insert_record(
                    'openstudio_folder_contents', $this->templatedfoldercontents[$j]);
        }

        // Leave one blank.

        // Add 2 slots to one, but leave one templated position unfilled.
        for ($i = 1; $i < 3; $i++) {
            $j = $i + count($this->templatedcontents);
            $this->templatedcontents[$j] = $this->generate_slot_data();
            $this->templatedcontents[$j]->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                    $this->users->students->one->id,
                    0, 0, $this->templatedcontents[$j]);
            $this->templatedfoldercontents[$j] = (object) [
                    'folderid'         => $this->templatedfolders['offset'],
                    'contentid'        => $this->templatedcontents[$j]->id,
                    'timemodified'  => time()
            ];
            $templateslot = $this->templatecontents[$i];
            $templateset = reset($settemplates);
            if (isset($templateslot->id)
                    && $templateslot->foldertemplateid == $templateset->id
                    && $templateslot->status == \mod_openstudio\local\api\levels::ACTIVE
            ) {
                $this->templatedfoldercontents[$j]->foldercontenttemplateid = $this->templatecontents[$i]->id;
            }
            $this->templatedfoldercontents[$j]->contentorder = $i + 1;
            $this->templatedfoldercontents[$j]->id = $DB->insert_record(
                    'openstudio_folder_contents', $this->templatedfoldercontents[$j]);
        }

        // Create 2 slots outside of any sets, belonging to 2 different users.
        $this->collectables = new \stdClass();
        $this->collectables->one = $this->generate_slot_data();
        $this->collectables->one->visibility = \mod_openstudio\local\api\content::VISIBILITY_MODULE;
        $this->collectables->one->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->collectables->one);
        $this->collectables->two = $this->generate_slot_data();
        $this->collectables->two->visibility = \mod_openstudio\local\api\content::VISIBILITY_MODULE;
        $this->collectables->two->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->two->id,
                0, 0, $this->collectables->two);

        // Create a slot in a different studio.
        $this->altstudio = $this->generator->create_instance(['course' => $this->course->id, 'enablefolders' => 1]);
        $this->altstudio->leveldata = $this->generator->create_mock_levels($this->altstudio->id);
        $this->collectables->three = $this->generate_slot_data();
        $this->collectables->three->visibility = \mod_openstudio\local\api\content::VISIBILITY_MODULE;
        $this->collectables->three->id = \mod_openstudio\local\api\content::create($this->altstudio->id,
                $this->users->students->one->id,
                0, 0, $this->collectables->three);

        // Create a set of copied slots.
        $this->provenancefolder = $this->generate_set_data();
        $this->provenancefolder->id = \mod_openstudio\local\api\content::create_in_pinboard($this->studiolevels->id,
                $this->users->students->one->id,
                $this->provenancefolder);

        // Create a second set for soft-link test.
        $this->provenancefolder2 = $this->generate_set_data();
        $this->provenancefolder2->id = \mod_openstudio\local\api\content::create_in_pinboard($this->studiolevels->id,
                $this->users->students->one->id,
                $this->provenancefolder2);

        $this->provenance = new \stdClass();

        // Original slot, not in the set.
        $this->provenance->original = $this->generate_slot_data();
        $this->provenance->original->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->original);

        // Copy of original slot, unedited.
        $this->provenance->copy1 = clone $this->provenance->original;
        $this->provenance->copy1->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->copy1);
        // Copy of copy1, unedited (gets recorded as a copy of original).
        $this->provenance->copy2 = clone $this->provenance->original;
        $this->provenance->copy2->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->copy2);
        // Copy of original slot, edited.
        $this->provenance->edited = clone $this->provenance->original;
        $this->provenance->edited->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->edited);
        // Copy of edited copy, unlinked.
        $this->provenance->unlinked = clone $this->provenance->original;
        $this->provenance->unlinked->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->unlinked);

        // Another slot, not in the set, to be soft-linked.
        $this->provenance->softlinked = $this->generate_slot_data();
        $this->provenance->softlinked->id = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->softlinked);

        $this->provenance->copy1->setslot = (object) [
                'folderid' => $this->provenancefolder->id,
                'contentid' => $this->provenance->copy1->id,
                'contentorder' => 1,
                'provenanceid' => $this->provenance->original->id,
                'provenancestatus' => \mod_openstudio\local\api\folder::PROVENANCE_COPY,
                'timemodified' => time()
        ];
        $this->provenance->copy1->setslot->id = $DB->insert_record('openstudio_folder_contents', $this->provenance->copy1->setslot);
        $this->provenance->copy2->setslot = (object) [
                'folderid' => $this->provenancefolder->id,
                'contentid' => $this->provenance->copy2->id,
                'contentorder' => 2,
                'provenanceid' => $this->provenance->original->id,
                'provenancestatus' => \mod_openstudio\local\api\folder::PROVENANCE_COPY,
                'timemodified' => time()
        ];
        $this->provenance->copy2->setslot->id = $DB->insert_record('openstudio_folder_contents', $this->provenance->copy2->setslot);
        $this->provenance->edited->setslot = (object) [
                'folderid' => $this->provenancefolder->id,
                'contentid' => $this->provenance->edited->id,
                'contentorder' => 3,
                'provenanceid' => $this->provenance->original->id,
                'provenancestatus' => \mod_openstudio\local\api\folder::PROVENANCE_EDITED,
                'timemodified' => time()
        ];
        $this->provenance->edited->setslot->id = $DB->insert_record('openstudio_folder_contents',
                $this->provenance->edited->setslot);
        $this->provenance->unlinked->setslot = (object) [
                'folderid' => $this->provenancefolder->id,
                'contentid' => $this->provenance->unlinked->id,
                'contentorder' => 4,
                'provenanceid' => $this->provenance->edited->id,
                'provenancestatus' => \mod_openstudio\local\api\folder::PROVENANCE_UNLINKED,
                'timemodified' => time()
        ];
        $this->provenance->unlinked->setslot->id = $DB->insert_record('openstudio_folder_contents',
                $this->provenance->unlinked->setslot);

        $this->provenance->softlinked->setslot1 = (object) [
                'folderid' => $this->provenancefolder->id,
                'contentid' => $this->provenance->softlinked->id,
                'contentorder' => 4,
                'provenanceid' => $this->provenance->softlinked->id,
                'provenancestatus' => \mod_openstudio\local\api\folder::PROVENANCE_COPY,
                'timemodified' => time()
        ];
        $this->provenance->softlinked->setslot1->id = $DB->insert_record('openstudio_folder_contents',
                $this->provenance->softlinked->setslot1);

        $this->provenance->softlinked->setslot2 = (object) [
                'folderid' => $this->provenancefolder2->id,
                'contentid' => $this->provenance->softlinked->id,
                'contentorder' => 1,
                'provenanceid' => $this->provenance->softlinked->id,
                'provenancestatus' => \mod_openstudio\local\api\folder::PROVENANCE_COPY,
                'timemodified' => time()
        ];
        $this->provenance->softlinked->setslot2->id = $DB->insert_record('openstudio_folder_contents',
                $this->provenance->softlinked->setslot2);
    }

    protected function generate_set_data() {
        return (object) [
                'name' => 'Test Set ' . random_string(),
                'attachments' => '',
                'embedcode' => '',
                'weblink' => '',
                'urltitle' => '',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
                'contenttype' => \mod_openstudio\local\api\content::TYPE_FOLDER,
                'description' => random_string(),
                'tags' => [random_string(), random_string(), random_string()],
                'ownership' => 0,
                'sid' => 0 // For a new slot.
        ];
    }

    protected function generate_slot_data() {
        return (object) [
                'name' => 'Lorem Ipsum ' . random_string(),
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://example.com',
                'urltitle' => 'An example weblink',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'description' => random_string(),
                'tags' => [random_string(), random_string(), random_string()],
                'ownership' => 0,
                'sid' => 0 // For a new slot.
        ];
    }

    protected function generate_set_template_data() {
        return (object) [
                'levelcontainer' => 3,
                'levelid' => 0,
                'guidance' => random_string(),
                'additionalslots' => 0,
                'status' => \mod_openstudio\local\api\levels::ACTIVE
        ];
    }

    protected function generate_slot_template_data() {
        $this->contenttemplatecount++;
        return (object) [
                'name' => 'Dolor sit amet ' . random_string(),
                'guidance' => random_string(),
                'permissions' => 0,
                'status' => \mod_openstudio\local\api\levels::ACTIVE,
                'contentorder' => $this->contenttemplatecount
        ];
    }

    protected function tearDown() {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
    }
    /**
     * Add a slot to a set
     */
    public function test_add_content() {
        global $DB;
        $folder = end($this->folders);
        $content = end($this->contents);
        $userid = $this->users->students->one->id;

        // Check the content's not already in the folder.
        $params = ['folderid' => $folder->id, 'contentid' => $content->id];
        $this->assertFalse($DB->record_exists('openstudio_folder_contents', $params));

        $this->assertTrue((bool) \mod_openstudio\local\api\folder::add_content($folder->id, $content->id, $userid));

        $this->assertTrue($DB->record_exists('openstudio_folder_contents', $params));

        // Requests to add slots that are already added should be ignored, and not
        // create duplicates.
        $this->assertTrue((bool) \mod_openstudio\local\api\folder::add_content($folder->id, $content->id, $userid));
        $this->assertEquals(1, $DB->count_records('openstudio_folder_contents', $params));

        $fakesetid = $this->get_nonexistant_id('openstudio_contents');
        $fakeslotid = $this->get_nonexistant_id('openstudio_contents');
        $this->assertFalse((bool) \mod_openstudio\local\api\folder::add_content($fakesetid, $content->id, $userid));
        $this->assertFalse((bool) \mod_openstudio\local\api\folder::add_content($folder->id, $fakeslotid, $userid));
        $this->assertFalse((bool) \mod_openstudio\local\api\folder::add_content($fakesetid, $fakeslotid, $userid));
    }

    public function test_get() {
        global $DB;
        $foldercontent = reset($this->foldercontents);
        $folder = reset($this->folders);

        // Get what we know to be the content record for the folder.
        $folderrecord = $DB->get_record('openstudio_contents', ['id' => $folder->id]);

        $getfolder = \mod_openstudio\local\api\folder::get($foldercontent->folderid);
        // Check that we get the correct slot back.
        $this->assertEquals($folderrecord->id, $getfolder->id);
        $this->assertEquals($folderrecord->name, $getfolder->name);
    }

    public function test_get_nonexistant_folder() {
        // Check that the function returns false if the folder doesn't exist.
        $this->assertFalse(\mod_openstudio\local\api\folder::get($this->get_nonexistant_id('openstudio_contents')));
    }

    public function test_get_wrong_type() {
        // Check that the folder returns false if we try to get a content record that's not contenttype == content::TYPE_FOLDER.
        $content = end($this->contents);
        $this->assertFalse(\mod_openstudio\local\api\folder::get($content->id));
    }

    public function test_get_content() {
        $folder = reset($this->folders);
        $content = reset($this->contents);
        $fc = reset($this->foldercontents);

        $foldercontent = \mod_openstudio\local\api\folder::get_content($folder->id, $content->id);
        $this->assertEquals($content->id, $foldercontent->id);
        $this->assertObjectHasAttribute('fcid', $foldercontent);
        $this->assertEquals($fc->id, $foldercontent->fcid);
    }

    public function test_get_content_not_in_folder() {
        $folder = reset($this->folders);
        $content = end($this->contents);
        $this->assertFalse(\mod_openstudio\local\api\folder::get_content($folder->id, $content->id));
    }

    public function test_get_content_nonexistant_folder() {
        $folderid = $this->get_nonexistant_id('openstudio_contents');
        $content = reset($this->contents);
        $this->assertFalse(\mod_openstudio\local\api\folder::get_content($folderid, $content->id));
    }

    public function test_get_content_nonexistant_content() {
        $folder = reset($this->folders);
        $contentid = $this->get_nonexistant_id('openstudio_contents');
        $this->assertFalse(\mod_openstudio\local\api\folder::get_content($folder->id, $contentid));
    }

    public function test_get_containing_folder() {
        $content = reset($this->contents); // This content is in the first folder.
        $folder = reset($this->folders);

        $containingfolder = \mod_openstudio\local\api\folder::get_containing_folder($content->id);
        $this->assertEquals($folder->id, $containingfolder->id);
    }

    public function test_get_containing_folder_not_in_folder() {
        $content = end($this->contents); // Not in a folder.

        $this->assertFalse(\mod_openstudio\local\api\folder::get_containing_folder($content->id));
    }

    public function test_get_contents() {
        $set = reset($this->folders);
        $slots = $this->contents;

        $returnedslots = \mod_openstudio\local\api\folder::get_contents($set->id);

        $this->assertNotEmpty($returnedslots);
        // There are 4 slots, but only 2 are part of the set.
        $this->assertEquals(2, count($returnedslots));
        $this->assertTrue(array_key_exists(reset($slots)->id, $returnedslots));
        $this->assertTrue(array_key_exists(next($slots)->id, $returnedslots));
        $this->assertFalse(array_key_exists(next($slots)->id, $returnedslots));
        $this->assertFalse(array_key_exists(next($slots)->id, $returnedslots));
    }

    public function test_get_contents_empty_folder() {
        reset($this->folders);
        $emptyset = next($this->folders);
        $this->assertEmpty(\mod_openstudio\local\api\folder::get_containing_folder($emptyset->id));
    }

    public function test_get_contents_nonexistant_folder() {
        $id = $this->get_nonexistant_id('openstudio_contents');
        $this->assertEmpty(\mod_openstudio\local\api\folder::get_containing_folder($id));
    }

    public function test_get_deleted_contents() {
        global $DB;
        $folder = reset($this->folders);
        $deletedcontent = (object) [
            'openstudioid' => $this->studiolevels->id,
            'contenttype' => \mod_openstudio\local\api\content::TYPE_NONE,
            'visibility' => \mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
            'userid' => $this->users->students->one->id,
            'levelid' => 0,
            'levelcontainer' => 0,
            'showextradata' => 0,
            'deletedby' => $this->users->students->one->id,
            'deletedtime' => time(),
            'timemodified' => time(),
            'timeflagged' => time()
        ];
        $deletedcontent->id = $DB->insert_record('openstudio_contents', $deletedcontent);
        $foldercontent = (object) [
            'folderid' => $folder->id,
            'contentid' => $deletedcontent->id,
            'status' => \mod_openstudio\local\api\levels::SOFT_DELETED,
            'contentmodified' => time(),
            'contentorder' => 1,
            'timemodified' => time()
        ];
        $foldercontent->id = $DB->insert_record('openstudio_folder_contents', $foldercontent);
        $contentversion = (object) [
            'contentid' => $deletedcontent->id,
            'contenttype' => \mod_openstudio\local\api\content::TYPE_TEXT,
            'name' => 'Deleted Slot',
            'description' => 'Deleted slot description',
            'deletedby' => $this->users->students->one->id,
            'deletedtime' => time(),
            'timemodified' => time()
        ];
        $contentversion->id = $DB->insert_record('openstudio_content_versions', $contentversion);

        $notdeleted = \mod_openstudio\local\api\folder::get_contents($folder->id);
        $this->assertEquals(2, count($notdeleted));
        $this->assertFalse(array_key_exists($deletedcontent->id, $notdeleted));

        $slotsdeleted = \mod_openstudio\local\api\folder::get_deleted_contents($folder->id);
        $this->assertEquals(1, count($slotsdeleted));
        $this->assertTrue(array_key_exists($contentversion->id, $slotsdeleted));
    }

    public function test_deleted_contents_none_deleted() {
        $folder = reset($this->folders);
        $this->assertEmpty(\mod_openstudio\local\api\folder::get_deleted_contents($folder->id));
    }

    public function test_get_content_with_templates_full_folder() {
        $fullfolderid = $this->templatedfolders['full'];
        // This folder has 2 contents, the same as in the template,
        // So should get 2 real contents back.
        $fullcontents = \mod_openstudio\local\api\folder::get_contents_with_templates($fullfolderid);
        $this->assertEquals(2, count($fullcontents));
        // Check that we have 2 real and 0 template contents,
        // And that the contents are in the correct order.
        $this->check_template_contents($fullcontents, 2, 0);
    }

    public function test_get_content_with_templates_overfull_folder() {
        $overfolderid = $this->templatedfolders['over'];
        // This folder has 3 contents, more than the template,
        // so should get 3 back.
        $overcontents = \mod_openstudio\local\api\folder::get_contents_with_templates($overfolderid);
        $this->assertEquals(3, count($overcontents));
        // Check that all contents are full contents, not just templates.
        $this->check_template_contents($overcontents, 3, 0);
    }

    public function test_get_content_with_templates_folder_with_spaces() {
        $underfolderid = $this->templatedfolders['under'];
        // This folder has 1 content, less than the template,
        // so should get 2 back (1 real and 1 template).
        $undercontents = \mod_openstudio\local\api\folder::get_contents_with_templates($underfolderid);
        $this->assertEquals(2, count($undercontents));
        // Check that 1 content is full and 1 is a template.
        $this->check_template_contents($undercontents, 1, 1);
    }

    public function test_get_content_with_templates_empty_folder() {
        $emptyfolderid = $this->templatedfolders['empty'];
        // This folder has no contents, so should just get 2 template contents.
        $emptycontents = \mod_openstudio\local\api\folder::get_contents_with_templates($emptyfolderid);
        $this->assertEquals(2, count($emptycontents));
        // Check that both contents are templates.
        $this->check_template_contents($emptycontents, 0, 2);
    }

    public function test_get_content_with_templates_offset_content() {
        $offsetfolderid = $this->templatedfolders['offset'];
        // This folder has 2 contents, but one templated position remains unfilled,
        // so should get 3 back (2 real and 1 template).
        $offsetcontents = \mod_openstudio\local\api\folder::get_contents_with_templates($offsetfolderid);
        $this->assertEquals(3, count($offsetcontents));
        // Check that 2 contents are full and 1 is a template.
        $this->check_template_contents($offsetcontents, 2, 1);
    }

    public function test_get_content_with_template_no_template() {
        // For non-templated contents, we should just get the contents and no templates.
        $folder = reset($this->folders);
        $setcontents = $this->foldercontents;

        $returnedcontents = \mod_openstudio\local\api\folder::get_contents_with_templates($folder->id);

        // There are 4 contents, but only 2 are part of the folder.
        $this->assertEquals(2, count($returnedcontents));
        $firstcontent = reset($setcontents);
        $secondcontent = next($setcontents);
        $this->assertEquals($firstcontent->contentid, reset($returnedcontents)->id);
        $this->assertEquals($secondcontent->contentid, next($returnedcontents)->id);
    }

    public function test_get_content_with_template_nonexistant_folder() {
        $id = $this->get_nonexistant_id('openstudio_contents');
        $this->assertEmpty(\mod_openstudio\local\api\folder::get_contents_with_templates($id));
    }

    public function test_get_first_content() {
        // Regular folder with slots, should return the first slot.
        $folder = reset($this->folders);
        $firstfoldercontents = reset($this->foldercontents);
        $firstcontent = reset($this->contents);
        $returnedfoldercontent = \mod_openstudio\local\api\folder::get_first_content($folder->id);

        $this->assertEquals($folder->id, $returnedfoldercontent->folderid);
        $this->assertEquals($firstcontent->id, $returnedfoldercontent->id);
        $this->assertEquals($firstcontent->name, $returnedfoldercontent->name);
        $this->assertEquals($firstfoldercontents->id, $returnedfoldercontent->fcid);
        $this->assertEquals(1, $returnedfoldercontent->contentorder);
    }

    public function test_get_first_content_empty_folder() {
        // Regular set without slots, should return false.
        $emptyset = next($this->folders);
        $this->assertFalse(\mod_openstudio\local\api\folder::get_first_content($emptyset->id));
    }

    public function test_get_first_content_filled_template() {

        // Templated set with content in the first slot, should return the slot.
        $templatedfolderid = $this->templatedfolders['full'];
        $templatedfirstcontent = $this->templatedcontents[0];
        $templatedfirstfoldercontent = $this->templatedfoldercontents[0];
        $returnedfoldercontent = \mod_openstudio\local\api\folder::get_first_content($templatedfolderid);

        $this->assertEquals($templatedfolderid, $returnedfoldercontent->folderid);
        $this->assertEquals($templatedfirstcontent->id, $returnedfoldercontent->id);
        $this->assertEquals($templatedfirstcontent->name, $returnedfoldercontent->name);
        $this->assertEquals($templatedfirstfoldercontent->id, $returnedfoldercontent->fcid);
        $this->assertEquals(1, $returnedfoldercontent->contentorder);

    }

    public function test_get_first_content_empty_template() {
        // Templated slot with the first slot empty, should return the template.
        $offsetfolderid = $this->templatedfolders['offset'];
        $templatefirstcontent = reset($this->templatecontents);
        $returnedfoldercontent = \mod_openstudio\local\api\folder::get_first_content($offsetfolderid);

        $this->assertEquals($templatefirstcontent->name, $returnedfoldercontent->name);
        $this->assertEquals(1, $returnedfoldercontent->contentorder);
    }

    public function test_get_first_content_nonexistant_folder() {
        $this->assertFalse(\mod_openstudio\local\api\folder::get_first_content($this->get_nonexistant_id('openstudio_contents')));
    }

    public function test_filter_empty_templates() {
        $offsetfolderid = $this->templatedfolders['offset'];
        $contents = \mod_openstudio\local\api\folder::get_contents_with_templates($offsetfolderid);
        $this->assertEquals(3, count($contents));
        $filteredcontents = \mod_openstudio\local\api\folder::filter_empty_templates($contents);
        $this->assertEquals(2, count($filteredcontents));
    }

    public function test_filter_empty_templates_no_templates() {
        $fullfolderid = $this->templatedfolders['full'];
        $contents = \mod_openstudio\local\api\folder::get_contents_with_templates($fullfolderid);
        $this->assertEquals(2, count($contents));
        $filteredcontents = \mod_openstudio\local\api\folder::filter_empty_templates($contents);
        $this->assertEquals(count($contents), count($filteredcontents));
    }

    public function test_update_content() {
        global $DB;
        sleep(1); // So we can check that timemodified is updated.
        $foldercontent = reset($this->foldercontents);
        $foldercontentrecord = $DB->get_record('openstudio_folder_contents', ['id' => $foldercontent->id]);
        $updatedcontent = (object) [
            'id' => $foldercontent->id,
            'name' => random_string()
        ];
        $this->assertNotEquals($foldercontentrecord->name, $updatedcontent->name);

        $this->assertTrue(\mod_openstudio\local\api\folder::update_content($updatedcontent));
        $updatedrecord = $DB->get_record('openstudio_folder_contents', ['id' => $foldercontent->id]);
        $this->assertGreaterThan($foldercontentrecord->timemodified, $updatedrecord->timemodified);
        $this->assertEquals($updatedcontent->name, $updatedrecord->name);

    }

    public function test_update_content_nonexistant_content() {
        $nonexistantcontent = (object) [
            'id' => $this->get_nonexistant_id('openstudio_folder_contents'),
            'name' => random_string()
        ];
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::update_content($nonexistantcontent);
    }

    public function test_update_contentorders() {
        global $DB;
        sleep(1);
        $firstcontent = reset($this->foldercontents);
        $secondcontent = next($this->foldercontents);

        $firstrecord = $DB->get_record('openstudio_folder_contents', ['id' => $firstcontent->id]);
        $secondrecord = $DB->get_record('openstudio_folder_contents', ['id' => $secondcontent->id]);

        $neworders = [
            $secondrecord->contentorder,
            $firstrecord->contentorder
        ];

        \mod_openstudio\local\api\folder::update_contentorders($firstrecord->folderid, $neworders);

        $updatedfirstrecord = $DB->get_record('openstudio_folder_contents', ['id' => $firstcontent->id]);
        $updatedsecondrecord = $DB->get_record('openstudio_folder_contents', ['id' => $secondrecord->id]);

        $this->assertNotEquals($firstrecord->contentorder, $updatedfirstrecord->contentorder);
        $this->assertEquals($secondrecord->contentorder, $updatedfirstrecord->contentorder);
        $this->assertGreaterThan($firstrecord->timemodified, $updatedfirstrecord->timemodified);

        $this->assertNotEquals($secondrecord->contentorder, $updatedsecondrecord->contentorder);
        $this->assertEquals($firstrecord->contentorder, $updatedsecondrecord->contentorder);
        $this->assertGreaterThan($secondrecord->timemodified, $updatedsecondrecord->timemodified);

    }

    public function test_update_contentorders_no_folder() {
        global $DB;
        $fakefolderid = $this->get_nonexistant_id('openstudio_contents');
        $firstcontent = reset($this->foldercontents);
        $secondcontent = next($this->foldercontents);

        $firstrecord = $DB->get_record('openstudio_folder_contents', ['id' => $firstcontent->id]);
        $secondrecord = $DB->get_record('openstudio_folder_contents', ['id' => $secondcontent->id]);

        $neworders = [
            $secondrecord->contentorder,
            $firstrecord->contentorder
        ];
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::update_contentorders($fakefolderid, $neworders);
    }

    public function test_update_contentorders_no_order() {
        global $DB;
        $firstcontent = reset($this->foldercontents);
        $firstrecord = $DB->get_record('openstudio_folder_contents', ['id' => $firstcontent->id]);
        $fakeslotorder = 10000000;
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::update_contentorders($firstrecord->folderid, [$fakeslotorder => 1]);
    }

    public function test_update_contentorders_duplicate_order() {
        global $DB;
        $firstcontent = reset($this->foldercontents);
        $firstrecord = $DB->get_record('openstudio_folder_contents', ['id' => $firstcontent->id]);
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::update_contentorders($firstrecord->folderid, [1 => 1, 2 => 1]);
    }


    public function test_remove_content() {
        global $DB;
        $folder = reset($this->folders);
        $content = reset($this->contents);
        $userid = $this->users->students->one->id;

        // Verify that the content is currently in the folder.
        $params = ['folderid' => $folder->id, 'contentid' => $content->id, 'status' => \mod_openstudio\local\api\levels::ACTIVE];
        $this->assertTrue($DB->record_exists('openstudio_folder_contents', $params));

        // Remove the content from the folder.
        \mod_openstudio\local\api\folder::remove_content($folder->id, $content->id, $userid);

        // Check that the content has been removed from the folder.
        $this->assertFalse($DB->record_exists('openstudio_folder_contents', $params));
        $params['status'] = \mod_openstudio\local\api\levels::SOFT_DELETED;
        $this->assertTrue($DB->record_exists('openstudio_folder_contents', $params));
    }

    public function test_remove_content_no_content() {
        $folder = reset($this->folders);
        $contentid = $this->get_nonexistant_id('openstudio_contents');
        $userid = $this->users->students->one->id;
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::remove_content($folder->id, $contentid, $userid);
    }

    public function test_remove_content_no_folder() {
        $content = reset($this->contents);
        $folderid = $this->get_nonexistant_id('openstudio_contents');
        $userid = $this->users->students->one->id;
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::remove_content($folderid, $content->id, $userid);
    }

    public function test_remove_content_wrong_folder() {
        reset($this->contents);
        $content = end($this->contents); // This content isn't in the folder.
        $folder = reset($this->folders);
        $userid = $this->users->students->one->id;
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::remove_content($folder->id, $content->id, $userid);
    }

    public function test_remove_contents() {
        global $DB;

        $folder = reset($this->folders);
        $userid = $this->users->students->one->id;

        $params = ['folderid' => $folder->id, 'status' => \mod_openstudio\local\api\levels::ACTIVE];
        $contentcount = $DB->count_records('openstudio_folder_contents', $params);

        $this->assertEquals($contentcount, \mod_openstudio\local\api\folder::remove_contents($folder->id, $userid));

        $this->assertFalse($DB->record_exists('openstudio_folder_contents', $params));
        $params['status'] = \mod_openstudio\local\api\levels::SOFT_DELETED;
        $this->assertEquals($contentcount, $DB->count_records('openstudio_folder_contents', $params));

        // Empty the empty folder again, should return 0 as it doesn't do anything.
        $this->assertEquals(0, \mod_openstudio\local\api\folder::remove_contents($folder->id, $userid));
    }

    public function test_remove_contents_no_folder() {
        $this->expectException('coding_exception');
        $userid = $this->users->students->one->id;
        \mod_openstudio\local\api\folder::remove_contents($this->get_nonexistant_id('openstudio_contents'), $userid);
    }

    public function test_collect_content_softlink_own() {
        global $DB;
        $folder = reset($this->folders);
        $userid = $this->users->students->one->id;

        // Test that we can soft-link a user's content to their own folder.
        $contentid = $this->collectables->one->id;
        $this->assertEquals(\mod_openstudio\local\api\folder::collect_content($folder->id, $contentid, $userid, null, true),
                $contentid);
        $params = ['folderid' => $folder->id, 'contentid' => $contentid];
        $foldercontent = $DB->get_record('openstudio_folder_contents', $params);
        $this->assertNotEquals(false, $foldercontent);
        $this->assertEquals($foldercontent->provenanceid, $contentid);
        $this->assertEquals($foldercontent->provenancestatus, \mod_openstudio\local\api\folder::PROVENANCE_COPY);
    }

    public function test_collect_content_copy_own() {
        global $DB;
        $folder = reset($this->folders);
        $userid = $this->users->students->one->id;

        // Test that we can copy a user's content to their own folder.
        $contentid = \mod_openstudio\local\api\folder::collect_content($folder->id, $this->collectables->one->id, $userid);
        $this->assertNotEquals(false, $contentid);
        $this->assertNotEquals($this->collectables->one->id, $contentid);
        // Verify that the new content is a copy of the existing one.
        $this->assertEquals($this->collectables->one->name, $DB->get_field('openstudio_contents', 'name', ['id' => $contentid]));
        // Verify that the new slot is in the folder.
        $params = ['folderid' => $folder->id, 'contentid' => $contentid];
        $foldercontent = $DB->get_record('openstudio_folder_contents', $params);
        $this->assertNotEquals(false, $foldercontent);
        $this->assertEquals($foldercontent->provenanceid, $this->collectables->one->id);
        $this->assertEquals($foldercontent->provenancestatus, \mod_openstudio\local\api\folder::PROVENANCE_COPY);
    }

    public function test_collect_content_copy_other() {
        $folder = reset($this->folders);
        $userid = $this->users->students->one->id;

        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::collect_content($folder->id, $this->collectables->two->id, $userid);
    }

    public function test_collect_content_copy_other_with_permission() {
        global $DB;
        $folder = reset($this->folders);
        $userid = $this->users->students->one->id;

        // Enable collection of other user's contents...
        $studiorecord = $DB->get_record('openstudio', ['id' => $this->studiolevels->id]);
        $studiorecord->themefeatures += \mod_openstudio\local\util\feature::ENABLEFOLDERSANYCONTENT;
        $DB->update_record('openstudio', $studiorecord);

        // Test that we can copy another user's slot.
        $contentid = \mod_openstudio\local\api\folder::collect_content($folder->id, $this->collectables->two->id, $userid);
        $this->assertNotEquals(false, $contentid);
        $this->assertNotEquals($this->collectables->two->id, $contentid, $userid);

    }

    public function test_collect_content_softlink_other() {
        global $DB;
        $folder = reset($this->folders);
        $userid = $this->users->students->one->id;

        // Enable collection of other user's contents...
        $studiorecord = $DB->get_record('openstudio', ['id' => $this->studiolevels->id]);
        $studiorecord->themefeatures += \mod_openstudio\local\util\feature::ENABLEFOLDERSANYCONTENT;
        $DB->update_record('openstudio', $studiorecord);

        // Test that we cannot soft-link another user's slot.
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::collect_content($folder->id, $this->collectables->two->id, $userid, null, true);
    }

    public function test_collect_content_wrong_studio() {
        $folder = reset($this->folders);
        $userid = $this->users->students->one->id;
        $contentid = $this->collectables->three->id;
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::collect_content($folder->id, $contentid, $userid);
    }

    public function test_collect_content_fake_folder() {
        $userid = $this->users->students->one->id;
        $fakeid = $this->get_nonexistant_id('openstudio_contents');
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::collect_content($fakeid, $this->collectables->one->id, $userid);
    }

    public function test_collect_content_fake_content() {
        $folder = reset($this->folders);
        $userid = $this->users->students->one->id;
        $fakeid = $this->get_nonexistant_id('openstudio_contents');

        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::collect_content($folder->id, $fakeid, $userid);
    }

    public function test_count_contents() {
        $folder = reset($this->folders);
        // Folder contains 2 content posts, no template so excluding templated should make no difference.
        $this->assertEquals(2, \mod_openstudio\local\api\folder::count_contents($folder->id));
        $this->assertEquals(2, \mod_openstudio\local\api\folder::count_contents($folder->id, false));
    }

    public function test_count_contents_full_template() {
        $folderid = $this->templatedfolders['full'];
        // 2 contents, both templated.
        $this->assertEquals(2, \mod_openstudio\local\api\folder::count_contents($folderid));
        $this->assertEquals(0, \mod_openstudio\local\api\folder::count_contents($folderid, false));
    }

    public function test_count_contents_underfull_template() {
        $folderid = $this->templatedfolders['under'];
        // 1 content, templated.
        $this->assertEquals(1, \mod_openstudio\local\api\folder::count_contents($folderid));
        $this->assertEquals(0, \mod_openstudio\local\api\folder::count_contents($folderid, false));
    }

    public function test_count_contents_overfull_template() {
        $folderid = $this->templatedfolders['over'];
        // 3 contents, 2 are templated.
        $this->assertEquals(3, \mod_openstudio\local\api\folder::count_contents($folderid));
        $this->assertEquals(1, \mod_openstudio\local\api\folder::count_contents($folderid, false));
    }

    public function test_count_contents_empty_template() {
        $folderid = $this->templatedfolders['empty'];
        // 0 contents.
        $this->assertEquals(0, \mod_openstudio\local\api\folder::count_contents($folderid));
        $this->assertEquals(0, \mod_openstudio\local\api\folder::count_contents($folderid, false));
    }

    public function test_count_contents_offset_template() {
        $folder = $this->templatedfolders['offset'];
        // 2 contents, 1 templated and 1 not.
        $this->assertEquals(2, \mod_openstudio\local\api\folder::count_contents($folder));
        $this->assertEquals(1, \mod_openstudio\local\api\folder::count_contents($folder, false));
    }

    public function test_determine_content_provenance() {
        // Original slot, not in a set, should return own ID.
        $this->assertEquals($this->provenance->original->id,
                \mod_openstudio\local\api\folder::determine_content_provenance($this->provenance->original->id));
    }

    public function test_determine_content_provenance_copy() {
        // Copy of original slot, should return original ID.
        $this->assertEquals($this->provenance->original->id,
                \mod_openstudio\local\api\folder::determine_content_provenance($this->provenance->copy1->id));
    }

    public function test_determine_content_provenance_copy_of_copy() {
        // Copy of copy, should return original ID.
        $this->assertEquals($this->provenance->original->id,
                \mod_openstudio\local\api\folder::determine_content_provenance($this->provenance->copy2->id));
    }

    public function test_determine_content_provenance_edited_copy() {
        // Edited copy of original, should return own ID.
        $this->assertEquals($this->provenance->edited->id,
                \mod_openstudio\local\api\folder::determine_content_provenance($this->provenance->edited->id));
    }

    public function test_determine_content_provenance_unlinked_copy() {
        // Unlinked copy of edited copy, should return own ID.
        $this->assertEquals($this->provenance->unlinked->id,
                \mod_openstudio\local\api\folder::determine_content_provenance($this->provenance->unlinked->id));
    }

    public function test_determine_content_provenance_no_content() {
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\folder::determine_content_provenance($this->get_nonexistant_id('openstudio_contents'));
    }

    public function test_get_content_provenance_no_provenance() {
        // Original slot, has no provenance.
        $originalprovenance = \mod_openstudio\local\api\folder::get_content_provenance(
                $this->provenancefolder->id, $this->provenance->original->id);
        $this->assertNull($originalprovenance);
    }

    public function test_get_content_provencance_copy() {
        // Copy of original, should return original.
        $copy1provenance = \mod_openstudio\local\api\folder::get_content_provenance(
                $this->provenancefolder->id, $this->provenance->copy1->id);
        $this->assertEquals($this->provenance->original->id, $copy1provenance->id);
    }

    public function test_get_content_provenance_copy_of_copy() {
        // Copy of copy1, should return original.
        $copy2provenance = \mod_openstudio\local\api\folder::get_content_provenance(
                $this->provenancefolder->id, $this->provenance->copy2->id);
        $this->assertEquals($this->provenance->original->id, $copy2provenance->id);
    }

    public function test_get_content_provenance_edited_copy() {
        // Edited copy of original, should return original.
        $editedprovenance = \mod_openstudio\local\api\folder::get_content_provenance(
                $this->provenancefolder->id, $this->provenance->edited->id);
        $this->assertEquals($this->provenance->original->id, $editedprovenance->id);
    }

    public function test_get_content_provenance_unlinked() {
        // Unlinked copy of copy1, should not return provenance.
        $unlinkedprovenance = \mod_openstudio\local\api\folder::get_content_provenance(
                $this->provenancefolder->id, $this->provenance->unlinked->id);
        $this->assertNull($unlinkedprovenance);
    }

    public function test_get_content_copies_multiple_copies() {
        // Slots copy1, copy2 and edited are all copies of original, so should get all 3 back.
        $originalcopies = \mod_openstudio\local\api\folder::get_content_copies($this->provenance->original->id);
        $this->assertEquals(3, count($originalcopies));
        $originalcopyids = array_map(function($a) {
            return $a->contentid;
        }, $originalcopies);
        $this->assertContains($this->provenance->copy1->id, $originalcopyids);
        $this->assertContains($this->provenance->copy2->id, $originalcopyids);
        $this->assertContains($this->provenance->edited->id, $originalcopyids);
    }

    public function test_get_content_copies_copy_of_copy() {
        // Slot copy2 was copied from copy1, but is recorded as a copy of original, so copy1 has no copies.
        $this->assertEmpty(\mod_openstudio\local\api\folder::get_content_copies($this->provenance->copy1->id));
    }

    public function test_get_content_copies_no_copy() {
        // Slot copy2 has no copies.
        $this->assertEmpty(\mod_openstudio\local\api\folder::get_content_copies($this->provenance->copy2->id));
    }

    public function test_get_content_copies_unlinked() {
        // Slot edited has a copy, but it's unlinked.
        $this->assertEmpty(\mod_openstudio\local\api\folder::get_content_copies($this->provenance->edited->id));
    }

    public function test_get_content_softlinks() {
        // Softlinked has 2 softlinks in different sets, so we should get 2.
        $softlinks = \mod_openstudio\local\api\folder::get_content_softlinks($this->provenance->softlinked->id);
        $this->assertEquals(2, count($softlinks));
        $this->assertTrue(array_key_exists($this->provenance->softlinked->setslot1->id, $softlinks));
        $this->assertTrue(array_key_exists($this->provenance->softlinked->setslot2->id, $softlinks));
    }

    public function test_get_content_softlinks_no_softlink() {
        // Original only has copies, no softlinks, so we should get 0.
        $this->assertEquals(0, count(\mod_openstudio\local\api\folder::get_content_softlinks($this->provenance->original->id)));
    }

    public function test_move_content() {
        $userid = $this->users->students->one->id;
        $contentdata = [
            'openstudio' => 'OS1',
            'visibility' => \mod_openstudio\local\api\content::VISIBILITY_MODULE,
            'userid' => $userid,
            'name' => random_string(),
            'description' => random_string()
        ];
        $newname = 'foo';
        $newdescription = 'bar';
        $contentid = $this->generator->create_contents($contentdata);

        $content = \mod_openstudio\local\api\folder::copy_content($contentid, $userid, $newname, $newdescription);
        $this->assertEquals($newname, $content->name);
        $this->assertEquals($newdescription, $content->description);
        $this->assertEquals(\mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY, $content->visibility);
    }

    public function test_move_content_no_content() {
        $id = $this->get_nonexistant_id('openstudio_contents');
        $this->expectException('dml_exception');
        \mod_openstudio\local\api\folder::copy_content($id, $this->users->students->one->id);
    }

    /**
     * Test that we get the default limit for a new pinboard set
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit() {
        $this->assertEquals(\mod_openstudio\local\util\defaults::MAXPINBOARDFOLDERSCONTENTS,
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit));
    }

    /**
     * Test that we get the defined limit for an empty pinboard set
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_empty_pinboard_set() {
        $folderdata = [
            'openstudio' => 'OS1',
            'name' => 'empty_pinboard_folder',
            'levelid' => 0,
            'levelcontainer' => 0,
            'userid' => $this->users->students->one->id
        ];
        $folderid = $this->generator->create_folders($folderdata);
        $this->assertEquals($this->permissions->pinboardfolderlimit,
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, $folderid));
    }

    /**
     * Test that we get the defined limit - number of slots for a populated pinboard set
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_populated_pinboard_set() {
        $folderdata = [
            'openstudio' => 'OS1',
            'name' => 'populated_pinboard_folder',
            'levelid' => 0,
            'levelcontainer' => 0,
            'userid' => $this->users->students->one->id
        ];
        $folderid = $this->generator->create_folders($folderdata);
        $popcount = rand(1, \mod_openstudio\local\util\defaults::MAXPINBOARDFOLDERSCONTENTS);
        for ($i = 1; $i <= $popcount; $i++) {
            $contentdata = [
                'openstudio' => 'OS1',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $folderdata['userid'],
                'name' => 'folder_content_' . $i,
                'contenttype' => \mod_openstudio\local\api\content::TYPE_TEXT,
                'description' => random_string()
            ];
            $contentid = $this->generator->create_contents($contentdata);
            $content = \mod_openstudio\local\api\content::get($contentid);
            $this->generator->create_folder_contents([
                'openstudio' => 'OS1',
                'folder' => $folderdata['name'],
                'content' => $content->name,
                'userid' => $this->users->students->one->id]);
        }
        $this->assertEquals($this->permissions->pinboardfolderlimit - $popcount,
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, $folderid));
    }

    /**
     * Test that we get the defined limit for a new templated set
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_new_predefined_set() {
        $level1data = ['openstudio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1'];
        $level2data = [
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        ];
        $level3data = [
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        ];
        $level3id = $this->generator->create_levels($level3data);
        $foldertemplatedata = [
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        ];
        $this->generator->create_folder_template($foldertemplatedata);

        $this->assertEquals($foldertemplatedata['additionalcontents'],
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, 0, $level3id));

    }

    /**
     * Test that we get the defined limit for an empty predefined set
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_empty_predefined_set() {
        $level1data = ['openstudio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1'];
        $level2data = [
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        ];
        $level3data = [
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        ];
        $level3id = $this->generator->create_levels($level3data);
        $settemplatedata = [
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        ];
        $this->generator->create_folder_template($settemplatedata);
        $folderdata = [
            'openstudio' => 'OS1',
            'name' => 'empty_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        ];
        $folderid = $this->generator->create_folders($folderdata);
        $this->assertEquals($settemplatedata['additionalcontents'],
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, $folderid));

    }

    /**
     * Test that we get the defined limit - number of slots for a populated templated set
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_populated_predefined_set() {
        $level1data = ['openstudio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1'];
        $level2data = [
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        ];
        $level3data = [
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        ];
        $level3id = $this->generator->create_levels($level3data);
        $foldertemplateid = [
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        ];
        $this->generator->create_folder_template($foldertemplateid);

        $folderdata = [
            'openstudio' => 'OS1',
            'name' => 'empty_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        ];
        $folderid = $this->generator->create_folders($folderdata);
        $popcount = rand(1, $foldertemplateid['additionalcontents']);
        for ($i = 1; $i <= $popcount; $i++) {
            $contentdata = [
                'openstudio' => 'OS1',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $folderdata['userid'],
                'name' => 'folder_content_' . $i,
                'contenttype' => \mod_openstudio\local\api\content::TYPE_TEXT,
                'description' => random_string()
            ];
            $contentid = $this->generator->create_contents($contentdata);
            $content = \mod_openstudio\local\api\content::get($contentid);
            $foldercontentdata = [
                'openstudio' => 'OS1',
                'folder' => $folderdata['name'],
                'content' => $content->name,
                'contentorder' => $i,
                'userid' => $this->users->students->one->id
            ];
            $this->generator->create_folder_contents($foldercontentdata);
        }
        $this->assertEquals($foldertemplateid['additionalcontents'] - $popcount,
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, $folderid));

    }

    /**
     * Test that we get the defined limit for a templated set with slot templates
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_empty_predefined_set_with_slot_templates() {
        $level1data = ['openstudio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1'];
        $level2data = [
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        ];
        $level3data = [
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        ];
        $level3id = $this->generator->create_levels($level3data);
        $foldertemplatedata = [
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        ];
        $foldertemplateid = $this->generator->create_folder_template($foldertemplatedata);
        $this->generator->create_folder_content_template(['foldertemplateid' => $foldertemplateid]);
        $this->generator->create_folder_content_template(['foldertemplateid' => $foldertemplateid]);
        $setdata = [
            'openstudio' => 'OS1',
            'name' => 'empty_predefined_folder_with_slot_templates',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        ];
        $setid = $this->generator->create_folders($setdata);
        $this->assertEquals($foldertemplatedata['additionalcontents'],
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, $setid));
    }

    /**
     * Test that we get the defined limit - number of slots for a templated set with
     * slot templates.
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_populated_predefined_set_with_slot_templates() {
        $level1data = ['openstudio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1'];
        $level2data = [
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        ];
        $level3data = [
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        ];
        $level3id = $this->generator->create_levels($level3data);
        $settemplatedata = [
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        ];
        $foldertemplateid = $this->generator->create_folder_template($settemplatedata);
        $this->generator->create_folder_content_template(['foldertemplateid' => $foldertemplateid]);
        $this->generator->create_folder_content_template(['foldertemplateid' => $foldertemplateid]);

        $folderdata = [
            'openstudio' => 'OS1',
            'name' => 'empty_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        ];
        $folderid = $this->generator->create_folders($folderdata);
        $popcount = rand(1, $settemplatedata['additionalcontents']);
        for ($i = 1; $i <= $popcount; $i++) {
            $contentdata = [
                'openstudio' => 'OS1',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $folderdata['userid'],
                'name' => 'folder_content_' . $i,
                'contenttype' => \mod_openstudio\local\api\content::TYPE_TEXT,
                'description' => random_string()
            ];
            $contentid = $this->generator->create_contents($contentdata);
            $content = \mod_openstudio\local\api\content::get($contentid);
            $this->generator->create_folder_contents([
                    'openstudio' => 'OS1',
                    'folder' => $folderdata['name'],
                    'content' => $content->name,
                    'userid' => $this->users->students->one->id]);
        }
        $this->assertEquals($settemplatedata['additionalcontents'] - $popcount,
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, $folderid));

    }

    /**
     * Test that we get the definied limit - the number of slots *not including* templated slots
     * for a populated templated set with populated templated slots
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_populated_predefined_set_with_populated_slot_templates() {
        $level1data = ['openstudio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1'];
        $level2data = [
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        ];
        $level3data = [
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        ];
        $level3id = $this->generator->create_levels($level3data);
        $foldertemplatedata = [
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        ];
        $foldertemplateid = $this->generator->create_folder_template($foldertemplatedata);
        $contenttemplateid1 = $this->generator->create_folder_content_template(['foldertemplateid' => $foldertemplateid]);
        $contenttemplateid2 = $this->generator->create_folder_content_template(['foldertemplateid' => $foldertemplateid]);

        $folderdata = [
            'openstudio' => 'OS1',
            'name' => 'populated_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        ];
        $folderid = $this->generator->create_folders($folderdata);
        $templatedcontentdata = [
            'openstudio' => 'OS1',
            'visibility' => \mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
            'userid' => $folderdata['userid'],
            'contenttype' => \mod_openstudio\local\api\content::TYPE_TEXT,
            'description' => random_string()
        ];
        $templatedcontent1data = array_merge($templatedcontentdata, ['name' => 'templated_slot_1']);
        $templatedcontent2data = array_merge($templatedcontentdata, ['name' => 'templated_slot_2']);
        $this->generator->create_contents($templatedcontent1data);
        $this->generator->create_contents($templatedcontent2data);
        $foldercontentdata = [
            'openstudio' => 'OS1',
            'folder' => $folderdata['name'],
            'userid' => $folderdata['userid']
        ];
        $foldercontentdata1 = array_merge($foldercontentdata,
                ['foldercontenttemplateid' => $contenttemplateid1, 'content' => 'templated_slot_1']);
        $foldercontentdata2 = array_merge($foldercontentdata,
                ['foldercontenttemplateid' => $contenttemplateid2, 'content' => 'templated_slot_2']);
        $this->generator->create_folder_contents($foldercontentdata1);
        $this->generator->create_folder_contents($foldercontentdata2);

        $popcount = rand(1, $foldertemplatedata['additionalcontents']);
        for ($i = 1; $i <= $popcount; $i++) {
            $contentdata = [
                'openstudio' => 'OS1',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $folderdata['userid'],
                'name' => 'folder_content_' . $i,
                'contenttype' => \mod_openstudio\local\api\content::TYPE_TEXT,
                'description' => random_string()
            ];
            $contentid = $this->generator->create_contents($contentdata);
            $content = \mod_openstudio\local\api\content::get($contentid);
            $this->generator->create_folder_contents([
                'openstudio' => 'OS1',
                'folder' => $folderdata['name'],
                'content' => $content->name,
                'userid' => $folderdata['userid']
            ]);
        }
        $this->assertEquals($foldertemplatedata['additionalcontents'] - $popcount,
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, $folderid));
    }

    /**
     * Test that we get the default set slot limit for an uninstatiated predefined set
     * with no template
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_new_predefined_set_notemplate() {
        $level1data = ['openstudio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1'];
        $level2data = [
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        ];
        $level3data = [
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        ];
        $level3id = $this->generator->create_levels($level3data);

        $this->assertEquals(\mod_openstudio\local\util\defaults::FOLDERTEMPLATEADDITIONALCONTENTS,
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, 0, $level3id));

    }

    /**
     * Test that we get the default set slot limit for an empty predefined set
     * with no template
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_empty_predefined_set_notemplate() {
        $level1data = ['openstudio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1'];
        $level2data = [
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        ];
        $level3data = [
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        ];
        $level3id = $this->generator->create_levels($level3data);
        $folderdata = [
            'openstudio' => 'OS1',
            'name' => 'empty_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        ];
        $folderid = $this->generator->create_folders($folderdata);

        $this->assertEquals(\mod_openstudio\local\util\defaults::FOLDERTEMPLATEADDITIONALCONTENTS,
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, $folderid));

    }

    /**
     * Test that we get the default - the number of slots for a populated predefined set
     * with no template
     *
     * @group mod_openstudio_get_addition_limit
     */
    public function test_get_addition_limit_populated_predefined_set_notemplate() {
        $level1data = ['openstudio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1'];
        $level2data = [
                'level' => 2,
                'name' => 'predefined_folder_level2',
                'parentid' => $this->generator->create_levels($level1data)
        ];
        $level3data = [
                'level' => 3,
                'name' => 'predefined_folder_level3',
                'parentid' => $this->generator->create_levels($level2data)
        ];
        $level3id = $this->generator->create_levels($level3data);
        $folderdata = [
            'openstudio' => 'OS1',
            'name' => 'populated_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        ];
        $folderid = $this->generator->create_folders($folderdata);

        $popcount = rand(1, \mod_openstudio\local\util\defaults::FOLDERTEMPLATEADDITIONALCONTENTS);
        for ($i = 1; $i <= $popcount; $i++) {
            $contentdata = [
                'openstudio' => 'OS1',
                'visibility' => \mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $folderdata['userid'],
                'name' => 'folder_content_' . $i,
                'contenttype' => \mod_openstudio\local\api\content::TYPE_TEXT,
                'description' => random_string()
            ];
            $contentid = $this->generator->create_contents($contentdata);
            $content = \mod_openstudio\local\api\content::get($contentid);
            $this->generator->create_folder_contents([
                'openstudio' => 'OS1',
                'folder' => $folderdata['name'],
                'content' => $content->name,
                'userid' => $folderdata['userid']
            ]);
        }
        $this->assertEquals(\mod_openstudio\local\util\defaults::FOLDERTEMPLATEADDITIONALCONTENTS - $popcount,
                \mod_openstudio\local\api\folder::get_addition_limit($this->permissions->pinboardfolderlimit, $folderid));

    }

    private function get_nonexistant_id($table) {
        global $DB;
        $randomid = mt_rand();
        while ($DB->record_exists($table, ['id' => $randomid])) {
            $randomid = mt_rand();
        }
        return $randomid;
    }

    private function check_template_contents($contents, $expectedrealcount, $expectedtemplatecount) {
        $realcount = 0;
        $templatecount = 0;
        $lastslotorder = 1;
        foreach ($contents as $content) {
            if (!isset($content->template)) {
                $realcount++;
            } else {
                $templatecount++;
            }
            $this->assertGreaterThanOrEqual($lastslotorder, $content->contentorder);
            $lastslotorder = $content->contentorder;
        }
        $this->assertEquals($expectedrealcount, $realcount);
        $this->assertEquals($expectedtemplatecount, $templatecount);
    }

}
