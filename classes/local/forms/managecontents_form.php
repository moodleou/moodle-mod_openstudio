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
 * Open Studio manage content structure form.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\forms;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\levels;
use mod_openstudio\local\api\lock;

class managecontents_form extends \moodleform {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $context = \context_module::instance($this->_customdata['id']);
        $contextinfo = get_context_info_array($context->id);
        $studioid = $contextinfo[2]->instance;
        $setsorcollections = $this->_customdata['setsorcollections'];
        if ($setsorcollections !== false) {
            $collectionlabel = get_string('typeisfolder', 'openstudio');
        }
        if (has_capability('mod/openstudio:deletelevels', $context)) {
            $sdcontents = levels::get_records(3, $this->_customdata['l2id'], true, '',
                levels::SOFT_DELETED);
            if (count($sdcontents) > 0) {
                $mform->addElement('header', 'softdeletedcontentsheader',
                    get_string('softdeletedcontentsheader', 'openstudio'));
                $mform->addHelpButton('softdeletedcontentsheader', 'softdeletedcontentsheader', 'openstudio');
                $mform->setExpanded('softdeletedcontentsheader', false);
            }
            $counter = 0;
            foreach ((array)$sdcontents as $content) {
                $counter++;
                $contentname = array();
                $contentsincontents = 0;

                // We have to run this here each time as moodle does not support reset() or rewind() for iterators.
                $allcontentsinstudio = content::get_all_records($studioid);
                // Get used content it for URL.
                if ($allcontentsinstudio) {
                    $contentsincontents = levels::count_contents_in_level(3, $content->id, $allcontentsinstudio);
                }
                $contentname[] = $mform->createElement('static', 'contentname[' . $content->id . ']', '', $content->name);
                if ($contentsincontents > 0) {
                    $contenturl = new \moodle_url('/mod/openstudio/search.php', array('id' => $this->_customdata['id']));
                    $parentactivity = levels::get_record(2, $this->_customdata['l2id']);
                    $parentblock = levels::get_record(1, $this->_customdata['l1id']);
                    $searchstring = '&searchtext='
                        . urlencode('"' . $parentblock->name . '" ' . '"' . $parentactivity->name
                            . '" ' . '"' . $content->name . '"');
                    $contentname[] = $mform->createElement('static', 'contentcount[' . $content->id . ']', '',
                        "(". \html_writer::link($contenturl->out(false).$searchstring,
                            get_string('viewcontent', 'openstudio').")"));
                }
                $contentname[] = $mform->createElement('image', 'reinstatebutton[' . $content->id . ']',
                    $OUTPUT->image_url('undo', 'openstudio'), array('title' => get_string('undeletelevel', 'openstudio')));
                $mform->addGroup($contentname, null, $counter . '. ', ' ', false);
            }
        }

