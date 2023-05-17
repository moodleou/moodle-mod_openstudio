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
 * Update group content task.
 *
 * @package    mod_openstudio
 * @copyright  2023 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_openstudio\task;

use cm_info;
use coding_exception;
use moodle_recordset;

class updated_group_moving_contents extends \core\task\scheduled_task {

    /** @var int Maximum records per schedule task process. */
    private int $maximumrecords = 1000;

    /** @var string For unit testing only. */
    private string $testmtracebuffer = '';

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cron_updategroupcontent', 'mod_openstudio');
    }

    /**
     * Get allow group mode.
     *
     * @return array
     */
    public function get_allow_group_mode(): array {
        return [
            SEPARATEGROUPS,
            VISIBLEGROUPS,
        ];
    }

    /**
     * Get maximum records per schedule task process.
     *
     * @return int
     */
    public function get_maximum_records(): int {
        return $this->maximumrecords;
    }

    /**
     * Run task. Throw error if anything goes wrong.
     */
    public function execute() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        try {
            $this->scan_and_update();
            $transaction->allow_commit();
        } catch (\Exception $e) {
            $DB->rollback_delegated_transaction($transaction, $e);
        }
    }

    /**
     * Scan all OS2 with separate/visible group mode and update incorrect contents.
     *
     * @throws coding_exception
     */
    public function scan_and_update(): void {

        $rs = $this->get_data();

        if (!$rs->valid()) {
            return;
        }

        $studios = [];
        $groups = [];

        foreach ($rs as $rec) {

            if (!array_key_exists($rec->instance, $studios)) {
                $studios[$rec->instance] = cm_info::create(get_coursemodule_from_id('openstudio', $rec->cmid));
            }

            $groupuserkey = $rec->instance . '_' . $rec->userid;
            if (!array_key_exists($groupuserkey, $groups)) {
                $data = groups_get_activity_allowed_groups($studios[$rec->instance], $rec->userid);

                usort($data, function($a, $b) {
                    return ($a->id > $b->id) ? -1 : 1;
                });

                $groups[$groupuserkey] = $data;
            }

            $allowedgroups = $groups[$groupuserkey];

            if (empty($allowedgroups)) {
                continue;
            }

            $latestgroupid = false;

            foreach ($allowedgroups as $allowedgroup) {
                $latestgroupid = $allowedgroup->id;
                break;
            }

            $currentgroupid = -$rec->visibility;

            if ($currentgroupid == $latestgroupid) {
                continue;
            }

            $this->update($rec->contentid, $latestgroupid);

            $message = get_string('cron_updategroupcontent:movedlog', 'mod_openstudio', (object) [
                'contentid' => $rec->contentid,
                'fromgroupid' => $currentgroupid,
                'togroupid' => $latestgroupid,
                'userid' => $rec->userid,
                'studioid' => $rec->instance,
                'courseid' => $rec->course,
            ]);

            $this->mtrace($message);
        }

        $rs->close();
    }

    /**
     * Get a list of OS2 activities using separate/visible groups.
     *
     * @return moodle_recordset
     */
    public function get_data(): moodle_recordset {
        global $DB;

        [$insql, $inparams] = $DB->get_in_or_equal($this->get_allow_group_mode());

        // Find contents have a group that users are no longer a member.
        $sql = "SELECT c.id as contentid, c.userid, c.visibility,
                       cm.instance, cm.groupingid, cm.id as cmid, cm.course,
                       c.userid, c.visibility
                  FROM {openstudio_contents} c
                  JOIN {openstudio} s ON s.id = c.openstudioid
                  JOIN {course_modules} cm ON cm.instance = s.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'openstudio'
             LEFT JOIN {groups_members} gm ON gm.userid = c.userid
                   AND gm.groupid = -c.visibility
                 WHERE cm.groupmode {$insql}
                   AND cm.groupingid > 0
                   AND c.visibility < 0
                   AND gm.id IS NULL";
        $params = $inparams;

        $sql .= ' UNION ';

        // Find contents have the groups that are no longer in the grouping.
        $sql .= "SELECT c.id as contentid, c.userid, c.visibility,
                        cm.instance, cm.groupingid, cm.id as cmid, cm.course,
                        c.userid, c.visibility
                   FROM {openstudio_contents} c
                   JOIN {openstudio} s ON s.id = c.openstudioid
                   JOIN {course_modules} cm ON cm.instance = s.id
                   JOIN {modules} m ON m.id = cm.module AND m.name = 'openstudio'
              LEFT JOIN {groupings_groups} gg ON gg.groupingid = cm.groupingid
                    AND gg.groupid = -c.visibility
                  WHERE cm.groupmode {$insql}
                    AND cm.groupingid > 0
                    AND c.visibility < 0
                    AND gg.id IS NULL";
        $params = array_merge($params, $inparams);

        $sql .= ' ORDER BY instance DESC, contentid DESC';

        return $DB->get_recordset_sql($sql, $params, 0, $this->get_maximum_records());
    }

    /**
     * Update to correct visibility.
     *
     * @param int $contentid
     * @param int $latestgroupid
     * @return void
     */
    private function update(int $contentid, int $latestgroupid): void {
        global $DB;
        $sql = "UPDATE {openstudio_contents}
                   SET visibility = ?
                 WHERE id = ?";
        $params = [-$latestgroupid, $contentid];
        $DB->execute($sql, $params);
    }

    /**
     * Outputs a line of text to the cron log.
     *
     * @param string $line Line of text
     */
    public function mtrace(string $line): void {
        if (PHPUNIT_TEST) {
            $this->testmtracebuffer .= $line . '\n';
            return;
        }

        mtrace($line);
    }

    /**
     * For unit testing only. Get the mtraced output, and clear it.
     *
     * @return string Mtraced output (since last call)
     * @throws coding_exception
     */
    public function get_and_clear_test_mtrace_buffer(): string {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception(get_string('unittestcodingexception', 'mod_openstudio'));
        }
        $result = $this->testmtracebuffer;
        $this->testmtracebuffer = '';
        return $result;
    }
}
