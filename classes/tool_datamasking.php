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

use mod_openstudio\local\api\comments;

/**
 * Implementation of data masking for this plugin.
 *
 * The corresponding test script tool_datamasking_test.php checks every masked field.
 *
 * @package mod_openstudio
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_datamasking implements \tool_datamasking\plugin {

    public function build_plan(\tool_datamasking\plan $plan): void {
        $plan->table('files')->add(
                new files_mask('mod_openstudio', 'content'));
        $plan->table('files')->add(
                new \tool_datamasking\files_mask('mod_openstudio', 'description', 'openstudio_contents', 'description'));
        $plan->table('files')->add(
                new \tool_datamasking\files_mask('mod_openstudio', 'descriptionversion', 'openstudio_content_versions', 'description'));
        $plan->table('files')->add(
                new \tool_datamasking\files_mask('mod_openstudio', 'contentcomment'));
        $plan->table('files')->add(
                new \tool_datamasking\files_mask('mod_openstudio', comments::COMMENT_TEXT_AREA, 'openstudio_comments', 'commenttext'));
        $plan->table('files')->add(
                new \tool_datamasking\files_mask('mod_openstudio', 'contenttemp'));
        $plan->table('files')->add(
                new \tool_datamasking\files_mask('mod_openstudio', 'contentthumbnail'));
        $plan->table('files')->add(
                new \tool_datamasking\files_mask('mod_openstudio', 'notebook'));

        $plan->table('openstudio')->add(new \tool_datamasking\unique_email_mask('reportingemail'));
        $plan->table('openstudio_comments')->add(new \tool_datamasking\similar_text_mask(
                'title', false, \tool_datamasking\similar_text_mask::MODEL_SUBJECT));
        $plan->table('openstudio_comments')->add(new \tool_datamasking\similar_text_mask(
                'commenttext', true, \tool_datamasking\similar_text_mask::MODEL_POST));
        $plan->table('openstudio_contents')->add(new \tool_datamasking\fixed_value_mask(
                'ownershipdetail', 'Masked content'));
    }
}
