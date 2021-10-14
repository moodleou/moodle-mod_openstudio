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
 * Steps definitions related with the studio activity.
 *
 * @package    mod_openstudio
 * @category   test
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\util\feature;

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException;
/**
 * Open Studio-related steps definitions.
 *
 * @package    mod_openstudio
 * @category   test
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_openstudio extends behat_base {

    protected $datagenerator;
    protected $plugingenerator;

    protected static $elements = array(
            'instances' => array(
                'datagenerator' => 'instance',
                'required' => array('course'),
                'switchids' => array(
                    'course' => 'course',
                    'defaultvisibility' => 'defaultvisibility',
                    'grouping' => 'groupingid',
                    'tutorroles' => 'tutorroles'
                )
            ),
            'contents' => array(
                'datagenerator' => 'contents',
                'required' => array('name', 'openstudio', 'user'),
                'switchids' => array(
                    'user' => 'userid',
                    'visibility' => 'visibility',
                    'visibilitygroup' => 'visibility',
                    'contenttype' => 'contenttype'
                )
            ),
            'level3contents' => array(
                'datagenerator' => 'contents',
                'required' => array('name', 'openstudio', 'user'),
                'switchids' => array(
                    'user' => 'userid',
                    'visibility' => 'visibility',
                    'visibilitygroup' => 'visibility',
                    'contenttype' => 'contenttype',
                    'level3' => 'levelid',
                    'levelcontainer' => 'levelcontainer'
                )
            ),
            'folders' => array(
                'datagenerator' => 'folders',
                'required' => array('name', 'openstudio', 'user'),
                'switchids' => array(
                    'user' => 'userid',
                    'visibility' => 'visibility',
                    'contenttype' => 'contenttype'
                )
            ),
            'folder contents' => array(
                'datagenerator' => 'folder_contents',
                'required' => array('openstudio', 'folder', 'content', 'user'),
                'switchids' => array(
                    'status' => 'status',
                    'user' => 'userid'
                )
            ),
            'collected set contents' => array(
                'datagenerator' => 'collected_set_contents',
                'required' => array('openstudio', 'set', 'content')
            ),
            'level1s' => array(
                'datagenerator' => 'levels',
                'required' => array('openstudio', 'name', 'sortorder')
            ),
            'level2s' => array(
                'datagenerator' => 'levels',
                'required' => array('name', 'sortorder', 'level1'),
            ),
            'level3s' => array(
                'datagenerator' => 'levels',
                'required' => array('name', 'sortorder', 'level2'),
                'switchids' => array('locktype' => 'locktype', 'contenttype' => 'contenttype')
            ),
            'folder templates' => array(
                'datagenerator' => 'folder_template',
                'required' => array('level3'),
                'switchids' => array('level3' => 'levelid')
            ),
            'folder content templates' => array(
                'datagenerator' => 'folder_content_template',
                'required' => array('level3', 'name'),
                'switchids' => array('level3' => 'levelid', 'levelid' => 'foldertemplateid')
            ),
            'comments' => array(
                'datagenerator' => 'comment',
                'required' => array('openstudio', 'user', 'content', 'comment'),
                'switchids' => array('user' => 'userid')
            )
    );

    private function get_openstudio_by_idnumber($idnumber) {
        global $DB;

        $select = 'SELECT s.*, cm.id as cmid ';
        $from = 'FROM {openstudio} s
                 JOIN {course_modules} cm ON s.id = cm.instance AND s.course = cm.course
                 JOIN {modules} m ON cm.module = m.id ';
        $where = "WHERE m.name = 'openstudio'
                    AND cm.idnumber = ?";
        $studio = $DB->get_record_sql($select . $from . $where, array($idnumber));
        if (!$studio) {
            throw new Exception('There is no studio instance with idnumber ' . $idnumber);
        }
        return $studio;
    }

    /**
     * @Given /^Open Studio test instance is configured for "(?P<studioname_string>(?:[^"]|\\")*)"$/
     */
    public function studio_test_instance_is_configured_for($studioname) {
        global $DB;

        $studio = $DB->get_record('openstudio', array('name' => $studioname));

        $studio->reportingemail = 'teacher1@asd.com';
        $studio->versioning = 99;
        $studio->themefeatures = feature::MODULE;
        $studio->themefeatures += feature::GROUP;
        $studio->themefeatures += feature::STUDIO;
        $studio->themefeatures += feature::PINBOARD;
        $studio->themefeatures += feature::CONTENTUSESFILEUPLOAD;
        $studio->themefeatures += feature::CONTENTCOMMENTUSESAUDIO;
        $studio->themefeatures += feature::ENABLEFOLDERS;
        $studio->themefeatures += feature::ENABLERSS;
        $studio->themefeatures += feature::ENABLESUBSCRIPTION;
        $studio->themefeatures += feature::ENABLEEXPORTIMPORT;
        $studio->themefeatures += feature::CONTENTUSESWEBLINK;
        $studio->themefeatures += feature::CONTENTUSESEMBEDCODE;
        $studio->themefeatures += feature::ENABLELOCK;

        $DB->update_record('openstudio', $studio);
    }

    /**
     * @Given /^Open Studio levels are configured for "(?P<studioname_string>(?:[^"]|\\")*)"$/
     */
    public function studio_levels_are_configured_for($studioname) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/openstudio/locallib.php');

        $studio = $DB->get_record('openstudio', array('name' => $studioname));

        $blocks = array();
        $blockcounter = 1;
        for ($counter = 1; $counter < 5; $counter++) {
            $insertdata = (object) array();
            $insertdata->studioid = $studio->id;
            $insertdata->name = "blockxyz{$counter}";
            $insertdata->sortorder = $counter;
            $blocks[$blockcounter] = mod_openstudio\local\api\levels::create(1, $insertdata);
            $blockcounter++;
        }

        $activities = array();
        $activitycounter = 1;
        foreach ($blocks as $blockid) {
            for ($counter = 1; $counter < 5; $counter++) {
                $insertdata = (object) array();
                $insertdata->parentid = $blockid;
                $insertdata->name = "activityxyz{$counter}";
                $insertdata->sortorder = $counter;
                $activities[$activitycounter] = mod_openstudio\local\api\levels::create(2, $insertdata);
                $activitycounter++;
            }
        }

        $contents = array();
        $contentcounter = 1;
        foreach ($activities as $activityid) {
            for ($counter = 1; $counter < 11; $counter++) {
                $insertdata = (object) array();
                $insertdata->parentid = $activityid;
                $insertdata->name = "contentxyz{$counter}";
                $insertdata->sortorder = $counter;
                $contents[$contentcounter] = mod_openstudio\local\api\levels::create(3, $insertdata);
                $contentcounter++;
            }
        }

        foreach ($activities as $activityid) {
            for ($counter = 11; $counter < 16; $counter++) {
                $insertdata = (object) array();
                $insertdata->parentid = $activityid;
                $insertdata->name = "contentxyz{$counter}";
                $insertdata->required = 1;
                $insertdata->sortorder = $counter;
                $contents[$contentcounter] = mod_openstudio\local\api\levels::create(3, $insertdata);
                $contentcounter++;
            }
        }
    }

    /**
     * @Given /^Open Studio test contents created for
     *  "(?P<studioname_string>(?:[^"]|\\")*)" as "(?P<username_string>(?:[^"]|\\")*)"$/
     */
    public function studio_test_contents_created_for($studioname, $username) {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/mod/openstudio/locallib.php');

        $studio = $DB->get_record('openstudio', array('name' => $studioname));
        $user = $DB->get_record('user', array('username' => $username));
        $group1 = $DB->get_record('groups', array('name' => 'group1'));
        $group2 = $DB->get_record('groups', array('name' => 'group2'));
        $group3 = $DB->get_record('groups', array('name' => 'group3'));

        $levelsql = <<<EOF
SELECT l3.*, l2.name AS l2name, l1.name AS l1name
  FROM {openstudio_level3} l3
  JOIN {openstudio_level2} l2 ON l2.id = l3.level2id AND l2.status >= 0
  JOIN {openstudio_level1} l1 ON l1.id = l2.level1id AND l1.status >= 0 AND l1.openstudioid = ?
 WHERE l3.status >= 0

EOF;

        $results = $DB->get_records_sql($levelsql, array($studio->id));
        if (count($results) > 0) {
            foreach ($results as $leveldata) {
                $contentname = "{$username}:{$leveldata->l1name}:{$leveldata->l2name}:{$leveldata->name}";
                switch ($leveldata->l1name) {
                    case 'blockxyz3':
                        if ($username == 'student1') {
                            $contentvisibility = (0 - $group1->id);
                        } else if ($username == 'student2') {
                            $contentvisibility = (0 - $group2->id);
                        } else if ($username == 'student3') {
                            $contentvisibility = (0 - $group3->id);
                        } else {
                            $contentvisibility = (0 - $group3->id);
                        }
                        break;

                    case 'blockxyz4':
                        $contentvisibility = content::VISIBILITY_PRIVATE;
                        break;

                    default:
                        $contentvisibility = content::VISIBILITY_MODULE;
                        break;
                }
                $data = array(
                    'name' => $contentname,
                    'attachments' => '',
                    'embedcode' => '',
                    'weblink' => 'http://www.open.ac.uk/',
                    'urltitle' => random_string(),
                    'visibility' => $contentvisibility,
                    'description' => random_string(),
                    'ownership' => 0,
                    'sid' => 0
                );
                $contentid = content::create($studio->id, $user->id, 3, $leveldata->id, $data);

                // We hack the database record to artificially fudge the data to allow us to test different scenarios.
                if ($contentid !== false) {
                    switch ($leveldata->name) {
                        case 'contentxyz1':
                            $DB->set_field('openstudio_contents', 'contenttype', content::TTYPE_IMAGE, array('id' => $contentid));
                            flags::toggle($contentid, flags::FAVOURITE, 'on', $user->id);
                            break;

                        case 'contentxyz2':
                            $DB->set_field('openstudio_contents', 'contenttype', content::TYPE_VIDEO, array('id' => $contentid));
                            flags::toggle($contentid, flags::NEEDHELP, 'on', $user->id);
                            break;

                        case 'contentxyz3':
                            $DB->set_field('openstudio_contents', 'contenttype', content::TYPE_AUDIO, array('id' => $contentid));
                            flags::toggle($contentid, flags::MADEMELAUGH, 'on', $user->id);
                            break;

                        case 'contentxyz4':
                            $DB->set_field('openstudio_contents', 'contenttype', content::TYPE_DOCUMENT, array('id' => $contentid));
                            flags::toggle($contentid, flags::INSPIREDME, 'on', $user->id);
                            break;

                        case 'contentxyz5':
                            $DB->set_field('openstudio_contents', 'contenttype', content::TYPE_PRESENTATION,
                                array('id' => $contentid));
                            mod_openstudio\local\api\comments::create($contentid, $user->id, 'Fire and Blood');
                            break;

                        case 'contentxyz6':
                            $DB->set_field('openstudio_contents', 'contenttype', content::TYPE_SPREADSHEET,
                                array('id' => $contentid));
                            break;

                        case 'contentxyz7':
                            $DB->set_field('openstudio_contents', 'contenttype', content::TYPE_TEXT, array('id' => $contentid));
                            break;
                    }
                }
            }
        }
    }

    /**
     * @Given /^I go to content edit view$/
     */
    public function openstudio_go_to_contentedit() {
         $url = str_replace('content', 'contentedit', $this->getSession()->getCurrentUrl());
         $this->getSession()->visit($url);
    }

    /**
     * @Given /^all users have accepted the plagarism statement for "(?P<openstudioname_string>(?:[^"]|\\")*)" openstudio$/
     * @param $openstudioidnumber
     */
    public function all_users_have_accepted_plagarism_for($openstudioidnumber) {
        global $DB;
        $users = $DB->get_records('user');
        $openstudio = $this->get_openstudio_by_idnumber($openstudioidnumber);
        if (!$openstudio) {
            throw new ExpectationException('Open Studio idnumber provided does not exist', $this->getSession());
        }
        foreach ($users as $user) {
            $honestycheck = (object) array(
                'openstudioid' => $openstudio->cmid,
                'userid' => $user->id,
                'timemodified' => time()
            );
            $DB->insert_record('openstudio_honesty_checks', $honestycheck, true, true);
        }
    }

    /**
     * @Given /^the following open studio "(?P<slottype_string>(?:[^"]|\\")*)" exist:$/
     * @param string $elementname "folders" or "contents"
     * @param TableNode $data
     */
    public function the_following_studio_exist($elementname, TableNode $data) {
        // Now that we need them require the data generators.
        require_once(__DIR__ . '/../generator/lib.php');

        if (empty(self::$elements[$elementname])) {
            throw new ExpectationException($elementname . ' data generator is not implemented', $this->getSession());
        }

        $this->datagenerator = testing_util::get_data_generator();
        $this->plugingenerator = $this->datagenerator->get_plugin_generator('mod_openstudio');

        $elementdatagenerator = self::$elements[$elementname]['datagenerator'];
        $requiredfields = self::$elements[$elementname]['required'];
        if (!empty(self::$elements[$elementname]['switchids'])) {
            $switchids = self::$elements[$elementname]['switchids'];
        }

        foreach ($data->getHash() as $elementdata) {

            // Check if all the required fields are there.
            foreach ($requiredfields as $requiredfield) {
                if (!isset($elementdata[$requiredfield])) {
                    throw new Exception($elementname . ' requires the field ' . $requiredfield . ' to be specified');
                }
            }

            // Switch from human-friendly references to ids.
            if (isset($switchids)) {
                foreach ($switchids as $element => $field) {
                    $methodname = 'get_' . $element . '_id';

                    // Not all the switch fields are required, default vars will be assigned by data generators.
                    if (isset($elementdata[$element])) {
                        // Temp $id var to avoid problems when $element == $field.
                        $id = $this->{$methodname}($elementdata[$element]);
                        unset($elementdata[$element]);
                        $elementdata[$field] = $id;
                    }
                }
            }

            // Preprocess the entities that requires a special treatment.
            if (method_exists($this, 'preprocess_' . $elementdatagenerator)) {
                $elementdata = $this->{'preprocess_' . $elementdatagenerator}($elementdata);
            }

            // Creates element.
            $methodname = 'create_' . $elementdatagenerator;
            if (method_exists($this->plugingenerator, $methodname)) {
                // Using data generators directly.
                $iditem = $this->plugingenerator->{$methodname}($elementdata);
                if (!empty($elementdata['index'])) {
                    // Get areaname.
                    switch ($elementdatagenerator) {
                        // Contents have areaname is posts.
                        case 'contents':
                            $areaname = 'posts';
                            break;
                        // Comment have areaname is comments.
                        case 'comment':
                            $areaname = 'comments';
                            break;
                        default:
                            $areaname = $elementdatagenerator;
                    }
                    // Create mock document for global search.
                    $openstudio = $this->plugingenerator->get_studio_by_idnumber($elementdata['openstudio']);
                    $this->create_mock_index_data($openstudio, $iditem, $elementdata['keyword'], $areaname,
                        $elementdata['keyword'], $openstudio->intro);
                }

            } else if (method_exists($this, 'process_' . $elementdatagenerator)) {
                // Using an alternative to the direct data generator call.
                $this->{'process_' . $elementdatagenerator}($elementdata);
            } else {
                throw new ExpectationException($elementname . ' data generator is not implemented', $this->getSession());
            }
        }
    }

    /**
     * Create mock document for global search.
     *
     * @param $openstudio
     * @param $itemid
     * @param $query
     * @param $areaname
     * @param $title
     * @param $content
     * @throws \moodle_exception
     */
    protected function create_mock_index_data($openstudio, $itemid, $query, $areaname, $title, $content) {
        // Data stub.
        $fakedata = new \stdClass();
        $fakedata->query = $query;
        $fakedata->results = [];

        // Get existing data if exists.
        if ($existing = get_config('core_search', 'behat_fakeresult')) {
            $existing = json_decode($existing);
        }

        if (!$existing || $existing->query != $fakedata->query) {
            // New or existing data doesn't have this query so start new data (can setup 1 query).
            $existing = $fakedata;
        }

        $resultdata = new \stdClass();
        $resultdata->itemid = $itemid;
        $resultdata->componentname = 'mod_openstudio';
        $resultdata->areaname = $areaname;
        $resultdata->fields = new \stdClass();

        $resultdata->fields->contextid = \context_module::instance($openstudio->cmid)->id;
        $resultdata->fields->courseid = $openstudio->course;
        $resultdata->fields->title = $title;
        $resultdata->fields->content = $content;
        $resultdata->fields->modified =  time();
        $resultdata->extrafields = new \stdClass();
        $resultdata->extrafields->coursefullname = $openstudio->sitename;

        $existing->results[] = $resultdata;

        set_config('behat_fakeresult', json_encode($existing), 'core_search');
    }

    /**
     * Check level exist and get level 1/2 ID.
     * @throws Exception
     * @param TableNode $leveldata
     * @return TableNode
     */
    protected function preprocess_levels($leveldata) {
        global $DB;

        if (isset($leveldata['level1'])) {
            $leveldata['level'] = 2;
            try {
                if (!$leveldata['parentid'] = $DB->get_field('openstudio_level1', 'id', array('name' => $leveldata['level1']))) {
                    throw new Exception('The specified level1 with name "' . $leveldata['level1'] . '" does not exist');
                }
            } catch (dml_multiple_records_exception $e) {
                throw new exception('more than one level1 was found with the name ' . $leveldata['level1']
                        .'. For simplicity, please use unique level names in behat tests.');
            }
        } else if (isset($leveldata['level2'])) {
            $leveldata['level'] = 3;
            try {
                if (!$leveldata['parentid'] = $DB->get_field('openstudio_level2', 'id', array('name' => $leveldata['level2']))) {
                    throw new Exception('The specified level2 with name "' . $leveldata['level2'] . '" does not exist');
                }
            } catch (dml_multiple_records_exception $e) {
                throw new exception('more than one level1 was found with the name ' . $leveldata['level2']
                        .'. For simplicity, please use unique level names in behat tests.');
            }

            if (isset($leveldata['lockprocessed'])) {
                if (empty($leveldata['lockprocessed'])) {
                    unset($leveldata['lockprocessed']);
                } else {
                    $leveldata['lockprocessed'] = strtotime($leveldata['lockprocessed']);
                }
            }
            if (isset($leveldata['unlocktime'])) {
                if (empty($leveldata['unlocktime'])) {
                    unset($leveldata['unlocktime']);
                } else {
                    $leveldata['unlocktime'] = strtotime($leveldata['unlocktime']);
                }
            }
            if (isset($leveldata['locktime'])) {
                if (empty($leveldata['locktime'])) {
                    unset($leveldata['locktime']);
                } else {
                    $leveldata['locktime'] = strtotime($leveldata['locktime']);
                }
            }
        } else {
            $leveldata['level'] = 1;
        }
        return $leveldata;
    }

    /**
     * Gets the lock type constant from lock type name.
     * @throws Exception
     * @param string $locktype
     * @return int
     */
    protected function get_locktype_id($locktype) {
        if (empty($locktype)) {
            return null;
        }
        $constantname = lock::class . '::'. strtoupper($locktype);
        if (!defined($constantname)) {
            throw new Exception('The lock type constant "' . $constantname . '" does not exist');
        }
        return constant($constantname);
    }

    /**
     * Gets the content type constant from it's name.
     * @throws Exception
     * @param string $contenttype
     * @return int
     */
    protected function get_contenttype_id ($contenttype) {
        $constantname = content::class.'::TYPE_' . strtoupper($contenttype);
        if (!defined($constantname)) {
            throw new Exception('The content type constant "' . $constantname . '" does not exist');
        }
        return constant($constantname);
    }

    /**
     * Gets the user id from it's username.
     * @throws Exception
     * @param string $username
     * @return int
     */
    protected function get_user_id($username) {
        global $DB;

        if (!$id = $DB->get_field('user', 'id', array('username' => $username))) {
            throw new Exception('The specified user with username "' . $username . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the visibility id from visibility id.
     * @throws Exception
     * @param string $visibility
     * @return int
     */
    protected function get_visibility_id($visibility) {
        $constantname = content::class.'::VISIBILITY_' . strtoupper(trim($visibility));
        if (!defined($constantname)) {
            throw new Exception('The visibility constant "' . $constantname . '" does not exist');
        }
        return constant($constantname);
    }

    /**
     * Gets the visibility group ID from group name.
     * @throws Exception
     * @param string $group
     * @return int
     */
    protected function get_visibilitygroup_id($group) {
        global $DB;
        if (!$id = $DB->get_field('groups', 'id', array('idnumber' => $group))) {
            throw new Exception('The group with idnumber "' . $group . '" does not exist');
        }
        return (0 - $id);
    }

    /**
     * Follows the open studio redirection
     *
     * @Given /^I follow "(?P<link_string>(?:[^"]|\\")*)" in the openstudio navigation$/
     * @throws Exception
     * @param string $link
     */
    public function go_to_openstudio_navigation($link) {
        $parentnodes = array_map('trim', explode('>', $link));
        $subitem = strtolower(str_replace(' ', '-', $parentnodes[0]));
        if (isset($parentnodes[1])) {
            $selector = "ul > li.dropdown.".$subitem." > a > span.openstudio-navigation-text";
            $menulink = $this->getSession()->getPage()->find("css", $selector);
            $menulink->click();
            $linknode = $this->getSession()->getPage()->find('xpath', '//div[@class="openstudio-nav-primary"]//a[contains(translate(' .
                    'normalize-space(.), "abcdefghijklmnopqrstuvwxyz", "ABCDEFGHIJKLMNOPQRSTUVWXYZ"),"' .
                    strtoupper($parentnodes[1]) . '")]');
            if (empty($linknode)) {
                // Fallback to find link using less precise method.
                $linknode = $this->find_link($parentnodes[1]);
            }
            $this->ensure_node_is_visible($linknode);
            $linknode->click();
        } else {
            $selector = "li.".$subitem." > a > span.openstudio-navigation-text";
            $this->getSession()->getPage()->find("css", $selector)->click();
        }
    }

    /**
     * Follows the first content in my activity
     *
     * @Given /^I follow the first content in my activity$/
     * @throws Exception
     */
    public function follow_first_content_in_my_activity() {
        $this->getSession()->getPage()->find(
                'css', '.openstudio-grid-item:first-child div.openstudio-grid-item-content-preview > a > img')->click();
    }

    /**
     * Gets the course id from it's shortname.
     *
     * @throws Exception
     * @param string $shortname Course short name
     * @return int
     */
    protected function get_course_id($shortname) {
        global $DB;

        if (!$id = $DB->get_field('course', 'id', array('shortname' => $shortname))) {
            throw new Exception('The specified course with shortname "' . $shortname . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the tutor role id from it's shortname.
     *
     * @throws Exception
     * @param string $tutorroles Tutor role's short names
     * @return string
     */
    protected function get_tutorroles_id($tutorroles) {
        global $DB;
        $shortnames = array_filter(explode(',', $tutorroles));
        $ids = array();
        foreach ($shortnames as $shortname) {
            if (!$ids[] = $DB->get_field('role', 'id', array('shortname' => $shortname))) {
                throw new Exception('The role with shortname "' . $shortname . '" does not exist');
            }
        }
        return implode(',', $ids);
    }

    /**
     * Get level3 ID from it's name.
     * @param string $level3name
     * @return int
     * @throws Exception
     */
    protected function get_level3_id($level3name) {
        global $DB;

        try {
            if (!$id = $DB->get_field('openstudio_level3', 'id', array('name' => $level3name))) {
                throw new Exception('The specified course with shortname "' . $level3name . '" does not exist');
            }
        } catch (dml_multiple_records_exception $e) {
            throw new Exception('More than one level3 was found with the name ' . $level3name
                    .'. For simplicity, please use unique level names in behat tests.');
        }
        return $id;
    }

    /**
     * Get level ID from level3 ID.
     * @param $level3id
     * @return int
     * @throws Exception
     */
    protected function get_levelid_id($level3id) {
        global $DB;

        if (!$id = $DB->get_field('openstudio_folder_templates', 'id', array('levelid' => $level3id))) {
            throw new Exception('The set template for level "' . $level3id . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the levelcontainer id from levelcontainer id.
     * @throws Exception
     * @param string $visibility
     * @return int
     */
    protected function get_levelcontainer_id($visibility) {
        $constantname = content::class . '::VISIBILITY_' . strtoupper(trim($visibility));
        if (!defined($constantname)) {
            throw new Exception('The visibility constant "' . $constantname . '" does not exist');
        }
        return constant($constantname);
    }

    /**
     * Get the ID of a grouping from the idnumber
     *
     * @param string $grouping ID number of the grouping
     * @return int
     * @throws Exception
     */
    protected function get_grouping_id($grouping) {
        global $DB;

        if (!$id = $DB->get_field('groupings', 'id', array('idnumber' => $grouping))) {
            throw new Exception('The grouping with idnumber "' . $grouping . '" does not exist');
        }
        return $id;
    }

    /**
     * Set the follow flag for a user on a content post.
     *
     * @Given /^"(?P<username_string>(?:[^"]|\\")*)" will recieve notifications for openstudio content "(?P<contentname_string>(?:[^"]|\\")*)"$/
     *
     * @param $username
     * @param $contentname
     */
    public function user_will_get_notifications_for_content($username, $contentname) {
        $userid = $this->get_user_id($username);
        $contentid = $this->get_content_id($contentname);
        mod_openstudio\local\api\flags::toggle($contentid, mod_openstudio\local\api\flags::FOLLOW_CONTENT, 'on', $userid);
    }

    /**
     * Get the content ID for a comment based on the name.
     *
     * @param $commentdata
     * @return mixed
     * @throws Exception
     */
    protected function preprocess_comment($commentdata) {
        global $DB;
        $studio = $this->get_openstudio_by_idnumber($commentdata['openstudio']);
        $content = $DB->get_record('openstudio_contents', ['name' => $commentdata['content'], 'openstudioid' => $studio->id]);
        if (!$content) {
            throw new Exception('There is no content post called ' . $commentdata['content'] . ' in this studio.');
        }
        $commentdata['contentid'] = $content->id;
        return $commentdata;
    }

    /**
     * Get the ID of a content post by its name
     *
     * This requires that all posts in the test have unique names.
     *
     * @param string $name
     * @return int
     * @throws Exception
     */
    protected function get_content_id($name) {
        global $DB;

        try {
            if (!$id = $DB->get_field('openstudio_contents', 'id', array('name' => $name))) {
                throw new Exception('The content with name "' . $name . '" does not exist');
            }
        } catch (dml_multiple_records_exception $e) {
            throw new Exception('More than one content record was found with the name ' . $name
                    .'. For simplicity, please use unique content names in behat tests.');
        }
        return $id;
    }

    /**
     * Set the follow flag for a user on a comment.
     *
     * @Given /^"(?P<username_string>(?:[^"]|\\")*)" will recieve notifications for openstudio comment "(?P<comment_string>(?:[^"]|\\")*)"$/
     *
     * @param $username
     * @param $commenttext
     */
    public function user_will_get_notifications_for_comment($username, $commenttext) {
        $userid = $this->get_user_id($username);
        $comment = $this->get_comment($commenttext);
        mod_openstudio\local\api\flags::comment_toggle($comment->contentid, $comment->id, $userid, 'on', false,
                mod_openstudio\local\api\flags::FOLLOW_CONTENT);
    }

    /**
     * Get the record for a comment by its text.
     *
     * This requires that all comment in the test have unique text.
     *
     * @param string $commenttext
     * @return object
     * @throws Exception
     */
    protected function get_comment($commenttext) {
        global $DB;

        try {
            $where = $DB->sql_compare_text('commenttext') . ' = ?';
            if (!$comment = $DB->get_record_select('openstudio_comments', $where, [$commenttext])) {
                throw new Exception('The content with name "' . $commenttext . '" does not exist');
            }
        } catch (dml_multiple_records_exception $e) {
            throw new Exception('More than one comment record was found with the text ' . $commenttext
                    .'. For simplicity, please use unique comment text in behat tests.');
        }
        return $comment;
    }

    /**
     * Get the ID for a comment by its text.
     *
     * This requires that all comment in the test have unique text.
     *
     * @param string $commenttext
     * @return int
     * @throws Exception
     */
    protected function get_comment_id($commenttext) {
        return $this->get_comment($commenttext)->id;
    }

    /**
     * Checks that contents on the Re-order contents screen are displayed in the provided order
     * A table with | title | fixed | filled | is expected.  The second and third columns are
     * optional, and should contain "true" or "false".
     *
     * @Then /^Open studio contents should be in the following order:$/
     */
    public function openstudio_slots_should_be_in_the_following_order(TableNode $slots) {
        global $CFG, $DB;

        // Optional heading row.
        $firstrow = $slots->getRow(0);
        if (!in_array('title', $firstrow)) {
            $headings = array_slice(array('title', 'fixed', 'filled'), 0, count($firstrow));
            $rows = $slots->getRows();
            array_unshift($rows, $headings);
            $slots = new TableNode($rows);
        }

        $slotscopy = $slots->getHash();
        foreach ($slots->getHash() as $key => $slot) {
            $title = (string) $slot['title'];
            $currentxpath = "//a[contains(., '" . $title . "')]";

            if (isset($slotscopy[$key + 1])) {
                $nextslot = $slotscopy[$key + 1];
                $nextxpath = "//a[contains(., '" . $nextslot['title'] . "')]";

                $this->execute('behat_general::should_appear_before',
                        array($currentxpath, 'xpath_element', $nextxpath, 'xpath_element'));
            }

            if (isset($set['fixed'])) {
                $fixed = strtolower($slot['fixed']) == 'true';
                if ($slot['fixed']) {
                    $not = '';
                } else {
                    $not = ' not_';
                }

                $this->execute('behat_general::should_' . $not . 'exist_in_the',
                        array('//img[@alt=\'Order fixed\']', 'xpath_element', $currentxpath . '/../../', 'xpath_element'));
            }

            if (isset($slot['filled'])) {
                $filled = strtolower($slot['filled']) == 'true';
                if ($filled) {
                    $enabled = 'enabled';
                } else {
                    $enabled = 'disabled';
                }
                $xpath = sprintf("%s/../..//input[contains(@class, 'studio-set-reorder-input')]",
                        $currentxpath);

                $this->execute('behat_general::the_element_should_be_' . $enabled,
                        array($xpath, 'xpath_element'));
            }
        }
    }

    /**
     * Submits the openstudio search form containing the specified element
     *
     * @When /^I submit the openstudio search form "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function submit_form($element, $selectortype)
    {
        $node = $this->get_selected_node($selectortype, $element);
        $node->submit();
    }

    /**
     * Selects the named post in the browse posts dialogue which appears when selecting existing posts to add to a folder
     *
     * @When /^I select the existing openstudio post "(?P<title_string>(?:[^"]|\\")*)"$/
     * @param string $title Title of the post
     */
    public function select_post_to_add_to_folder($title)
    {
        list($selector, $locator) = $this->transform_selector('text', $title);
        $fieldnode = $this->find($selector, $locator);
        $selectbutton = $fieldnode->getParent()->getParent()->find('css', '.openstudio-folder-select-post-btn');
        $selectbutton->click();
    }

    /**
     * Checks that breadcrumbs are as expected.
     *
     * @param string $breadcrumbs Breadcrumbs separated by >
     * @throws ExpectationException
     * @Given /^the openstudio breadcrumbs should be "(?P<breadcrumbs_string>(?:[^"]|\\")*)"$/
     */
    public function the_breadcrumbs_should_be($breadcrumbs) {
        $actual = $this->getSession()->evaluateScript(
                'return (function() {'.
                'var lis = document.getElementById("page-navbar").getElementsByTagName("li");' .
                'var result = "";' .
                'for(var i = 0 ; i < lis.length; i++) {' .
                'if (i) {' .
                'result += " > ";' .
                '}' .
                'result += lis[i].textContent || lis[i].innerText;' .
                '}' .
                'return result;' .
                '})()');
        $expected = trim(preg_replace('~\s+~', ' ', $breadcrumbs));
        $actual = trim(preg_replace('~\s+~', ' ', $actual));
        if ($expected !== $actual) {
            throw new ExpectationException('Breadcrumbs are "' . $actual .
                    '" (expected "' . $expected . '")', $this->getSession());
        }
    }
}
