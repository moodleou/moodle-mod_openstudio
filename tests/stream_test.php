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
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

global $CFG;

class mod_openstudio_stream_testcase extends advanced_testcase {

    private $course;
    private $users;
    private $groups;
    private $teacherroleid;
    private $studentroleid;
    private $generator; // Contains mod_openstudio specific data generator functions.
    private $studioprivate;
    private $studiogroup;
    private $studiomodule;
    private $studioworkspace;
    private $studiogeneric; // Generic studio instance with no levels or contents.
    private $studiolevels; // Generic studio instance with levels only.
    private $totalcontents;
    private $pinboardcontents;
    private $groupings;
    private $tutorgroups;
    private $tutorrole;
    private $tutorrole2;

    /**
     * Sets up our fixtures.
     */
    protected function setUp() {
        global $DB;
        $this->resetAfterTest(true);
        $this->teacherroleid = 3;
        $this->studentroleid = 5;
        $this->totalcontents = 24; // This is what the scripts below create for ONE CMID.
        $this->pinboardcontents = 3; // This is what the scripts below create for ONE CMID.

        // Our test data has 1 course, 2 groups, 2 teachers and 10 students.

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create course groups.
        $this->groups = new stdClass();
        $this->groupings = new stdClass();
        $this->groupings->a  = $this->getDataGenerator()->create_grouping(
                array('name' => 'Grouping A', 'courseid' => $this->course->id));
        $this->groups->one = $this->getDataGenerator()->create_group(
                array('courseid' => $this->course->id, 'name' => 'The Starks'));
        $this->groups->two = $this->getDataGenerator()->create_group(
                array('courseid' => $this->course->id, 'name' => 'The Lannisters'));

        // Add groups to our groupings.
        $insert = new stdClass();
        $insert->groupingid = $this->groupings->a->id;
        $insert->groupid = $this->groups->one->id;

        $DB->insert_record('groupings_groups', $insert);
        $insert->groupingid = $this->groupings->a->id;
        $insert->groupid = $this->groups->two->id;
        $DB->insert_record('groupings_groups', $insert);

        // Create Users.
        $this->users = new stdClass();
        $this->users->students = new stdClass();
        $this->users->students->one = $this->getDataGenerator()->create_user(
                array('email' => 'student1@ouunittest.com', 'username' => 'student1'));
        $this->users->students->two = $this->getDataGenerator()->create_user(
                array('email' => 'student2@ouunittest.com', 'username' => 'student2'));
        $this->users->students->three = $this->getDataGenerator()->create_user(
                array('email' => 'student3@ouunittest.com', 'username' => 'student3'));
        $this->users->students->four = $this->getDataGenerator()->create_user(
                array('email' => 'student4@ouunittest.com', 'username' => 'student4'));
        $this->users->students->five = $this->getDataGenerator()->create_user(
                array('email' => 'student5@ouunittest.com', 'username' => 'student5'));
        $this->users->students->six = $this->getDataGenerator()->create_user(
                array('email' => 'student6@ouunittest.com', 'username' => 'student6'));
        $this->users->students->seven = $this->getDataGenerator()->create_user(
                array('email' => 'student7@ouunittest.com', 'username' => 'student7'));
        $this->users->students->eight = $this->getDataGenerator()->create_user(
                array('email' => 'student8@ouunittest.com', 'username' => 'student8'));
        $this->users->students->nine = $this->getDataGenerator()->create_user(
                array('email' => 'student9@ouunittest.com', 'username' => 'student9'));
        $this->users->students->ten = $this->getDataGenerator()->create_user(
                array('email' => 'student10@ouunittest.com', 'username' => 'student10'));
        $this->users->teachers = new stdClass();
        $this->users->teachers->one = $this->getDataGenerator()->create_user(
                array('email' => 'teacher1@ouunittest.com', 'username' => 'teacher1'));
        $this->users->teachers->two = $this->getDataGenerator()->create_user(
                array('email' => 'teacher2@ouunittest.com', 'username' => 'teacher2'));

        // Enroll our students and teacher (users) in the course.
        $this->getDataGenerator()->enrol_user(
                $this->users->students->one->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->two->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->three->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->four->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->five->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->six->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->eight->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->nine->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->students->ten->id, $this->course->id, $this->studentroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->one->id, $this->course->id, $this->teacherroleid, 'manual');
        $this->getDataGenerator()->enrol_user(
                $this->users->teachers->two->id, $this->course->id, $this->teacherroleid, 'manual');

        // Assign Students a group.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_openstudio');

        // Note: Students 1 to 5 + teacher 1 belong to group 1, students 6 to 10 + teacher 2 to group 2.
        $this->generator->add_users_to_groups(array(
                $this->groups->one->id => array(
                                $this->users->students->one->id,
                                $this->users->students->two->id,
                                $this->users->students->three->id,
                                $this->users->students->four->id,
                                $this->users->students->five->id,
                                $this->users->teachers->one->id
                        ),
                $this->groups->two->id => array(
                                $this->users->students->six->id,
                                $this->users->students->seven->id,
                                $this->users->students->eight->id,
                                $this->users->students->nine->id,
                                $this->users->students->ten->id,
                                $this->users->teachers->two->id
                        )
        ));

        $this->studioprivate = $this->generator->create_instance(array('course' => $this->course->id));
        // Let's create and populate our mock levels.
        $this->studioprivate->leveldata = $this->generator->create_mock_levels($this->studioprivate->id);

        // Now let's create and populate some contents.
        $this->studioprivate->contentinstances = new stdClass();
        // Student1 is owner of 24 normal and 3 pin contents of 24 normal and 3 pin contents.
        $this->studioprivate->contentinstances->student = $this->generator->create_mock_contents($this->studioprivate->id,
                $this->studioprivate->leveldata, $this->users->students->one->id,
                mod_openstudio\local\api\content::VISIBILITY_PRIVATE);

        $this->studiomodule = $this->generator->create_instance(array('course' => $this->course->id));
        // Let's create and populate our mock levels.
        $this->studiomodule->leveldata = $this->generator->create_mock_levels($this->studiomodule->id);

        // Now let's create and populate some contents.
        $this->studiomodule->contentinstances = new stdClass();
        // Student5 is owner of 24 normal and 3 pin contents of 24 normal and 3 pin contents.
        $this->studiomodule->contentinstances->student = $this->generator->create_mock_contents($this->studiomodule->id,
                $this->studiomodule->leveldata, $this->users->students->five->id,
                mod_openstudio\local\api\content::VISIBILITY_MODULE);

        $this->studiogroup = $this->generator->create_instance(array('course' => $this->course->id),
                array('groupingid' => $this->groupings->a->id));
        // Let's create and populate our mock levels.
        $this->studiogroup->leveldata = $this->generator->create_mock_levels($this->studiogroup->id);

        // Now let's create and populate some contents.
        $this->studiogroup->contentinstances = new stdClass();
        $this->studiogroup->contentinstances->student6 = $this->generator->create_mock_contents(
                $this->studiogroup->id, $this->studiogroup->leveldata,
                $this->users->students->six->id, mod_openstudio\local\api\content::VISIBILITY_GROUP);
        $this->studiogroup->contentinstances->student8 = $this->generator->create_mock_contents(
                $this->studiogroup->id, $this->studiogroup->leveldata,
                $this->users->students->eight->id, mod_openstudio\local\api\content::VISIBILITY_GROUP);

        $this->studioworkspace = $this->generator->create_instance(array('course' => $this->course->id));
        // Let's create and populate our mock levels.
        $this->studioworkspace->leveldata = $this->generator->create_mock_levels($this->studioworkspace->id);

        // Now let's create and populate some contents.
        $this->studioworkspace->contentinstances = new stdClass();
        $this->studioworkspace->contentinstances->student8 = $this->generator->create_mock_contents(
                $this->studioworkspace->id,
                $this->studioworkspace->leveldata,
                $this->users->students->eight->id, mod_openstudio\local\api\content::VISIBILITY_PRIVATE);
        $this->studioworkspace->contentinstances->student9 = $this->generator->create_mock_contents(
                $this->studioworkspace->id,
                $this->studioworkspace->leveldata,
                $this->users->students->nine->id, mod_openstudio\local\api\content::VISIBILITY_MODULE);
        $this->studioworkspace->contentinstances->student10 = $this->generator->create_mock_contents(
                $this->studioworkspace->id,
                $this->studioworkspace->leveldata,
                $this->users->students->ten->id, mod_openstudio\local\api\content::VISIBILITY_MODULE);
        $this->studioworkspace->contentinstances->student3 = $this->generator->create_mock_contents(
                $this->studioworkspace->id,
                $this->studioworkspace->leveldata,
                $this->users->students->four->id, mod_openstudio\local\api\content::VISIBILITY_GROUP);
        $this->studioworkspace->contentinstances->student4 = $this->generator->create_mock_contents(
                $this->studioworkspace->id,
                $this->studioworkspace->leveldata,
                $this->users->students->three->id, mod_openstudio\local\api\content::VISIBILITY_GROUP);

        // Create generic studios.
        $this->studiogeneric = $this->generator->create_instance(array('course' => $this->course->id, 'idnumber' => 'OS1'));
        $this->studiolevels = $this->generator->create_instance(array('course' => $this->course->id));
        $this->studiolevels->leveldata = $this->generator->create_mock_levels($this->studiolevels->id);

    }

