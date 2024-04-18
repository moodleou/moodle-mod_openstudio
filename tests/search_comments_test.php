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
 * Test case for Open Studio search function.
 *
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio;

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\search;
use mod_openstudio\search\comments;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/openstudio/tests/fixtures/testable_openstudio_search.php');

/**
 * Test case for Open Studio search function.
 *
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_comments_test extends \advanced_testcase {

    protected $user;
    protected $course;
    protected $generator;
    protected $studiolevels;
    protected $studentroleid;
    protected $cm;
    protected $emptycontent;
    protected $urlcontent;
    protected $urlcontentprivate;
    protected $foldercontent;
    protected $emptycontentcomment;
    protected $urlcontentcomment;
    protected $urlcontentprivatecomment;
    protected $foldercontentcomment;

    /**
     * Sets up our fixtures.
     */
    protected function setUp(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->studentroleid = 5;

        // Enable global search for this test.
        \testable_openstudio_search::instance();

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create Users.
        $this->user = $this->getDataGenerator()->create_user(
            ['email' => 'student1@ouunittest.com', 'username' => 'student1']);

        // Enroll our students and teacher (users) in the course.
        $this->getDataGenerator()->enrol_user(
            $this->user->id, $this->course->id, $this->studentroleid, 'manual');

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Create generic studios.
        $this->studiolevels = $this->generator->create_instance(['course' => $this->course->id, 'idnumber' => 'OS1']);
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);
        $this->cm = get_coursemodule_from_id('openstudio', $this->studiolevels->cmid);

        // Create content entry.
        $emptycontententry = (object)[
            'name' => 'Empty content entry',
            'visibility' => content::VISIBILITY_MODULE,
            'contenttype' => content::TYPE_NONE,
            'ownership' => 0,
            'sid' => 0
        ];

        $urlcontententry = $this->generator->generate_single_data_array($this->user);
        $urlcontententry['contenttype'] = content::TYPE_URL;

        $urlcontentprivateentry = $this->generator->generate_single_data_array($this->user);
        $urlcontentprivateentry['contenttype'] = content::TYPE_URL;
        $urlcontentprivateentry['visibility'] = content::VISIBILITY_PRIVATE;

        $foldercontententry = $this->generator->generate_single_data_array($this->user);
        $foldercontententry['contenttype'] = content::TYPE_FOLDER;

        // Create real content based on entry.
        $this->emptycontent = content::create_in_pinboard($this->studiolevels->id, $this->user->id,
            $emptycontententry, $this->cm);
        $this->urlcontent = content::create_in_pinboard($this->studiolevels->id, $this->user->id,
            $urlcontententry, $this->cm);
        $this->urlcontentprivate = content::create_in_pinboard($this->studiolevels->id, $this->user->id,
            $urlcontentprivateentry, $this->cm);
        $this->foldercontent = content::create_in_pinboard($this->studiolevels->id, $this->user->id,
            $foldercontententry, $this->cm);

        // Create comments for existed contents.
        $this->emptycontentcomment = \mod_openstudio\local\api\comments::create($this->emptycontent, $this->user->id,
            'Comment belong to empty content');
        $this->urlcontentcomment = \mod_openstudio\local\api\comments::create($this->urlcontent, $this->user->id,
            'Comment belong to URL content');
        $this->urlcontentprivatecomment = \mod_openstudio\local\api\comments::create($this->urlcontentprivate, $this->user->id,
            'Comment belong to URL content private');
        $this->foldercontentcomment = \mod_openstudio\local\api\comments::create($this->foldercontent, $this->user->id,
            'Comment belong to folder content');

        // Update time modified to guarantee the ordering of the query results.
        $sql = "UPDATE {openstudio_comments}
                   SET timemodified =
                  CASE
                       WHEN id = ? THEN 1
                       WHEN id = ? THEN 2
                       WHEN id = ? THEN 3
                       WHEN id = ? THEN 4
                  END";

        $DB->execute($sql, [
                $this->emptycontentcomment,
                $this->urlcontentcomment,
                $this->urlcontentprivatecomment,
                $this->foldercontentcomment]
        );
    }

    public function tearDown(): void {
        \testable_openstudio_search::$fakeresult = null;
        parent::tearDown();
    }

    /**
     * Test function not get any record comment belong to empty content.
     */
    public function test_not_return_comment_from_empty_content() {
        $comments = new comments();

        $results = self::recordset_to_array($comments->get_recordset_by_timestamp());

        // Check that function return 3 comments, all not include comment belong to empty content.
        $this->assertCount(3, $results);
        $this->assertEquals('Comment belong to URL content', $results[0]->commenttext);
        $this->assertEquals('Comment belong to URL content private', $results[1]->commenttext);
        $this->assertEquals('Comment belong to folder content', $results[2]->commenttext);
    }

    /**
     * Test check get document.
     */
    public function test_check_get_document_function() {
        $comments = new comments();

        $result = $comments->get_document((object)[
            'id' => $this->urlcontentcomment,
            'name' => 'Sample name 1',
            'openstudioid' => $this->cm->instance,
            'commenttext' => 'Comment belong to URL content',
            'commentuser' => $this->user->id,
            'timemodified' => 0,
            'course' => $this->course->id
        ]);

        $this->assertEquals('mod_openstudio-comments', $result->get('areaid'));
        $this->assertEquals('mod_openstudio-comments-' . $this->urlcontentcomment, $result->get('id'));
        $this->assertEquals($this->urlcontentcomment, $result->get('itemid'));
        $this->assertEquals('Sample name 1', $result->get('title'));
        $this->assertEquals('Comment belong to URL content', $result->get('content'));
        $this->assertEquals($this->user->id, $result->get('userid'));
        $this->assertEquals(0, $result->get('owneruserid'));
        $this->assertEquals(0, $result->get('modified'));
    }

    /**
     * Test check get document.
     */
    public function test_check_get_permission() {
        $comments = new comments();

        // Check as admin.
        $this->setAdminUser();

        // Deleted if trying to get comment belong to empty content since it will not indexed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $comments->check_access($this->emptycontentcomment));
        // Deleted if trying to get comment that not existed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $comments->check_access(0));
        // Check return granted when get url comment and folder comment.
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $comments->check_access($this->urlcontentcomment));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $comments->check_access($this->urlcontentprivatecomment));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $comments->check_access($this->foldercontentcomment));

        // Check as owner user.
        $this->setUser($this->user);

        // Deleted if trying to get comment belong to empty content since it will not indexed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $comments->check_access($this->emptycontentcomment));
        // Deleted if trying to get comment that not existed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $comments->check_access(0));
        // Check return granted when get url comment and folder comment.
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $comments->check_access($this->urlcontentcomment));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $comments->check_access($this->urlcontentprivatecomment));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $comments->check_access($this->foldercontentcomment));

        // Check as non-owner user.
        // Create Users and enroll to course.
        $anotheruser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user(
            $anotheruser->id, $this->course->id, $this->studentroleid, 'manual');
        $this->setUser($anotheruser);

        // Deleted if trying to get comment belong to empty content since it will not indexed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $comments->check_access($this->emptycontentcomment));
        // Deleted if trying to get comment that not existed.
        $this->assertEquals(\core_search\manager::ACCESS_DELETED, $comments->check_access(0));
        // Check return granted when get url comment, folder comment and deny when get private comment.
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $comments->check_access($this->urlcontentcomment));
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $comments->check_access($this->urlcontentprivatecomment));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $comments->check_access($this->foldercontentcomment));
    }

    /**
     * Test check file index.
     * @throws \coding_exception
     */
    public function test_check_file_index() {
        $comments = new comments();

        $commenttext = '<p>Test image link: <img src="@@PLUGINFILE@@/test.img"  alt="image"/></p>';

        $doc = $comments->get_document((object)[
            'id' => $this->urlcontentcomment,
            'name' => 'Sample name 1',
            'openstudioid' => $this->cm->instance,
            'commenttext' => $commenttext,
            'commentuser' => $this->user->id,
            'timemodified' => 0,
            'course' => $this->course->id
        ]);

        $context = \context_module::instance($this->cm->id);
        // Check content attachment.
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'mod_openstudio',
            'filearea' => comments::FILEAREA['COMMENT'],
            'itemid' => $this->urlcontentcomment,
            'filepath' => '/',
            'filename' => 'audio.mp3'
        ];
        $file1 = $fs->create_file_from_string($filerecord, 'File 1 content');

        $contentsareaid = \core_search\manager::generate_areaid('mod_openstudio', 'comments');
        $searcharea = \core_search\manager::get_search_area($contentsareaid);

        $this->assertCount(0, $doc->get_files());
        $searcharea->attach_files($doc);
        $files = $doc->get_files();
        $this->assertCount(1, $files);

        $filerecord2 = [
                'contextid' => $context->id,
                'component' => 'mod_openstudio',
                'filearea' => comments::FILEAREA['COMMENTTEXT'],
                'itemid' => $this->urlcontentcomment,
                'filepath' => '/',
                'filename' => 'test.png'
        ];
        $file2 = $fs->create_file_from_string($filerecord2, 'File 2 comment text');
        $searcharea->attach_files($doc);
        $files = $doc->get_files();
        $this->assertCount(2, $files);

        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            switch ($file->get_filearea()) {
                case comments::FILEAREA['COMMENT']:
                    $this->assertEquals('audio.mp3', $file->get_filename());
                    break;

                case comments::FILEAREA['COMMENTTEXT']:
                    $this->assertEquals('test.png', $file->get_filename());
                    break;

                default:
                    break;
            }
        }

        // Create a second instance to check the context restriction.
        $openstudiodata = ['course' => $this->course->id, 'enablefolders' => 1, 'idnumber' => 'OSother'];
        $other = $this->generator->create_instance($openstudiodata);
        $othercontext = \context_module::instance($other->cmid);

        // Test get_document_recordset with and without context.
        $results = self::recordset_to_array($comments->get_document_recordset(0));
        $this->assertCount(3, $results);
        $results = self::recordset_to_array($comments->get_document_recordset(0, $context));
        $this->assertCount(3, $results);
        $results = self::recordset_to_array($comments->get_document_recordset(0, $othercontext));
        $this->assertCount(0, $results);
    }

    /**
     * Test check search comments with global search.
     */
    public function test_check_global_search() {
        // Create new comment.
        $comments = new comments();
        $commentsdata = $comments->get_document((object)[
                'id' => $this->urlcontentcomment,
                'name' => 'Sample name 1',
                'openstudioid' => $this->cm->instance,
                'commenttext' => 'Comment belong to URL content',
                'commentuser' => $this->user->id,
                'timemodified' => 0,
                'course' => $this->course->id
        ]);
        // Add comment to fake data.
        $fakedata = new \stdClass();
        $fakedata->totalcount = 1;
        $fakedata->actualpage = 1;

        $commentsdata->set_extra('coursefullname', $this->course->fullname);
        $commentsdata->set_extra('userfullname', fullname($this->user));
        $commentsdata->set_doc_url(new \moodle_url('/mod/openstudio/content.php', ['sid' => $this->urlcontent]));
        $commentsdata->set_context_url(new \moodle_url('/mod/openstudio/content.php', ['sid' => $this->urlcontent]));

        $fakedata->results[] = $commentsdata;

        \testable_openstudio_search::$fakeresult = $fakedata;

        // Search comment.
        $data = search::query($this->cm, 'This is a comment');

        $this->assertCount(1, $data->result);
        $comment = array_pop($data->result);
        $this->assertEquals($this->urlcontent, $comment->intref1);
        $this->assertEquals('openstudio-comment-' . $this->urlcontentcomment, $comment->anchor);
    }

    /**
     * Converts recordset to array, indexed numberically (0, 1, 2).
     *
     * @param \moodle_recordset $rs Record set to convert
     * @return \stdClass[] Array of converted records
     */
    protected static function recordset_to_array(\moodle_recordset $rs) {
        $result = [];
        foreach ($rs as $rec) {
            $result[] = $rec;
        }
        $rs->close();
        return $result;
    }
}
