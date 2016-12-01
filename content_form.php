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
require_once($CFG->libdir . '/formslib.php');

use mod_openstudio\local\api\content;

/**
 * Studio content edit form.
 *
 * @package mod_openstudio
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_openstudio_content_form extends moodleform {

    protected function definition() {
        global $CFG, $USER, $DB, $PAGE, $OUTPUT;

        $mform = $this->_form;

        if (isset($this->_customdata['isfolderlock']) && $this->_customdata['isfolderlock']) {
            return;
        }

        if ($this->_customdata['isfoldercontent'] == true) {
            $mform->addElement('hidden', 'visibility');
            $mform->setType('visibility', PARAM_INT);
            $mform->setDefault('visibility', content::VISIBILITY_INFOLDERONLY);
        } else {
            $options = array();
            if (in_array(content::VISIBILITY_PRIVATE, $this->_customdata['allowedvisibility'])) {
                $options[content::VISIBILITY_PRIVATE] = get_string('contentformvisibilityprivate', 'openstudio');
            }
            if ($this->_customdata['sharewithothers'] && $this->_customdata['isenrolled']) {
                if ($this->_customdata['feature_module']
                        && in_array(content::VISIBILITY_MODULE, $this->_customdata['allowedvisibility'])) {
                    $options[content::VISIBILITY_MODULE] = get_string('contentformvisibilitymodule', 'openstudio');
                } else {
                    if ($this->_customdata['defaultvisibility'] == content::VISIBILITY_MODULE) {
                        $this->_customdata['defaultvisibility'] = content::VISIBILITY_PRIVATE;
                    }
                }

                if ($this->_customdata['feature_group']) {
                    if (in_array(content::VISIBILITY_TUTOR, $this->_customdata['allowedvisibility'])) {
                        $options[content::VISIBILITY_TUTOR] = get_string('contentformvisibilitytutor', 'openstudio');
                    }
                    if (in_array(content::VISIBILITY_GROUP, $this->_customdata['allowedvisibility'])) {
                        // Users can only share contents to groups that they are a member of.
                        // This applies to all users and admins.
                        if ($this->_customdata['groupingid'] > 0) {
                            $tutorgroups = studio_api_group_list(
                                    $this->_customdata['courseid'], $this->_customdata['groupingid'], $USER->id, 1);
                        } else {
                            $tutorgroups = studio_api_group_list(
                                    $this->_customdata['courseid'], 0, $USER->id, 1);
                        }
                        $firsttutorgroupid = false;
                        if ($tutorgroups !== false) {
                            foreach ($tutorgroups as $tutorgroup) {
                                $tutorgroupid = 0 - $tutorgroup->groupid;
                                if ($firsttutorgroupid === false) {
                                    $firsttutorgroupid = $tutorgroupid;
                                }
                                $options[$tutorgroupid] = get_string('viewgroupname', 'openstudio',
                                    array('name' => $tutorgroup->name));
                            }
                        }
                        if ($this->_customdata['defaultvisibility'] == content::VISIBILITY_GROUP) {
                            if ($firsttutorgroupid !== false) {
                                $this->_customdata['defaultvisibility'] = $firsttutorgroupid;
                            } else {
                                $this->_customdata['defaultvisibility'] = content::VISIBILITY_PRIVATE;
                            }
                        }
                    }
                } else {
                    if ($this->_customdata['defaultvisibility'] == content::VISIBILITY_GROUP) {
                        $this->_customdata['defaultvisibility'] = content::VISIBILITY_PRIVATE;
                    }
                }
            } else {
                $this->_customdata['defaultvisibility'] = content::VISIBILITY_PRIVATE;
            }
            $mform->addElement('select', 'visibility',
                    get_string('contentformvisibility', 'openstudio'),
                    $options, array('class' => 'visibility'));
            $mform->setDefault('visibility', $this->_customdata['defaultvisibility']);
        }

        $mform->addElement('text', 'name',
                get_string('contentformname', 'openstudio'));
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('editor', 'description',
                get_string('contentformdescription', 'openstudio'));
        $mform->setType('description', PARAM_RAW);

        if (in_array((int) $this->_customdata['contenttype'],
                array(content::TYPE_FOLDER), true)) {
            $mform->addElement('hidden', 'weblink');
            $mform->setType('weblink', PARAM_URL);
            $mform->addElement('hidden', 'urltitle');
            $mform->setType('urltitle', PARAM_TEXT);
            $mform->addElement('hidden', 'embedcode');
            $mform->setType('embedcode', PARAM_TEXT);
            $mform->addElement('hidden', 'contenttype');
            $mform->setType('contenttype', PARAM_INT);
            $mform->setDefault('contenttype', (int) $this->_customdata['contenttype']);

        } else {

            if ($this->_customdata['feature_contentusesfileupload']) {

                if ($this->_customdata['max_bytes']) {
                    $maxbytes = $this->_customdata['max_bytes'];
                } else {
                    $maxbytes = (isset($CFG->maxbytes) ? $CFG->maxbytes : \mod_openstudio\local\util\defaults::MAXBYTES);
                }

                $mform->addElement('filemanager', 'attachments',
                        get_string('contentformattachments', 'openstudio'), null,
                        array('maxbytes' => $maxbytes, 'subdirs' => false,
                              'maxfiles' => 1, 'accepted_types' => '*'));
                $mform->addHelpButton('attachments', 'attachments', 'openstudio');
            }

            if ($this->_customdata['feature_contentusesweblink']) {

                $mform->addElement('text', 'weblink',
                        get_string('contentformweblink', 'openstudio'));
                $mform->setType('weblink', PARAM_URL);
                $mform->addRule(
                        'weblink',
                        get_string('contentformweblinkerror', 'openstudio'),
                        'regex',
                        '/(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/',
                        'client');
                $mform->addHelpButton('weblink', 'weblink', 'openstudio');
                $mform->addElement('text', 'urltitle',
                        get_string('contentformurltitle', 'openstudio'));
                $mform->setType('urltitle', PARAM_TEXT);
                $mform->addHelpButton('urltitle', 'urltitle', 'openstudio');
            } else {
                $mform->addElement('hidden', 'weblink');
                $mform->setType('weblink', PARAM_URL);
                $mform->setDefault('weblink', '');
                $mform->addElement('hidden', 'urltitle');
                $mform->setType('urltitle', PARAM_TEXT);
                $mform->setDefault('weblink', '');
            }

            if ($this->_customdata['feature_contentusesembedcode']) {

                $mform->addElement('textarea', 'embedcode',
                        get_string('contentformembedcode', 'openstudio'),
                        'rows="2" cols="100"');
                $mform->addHelpButton('embedcode', 'embedcode', 'openstudio');
            } else {
                $mform->addElement('hidden', 'embedcode');
                $mform->setType('embedcode', PARAM_TEXT);
                $mform->setDefault('embedcode', '');
            }

        }

        $mform->addElement('tags', 'tags', get_string('tags'), array(
            'itemtype' => 'openstudio_contents',
            'component' => 'mod_studio'
        ));

        if (!$this->_customdata['feature_contentusesfileupload'] ||
                (((int) $this->_customdata['contenttype']) === content::TYPE_FOLDER)) {
            $mform->addElement('hidden', 'showgps');
            $mform->setType('showgps', PARAM_INT);
            $mform->setDefault('showgps', 0);
            $mform->addElement('hidden', 'showimagedata');
            $mform->setType('showimagedata', PARAM_INT);
            $mform->setDefault('showimagedata', 0);
        } else {
            $mform->addElement('advcheckbox', 'showgps',
                    get_string('contentformshowgps', 'openstudio'),
                    get_string('contentformshowgpsdescription', 'openstudio'),
                    array('group' => 1), array(0, content::INFO_GPSDATA));
            $mform->setDefault('showgps', 0);

            $mform->addElement('advcheckbox', 'showimagedata',
                    get_string('contentformshowimagedata', 'openstudio'),
                    get_string('contentformshowimagedatadescription', 'openstudio'),
                    array('group' => 1), array(0, content::INFO_IMAGEDATA));
            $mform->setDefault('showimagedata', 0);

            if ($this->_customdata['contentid']) {
                $showextradata = $DB->get_field('openstudio_contents', 'showextradata',
                    array('id' => $this->_customdata['contentid']));
                if ($showextradata & content::INFO_GPSDATA) {
                    $mform->setDefault('showgps', content::INFO_GPSDATA);
                }
                if ($showextradata & content::INFO_IMAGEDATA) {
                    $mform->setDefault('showimagedata', content::INFO_IMAGEDATA);
                }
            }
        }

        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'ownership', '',
                get_string('contentformownershipmyownwork', 'openstudio'),
                content::OWNERSHIP_MYOWNWORK);
        $radioarray[] = $mform->createElement('radio', 'ownership', '',
                get_string('contentformownershipfoundonline', 'openstudio'),
                content::OWNERSHIP_FOUNDONLINE);
        $radioarray[] = $mform->createElement('radio', 'ownership', '',
                get_string('contentformownershipfoundelsewhere', 'openstudio'),
                content::OWNERSHIP_FOUNDELSEWHERE);
        $mform->addGroup($radioarray, 'ownershiparray',
                get_string('contentformownership', 'openstudio'),
                array(' '), false);

        $mform->addElement('text',
                'ownershipdetail',
                get_string('contentformownershipdetail', 'openstudio'));
        $mform->setType('ownershipdetail', PARAM_TEXT);

        $mform->addElement('hidden', 'sid');
        $mform->setType('sid', PARAM_INT);
        $mform->addElement('hidden', 'levelid');
        $mform->setType('levelid', PARAM_INT);
        $mform->addElement('hidden', 'levelcontainer');
        $mform->setType('levelcontainer', PARAM_INT);
        $mform->addElement('hidden', 'checksum');
        $mform->setType('checksum', PARAM_TEXT);
        $mform->addElement('hidden', 'textformat');
        $mform->setType('textformat', PARAM_INT);
        $mform->addElement('hidden', 'commentformat');
        $mform->setType('commentformat', PARAM_INT);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton',
                get_string('contentformsubmitbutton', 'openstudio'),
                array('id' => 'id_submitbutton'));

        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton',
                '', array('id' => 'id_cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

    }

    public function validation($data, $files) {
        global $DB, $USER;
        $errors = parent::validation($data, $files);
        if (!empty($data['attachments'])) {
            $fs = get_file_storage();
            $usercontext = context_user::instance($USER->id);
            $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['attachments']);
            if ($draftfiles) {
                // The form only lets us upload 1 file, so there will only be 1 file in the array.
                $file = array_pop($draftfiles);
                if ($file->get_mimetype() == 'application/x-smarttech-notebook') {
                    $packer = new zip_packer();
                    if ($file) {
                        $itemid = $file->get_itemid();
                        $filepath = $file->get_filepath();
                        $filelist = $file->list_files($packer);

                        // Check that we've got the right number of files.
                        if (count($filelist) < 2) {
                            $errors['attachments'] = get_string('errorcontentemptynotebook', 'openstudio');
                        } else if (count($filelist) > 2) {
                            $errors['attachments'] = get_string('errorcontentfullnotebook', 'openstudio');
                        } else {
                            // We've got the right number of files, let's check if they appear to be the right types.
                            $expectedtypes = array('html', 'htm', 'ipynb');
                            foreach ($filelist as $contentfile) {
                                $extension = pathinfo($contentfile->pathname, PATHINFO_EXTENSION);
                                if (in_array($extension, $expectedtypes)) {
                                    $foundtypes = array($extension);
                                    // We only want 1 of htm or html, so if we've found one, remove both.
                                    if ($extension == 'html') {
                                        $foundtypes[] = 'htm';
                                    } else if ($extension == 'htm') {
                                        $foundtypes[] = 'html';
                                    }
                                    $expectedtypes = array_diff($expectedtypes, $foundtypes);
                                } else {
                                    $errors['attachments'] = get_string('errorcontentinvalidnotebook', 'openstudio');
                                }
                            }
                            if (!isset($errors['attachments'])) {
                                // We've got the right number of files and they appear to be of the correct type.
                                // Now we need to extract them and take a look to make sure they're actually notebook
                                // files before we accept them.
                                $extractedfilenames = $file->extract_to_storage(
                                    $packer,
                                    $usercontext->id,
                                    'user',
                                    'draft',
                                    $itemid,
                                    $filepath
                                );
                                $file->set_sortorder(3);
                                $html = '';
                                $ipynb = '';
                                foreach ($extractedfilenames as $extractedfilename => $success) {
                                    if ($success !== true) {
                                        $parts = (object)array('filename' => $extractedfilename, 'message' => $success);
                                        $errors['attachments'] = get_string('errorcontentnotebookerror', 'openstudio', $parts);
                                        break;
                                    }
                                    $extractedfile = $fs->get_file(
                                        $usercontext->id,
                                        'user',
                                        'draft',
                                        $itemid,
                                        $filepath,
                                        $extractedfilename
                                    );
                                    $content = $extractedfile->get_content();
                                    if (pathinfo($extractedfilename, PATHINFO_EXTENSION) == 'ipynb') {
                                        $ipynb = json_decode($content);
                                        if ($ipynb === null) {
                                            // If the file contents isn't decodeable from JSON, it isn't
                                            // really an ipython notebook.
                                            $errors['attachments'] = get_string('errorcontentinvalidnotebook', 'openstudio');
                                            break;
                                        } else {
                                            // Check that the object parsed from JSON has at least the most basic properties
                                            // of an ipynb file.
                                            // Note there are some differences between versions of notebook files.
                                            if ((!isset($ipynb->metadata, $ipynb->nbformat_minor, $ipynb->nbformat) ||
                                                ($ipynb->nbformat == 3 && !isset($ipynb->worksheets)) ||
                                                ($ipynb->nbformat == 4 && !isset($ipynb->cells)))) {
                                                $errors['attachments'] = get_string('errorcontentinvalidnotebook', 'openstudio');
                                                break;
                                            }
                                        }
                                        // Set the ipynb's sort order to 1 so it's returned first when getting files from the
                                        // file area, and therefore it's seen as "the" file in the content when we call
                                        // studio_api_content_create in contentedit.php. Since this is the only instance
                                        // we'll ever have a ipynb file at that point, we can use it as an indication
                                        // that the content contains a notebook.
                                        $extractedfile->set_sortorder(1);
                                    } else {
                                        $html = $content;
                                        // For the HTML file, There's not a lot that we can reliably check at this stage, so
                                        // we'll just set the file's sortorder.
                                        $extractedfile->set_sortorder(2);
                                    }
                                }
                                if (!isset($errors['attachments'])) {
                                    // We've got 2 files and they're the right types. Finally, if we can, let's check that the HTML
                                    // has the same number of cells as the source file.
                                    $cellcount = 0;
                                    if ($ipynb->nbformat == 3) {
                                        if (count($ipynb->worksheets) > 0) {
                                            $worksheet = $ipynb->worksheets[0];
                                            if (isset($worksheet->cells)) {
                                                $cellcount = count($worksheet->cells);
                                            }
                                        }
                                    } else {
                                        $cellcount = count($ipynb->cells);
                                    }
                                    if ($cellcount > 0 && $cellcount != substr_count($html, '<div class="cell')) {
                                        $errors['attachments'] = get_string('errorcontentinvalidnotebook', 'openstudio');
                                    }
                                }
                            }
                        }
                    } else {
                        $errors['attachments'] = get_string('errorcontentinvalidnotebook', 'openstudio');
                    }
                }

                if (isset($errors['attachments'])) {
                    // If the file was invalid, clean up the drafts area.
                    $fs->delete_area_files($usercontext->id, 'user', 'draft', $data['attachments']);
                }
            }
        }
        return $errors;
    }
}
