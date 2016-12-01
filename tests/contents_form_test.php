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
 * Unit tests for OpenStudio content form
 *
 * TODO: New form class isn't yet in place, so this won't run yet.
 *
 * @package mod_studio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();
class contents_form_testcase extends advanced_testcase {

    /**
     * Test validation of Notebook files uploaded to a content.
     *
     * Checks a valid archive, a valid archive with a version 3 notebook, an empty one, one containing two many files,
     * and one containing the correctly named files but containing invalid data.
     */
    public function test_content_nbk_validation() {
        global $CFG, $USER;
        $this->setUser($this->users->students->one);
        $this->resetAfterTest(true);
        $context = context_user::instance($USER->id);
        $fs = get_file_storage();
        $options = array(
                'courseid' => $this->course->id,
                'feature_module' => 1,
                'feature_group' => 0,
                'isenrolled' => 1,
                'groupingid' => 1,
                'groupmode' => 0,
                'sharewithothers' => 0,
                'feature_contenttextuseshtml' => 0,
                'feature_contentusesfileupload' => 1,
                'feature_contentusesweblink' => 0,
                'feature_contentusesembedcode' => 0,
                'feature_contentallownotebooks' => 1,
                'defaultvisibility' => '',
                'allowedvisibility' => array(),
                'allowedfiletypes' => array('documents'),
                'contentid' => 0,
                'contenttype' => '',
                'contentname' => '',
                'isfoldercontent' => false
        );
        $form = new mod_openstudio_content_form(null, $options);
        $itemid = file_get_unused_draft_itemid();
        $validfile = (object) array(
                'contextid' => $context->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => 'test.nbk',
                'author' => fullname($USER),
                'filesize' => 1
        );
        $fs->create_file_from_pathname($validfile, $CFG->dirroot.'/mod/openstudio/tests/importfiles/test.nbk');
        $data1 = array('attachments' => $itemid);
        $errors1 = $form->validation($data1, array());

        $this->assertEmpty($errors1);

        $itemid = file_get_unused_draft_itemid();
        $validfile = (object) array(
                'contextid' => $context->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => 'testv3.nbk',
                'author' => fullname($USER),
                'filesize' => 1
        );
        $fs->create_file_from_pathname($validfile, $CFG->dirroot.'/mod/openstudio/tests/importfiles/testv3.nbk');
        $data2 = array('attachments' => $itemid);
        $errors2 = $form->validation($data2, array());

        $this->assertEmpty($errors2);

        $itemid = file_get_unused_draft_itemid();
        $emptyfile = (object) array(
                'contextid' => $context->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => 'empty.nbk',
                'author' => fullname($USER),
                'filesize' => 1
        );
        $fs->create_file_from_pathname($emptyfile, $CFG->dirroot.'/mod/openstudio/tests/importfiles/empty.nbk');
        $data3 = array('attachments' => $itemid);
        $errors3 = $form->validation($data3, array());

        $this->assertEquals(array('attachments' => get_string('errorcontentemptynotebook', 'mod_studio')), $errors3);

        $itemid = file_get_unused_draft_itemid();
        $fullfile = (object) array(
                'contextid' => $context->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => 'full.nbk',
                'author' => fullname($USER),
                'filesize' => 1
        );
        $fs->create_file_from_pathname($fullfile, $CFG->dirroot.'/mod/openstudio/tests/importfiles/full.nbk');
        $data4 = array('attachments' => $itemid);
        $errors4 = $form->validation($data4, array());

        $this->assertEquals(array('attachments' => get_string('errorcontentfullnotebook', 'mod_studio')), $errors4);

        $itemid = file_get_unused_draft_itemid();
        $invalidfile = (object) array(
                'contextid' => $context->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => 'malicious.nbk',
                'author' => fullname($USER),
                'filesize' => 1
        );
        $fs->create_file_from_pathname($invalidfile, $CFG->dirroot.'/mod/openstudio/tests/importfiles/malicious.nbk');
        $data5 = array('attachments' => $itemid);
        $errors5 = $form->validation($data5, array());

        $this->assertEquals(array('attachments' => get_string('errorcontentinvalidnotebook', 'mod_studio')), $errors5);
    }

}