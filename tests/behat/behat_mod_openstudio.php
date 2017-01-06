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
}
