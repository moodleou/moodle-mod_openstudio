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

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/openstudio/api/set.php');

class mod_openstudio_set_testcase extends advanced_testcase {

    private $users;
    private $permissions;
    private $course;
    private $generator;
    private $studiolevels;
    private $slots;
    private $sets;
    private $setslots;
    private $slottemplatecount = 0;
    private $settemplate;
    private $slotlevelid;
    private $templateslots;
    private $othertemplate;
    private $templatedsets;
    private $templatedslots;
    private $templatedsetslots;
    private $collectables;
    private $altstudio;
    private $provenance;
    private $provenanceset;
    private $provenanceset2;

    /**
     * Sets up our fixtures.
     */
    protected function setUp() {
        global $DB;
        $this->resetAfterTest(true);
        $teacherroleid = 3;
        $studentroleid = 5;
        $this->permissions = (object) array(
                'pinboardfolderlimit' => \mod_openstudio\local\util\defaults::MAXPINBOARDFOLDERSCONTENTS
        );

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
        $studiodata = array('course' => $this->course->id, 'enablefolders' => 1, 'idnumber' => 'OS1');
        $this->studiolevels = $this->generator->create_instance($studiodata);
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);

        // Create slots containing sets.
        $this->sets = array();
        for ($i = 0; $i < 2; $i++) {
            $this->sets[$i] = $this->generate_set_data();
            $this->sets[$i]->id = mod_openstudio\local\api\content::create_in_pinboard($this->studiolevels->id,
                    $this->users->students->one->id,
                    $this->sets[$i]);
            $this->sets[$i] = $this->generate_set_data();
            $this->sets[$i]->id = mod_openstudio\local\api\content::create_in_pinboard($this->studiolevels->id,
                    $this->users->students->one->id,
                    $this->sets[$i]);
        }

