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

namespace mod_openstudio;

/**
 * Tests the tool_datamasking class for this plugin.
 *
 * @package mod_openstudio
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_datamasking_test extends \advanced_testcase {
    protected function tearDown(): void {
        global $CFG;
        if (file_exists("{$CFG->dirroot}/admin/tool/datamasking/version.php")) {
            \tool_datamasking\mapping_tables::reset();
            \tool_datamasking\files_mask::clear_statics();
            \tool_datamasking\masked_glossaries::clear_statics();
        }
        parent::tearDown();
    }

    /**
     * Tests actual behaviour of the masking applied in this plugin.
     */
    public function test_behaviour(): void {
        global $DB, $CFG;

        if (!file_exists("{$CFG->dirroot}/admin/tool/datamasking/version.php")) {
            $this->markTestSkipped('This test uses tool_datamasking, which is not installed. Skipping.');
        }

        $this->resetAfterTest();

        // Set up data to be masked.
        $openstudioid1 = $DB->insert_record('openstudio', ['course' => 0, 'name' => '',
                'timemodified' => 0, 'reportingemail' => 'secret@example.org']);
        $DB->insert_record('openstudio', ['course' => 0, 'name' => '', 'timemodified' => 0]);
        $DB->insert_record('openstudio_comments', ['contentid' => 0, 'userid' => 0,
                'timemodified' => 0, 'title' => 'Q', 'commenttext' => 'Q.']);
        $DB->insert_record('openstudio_comments', ['contentid' => 0, 'userid' => 0,
                'timemodified' => 0, 'commenttext' => '']);
        $DB->insert_record('openstudio_contents', ['openstudioid' => 0, 'levelid' => 0,
                'levelcontainer' => 0, 'userid' => 0, 'timemodified' => 0,
                'ownershipdetail' => 'Secret']);
        $DB->insert_record('openstudio_contents', ['openstudioid' => 0, 'levelid' => 0,
                'levelcontainer' => 0, 'userid' => 0, 'timemodified' => 0]);

        // Add some files.
        $fileids = [];
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_openstudio', 'content',
                'a.txt', 'a');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_openstudio', 'contentcomment',
                'b.txt', 'bb');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_openstudio', 'contenttemp',
                'c.txt', 'ccc');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_openstudio', 'contentthumbnail',
                'd.txt', 'dddd');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_openstudio', 'notebook',
                'e.txt', 'eeeee');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_openstudio', 'intro',
                'f.txt', 'ffffff');

        // Before checks.
        $openstudiosql = 'SELECT reportingemail FROM {openstudio} ORDER BY id';
        $this->assertEquals(['secret@example.org', null], $DB->get_fieldset_sql($openstudiosql));
        $openstudiocommentstitlesql = 'SELECT title FROM {openstudio_comments} ORDER BY id';
        $this->assertEquals(['Q', null], $DB->get_fieldset_sql($openstudiocommentstitlesql));
        $openstudiocommentscommenttextsql = 'SELECT commenttext FROM {openstudio_comments} ORDER BY id';
        $this->assertEquals(['Q.', ''], $DB->get_fieldset_sql($openstudiocommentscommenttextsql));
        $openstudiocontentssql = 'SELECT ownershipdetail FROM {openstudio_contents} ORDER BY id';
        $this->assertEquals(['Secret', ''], $DB->get_fieldset_sql($openstudiocontentssql));

        \tool_datamasking\testing_utils::check_file($this, $fileids[0], 'a.txt', 1);
        \tool_datamasking\testing_utils::check_file($this, $fileids[1], 'b.txt', 2);
        \tool_datamasking\testing_utils::check_file($this, $fileids[2], 'c.txt', 3);
        \tool_datamasking\testing_utils::check_file($this, $fileids[3], 'd.txt', 4);
        \tool_datamasking\testing_utils::check_file($this, $fileids[4], 'e.txt', 5);
        \tool_datamasking\testing_utils::check_file($this, $fileids[5], 'f.txt', 6);

        // Run the full masking plan including this plugin, but without requiring mapping tables.
        \tool_datamasking\api::get_plan()->execute([], [\tool_datamasking\tool_datamasking::TAG_SKIP_ID_MAPPING]);

        // After checks.
        $this->assertEquals(['email' . $openstudioid1 . '@open.ac.uk.invalid', null],
                $DB->get_fieldset_sql($openstudiosql));
        $this->assertEquals(['X', null], $DB->get_fieldset_sql($openstudiocommentstitlesql));
        $openstudiocommentscommenttextsql = 'SELECT commenttext FROM {openstudio_comments} ORDER BY id';
        $this->assertEquals(['X.', ''], $DB->get_fieldset_sql($openstudiocommentscommenttextsql));
        $this->assertEquals(['Masked content', ''], $DB->get_fieldset_sql($openstudiocontentssql));
        $maskedlength = strlen(file_get_contents($CFG->dirroot . '/admin/tool/datamasking/placeholders/text_plain.txt'));

        \tool_datamasking\testing_utils::check_file($this, $fileids[0], 'masked.txt', $maskedlength);
        \tool_datamasking\testing_utils::check_file($this, $fileids[1], 'masked.txt', $maskedlength);
        \tool_datamasking\testing_utils::check_file($this, $fileids[2], 'masked.txt', $maskedlength);
        \tool_datamasking\testing_utils::check_file($this, $fileids[3], 'masked.txt', $maskedlength);
        \tool_datamasking\testing_utils::check_file($this, $fileids[4], 'masked.txt', $maskedlength);
        \tool_datamasking\testing_utils::check_file($this, $fileids[5], 'f.txt', 6);
    }
}
