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
 * Template API unit tests.
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

defined('MOODLE_INTERNAL') || die();

class template_testcase extends \advanced_testcase {

    private $users;
    private $permissions;
    private $course;
    private $generator;
    private $studiolevels;
    private $folder;
    private $contenttemplatecount = 0;
    private $foldertemplate;
    private $contentlevelid;
    private $templatecontents;
    private $templatedfolders;
    private $templatedcontents;
    private $templatedfoldercontents;

    /**
     * Sets up our fixtures.
     */
    protected function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);
        $studentroleid = 5;
        $this->permissions = (object) [
                'pinboardfolderlimit' => \mod_openstudio\local\util\defaults::MAXPINBOARDFOLDERSCONTENTS
        ];

        // Our test data has 1 course with 2 student.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                ['email' => 'student1@ouunittest.com', 'username' => 'student1']);
        $this->users->students->two = $this->getDataGenerator()->create_user(
                ['email' => 'student2@ouunittest.com', 'username' => 'student2']);

        // Enroll our students and teacher (users) in the course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->one->id, $this->course->id, $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->two->id, $this->course->id, $studentroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $studiodata = ['course' => $this->course->id, 'enablefolders' => 1, 'idnumber' => 'OS1'];
        $this->studiolevels = $this->generator->create_instance($studiodata);
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);

        // Create a folder without a template.
        $this->folder = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->two->id,
                3, $this->contentlevelid, $this->generate_folder_data());

        // Create folder templates.
        $activitylevels = end($this->studiolevels->leveldata['contentslevels']);
        $slotlevels = end($activitylevels);
        $this->contentlevelid = end($slotlevels);

        $this->foldertemplate = $this->generate_folder_template_data();
        $this->foldertemplate->levelid = $this->contentlevelid;
        // This is active and in the level where we'll create slots.
        $this->foldertemplate->id = $DB->insert_record('openstudio_folder_templates', $this->foldertemplate);

        // Create template contents.
        $this->templatecontents = [
                $this->generate_content_template_data(),
                $this->generate_content_template_data(),
                $this->generate_content_template_data(),
                $this->generate_content_template_data()
        ];

        // 2 template contents in the template and active.
        $this->templatecontents[0]->foldertemplateid = $this->foldertemplate->id;
        $this->templatecontents[1]->foldertemplateid = $this->foldertemplate->id;
        // 1 in the template but deleted.
        $this->templatecontents[2]->foldertemplateid = $this->foldertemplate->id;
        $this->templatecontents[2]->status = \mod_openstudio\local\api\levels::SOFT_DELETED;

        $this->templatecontents[0]->id = $DB->insert_record('openstudio_content_templates', $this->templatecontents[0]);
        $this->templatecontents[1]->id = $DB->insert_record('openstudio_content_templates', $this->templatecontents[1]);
        $this->templatecontents[2]->id = $DB->insert_record('openstudio_content_templates', $this->templatecontents[2]);

        $this->templatedfolders = [];
        $this->templatedfolders['full'] = \mod_openstudio\local\api\content::create($this->studiolevels->id,
                $this->users->students->one->id,
                3, $this->contentlevelid, $this->generate_folder_data());

        // Add 2 contents to 1 templated folder.
        for ($i = 0; $i < 2; $i++) {
            $this->templatedcontents[$i] = $this->generate_content_data();
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

    }

    protected function tearDown(): void {
        $this->course = '';
        $this->generator = '';
        $this->studiolevels = '';
    }

    protected function generate_folder_data() {
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

    protected function generate_content_data() {
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

    protected function generate_folder_template_data() {
        return (object) [
                'levelcontainer' => 3,
                'levelid' => 0,
                'guidance' => random_string(),
                'additionalslots' => 0,
                'status' => \mod_openstudio\local\api\levels::ACTIVE
        ];
    }

    protected function generate_content_template_data() {
        $this->contenttemplatecount++;
        return (object) [
                'name' => 'Dolor sit amet ' . random_string(),
                'guidance' => random_string(),
                'permissions' => 0,
                'status' => \mod_openstudio\local\api\levels::ACTIVE,
                'contentorder' => $this->contenttemplatecount
        ];
    }

    private function get_nonexistant_id($table) {
        global $DB;
        $randomid = mt_rand();
        while ($DB->record_exists($table, ['id' => $randomid])) {
            $randomid = mt_rand();
        }
        return $randomid;
    }


    public function test_create() {
        global $DB;
        // Get an unused level.
        $activitylevels = reset($this->studiolevels->leveldata['contentslevels']);
        $slotlevels = reset($activitylevels);
        $levelid = reset($slotlevels);
        // Check we dont already have an active template on this level.
        $params = [
                'levelcontainer' => 3,
                'levelid'        => $levelid,
                'status'         => \mod_openstudio\local\api\levels::ACTIVE
        ];
        $this->assertFalse($DB->record_exists('openstudio_folder_templates', $params));

        $template = [
                'guidance' => random_string(),
                'additionalcontents' => 1
        ];
        \mod_openstudio\local\api\template::create($levelid, $template);

        $select = <<<EOF
            levelcontainer = :levelcontainer
        AND levelid = :levelid
        AND status = :status
        AND additionalcontents = :additionalcontents
EOF;
        $select .= ' AND ' . $DB->sql_compare_text('guidance') . ' = :guidance';

        $this->assertTrue($DB->record_exists_select('openstudio_folder_templates', $select, array_merge($params, $template)));
    }

    public function test_create_content() {
        global $DB;

        $foldertemplate = $this->foldertemplate;

        $params = ['foldertemplateid' => $foldertemplate->id];
        $contentcount = $DB->count_records('openstudio_content_templates', $params);

        $template = [
                'name' => random_string(),
                'guidance' => random_string(),
                'permissions' => \mod_openstudio\local\api\folder::PERMISSION_REORDER
        ];

        $template['id'] = \mod_openstudio\local\api\template::create_content($foldertemplate->id, $template);
        $this->assertNotEmpty($template['id']);

        // Check that we've added a slot to the template.
        $this->assertEquals($contentcount + 1, $DB->count_records('openstudio_content_templates', $params));

        $select = 'id = :id AND permissions = :permissions AND foldertemplateid = :foldertemplateid AND ';
        $select .= $DB->sql_compare_text('name') . ' = :name  AND ';
        $select .= $DB->sql_compare_text('guidance') . ' = :guidance';
        // Check that the new template slot was created correctly.
        $this->assertTrue($DB->record_exists_select('openstudio_content_templates', $select, array_merge($template, $params)));
    }

    public function test_get_by_folderid() {
        $templatedfolderid = $this->templatedfolders['full'];
        // This folder has a template, but there is also a deleted template
        // for this level. Make sure we get the right one.
        $template = \mod_openstudio\local\api\template::get_by_folderid($templatedfolderid);
        $this->assertNotEmpty($template);
        $this->assertEquals($this->foldertemplate->id, $template->id);
    }

    public function test_get_by_folderid_no_template() {
        // This folder has no template, so should return false.
        $this->assertFalse(\mod_openstudio\local\api\template::get_by_folderid($this->folder));
    }

    public function test_get_by_folderid_no_folder() {
        $id = $this->get_nonexistant_id('openstudio_contents');
        $this->assertFalse(\mod_openstudio\local\api\template::get_by_folderid($id));
    }

    public function test_get_template_by_levelid() {
        // This level has a template, but there is also a deleted template
        // for this level. Make sure we get the right one.
        $template = \mod_openstudio\local\api\template::get_by_levelid($this->contentlevelid);
        $this->assertNotEmpty($template);
        $this->assertEquals($this->foldertemplate->id, $template->id);
    }

    public function test_get_template_by_levelid_no_template() {
        $activitylevels = reset($this->studiolevels->leveldata['contentslevels']);
        $contentlevels = end($activitylevels);
        $emptycontentlevel = end($contentlevels);

        // This level has no template, so should return false.
        $this->assertFalse(\mod_openstudio\local\api\template::get_by_levelid($emptycontentlevel));
    }

    public function test_get() {
        global $DB;
        $templateid = $this->foldertemplate->id;
        $templaterecord = $DB->get_record('openstudio_folder_templates', ['id' => $templateid]);
        $template = \mod_openstudio\local\api\template::get($templateid);
        $this->assertEquals($templaterecord, $template);
    }

    public function test_get_no_template() {
        $id = $this->get_nonexistant_id('openstudio_folder_templates');
        $this->assertFalse(\mod_openstudio\local\api\template::get($id));
    }

    public function test_get_contents() {
        $templateid = $this->foldertemplate->id;

        // There are 2 active slots and 1 deleted slot in the template, check we only get the active ones.
        $templateslots = \mod_openstudio\local\api\template::get_contents($templateid);
        $this->assertEquals(2, count($templateslots));
        for ($i = 0; $i < 2; $i++) {
            $this->assertTrue(array_key_exists($this->templatecontents[$i]->id, $templateslots));
        }

    }

    public function test_get_contents_no_template() {
        $id = $this->get_nonexistant_id('openstudio_folder_templates');
        $this->assertEmpty(\mod_openstudio\local\api\template::get_contents($id));
    }

    public function test_get_content() {
        global $DB;
        $slottemplateid = reset($this->templatecontents)->id;
        $templaterecord = $DB->get_record('openstudio_content_templates', ['id' => $slottemplateid]);

        $template = \mod_openstudio\local\api\template::get_content($slottemplateid);
        $this->assertEquals($templaterecord, $template);
    }

    public function test_get_content_no_template() {
        $id = $this->get_nonexistant_id('openstudio_content_templates');
        $this->assertFalse(\mod_openstudio\local\api\template::get_content($id));
    }

    public function test_get_content_by_contentorder() {
        global $DB;

        // Get the folder template and one if the contents in it.
        $templateid = $this->foldertemplate->id;
        $contenttemplate = $this->templatecontents[rand(0, 1)];

        $params = ['foldertemplateid' => $templateid, 'contentorder' => $contenttemplate->contentorder];
        $templaterecord = $DB->get_record('openstudio_content_templates', $params);

        // Check that we get the correct template for the content.
        $template = \mod_openstudio\local\api\template::get_content_by_contentorder(
                $templateid, $contenttemplate->contentorder);
        $this->assertEquals($templaterecord, $template);
    }

    public function test_get_content_contentorder_deleted() {
        $templateid = $this->foldertemplate->id;
        // This content exists in the template, but has been deleted.
        $deletedslot = $this->templatecontents[2];
        $this->assertFalse(\mod_openstudio\local\api\template::get_content_by_contentorder(
                $templateid, $deletedslot->contentorder));
    }

    public function test_get_content_by_contentorder_wrong_folder() {
        // This content is in a different template.
        $templateid = $this->foldertemplate->id;
        $otherslot = end($this->templatecontents);
        $this->assertFalse(\mod_openstudio\local\api\template::get_content_by_contentorder(
                $templateid, $otherslot->contentorder));
    }

    public function test_get_content_by_contentorder_no_contentorder() {
        $templateid = $this->foldertemplate->id;
        $otherslot = end($this->templatecontents);
        // There is no content template with this slotorder.
        $fakeslotorder = $otherslot->contentorder + 1;
        $this->assertFalse(\mod_openstudio\local\api\template::get_content_by_contentorder($templateid, $fakeslotorder));
    }

    public function test_get_content_by_contentorder_no_folder() {
        // This folder template doesn't exist.
        $templateslot = $this->templatecontents[rand(0, 1)];
        $this->assertFalse(\mod_openstudio\local\api\template::get_content_by_contentorder(
                $this->get_nonexistant_id('openstudio_folder_templates'),
                $templateslot->contentorder));
    }

    public function test_update() {
        global $DB;
        $foldertemplate = $this->foldertemplate;

        $templaterecord = $DB->get_record('openstudio_folder_templates', ['id' => $foldertemplate->id]);

        $templateupdate = (object) [
                'id' => $foldertemplate->id,
                'guidance' => random_string(),
                'additionalcontents' => mt_rand(10, 20)
        ];

        \mod_openstudio\local\api\template::update($templateupdate);

        $updatedtemplate = $DB->get_record('openstudio_folder_templates', ['id' => $foldertemplate->id]);

        $this->assertNotEquals($templaterecord->guidance, $updatedtemplate->guidance);
        $this->assertEquals($templateupdate->guidance, $updatedtemplate->guidance);
        $this->assertNotEquals($templaterecord->additionalcontents, $updatedtemplate->additionalcontents);
        $this->assertEquals($templateupdate->additionalcontents, $updatedtemplate->additionalcontents);
    }

    public function test_update_fake_id() {
        $faketemplate = (object) [
                'id' => $this->get_nonexistant_id('openstudio_folder_templates'),
                'guidance' => random_string()
        ];
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\template::update($faketemplate);
    }

    public function test_update_no_id() {
        $faketemplate = (object) [
                'guidance' => random_string()
        ];
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\template::update($faketemplate);
    }

    public function test_update_content() {
        global $DB;
        $contenttemplate = reset($this->templatecontents);

        $templaterecord = $DB->get_record('openstudio_content_templates', ['id' => $contenttemplate->id]);

        $templateupdate = (object) [
                'id' => $contenttemplate->id,
                'name' => random_string(),
                'guidance' => random_string()
        ];

        \mod_openstudio\local\api\template::update_content($templateupdate);

        $updatedtemplate = $DB->get_record('openstudio_content_templates', ['id' => $contenttemplate->id]);

        $this->assertNotEquals($templaterecord->guidance, $updatedtemplate->guidance);
        $this->assertEquals($templateupdate->guidance, $updatedtemplate->guidance);
        $this->assertNotEquals($templaterecord->name, $updatedtemplate->name);
        $this->assertEquals($templateupdate->name, $updatedtemplate->name);
    }

    public function test_update_content_fake_id() {
        $faketemplate = (object) [
                'id' => $this->get_nonexistant_id('openstudio_content_templates'),
                'guidance' => random_string()
        ];
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\template::update_content($faketemplate);
    }

    public function test_update_content_no_id() {
        $faketemplate = (object) [
                'guidance' => random_string()
        ];
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\template::update_content($faketemplate);
    }

    public function test_delete() {
        global $DB;

        $template = $this->foldertemplate;
        // Verify that the template exists and has content templates.
        $folder = ['id' => $template->id, 'status' => \mod_openstudio\local\api\levels::ACTIVE];
        $contentparams = ['foldertemplateid' => $template->id, 'status' => \mod_openstudio\local\api\levels::ACTIVE];
        $this->assertTrue($DB->record_exists('openstudio_folder_templates', $folder));
        $this->assertTrue($DB->record_exists('openstudio_content_templates', $contentparams));

        \mod_openstudio\local\api\template::delete($template->id);

        // Check that the template records have been marked deleted.
        $this->assertFalse($DB->record_exists('openstudio_folder_templates', $folder));
        $this->assertFalse($DB->record_exists('openstudio_content_templates', $contentparams));
        $folder['status'] = \mod_openstudio\local\api\levels::SOFT_DELETED;
        $contentparams['status'] = \mod_openstudio\local\api\levels::SOFT_DELETED;
        $this->assertTrue($DB->record_exists('openstudio_folder_templates', $folder));
        $this->assertTrue($DB->record_exists('openstudio_content_templates', $contentparams));
    }

    public function test_delete_no_template() {
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\template::delete($this->get_nonexistant_id('openstudio_folder_templates'));
    }

    public function test_delete_content() {
        global $DB;

        $template = reset($this->templatecontents);
        $slotparams = ['id' => $template->id, 'status' => \mod_openstudio\local\api\levels::ACTIVE];
        $this->assertTrue($DB->record_exists('openstudio_content_templates', $slotparams));

        // Get the other slots in the template.
        $params = [
                'foldertemplateid' => $template->foldertemplateid,
                'status' => \mod_openstudio\local\api\levels::ACTIVE
        ];
        $slottemplates = $DB->get_records('openstudio_content_templates', $params);
        unset($slottemplates[$template->id]);

        \mod_openstudio\local\api\template::delete_content($template->id);

        $this->assertFalse($DB->record_exists('openstudio_content_templates', $slotparams));
        $slotparams['status'] = \mod_openstudio\local\api\levels::SOFT_DELETED;
        $this->assertTrue($DB->record_exists('openstudio_content_templates', $slotparams));

        // Verify that slotorder is changed for other slots.
        foreach ($slottemplates as $slottemplate) {
            $params = ['id' => $slottemplate->id, 'contentorder' => $slottemplate->contentorder - 1];
            $this->assertTrue($DB->record_exists('openstudio_content_templates', $params));
        }
    }

    public function test_delete_content_no_template() {
        $this->expectException('coding_exception');
        \mod_openstudio\local\api\template::delete_content($this->get_nonexistant_id('openstudio_content_templates'));
    }

}