        $contents = levels::get_records(3, $this->_customdata['l2id']);
        if ($setsorcollections === false) {
            $contentsheader = get_string('activecontentsheader', 'openstudio');
        } else {
            $contentsheader = get_string('activecontentsfoldersheader', 'openstudio');
        }
        $mform->addElement('header', 'activecontentsheader', $contentsheader);
        $mform->addHelpButton('activecontentsheader', 'activecontentsheader', 'openstudio');
        $total = count($contents);
        $counter = 0;
        foreach ((array)$contents as $content) {

            $counter++;
            $contentname = array();
            $contentsincontents = 0;
            $smallicon = 'icon smallicon';
            // We have to run this here each time as moodle does not support reset() or rewind() for iterators.
            $allcontentsinstudio = content::get_all_records($studioid);
            // Get used content it for URL.
            if ($allcontentsinstudio) {
                $contentsincontents = levels::count_contents_in_level(3, $content->id, $allcontentsinstudio);
            }

            // Create URL to drill down.
            if (isset($this->_customdata['editcontent']) && $this->_customdata['editcontent'] == $content->id) {
                $mform->addElement('hidden', 'editcontentid', $content->id);
                $mform->setType('editcontentid', PARAM_INT);
                $formelementedit = $mform->createElement('text', 'name');
                $formelementedit->setType('name', PARAM_TEXT);
                $contentname[] = $formelementedit;

                $formelementrequired = $mform->createElement('advcheckbox',
                    'required', '', get_string('requiredfortma', 'openstudio'));
                $contentname[] = $formelementrequired;

                if ($setsorcollections !== false) {
                    $formelementiscollection = $mform->createElement('advcheckbox',
                        'iscollection', '', $collectionlabel);
                    $contentname[] = $formelementiscollection;
                }
            } else {
                $setorcollection = '';
                if ($content->contenttype == content::TYPE_FOLDER) {
                    $urlparams = array(
                        'id' => $context->instanceid,
                        'l1id' => $this->_customdata['l1id'],
                        'l2id' => $this->_customdata['l2id'],
                        'l3id' => $content->id
                    );
                    $url = new \moodle_url('/mod/openstudio/managefolders.php', $urlparams);
                    $content->name = \html_writer::link($url, $content->name,
                        array('title' => get_string('configurefolder', 'openstudio')));
                    $setorcollection = ' (' . get_string('reportcontenttypename100', 'openstudio') . ')';
                }

                $contentname[] = $mform->createElement('static', 'contentname[' . $content->id . ']', '',
                    $content->name.$setorcollection);
            }

            if ($total > 1) {
                if ($counter == 1) {
                    $contentname[] = $mform->createElement('image', 'movednbutton[' . $content->id . ']',
                        $OUTPUT->image_url('t/down'), ['title' => get_string('movedown', 'openstudio'), 'class' => $smallicon]);
                } else if ($counter == $total) {
                    $contentname[] = $mform->createElement('image', 'moveupbutton[' . $content->id . ']',
                        $OUTPUT->image_url('t/up'), ['title' => get_string('moveup', 'openstudio'), 'class' => $smallicon]);
                } else {
                    $contentname[] = $mform->createElement('image', 'movednbutton[' . $content->id . ']',
                        $OUTPUT->image_url('t/down'), ['title' => get_string('movedown', 'openstudio'), 'class' => $smallicon]);
                    $contentname[] = $mform->createElement('image', 'moveupbutton[' . $content->id . ']',
                        $OUTPUT->image_url('t/up'), ['title' => get_string('moveup', 'openstudio'), 'class' => $smallicon]);
                }
            }

            $contentname[] = $mform->createElement('image', 'editbutton[' . $content->id . ']',
                $OUTPUT->image_url('t/edit'), ['title' => get_string('editlevel', 'openstudio'), 'class' => $smallicon]);

            if ($contentsincontents > 0) {
                $contenturl = new \moodle_url('/mod/openstudio/search.php', array('id' => $this->_customdata['id']));
                $parentactivity = levels::get_record(2, $this->_customdata['l2id']);
                $parentblock = levels::get_record(1, $this->_customdata['l1id']);
                $searchstring = '&searchtext='
                    . urlencode('"' . $parentblock->name . '" ' . '"' . $parentactivity->name
                        . '" ' . '"' . $content->name . '"');
                $contentname[] = $mform->createElement('static', 'contentcount[' . $content->id . ']', '',
                    "(". \html_writer::link($contenturl->out(false).$searchstring, get_string('viewcontent', 'openstudio') .")"));
                if (has_capability('mod/openstudio:deletelevels', $context)) {
                    $contentname[] = $mform->createElement('image', 'deletebutton[' . $content->id . ']',
                        $OUTPUT->image_url('t/delete'), ['title' => get_string('deletelevel', 'openstudio'), 'class' => $smallicon]);
                }
            } else {
                $contentname[] = $mform->createElement('image', 'deletebutton[' . $content->id . ']',
                    $OUTPUT->image_url('t/delete'), ['title' => get_string('deletelevel', 'openstudio'), 'class' => $smallicon]);
            }
            $mform->addGroup($contentname, null, $counter . '. ', ' ', false);

            if (isset($this->_customdata['editcontent']) && $this->_customdata['editcontent'] == $content->id) {
                $defaulttime = time();
                // This shouldn't really be necessary, we should just be able to set the time using
                // set_data(). However, for some reason this moodleform wont accept values for the
                // locktime and unlocktime fields, so this does the job.
                if (array_key_exists('lockenabled', $this->_customdata)) {
                    $defaulttime = array();
                    $defaulttime['enabled'] = $this->_customdata['lockenabled'];
                    if (array_key_exists('lockday', $this->_customdata)) {
                        $defaulttime['day'] = $this->_customdata['lockday'];
                    }
                    if (array_key_exists('lockmonth', $this->_customdata)) {
                        $defaulttime['month'] = $this->_customdata['lockmonth'];
                    }
                    if (array_key_exists('lockyear', $this->_customdata)) {
                        $defaulttime['year'] = $this->_customdata['lockyear'];
                    }
                    if (array_key_exists('lockhour', $this->_customdata)) {
                        $defaulttime['hour'] = $this->_customdata['lockhour'];
                    }
                    if (array_key_exists('lockminute', $this->_customdata)) {
                        $defaulttime['minute'] = $this->_customdata['lockminute'];
                    }
                }
                // Apply Lock on content from date.
                $mform->addElement('date_time_selector', 'locktime',
                    get_string('lockdate', 'openstudio'), array('optional' => true, 'defaulttime' => $defaulttime));

                // Adding the "locktype" selector field of the content.
                $options = array(
                    lock::ALL => get_string('locktypename1', 'openstudio'),
                    lock::CRUD => get_string('locktypename2', 'openstudio'),
                    lock::SOCIAL => get_string('locktypename4', 'openstudio'),
                    lock::COMMENT => get_string('locktypename8', 'openstudio'));
                $mform->addElement('select', 'locktype', get_string('locktype', 'openstudio'), $options);
                $mform->addHelpButton('locktype', 'locktype', 'openstudio');
                $mform->disabledIf('locktype', 'locktime[enabled]', 'notchecked', 1);

                $defaulttime = time();
                if (array_key_exists('unlockenabled', $this->_customdata)) {
                    $defaulttime = array();
                    $defaulttime['enabled'] = $this->_customdata['unlockenabled'];
                    if (array_key_exists('unlockday', $this->_customdata)) {
                        $defaulttime['day'] = $this->_customdata['unlockday'];
                    }
                    if (array_key_exists('unlockmonth', $this->_customdata)) {
                        $defaulttime['month'] = $this->_customdata['unlockmonth'];
                    }
                    if (array_key_exists('unlockyear', $this->_customdata)) {
                        $defaulttime['year'] = $this->_customdata['unlockyear'];
                    }
                    if (array_key_exists('unlockhour', $this->_customdata)) {
                        $defaulttime['hour'] = $this->_customdata['unlockhour'];
                    }
                    if (array_key_exists('unlockminute', $this->_customdata)) {
                        $defaulttime['minute'] = $this->_customdata['unlockminute'];
                    }
                }
                // Unlock content from date.
                $mform->addElement('date_time_selector', 'unlocktime',
                    get_string('unlockdate', 'openstudio'), array('optional' => true, 'defaulttime' => $defaulttime));

                if (isset($content->lockprocessed) && $content->lockprocessed > 0) {
                    // Display only - Date Lock was last processed.
                    $mform->addElement('static', 'lastprocesseddate', get_string('lastprocesseddate', 'openstudio'),
                        userdate($content->lockprocessed));
                    $mform->setType('lastprocesseddate', PARAM_TEXT);
                }

                // Reapply schedule. Currently not being used.
                $mform->addElement('hidden', 'reapplyschedule', 0);
                $mform->setType('reapplyschedule', PARAM_INT);

                // Add a little space between contents.
                $mform->addElement('static', 'emptytype', get_string('emptytype', 'openstudio'));
            }

        }
        $mform->addElement('hidden', 'numberofcontents', $total);
        $mform->setType('numberofcontents', PARAM_INT);

