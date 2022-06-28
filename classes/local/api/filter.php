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
 * Class for Filter API functions.
 *
 * @package    mod_openstudio
 * @copyright  2022 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

use coding_exception;
use mod_openstudio\local\util\defaults;

defined('MOODLE_INTERNAL') || die();

/**
 * Filter API functions.
 */
class filter {

    const PAGE_VIEW = 1;
    const PAGE_PEOPLE = 2;

    const SORT_BY_DATE_ASCENDING = 1;
    const SORT_BY_DATE_DESCENDING = 2;
    const SORT_BY_USERNAME_ASCENDING = 3;
    const SORT_BY_USERNAME_DESCENDING = 4;
    const SORT_BY_ACTIVITYTITLE_ASCENDING = 5;
    const SORT_BY_ACTIVITYTITLE_DESCENDING = 6;

    const FILTER_COMBINATION_MY_POSTS_WITH_MY_COMMENTS = 1;
    const FILTER_COMBINATION_MY_POSTS_WITH_USERS_COMMENTS = 2;
    const FILTER_COMBINATION_USERS_POSTS_WITH_MY_COMMENTS = 3;
    const FILTER_COMBINATION_USERS_POSTS_WITH_FEEDBACK_REQUESTS = 4;

    /**
     * Sort by params.
     *
     * @return array[]
     */
    private static function sort_by_params(): array {
        return [
                self::SORT_BY_DATE_ASCENDING => [
                        'fsort' => stream::SORT_BY_DATE,
                        'osort' => stream::SORT_ASC,
                ],
                self::SORT_BY_DATE_DESCENDING => [
                        'fsort' => stream::SORT_BY_DATE,
                        'osort' => stream::SORT_DESC,
                ],
                self::SORT_BY_USERNAME_ASCENDING => [
                        'fsort' => stream::SORT_BY_USERNAME,
                        'osort' => stream::SORT_ASC,
                ],
                self::SORT_BY_USERNAME_DESCENDING => [
                        'fsort' => stream::SORT_BY_USERNAME,
                        'osort' => stream::SORT_DESC,
                ],
                self::SORT_BY_ACTIVITYTITLE_ASCENDING => [
                        'fsort' => stream::SORT_BY_ACTIVITYTITLE,
                        'osort' => stream::SORT_ASC,
                ],
                self::SORT_BY_ACTIVITYTITLE_DESCENDING => [
                        'fsort' => stream::SORT_BY_ACTIVITYTITLE,
                        'osort' => stream::SORT_DESC,
                ],
        ];
    }

    /**
     * Quick Select params.
     *
     * @return array[]
     */
    private static function quick_select_params(): array {
        return [
                self::FILTER_COMBINATION_MY_POSTS_WITH_MY_COMMENTS => [
                        'ftypearray' => [
                                content::TYPE_NONE,
                        ],
                        'fflagarray' => [
                                stream::FILTER_COMMENTS,
                        ],
                        'fstatus' => stream::FILTER_STATUS_ALL_POST,
                        'fscope' => stream::SCOPE_MY,
                ],
                self::FILTER_COMBINATION_MY_POSTS_WITH_USERS_COMMENTS => [
                        'ftypearray' => [
                                content::TYPE_NONE,
                        ],
                        'fflagarray' => [
                                stream::FILTER_COMMENTS,
                        ],
                        'fstatus' => stream::FILTER_STATUS_ALL_POST,
                        'fscope' => stream::SCOPE_THEIRS,
                ],
                self::FILTER_COMBINATION_USERS_POSTS_WITH_MY_COMMENTS => [
                        'ftypearray' => [
                                content::TYPE_NONE,
                        ],
                        'fflagarray' => [
                                stream::FILTER_COMMENTS,
                        ],
                        'fstatus' => stream::FILTER_STATUS_ALL_POST,
                        'fscope' => stream::SCOPE_MY,
                        'fownerscope' => stream::SCOPE_THEIRS,
                ],
                self::FILTER_COMBINATION_USERS_POSTS_WITH_FEEDBACK_REQUESTS => [
                        'ftypearray' => [
                                content::TYPE_NONE,
                        ],
                        'fflagarray' => [
                                stream::FILTER_HELPME,
                        ],
                        'fstatus' => stream::FILTER_STATUS_ALL_POST,
                        'fscope' => stream::SCOPE_THEIRS,
                        'fownerscope' => stream::SCOPE_THEIRS,
                ],
        ];
    }

    /**
     * Define all quick select by stream view.
     *
     * @param int $vid
     * @return array
     * @throws coding_exception
     */
    public static function get_quick_select_params_stream_view(int $vid): array {
        $result = [];

        switch ($vid) {
            case content::VISIBILITY_PRIVATE:
            case content::VISIBILITY_PRIVATE_PINBOARD:
                $result = [
                        self::FILTER_COMBINATION_MY_POSTS_WITH_MY_COMMENTS
                        => get_string('filtermypostswithmycomments', 'mod_openstudio'),
                        self::FILTER_COMBINATION_MY_POSTS_WITH_USERS_COMMENTS
                        => get_string('filtermypostswithotheruserscomments', 'mod_openstudio'),
                ];
                break;
            case content::VISIBILITY_GROUP:
            case content::VISIBILITY_MODULE:
                $result = [
                        self::FILTER_COMBINATION_USERS_POSTS_WITH_MY_COMMENTS
                        => get_string('filterotheruserspostswithmycomments', 'mod_openstudio'),
                        self::FILTER_COMBINATION_USERS_POSTS_WITH_FEEDBACK_REQUESTS
                        => get_string('filterotheruserspostswithfeedbackrequests',
                                'mod_openstudio'),
                ];
                break;
        }

        return $result;
    }

