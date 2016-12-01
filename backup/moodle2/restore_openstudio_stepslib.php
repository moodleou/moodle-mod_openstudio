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
 * Define all the restore steps that will be used by the restore_openstudio_activity_task
 *
 * @package   mod_openstudio
 * @category  backup
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one openstudio activity
 *
 * @package   mod_openstudio
 * @category  backup
 * @copyright 2015 Your Name <your@email.adress>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_openstudio_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('openstudio', '/activity/openstudio');
        $paths[] = new restore_path_element('openstudio_level1', '/activity/openstudio/openstudio_level1');
        $paths[] = new restore_path_element('openstudio_level2',
            '/activity/openstudio/openstudio_level1/openstudio_level2');
        $paths[] = new restore_path_element('openstudio_level3',
            '/activity/openstudio/openstudio_level1/openstudio_level2/openstudio_level3');
        $paths[] = new restore_path_element('openstudio_folder_templates',
            '/activity/openstudio/openstudio_level1/openstudio_level2/openstudio_level3/openstudio_folder_templates');
        $paths[] = new restore_path_element('openstudio_content_templates',
            '/activity/openstudio/openstudio_level1/openstudio_level2/openstudio_level3/'
            .'openstudio_folder_templates/openstudio_content_templates');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_openstudio($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        unset($data->openstudio_level1);

        $newitemid = $DB->insert_record('openstudio', $data);
        $this->apply_activity_instance($newitemid);
        $this->openstudioid = $newitemid;
    }

    /**
     * Process the given restore path element data for level1
     *
     * @param array $data parsed element data
     */
    protected function process_openstudio_level1($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->openstudioid = $this->openstudioid;
        $newitemid = $DB->insert_record('openstudio_level1', $data);
        $this->set_mapping('openstudio_level1', $oldid, $newitemid);
    }

    /**
     * Process the given restore path element data for level2
     *
     * @param array $data parsed element data
     */
    protected function process_openstudio_level2($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->level1id = $this->get_new_parentid('openstudio_level1');
        $newitemid = $DB->insert_record('openstudio_level2', $data);
        $this->set_mapping('openstudio_level2', $oldid, $newitemid);
    }

    /**
     * Process the given restore path element data for level3
     *
     * @param array $data parsed element data
     */
    protected function process_openstudio_level3($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->level2id = $this->get_new_parentid('openstudio_level2');
        $newitemid = $DB->insert_record('openstudio_level3', $data);
        $this->set_mapping('openstudio_level3', $oldid, $newitemid);
    }

    /**
     * Process the given restore path element data for folder_templates
     *
     * @param array $data parsed element data
     */
    protected function process_openstudio_folder_templates($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->levelid = $this->get_new_parentid('openstudio_level3');
        $newitemid = $DB->insert_record('openstudio_folder_templates', $data);
        $this->set_mapping('openstudio_folder_templates', $oldid, $newitemid);
    }

    /**
     * Process the given restore path element data for content_templates
     *
     * @param array $data parsed element data
     */
    protected function process_openstudio_content_templates($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->foldertemplateid = $this->get_new_parentid('openstudio_folder_templates');
        $newitemid = $DB->insert_record('openstudio_content_templates', $data);
    }
}
