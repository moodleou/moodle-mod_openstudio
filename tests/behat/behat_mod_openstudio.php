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
            'sets' => array(
                'datagenerator' => 'sets',
                'required' => array('name', 'openstudio', 'user'),
                'switchids' => array('user' => 'userid', 'visibility' => 'visibility', 'contenttype' => 'contenttype')
            ),
            'set contents' => array(
                'datagenerator' => 'set_contents',
                'required' => array('openstudio', 'set', 'content', 'user'),
                'switchids' => array('status' => 'status', 'provenancestatus' => 'provenancestatus', 'user' => 'userid')
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
            'set templates' => array(
                'datagenerator' => 'set_template',
                'required' => array('level3'),
                'switchids' => array('level3' => 'levelid')
            ),
            'set content templates' => array(
                'datagenerator' => 'set_content_template',
                'required' => array('level3', 'name'),
                'switchids' => array('level3' => 'levelid', 'levelid' => 'settemplateid')
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
        $studio->themefeatures = STUDIO_FEATURE_MODULE;
        $studio->themefeatures += STUDIO_FEATURE_GROUP;
        $studio->themefeatures += STUDIO_FEATURE_STUDIO;
        $studio->themefeatures += STUDIO_FEATURE_PINBOARD;
        $studio->themefeatures += STUDIO_FEATURE_SLOTUSESFILEUPLOAD;
        $studio->themefeatures += STUDIO_FEATURE_SLOTCOMMENTUSESAUDIO;
        $studio->themefeatures += STUDIO_FEATURE_ENABLECOLLECTIONS;
        $studio->themefeatures += STUDIO_FEATURE_ENABLERSS;
        $studio->themefeatures += STUDIO_FEATURE_ENABLESUBSCRIPTION;
        $studio->themefeatures += STUDIO_FEATURE_ENABLEEXPORTIMPORT;
        $studio->themefeatures += STUDIO_FEATURE_SLOTUSESWEBLINK;
        $studio->themefeatures += STUDIO_FEATURE_SLOTUSESEMBEDCODE;
        $studio->themefeatures += STUDIO_FEATURE_ENABLELOCK;

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
                            studio_api_flags_toggle($contentid, flags::FAVOURITE, 'on', $user->id);
                            break;

                        case 'contentxyz2':
                            $DB->set_field('openstudio_contents', 'contenttype', content::TYPE_VIDEO, array('id' => $contentid));
                            studio_api_flags_toggle($contentid, flags::NEEDHELP, 'on', $user->id);
                            break;

                        case 'contentxyz3':
                            $DB->set_field('openstudio_contents', 'contenttype', content::TYPE_AUDIO, array('id' => $contentid));
                            studio_api_flags_toggle($contentid, flags::MADEMELAUGH, 'on', $user->id);
                            break;

                        case 'contentxyz4':
                            $DB->set_field('openstudio_contents', 'contenttype', content::TYPE_DOCUMENT, array('id' => $contentid));
                            studio_api_flags_toggle($contentid, flags::INSPIREDME, 'on', $user->id);
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
            throw new ExpectationException($elementname . ' data generator is not implemented');
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
                $this->plugingenerator->{$methodname}($elementdata);

            } else if (method_exists($this, 'process_' . $elementdatagenerator)) {
                // Using an alternative to the direct data generator call.
                $this->{'process_' . $elementdatagenerator}($elementdata);
            } else {
                throw new ExpectationException($elementname . ' data generator is not implemented');
            }
        }
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
}
