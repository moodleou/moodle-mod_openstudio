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
 * @package mod_studio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/*
 * Studio slot edit form.
 *
 * @package mod_studio
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studio_slot_form extends moodleform {

    protected function definition() {
        global $CFG, $USER, $DB, $PAGE, $OUTPUT;

        $mform = $this->_form;

        $mform->addElement('header', 'attachment', $this->_customdata['slotname']);

        if (isset($this->_customdata['issetlock']) && $this->_customdata['issetlock']) {
            $mform->addElement('html', html_writer::tag('p', get_string('slotislocked', 'studio')));
            return;
        }

        $mform->addElement('text', 'name',
                get_string('slotformname', 'studio'));
        $mform->setType('name', PARAM_TEXT);

        $textformat = $DB->get_field('studio_slots', 'textformat', array('id' => $this->_customdata['slotid']));
        if ($this->_customdata['feature_slottextuseshtml'] || ((int) $textformat === 1)) {
            $mform->addElement('editor', 'description',
                    get_string('slotformdescription', 'studio'));
            $mform->setType('description', PARAM_RAW);
        } else {
            $mform->addElement('textarea', 'description',
                    get_string('slotformdescription', 'studio'),
                    'wrap="virtual" cols="100" rows="4" ');
        }

        if ($this->_customdata['issetslot'] == true) {
            $mform->addElement('hidden', 'visibility');
            $mform->setType('visibility', PARAM_INT);
            $mform->setDefault('visibility', STUDIO_VISIBILITY_INSETONLY);
        } else {
            $options = array();
            if (in_array(STUDIO_VISIBILITY_PRIVATE, $this->_customdata['allowedvisibility'])) {
                $options[STUDIO_VISIBILITY_PRIVATE] =
                        get_string('slotformvisibilityprivate', 'studio');
            }
            if ($this->_customdata['sharewithothers'] && $this->_customdata['isenrolled']) {
                if ($this->_customdata['feature_module']
                        && in_array(STUDIO_VISIBILITY_MODULE, $this->_customdata['allowedvisibility'])) {
                    $options[STUDIO_VISIBILITY_MODULE] =
                            get_string('slotformvisibilitymodule', 'studio');
                } else {
                    if ($this->_customdata['defaultvisibility'] == STUDIO_VISIBILITY_MODULE) {
                        $this->_customdata['defaultvisibility'] = STUDIO_VISIBILITY_PRIVATE;
                    }
                }

                if ($this->_customdata['feature_group']) {
                    if (in_array(STUDIO_VISIBILITY_TUTOR, $this->_customdata['allowedvisibility'])) {
                        $options[STUDIO_VISIBILITY_TUTOR] =
                                get_string('slotformvisibilitytutor', 'studio');
                    }
                    if (in_array(STUDIO_VISIBILITY_GROUP, $this->_customdata['allowedvisibility'])) {
                        // Users can only share slots to groups that they are a member of.
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
                                $options[$tutorgroupid] =
                                        get_string('viewgroupname', 'studio', array('name' => $tutorgroup->name));
                            }
                        }
                        if ($this->_customdata['defaultvisibility'] == STUDIO_VISIBILITY_GROUP) {
                            if ($firsttutorgroupid !== false) {
                                $this->_customdata['defaultvisibility'] = $firsttutorgroupid;
                            } else {
                                $this->_customdata['defaultvisibility'] = STUDIO_VISIBILITY_PRIVATE;
                            }
                        }
                    }
                } else {
                    if ($this->_customdata['defaultvisibility'] == STUDIO_VISIBILITY_GROUP) {
                        $this->_customdata['defaultvisibility'] = STUDIO_VISIBILITY_PRIVATE;
                    }
                }
            } else {
                $this->_customdata['defaultvisibility'] = STUDIO_VISIBILITY_PRIVATE;
            }
            $mform->addElement('select', 'visibility',
                    get_string('slotformvisibility', 'studio'),
                    $options);
            $mform->setDefault('visibility', $this->_customdata['defaultvisibility']);
        }

        if (in_array((int) $this->_customdata['slottype'],
                array(STUDIO_CONTENTTYPE_COLLECTION, STUDIO_CONTENTTYPE_SET), true)) {
            $mform->addElement('hidden', 'weblink');
            $mform->setType('weblink', PARAM_URL);
            $mform->addElement('hidden', 'urltitle');
            $mform->setType('urltitle', PARAM_TEXT);
            $mform->addElement('hidden', 'embedcode');
            $mform->setType('embedcode', PARAM_TEXT);
            $mform->addElement('hidden', 'contenttype');
            $mform->setType('contenttype', PARAM_INT);
            $mform->setDefault('contenttype', (int) $this->_customdata['slottype']);

            $collectionitems = studio_api_collection_get_items($this->_customdata['slotid']);
            $total = count($collectionitems);
            if ($total > 0) {
                $mform->addElement('html', html_writer::tag('a', '', array('name' => 'collectionslotsheader')));
                $mform->addElement('header', 'collectionslotsheader', 'Slots in this collection');
                $mform->setExpanded('collectionslotsheader', true);

                $counter = 0;
                foreach ($collectionitems as $collectionitem) {
                    $counter++;
                    $collectionitemrow = array();
                    $collectionitemname = $collectionitem->name;
                    if (trim($collectionitem->name) == '') {
                        $collectionitemname = 'Untitled';
                    }

                    $collectionitemrow[] = $mform->createElement('static',
                            'collectionitem[' . $collectionitem->id . ']', '', $collectionitemname);

                    if ($total > 1) {
                        if ($counter == 1) {
                            $collectionitemrow[] = $mform->createElement('image',
                                    'movednbutton[' . $collectionitem->id . ']',
                                    $OUTPUT->pix_url('t/down'), array('title' => get_string('movedown', 'studio')));
                        } else if ($counter == $total) {
                            $collectionitemrow[] = $mform->createElement('image',
                                    'moveupbutton[' . $collectionitem->id . ']',
                                    $OUTPUT->pix_url('t/up'), array('title' => get_string('moveup', 'studio')));
                        } else {
                            $collectionitemrow[] = $mform->createElement('image',
                                    'movednbutton[' . $collectionitem->id . ']',
                                    $OUTPUT->pix_url('t/down'), array('title' => get_string('movedown', 'studio')));
                            $collectionitemrow[] = $mform->createElement('image',
                                    'moveupbutton[' . $collectionitem->id . ']',
                                    $OUTPUT->pix_url('t/up'), array('title' => get_string('moveup', 'studio')));
                        }
                    }

                    $collectionitemrow[] = $mform->createElement('image',
                            'editbutton[' . $collectionitem->id . ']',
                            $OUTPUT->pix_url('t/edit'), array('title' => get_string('editlevel', 'studio')));

                    $collectionitemrow[] = $mform->createElement('image',
                            'deletebutton[' . $collectionitem->id . ']',
                            $OUTPUT->pix_url('t/delete'), array('title' => get_string('deletelevel', 'studio')));

                    $mform->addGroup($collectionitemrow, null, $counter . '. ', ' ', null, true);
                }
            }

        } else {

            if ($this->_customdata['feature_slotusesfileupload']) {
                $mform->addElement('html', html_writer::tag('p', get_string('slotformhelp1', 'studio'),
                        array('class' => 'slotformsectionhelpmsg')));

                $maxbytes = defined('STUDIO_SLOT_MAXBYTES') ? STUDIO_SLOT_MAXBYTES :
                        (isset($CFG->maxbytes) ? $CFG->maxbytes : STUDIO_DEFAULT_MAXBYTES);

                // Check for what file upload types to restrict.
                $acceptedtypes = array();
                $availabletypes = get_mimetypes_array();
                foreach ($this->_customdata['allowedfiletypes'] as $allowedfiletype) {
                    $acceptabletypes = array();
                    if ($allowedfiletype == 'images') {
                        $acceptabletypes = array('image', 'jpeg', 'png', 'gif', 'bmp');
                    }
                    if ($allowedfiletype == 'videos') {
                        $acceptabletypes = array('video', 'avi', 'mpeg', 'quicktime');
                    }
                    if ($allowedfiletype == 'audio') {
                        $acceptabletypes = array('audio', 'mp3');
                    }
                    if ($allowedfiletype == 'documents') {
                        $acceptabletypes = array('document', 'text', 'word', 'docx', 'dotx', 'pdf', 'odt', 'odm', 'writer');
                    }
                    if ($allowedfiletype == 'presentations') {
                        $acceptabletypes = array('presentation', 'powerpoint', 'pptx', 'potx', 'ppsx', 'odp', 'impress');
                    }
                    if ($allowedfiletype == 'spreadsheets') {
                        $acceptabletypes = array('spreadsheet', 'excel', 'xlsx', 'xltx', 'ods', 'calc');
                    }

                    foreach ($availabletypes as $typeextension => $typeinfo) {
                        if (in_array($typeinfo['icon'], $acceptabletypes)) {
                            $acceptedtypes[] = ".{$typeextension}";
                        }
                    }

                    // Additional types not in moodle.
                    if ($allowedfiletype == 'audio') {
                        $acceptedtypes[] = '.wav';
                    }
                    if ($allowedfiletype == 'videos') {
                        $acceptedtypes[] = '.flv';
                        $acceptedtypes[] = '.wmv';
                        $acceptedtypes[] = '.swf';
                    }
                    if ($allowedfiletype == 'documents' && $this->_customdata['feature_slotallownotebooks']) {
                        $acceptedtypes[] = '.nbk';
                    }

                }
                $mform->addElement('filemanager', 'attachments',
                        get_string('slotformattachments', 'studio'), null,
                        array('maxbytes' => $maxbytes, 'subdirs' => false,
                              'maxfiles' => 1, 'accepted_types' => $acceptedtypes));
                $mform->addHelpButton('attachments', 'attachments', 'studio');
            }

            if ($this->_customdata['feature_slotusesweblink']) {
                $mform->addElement('html', html_writer::tag('p', get_string('slotformhelp2', 'studio'),
                        array('class' => 'slotformsectionhelpmsg slotformsectionhelpmsg2')));

                $mform->addElement('text', 'weblink',
                        get_string('slotformweblink', 'studio'));
                $mform->setType('weblink', PARAM_URL);
                $mform->addRule(
                        'weblink',
                        get_string('slotformweblinkerror', 'studio'),
                        'regex',
                        '/(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/',
                        'client');

                $mform->addElement('text', 'urltitle',
                        get_string('slotformurltitle', 'studio'));
                $mform->setType('urltitle', PARAM_TEXT);
            } else {
                $mform->addElement('hidden', 'weblink');
                $mform->setType('weblink', PARAM_URL);
                $mform->setDefault('weblink', '');
                $mform->addElement('hidden', 'urltitle');
                $mform->setType('urltitle', PARAM_TEXT);
                $mform->setDefault('weblink', '');
            }

            if ($this->_customdata['feature_slotusesembedcode']) {
                $mform->addElement('html', html_writer::tag('p', get_string('slotformhelp4', 'studio'),
                        array('class' => 'slotformsectionhelpmsg slotformsectionhelpmsg3')));

                $mform->addElement('textarea', 'embedcode',
                        get_string('slotformembedcode', 'studio'),
                        'rows="2" cols="100"');
                $mform->addHelpButton('embedcode', 'embedcode', 'studio');
            } else {
                $mform->addElement('hidden', 'embedcode');
                $mform->setType('embedcode', PARAM_TEXT);
                $mform->setDefault('embedcode', '');
            }

        }

        $renderer = $PAGE->get_renderer('mod_studio');

        $optionmetadatahtml = get_string('slotformheadermetadata', 'studio');

        $mform->addElement('header', 'metadata', $optionmetadatahtml);
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded('metadata', false);
        }

        $mform->addElement('html', html_writer::tag('p', get_string('slotformhelp3', 'studio'),
                array('class' => 'slotformsectionhelpmsg')));

        $mform->addElement('html', html_writer::start_tag('div',
                array('id' => 'studio-slot-form-optional-metadata')));

        $mform->addElement('tags', 'tags', get_string('tags'), array('itemtype' => 'studio_slots', 'component' => 'mod_studio'));

        if (!$this->_customdata['feature_slotusesfileupload'] ||
                (((int) $this->_customdata['slottype']) === STUDIO_CONTENTTYPE_COLLECTION) ||
                (((int) $this->_customdata['slottype']) === STUDIO_CONTENTTYPE_SET)) {
            $mform->addElement('hidden', 'showgps');
            $mform->setType('showgps', PARAM_INT);
            $mform->setDefault('showgps', 0);
            $mform->addElement('hidden', 'showimagedata');
            $mform->setType('showimagedata', PARAM_INT);
            $mform->setDefault('showimagedata', 0);
        } else {
            $mform->addElement('advcheckbox', 'showgps',
                    get_string('slotformshowgps', 'studio'),
                    get_string('slotformshowgpsdescription', 'studio'),
                    array('group' => 1), array(0, STUDIO_SLOT_INFO_GPSDATA));
            $mform->setDefault('showgps', 0);

            $mform->addElement('advcheckbox', 'showimagedata',
                    get_string('slotformshowimagedata', 'studio'),
                    get_string('slotformshowimagedatadescription', 'studio'),
                    array('group' => 1), array(0, STUDIO_SLOT_INFO_IMAGEDATA));
            $mform->setDefault('showimagedata', 0);

            if ($this->_customdata['slotid']) {
                $showextradata = $DB->get_field('studio_slots', 'showextradata', array('id' => $this->_customdata['slotid']));
                if ($showextradata & STUDIO_SLOT_INFO_GPSDATA) {
                    $mform->setDefault('showgps', STUDIO_SLOT_INFO_GPSDATA);
                }
                if ($showextradata & STUDIO_SLOT_INFO_IMAGEDATA) {
                    $mform->setDefault('showimagedata', STUDIO_SLOT_INFO_IMAGEDATA);
                }
            }
        }

        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'ownership', '',
                get_string('slotformownershipmyownwork', 'studio'),
                STUDIO_OWNERSHIP_MYOWNWORK);
        $radioarray[] = $mform->createElement('radio', 'ownership', '',
                get_string('slotformownershipfoundonline', 'studio'),
                STUDIO_OWNERSHIP_FOUNDONLINE);
        $radioarray[] = $mform->createElement('radio', 'ownership', '',
                get_string('slotformownershipfoundelsewhere', 'studio'),
                STUDIO_OWNERSHIP_FOUNDELSEWHERE);
        $mform->addGroup($radioarray, 'ownershiparray',
                get_string('slotformownership', 'studio'),
                array(' '), false);

        $mform->addElement('text',
                'ownershipdetail',
                get_string('slotformownershipdetail', 'studio'));
        $mform->setType('ownershipdetail', PARAM_TEXT);

        $mform->addElement('html', html_writer::end_tag('div'));

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
                get_string('slotformsubmitbutton', 'studio'),
                array('id' => 'id_submitbutton'));
        if (((int) $this->_customdata['slottype']) === STUDIO_CONTENTTYPE_COLLECTION) {
            $buttonarray[] = $mform->createElement('submit', 'submitbutton',
                    get_string('slotformsaveandcollect', 'studio'),
                    array('id' => 'id_submitcollectbutton', 'class' => 'studio-collection-edit-collect-button'));
        }
        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton',
                '', array('id' => 'id_cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        global $DB, $USER;
        $errors = array();
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
                        $contextid = $file->get_contextid();
                        $itemid = $file->get_itemid();
                        $filepath = $file->get_filepath();
                        $filelist = $file->list_files($packer);

                        // Check that we've got the right number of files.
                        if (count($filelist) < 2) {
                            $errors['attachments'] = get_string('errorslotemptynotebook', 'studio');
                        } else if (count($filelist) > 2) {
                            $errors['attachments'] = get_string('errorslotfullnotebook', 'studio');
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
                                    $errors['attachments'] = get_string('errorslotinvalidnotebook', 'studio');
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
                                $extractedfiles = array();
                                foreach ($extractedfilenames as $extractedfilename => $success) {
                                    if ($success !== true) {
                                        $parts = (object)array('filename' => $extractedfilename, 'message' => $success);
                                        $errors['attachments'] = get_string('errorslotnotebookerror', 'studio', $parts);
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
                                            $errors['attachments'] = get_string('errorslotinvalidnotebook', 'studio');
                                            break;
                                        } else {
                                            // Check that the object parsed from JSON has at least the most basic properties
                                            // of an ipynb file.
                                            // Note there are some differences between versions of notebook files.
                                            if ((!isset($ipynb->metadata, $ipynb->nbformat_minor, $ipynb->nbformat) ||
                                                ($ipynb->nbformat == 3 && !isset($ipynb->worksheets)) ||
                                                ($ipynb->nbformat == 4 && !isset($ipynb->cells)))) {
                                                $errors['attachments'] = get_string('errorslotinvalidnotebook', 'studio');
                                                break;
                                            }
                                        }
                                        // Set the ipynb's sort order to 1 so it's returned first when getting files from the
                                        // file area, and therefore it's seen as "the" file in the slot when we call
                                        // studio_api_slot_create in slotedit.php. Since this is the only instance we'll ever have
                                        // a ipynb file at that point, we can use it as an indication that the slot contains
                                        // a notebook.
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
                                        $errors['attachments'] = get_string('errorslotinvalidnotebook', 'studio');
                                    }
                                }
                            }
                        }
                    } else {
                        $errors['attachments'] = get_string('errorslotinvalidnotebook', 'studio');
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