    /**
     * Get sort by dropdown by page view.
     *
     * @param int $id
     * @return array
     * @throws coding_exception
     */
    public static function get_sort_by_dropdown(int $id): array {
        $data = [
                self::SORT_BY_DATE_ASCENDING => get_string('filterdateascending', 'mod_openstudio'),
                self::SORT_BY_DATE_DESCENDING => get_string('filterdatedescending', 'mod_openstudio'),
        ];

        switch ($id) {
            case self::PAGE_PEOPLE:
                $custom = [
                        self::SORT_BY_USERNAME_ASCENDING => get_string('filtersortbynameascending', 'mod_openstudio'),
                        self::SORT_BY_USERNAME_DESCENDING => get_string('filtersortbynamedescending', 'mod_openstudio'),
                ];
                break;
            default:
                $custom = [
                        self::SORT_BY_ACTIVITYTITLE_ASCENDING => get_string('filtertitleascending', 'mod_openstudio'),
                        self::SORT_BY_ACTIVITYTITLE_DESCENDING => get_string('filtertitledescending', 'mod_openstudio'),
                ];
        }

        return $data + $custom;
    }

    /**
     * Get sort by params.
     *
     * @param int|null $key
     * @return array|null
     */
    public static function get_sort_by_params(?int $key): ?array {
        if ($key === null) {
            return null;
        }
        $list = static::sort_by_params();
        if (empty($list[$key])) {
            return null;
        }
        $data = $list[$key];

        $requiredfields = [
                'fsort',
                'osort',
        ];

        foreach ($requiredfields as $field) {
            if (!array_key_exists($field, $data)) {
                return null;
            }
        }

        return $data;
    }

    /**
     * Get Quick Select params.
     *
     * @param int|null $key
     * @return array|null
     */
    public static function get_quick_select_params(?int $key): ?array {
        if ($key === null) {
            return null;
        }
        $list = self::quick_select_params();
        if (empty($list[$key])) {
            return null;
        }

        $data = $list[$key];

        $requiredfields = [
                'ftypearray',
                'fflagarray',
                'fstatus',
                'fscope',
        ];

        foreach ($requiredfields as $field) {
            if (!array_key_exists($field, $data)) {
                return null;
            }
        }

        return $data;
    }

    /**
     * Build array list for quick select filter.
     *
     * @param int $vid
     * @param int|null $quickselect
     * @return array
     *     [
     *          [
     *              'value'    => 1,
     *              'text'     => 'Date ascending',
     *              'params'   => '{"ftypearray":[0],"fflagarray":[8],"fstatus":0,"fscope":2}',
     *              'selected' => true,
     *          ],
     *     ],
     * @throws coding_exception
     */
    public static function build_quick_select_filter(int $vid, ?int $quickselect): array {
        $defaultparams = [
                'ftypearray' => [content::TYPE_NONE],
                'fflagarray' => [flags::DEFAULT],
                'fstatus' => stream::FILTER_STATUS_ALL_POST,
                'fscope' => stream::SCOPE_EVERYONE,
                'fownerscope' => stream::SCOPE_EVERYONE,
        ];
        $result = [];
        $result[] = [
                'value' => null,
                'text' => get_string('filterquickselect', 'mod_openstudio'),
                'params' => json_encode($defaultparams),
                'selected' => $quickselect == null,
        ];

        $data = static::get_quick_select_params_stream_view($vid);
        foreach ($data as $key => $text) {
            $params = self::get_quick_select_params($key);
            if ($params === null) {
                continue;
            }
            $result[] = [
                    'value' => $key,
                    'text' => $text,
                    'params' => json_encode($params),
                    'selected' => $quickselect == $key,
            ];
        }
        return $result;
    }

    /**
     * Build array list for sort by filter.
     *
     * @param int $id
     * @param int|null $sortby
     * @return array
     *     [
     *          [
     *              'value'    => 1,
     *              'text'     => 'Date ascending',
     *              'url'      => '{"fsort":1,"osort":1}'
     *              'selected' => true,
     *          ],
     *     ],
     * @throws coding_exception
     */
    public static function build_sort_by_filter(int $id, ?int $sortby): array {
        $result = [];
        $defaultparams = [
                'fsort' => defaults::OPENSTUDIO_SORT_FLAG_DATE,
                'osort' => defaults::OPENSTUDIO_SORT_DESC,
        ];
        $result[] = [
                'value' => null,
                'text' => get_string('filtersortbyselectoption', 'mod_openstudio'),
                'params' => json_encode($defaultparams),
                'selected' => null === $sortby,
        ];

        $data = self::get_sort_by_dropdown($id);
        foreach ($data as $key => $text) {
            $params = static::get_sort_by_params($key);
            if ($params === null) {
                continue;
            }
            $result[] = [
                    'value' => $key,
                    'text' => $text,
                    'params' => json_encode($params),
                    'selected' => $key == $sortby,
            ];
        }
        return $result;
    }
}