        // Add 2 slots to the set.
        $this->slots = array();
        $this->setslots = array();
        for ($i = 0; $i < 2; $i++) {
            $this->slots[$i] = $this->generate_slot_data();
            $this->slots[$i]->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                    $this->users->students->one->id,
                    0, 0, $this->slots[$i]);
            $this->setslots[$i] = (object) array(
                    'folderid'        => $this->sets[0]->id,
                    'contentid'       => $this->slots[$i]->id,
                    'timemodified' => time(),
                    'contentorder'    => $i + 1,
                    'status'       => mod_openstudio\local\api\levels::ACTIVE
            );
            $this->setslots[$i]->id = $DB->insert_record('openstudio_folder_contents', $this->setslots[$i]);
        }

        // Add a slot that should be in the set, but hasn't been added to it.
        $extraslot = $this->generate_slot_data();
        $extraslot->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $extraslot);
        $this->slots[] = $extraslot;

        // Add another slot that's not in the set.
        $outsideslot = $this->generate_slot_data();
        $outsideslot->visibility = mod_openstudio\local\api\content::VISIBILITY_MODULE;
        $outsideslot->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $outsideslot);
        $this->slots[] = $outsideslot;

        // Create set templates.
        $activitylevels = end($this->studiolevels->leveldata['contentslevels']);
        $slotlevels = end($activitylevels);
        $this->slotlevelid = end($slotlevels);

        $settemplates = array(
                $this->generate_set_template_data(),
                $this->generate_set_template_data(),
                $this->generate_set_template_data()
        );

        $settemplates[0]->levelid = $this->slotlevelid;
        // This is active and in the level where we'll create slots.
        $this->settemplate = $settemplates[0];
        $this->settemplate->id = $DB->insert_record('openstudio_folder_templates', $settemplates[0]);

        // This is in the level, but has been deleted.
        $settemplates[1]->levelid = $this->slotlevelid;
        $settemplates[1]->status = mod_openstudio\local\api\levels::SOFT_DELETED;
        $DB->insert_record('openstudio_folder_templates', $settemplates[1]);

        // This is active, but in a different level.
        $otherlevelid = prev($slotlevels);
        $settemplate[2] = (object)array('levelid' => $otherlevelid);
        $this->othertemplate = $settemplates[2];
        $this->othertemplate->id = $DB->insert_record('openstudio_folder_templates', $settemplates[2]);

        // Create template slots.
        $this->templateslots = array(
                $this->generate_slot_template_data(),
                $this->generate_slot_template_data(),
                $this->generate_slot_template_data(),
                $this->generate_slot_template_data()
        );

        // 2 template slots in the template and active.
        $this->templateslots[0]->foldertemplateid = $this->settemplate->id;
        $this->templateslots[1]->foldertemplateid = $this->settemplate->id;
        // 1 in the template but deleted.
        $this->templateslots[2]->foldertemplateid = $this->settemplate->id;
        $this->templateslots[2]->status = mod_openstudio\local\api\levels::SOFT_DELETED;
        // 1 active but in another template.
        $this->templateslots[3]->foldertemplateid = $this->othertemplate->id;

        $this->templateslots[0]->id = $DB->insert_record('openstudio_content_templates', $this->templateslots[0]);
        $this->templateslots[1]->id = $DB->insert_record('openstudio_content_templates', $this->templateslots[1]);
        $this->templateslots[2]->id = $DB->insert_record('openstudio_content_templates', $this->templateslots[2]);
        $this->templateslots[3]->id = $DB->insert_record('openstudio_content_templates', $this->templateslots[3]);

        $this->templatedsets = array();
        $this->templatedsets['full'] = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                3, $this->slotlevelid, $this->generate_set_data());

        $this->templatedsets['under'] = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->two->id,
                3, $this->slotlevelid, $this->generate_set_data());

        $this->templatedsets['over'] = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->three->id,
                3, $this->slotlevelid, $this->generate_set_data());

        $this->templatedsets['empty'] = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->four->id,
                3, $this->slotlevelid, $this->generate_set_data());
        $this->templatedsets['offset'] = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->five->id,
                3, $this->slotlevelid, $this->generate_set_data());

        $templatedslots = array();
        $templatedsetslots = array();
        // Add 2 slots to 1 templated set.
        for ($i = 0; $i < 2; $i++) {
            $this->templatedslots[$i] = $this->generate_slot_data();
            $this->templatedslots[$i]->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                    $this->users->students->one->id,
                    0, 0, $this->templatedslots[$i]);
            $this->templatedsetslots[$i] = (object) array(
                    'folderid'             => $this->templatedsets['full'],
                    'contentid'            => $this->templatedslots[$i]->id,
                    'foldercontenttemplateid' => $this->templateslots[$i]->id,
                    'contentorder'         => $this->templateslots[$i]->contentorder,
                    'timemodified'      => time()
            );
            $this->templatedsetslots[$i]->id = $DB->insert_record('openstudio_folder_contents', $this->templatedsetslots[$i]);
        }

        // Add 1 slot to another.
        $i = count($this->templatedslots);
        $this->templatedslots[$i] = $this->generate_slot_data();
        $this->templatedslots[$i]->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->templatedslots[$i]);
        $this->templatedsetslots[] = (object) array(
                'folderid'             => $this->templatedsets['under'],
                'contentid'            => $this->templatedslots[$i]->id,
                'foldercontenttemplateid' => $this->templateslots[0]->id,
                'contentorder'         => $this->templateslots[0]->contentorder,
                'timemodified'      => time()
        );
        $this->templatedsetslots[$i]->id = $DB->insert_record('openstudio_folder_contents', $this->templatedsetslots[$i]);

        // Add 3 slots to another.
        for ($i = 0; $i < 3; $i++) {
            $j = $i + count($this->templatedslots);
            $this->templatedslots[$j] = $this->generate_slot_data();
            $this->templatedslots[$j]->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                    $this->users->students->one->id,
                    0, 0, $this->templatedslots[$j]);
            $this->templatedsetslots[$j] = (object) array(
                    'folderid'             => $this->templatedsets['over'],
                    'contentid'            => $this->templatedslots[$j]->id,
                    'timemodified'      => time()
            );
            if ($i < 2) {
                $this->templatedsetslots[$j]->foldercontenttemplateid = $this->templateslots[$i]->id;
                $this->templatedsetslots[$j]->contentorder = $this->templateslots[$i]->contentorder;
            } else {
                $this->templatedsetslots[$j]->contentorder = $i + 1;
            }
            $this->templatedsetslots[$j]->id = $DB->insert_record('openstudio_folder_contents', $this->templatedsetslots[$j]);
        }

        // Leave one blank.

        // Add 2 slots to one, but leave one templated position unfilled.
        for ($i = 1; $i < 3; $i++) {
            $j = $i + count($this->templatedslots);
            $this->templatedslots[$j] = $this->generate_slot_data();
            $this->templatedslots[$j]->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                    $this->users->students->one->id,
                    0, 0, $this->templatedslots[$j]);
            $this->templatedsetslots[$j] = (object) array(
                    'folderid'         => $this->templatedsets['offset'],
                    'contentid'        => $this->templatedslots[$j]->id,
                    'timemodified'  => time()
            );
            $templateslot = $this->templateslots[$i];
            $templateset = reset($settemplates);
            if (isset($templateslot->id)
                    && $templateslot->foldertemplateid == $templateset->id
                    && $templateslot->status == mod_openstudio\local\api\levels::ACTIVE
            ) {
                $this->templatedsetslots[$j]->foldercontenttemplateid = $this->templateslots[$i]->id;
            }
            $this->templatedsetslots[$j]->contentorder = $i + 1;
            $this->templatedsetslots[$j]->id = $DB->insert_record('openstudio_folder_contents', $this->templatedsetslots[$j]);
        }

        // Create 2 slots outside of any sets, belonging to 2 different users.
        $this->collectables = new stdClass();
        $this->collectables->one = $this->generate_slot_data();
        $this->collectables->one->visibility = mod_openstudio\local\api\content::VISIBILITY_MODULE;
        $this->collectables->one->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->collectables->one);
        $this->collectables->two = $this->generate_slot_data();
        $this->collectables->two->visibility = mod_openstudio\local\api\content::VISIBILITY_MODULE;
        $this->collectables->two->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->two->id,
                0, 0, $this->collectables->two);

        // Create a slot in a different studio.
        $this->altstudio = $this->generator->create_instance(array('course' => $this->course->id, 'enablefolders' => 1));
        $this->altstudio->leveldata = $this->generator->create_mock_levels($this->altstudio->id);
        $this->collectables->three = $this->generate_slot_data();
        $this->collectables->three->visibility = mod_openstudio\local\api\content::VISIBILITY_MODULE;
        $this->collectables->three->id = mod_openstudio\local\api\content::create($this->altstudio->id,
                $this->users->students->one->id,
                0, 0, $this->collectables->three);

        // Create a set of copied slots.
        $this->provenanceset = $this->generate_set_data();
        $this->provenanceset->id = mod_openstudio\local\api\content::create_in_pinboard($this->studiolevels->id,
                $this->users->students->one->id,
                $this->provenanceset);

        // Create a second set for soft-link test.
        $this->provenanceset2 = $this->generate_set_data();
        $this->provenanceset2->id = mod_openstudio\local\api\content::create_in_pinboard($this->studiolevels->id,
                $this->users->students->one->id,
                $this->provenanceset2);

        $this->provenance = new stdClass();

        // Original slot, not in the set.
        $this->provenance->original = $this->generate_slot_data();
        $this->provenance->original->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->original);

        // Copy of original slot, unedited.
        $this->provenance->copy1 = clone $this->provenance->original;
        $this->provenance->copy1->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->copy1);
        // Copy of copy1, unedited (gets recorded as a copy of original).
        $this->provenance->copy2 = clone $this->provenance->original;
        $this->provenance->copy2->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->copy2);
        // Copy of original slot, edited.
        $this->provenance->edited = clone $this->provenance->original;
        $this->provenance->edited->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->edited);
        // Copy of edited copy, unlinked.
        $this->provenance->unlinked = clone $this->provenance->original;
        $this->provenance->unlinked->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->unlinked);

        // Another slot, not in the set, to be soft-linked.
        $this->provenance->softlinked = $this->generate_slot_data();
        $this->provenance->softlinked->id = mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                0, 0, $this->provenance->softlinked);

        $this->provenance->copy1->setslot = (object) array(
                'folderid' => $this->provenanceset->id,
                'contentid' => $this->provenance->copy1->id,
                'contentorder' => 1,
                'provenanceid' => $this->provenance->original->id,
                'provenancestatus' => mod_openstudio\local\api\folder::PROVENANCE_COPY,
                'timemodified' => time()
        );
        $this->provenance->copy1->setslot->id = $DB->insert_record('openstudio_folder_contents', $this->provenance->copy1->setslot);
        $this->provenance->copy2->setslot = (object) array(
                'folderid' => $this->provenanceset->id,
                'contentid' => $this->provenance->copy2->id,
                'contentorder' => 2,
                'provenanceid' => $this->provenance->original->id,
                'provenancestatus' => mod_openstudio\local\api\folder::PROVENANCE_COPY,
                'timemodified' => time()
        );
        $this->provenance->copy2->setslot->id = $DB->insert_record('openstudio_folder_contents', $this->provenance->copy2->setslot);
        $this->provenance->edited->setslot = (object) array(
                'folderid' => $this->provenanceset->id,
                'contentid' => $this->provenance->edited->id,
                'contentorder' => 3,
                'provenanceid' => $this->provenance->original->id,
                'provenancestatus' => mod_openstudio\local\api\folder::PROVENANCE_EDITED,
                'timemodified' => time()
        );
        $this->provenance->edited->setslot->id = $DB->insert_record('openstudio_folder_contents',
                $this->provenance->edited->setslot);
        $this->provenance->unlinked->setslot = (object) array(
                'folderid' => $this->provenanceset->id,
                'contentid' => $this->provenance->unlinked->id,
                'contentorder' => 4,
                'provenanceid' => $this->provenance->edited->id,
                'provenancestatus' => mod_openstudio\local\api\folder::PROVENANCE_UNLINKED,
                'timemodified' => time()
        );
        $this->provenance->unlinked->setslot->id = $DB->insert_record('openstudio_folder_contents',
                $this->provenance->unlinked->setslot);

        $this->provenance->softlinked->setslot1 = (object) array(
                'folderid' => $this->provenanceset->id,
                'contentid' => $this->provenance->softlinked->id,
                'contentorder' => 4,
                'provenanceid' => $this->provenance->softlinked->id,
                'provenancestatus' => mod_openstudio\local\api\folder::PROVENANCE_COPY,
                'timemodified' => time()
        );
        $this->provenance->softlinked->setslot1->id = $DB->insert_record('openstudio_folder_contents',
                $this->provenance->softlinked->setslot1);

        $this->provenance->softlinked->setslot2 = (object) array(
                'folderid' => $this->provenanceset2->id,
                'contentid' => $this->provenance->softlinked->id,
                'contentorder' => 1,
                'provenanceid' => $this->provenance->softlinked->id,
                'provenancestatus' => mod_openstudio\local\api\folder::PROVENANCE_COPY,
                'timemodified' => time()
        );
        $this->provenance->softlinked->setslot2->id = $DB->insert_record('openstudio_folder_contents',
                $this->provenance->softlinked->setslot2);
    }

    protected function generate_set_data() {
        return (object) array(
                'name' => 'Test Set ' . random_string(),
                'attachments' => '',
                'embedcode' => '',
                'weblink' => '',
                'urltitle' => '',
                'visibility' => mod_openstudio\local\api\content::VISIBILITY_MODULE,
                'contenttype' => mod_openstudio\local\api\content::TYPE_FOLDER,
                'description' => random_string(),
                'tags' => array(random_string(), random_string(), random_string()),
                'ownership' => 0,
                'sid' => 0 // For a new slot.
        );
    }

    protected function generate_slot_data() {
        return (object) array(
                'name' => 'Lorem Ipsum ' . random_string(),
                'attachments' => '',
                'embedcode' => '',
                'weblink' => 'http://example.com',
                'urltitle' => 'An example weblink',
                'visibility' => mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'description' => random_string(),
                'tags' => array(random_string(), random_string(), random_string()),
                'ownership' => 0,
                'sid' => 0 // For a new slot.
        );
    }

    protected function generate_set_template_data() {
        return (object) array(
                'levelcontainer' => 3,
                'levelid' => 0,
                'guidance' => random_string(),
                'additionalslots' => 0,
                'status' => mod_openstudio\local\api\levels::ACTIVE
        );
    }

    protected function generate_slot_template_data() {
        $this->slottemplatecount++;
        return (object) array(
                'name' => 'Dolor sit amet ' . random_string(),
                'guidance' => random_string(),
                'permissions' => 0,
                'status' => mod_openstudio\local\api\levels::ACTIVE,
                'contentorder' => $this->slottemplatecount
        );
    }

    protected function tearDown() {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
    }
    /**
     * Add a slot to a set
     */
    public function test_studio_api_set_slot_add() {
        global $DB;
        $set = end($this->sets);
        $slot = end($this->slots);
        $userid = $this->users->students->one->id;

        // Check the slot's not already in the set.
        $params = array('folderid' => $set->id, 'contentid' => $slot->id);
        $this->assertFalse($DB->record_exists('openstudio_folder_contents', $params));

        $this->assertTrue((bool) studio_api_set_slot_add($set->id, $slot->id, $userid));

        $this->assertTrue($DB->record_exists('openstudio_folder_contents', $params));

        // Requests to add slots that are already added should be ignored, and not
        // create duplicates.
        $this->assertTrue((bool) studio_api_set_slot_add($set->id, $slot->id, $userid));
        $this->assertEquals(1, $DB->count_records('openstudio_folder_contents', $params));

        $fakesetid = $this->get_nonexistant_id('openstudio_contents');
        $fakeslotid = $this->get_nonexistant_id('openstudio_contents');
        $this->assertFalse((bool) studio_api_set_slot_add($fakesetid, $slot->id, $userid));
        $this->assertFalse((bool) studio_api_set_slot_add($set->id, $fakeslotid, $userid));
        $this->assertFalse((bool) studio_api_set_slot_add($fakesetid, $fakeslotid, $userid));
    }

    public function test_studio_api_set_template_create() {
        global $DB;
        // Get an unused level.
        $activitylevels = reset($this->studiolevels->leveldata['contentslevels']);
        $slotlevels = reset($activitylevels);
        $levelid = reset($slotlevels);
        // Check we dont already have an active template on this level.
        $params = array(
            'levelcontainer' => 3,
            'levelid'        => $levelid,
            'status'         => mod_openstudio\local\api\levels::ACTIVE
        );
        $this->assertFalse($DB->record_exists('openstudio_folder_templates', $params));

        $template = array(
            'guidance' => random_string(),
            'additionalcontents' => 1
        );
        studio_api_set_template_create(3, $levelid, $template);

        $select = <<<EOF
            levelcontainer = :levelcontainer
        AND levelid = :levelid
        AND status = :status
        AND additionalcontents = :additionalcontents
EOF;
        $select .= ' AND ' . $DB->sql_compare_text('guidance') . ' = :guidance';

        $this->assertTrue($DB->record_exists_select('openstudio_folder_templates', $select, array_merge($params, $template)));
    }

    public function test_studio_api_set_template_slot_create() {
        global $DB;

        $settemplate = $this->settemplate;

        $params = array('foldertemplateid' => $settemplate->id);
        $slotcount = $DB->count_records('openstudio_content_templates', $params);

        $template = array(
            'name' => random_string(),
            'guidance' => random_string(),
            'permissions' => mod_openstudio\local\api\folder::PERMISSION_REORDER
        );

        $template['id'] = studio_api_set_template_slot_create($settemplate->id, $template);
        $this->assertNotEmpty($template['id']);

        // Check that we've added a slot to the template.
        $this->assertEquals($slotcount + 1, $DB->count_records('openstudio_content_templates', $params));

        $select = 'id = :id AND permissions = :permissions AND foldertemplateid = :foldertemplateid AND ';
        $select .= $DB->sql_compare_text('name') . ' = :name  AND ';
        $select .= $DB->sql_compare_text('guidance') . ' = :guidance';
        // Check that the new template slot was created correctly.
        $this->assertTrue($DB->record_exists_select('openstudio_content_templates', $select, array_merge($template, $params)));
    }

    public function test_studio_api_set_containing_slot_get_by_setid() {
        global $DB;
        $setslot = reset($this->setslots);
        $set = reset($this->sets);

        // Get what we know to be the slot that contains the set.
        $setrecord = $DB->get_record('openstudio_contents', array('id' => $set->id));

        $containingslot = studio_api_set_containing_slot_get_by_setid($setslot->folderid);
        // Check that we get the correct slot back.
        $this->assertEquals($setrecord->id, $containingslot->id);
        $this->assertEquals($setrecord->name, $containingslot->name);

        $this->assertFalse(studio_api_set_containing_slot_get_by_setid($this->get_nonexistant_id('openstudio_contents')));
    }

    public function test_studio_api_set_slot_get_by_id() {
        $setslot = reset($this->setslots);

        $returnedsetslot = studio_api_set_slot_get_by_id($setslot->id);
        $this->assertNotEmpty($returnedsetslot);
        $this->assertEquals($setslot->id, $returnedsetslot->foldercontentid);
        $this->assertEquals($setslot->timemodified, $returnedsetslot->setslottimemodified);
        $this->assertEquals($setslot->folderid, $returnedsetslot->folderid);
        $this->assertEquals($setslot->contentid, $returnedsetslot->id);

        $this->assertFalse(studio_api_set_slot_get_by_id($this->get_nonexistant_id('openstudio_folder_contents')));
    }

    public function test_studio_api_set_slots_get() {
        $set = reset($this->sets);
        $slots = $this->slots;
        $setslots = $this->setslots;

        $returnedslots = studio_api_set_slots_get($set->id);

        $this->assertNotEmpty($returnedslots);
        // There are 4 slots, but only 2 are part of the set.
        $this->assertEquals(2, count($returnedslots));
        $this->assertTrue(array_key_exists(reset($slots)->id, $returnedslots));
        $this->assertTrue(array_key_exists(next($slots)->id, $returnedslots));
        $this->assertFalse(array_key_exists(next($slots)->id, $returnedslots));
        $this->assertFalse(array_key_exists(next($slots)->id, $returnedslots));

        $emptyset = next($this->sets);
        $this->assertEmpty(studio_api_set_slots_get($emptyset->id));

        $this->assertEmpty(studio_api_set_slots_get($this->get_nonexistant_id('openstudio_contents')));
    }

    public function test_studio_api_set_slots_get_deleted() {
        global $DB;
        $set = reset($this->sets);
        $deletedslot = (object) array(
            'openstudioid' => $this->studiolevels->id,
            'contenttype' => mod_openstudio\local\api\content::TYPE_NONE,
            'visibility' => mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
            'userid' => $this->users->students->one->id,
            'levelid' => 0,
            'levelcontainer' => 0,
            'showextradata' => 0,
            'deletedby' => $this->users->students->one->id,
            'deletedtime' => time(),
            'timemodified' => time(),
            'timeflagged' => time()
        );
        $deletedslot->id = $DB->insert_record('openstudio_contents', $deletedslot);
        $setslot = (object) array(
            'folderid' => $set->id,
            'contentid' => $deletedslot->id,
            'status' => mod_openstudio\local\api\levels::SOFT_DELETED,
            'slotmodified' => time(),
            'contentorder' => 1,
            'timemodified' => time()
        );
        $setslot->id = $DB->insert_record('openstudio_folder_contents', $setslot);
        $slotversion = (object) array(
            'contentid' => $deletedslot->id,
            'contenttype' => mod_openstudio\local\api\content::TYPE_TEXT,
            'name' => 'Deleted Slot',
            'description' => 'Deleted slot description',
            'deletedby' => $this->users->students->one->id,
            'deletedtime' => time(),
            'timemodified' => time()
        );
        $slotversion->id = $DB->insert_record('openstudio_content_versions', $slotversion);

        $slotsnotdeleted = studio_api_set_slots_get($set->id);
        $this->assertEquals(2, count($slotsnotdeleted));
        $this->assertFalse(array_key_exists($deletedslot->id, $slotsnotdeleted));

        $slotsdeleted = studio_api_set_slots_get_deleted($set->id);
        $this->assertEquals(1, count($slotsdeleted));
        $this->assertTrue(array_key_exists($slotversion->id, $slotsdeleted));
    }

    public function test_studio_api_set_slots_get_with_templates() {
        $fullsetid = $this->templatedsets['full'];
        // This set has 2 slots, the same as in the template,
        // So should get 2 real slots back.
        $fullslots = studio_api_set_slots_get_with_templates($fullsetid);
        $this->assertEquals(2, count($fullslots));
        // Check that we have 2 real and 0 template slots,
        // And that the slots are in the correct order.
        $this->check_template_slots($fullslots, 2, 0);

        $oversetid = $this->templatedsets['over'];
        // This set has 3 slots, more than the template,
        // so should get 3 back.
        $overslots = studio_api_set_slots_get_with_templates($oversetid);
        $this->assertEquals(3, count($overslots));
        // Check that all slots are full slots, not just templates.
        $this->check_template_slots($overslots, 3, 0);

        $undersetid = $this->templatedsets['under'];
        // This set has 1 slot, less than the template,
        // so should get 2 back (1 real and 1 template).
        $underslots = studio_api_set_slots_get_with_templates($undersetid);
        $this->assertEquals(2, count($underslots));
        // Check that 1 slot is full and 1 is a template.
        $this->check_template_slots($underslots, 1, 1);

        $emptysetid = $this->templatedsets['empty'];
        // This set has no slots, so should just get 2 template slots.
        $emptyslots = studio_api_set_slots_get_with_templates($emptysetid);
        $this->assertEquals(2, count($emptyslots));
        // Check that both slots are templates.
        $this->check_template_slots($emptyslots, 0, 2);

        $offsetsetid = $this->templatedsets['offset'];
        // This set has 2 slots, but one templated position remains unfilled,
        // so should get 3 back (2 real and 1 template).
        $offsetslots = studio_api_set_slots_get_with_templates($offsetsetid);
        $this->assertEquals(3, count($offsetslots));
        // Check that 2 slots are full and 1 is a template.
        $this->check_template_slots($offsetslots, 2, 1);

        // For non-templated slots, we should just get the slots and no templates.
        $set = reset($this->sets);
        $slots = $this->slots;
        $setslots = $this->setslots;

        $returnedslots = studio_api_set_slots_get_with_templates($set->id);

        // There are 4 slots, but only 2 are part of the set.
        $this->assertEquals(2, count($returnedslots));
        $firstslot = reset($setslots);
        $secondslot = next($setslots);
        $this->assertEquals($firstslot->contentid, reset($returnedslots)->id);
        $this->assertEquals($secondslot->contentid, next($returnedslots)->id);

        $this->assertEmpty(studio_api_set_slots_get_with_templates($this->get_nonexistant_id('openstudio_contents')));
    }

    public function test_studio_api_set_slot_get_first() {
        // Regular set with slots, should return the first slot.
        $set = reset($this->sets);
        $firstsetslot = reset($this->setslots);
        $firstslot = reset($this->slots);
        $returnedsetslot = studio_api_set_slot_get_first($set->id);

        $this->assertEquals($set->id, $returnedsetslot->folderid);
        $this->assertEquals($firstslot->id, $returnedsetslot->id);
        $this->assertEquals($firstslot->name, $returnedsetslot->name);
        $this->assertEquals($firstsetslot->id, $returnedsetslot->foldercontentid);
        $this->assertEquals(1, $returnedsetslot->contentorder);

        // Regular set without slots, should return false.
        $emptyset = next($this->sets);
        $this->assertFalse(studio_api_set_slot_get_first($emptyset->id));

        // Templated set with content in the first slot, should return the slot.
        $templatedsetid = $this->templatedsets['full'];
        $templatedfirstslot = $this->templatedslots[0];
        $templatedfirstsetslot = $this->templatedsetslots[0];
        $returnedsetslot = studio_api_set_slot_get_first($templatedsetid);

        $this->assertEquals($templatedsetid, $returnedsetslot->folderid);
        $this->assertEquals($templatedfirstslot->id, $returnedsetslot->id);
        $this->assertEquals($templatedfirstslot->name, $returnedsetslot->name);
        $this->assertEquals($templatedfirstsetslot->id, $returnedsetslot->foldercontentid);
        $this->assertEquals(1, $returnedsetslot->contentorder);

        // Templated slot with the first slot empty, should return the template.
        $offsetsetid = $this->templatedsets['offset'];
        $templatefirstslot = reset($this->templateslots);
        $returnedsetslot = studio_api_set_slot_get_first($offsetsetid);

        $this->assertEquals($templatefirstslot->name, $returnedsetslot->name);
        $this->assertEquals(1, $returnedsetslot->contentorder);

        $this->assertFalse(studio_api_set_slot_get_first($this->get_nonexistant_id('openstudio_contents')));
    }

    public function test_studio_api_set_template_get() {
        $set = reset($this->sets);

        // This set has no template, so should return false.
        $this->assertFalse(studio_api_set_template_get($set->id));

        $templatedsetid = $this->templatedsets['full'];
        // This set has a template, but there is also a deleted template
        // for this level. Make sure we get the right one.
        $template = studio_api_set_template_get($templatedsetid);
        $this->assertNotEmpty($template);
        $this->assertEquals($this->settemplate->id, $template->id);
    }

    public function test_studio_api_set_template_get_by_levelid() {
        $activitylevels = reset($this->studiolevels->leveldata['contentslevels']);
        $slotlevels = end($activitylevels);
        $emptyslotlevelid = end($slotlevels);

        // This level has no template, so should return false.
        $this->assertFalse(studio_api_set_template_get_by_levelid($emptyslotlevelid));

        // This level has a template, but there is also a deleted template
        // for this level. Make sure we get the right one.
        $template = studio_api_set_template_get_by_levelid($this->slotlevelid);
        $this->assertNotEmpty($template);
        $this->assertEquals($this->settemplate->id, $template->id);
    }

    public function test_studio_api_set_template_get_by_id() {
        global $DB;
        $templateid = $this->settemplate->id;
        $templaterecord = $DB->get_record('openstudio_folder_templates', array('id' => $templateid));
        $template = studio_api_set_template_get_by_id($templateid);
        $this->assertEquals($templaterecord, $template);

        $this->assertFalse(studio_api_set_template_get_by_id($this->get_nonexistant_id('openstudio_folder_templates')));
    }

    public function test_studio_api_set_template_slots_get() {
        global $DB;
        $templateid = $this->settemplate->id;

        // There are 2 active slots and 1 deleted slot in the template, check we only get the active ones.
        $templateslots = studio_api_set_template_slots_get($templateid);
        $this->assertEquals(2, count($templateslots));
        for ($i = 0; $i < 2; $i++) {
            $this->assertTrue(array_key_exists($this->templateslots[$i]->id, $templateslots));
        }

        $this->assertEmpty(studio_api_set_template_slots_get($this->get_nonexistant_id('openstudio_folder_templates')));
    }

    public function test_studio_api_set_template_slot_get() {
        global $DB;
        $slottemplateid = reset($this->templateslots)->id;
        $templaterecord = $DB->get_record('openstudio_content_templates', array('id' => $slottemplateid));

        $template = studio_api_set_template_slot_get($slottemplateid);
        $this->assertEquals($templaterecord, $template);

        $this->assertFalse(studio_api_set_template_slot_get($this->get_nonexistant_id('openstudio_content_templates')));
    }

    public function test_studio_api_set_template_slot_get_by_slotid() {
        global $DB;

        $templatedsetslot = reset($this->templatedsetslots);

        $templaterecord = $DB->get_record('openstudio_content_templates',
                array('id' => $templatedsetslot->foldercontenttemplateid));

        // Check that we get the correct template for the slot.
        $template = studio_api_set_template_slot_get_by_slotid($templatedsetslot->contentid);
        $this->assertEquals($templaterecord, $template);

        // This slot has no template, check that we don't get one.
        $slot = reset($this->slots);
        $this->assertFalse(studio_api_set_template_slot_get_by_slotid($slot->id));

        $this->assertFalse(studio_api_set_template_slot_get_by_slotid($this->get_nonexistant_id('openstudio_contents')));
    }

    public function test_studio_api_set_template_slot_get_by_slotorder() {
        global $DB;

        // Get the set template and one if the slots in it.
        $templateid = $this->settemplate->id;
        $templateslot = $this->templateslots[rand(0, 1)];

        $params = array('foldertemplateid' => $templateid, 'contentorder' => $templateslot->contentorder);
        $templaterecord = $DB->get_record('openstudio_content_templates', $params);

        // Check that we get the correct template for the slot.
        $template = studio_api_set_template_slot_get_by_slotorder($templateid, $templateslot->contentorder);
        $this->assertEquals($templaterecord, $template);

        // This slot exists in the template, but has been deleted.
        $deletedslot = $this->templateslots[2];
        $this->assertFalse(studio_api_set_template_slot_get_by_slotorder($templateid, $deletedslot->contentorder));

        // This slot is in a different template.
        $otherslot = end($this->templateslots);
        $this->assertFalse(studio_api_set_template_slot_get_by_slotorder($templateid, $otherslot->contentorder));

        // There is no slot template with this slotorder.
        $fakeslotorder = $otherslot->contentorder + 1;
        $this->assertFalse(studio_api_set_template_slot_get_by_slotorder($templateid, $fakeslotorder));

        // This set template doesn't exist.
        $this->assertFalse(studio_api_set_template_slot_get_by_slotorder($this->get_nonexistant_id('openstudio_folder_templates'),
                                                                         $templateslot->contentorder));
        $this->assertFalse(studio_api_set_template_slot_get_by_slotorder($this->get_nonexistant_id('openstudio_folder_templates'),
                                                                         $fakeslotorder));
    }


    public function test_studio_api_set_slot_update() {
        global $DB;
        sleep(1); // So we can check that timemodified is updated.
        $setslot = reset($this->setslots);
        $setslotrecord = $DB->get_record('openstudio_folder_contents', array('id' => $setslot->id));
        $updatedslot = (object) array(
            'id' => $setslot->id,
            'name' => random_string()
        );
        $this->assertNotEquals($setslotrecord->name, $updatedslot->name);

        $this->assertTrue(studio_api_set_slot_update($updatedslot));
        $updatedsetslotrecord = $DB->get_record('openstudio_folder_contents', array('id' => $setslot->id));
        $this->assertGreaterThan($setslotrecord->timemodified, $updatedsetslotrecord->timemodified);
        $this->assertEquals($updatedslot->name, $updatedsetslotrecord->name);

        $nonexistantslot = (object) array(
            'id' => $this->get_nonexistant_id('openstudio_folder_contents'),
            'name' => random_string()
        );
        $this->setExpectedException('coding_exception');
        studio_api_set_slot_update($nonexistantslot);
    }

    public function test_studio_api_set_slot_update_slotorders() {
        global $DB;
        sleep(1);
        $firstsetslot = reset($this->setslots);
        $secondsetslot = next($this->setslots);

        $firstrecord = $DB->get_record('openstudio_folder_contents', array('id' => $firstsetslot->id));
        $secondrecord = $DB->get_record('openstudio_folder_contents', array('id' => $secondsetslot->id));

        $neworders = array(
            $secondrecord->contentorder,
            $firstrecord->contentorder
        );

        studio_api_set_slot_update_slotorders($firstrecord->folderid, $neworders);

        $updatedfirstrecord = $DB->get_record('openstudio_folder_contents', array('id' => $firstsetslot->id));
        $updatedsecondrecord = $DB->get_record('openstudio_folder_contents', array('id' => $secondrecord->id));

        $this->assertNotEquals($firstrecord->contentorder, $updatedfirstrecord->contentorder);
        $this->assertEquals($secondrecord->contentorder, $updatedfirstrecord->contentorder);
        $this->assertGreaterThan($firstrecord->timemodified, $updatedfirstrecord->timemodified);

        $this->assertNotEquals($secondrecord->contentorder, $updatedsecondrecord->contentorder);
        $this->assertEquals($firstrecord->contentorder, $updatedsecondrecord->contentorder);
        $this->assertGreaterThan($secondrecord->timemodified, $updatedsecondrecord->timemodified);

        $fakesetid = $this->get_nonexistant_id('openstudio_contents');
        $fakeslotorder = 10000000;
        try {
            studio_api_set_slot_update_slotorders($fakesetid, $neworders);
        } catch (coding_exception $expected1) {
            try {
                studio_api_set_slot_update_slotorders($firstrecord->folderid, array($fakeslotorder => 1));
            } catch (coding_exception $expected2) {
                try {
                    studio_api_set_slot_update_slotorders($firstrecord->folderid, array(1 => 1, 2 => 1));
                } catch (coding_exception $expected3) {
                    return;
                }
            }
        }

        $this->fail('Expected coding_exception not thrown');

    }

    public function test_studio_api_set_template_update() {
        global $DB;
        $settemplate = $this->settemplate;

        $templaterecord = $DB->get_record('openstudio_folder_templates', array('id' => $settemplate->id));

        $templateupdate = (object) array(
            'id' => $settemplate->id,
            'guidance' => random_string(),
            'additionalcontents' => mt_rand(10, 20)
        );

        studio_api_set_template_update($templateupdate);

        $updatedtemplate = $DB->get_record('openstudio_folder_templates', array('id' => $settemplate->id));

        $this->assertNotEquals($templaterecord->guidance, $updatedtemplate->guidance);
        $this->assertEquals($templateupdate->guidance, $updatedtemplate->guidance);
        $this->assertNotEquals($templaterecord->additionalcontents, $updatedtemplate->additionalcontents);
        $this->assertEquals($templateupdate->additionalcontents, $updatedtemplate->additionalcontents);

        $faketemplate = (object) array(
            'id' => $this->get_nonexistant_id('openstudio_folder_templates'),
            'guidance' => random_string()
        );
        $this->setExpectedException('coding_exception');
        studio_api_set_template_update($faketemplate);

    }

    public function test_studio_api_set_template_slot_update() {
        global $DB;
        $slottemplate = reset($this->templateslots);

        $templaterecord = $DB->get_record('openstudio_content_templates', array('id' => $slottemplate->id));

        $templateupdate = (object) array(
            'id' => $slottemplate->id,
            'name' => random_string(),
            'guidance' => random_string()
        );

        studio_api_set_template_slot_update($templateupdate);

        $updatedtemplate = $DB->get_record('openstudio_content_templates', array('id' => $slottemplate->id));

        $this->assertNotEquals($templaterecord->guidance, $updatedtemplate->guidance);
        $this->assertEquals($templateupdate->guidance, $updatedtemplate->guidance);
        $this->assertNotEquals($templaterecord->name, $updatedtemplate->name);
        $this->assertEquals($templateupdate->name, $updatedtemplate->name);

        $faketemplate = (object) array(
            'id' => $this->get_nonexistant_id('openstudio_content_templates'),
            'guidance' => random_string()
        );
        $this->setExpectedException('coding_exception');
        studio_api_set_template_slot_update($faketemplate);
    }

    public function test_studio_api_set_slot_remove() {
        global $DB;
        $set = reset($this->sets);
        $slot = reset($this->slots);
        $userid = $this->users->students->one->id;

        // Verify that the slot is currently in the set.
        $params = array('folderid' => $set->id, 'contentid' => $slot->id, 'status' => mod_openstudio\local\api\levels::ACTIVE);
        $this->assertTrue($DB->record_exists('openstudio_folder_contents', $params));

        // Remove the slot from the set.
        studio_api_set_slot_remove($set->id, $slot->id, $userid);

        // Check that the slot has been removed from the set.
        $this->assertFalse($DB->record_exists('openstudio_folder_contents', $params));
        $params['status'] = mod_openstudio\local\api\levels::SOFT_DELETED;
        $this->assertTrue($DB->record_exists('openstudio_folder_contents', $params));

        $newslot = next($this->slots);
        $fakesetid = $this->get_nonexistant_id('openstudio_contents');
        $fakeslotid = $this->get_nonexistant_id('openstudio_contents');

        try {
            studio_api_set_slot_remove($set->id, $fakeslotid, $userid);
        } catch (coding_exception $expected1) {
            try {
                studio_api_set_slot_remove($fakesetid, $newslot->id, $userid);
            } catch (coding_exception $expected2) {
                try {
                    studio_api_set_slot_remove($fakesetid, $fakeslotid, $userid);
                } catch (coding_exception $expected3) {
                    return;
                }
            }
        }

        $this->fail('Expected coding_exception was not raised');
    }

    public function test_studio_api_set_empty() {
        global $DB;

        $set = reset($this->sets);
        $userid = $this->users->students->one->id;

        $params = array('folderid' => $set->id, 'status' => mod_openstudio\local\api\levels::ACTIVE);
        $slotcount = $DB->count_records('openstudio_folder_contents', $params);

        $this->assertEquals($slotcount, studio_api_set_empty($set->id, $userid));

        $this->assertFalse($DB->record_exists('openstudio_folder_contents', $params));
        $params['status'] = mod_openstudio\local\api\levels::SOFT_DELETED;
        $this->assertEquals($slotcount, $DB->count_records('openstudio_folder_contents', $params));

        $this->assertEquals(0, studio_api_set_empty($set->id, $userid));

        $this->setExpectedException('coding_exception');
        studio_api_set_empty($this->get_nonexistant_id('openstudio_contents'), $userid);

    }

    public function test_studio_api_set_template_delete() {
        global $DB;

        $template = $this->settemplate;
        // Verify that the template exists and has slot templates.
        $setparams = array('id' => $template->id, 'status' => mod_openstudio\local\api\levels::ACTIVE);
        $slotparams = array('foldertemplateid' => $template->id, 'status' => mod_openstudio\local\api\levels::ACTIVE);
        $this->assertTrue($DB->record_exists('openstudio_folder_templates', $setparams));
        $this->assertTrue($DB->record_exists('openstudio_content_templates', $slotparams));

        studio_api_set_template_delete($template->id);

        // Check that the template records have been marked deleted.
        $this->assertFalse($DB->record_exists('openstudio_folder_templates', $setparams));
        $this->assertFalse($DB->record_exists('openstudio_content_templates', $slotparams));
        $setparams['status'] = mod_openstudio\local\api\levels::SOFT_DELETED;
        $slotparams['status'] = mod_openstudio\local\api\levels::SOFT_DELETED;
        $this->assertTrue($DB->record_exists('openstudio_folder_templates', $setparams));
        $this->assertTrue($DB->record_exists('openstudio_content_templates', $slotparams));

        $this->setExpectedException('coding_exception');
        studio_api_set_template_delete($this->get_nonexistant_id('openstudio_folder_templates'));

    }

    public function test_studio_api_set_template_slot_delete() {
        global $DB;

        $template = reset($this->templateslots);
        $slotparams = array('id' => $template->id, 'status' => mod_openstudio\local\api\levels::ACTIVE);
        $this->assertTrue($DB->record_exists('openstudio_content_templates', $slotparams));

        // Get the other slots in the template.
        $params = array(
                'foldertemplateid' => $template->foldertemplateid,
                'status' => mod_openstudio\local\api\levels::ACTIVE
        );
        $slottemplates = $DB->get_records('openstudio_content_templates', $params);
        unset($slottemplates[$template->id]);

        studio_api_set_template_slot_delete($template->id);

        $this->assertFalse($DB->record_exists('openstudio_content_templates', $slotparams));
        $slotparams['status'] = mod_openstudio\local\api\levels::SOFT_DELETED;
        $this->assertTrue($DB->record_exists('openstudio_content_templates', $slotparams));

        // Verify that slotorder is changed for other slots.
        foreach ($slottemplates as $slottemplate) {
            $params = array('id' => $slottemplate->id, 'contentorder' => $slottemplate->contentorder - 1);
            $this->assertTrue($DB->record_exists('openstudio_content_templates', $params));
        }

        $this->setExpectedException('coding_exception');
        studio_api_set_template_slot_delete($this->get_nonexistant_id('openstudio_content_templates'));
    }

    /**
     * TODO: Currently fails as this uses defunct copy_to_pinboard function.
     */
    public function test_studio_api_set_slot_collect() {
        global $DB;

        $set = reset($this->sets);

        // Assert that the test slots do not currently belong to a set.
        $ids = array(
            $this->collectables->one->id,
            $this->collectables->two->id,
            $this->collectables->three->id
        );
        $userid = $this->users->students->one->id;
        list($usql, $params) = $DB->get_in_or_equal($ids);
        $this->assertFalse($DB->record_exists_select('openstudio_folder_contents', 'contentid ' . $usql, $params));

        // Test that we can soft-link a user's slot to their own set.
        $slotid = $this->collectables->one->id;
        $this->assertEquals(studio_api_set_slot_collect($set->id, $slotid, $userid, null, true), $slotid);
        $params = array('folderid' => $set->id, 'contentid' => $slotid);
        $setslot = $DB->get_record('openstudio_folder_contents', $params);
        $this->assertNotEquals(false, $setslot);
        $this->assertEquals($setslot->provenanceid, $slotid);
        $this->assertEquals($setslot->provenancestatus, mod_openstudio\local\api\folder::PROVENANCE_COPY);

        // Test that we can copy a user's slot to their own set.
        $slotid = studio_api_set_slot_collect($set->id, $this->collectables->one->id, $userid);
        $this->assertNotEquals(false, $slotid);
        $this->assertNotEquals($this->collectables->one->id, $slotid);
        // Verify that the new slot is a copy of the existing one.
        $params = array('id' => $slotid);
        $this->assertEquals($this->collectables->one->name, $DB->get_field('openstudio_contents', 'name', array('id' => $slotid)));
        // Verify that the new slot is in the set.
        $params = array('folderid' => $set->id, 'contentid' => $slotid);
        $setslot = $DB->get_record('openstudio_folder_contents', $params);
        $this->assertNotEquals(false, $setslot);
        $this->assertEquals($setslot->provenanceid, $this->collectables->one->id);
        $this->assertEquals($setslot->provenancestatus, mod_openstudio\local\api\folder::PROVENANCE_COPY);

        // Test that we cannot collect another user's slot.
        $caughtexception = false;
        try {
            studio_api_set_slot_collect($set->id, $this->collectables->two->id, $userid);
        } catch (coding_exception $expected1) {
            $caughtexception = true;
        }
        if (!$caughtexception) {
            $this->fail('Didn\'t catch excpected exception');
        }

        // Enable collection of other user's slots...
        $studiorecord = $DB->get_record('openstudio', array('id' => $this->studiolevels->id));
        $studiorecord->themefeatures += \mod_openstudio\local\util\feature::ENABLEFOLDERSANYCONTENT;
        $DB->update_record('openstudio', $studiorecord);

        // Test that we cannot soft-link another user's slot.
        $slotid = $this->collectables->two->id;
        $caughtexception = false;
        try {
            studio_api_set_slot_collect($set->id, $slotid, $userid, null, true);
        } catch (coding_exception $expected2) {
            $caughtexception = true;
        }
        if (!$caughtexception) {
            $this->fail('Didn\'t catch excpected exception');
        }
        $this->assertFalse($DB->record_exists('openstudio_folder_contents', array('contentid' => $slotid)));

        // Test that we can copy another user's slot.
        $slotid = studio_api_set_slot_collect($set->id, $this->collectables->two->id, $userid);
        $this->assertNotEquals(false, $slotid);
        $this->assertNotEquals($this->collectables->two->id, $slotid, $userid);
        // Verify that the new slot is a copy of the existing one.
        $params = array('id' => $slotid);
        $this->assertEquals($this->collectables->two->name, $DB->get_field('openstudio_contents', 'name', array('id' => $slotid)));
        // Verify that the new slot is in the set.
        $params = array('folderid' => $set->id, 'contentid' => $slotid);
        $setslot = $DB->get_record('openstudio_folder_contents', $params);
        $this->assertNotEquals(false, $setslot);
        $this->assertEquals($setslot->provenanceid, $this->collectables->two->id);
        $this->assertEquals($setslot->provenancestatus, mod_openstudio\local\api\folder::PROVENANCE_COPY);

        try {
            // Test that we cannot collect a slot from another studio instance.
            $slotid = $this->collectables->three->id;
            $this->assertFalse(studio_api_set_slot_collect($set->id, $slotid, $userid));
            $this->assertFalse($DB->record_exists('studo_folder_contents', array('slotid' => $slotid)));
        } catch (coding_exception $expected3) {
            $fakeid = $this->get_nonexistant_id('openstudio_contents');
            try {
                // Test that we cannot collect a non-existant slot.
                $this->assertFalse(studio_api_set_slot_collect($set->id, $fakeid, $userid));
                $this->assertFalse($DB->record_exists('studo_folder_contents', array('contentid' => $slotid)));
            } catch (coding_exception $expected4) {
                try {
                    // Test that we cannot collect a slot in a non-existant set.
                    $this->assertFalse(studio_api_set_slot_collect($fakeid, $this->collectables->one->id, $userid));
                    $this->assertFalse($DB->record_exists('studo_folder_contents', array('folderid' => $set->id)));
                } catch (coding_exception $expected5) {
                    return;
                }
            }
        }
        $this->fail('Didn\'t catch expected exception');

    }

    public function test_studio_api_set_determine_provenance() {
        // Original slot, not in a set, should return own ID.
        $this->assertEquals($this->provenance->original->id,
                studio_api_set_slot_determine_provenance($this->provenance->original->id));
        // Copy of original slot, should return original ID.
        $this->assertEquals($this->provenance->original->id,
                studio_api_set_slot_determine_provenance($this->provenance->copy1->id));
        // Copy of copy, should return original ID.
        $this->assertEquals($this->provenance->original->id,
                studio_api_set_slot_determine_provenance($this->provenance->copy2->id));
        // Edited copy of original, should return own ID.
        $this->assertEquals($this->provenance->edited->id,
                studio_api_set_slot_determine_provenance($this->provenance->edited->id));
        // Unlinked copy of edited copy, should return own ID.
        $this->assertEquals($this->provenance->unlinked->id,
                studio_api_set_slot_determine_provenance($this->provenance->unlinked->id));

        $this->setExpectedException('coding_exception');
        studio_api_set_slot_determine_provenance($this->get_nonexistant_id('openstudio_contents'));
    }

    public function test_studio_api_set_get_provenance() {
        // Original slot, has no provenance.
        $originalprovenance = studio_api_set_slot_get_provenance($this->provenanceset->id, $this->provenance->original->id);
        $this->assertNull($originalprovenance);
        // Copy of original, should return original.
        $copy1provenance = studio_api_set_slot_get_provenance($this->provenanceset->id, $this->provenance->copy1->id);
        $this->assertEquals($this->provenance->original->id, $copy1provenance->id);
        // Copy of copy1, should return original.
        $copy2provenance = studio_api_set_slot_get_provenance($this->provenanceset->id, $this->provenance->copy2->id);
        $this->assertEquals($this->provenance->original->id, $copy2provenance->id);
        // Edited copy of original, should return original.
        $editedprovenance = studio_api_set_slot_get_provenance($this->provenanceset->id, $this->provenance->edited->id);
        $this->assertEquals($this->provenance->original->id, $editedprovenance->id);
        // Unlinked copy of copy1, should not return provenance.
        $unlinkedprovenance = studio_api_set_slot_get_provenance($this->provenanceset->id, $this->provenance->unlinked->id);
        $this->assertNull($unlinkedprovenance);
        // Include unlinked, should return edited copy.
        $unlinkedprovenance = studio_api_set_slot_get_provenance($this->provenanceset->id, $this->provenance->unlinked->id, true);
        $this->assertEquals($this->provenance->edited->id, $unlinkedprovenance->id);
    }

    public function test_studio_api_set_slot_get_copies() {
        // Slots copy1, copy2 and edited are all copies of original, so should get all 3 back.
        $originalcopies = studio_api_set_slot_get_copies($this->provenance->original->id);
        $this->assertEquals(3, count($originalcopies));
        $originalcopyids = array_map(function($a) {
            return $a->contentid;
        }, $originalcopies);
        $this->assertContains($this->provenance->copy1->id, $originalcopyids);
        $this->assertContains($this->provenance->copy2->id, $originalcopyids);
        $this->assertContains($this->provenance->edited->id, $originalcopyids);

        // Slot copy2 was copied from copy1, but is recorded as a copy of original, so copy1 has no copies.
        $this->assertEmpty(studio_api_set_slot_get_copies($this->provenance->copy1->id));

        // Slot copy2 has no copies.
        $this->assertEmpty(studio_api_set_slot_get_copies($this->provenance->copy2->id));

        // Slot edited has a copy, but it's unlinked.
        $this->assertEmpty(studio_api_set_slot_get_copies($this->provenance->edited->id));
        $editedcopies = studio_api_set_slot_get_copies($this->provenance->edited->id, true);
        $this->assertEquals(1, count($editedcopies));
        $editedcopyids = array_map(function($a) {
            return $a->contentid;
        }, $editedcopies);
        $this->assertContains($this->provenance->unlinked->id, $editedcopyids);

        // Slot unlinked has no copies.
        $this->assertEmpty(studio_api_set_slot_get_copies($this->provenance->unlinked->id));
    }

    public function test_studio_api_set_slot_get_softlinks() {
        // Original only has copies, no softlinks, so we should get 0.
        $this->assertEquals(0, count(studio_api_set_slot_get_softlinks($this->provenance->original->id)));

        // Softlinked has 2 softlinks in different sets, so we should get 2.
        $softlinks = studio_api_set_slot_get_softlinks($this->provenance->softlinked->id);
        $this->assertEquals(2, count($softlinks));
        $this->assertTrue(array_key_exists($this->provenance->softlinked->setslot1->id, $softlinks));
        $this->assertTrue(array_key_exists($this->provenance->softlinked->setslot2->id, $softlinks));
    }

    /**
     * Test that we get the default limit for a new pinboard set
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_new_pinboard_set() {
        $this->assertEquals(\mod_openstudio\local\util\defaults::MAXPINBOARDFOLDERSCONTENTS,
                studio_api_set_get_addition_limit($this->permissions));
    }

    /**
     * Test that we get the defined limit for an empty pinboard set
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_empty_pinboard_set() {
        $setdata = array(
            'studio' => 'OS1',
            'name' => 'empty_pinboard_folder',
            'levelid' => 0,
            'levelcontainer' => 0,
            'userid' => $this->users->students->one->id
        );
        $setid = $this->generator->create_folders($setdata);
        $this->assertEquals($this->permissions->pinboardfolderlimit, studio_api_set_get_addition_limit($this->permissions, $setid));
    }

    /**
     * Test that we get the defined limit - number of slots for a populated pinboard set
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_populated_pinboard_set() {
        $setdata = array(
            'studio' => 'OS1',
            'name' => 'populated_pinboard_folder',
            'levelid' => 0,
            'levelcontainer' => 0,
            'userid' => $this->users->students->one->id
        );
        $setid = $this->generator->create_folders($setdata);
        $popcount = rand(1, \mod_openstudio\local\util\defaults::MAXPINBOARDFOLDERSCONTENTS);
        for ($i = 1; $i <= $popcount; $i++) {
            $slotdata = array(
                'studio' => 'OS1',
                'visibility' => mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $setdata['userid'],
                'name' => 'folder_content_' . $i,
                'contenttype' => mod_openstudio\local\api\content::TYPE_TEXT,
                'description' => random_string()
            );
            $slotid = $this->generator->create_contents($slotdata);
            $slot = mod_openstudio\local\api\content::get($slotid);
            $this->generator->create_folder_contents(array(
                'studio' => 'OS1',
                'folder' => $setdata['name'],
                'content' => $slot->name,
                'userid' => $this->users->students->one->id));
        }
        $this->assertEquals($this->permissions->pinboardfolderlimit - $popcount,
                studio_api_set_get_addition_limit($this->permissions, $setid));
    }

    /**
     * Test that we get the defined limit for a new templated set
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_new_predefined_set() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);
        $settemplatedata = array(
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        );
        $this->generator->create_folder_template($settemplatedata);

        $this->assertEquals($settemplatedata['additionalcontents'],
                studio_api_set_get_addition_limit($this->permissions, 0, $level3id));

    }

    /**
     * Test that we get the defined limit for an empty predefined set
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_empty_predefined_set() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);
        $settemplatedata = array(
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        );
        $this->generator->create_folder_template($settemplatedata);
        $setdata = array(
            'studio' => 'OS1',
            'name' => 'empty_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        );
        $setid = $this->generator->create_folders($setdata);
        $this->assertEquals($settemplatedata['additionalcontents'],
                studio_api_set_get_addition_limit($this->permissions, $setid));

    }

    /**
     * Test that we get the defined limit - number of slots for a populated templated set
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_populated_predefined_set() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);
        $settemplatedata = array(
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        );
        $this->generator->create_folder_template($settemplatedata);

        $setdata = array(
            'studio' => 'OS1',
            'name' => 'empty_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        );
        $setid = $this->generator->create_folders($setdata);
        $popcount = rand(1, $settemplatedata['additionalcontents']);
        for ($i = 1; $i <= $popcount; $i++) {
            $slotdata = array(
                'studio' => 'OS1',
                'visibility' => mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $setdata['userid'],
                'name' => 'folder_content_' . $i,
                'contenttype' => mod_openstudio\local\api\content::TYPE_TEXT,
                'description' => random_string()
            );
            $slotid = $this->generator->create_contents($slotdata);
            $slot = mod_openstudio\local\api\content::get($slotid);
            $setslotdata = array(
                'studio' => 'OS1',
                'folder' => $setdata['name'],
                'content' => $slot->name,
                'contentorder' => $i,
                'userid' => $this->users->students->one->id
            );
            $this->generator->create_folder_contents($setslotdata);
        }
        $this->assertEquals($settemplatedata['additionalcontents'] - $popcount,
                studio_api_set_get_addition_limit($this->permissions, $setid));

    }

    /**
     * Test that we get the defined limit for a templated set with slot templates
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_empty_predefined_set_with_slot_templates() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);
        $settemplatedata = array(
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        );
        $settemplateid = $this->generator->create_folder_template($settemplatedata);
        $this->generator->create_folder_content_template(array('foldertemplateid' => $settemplateid));
        $this->generator->create_folder_content_template(array('foldertemplateid' => $settemplateid));
        $setdata = array(
            'studio' => 'OS1',
            'name' => 'empty_predefined_folder_with_slot_templates',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        );
        $setid = $this->generator->create_folders($setdata);
        $this->assertEquals($settemplatedata['additionalcontents'],
                studio_api_set_get_addition_limit($this->permissions, $setid));
    }

    /**
     * Test that we get the defined limit - number of slots for a templated set with
     * slot templates.
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_populated_predefined_set_with_slot_templates() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);
        $settemplatedata = array(
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        );
        $settemplateid = $this->generator->create_folder_template($settemplatedata);
        $this->generator->create_folder_content_template(array('foldertemplateid' => $settemplateid));
        $this->generator->create_folder_content_template(array('foldertemplateid' => $settemplateid));

        $setdata = array(
            'studio' => 'OS1',
            'name' => 'empty_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        );
        $setid = $this->generator->create_folders($setdata);
        $popcount = rand(1, $settemplatedata['additionalcontents']);
        for ($i = 1; $i <= $popcount; $i++) {
            $slotdata = array(
                'studio' => 'OS1',
                'visibility' => mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $setdata['userid'],
                'name' => 'folder_content_' . $i,
                'contenttype' => mod_openstudio\local\api\content::TYPE_TEXT,
                'description' => random_string()
            );
            $slotid = $this->generator->create_contents($slotdata);
            $slot = mod_openstudio\local\api\content::get($slotid);
            $this->generator->create_folder_contents(array(
                    'studio' => 'OS1',
                    'folder' => $setdata['name'],
                    'content' => $slot->name,
                    'userid' => $this->users->students->one->id));
        }
        $this->assertEquals($settemplatedata['additionalcontents'] - $popcount,
                studio_api_set_get_addition_limit($this->permissions, $setid));

    }

    /**
     * Test that we get the definied limit - the number of slots *not including* templated slots
     * for a populated templated set with populated templated slots
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_populated_predefined_set_with_populated_slot_templates() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);
        $settemplatedata = array(
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        );
        $settemplateid = $this->generator->create_folder_template($settemplatedata);
        $slottemplateid1 = $this->generator->create_folder_content_template(array('foldertemplateid' => $settemplateid));
        $slottemplateid2 = $this->generator->create_folder_content_template(array('foldertemplateid' => $settemplateid));

        $setdata = array(
            'studio' => 'OS1',
            'name' => 'populated_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        );
        $setid = $this->generator->create_folders($setdata);
        $templatedslotdata = array(
            'studio' => 'OS1',
            'visibility' => mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
            'userid' => $setdata['userid'],
            'contenttype' => mod_openstudio\local\api\content::TYPE_TEXT,
            'description' => random_string()
        );
        $templatedslot1data = array_merge($templatedslotdata, array('name' => 'templated_slot_1'));
        $templatedslot2data = array_merge($templatedslotdata, array('name' => 'templated_slot_2'));
        $this->generator->create_contents($templatedslot1data);
        $this->generator->create_contents($templatedslot2data);
        $setslotdata = array(
            'studio' => 'OS1',
            'folder' => $setdata['name'],
            'userid' => $setdata['userid']
        );
        $setslotdata1 = array_merge($setslotdata,
                array('foldercontenttemplateid' => $slottemplateid1, 'content' => 'templated_slot_1'));
        $setslotdata2 = array_merge($setslotdata,
                array('foldercontenttemplateid' => $slottemplateid2, 'content' => 'templated_slot_2'));
        $this->generator->create_folder_contents($setslotdata1);
        $this->generator->create_folder_contents($setslotdata2);

        $popcount = rand(1, $settemplatedata['additionalcontents']);
        for ($i = 1; $i <= $popcount; $i++) {
            $slotdata = array(
                'studio' => 'OS1',
                'visibility' => mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $setdata['userid'],
                'name' => 'folder_content_' . $i,
                'contenttype' => mod_openstudio\local\api\content::TYPE_TEXT,
                'description' => random_string()
            );
            $slotid = $this->generator->create_contents($slotdata);
            $slot = mod_openstudio\local\api\content::get($slotid);
            $this->generator->create_folder_contents(array(
                'studio' => 'OS1',
                'folder' => $setdata['name'],
                'content' => $slot->name,
                'userid' => $setdata['userid']
            ));
        }
        $this->assertEquals($settemplatedata['additionalcontents'] - $popcount,
                studio_api_set_get_addition_limit($this->permissions, $setid));
    }

    /**
     * Test that we get 1 for a set slot template in an uninstantiated templated set
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_new_predefined_set_slot_template() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);
        $settemplatedata = array(
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        );
        $settemplateid = $this->generator->create_folder_template($settemplatedata);
        $slottemplateid = $this->generator->create_folder_content_template(array('foldertemplateid' => $settemplateid));

        $this->assertEquals(1, studio_api_set_get_addition_limit($this->permissions, 0, $level3id, $slottemplateid));

    }

    /**
     * Test that we get 1 for a set slot template in an instatiated templated set
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_empty_predefined_set_slot_template() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);
        $settemplatedata = array(
            'levelid' => $level3id,
            'additionalcontents' => rand(5, 100)
        );
        $settemplateid = $this->generator->create_folder_template($settemplatedata);
        $slottemplateid = $this->generator->create_folder_content_template(array('foldertemplateid' => $settemplateid));

        $setdata = array(
            'studio' => 'OS1',
            'name' => 'empty_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        );
        $setid = $this->generator->create_folders($setdata);
        $this->assertEquals(1, studio_api_set_get_addition_limit($this->permissions, $setid, 0, $slottemplateid));

    }

    /**
     * Test that we get the default set slot limit for an uninstatiated predefined set
     * with no template
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_new_predefined_set_notemplate() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);

        $this->assertEquals(\mod_openstudio\local\util\defaults::FOLDERTEMPLATEADDITIONALCONTENTS,
                studio_api_set_get_addition_limit($this->permissions, 0, $level3id));

    }

    /**
     * Test that we get the default set slot limit for an empty predefined set
     * with no template
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_empty_predefined_set_notemplate() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
            'level' => 2,
            'name' => 'predefined_folder_level2',
            'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
            'level' => 3,
            'name' => 'predefined_folder_level3',
            'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);
        $setdata = array(
            'studio' => 'OS1',
            'name' => 'empty_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        );
        $setid = $this->generator->create_folders($setdata);

        $this->assertEquals(\mod_openstudio\local\util\defaults::FOLDERTEMPLATEADDITIONALCONTENTS,
                studio_api_set_get_addition_limit($this->permissions, $setid));

    }

    /**
     * Test that we get the default - the number of slots for a populated predefined set
     * with no template
     *
     * @group mod_openstudio_set_addition_limit
     */
    public function test_set_addition_limit_populated_predefined_set_notemplate() {
        $level1data = array('studio' => 'OS1', 'level' => 1, 'name' => 'predefined_folder_level1');
        $level2data = array(
                'level' => 2,
                'name' => 'predefined_folder_level2',
                'parentid' => $this->generator->create_levels($level1data)
        );
        $level3data = array(
                'level' => 3,
                'name' => 'predefined_folder_level3',
                'parentid' => $this->generator->create_levels($level2data)
        );
        $level3id = $this->generator->create_levels($level3data);
        $setdata = array(
            'studio' => 'OS1',
            'name' => 'populated_predefined_folder',
            'levelid' => $level3id,
            'levelcontainer' => 3,
            'userid' => $this->users->students->one->id
        );
        $setid = $this->generator->create_folders($setdata);

        $popcount = rand(1, \mod_openstudio\local\util\defaults::FOLDERTEMPLATEADDITIONALCONTENTS);
        for ($i = 1; $i <= $popcount; $i++) {
            $slotdata = array(
                'studio' => 'OS1',
                'visibility' => mod_openstudio\local\api\content::VISIBILITY_INFOLDERONLY,
                'userid' => $setdata['userid'],
                'name' => 'folder_content_' . $i,
                'contenttype' => mod_openstudio\local\api\content::TYPE_TEXT,
                'description' => random_string()
            );
            $slotid = $this->generator->create_contents($slotdata);
            $slot = mod_openstudio\local\api\content::get($slotid);
            $this->generator->create_folder_contents(array(
                'studio' => 'OS1',
                'folder' => $setdata['name'],
                'content' => $slot->name,
                'userid' => $setdata['userid']
            ));
        }
        $this->assertEquals(\mod_openstudio\local\util\defaults::FOLDERTEMPLATEADDITIONALCONTENTS - $popcount,
                studio_api_set_get_addition_limit($this->permissions, $setid));

    }

    private function get_nonexistant_id($table) {
        global $DB;
        $randomid = mt_rand();
        while ($DB->record_exists($table, array('id' => $randomid))) {
            $randomid = mt_rand();
        }
        return $randomid;
    }

    private function check_template_slots($slots, $expectedrealcount, $expectedtemplatecount) {
        $realcount = 0;
        $templatecount = 0;
        $lastslotorder = 1;
        foreach ($slots as $slot) {
            if (isset($slot->description)) {
                $realcount++;
            } else {
                $templatecount++;
            }
            $this->assertGreaterThanOrEqual($lastslotorder, $slot->contentorder);
            $lastslotorder = $slot->contentorder;
        }
        $this->assertEquals($expectedrealcount, $realcount);
        $this->assertEquals($expectedtemplatecount, $templatecount);
    }

}
