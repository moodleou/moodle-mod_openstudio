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
 * Unit tests for mod/openstudio/classes/local/api/filter.php.
 *
 * @package    mod_openstudio
 * @category   phpunit
 * @copyright  2022 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

use coding_exception;
use mod_openstudio\local\api\content;
use mod_openstudio\local\api\filter;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for mod/openstudio/classes/local/api/filter.php.
 *
 * @package    mod_openstudio
 * @category   phpunit
 * @copyright  2022 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_test extends \advanced_testcase {

    public function test_get_sort_by_params_failed() {
        $this->assertNull(filter::get_sort_by_params(-1));
        $this->assertNull(filter::get_sort_by_params(null));
    }

    /**
     * @return array[]
     */
    public function get_sort_by_params_provider(): array {
        return [
                [filter::SORT_BY_DATE_ASCENDING],
                [filter::SORT_BY_DATE_DESCENDING],
                [filter::SORT_BY_USERNAME_ASCENDING],
                [filter::SORT_BY_USERNAME_DESCENDING],
                [filter::SORT_BY_ACTIVITYTITLE_ASCENDING],
                [filter::SORT_BY_ACTIVITYTITLE_DESCENDING],
        ];
    }

    /**
     * @depends      test_get_sort_by_params_failed
     * @dataProvider get_sort_by_params_provider
     * @param int $key
     * @return void
     */
    public function test_get_sort_by_params(int $key) {
        $params = filter::get_sort_by_params($key);
        $this->assertArrayHasKey('fsort', $params);
        $this->assertArrayHasKey('osort', $params);
    }

    /**
     * @return array[]
     */
    public function get_sort_by_dropdown_provider(): array {
        return [
                [
                        filter::PAGE_VIEW,
                        [
                                filter::SORT_BY_DATE_ASCENDING,
                                filter::SORT_BY_DATE_DESCENDING,
                                filter::SORT_BY_ACTIVITYTITLE_ASCENDING,
                                filter::SORT_BY_ACTIVITYTITLE_DESCENDING,
                        ],
                ],
                [
                        filter::PAGE_PEOPLE,
                        [
                                filter::SORT_BY_DATE_ASCENDING,
                                filter::SORT_BY_DATE_DESCENDING,
                                filter::SORT_BY_USERNAME_ASCENDING,
                                filter::SORT_BY_USERNAME_DESCENDING,
                        ],
                ],
        ];
    }

    /**
     * @depends      test_get_sort_by_params
     * @dataProvider get_sort_by_dropdown_provider
     * @param int $page
     * @param int[] $filters
     * @return void
     * @throws coding_exception
     */
    public function test_get_sort_by_dropdown(int $page, array $filters) {
        $list = filter::get_sort_by_dropdown($page);
        $this->assertSame($filters, array_keys($list));
    }

    /**
     * @depends test_get_sort_by_dropdown
     * @return void
     * @throws moodle_exception
     */
    public function test_build_sort_by_filter() {
        $list = filter::build_sort_by_filter(filter::PAGE_VIEW, null);
        $this->assertNotEmpty($list);
        foreach ($list as $item) {
            $this->assertArrayHasKey('text', $item);
            $this->assertArrayHasKey('params', $item);
            $this->assertArrayHasKey('selected', $item);
        }
    }

    public function test_get_quick_select_params_failed() {
        $this->assertNull(filter::get_quick_select_params(-1));
        $this->assertNull(filter::get_quick_select_params(null));
    }

    /**
     * @return array[]
     */
    public function get_quick_select_params_provider(): array {
        return [
                [filter::FILTER_COMBINATION_MY_POSTS_WITH_MY_COMMENTS],
                [filter::FILTER_COMBINATION_MY_POSTS_WITH_USERS_COMMENTS],
                [filter::FILTER_COMBINATION_USERS_POSTS_WITH_MY_COMMENTS],
                [filter::FILTER_COMBINATION_USERS_POSTS_WITH_FEEDBACK_REQUESTS],
        ];
    }

    /**
     * @depends      test_get_quick_select_params_failed
     * @dataProvider get_quick_select_params_provider
     * @param int $key
     * @return void
     */
    public function test_get_quick_select_params(int $key) {
        $params = filter::get_quick_select_params($key);
        $this->assertArrayHasKey('ftypearray', $params);
        $this->assertIsArray($params['ftypearray']);
        $this->assertArrayHasKey('fflagarray', $params);
        $this->assertIsArray($params['fflagarray']);
        $this->assertArrayHasKey('fstatus', $params);
        $this->assertArrayHasKey('fscope', $params);
    }

    /**
     * @return array[]
     */
    public function get_quick_select_params_stream_view_provider(): array {
        return [
                [
                        content::VISIBILITY_PRIVATE,
                        [
                                filter::FILTER_COMBINATION_MY_POSTS_WITH_MY_COMMENTS,
                                filter::FILTER_COMBINATION_MY_POSTS_WITH_USERS_COMMENTS,

                        ],
                ],
                [
                        content::VISIBILITY_PRIVATE_PINBOARD,
                        [
                                filter::FILTER_COMBINATION_MY_POSTS_WITH_MY_COMMENTS,
                                filter::FILTER_COMBINATION_MY_POSTS_WITH_USERS_COMMENTS,

                        ],
                ],
                [
                        content::VISIBILITY_GROUP,
                        [
                                filter::FILTER_COMBINATION_USERS_POSTS_WITH_MY_COMMENTS,
                                filter::FILTER_COMBINATION_USERS_POSTS_WITH_FEEDBACK_REQUESTS,

                        ],
                ],
                [
                        content::VISIBILITY_MODULE,
                        [
                                filter::FILTER_COMBINATION_USERS_POSTS_WITH_MY_COMMENTS,
                                filter::FILTER_COMBINATION_USERS_POSTS_WITH_FEEDBACK_REQUESTS,
                        ],
                ],
        ];
    }

    /**
     * @depends      test_get_quick_select_params
     * @dataProvider get_quick_select_params_stream_view_provider
     * @param int $vid
     * @param array<int, string> $filters
     * @return void
     * @throws coding_exception
     */
    public function test_get_quick_select_params_stream_view(int $vid, array $filters) {
        $response = filter::get_quick_select_params_stream_view($vid);
        $this->assertSame($filters, array_keys($response));
    }

    /**
     * @depends test_get_quick_select_params_stream_view
     * @return void
     * @throws moodle_exception
     */
    public function test_build_quick_select_filter() {
        $list = filter::build_quick_select_filter(content::VISIBILITY_PRIVATE, null, '/mod/openstudio/view.php', 0);
        $this->assertNotEmpty($list);
        foreach ($list as $item) {
            $this->assertArrayHasKey('text', $item);
            $this->assertArrayHasKey('params', $item);
            $this->assertArrayHasKey('selected', $item);
            // Params will be converted into JSON string.
            $this->assertIsString($item['params']);
        }
    }
}