    protected function tearDown() {
        $this->course = '';
        $this->users = '';
        $this->groups = '';
        $this->teacherroleid = '';
        $this->studentroleid = '';
        $this->generator = '';
        $this->studioprivate = '';
        $this->studiogroup = '';
        $this->studiomodule = '';
        $this->studiogeneric = '';
        $this->studiolevels = '';
        $this->totalcontents = '';
        $this->pinboardcontents = '';
        $this->workspace = '';
    }
    /**
     * Test to key internal functions which are used to clean URL and URL query parameters
     * to prevent security/malicious attacks.
     */
    public function test_stream_param_cleaners() {
        $this->resetAfterTest(true);
        $searchurl = 'https://learn2.open.ac.uk/mod/openstudio/search.php';
        $viewurl = 'https://learn2.open.ac.uk/mod/openstudio/view.php';

        $url = $searchurl . '?searchtext=My+Life+Story&id=498074&vid=1';
        $result = mod_openstudio\local\util::extract_url_params($url);
        $this->assertEquals($result['searchtext'], 'My+Life+Story');
        $this->assertEquals($result['id'], '498074');
        $this->assertEquals($result['vid'], '1');

        $url = $searchurl . '?searchtext=My%20Life%20Story&attack=<script>alert(1);</script>&bluff=rogue?bluff=1&endvalue=asda';
        $result = mod_openstudio\local\util::extract_url_params($url);
        $this->assertEquals($result['searchtext'], 'My%20Life%20Story');
        $this->assertEquals($result['attack'], 'alert(1);');
        $this->assertEquals($result['bluff'], 'rogue?bluff');
        $this->assertEquals($result['endvalue'], 'asda');

        $_SERVER['REQUEST_URI'] = $viewurl . '?filteron=1&reset=0&id=498074&vid=1&page=0&fsort=3&osort=1&groupid=0&fblock=-2'
                . '&fblockarray%5B%5D=16&fblockarray%5B%5D=17&fblockarray%5B%5D=18&fblockarray%5B%5D=19&ftypearray%5B%5D=0'
                . '&fflagarray%5B%5D=0&fstatus=0&fscope=1#filter';
        $url = mod_openstudio\local\util::get_page_name_and_substitute_params();
        $this->assertEquals($url, 'view.php?filteron=1&id=498074&vid=1&page=0&fsort=3&osort=1&groupid=0&fblock=-2'
                . '&ftypearray%5B%5D=0&fflagarray%5B%5D=0&fstatus=0&fscope=1&9=fblockarray%5B%5D=16&10=fblockarray%5B%5D=17'
                . '&11=fblockarray%5B%5D=18&12=fblockarray%5B%5D=19&');

        $_SERVER['REQUEST_URI'] = $viewurl . '?filteron=1&reset=0&id=498074&vid=1&page=0&fsort=3&osort=1&groupid=0&fblock=-2'
                . '&fblockarray%5B%5D=16&fblockarray%5B%5D=17&fblockarray%5B%5D=18&fblockarray%5B%5D=19&ftypearray%5B%5D=0'
                . '&fflagarray%5B%5D=0&fstatus=0&fscope=1#filter';
        $url = mod_openstudio\local\util::get_page_name_and_substitute_params(
                array('filteron' => 0),
                array('vid')
        );
        $this->assertEquals($url, 'view.php?filteron=0&reset=0&id=498074&page=0&fsort=3&osort=1&groupid=0&fblock=-2'
                . '&ftypearray%5B%5D=0&fflagarray%5B%5D=0&fstatus=0&fscope=1&9=fblockarray%5B%5D=16&10=fblockarray%5B%5D=17'
                . '&11=fblockarray%5B%5D=18&12=fblockarray%5B%5D=19&');

        $filename = rawurlencode('file name with spaces.doc');
        $this->assertEquals('file%20name%20with%20spaces.doc', $filename);

        $filename = urlencode('file name with spaces.doc');
        $this->assertEquals('file+name+with+spaces.doc', $filename);

        $url = 'https://learn2.open.ac.uk/mod/openstudio/search.php?searchtext=My+Life+Story&id=498074&vid=1';
        $result = urlencode($url);
        $this->assertEquals($result, 'https%3A%2F%2Flearn2.open.ac.uk%2Fmod%2Fopenstudio%2Fsearch.php'
                . '%3Fsearchtext%3DMy%2BLife%2BStory%26id%3D498074%26vid%3D1');
    }