        // Add in CMID.
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'studioid', $studioid);
        $mform->setType('studioid', PARAM_INT);
        $mform->addElement('hidden', 'l1id', $this->_customdata['l1id']);
        $mform->setType('l1id', PARAM_INT);
        $mform->addElement('hidden', 'l2id', $this->_customdata['l2id']);
        $mform->setType('l2id', PARAM_INT);

        // Code below adds in the repeater function to add additional blocks.
        $repeatarray = array();
        $repeatarray[] = $mform->createElement('header', '',
            get_string('newcontentheader', 'openstudio'));
        $formelement1 = $mform->createElement('text', 'odsnewcontentname',
            get_string('newcontentlabel', 'openstudio'));
        $formelement1->setType('odsnewcontentname', PARAM_TEXT);
        $repeatarray[] = $formelement1;
        $formelement2 = $mform->createElement('text', 'odsnewcontentsortorder',
            get_string('newcontentposition', 'openstudio'));
        $formelement2->setType('odsnewcontentsortorder', PARAM_INT);
        $repeatarray[] = $formelement2;
        $formelement3 = $mform->createElement('checkbox',
            'odsnewcontentrequired', '', get_string('requiredfortma', 'openstudio'));
        $repeatarray[] = $formelement3;
        if ($setsorcollections !== false) {
            $formelement4 = $mform->createElement('checkbox',
                'odsnewcontentiscollection', '', $collectionlabel);
            $repeatarray[] = $formelement4;
        }
        $repeateloptions = array();
        $repeateloptions['odsnewcontentsortorder']['default'] = levels::get_latest_sortorder(
            3, $this->_customdata['l2id']);

        $mform->setType('odsnewcontentsortorder', PARAM_INT);
        $repeatno = 0;
        $this->repeat_elements($repeatarray, $repeatno,
            $repeateloptions, 'addcontentcount', 'add_content', 1, get_string('addanothercontent', 'openstudio'));

        if (isset($this->_customdata['editcontent']) || isset($this->_customdata['add_content'])) {
            // Add save and cancel buttons.
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'submitbutton',
                get_string('savechanges', 'openstudio'), array('id' => 'id_submitbutton'));
            $buttonarray[] = $mform->createElement('cancel', 'cancelbutton',
                '', array('id' => 'id_cancel'));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        }
    }
}
