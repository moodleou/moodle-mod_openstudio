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
declare(strict_types=1);

namespace mod_openstudio\completion;

use cm_info;
use completion_info;
use core_completion\activity_custom_completion;
use mod_openstudio\local\api\content;

/**
 * Activity custom completion subclass for the data activity.
 *
 * Class for defining mod_openstudio's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given data instance and a user.
 *
 * @package mod_openstudio
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * @var string Completion posts name.
     */
    public const COMPLETION_POSTS = 'completionposts';

    /**
     * @var string Completion posts enable.
     */
    public const COMPLETION_POSTS_ENABLED = 'completionpostsenabled';

    /**
     * @var string Completion comments name.
     */
    public const COMPLETION_COMMENTS = 'completioncomments';

    /**
     * @var string Completion comments enable.
     */
    public const COMPLETION_COMMENTS_ENABLED = 'completioncommentsenabled';

    /**
     * @var string Completion posts + comments name.
     */
    public const COMPLETION_POSTS_COMMENTS = 'completionpostscomments';

    /**
     * @var string Completion posts + comments enable.
     */
    public const COMPLETION_POSTS_COMMENTS_ENABLED = 'completionpostscommentsenabled';

    /**
     * @param string $rule
     * @return int
     */
    public function get_state(string $rule): int {
        $this->validate_rule($rule);
        $result = static::get_completion_state($this->cm, $this->userid, $rule);
        return $result ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
                self::COMPLETION_POSTS,
                self::COMPLETION_COMMENTS,
                self::COMPLETION_POSTS_COMMENTS,
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $completionposts = $this->cm->customdata->customcompletionrules[self::COMPLETION_POSTS] ?? 0;
        $completioncomments = $this->cm->customdata->customcompletionrules[self::COMPLETION_COMMENTS] ?? 0;
        $completionpostscomments = $this->cm->customdata->customcompletionrules[self::COMPLETION_POSTS_COMMENTS] ?? 0;

        return [
                self::COMPLETION_POSTS => get_string('completiondetail:posts', 'openstudio', $completionposts),
                self::COMPLETION_COMMENTS => get_string('completiondetail:comments', 'openstudio', $completioncomments),
                self::COMPLETION_POSTS_COMMENTS => get_string('completiondetail:postscomments',
                        'openstudio', $completionpostscomments),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        $defaults = [
                'completionview',
        ];
        return array_merge($defaults, self::get_defined_custom_rules());
    }

    /**
     * Get completion state.
     *
     * @param cm_info $cm Course module object.
     * @param string $type Type of comparison.
     * @return bool|string True if completed, false if not (if no conditions, then
     *   return value is $type)
     */
    public static function get_completion_state(cm_info $cm, int $userid, string $type) {
        global $DB;

        $result = $type;
        $totalposts = $totalcomments = 0;

        $fields = 'id,' . implode(',', self::get_defined_custom_rules());
        $openstudio = $DB->get_record('openstudio', ['id' => $cm->instance],
                $fields, MUST_EXIST);

        if ($openstudio->completionposts || $openstudio->completionpostscomments) {
            $excludecontents = [
                    content::TYPE_FOLDER,
                    content::TYPE_NONE,
            ];
            [$insql, $inparams] = $DB->get_in_or_equal($excludecontents, SQL_PARAMS_QM, 'param', false);
            $countpostssql = "
        SELECT COUNT(1)
          FROM {openstudio_contents} oc
          JOIN {openstudio} os ON oc.openstudioid = os.id AND os.id = ?
         WHERE oc.userid = ?
               AND oc.deletedby IS NULL AND oc.deletedtime IS NULL
               AND oc.contenttype {$insql}
             ";
            // Exclude folders + contents on deleted folders.
            $postsparams = [
                    $openstudio->id,
                    $userid,
            ];
            $postsparams = array_merge($postsparams, $inparams);
            $totalposts = $DB->get_field_sql($countpostssql, $postsparams);
            $value = $openstudio->completionposts <= $totalposts;
            if ($openstudio->completionposts) {
                if ($type == COMPLETION_AND) {
                    $result = $result && $value;
                } else {
                    $result = $result || $value;
                }
            }
        }

        if ($openstudio->completioncomments || $openstudio->completionpostscomments) {
            $commentexcludecontents = [
                    content::TYPE_NONE,
            ];
            [$commentinsql, $commentinparams] = $DB->get_in_or_equal($commentexcludecontents, SQL_PARAMS_QM, 'param', false);
            $countcommentssql = "
            SELECT COUNT(1)
              FROM {openstudio_comments} oco
              JOIN {openstudio_contents} oc ON oco.contentid = oc.id
                   AND oc.deletedby IS NULL AND oc.deletedtime IS NULL
                   AND oc.contenttype {$commentinsql}
              JOIN {openstudio} os ON oc.openstudioid = os.id AND os.id = ?
             WHERE oco.userid = ?
                   AND oco.deletedby IS NULL AND oco.deletedtime IS NULL";
            $countcommentsparams = [$openstudio->id, $userid, $userid];
            $countcommentsparams = array_merge($commentinparams, $countcommentsparams);
            $totalcomments = $DB->get_field_sql($countcommentssql, $countcommentsparams);
            $value = $openstudio->completioncomments <= $totalcomments;
            if ($openstudio->completioncomments) {
                if ($type == COMPLETION_AND) {
                    $result = $result && $value;
                } else {
                    $result = $result || $value;
                }
            }
        }

        if ($openstudio->completionpostscomments) {
            // Should ensure total comments/posts count already loaded.
            $value = $openstudio->completionpostscomments <= ($totalcomments + $totalposts);
            if ($type == COMPLETION_AND) {
                $result = $result && $value;
            } else {
                $result = $result || $value;
            }
        }

        return $result;
    }

    /**
     * Updates completion status based on changes made to entire openstudio.
     *
     * @param \stdClass|\cm_info $cm
     * @param int $userid
     * @param int $possibleresult Expected completion result. If the event that
     *   has just occurred (e.g. add post) can only result in making the activity
     *   complete when it wasn't before, use COMPLETION_COMPLETE. If the event that
     *   has just occurred (e.g. delete post) can only result in making the activity
     *   not complete when it was previously complete, use COMPLETION_INCOMPLETE.
     *   Otherwise use COMPLETION_UNKNOWN. Setting this value to something other than
     *   COMPLETION_UNKNOWN significantly improves performance because it will abandon
     *   processing early if the user's completion state already matches the expected
     *   result. For manual events, COMPLETION_COMPLETE or COMPLETION_INCOMPLETE
     *   must be used; these directly set the specified state.
     * @param array $userdependencies - All userids that involved in specific context.
     * [ 1 => COMPLETION_INCOMPLETE, 2 => COMPLETION_INCOMPLETE]
     * @param bool $override
     * @return bool
     */
    public static function update_completion($cm, int $userid, int $possibleresult,
            array $userdependencies = [], bool $override = false
    ): bool {
        global $DB;
        if ($cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
            return false;
        }
        $course = $DB->get_record('course', [
                'id' => $cm->course,
        ], '*', MUST_EXIST);
        $completion = new completion_info($course);
        // Return early if no enabled completion.
        if (!$completion->is_enabled()) {
            return false;
        }
        $completion->update_state($cm, $possibleresult, $userid, $override);
        if (!empty($userdependencies)) {
            foreach ($userdependencies as $userid => $value) {
                $completion->update_state($cm, $value, $userid, $override);
            }
        }
        return true;
    }
}
