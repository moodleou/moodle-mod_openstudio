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
 * Unit tests for mod/openstudio/classes/local/renderer_utils.php.
 *
 * @package    mod_openstudio
 * @category   phpunit
 * @copyright  2023 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

use mod_openstudio\local\api\content;
use mod_openstudio\local\renderer_utils;

/**
 * Unit tests for mod/openstudio/classes/local/renderer_utils.php.
 *
 * @package    mod_openstudio
 * @category   phpunit
 * @copyright  2023 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        renderer_utils
 */
class renderer_utils_test extends \advanced_testcase {

    /**
     * Data provider for test_get_content_visibility().
     *
     * @return array[]
     */
    public function get_content_visibility_provider(): array {
        return [
            [-1, content::VISIBILITY_GROUP],
            [-20, content::VISIBILITY_GROUP],
            [content::VISIBILITY_MODULE, content::VISIBILITY_MODULE],
            [content::VISIBILITY_PRIVATE_PINBOARD, content::VISIBILITY_PRIVATE_PINBOARD],
        ];
    }

    /**
     * Test get content visibility from a content instance.
     *
     * @dataProvider get_content_visibility_provider
     * @param int $visibility
     * @param int $expectedvisibility
     */
    public function test_get_content_visibility(int $visibility, int $expectedvisibility): void {
        $contentdata = new \stdClass();
        $contentdata->visibility = $visibility;
        $this->assertEquals($expectedvisibility, renderer_utils::get_content_visibility($contentdata));
    }

    /**
     * Data provider for test_get_content_visibility_name().
     *
     * @return array[]
     */
    public function get_content_visibility_name_provider(): array {
        return [
            [
                content::VISIBILITY_MODULE,
                get_string('contentitemsharewithmymodule', 'openstudio'),
            ],
            [
                content::VISIBILITY_WORKSPACE,
                get_string('contentitemsharewithonlyme', 'openstudio'),
            ],
            [
                content::VISIBILITY_PRIVATE,
                get_string('contentitemsharewithonlyme', 'openstudio'),
            ],
            [
                content::VISIBILITY_PRIVATE_PINBOARD,
                get_string('contentitemsharewithonlyme', 'openstudio'),
            ],
            [
                content::VISIBILITY_TUTOR,
                get_string('contentitemsharewithmytutor', 'openstudio'),
            ],
        ];
    }

    /**
     * Test get content visibility when using different visibility types.
     *
     * @dataProvider get_content_visibility_name_provider
     * @param int $visibility
     * @param string $expectedname
     */
    public function test_get_content_visibility_name(int $visibility, string $expectedname): void {
        $contentdata = new \stdClass();
        $contentdata->visibility = $visibility;
        $this->assertEquals($expectedname, renderer_utils::get_content_visibility_name($contentdata));
    }

    /**
     * Test get content visibility name when using group visibility.
     *
     * @depends test_get_real_visibility
     * @depends test_get_content_visibility_name
     */
    public function test_get_content_visibility_name_with_group(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Group 1']);
        // Test.
        $contentdata = new \stdClass();
        $contentdata->visibility = -$group->id;
        $expectname = get_string('contentitemsharewithgroup', 'openstudio', $group->name);
        $this->assertEquals($expectname, renderer_utils::get_content_visibility_name($contentdata));
    }
}