    /**
     * Check all private streams.
     */
    public function test_stream_api_private() {
        $this->resetAfterTest(true);

        // Set Active User to student1.
        $this->setUser($this->users->students->one);

        // Now let's check if student1 can access his/her own contents.
        // So userid and contentownerid should be the same.
        $result1 = mod_openstudio\local\api\stream::get_contents(
                $this->studioprivate->id, $this->groupings->a->id,
                $this->users->students->one->id, $this->users->students->one->id,
                mod_openstudio\local\api\content::VISIBILITY_PRIVATE);

        $this->assertNotEquals(false, $result1);

        // This should bring back 24 contents, ALL owned by student1. $result2 will
        // be the same, so need to repeat this.
        $totalcount = 0;
        foreach ($result1 as $content) {
            $totalcount++;
            // Check that the user matches PRIVATE returns for non-pinboard contents.
            $this->assertEquals($this->users->students->one->id, $content->userid);
        }

        // Check that the total number of results is the same as those our script generates.
        $this->assertEquals($totalcount, $this->totalcontents);

        // Pinboard tested here.
        // Let's run the query again to get pinboard only contents and see if the count matches.
        $result2 = mod_openstudio\local\api\stream::get_contents(
                $this->studioprivate->id, $this->groupings->a->id,
                $this->users->students->one->id, $this->users->students->one->id,
                mod_openstudio\local\api\content::VISIBILITY_PRIVATE, null, null, null, null, null, null,
                array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0), 0, 0, true);
        $this->assertNotEquals(false, $result2);

