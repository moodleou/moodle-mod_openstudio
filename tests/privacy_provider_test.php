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
 * Data provider tests for openstudio module.
 *
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\tests;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\tests\provider_testcase;
use \mod_openstudio\local\api\content;
use \mod_openstudio\local\api\flags;
use \mod_openstudio\local\api\tracking;
use \mod_openstudio\local\api\honesty;
use \mod_openstudio\local\api\folder;
use \mod_openstudio\privacy\provider;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\transform;
use \mod_openstudio\local\api\subscription;
use \mod_openstudio\local\api\tags;
use \mod_openstudio\local\api\comments;

/**
 * Data provider testcase class.
 *
 * @package mod_bookingsystem
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_provider_test extends provider_testcase {

    protected $users;
    protected $course;
    protected $course2;
    protected $posts;
    protected $comments;
    protected $generator;
    protected $flags;
    protected $studio;
    protected $studio2;
    protected $studio3;
    protected $tracking;
    protected $tracking2;
    protected $notification;
    protected $notification2;
    protected $contextstudio;
    protected $contextstudio2;
    protected $contextstudio3;
    protected $commentspost1;
    protected $commentspost2;
    protected $flags2;
    protected $tags;
    protected $studiolevels;
    protected $folders;
    protected $contentsinfolder;
    protected $foldercontents;
    protected $group;
    protected $contextmodule;
    protected $subscription;

    /**
     * Set up for each test.
     *
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);
        $teacherroleid = 3;
        $studentroleid = 5;

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();
        $this->course2 = $this->getDataGenerator()->create_course();

        // Create user.
        $this->users = new \stdClass();
        $this->users->students = new \stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                ['email' => 'student1@ouunittest.com', 'username' => 'student1']);
        $this->users->students->two = $this->getDataGenerator()->create_user(
                ['email' => 'student2@ouunittest.com', 'username' => 'student2']);
        $this->users->students->three = $this->getDataGenerator()->create_user(
                ['email' => 'student3@ouunittest.com', 'username' => 'student3']);

        $this->users->teachers = new \stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                ['email' => 'teacher1@ouunittest.com', 'username' => 'teacher1']);
        $this->users->teachers->two = $this->getDataGenerator()->create_user(
                ['email' => 'teacher2@ouunittest.com', 'username' => 'teacher2']);

        $this->group = $this->getDataGenerator()->create_group(['courseid' => $this->course->id]);

        // Studio generator.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Enroll our students in the course.
        $this->getDataGenerator()->enrol_user($this->users->students->one->id, $this->course->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->students->one->id, $this->course2->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->students->two->id, $this->course->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->students->two->id, $this->course2->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->students->three->id, $this->course->id,
                $studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->one->id, $this->course->id, $teacherroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->two->id, $this->course->id, $teacherroleid, 'manual');

        // Create generic studios.
        $this->studio = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS1'
        ]);
        $this->studio2 = $this->generator->create_instance([
                'course' => $this->course2->id,
                'idnumber' => 'OS2',
                'enablefolders' => 1
        ]);
        $this->studio3 = $this->generator->create_instance([
                'course' => $this->course->id,
                'idnumber' => 'OS3',
                'enablefolders' => 1
        ]);

        // Get context info.
        $this->contextstudio = \context_module::instance($this->studio->cmid);
        $this->contextstudio2 = \context_module::instance($this->studio2->cmid);
        $this->contextstudio3 = \context_module::instance($this->studio3->cmid);
        $this->posts = new \stdClass();

        // Create folders in OS1 of student one.
        $folderdata = [
                'openstudio' => 'OS1',
                'name' => random_string(),
                'levelid' => 0,
                'levelcontainer' => 0,
                'userid' => $this->users->students->one->id
        ];
        $this->folders = $this->generator->create_folders($folderdata);

        // Add post to the folder.
        $this->contentsinfolder = [];
        $this->foldercontents = [];
        for ($i = 0; $i < 2; $i++) {
            $contentdata = [
                    'openstudio' => 'OS1',
                    'visibility' => content::VISIBILITY_INFOLDERONLY,
                    'userid' => $folderdata['userid'],
                    'name' => 'folder_content_' . $i,
                    'contenttype' => content::TYPE_TEXT,
                    'description' => random_string(),
                    'content' => random_string(),
            ];
            $this->contentsinfolder[$i] = $this->generator->create_contents($contentdata);
            $content = content::get($this->contentsinfolder[$i]);
            $this->foldercontents[$i] = [
                    'openstudio' => 'OS1',
                    'folder' => $folderdata['name'],
                    'folderid' => $this->folders,
                    'content' => $content->name,
                    'contentorder' => $i,
                    'userid' => $this->users->students->one->id
            ];
            $this->generator->create_folder_contents($this->foldercontents[$i]);
        }

        // Create contents and activities in course 1.

        // Post of student one of Studio 1 in course 1.
        $this->posts->one = $this->generator->create_contents([
                'userid' => $this->users->students->one->id,
                'openstudio' => 'OS1',
                'name' => random_string(),
                'description' => random_string(),
                'content' => random_string(),
                'contenttype' => content::TYPE_TEXT,
                'visibility' => content::VISIBILITY_MODULE,
                'file' => 'mod/openstudio/tests/importfiles/test1.jpg'
        ]);
        // Add tags for post one.
        tags::set($this->posts->one, ['aaa', 'bbb', 'ccc']);
        $this->commentspost1 = new \stdClass();
        // Comment post 1 by user 1.
        $this->commentspost1->one = $this->generator->create_comment([
                'contentid' => $this->posts->one,
                'userid' => $this->users->students->one->id,
                'comment' => random_string(),
                'filepath' => 'mod/studio/tests/importfiles/test.mp3',
                'filecontext' => $this->contextstudio
        ]);

        // Comment post one by user 2.
        $this->commentspost1->two = $this->generator->create_comment([
                'contentid' => $this->posts->one,
                'userid' => $this->users->students->two->id,
                'comment' => random_string(),
        ]);
        // Store flags of student one in post1.
        $this->flags = new \stdClass();
        $this->flags->one = $this->generator->create_flag([
                'contentid' => $this->posts->one,
                'userid' => $this->users->students->one->id,
                'flagid' => flags::FOLLOW_CONTENT
        ]);
        $this->flags->two = $this->generator->create_flag([
                'commentid' => $this->commentspost1->one,
                'userid' => $this->users->students->one->id,
                'flagid' => flags::FOLLOW_CONTENT
        ]);
        // Create notification for student one.
        $this->notification = new \stdClass();
        $this->notification->one = $this->generator->create_notification([
                'userid' => $this->users->students->one->id,
                'contentid' => $this->posts->one,
                'commentid' => $this->commentspost1->one,
                'userfrom' => $this->users->students->two->id
        ]);

        // Store tracking of user 1 in post1.
        $this->tracking = new \stdClass();
        $this->tracking->one = tracking::log_action(
                $this->posts->one, tracking::READ_CONTENT, $this->users->students->one->id);
        $this->tracking->two = tracking::log_action(
                $this->posts->one, tracking::DELETE_CONTENT, $this->users->students->one->id);
        // Student 1 checks honesty openstudio 1.
        honesty::set($this->studio->cmid, $this->users->students->one->id, true);
        // Student 1 has a subscription.
        $this->subscription = new \stdClass();
        $this->subscription->userone = subscription::create(subscription::MODULE, $this->users->students->one->id, $this->studio->id,
                subscription::FORMAT_HTML, subscription::FREQUENCY_HOURLY);

        // Post of student 2 in studio 2, course 2.
        $this->posts->two = $this->generator->create_contents([
                'userid' => $this->users->students->two->id,
                'openstudio' => 'OS2',
                'name' => random_string(),
                'description' => random_string(),
                'content' => random_string(),
                'contenttype' => content::TYPE_TEXT,
                'visibility' => (0 - $this->group->id),
                'file' => 'mod/openstudio/tests/importfiles/test2.jpg'
        ]);

        $this->commentspost2 = new \stdClass();
        $this->commentspost2->one = $this->generator->create_comment([
                'contentid' => $this->posts->two,
                'userid' => $this->users->students->two->id,
                'comment' => random_string(),
                'filepath' => 'mod/studio/tests/importfiles/test.mp3',
                'filecontext' => $this->contextstudio2
        ]);
        // Comment post2 by user one.
        $this->commentspost2->two = $this->generator->create_comment([
                'contentid' => $this->posts->two,
                'userid' => $this->users->students->one->id,
                'comment' => random_string()
        ]);

        // Activities of user2 in post1.
        // Store flags of student 2 in post1.
        $this->flags2 = new \stdClass();
        $this->flags2->one = $this->generator->create_flag([
                'contentid' => $this->posts->one,
                'userid' => $this->users->students->two->id,
                'flagid' => flags::FOLLOW_CONTENT
        ]);

        // Create notification for student 1 in course 1.
        $this->notification2 = new \stdClass();
        $this->notification2->one = $this->generator->create_notification([
                'userid' => $this->users->students->two->id,
                'contentid' => $this->posts->one,
                'commentid' => $this->commentspost2->one,
                'userfrom' => $this->users->students->one->id
        ]);

        // Store tracking of user 1 in post 1.
        $this->tracking2 = new \stdClass();
        $this->tracking2->one = tracking::log_action(
                $this->posts->one, tracking::READ_CONTENT, $this->users->students->two->id);

        // Post of student 1 in Studio 3 in course 1.
        $this->posts->three = $this->generator->create_contents([
                'userid' => $this->users->students->one->id,
                'openstudio' => 'OS3',
                'name' => random_string(),
                'description' => random_string(),
                'content' => random_string(),
                'contenttype' => content::TYPE_TEXT,
                'visibility' => content::VISIBILITY_MODULE,
                'file' => 'mod/openstudio/tests/importfiles/test1.jpg'
        ]);
        // User2 reply comment in post of user1.
        comments::create(
                $this->posts->two, $this->users->students->two->id, random_string(),
                null, null, null, $this->commentspost2->two);
    }

    /**
     *  Test get context list for user id.
     */
    public function test_get_contexts_for_userid() {
        // Get contexts for the first user.
        $contextidsstudent1 = provider::get_contexts_for_userid($this->users->students->one->id)->get_contextids();
        $contextidsstudent2 = provider::get_contexts_for_userid($this->users->students->two->id)->get_contextids();
        $contextidsstudent3 = provider::get_contexts_for_userid($this->users->students->three->id)->get_contextids();

        // Student one joins OS1, OS2, OS3.
        $this->assertEquals([
                $this->contextstudio->id,
                $this->contextstudio2->id,
                $this->contextstudio3->id
        ], $contextidsstudent1);

        // Student two joins OS1, OS2.
        $this->assertEquals([
                $this->contextstudio->id,
                $this->contextstudio2->id
        ], $contextidsstudent2);

        // Student three doesn't join any OS.
        $this->assertEmpty($contextidsstudent3);
    }

    /**
     * Test export user data.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_export_user_data() {
        global $DB;
        // Export all contexts for student one.
        $contextids = provider::get_contexts_for_userid($this->users->students->two->id)->get_contextids();
        $context = \context::instance_by_id($this->contextstudio->id);
        $context2 = \context::instance_by_id($this->contextstudio2->id);
        $fs = get_file_storage();

        $appctx = new approved_contextlist($this->users->students->one, 'mod_openstudio', $contextids);
        provider::export_user_data($appctx);
        $contextdata = writer::with_context($this->contextstudio);

        $getrecordscontentsuser1 = array_values($DB->get_records('openstudio_contents',
                ['userid' => $this->users->students->one->id]));

        // Contents one of student 1.
        $content1 = $contextdata->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->one)]);

        $this->assertEquals((object) [
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'name' => format_string($getrecordscontentsuser1[1]->name),
                'contenttype' => get_string('privacy:contenttype:' . $getrecordscontentsuser1[1]->contenttype, 'mod_openstudio'),
                'content' => format_text($getrecordscontentsuser1[1]->content, $getrecordscontentsuser1[1]->textformat, $context),
                'description' => format_text($getrecordscontentsuser1[1]->description,
                        $getrecordscontentsuser1[1]->textformat, $context),
                'visibility' => get_string('privacy:visibility:' . $getrecordscontentsuser1[1]->visibility, 'mod_openstudio'),
                'deletedtime' => '',
                'deletedby' => '',
                'locktype' => get_string('privacy:lock:' . $getrecordscontentsuser1[1]->locktype, 'mod_openstudio'),
                'lockedtime' => '',
                'lockedby' => '',
                'timeflagged' => transform::datetime($getrecordscontentsuser1[1]->timeflagged),
                'lockprocessed' => transform::datetime($getrecordscontentsuser1[1]->lockprocessed),
                'retainimagemetadata' => transform::yesno($getrecordscontentsuser1[1]->retainimagemetadata)
        ], $content1);
        // File system.
        $this->assertNotEmpty($fs->get_area_files($this->contextstudio->id,
                'mod_openstudio', 'content', false));

        // Flags in content1 of student one.
        $flagsuser1 = (array) $contextdata->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->one),
                get_string('privacy:subcontext:flags', 'mod_openstudio')]);

        $getflagsuser1 = array_values($DB->get_records('openstudio_flags',
                ['userid' => $this->users->students->one->id, 'contentid' => $getrecordscontentsuser1[1]->id]));

        $this->assertEquals([
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'contentname' => format_string($content1->name),
                'person' => '',
                'flagname' => get_string('privacy:flag:' . $getflagsuser1[0]->flagid, 'mod_openstudio'),
                'timemodified' => transform::datetime($getflagsuser1[0]->timemodified)
        ], $flagsuser1[$getflagsuser1[0]->id]);

        // Get comments in post 1 of student 1.
        $commentsuser1 = (array) $contextdata->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->one),
                get_string('privacy:subcontext:comments', 'mod_openstudio')]);

        $getcommentuser1 = array_values($DB->get_records('openstudio_comments',
                ['userid' => $this->users->students->one->id, 'contentid' => $getrecordscontentsuser1[1]->id]));

        $this->assertEquals([
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'title' => '',
                'namecontent' => format_string($content1->name),
                'commenttext' => format_string($getcommentuser1[0]->commenttext, true),
                'timemodified' => transform::datetime($getcommentuser1[0]->timemodified),
                'deletedby' => '',
                'deletedtime' => '',
                'inreplyto' => 0
        ], $commentsuser1[$getcommentuser1[0]->id]);

        // Tracking in content1 of student one.
        $trackinguser1 = (array) $contextdata->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->one),
                get_string('privacy:subcontext:tracking', 'mod_openstudio')]);

        $gettrackinguser1 = array_values($DB->get_records('openstudio_tracking',
                ['userid' => $this->users->students->one->id, 'contentid' => $getrecordscontentsuser1[1]->id]));

        $this->assertEquals([
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'contentname' => format_string($content1->name),
                'action' => get_string('privacy:tracking:' . $gettrackinguser1[0]->actionid, 'mod_openstudio'),
                'timemodified' => transform::datetime($gettrackinguser1[0]->timemodified),
        ], $trackinguser1[$gettrackinguser1[0]->id]);

        // Get subscriptions.
        $subscription = (array) $contextdata->get_data([get_string('privacy:subcontext:subscription', 'mod_openstudio')]);

        $getsubscriptionuser1 = array_values($DB->get_records('openstudio_subscriptions',
                ['userid' => $this->users->students->one->id]));

        $this->assertEquals([
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'nameopenstudio' => format_string($this->studio->name, true),
                'subscription' => get_string('privacy:subscription:' . $getsubscriptionuser1[0]->subscription, 'mod_openstudio'),
                'timeprocessed' => transform::datetime($getsubscriptionuser1[0]->timeprocessed),
                'timemodified' => transform::datetime($getsubscriptionuser1[0]->timemodified)
        ], $subscription);

        // Get honesty.
        $honesty = $contextdata->get_data([get_string('privacy:subcontext:honestycheck', 'mod_openstudio')]);
        $getsubscriptionuser1 = array_values($DB->get_records('openstudio_honesty_checks',
                ['userid' => $this->users->students->one->id]));

        $this->assertEquals((object) [
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'nameopenstudio' => format_string($this->studio->name, true),
                'honestychecked' => transform::yesno($getsubscriptionuser1[0]->userid == $this->users->students->one->id),
                'timemodified' => transform::datetime($getsubscriptionuser1[0]->timemodified)
        ], $honesty);

        // Get contents in folder.
        $contentinfolder = (array) $contextdata->get_data([get_string('privacy:subcontext:folders', 'mod_openstudio'),
                get_string('privacy:subcontext:folder', 'mod_openstudio', $this->folders),
                get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->contentsinfolder[0])]);

        $foldercontent = (array) folder::get_content($this->folders, $this->contentsinfolder[0]);

        $this->assertEquals([
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'name' => format_string($foldercontent['name']),
                'contenttype' => get_string('privacy:contenttype:' . $foldercontent['contenttype'], 'mod_openstudio'),
                'content' => format_text($foldercontent['content'], $foldercontent['textformat']),
                'description' => format_text($foldercontent['description'], $foldercontent['textformat']),
                'visibility' => get_string('privacy:visibility:' . $foldercontent['visibility'], 'mod_openstudio'),
                'deletedtime' => '',
                'deletedby' => '',
                'locktype' => get_string('privacy:lock:' . $foldercontent['locktype'], 'mod_openstudio'),
                'lockedtime' => '',
                'lockedby' => '',
                'timeflagged' => transform::datetime($foldercontent['timeflagged']),
                'lockprocessed' => transform::datetime($foldercontent['lockprocessed']),
                'retainimagemetadata' => transform::yesno($foldercontent['retainimagemetadata'])
        ], $contentinfolder);

        // Get comments of student one. Student one commented in post of Student two.
        $contextdata2 = writer::with_context($this->contextstudio2);

        $contentuser2 = array_values($DB->get_records('openstudio_contents',
                ['userid' => $this->users->students->two->id]));

        $commentuser1post2 = array_values($DB->get_records('openstudio_comments',
                ['userid' => $this->users->students->one->id, 'contentid' => $contentuser2[0]->id]));

        $othercontent = (array) $contextdata2->get_data([get_string('privacy:subcontext:othercontent', 'mod_openstudio'),
                get_string('privacy:subcontext:comments', 'mod_openstudio')]);

        $this->assertEquals([
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'title' => '',
                'namecontent' => format_string($contentuser2[0]->name),
                'commenttext' => format_string($commentuser1post2[0]->commenttext, true),
                'timemodified' => transform::datetime($commentuser1post2[0]->timemodified),
                'deletedby' => '',
                'deletedtime' => '',
                'inreplyto' => 0
        ], $othercontent[$commentuser1post2[0]->id]);

        // Export data for student two.
        writer::reset();
        $contextidsstudent2 = provider::get_contexts_for_userid($this->users->students->two->id)->get_contextids();

        $appctx2 = new approved_contextlist($this->users->students->two, 'mod_openstudio', $contextidsstudent2);
        provider::export_user_data($appctx2);

        $contextdata2 = writer::with_context($this->contextstudio2);
        $getrecordscontentsuser2 = array_values($DB->get_records('openstudio_contents',
                ['userid' => $this->users->students->two->id]));

        // Contents of student two.
        $content2 = $contextdata2->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->two)]);

        $this->assertEquals((object) [
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'name' => format_string($getrecordscontentsuser2[0]->name),
                'contenttype' => get_string('privacy:contenttype:' . $getrecordscontentsuser2[0]->contenttype, 'mod_openstudio'),
                'content' => format_text($getrecordscontentsuser2[0]->content, $getrecordscontentsuser2[0]->textformat, $context2),
                'description' => format_text($getrecordscontentsuser2[0]->description,
                        $getrecordscontentsuser2[0]->textformat, $context2),
                'visibility' => get_string('privacy:visibility:group', 'mod_openstudio', $this->group->name),
                'deletedtime' => '',
                'deletedby' => '',
                'locktype' => get_string('privacy:lock:' . $getrecordscontentsuser2[0]->locktype, 'mod_openstudio'),
                'lockedtime' => '',
                'lockedby' => '',
                'timeflagged' => transform::datetime($getrecordscontentsuser2[0]->timeflagged),
                'lockprocessed' => transform::datetime($getrecordscontentsuser2[0]->lockprocessed),
                'retainimagemetadata' => transform::yesno($getrecordscontentsuser2[0]->retainimagemetadata)
        ], $content2);

        $commentsuser2 = (array) $contextdata2->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->two),
                get_string('privacy:subcontext:comments', 'mod_openstudio')]);

        $getcommentuser2 = array_values($DB->get_records('openstudio_comments',
                ['userid' => $this->users->students->two->id, 'contentid' => $getrecordscontentsuser2[0]->id]));

        $this->assertEquals([
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'title' => '',
                'namecontent' => format_string($content2->name),
                'commenttext' => format_string($getcommentuser2[0]->commenttext, true),
                'timemodified' => transform::datetime($getcommentuser2[0]->timemodified),
                'deletedby' => '',
                'deletedtime' => '',
                'inreplyto' => $getcommentuser2[0]->inreplyto
        ], $commentsuser2[$getcommentuser2[0]->id]);

        // Flags of student two.
        $flagsuser2 = (array) $contextdata2->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->two),
                get_string('privacy:subcontext:flags', 'mod_openstudio')]);

        $getflagsuser2 = array_values($DB->get_records('openstudio_flags',
                ['userid' => $this->users->students->two->id, 'contentid' => $getrecordscontentsuser2[0]->id]));

        $this->assertEquals([
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'contentname' => format_string($content2->name),
                'person' => '',
                'flagname' => get_string('privacy:flag:' . $getflagsuser2[0]->flagid, 'mod_openstudio'),
                'timemodified' => transform::datetime($getflagsuser2[0]->timemodified)
        ], $flagsuser2[$getflagsuser2[0]->id]);

        // Tracking of student two.
        $trackinguser2 = (array) $contextdata2->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->two),
                get_string('privacy:subcontext:tracking', 'mod_openstudio')]);

        $gettrackinguser2 = array_values($DB->get_records('openstudio_tracking',
                ['userid' => $this->users->students->two->id, 'contentid' => $getrecordscontentsuser2[0]->id]));

        $this->assertEquals([
                'user' => get_string('privacy_you', 'mod_openstudio'),
                'contentname' => format_string($content2->name),
                'action' => get_string('privacy:tracking:' . $gettrackinguser2[0]->actionid, 'mod_openstudio'),
                'timemodified' => transform::datetime($gettrackinguser2[0]->timemodified),
        ], $trackinguser2[$gettrackinguser2[0]->id]);

        // Get subscriptions, Student two doesn't subscribe OS..
        $subscription = (array) $contextdata2->get_data([get_string('privacy:subcontext:subscription', 'mod_openstudio')]);
        $this->assertEmpty($subscription);

        // Export for student three.
        // Student three doesn't join any OS.
        writer::reset();
        $contextidsstudent3 = provider::get_contexts_for_userid($this->users->students->three->id)->get_contextids();
        $appctx3 = new approved_contextlist($this->users->students->three, 'mod_openstudio', $contextidsstudent3);

        $this->assertEmpty(provider::export_user_data($appctx3));

    }

    /**
     * Test delete data for all users in context.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_delete_data_for_all_users_in_context() {
        // Delete data in course 1.
        provider::delete_data_for_all_users_in_context($this->contextstudio);

        $appctx = new approved_contextlist($this->users->students->one, 'mod_openstudio', [
                        $this->contextstudio->id,
                        $this->contextstudio3->id]
        );
        provider::export_user_data($appctx);

        $contextdata = writer::with_context($this->contextstudio);
        $contextdata2 = writer::with_context($this->contextstudio3);

        $content1 = $contextdata->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->one)]);

        // Get data in course 3.
        $content2 = $contextdata2->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->three)]);

        $this->assertEmpty($content1);
        // Data in course three still exist.
        $this->assertNotEmpty($content2);
        writer::reset();

        // Data in course two still exist.
        $appctx3 = new approved_contextlist($this->users->students->two, 'mod_openstudio', [
                        $this->contextstudio2->id]
        );

        provider::export_user_data($appctx3);
        $this->assertTrue(writer::with_context($this->contextstudio2)->has_any_data());
        writer::reset();
    }

    /**
     * Test delete data for user.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_delete_data_for_user() {
        global $DB;
        // Create contents for student one in another studio.
        $this->posts->four = $this->generator->create_contents([
                'userid' => $this->users->students->one->id,
                'openstudio' => 'OS2',
                'name' => random_string(),
                'description' => random_string(),
                'content' => random_string(),
                'contenttype' => content::TYPE_TEXT,
                'visibility' => content::VISIBILITY_MODULE,
                'file' => 'mod/openstudio/tests/importfiles/test1.jpg'
        ]);

        $appctxstudentone = new approved_contextlist($this->users->students->one, 'mod_openstudio', [
                $this->contextstudio->id
        ]);

        $appctxstudentone2 = new approved_contextlist($this->users->students->one, 'mod_openstudio', [
                $this->contextstudio2->id
        ]);

        // Get data from student one.
        provider::export_user_data($appctxstudentone);
        provider::export_user_data($appctxstudentone2);

        // Content student one.
        $contextdata = writer::with_context($this->contextstudio);
        $content1a = $contextdata->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->one)]);
        $this->assertNotEmpty($content1a);

        $contextdata2 = writer::with_context($this->contextstudio2);
        $content1b = $contextdata2->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->four)]);
        $this->assertNotEmpty($content1b);

        writer::reset();
        // Test case Student one request delete data in OS1, but not data in OS2 won be affected.
        // Delete data from student one in OS1.
        provider::delete_data_for_user($appctxstudentone);
        provider::export_user_data($appctxstudentone);
        provider::export_user_data($appctxstudentone2);

        $contextdata = writer::with_context($this->contextstudio);
        $content1a = $contextdata->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->one)]);
        $this->assertEmpty($content1a);
        // Data of student one in OS2 doesn't be affected.
        $contextdata2 = writer::with_context($this->contextstudio2);
        $content1b = $contextdata2->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->four)]);
        $this->assertNotEmpty($content1b);

        writer::reset();
        // Test case Student one request delete data, but Studentone's comments have been replied should be updated to
        // 'Comment deleted by user request'.

        $appctxstudentone3 = new approved_contextlist($this->users->students->one, 'mod_openstudio', [
                $this->contextstudio->id,
                $this->contextstudio2->id
        ]);

        $appctxstudenttwo = new approved_contextlist($this->users->students->two, 'mod_openstudio', [
                $this->contextstudio2->id
        ]);
        provider::delete_data_for_user($appctxstudentone3);
        provider::export_user_data($appctxstudentone3);
        provider::export_user_data($appctxstudenttwo);

        // Content student one.
        $contextdata = writer::with_context($this->contextstudio);
        $content1 = $contextdata->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->one)]);
        // Post of students one is deleted.
        $this->assertEmpty($content1);

        // Content student two.
        $contextdata2 = writer::with_context($this->contextstudio2);
        $content2 = $contextdata2->get_data([get_string('privacy:subcontext:contents', 'mod_openstudio'),
                get_string('privacy:subcontext:content', 'mod_openstudio', $this->posts->two)]);
        // Post of students 2 has data.
        $this->assertNotEmpty($content2);

        $getcommentpost2 = array_values($DB->get_records('openstudio_comments',
                ['id' => $this->commentspost2->two]));

        $this->assertEquals($getcommentpost2[0]->commenttext, get_string('deletedbyrequest', 'mod_openstudio'));
        writer::reset();
    }

    /**
     * Create basic test data.
     *
     * @return array
     */
    private function create_basic_test_data() {
        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $course1 = $generator->create_course();
        $openstudio = $this->generator->create_instance([
            'course' => $course1->id,
            'idnumber' => 'Open Studio 1'
        ]);
        $contentdata = [
            'openstudio' => 'Open Studio 1',
            'userid' => $user3->id,
            'name' => 'Sample content 1',
            'description' => random_string(),
            'content' => random_string(),
        ];
        $content = $this->generator->create_contents($contentdata);

        return [$user1, $user2, $user3, $openstudio, $content];
    }

    /*
     * Test subscriptions.
     */
    public function test_userlist_provider_subscriptions() {
        global $DB;
        $component = 'openstudio';

        list($user1, , $user3, $openstudio) = $this->create_basic_test_data();
        $context = \context_module::instance($openstudio->cmid);
        subscription::create(subscription::MODULE, $user1->id, $openstudio->id, subscription::FORMAT_HTML,
            subscription::FREQUENCY_HOURLY);

        // Test get users in context.
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(2, $userids);
        $this->assertContains($user1->id, $userids);
        $this->assertContains($user3->id, $userids);

        // Test delete data for users.
        $this->assertNotEmpty($DB->get_records('openstudio_subscriptions', ['userid' => $user1->id]));
        $approvedlist = new \core_privacy\local\request\approved_userlist($context, $component, [$user1->id]);
        provider::delete_data_for_users($approvedlist);
        $this->assertEmpty($DB->get_records('openstudio_subscriptions', ['userid' => $user1->id]));
    }

    /*
     * Test honestycheck_check.
     */
    public function test_userlist_provider_honestycheck_check() {
        global $DB;
        $component = 'openstudio';

        list($user1, $user2, $user3, $openstudio) = $this->create_basic_test_data();
        $context = \context_module::instance($openstudio->cmid);
        $check1 = honesty::set($openstudio->cmid, $user1->id, true);
        $check2 = honesty::set($openstudio->cmid, $user2->id, true);

        // Test get users in context.
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(3, $userids);
        $this->assertContains($user1->id, $userids);
        $this->assertContains($user2->id, $userids);
        $this->assertContains($user3->id, $userids);

        // Test delete data for users.
        $records = $DB->get_records_select('openstudio_honesty_checks', 'userid IN (:user1, :user2)', [
            'user1' => $user1->id,
            'user2' => $user2->id
        ]);

        $this->assertCount(2, $records);
        $this->assertArrayHasKey($check1, $records);
        $this->assertArrayHasKey($check2, $records);

        $approvedlist = new \core_privacy\local\request\approved_userlist($context, $component, [$user1->id, $user2->id]);
        provider::delete_data_for_users($approvedlist);

        $records = $DB->get_records_select('openstudio_honesty_checks', 'userid IN (:user1, :user2)', [
            'user1' => $user1->id,
            'user2' => $user2->id
        ]);
        $this->assertEmpty($records);
    }

    /*
     * Test notification_check.
     */
    public function test_userlist_provider_notification_check() {
        global $DB;
        $component = 'openstudio';

        list($user1, $user2, $user3, $openstudio, $contentid) = $this->create_basic_test_data();
        $context = \context_module::instance($openstudio->cmid);

        $notification1 = $this->generator->create_notification([
            'userid' => $user1->id,
            'userfrom' => $user2->id,
            'contentid' => $contentid,
            'message' => 'Message 1 should be deleted.',
        ]);
        $notification2 = $this->generator->create_notification([
            'userid' => $user3->id,
            'userfrom' => $user2->id,
            'contentid' => $contentid,
            'message' => 'Message 2 content should be change to empty and owner to admin.',
        ]);

        // Test get users in context.
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();

        $this->assertCount(3, $userids);
        $this->assertContains($user1->id, $userids);
        $this->assertContains($user2->id, $userids);
        $this->assertContains($user3->id, $userids);

        // Test delete data for users.
        $approvedlist = new \core_privacy\local\request\approved_userlist($context, $component, [$user1->id, $user2->id]);
        provider::delete_data_for_users($approvedlist);
        $records = $DB->get_records_select('openstudio_notifications', 'id IN (:id1, :id2)', [
            'id1' => $notification1->id,
            'id2' => $notification2->id
        ]);

        // Only return second notification, first one is deleted.
        $this->assertCount(1, $records);
        // Message content is change.
        $this->assertEquals(get_string('deletedbyrequest', 'openstudio'), $records[$notification2->id]->message);
        // User not in approved list won't be affected.
        $this->assertEquals($user3->id, $records[$notification2->id]->userid);
        // Owner is changed to admin.
        $this->assertEquals(get_admin()->id, $records[$notification2->id]->userfrom);
    }

    /**
     * Test tracking check.
     */
    public function test_userlist_provider_tracking_check() {
        global $DB;
        $component = 'openstudio';

        list($user1, $user2, $user3, $openstudio, $contentid) = $this->create_basic_test_data();
        $context = \context_module::instance($openstudio->cmid);
        $tracking1 = tracking::log_action($contentid, tracking::READ_CONTENT, $user1->id, null, true);
        $tracking2 = tracking::log_action($contentid, tracking::READ_CONTENT, $user2->id, null, true);

        // Test get users in context.
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(3, $userids);
        $this->assertContains($user1->id, $userids);
        $this->assertContains($user2->id, $userids);
        $this->assertContains($user3->id, $userids);

        // Test delete data for users.
        $approvedlist = new \core_privacy\local\request\approved_userlist($context, $component, [$user1->id, $user2->id]);
        provider::delete_data_for_users($approvedlist);

        $records = $DB->get_records_select('openstudio_tracking', 'id IN (:id1, :id2)', [
            'id1' => $tracking1,
            'id2' => $tracking2
        ]);
        $this->assertEmpty($records);
    }

    /**
     * Test flag.
     */
    public function test_userlist_provider_flag() {
        global $DB;
        $component = 'openstudio';

        list($user1, $user2, $user3, $openstudio, $contentid) = $this->create_basic_test_data();
        $context = \context_module::instance($openstudio->cmid);

        $this->generator->create_flag([
            'contentid' => $contentid,
            'userid' => $user2->id,
            'flagid' => flags::FOLLOW_CONTENT,
        ]);

        $this->generator->create_flag([
            'contentid' => $contentid,
            'userid' => $user2->id,
            'flagid' => flags::FOLLOW_CONTENT,
        ]);

        $flagid = $this->generator->create_flag([
            'contentid' => $contentid,
            'userid' => $user2->id,
            'flagid' => flags::FOLLOW_CONTENT,
            'personid' => $user1->id,
        ]);

        $DB->update_record('openstudio_flags', ['id' => $flagid, 'contentid' => $contentid]);

        // Test get users in context.
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();

        $this->assertCount(3, $userids);
        $this->assertContains($user1->id, $userids);
        $this->assertContains($user2->id, $userids);
        $this->assertContains($user3->id, $userids);

        // Test delete data for users.
        $approvedlist = new \core_privacy\local\request\approved_userlist($context, $component, [$user1->id]);
        provider::delete_data_for_users($approvedlist);

        $records = array_values($DB->get_records_select('openstudio_flags', 'userid IN (:user1, :user2)', [
            'user1' => $user1->id,
            'user2' => $user2->id
        ], 'id'));

        $this->assertCount(2, $records);
        $this->assertEquals($user2->id, $records[0]->userid);
        $this->assertEquals($user2->id, $records[1]->userid);
        $this->assertEquals(get_admin()->id, $records[1]->personid);
    }

    /**
     * Test comment.
     */
    public function test_userlist_provider_comment() {
        global $DB;
        $component = 'openstudio';

        list($user1, $user2, $user3, $openstudio, $contentid) = $this->create_basic_test_data();
        $context = \context_module::instance($openstudio->cmid);

        $this->generator->create_comment([
                'contentid' => $contentid,
                'userid' => $user1->id,
                'comment' => random_string(),
                'filepath' => 'mod/studio/tests/importfiles/test.mp3',
                'filecontext' => $openstudio->cmid
        ]);

        // Comment post one by user 2.
        $this->generator->create_comment([
                'contentid' => $contentid,
                'userid' => $user2->id,
                'comment' => random_string(),
                'filepath' => 'mod/studio/tests/importfiles/test.mp3',
                'filecontext' => $openstudio->cmid
        ]);

        // Comment post one by user 3.
        $this->generator->create_comment([
                'contentid' => $contentid,
                'userid' => $user3->id,
                'comment' => random_string(),
                'filepath' => 'mod/studio/tests/importfiles/test.mp3',
                'filecontext' => $openstudio->cmid
        ]);

        // Test get users in context.
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(3, $userids);
        $this->assertContains($user1->id, $userids);
        $this->assertContains($user2->id, $userids);
        $this->assertContains($user3->id, $userids);
        // Test delete data for users.
        $approvedlist = new \core_privacy\local\request\approved_userlist($context, $component, [$user1->id, $user2->id, $user3->id]);
        provider::delete_data_for_users($approvedlist);
        $records = $DB->get_records_select('openstudio_comments', 'userid IN (:id1, :id2, :id3)', [
                'id1' => $user1->id,
                'id2' => $user2->id,
                'id3' => $user3->id
        ]);
        $this->assertEmpty($records);


    }

    /**
     * Test comment with reply.
     */
    public function test_userlist_provider_comment_with_reply() {
        global $DB;
        $component = 'openstudio';

        list($user1, $user2, $user3, $openstudio, $contentid) = $this->create_basic_test_data();
        $context = \context_module::instance($openstudio->cmid);

        $comment1 = $this->generator->create_comment([
                'contentid' => $contentid,
                'userid' => $user1->id,
                'comment' => random_string(),
        ]);
        // User2 reply comment in post of user1.
        comments::create(
                $contentid, $user2->id, random_string(),
                null, null, null, $comment1);
        $approvedlist = new \core_privacy\local\request\approved_userlist($context, $component, [$user1->id, $user2->id]);
        provider::delete_data_for_users($approvedlist);
        $adminid = get_admin()->id;
        $record = $DB->get_record_select('openstudio_comments', 'userid =:id1', [
                'id1' => $adminid,
        ]);
        $this->assertEquals($comment1, $record->id);
        $record = $DB->get_record_select('openstudio_comments', 'userid =:id1', [
                'id1' => $user2->id,
        ]);
        $this->assertEmpty($record);
    }

    /**
     * Test content and tags.
     */
    public function test_userlist_provider_content_and_tags() {
        global $DB;
        $component = 'openstudio';

        list($user1, $user2, $user3, $openstudio, $contentid) = $this->create_basic_test_data();
        $context = \context_module::instance($openstudio->cmid);
        // Add post to the folder.
        $contentdata1 = [
                'openstudio' => 'Open Studio 1',
                'visibility' => content::VISIBILITY_INFOLDERONLY,
                'userid' => $user1->id,
                'name' => 'folder_content_' . $user1->id,
                'contenttype' => content::TYPE_TEXT,
                'description' => random_string(),
                'content' => random_string(),
        ];
        $content1 = $this->generator->create_contents($contentdata1);
        $content = content::get($content1);
        // Create folders in OS1 of student one.
        $folderdata1 = [
                'openstudio' => 'Open Studio 1',
                'name' => random_string(),
                'levelid' => 0,
                'levelcontainer' => 0,
                'userid' => $user1->id
        ];
        $folderdataresult1 = $this->generator->create_folders($folderdata1);
        $folder1 = [
                'openstudio' => 'Open Studio 1',
                'folder' => 'folder_content_' . $user1->id,
                'folderid' => $folderdataresult1,
                'content' => $content->name,
                'contentorder' => $user1->id,
                'userid' =>  $user1->id
        ];
        $foldercontent1 = $this->generator->create_folder_contents($folder1);
        // Add tags Folder content by user1.
        tags::set($content1, ['aaa', 'bbb', 'ccc']);

        // Add post to the folder.
        $contentdata2 = [
                'openstudio' => 'Open Studio 1',
                'visibility' => content::VISIBILITY_INFOLDERONLY,
                'userid' => $user2->id,
                'name' => 'folder_content_' . $user2->id,
                'contenttype' => content::TYPE_TEXT,
                'description' => random_string(),
                'content' => random_string(),
        ];
        $content2 = $this->generator->create_contents($contentdata2);
        $content = content::get($content2);
        // Create folders in OS1 of student one.
        $folderdata2 = [
                'openstudio' => 'Open Studio 1',
                'name' => random_string(),
                'levelid' => 0,
                'levelcontainer' => 0,
                'userid' => $user2->id
        ];
        $folderdataresult2 = $this->generator->create_folders($folderdata2);
        $folder2 = [
                'openstudio' => 'Open Studio 1',
                'folder' => 'folder_content_' . $user2->id,
                'folderid' => $folderdataresult2,
                'content' => $content->name,
                'contentorder' => $user2->id,
                'userid' =>  $user2->id
        ];
        $foldercontent2 = $this->generator->create_folder_contents($folder2);
        // Add tags Folder content by user1.
        tags::set($content2, ['aaa', 'bbb', 'ccc']);
        $approvedlist = new \core_privacy\local\request\approved_userlist($context, $component, [$user1->id, $user2->id]);
        provider::delete_data_for_users($approvedlist);
        $resultfoldercontent1 = $DB->get_records('openstudio_contents', ['userid' => $user1->id]);
        $resultfoldercontent2 = $DB->get_records('openstudio_contents', ['userid' => $user2->id]);
        $foldercontent1result = $DB->get_records('openstudio_folder_contents', ['id' => $foldercontent1]);
        $foldercontent2result = $DB->get_records('openstudio_folder_contents', ['id' => $foldercontent2]);
        $foldercontentitem2result = $DB->get_records('openstudio_content_items', ['containerid' => $content2]);
        $foldercontentitem1result = $DB->get_records('openstudio_content_items', ['containerid' => $content1]);
        $tags = $DB->get_records('tag_instance', ['contextid' => $context->id]);
        $os1 = $DB->get_records('openstudio_contents', ['userid' => $user1->id]);
        $os2 = $DB->get_records('openstudio_contents', ['userid' => $user2->id]);
        $os3 = $DB->get_records('openstudio_contents', ['userid' => $user3->id]);
        $this->assertEmpty($resultfoldercontent1);
        $this->assertEmpty($resultfoldercontent2);
        $this->assertEmpty($foldercontent1result);
        $this->assertEmpty($foldercontent2result);
        $this->assertEmpty($foldercontentitem2result);
        $this->assertEmpty($foldercontentitem1result);
        $this->assertEmpty($tags);
        $this->assertEmpty($os1);
        $this->assertEmpty($os2);
        $this->assertCount(1, $os3);
    }
    /**
     * Test comment with own reply.
     */
    public function test_userlist_provider_comment_with_own_reply() {
        global $DB;
        $component = 'openstudio';

        list($user1, $user2, $user3, $openstudio, $contentid) = $this->create_basic_test_data();
        $context = \context_module::instance($openstudio->cmid);

        $comment1 = $this->generator->create_comment([
            'contentid' => $contentid,
            'userid' => $user1->id,
            'comment' => random_string(),
        ]);
        // User1 reply comment in post of user1.
        comments::create(
            $contentid, $user1->id, random_string(),
            null, null, null, $comment1);
        $approvedlist = new \core_privacy\local\request\approved_userlist($context, $component, [$user1->id, $user2->id]);
        provider::delete_data_for_users($approvedlist);
        $adminid = get_admin()->id;
        $records = $DB->get_records_select('openstudio_comments', 'userid =:id1', [
            'id1' => $adminid,
        ]);
        $this->assertEmpty($records);

        $reply1 =  $DB->get_record_select('openstudio_comments', 'userid =:id1 AND inreplyto =:inreplyto', [
            'id1' => $user1->id,
            'inreplyto' => $comment1
        ]);
        $this->assertEmpty($reply1);

        $comentuser1 = $DB->get_record_select('openstudio_comments', 'id =:id1', [
            'id1' => $comment1
        ]);
        $this->assertEmpty($comentuser1);
    }

    /**
     * Test comment with mixed reply.
     */
    public function test_userlist_provider_comment_with_mixed_reply() {
        global $DB;
        $component = 'openstudio';

        list($user1, $user2, $user3, $openstudio, $contentid) = $this->create_basic_test_data();
        $context = \context_module::instance($openstudio->cmid);

        $comment1 = $this->generator->create_comment([
            'contentid' => $contentid,
            'userid' => $user1->id,
            'comment' => random_string(),
        ]);
        // User3 reply comment in post of user1.
        comments::create(
            $contentid, $user3->id, random_string(),
            null, null, null, $comment1);
        // User1 reply comment in post of user1.
        comments::create(
            $contentid, $user1->id, random_string(),
            null, null, null, $comment1);
        $approvedlist = new \core_privacy\local\request\approved_userlist($context, $component, [$user1->id, $user2->id]);
        provider::delete_data_for_users($approvedlist);
        $adminid = get_admin()->id;
        $records = $DB->get_records_select('openstudio_comments', 'userid =:id1', [
            'id1' => $adminid,
        ]);
        $this->assertCount(1, $records);
        $deletedcomment = reset($records);
        $this->assertEquals(get_string('deletedbyrequest', 'mod_openstudio'), $deletedcomment->commenttext);
    }

}
