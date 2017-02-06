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
 * A scheduled task for Studio.
 *
 * @package mod_studio
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_openstudio\task;


class cleanup_files extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cron_cleanup', 'mod_openstudio');
    }

    /**
     * Function to be run periodically according to the moodle cron
     * This function searches for things that need to be done, such
     * as sending out mail, toggling flags etc ...
     *
     * Two jobs are performed:
     * 1) Delete temporary files generated when users export content from their studio.
     * 2) Remove files from slots that have been deleted.
     *
     * NOTE: task 2 should not be run if we want to keep the files for auditing purposes.
     * Current assumption is that users cannot undelete slots, so a deleted slot is
     * effectively useless.  If we don't clear up, then users can abuse the system
     * and effectively fill up the file system with deleted files.
     *
     * @return boolean
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/mod/openstudio/api/filesystem.php');

        try {
            studio_api_filesystem_export_delete_temporary_files();
        } catch (\Exception $e) {
            mtrace("Studio exception occurred deleting temporary files: " .
                    $e->getMessage() . "\n\n" .
                    $e->debuginfo . "\n\n" .
                    $e->getTraceAsString()."\n\n");
        }

        try {
            studio_api_filesystem_remove_deleted_slot_files_from_moodlefs();
        } catch (\Exception $e) {
            mtrace("Studio exception occurred removing deleted slot files: " .
                    $e->getMessage() . "\n\n" .
                    $e->debuginfo . "\n\n" .
                    $e->getTraceAsString()."\n\n");
        }
    }
}
