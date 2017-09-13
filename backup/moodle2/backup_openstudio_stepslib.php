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
 * Define all the backup steps that will be used by the backup_openstudio_activity_task
 *
 * @package   mod_openstudio
 * @category  backup
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete openstudio structure for backup, with file and id annotations
 *
 * @package   mod_openstudio
 * @category  backup
 * @copyright 2015 Your Name <your@email.adress>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_openstudio_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        // Define each element separated.
        $studio = new backup_nested_element('openstudio', array('id'),
            array('course', 'name', 'intro', 'introformat', 'contentmaxbytes',
                'reportingemail', 'defaultvisibility', 'allowedvisibility',
                'versioning', 'copying', 'flags', 'filetypes', 'sitename', 'pinboard',
                'pinboardname', 'level1name', 'level2name', 'level3name',
                'thememodulename', 'themegroupname', 'themestudioname', 'themepinboardname',
                'themefeatures', 'themehomedefault', 'themehelplink', 'themehelpname',
                'locking', 'pinboardsetlimit', 'tutorroles', 'timemodified'));

        $level1 = new backup_nested_element('openstudio_level1', array('id'),
            array('openstudioid', 'name', 'required', 'status', 'sortorder'));

        $level2 = new backup_nested_element('openstudio_level2', array('id'),
            array('level1id', 'name', 'required', 'hidelevel', 'status', 'sortorder'));

        $level3 = new backup_nested_element('openstudio_level3', array('id'),
            array('level2id', 'name', 'required', 'contenttype', 'status', 'sortorder', 'locktype', 'locktime', 'unlocktime'));

        $settemplate = new backup_nested_element('openstudio_folder_templates', array('id'),
            array('levelid', 'levelcontainer', 'guidance', 'additionalcontents', 'status'));

        $contenttemplate = new backup_nested_element('openstudio_content_templates', array('id'),
            array('foldertemplateid', 'name', 'guidance', 'permissions', 'contentorder', 'status'));

        // Build the tree.
        $studio->add_child($level1);
        $level1->add_child($level2);
        $level2->add_child($level3);
        $level3->add_child($settemplate);
        $settemplate->add_child($contenttemplate);

        // Define sources.
        $studio->set_source_table('openstudio', array('id' => backup::VAR_ACTIVITYID));
        $level1->set_source_table('openstudio_level1', array('openstudioid' => backup::VAR_ACTIVITYID));
        $level2->set_source_table('openstudio_level2', array('level1id' => '../id'));
        $level3->set_source_table('openstudio_level3', array('level2id' => '../id'));
        $settemplate->set_source_table('openstudio_folder_templates', array('levelid' => '../id'));
        $contenttemplate->set_source_table('openstudio_content_templates', array('foldertemplateid' => '../id'));

        // Define id annotations.
        // We're not linking to any external content so don't need these.
        // Define file annotations.
        // No file data is associated with this export.
        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($studio);
    }
}