        // This should bring back 3 contents.
        $pinboardcount = iterator_count($result2);
        foreach ($result2 as $content) {
            // Check that the user matches, only for pinboard contents.
            $this->assertEquals($content->userid, $this->users->students->one->id);
        }

        // Check that the total number of results is the same as those our script generates.
        $this->assertEquals($pinboardcount, $this->pinboardcontents);
    }

    /**
     * Check all module streams
     */

    public function test_stream_api_module() {
        $this->resetAfterTest(true);

         // Set Active User to student5.
        $this->setUser($this->users->students->five);

        // Student5 viewing his own contents. Should return false as the ONLY contents belong to student5.
        $result3 = mod_openstudio\local\api\stream::get_contents(
                $this->studiomodule->id, $this->groupings->a->id,
                $this->users->students->five->id, $this->users->students->five->id,
                mod_openstudio\local\api\content::VISIBILITY_MODULE);

        // Only data from student5 comes back so this should NOT be false.
        $this->assertNotEquals(false, $result3);

        // We must check to make sure that the expected 27 contents are given.
        if ($result3 != false) {
            $this->assertEquals(27, iterator_count($result3));
        }
    }

    /**
     * Check all group streams.
     */
    public function test_stream_api_group() {
        $this->resetAfterTest(true);

        $this->setUser($this->users->students->six);

        // For this test:
        // 1. Student6 owns 24 group visibility contents and 3 pinboard contents at group level.
        // 2. Student8 owns 24 group visibility contents and 3 pinboard contents at group level.

        $result4 = mod_openstudio\local\api\stream::get_contents(
                $this->studiogroup->id, $this->groupings->a->id,
                $this->users->students->eight->id, $this->users->students->eight->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP);
        $count = iterator_count($result4);
        foreach ($result4 as $content) {
            // The results should be filtered and show ONLY group visibility results ...
            // From this studio for the other user(s) in the group.
            $this->assertNotEquals($content->userid, $this->users->students->eight->id);

            // In our dataset, the only other user is student6, so he should be the user on all the results.
            $this->assertEquals($content->userid, $this->users->students->six->id);
        }

        // Always 54, which means owners pinboards are also included.
        $this->assertEquals($count, 2 * ($this->totalcontents + $this->pinboardcontents));

         // Let's test out the filters in the mod_openstudio\local\api\stream::get_contents() function.
        $resultblock11 = mod_openstudio\local\api\stream::get_contents(
                $this->studiogroup->id, $this->groupings->a->id,
                $this->users->students->eight->id, $this->users->students->eight->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP,
                array($this->studiogroup->leveldata['blockslevels'][0]),
                $filtertype = null, $filterscope = null, $filterparticipation = null,
                $filterstatus = null, $filtertags = null,
                $sortorder = array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0),
                $pagestart = 0, $pagesize = 0, $pinboardonly = false, $includecount = false,
                $canmanagecontent = false);
        // There should be only 6 contents in Block 11.
        $this->assertEquals(12, iterator_count($resultblock11));

        // There are no images sho this should be false.
        $resultblock11images = mod_openstudio\local\api\stream::get_contents(
                $this->studiogroup->id, $this->groupings->a->id,
                $this->users->students->eight->id, $this->users->students->eight->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP,
                array($this->studiogroup->leveldata['blockslevels'][0]),
                mod_openstudio\local\api\content::TYPE_IMAGE, $filterscope = null, $filterparticipation = null,
                $filterstatus = null, $filtertags = null,
                $sortorder = array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0),
                $pagestart = 0, $pagesize = 0, $pinboardonly = false, $includecount = false,
                $canmanagecontent = false);
        $this->assertEquals(false, $resultblock11images);

        // Let's add some flags to a content to test scope.
        // We'll use Slot ids 67, 68, 69, 71 and 72 (and not 70 delibarately).
        // These are student 6's contents (as we are viewing the group stream).
        // NOTE: The FLAG API uses mod_openstudio\local\api\flags constants, but the STREAM API uses
        // mod_openstudio\local\api\stream::FILTER_* constants ... For identifying the SAME flags.
        $contenttoflag = array();
        foreach ($this->studiogroup->contentinstances->student6['contents'] as $item) {
            $contenttoflag[] = $item[0];
            if (count($contenttoflag) >= 5) {
                break;
            }
        }
        $this->assertEquals(true, studio_api_flags_toggle($contenttoflag[0],
                mod_openstudio\local\api\flags::FAVOURITE,
                'on', $this->users->students->nine->id));
        $this->assertEquals(true, studio_api_flags_toggle($contenttoflag[1],
                mod_openstudio\local\api\flags::FAVOURITE,
                'on', $this->users->students->nine->id));
        $this->assertEquals(true, studio_api_flags_toggle($contenttoflag[2],
                mod_openstudio\local\api\flags::FAVOURITE,
                'on', $this->users->students->nine->id));
        $this->assertEquals(true, studio_api_flags_toggle($contenttoflag[3],
                mod_openstudio\local\api\flags::FAVOURITE,
                'on', $this->users->students->eight->id));
        $this->assertEquals(true, studio_api_flags_toggle($contenttoflag[4],
                mod_openstudio\local\api\flags::FAVOURITE,
                'on', $this->users->students->eight->id));

        $resultblock11everyone = mod_openstudio\local\api\stream::get_contents($this->studiogroup->id, $this->groupings->a->id,
                $this->users->students->eight->id, $this->users->students->six->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP,
                array($this->studiogroup->leveldata['blockslevels'][0]),
                $filtertype = null,
                mod_openstudio\local\api\stream::SCOPE_EVERYONE,
                mod_openstudio\local\api\stream::FILTER_FAVOURITES,
                $filterstatus = null, $filtertags = null,
                $sortorder = array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0),
                $pagestart = 0, $pagesize = 0, $pinboardonly = false, $includecount = false,
                $canmanagecontent = false, 0, 2);
        $this->assertEquals(5, iterator_count($resultblock11everyone));

        $resultblock11my = mod_openstudio\local\api\stream::get_contents($this->studiogroup->id, $this->groupings->a->id,
                $this->users->students->eight->id, $this->users->students->six->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP,
                array($this->studiogroup->leveldata['blockslevels'][0]),
                $filtertype = null,
                mod_openstudio\local\api\stream::SCOPE_MY,
                mod_openstudio\local\api\stream::FILTER_FAVOURITES,
                $filterstatus = null, $filtertags = null,
                $sortorder = array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0),
                $pagestart = 0, $pagesize = 0, $pinboardonly = false, $includecount = false,
                $canmanagecontent = false, 0, 2);
        $this->assertEquals(2, iterator_count($resultblock11my));

        // Now let's add some tags to test if our tag filter works.
        studio_api_tags_tag_slot($contenttoflag[0], 'Winterfell, Targeryen');
        studio_api_tags_tag_slot($contenttoflag[1], 'Winterfell, Lannister');
        studio_api_tags_tag_slot($contenttoflag[2], 'Martell');

        $resultblock11tags1 = mod_openstudio\local\api\stream::get_contents($this->studiogroup->id, $this->groupings->a->id,
                $this->users->students->eight->id, $this->users->students->six->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP,
                array($this->studiogroup->leveldata['blockslevels'][0]),
                $filtertype = null, $filterscope = null, $filterparticipation = null, $filterstatus = null,
                'Winterfell', $sortorder = array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0),
                $pagestart = 0, $pagesize = 0, $pinboardonly = false, $includecount = false,
                $canmanagecontent = false, 0, 2);
        $this->assertEquals(2, iterator_count($resultblock11tags1));

        $resultblock11tags2 = mod_openstudio\local\api\stream::get_contents($this->studiogroup->id, $this->groupings->a->id,
                $this->users->students->eight->id, $this->users->students->six->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP,
                array($this->studiogroup->leveldata['blockslevels'][0]),
                $filtertype = null, $filterscope = null, $filterparticipation = null, $filterstatus = null,
                'Martell', $sortorder = array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0),
                $pagestart = 0, $pagesize = 0, $pinboardonly = false, $includecount = false,
                $canmanagecontent = false, 0, 2);
        $this->assertEquals(1, iterator_count($resultblock11tags2));

        $resultblock11tags3 = mod_openstudio\local\api\stream::get_contents($this->studiogroup->id, $this->groupings->a->id,
                $this->users->students->eight->id, $this->users->students->six->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP,
                array($this->studiogroup->leveldata['blockslevels'][0]),
                $filtertype = null, $filterscope = null, $filterparticipation = null, $filterstatus = null,
                'Lannister', $sortorder = array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0),
                $pagestart = 0, $pagesize = 0, $pinboardonly = false, $includecount = false,
                $canmanagecontent = false, 0, 2);
        $this->assertEquals(1, iterator_count($resultblock11tags3));

    }

    /**
     * Check all workspace streams.
     */
    public function test_stream_api_workspace() {
        $this->resetAfterTest(true);

        $this->setUser($this->users->students->eight);

        // Let's run it for 2 different users.
        $result6 = mod_openstudio\local\api\stream::get_contents(
                $this->studioworkspace->id, $this->groupings->a->id,
                $this->users->students->eight->id,
                $this->users->students->nine->id, mod_openstudio\local\api\content::VISIBILITY_WORKSPACE);

        // Should only bring back the results of 9.
        $this->assertNotEquals(false, $result6);
        $count = iterator_count($result6);
        foreach ($result6 as $content) {
            $this->assertEquals($content->userid, $this->users->students->nine->id);
        }

        // Always 27, which means owners pinboards are also included.
        $this->assertEquals($count, $this->totalcontents + $this->pinboardcontents);

        // Let's run it for 2 different users.
        $result7 = mod_openstudio\local\api\stream::get_contents(
                $this->studioworkspace->id, $this->groupings->a->id,
                $this->users->students->eight->id,
                $this->users->students->three->id, mod_openstudio\local\api\content::VISIBILITY_WORKSPACE,
                $filterblocks = null, $filtertype = null, $filterscope = null,
                $filterparticipation = null, $filterstatus = null, $filtertags = null,
                $sortorder = array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0),
                $pagestart = 0, $pagesize = 0,
                $pinboardonly = false, $includecount = false, $canmanagecontent = false,
                $groupid = 0, $groupmode = 2);
        $this->assertEquals(27, iterator_count($result7));

        $this->setUser($this->users->students->three);

        // Let's run it for 2 different users in the same group.
        $result8 = mod_openstudio\local\api\stream::get_contents(
                $this->studioworkspace->id, $this->groupings->a->id,
                $this->users->students->three->id,
                $this->users->students->four->id, mod_openstudio\local\api\content::VISIBILITY_WORKSPACE,
                $filterblocks = null, $filtertype = null, $filterscope = null,
                $filterparticipation = null, $filterstatus = null, $filtertags = null,
                $sortorder = array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0),
                $pagestart = 0, $pagesize = 0,
                $pinboardonly = false, $includecount = false, $canmanagecontent = false,
                $groupid = 0, $groupmode = 2);

        // Should only bring back the results of 4.
        $this->assertNotEquals(false, $result8);
        $count = iterator_count($result8);
        foreach ($result8 as $content) {
            $this->assertEquals($content->userid, $this->users->students->four->id);
        }

        // Always 27, which means owners pinboards are also included.
        $this->assertEquals($count, $this->totalcontents + $this->pinboardcontents);

        // Irrespective of groups, users should be able to see MODULE level contents.
        $result9 = mod_openstudio\local\api\stream::get_contents(
                $this->studioworkspace->id, $this->groupings->a->id,
                $this->users->students->three->id,
                $this->users->students->ten->id, mod_openstudio\local\api\content::VISIBILITY_WORKSPACE);

        // Should only bring back the results of 10.
        $this->assertNotEquals(false, $result9);
        $count = iterator_count($result9);
        foreach ($result9 as $content) {
            $this->assertEquals($content->userid, $this->users->students->ten->id);
        }

        // Always 27, which means owners pinboards are also included.
        $this->assertEquals($count, $this->totalcontents + $this->pinboardcontents);

        // Finally, user 8's stream is private and should be invisible to all users.
        $result10 = mod_openstudio\local\api\stream::get_contents(
                $this->studioworkspace->id, $this->groupings->a->id,
                $this->users->students->three->id,
                $this->users->students->eight->id, mod_openstudio\local\api\content::VISIBILITY_WORKSPACE);

        // Should be false.
        $this->assertEquals(false, $result10);
    }

    public function test_stream_api_tutor_contents() {
        global $DB;
        // Setup tutor groups.
        $this->tutorrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->tutorrole2 = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->users->tutors = new stdClass();
        $this->users->tutors->one = $this->getDataGenerator()->create_user(
                array('email' => 'tutor1@ouunittest.com', 'username' => 'tutor1'));
        $this->users->tutors->two = $this->getDataGenerator()->create_user(
                array('email' => 'tutor2@ouunittest.com', 'username' => 'tutor2'));
        $this->users->tutors->three = $this->getDataGenerator()->create_user(
                array('email' => 'tutor2@ouunittest.com', 'username' => 'tutor3'));
        $this->users->tutors->four = $this->getDataGenerator()->create_user(
                array('email' => 'tutor2@ouunittest.com', 'username' => 'tutor4'));
        $this->getDataGenerator()->enrol_user($this->users->tutors->one->id, $this->course->id,
                $this->tutorrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->tutors->two->id, $this->course->id,
                $this->tutorrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->users->tutors->four->id, $this->course->id,
                $this->tutorrole2->id, 'manual');

        // Create tutor groups.
        $this->tutorgroups = new stdClass();
        $this->tutorgroups->one = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));
        $this->tutorgroups->two = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));
        $this->tutorgroups->three = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));

        $this->groupings->one = $this->getDataGenerator()->create_grouping(array('courseid' => $this->course->id));
        $this->groupings->two = $this->getDataGenerator()->create_grouping(array('courseid' => $this->course->id));

        $DB->set_field('course_modules', 'groupingid', $this->groupings->one->id, array('id' => $this->studiogeneric->cmid));

        $this->getDataGenerator()->create_grouping_group(array(
                        'groupid' => $this->tutorgroups->one->id,
                        'groupingid' => $this->groupings->one->id)
        );
        $this->getDataGenerator()->create_grouping_group(array(
                        'groupid' => $this->tutorgroups->two->id,
                        'groupingid' => $this->groupings->one->id)
        );
        $this->getDataGenerator()->create_grouping_group(array(
                        'groupid' => $this->tutorgroups->three->id,
                        'groupingid' => $this->groupings->two->id)
        );

        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->students->one->id,
                'groupid' => $this->tutorgroups->one->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->students->two->id,
                'groupid' => $this->tutorgroups->two->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->tutors->one->id,
                'groupid' => $this->tutorgroups->one->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->tutors->two->id,
                'groupid' => $this->tutorgroups->two->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->tutors->three->id,
                'groupid' => $this->tutorgroups->three->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->students->one->id,
                'groupid' => $this->tutorgroups->three->id
        ));
        $this->getDataGenerator()->create_group_member(array(
                'userid' => $this->users->tutors->four->id,
                'groupid' => $this->tutorgroups->one->id
        ));

        // Create content.
        $tutorcontentid = $this->generator->create_contents(array(
                'openstudio' => 'OS1',
                'userid' => $this->users->students->one->id,
                'name' => 'Test Slot',
                'description' => 'Test Slot',
                'visibility' => mod_openstudio\local\api\content::VISIBILITY_TUTOR
        ));

        $tutorroles = array($this->tutorrole->id);

        // Student 1 in in the right group, but isn't a tutor, so should not see the tutor content on the group stream.
        $stream = mod_openstudio\local\api\stream::get_contents(
                $this->studiogeneric->id, // Studio ID.
                $this->groupings->one->id, // Grouping ID.
                $this->users->students->one->id, // User ID.
                $this->users->students->one->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP, // Visibility.
                null, // Block filter.
                null, // Type filter.
                null, // Scope filter.
                null, // Patiticipation filter.
                null, // Status filter.
                null, // Tags filter.
                array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0), // Sorting.
                0, // Pagestart.
                100, // Pagesize.
                false, // Pinboard only.
                true, // Include Count.
                false, // Can manage content.
                $this->tutorgroups->one->id, // Group ID.
                SEPARATEGROUPS, // Group Mode.
                false, // Activity Mode.
                false, // Can access all groups.
                false, // In collection Mode.
                false, // Reciprocal access.
                $tutorroles // Tutor roles.
        );
        $this->assertFalse($stream);

        // Student 2 isn't a tutor or in the right group.
        $stream = mod_openstudio\local\api\stream::get_contents(
                $this->studiogeneric->id, // Studio ID.
                $this->groupings->one->id, // Grouping ID.
                $this->users->students->two->id, // User ID.
                $this->users->students->two->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP, // Visibility.
                null, // Block filter.
                null, // Type filter.
                null, // Scope filter.
                null, // Patiticipation filter.
                null, // Status filter.
                null, // Tags filter.
                array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0), // Sorting.
                0, // Pagestart.
                100, // Pagesize.
                false, // Pinboard only.
                true, // Include Count.
                false, // Can manage content.
                $this->tutorgroups->one->id, // Group ID.
                SEPARATEGROUPS, // Group Mode.
                false, // Activity Mode.
                false, // Can access all groups.
                false, // In collection Mode.
                false, // Reciprocal access.
                $tutorroles // Tutor roles.
        );
        $this->assertFalse($stream);

        // Tutor 2 is a tutor, but is in the wrong group.
        $stream = mod_openstudio\local\api\stream::get_contents(
                $this->studiogeneric->id, // Studio ID.
                $this->groupings->one->id, // Grouping ID.
                $this->users->tutors->two->id, // User ID.
                $this->users->tutors->two->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP, // Visibility.
                null, // Block filter.
                null, // Type filter.
                null, // Scope filter.
                null, // Patiticipation filter.
                null, // Status filter.
                null, // Tags filter.
                array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0), // Sorting.
                0, // Pagestart.
                100, // Pagesize.
                false, // Pinboard only.
                true, // Include Count.
                false, // Can manage content.
                $this->tutorgroups->one->id, // Group ID.
                SEPARATEGROUPS, // Group Mode.
                false, // Activity Mode.
                true, // Can access all groups.
                false, // In collection Mode.
                false, // Reciprocal access.
                $tutorroles // Tutor roles.
        );
        $this->assertFalse($stream);

        // Tutor 1 is a tutor, and is in the right group.
        $stream = mod_openstudio\local\api\stream::get_contents(
                $this->studiogeneric->id, // Studio ID.
                $this->groupings->one->id, // Grouping ID.
                $this->users->tutors->one->id, // User ID.
                $this->users->tutors->one->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP, // Visibility.
                null, // Block filter.
                null, // Type filter.
                null, // Scope filter.
                null, // Patiticipation filter.
                null, // Status filter.
                null, // Tags filter.
                array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0), // Sorting.
                0, // Pagestart.
                100, // Pagesize.
                false, // Pinboard only.
                true, // Include Count.
                false, // Can manage content.
                $this->tutorgroups->one->id, // Group ID.
                SEPARATEGROUPS, // Group Mode.
                false, // Activity Mode.
                true, // Can access all groups.
                false, // In collection Mode.
                false, // Reciprocal access.
                $tutorroles // Tutor roles.
        );
        $this->assertEquals(1, $stream->total);
        $streamcontents = array();
        foreach ($stream->contents as $content) {
            $streamcontents[] = $content->id;
        }
        $this->assertTrue(in_array($tutorcontentid, $streamcontents));

        // Tutor 3 is a tutor and in a group with student1, but the group is not in the right grouping.
        $stream = mod_openstudio\local\api\stream::get_contents(
                $this->studiogeneric->id, // Studio ID.
                $this->groupings->one->id, // Grouping ID.
                $this->users->tutors->three->id, // User ID.
                $this->users->tutors->three->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP, // Visibility.
                null, // Block filter.
                null, // Type filter.
                null, // Scope filter.
                null, // Patiticipation filter.
                null, // Status filter.
                null, // Tags filter.
                array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0), // Sorting.
                0, // Pagestart.
                100, // Pagesize.
                false, // Pinboard only.
                true, // Include Count.
                false, // Can manage content.
                $this->tutorgroups->one->id, // Group ID.
                SEPARATEGROUPS, // Group Mode.
                false, // Activity Mode.
                true, // Can access all groups.
                false, // In collection Mode.
                false, // Reciprocal access.
                $tutorroles // Tutor roles.
        );
        $this->assertFalse($stream);

        // Tutor 4 is in the right group, but has the wrong role.
        $stream = mod_openstudio\local\api\stream::get_contents(
                $this->studiogeneric->id, // Studio ID.
                $this->groupings->one->id, // Grouping ID.
                $this->users->tutors->four->id, // User ID.
                $this->users->tutors->four->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP, // Visibility.
                null, // Block filter.
                null, // Type filter.
                null, // Scope filter.
                null, // Patiticipation filter.
                null, // Status filter.
                null, // Tags filter.
                array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0), // Sorting.
                0, // Pagestart.
                100, // Pagesize.
                false, // Pinboard only.
                true, // Include Count.
                false, // Can manage content.
                $this->tutorgroups->one->id, // Group ID.
                SEPARATEGROUPS, // Group Mode.
                false, // Activity Mode.
                true, // Can access all groups.
                false, // In collection Mode.
                false, // Reciprocal access.
                $tutorroles // Tutor roles.
        );
        $this->assertFalse($stream);

        // Tutor 4 is in the right group, and their role is now included in the list.
        $stream = mod_openstudio\local\api\stream::get_contents(
                $this->studiogeneric->id, // Studio ID.
                $this->groupings->one->id, // Grouping ID.
                $this->users->tutors->four->id, // User ID.
                $this->users->tutors->four->id,
                mod_openstudio\local\api\content::VISIBILITY_GROUP, // Visibility.
                null, // Block filter.
                null, // Type filter.
                null, // Scope filter.
                null, // Patiticipation filter.
                null, // Status filter.
                null, // Tags filter.
                array('id' => mod_openstudio\local\api\stream::SORT_BY_DATE, 'asc' => 0), // Sorting.
                0, // Pagestart.
                100, // Pagesize.
                false, // Pinboard only.
                true, // Include Count.
                false, // Can manage content.
                $this->tutorgroups->one->id, // Group ID.
                SEPARATEGROUPS, // Group Mode.
                false, // Activity Mode.
                true, // Can access all groups.
                false, // In collection Mode.
                false, // Reciprocal access.
                [$this->tutorrole->id, $this->tutorrole2->id] // Tutor roles.
        );
        $this->assertEquals(1, $stream->total);
        $streamcontents = array();
        foreach ($stream->contents as $content) {
            $streamcontents[] = $content->id;
        }
        $this->assertTrue(in_array($tutorcontentid, $streamcontents));
    }

    public function test_get_contents_by_ids() {
        // Test getting a simple list of contents.
        $contentids = $this->studiomodule->contentinstances->student['pinboard_contents'];
        $contents = mod_openstudio\local\api\stream::get_contents_by_ids($this->users->students->five->id, $contentids);
        $this->assertEquals(count($contentids), iterator_count($contents));

        // Test reciprocal access.
        $levelcontentids = [];
        foreach ($this->studioworkspace->contentinstances->student8['contents'] as $level) {
            $levelcontentids = array_merge($levelcontentids, $level);
        }
        // Another user who has content in the same levels should see the contents.
        $levelcontents = mod_openstudio\local\api\stream::get_contents_by_ids(
                $this->users->students->nine->id, $levelcontentids, true);
        $this->assertEquals(count($levelcontentids), iterator_count($levelcontents));
        // Another user who doesn't have content in the same levels should not see the contents.
        $levelcontents = mod_openstudio\local\api\stream::get_contents_by_ids(
                $this->users->students->five->id, $levelcontentids, true);
        $this->assertFalse($levelcontents);
    }

}
