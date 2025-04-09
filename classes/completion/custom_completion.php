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
     * @var string Completion tracking to My Activities section only.
     */
    public const COMPLETION_TRACKING_RESTRICTED = 'completiontrackingrestricted';

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
     * @var string Completion word count min.
     */
    public const COMPLETION_WORD_COUNT_MIN = 'completionwordcountmin';

    /**
     * @var string Completion word count max.
     */
    public const COMPLETION_WORD_COUNT_MAX = 'completionwordcountmax';

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
                self::COMPLETION_TRACKING_RESTRICTED,
                self::COMPLETION_POSTS,
                self::COMPLETION_COMMENTS,
                self::COMPLETION_POSTS_COMMENTS,
                self::COMPLETION_WORD_COUNT_MIN,
                self::COMPLETION_WORD_COUNT_MAX,
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
        $completionwordcountmin = $this->cm->customdata->customcompletionrules[self::COMPLETION_WORD_COUNT_MIN] ?? 0;
        $completionwordcountmax = $this->cm->customdata->customcompletionrules[self::COMPLETION_WORD_COUNT_MAX] ?? 0;
        $completiontrackingrestricted =
                $this->cm->customdata->customcompletionrules[self::COMPLETION_TRACKING_RESTRICTED] ?? 0;

        return [
                self::COMPLETION_POSTS => get_string('completiondetail:posts', 'openstudio', $completionposts),
                self::COMPLETION_COMMENTS => get_string('completiondetail:comments', 'openstudio', $completioncomments),
                self::COMPLETION_POSTS_COMMENTS => get_string('completiondetail:postscomments',
                        'openstudio', $completionpostscomments),
                self::COMPLETION_WORD_COUNT_MIN => get_string('completiondetail:wordcountmin', 'openstudio',
                        $completionwordcountmin),
                self::COMPLETION_WORD_COUNT_MAX => get_string('completiondetail:wordcountmax', 'openstudio',
                        $completionwordcountmax),
                self::COMPLETION_TRACKING_RESTRICTED =>
                        get_string('completiondetail:completiontrackingrestricted', 'openstudio',
                                $completiontrackingrestricted),
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

        $min = (int) $openstudio->completionwordcountmin ?? 0;
        $max = (int) $openstudio->completionwordcountmax ?? 0;
        $completiontrackingrestricted = (int) $openstudio->completiontrackingrestricted ?? 0;

        if ($openstudio->completionposts || $openstudio->completionpostscomments) {
            $excludecontents = [
                    content::TYPE_FOLDER,
                    content::TYPE_NONE,
            ];
            [$insql, $inparams] = $DB->get_in_or_equal($excludecontents, SQL_PARAMS_QM, 'param', false);

            // Define base query and conditions that are common to both scenarios.
            $basequery = "
                SELECT oc.id, oc.description, oc.userid, oc.deletedby, oc.deletedtime, oc.contenttype
                  FROM {openstudio_contents} oc
                  JOIN {openstudio} os ON oc.openstudioid = os.id AND os.id = ?
            ";

            $conditions = "
                 WHERE oc.userid = ?
                       AND oc.deletedby IS NULL AND oc.deletedtime IS NULL
                       AND oc.contenttype {$insql}
            ";

            // Build the complete query based on restriction settings.
            if ($completiontrackingrestricted > 0) {
                $countpostssql = $basequery . "
                    LEFT JOIN {openstudio_folder_contents} fc ON oc.id = fc.contentid
                    LEFT JOIN {openstudio_contents} c1 ON fc.folderid = c1.id"
                        . $conditions .
                        "AND (oc.levelid > 0 OR c1.levelid > 0)";
            } else {
                $countpostssql = $basequery . $conditions;
            }
            $postsparams = array_merge([$openstudio->id, $userid], $inparams);
            $posts = self::get_posts_meet_conditions($min, $max, $countpostssql, $postsparams, 'description');
            $totalposts = count($posts);
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

            $basequery = "
                SELECT oco.id, oco.contentid, oco.deletedby, oco.deletedtime, oco.userid, oco.commenttext
                  FROM {openstudio_comments} oco
                  JOIN {openstudio_contents} oc ON oco.contentid = oc.id
                       AND oc.deletedby IS NULL AND oc.deletedtime IS NULL
                       AND oc.contenttype {$commentinsql}
            ";

            $conditions = "
                 WHERE oco.userid = ?
                       AND oco.deletedby IS NULL AND oco.deletedtime IS NULL
            ";

            if ($completiontrackingrestricted > 0) {
                $countcommentssql = $basequery . "
                  LEFT JOIN {openstudio_folder_contents} fc ON oc.id = fc.contentid
                  LEFT JOIN {openstudio_contents} c1 ON fc.folderid = c1.id
                       JOIN {openstudio} os ON oc.openstudioid = os.id AND os.id = ?
                " . $conditions . "
                       AND (oc.levelid > 0 OR c1.levelid > 0)";
            } else {
                $countcommentssql = $basequery . "
                       JOIN {openstudio} os ON oc.openstudioid = os.id AND os.id = ?
                " . $conditions;
            }

            $countcommentsparams = [$openstudio->id, $userid, $userid];
            $countcommentsparams = array_merge($commentinparams, $countcommentsparams);
            $posts = self::get_posts_meet_conditions($min, $max, $countcommentssql, $countcommentsparams, 'commenttext');
            $totalcomments = count($posts);
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
     * Get list openstudio posts that meet wordcount conditions.
     *
     * @param int $min min completion word count.
     * @param int $max max completion word count.
     * @param string $sql SQL query for getting the post.
     * @param array|null $params Query parameters.
     * @param string $fieldname Query field for word count.
     * @return array Array of openstudio post objects.
     */
    private static function get_posts_meet_conditions(int $min = 0,
                                                      int $max = 0,
                                                      string $sql = '',
                                                      ?array $params = null,
                                                      string $fieldname = '',
    ): array {
        global $DB;

        $posts = [];

        if (!$records = $DB->get_records_sql($sql, $params)) {
            return [];
        }

        // If no word count constraints, return all records immediately.
        if ($min === 0 && $max === 0) {
            return $records;
        }

        foreach ($records as $key => $value) {
            $wordcount = count_words($value->$fieldname);

            if (($min === 0 || $wordcount >= $min) && ($max === 0 || $wordcount <= $max)) {
                $posts[$key] = $value;
            }
        }

        return $posts;
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
