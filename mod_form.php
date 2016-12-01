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
 * The main openstudio configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\flags;
use mod_openstudio\local\util\defaults;
use mod_openstudio\local\util\feature;

/**
 * Module instance settings form
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_openstudio_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $COURSE;

        $mform = $this->_form;

        // -------------------------------------------------------------------------------

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name',
            get_string('studioname', 'openstudio'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name',
            get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the required "intro" field to hold the description of the instance.
        // Adding "introformat" field.
        $this->standard_intro_elements(get_string('studiointro', 'openstudio'));

        // -------------------------------------------------------------------------------

        $mform->addElement('header', 'theme',
            get_string('settingstheme', 'openstudio'));

        $mform->addElement('text', 'sitename',
            get_string('settingsthemesitename', 'openstudio'),
            array('size' => defaults::SITENAME_LENGTH));
        $mform->setType('sitename', PARAM_TEXT);
        $mform->addRule('sitename', null, 'required', null, 'client');
        $mform->addRule('sitename',
            get_string('maximumchars', '', defaults::SITENAME_LENGTH),
            'maxlength', defaults::SITENAME_LENGTH, 'client');
        $mform->setDefault('sitename',
            get_string('settingsthemesitenamedefault', 'openstudio'));

        $themehomedefaultarray = array(
            content::VISIBILITY_MODULE => get_string('settingsthemehomesettingsmodule', 'openstudio'),
            content::VISIBILITY_GROUP => get_string('settingsthemehomesettingsgroup', 'openstudio'),
            content::VISIBILITY_PRIVATE => get_string('settingsthemehomesettingsstudio', 'openstudio'),
            content::VISIBILITY_PRIVATE_PINBOARD => get_string('settingsthemehomesettingspinboard', 'openstudio'));
        $mformselect = $mform->addElement('select', 'themehomedefault',
            get_string('settingsthemehomesettings', 'openstudio'),
            $themehomedefaultarray);

        $mform->addElement('text', 'thememodulename',
            get_string('settingsthememodulename', 'openstudio'),
            array('size' => defaults::NAVITEM_LENGTH));
        $mform->setType('thememodulename', PARAM_TEXT);
        $mform->addRule('thememodulename',
            get_string('maximumchars', '', defaults::NAVITEM_LENGTH),
            'maxlength', defaults::NAVITEM_LENGTH, 'client');

        $mform->addElement('text', 'themegroupname',
            get_string('settingsthemegroupname', 'openstudio'),
            array('size' => defaults::NAVITEM_LENGTH));
        $mform->setType('themegroupname', PARAM_TEXT);
        $mform->addRule('themegroupname',
            get_string('maximumchars', '', defaults::NAVITEM_LENGTH),
            'maxlength', defaults::NAVITEM_LENGTH, 'client');

        $mform->addElement('text', 'themestudioname',
            get_string('settingsthemestudioname', 'openstudio'),
            array('size' => defaults::NAVITEM_LENGTH));
        $mform->setType('themestudioname', PARAM_TEXT);
        $mform->addRule('themestudioname',
            get_string('maximumchars', '', defaults::NAVITEM_LENGTH),
            'maxlength', defaults::NAVITEM_LENGTH, 'client');

        $mform->addElement('text', 'themepinboardname',
            get_string('settingsthemepinboardname', 'openstudio'),
            array('size' => defaults::NAVITEM_LENGTH));
        $mform->setType('themepinboardname', PARAM_TEXT);
        $mform->addRule('themepinboardname',
            get_string('maximumchars', '', defaults::NAVITEM_LENGTH),
            'maxlength', defaults::NAVITEM_LENGTH, 'client');

        $mform->addElement('text', 'themehelplink',
            get_string('settingsthemehelplink', 'openstudio'),
            array('size' => defaults::NAVITEM_LENGTH));
        $mform->setType('themehelplink', PARAM_URL);
        $mform->addRule('themehelplink',
            get_string('maximumchars', '', 600),
            'maxlength', 600, 'client');

        $mform->addElement('text', 'themehelpname',
            get_string('settingsthemehelpname', 'openstudio'),
            array('size' => defaults::NAVITEM_LENGTH));
        $mform->setType('themehelpname', PARAM_TEXT);
        $mform->addRule('themehelpname',
            get_string('maximumchars', '', 25),
            'maxlength', 255, 'client');

        // -------------------------------------------------------------------------------

        // Add standard elements and buttons, common to all modules.
        $this->standard_coursemodule_elements();

        // -------------------------------------------------------------------------------

        $mform->addElement('header', 'socialsettings',
            get_string('settingssocial', 'openstudio'));

        $sharringlevelarray = array(
            content::VISIBILITY_PRIVATE => get_string('settingssocialsharinglevelprivate', 'openstudio'),
            content::VISIBILITY_TUTOR => get_string('settingssocialsharingleveltutor', 'openstudio'),
            content::VISIBILITY_GROUP => get_string('settingssocialsharinglevelgroup', 'openstudio'),
            content::VISIBILITY_MODULE => get_string('settingssocialsharinglevelcourse', 'openstudio'));

        $mformselect = $mform->addElement('select', 'enabledvisibility',
            get_string('settingsenablesocialsharing', 'openstudio'), $sharringlevelarray);
        $mform->getElement('enabledvisibility')->setMultiple(true);

        $mformselect = $mform->addElement('select', 'defaultvisibility',
            get_string('settingssocialsharing', 'openstudio'),
            $sharringlevelarray);
        $mform->setDefault('defaultvisibility', 'private');

        if ($this->_cm) {
            $coursecontext = $this->context->get_parent_context();
        } else {
            $coursecontext = $this->context;
        }

        $tutorroles = get_assignable_roles($coursecontext, ROLENAME_ALIAS, false, get_admin());
        $tutorrolelabel = get_string('settingstutorroles', 'openstudio');
        $tutorrolesarray = array();
        foreach ($tutorroles as $id => $tutorrole) {
            $tutorrolesarray[] = $mform->createElement(
                'advcheckbox',
                $id,
                '',
                $tutorrole,
                array('group' => 1),
                array(0, 1));
        }
        $mform->addGroup($tutorrolesarray, 'tutorrolesgroup', $tutorrolelabel);
        $mform->addHelpButton('tutorrolesgroup', 'settingstutorroles', 'openstudio');

        $flagsarray = array(
            flags::FAVOURITE
            => get_string('settingssocialflagsfavourite', 'openstudio'),
            flags::INSPIREDME
            => get_string('settingssocialflagsinspiredme', 'openstudio'),
            flags::MADEMELAUGH
            => get_string('settingssocialflagsmademelaugh', 'openstudio'),
            flags::NEEDHELP
            => get_string('settingssocialflagsneedhelp', 'openstudio'),
            flags::COMMENT_LIKE
            => get_string('settingssocialflagcommentlike', 'openstudio'));
        $mformselect = $mform->addElement('select', 'enabledflags',
            get_string('settingssocialflags', 'openstudio'), $flagsarray);
        $mform->getElement('enabledflags')->setMultiple(true);

        // -------------------------------------------------------------------------------

        $mform->addElement('header', 'customfeatures',
            get_string('settingscustomfeatures', 'openstudio'));

        $mform->addElement('advcheckbox', 'enablemodule',
            get_string('settingsenablemodule', 'openstudio'),
            get_string('settingsenablemoduledescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('text', 'pinboard',
            get_string('settingsenablepinboard', 'openstudio'), array('size' => defaults::MAXPINBOARDCONTENTSLLENGTH));
        $mform->setType('pinboard', PARAM_INT);
        $mform->addRule('pinboard',
            get_string('err_numeric', 'form'), 'numeric', '', 'client');
        $mform->addRule('pinboard',
            get_string('err_maxlength', 'form', array('format' => defaults::MAXPINBOARDCONTENTSLLENGTH)),
            'maxlength', defaults::MAXPINBOARDCONTENTSLLENGTH, 'client');
        $mform->setDefault('pinboard', defaults::MAXPINBOARDCONTENTS);
        $mform->addHelpButton('pinboard', 'pinboard', 'openstudio');

        $mform->addElement('text', 'versioning',
            get_string('settingsenableversioning', 'openstudio'), array('size' => defaults::MAXCONTENTVERSIONSLENGTH));
        $mform->setType('versioning', PARAM_INT);
        $mform->addRule('versioning',
            get_string('err_numeric', 'form'), 'numeric', '', 'client');
        $mform->addRule('versioning',
            get_string('err_maxlength', 'form', array('format' => defaults::MAXCONTENTVERSIONSLENGTH)),
            'maxlength', defaults::MAXCONTENTVERSIONSLENGTH, 'client');
        $mform->setDefault('versioning', defaults::MAXCONTENTVERSIONS);
        $mform->addHelpButton('versioning', 'versioning', 'openstudio');

        // Checkbox option added to Custom Features to enable/disable the locking feature.
        $mform->addElement('advcheckbox', 'enablelocking',
            get_string('settingsenablelocking', 'openstudio'),
            get_string('settingsenablelockingdescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('hidden', 'copying', 0);
        $mform->setType('copying', PARAM_INT);

        $mform->addElement('advcheckbox', 'enablecontenthtml',
            get_string('settingsenablecontenthtml', 'openstudio'),
            get_string('settingsenablecontenthtmldescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enablecontentcommenthtml',
            get_string('settingsenablecontentcommenthtml', 'openstudio'),
            get_string('settingsenablecontentcommenthtmldescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enablecontentcommentaudio',
            get_string('settingsenablecontentcommentaudio', 'openstudio'),
            get_string('settingsenablecontentcommentaudiodescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enablecontentusesfileupload',
            get_string('settingsenablecontentusesfileupload', 'openstudio'),
            get_string('settingsenablecontentusesfileuploaddescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enablecontentusesweblink',
            get_string('settingsenablecontentusesweblink', 'openstudio'),
            get_string('settingsenablecontentusesweblinkdescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enablecontentusesembedcode',
            get_string('settingsenablecontentusesembedcode', 'openstudio'),
            get_string('settingsenablecontentusesembedcodedescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enablecontentallownotebooks',
            get_string('settingsenablecontentallownotebooks', 'openstudio'),
            get_string('settingsenablecontentallownotebooksdescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enablerss',
            get_string('settingsenablerss', 'openstudio'),
            get_string('settingsenablerssdescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enablesubscription',
            get_string('settingsenablesubscription', 'openstudio'),
            get_string('settingsenablesubscriptiondescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enableexportimport',
            get_string('settingsenableexportimport', 'openstudio'),
            get_string('settingsenableexportimportdescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enablecontentreciprocalaccess',
            get_string('settingsenablecontentreciprocalaccess', 'openstudio'),
            get_string('settingsenablecontentreciprocalaccessdescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enableparticipationsmiley',
            get_string('settingsenableparticipationsmiley', 'openstudio'),
            get_string('settingsenableparticipationsmileydescription', 'openstudio'),
            array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'enablefolders',
            get_string('settingsenablefolders', 'openstudio'), '&nbsp;');

        $mform->addElement('advcheckbox', 'enablefoldersanycontent',
            get_string('settingsenablefoldersanycontent', 'openstudio'),
            get_string('settingsenablefoldersanycontentdescription', 'openstudio'),
            array('group' => 1), array(0, 1));
        $mform->disabledIf('enablefoldersanycontent', 'enablefolders', 'neq', 1);

        $mform->addElement('text', 'pinboardfolderlimit',
            get_string('settingspinboardfolderscontentlimit', 'openstudio'),
            array('size' => defaults::MAXPINBOARDFOLDERSCONTENTSLENGTH));
        $mform->setType('pinboardfolderlimit', PARAM_INT);
        $mform->addRule('pinboardfolderlimit',
            get_string('err_numeric', 'form'), 'numeric', '', 'client');
        $mform->addRule('pinboardfolderlimit',
            get_string('err_maxlength', 'form', array('format' => defaults::MAXPINBOARDFOLDERSCONTENTSLENGTH)),
            'maxlength', defaults::MAXPINBOARDFOLDERSCONTENTSLENGTH, 'client');
        $mform->setDefault('pinboardfolderlimit', defaults::MAXPINBOARDFOLDERSCONTENTS);
        $mform->addHelpButton('pinboardfolderlimit', 'settingspinboardfolderscontentlimittext', 'openstudio');
        $mform->disabledIf('pinboardfolderlimit', 'enablefolders', 'neq', 1);

        $mform->addElement('text', 'reportingemail',
            get_string('settingsadditionalreportingemail', 'openstudio'),
            array('size' => '64'));
        $mform->setType('reportingemail', PARAM_TEXT);
        $mform->addRule('reportingemail',
            get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // -------------------------------------------------------------------------------

        $mform->addElement('header', 'customuploadsettings',
            get_string('settingscustomuploadsettings', 'openstudio'));

        // Get maxbytes from the module configuration if available,
        // otherwise from the course/site configuration if available, otherise set it to default.
        //
        // NOTE: the moodle form for file upload is hardwired to restrict the upload
        // setting to site, then course, then module. So even if you can sepcify the
        // size to be greater, it will be ignored.
        $maxupload = (int) (ini_get('upload_max_filesize'));
        $maxpost = (int) (ini_get('post_max_size'));
        $memorylimit = (int) (ini_get('memorylimit'));
        $serveruploadlimit = min($maxupload, $maxpost, $memorylimit) * 1024 * 1024;
        $sitemaxbytes = (int) ((isset($CFG->maxbytes) && ($CFG->maxbytes > 0)) ? $CFG->maxbytes : $serveruploadlimit);
        $coursemaxbytes = (int) ((isset($COURSE->maxbytes) && ($COURSE->maxbytes > 0)) ? $COURSE->maxbytes : $sitemaxbytes);
        $maxbytes = (int) (get_config('openstudio', 'maxbytes') ? get_config('openstudio', 'maxbytes') : $coursemaxbytes);
        if (($sitemaxbytes > 0) && ($coursemaxbytes > $sitemaxbytes)) {
            $coursemaxbytes = $sitemaxbytes;
        }
        if ($maxbytes > $sitemaxbytes) {
            $maxbytes = $sitemaxbytes;
        }
        if ($maxbytes > $coursemaxbytes) {
            $maxbytes = $coursemaxbytes;
        }
        $maxbytesdeafultarray = get_max_upload_sizes($coursemaxbytes);
        // Restrict the list of file upload sizes to the limit folder for the module/site.
        $maxbytesarray = array();
        foreach ($maxbytesdeafultarray as $key => $value) {
            $maxbytesarray[$key] = $value;
        }
        $mform->addElement('select', 'contentmaxbytes',
            get_string('settingscustomuploadsettingsfilesizelimit', 'openstudio'),
            $maxbytesarray);
        if (($maxbytes <= 0) || (defaults::MAXBYTES < $maxbytes)) {
            $mform->setDefault('contentmaxbytes', defaults::MAXBYTES);
        } else {
            $mform->setDefault('contentmaxbytes',
                ((defaults::MAXBYTES > $maxbytes) ? $maxbytes : defaults::MAXBYTES));
        }

        // -------------------------------------------------------------------------------

        $mform->addElement('hidden', 'returnurl', 0);
        $mform->setType('returnurl', PARAM_LOCALURL);

        $mform->addElement('hidden', 'themefeatures', 0);
        $mform->setType('themefeatures', PARAM_INT);

        // Add standard elements and buttons, common to all modules.
        $this->add_action_buttons();
    }

    /*
     * Any data processing needed before the form is displayed
     * (needed to set up draft areas for editor and filemanager elements).
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        if (isset($defaultvalues['allowedvisibility'])) {
            $defaultvalues['enabledvisibility'] = explode(",", $defaultvalues['allowedvisibility']);
        } else {
            $defaultvalues['enabledvisibility'] = array(content::VISIBILITY_PRIVATE,
                    content::VISIBILITY_TUTOR,
                    content::VISIBILITY_GROUP,
                    content::VISIBILITY_MODULE);
        }

        if (isset($defaultvalues['tutorroles'])) {
            $tutorroles = explode(",", $defaultvalues['tutorroles']);
            foreach ($tutorroles as $tutorrole) {
                $defaultvalues['tutorrolesgroup'][$tutorrole] = 1;
            }
        } else {
            if ($this->_cm) {
                $coursecontext = $this->context->get_parent_context();
            } else {
                $coursecontext = $this->context;
            }
            $tutorroles = get_assignable_roles($coursecontext, ROLENAME_ALIAS, false, get_admin());
            $tutorrole = array_search(get_string('settingstutorrolesdefault', 'openstudio'), $tutorroles);
            if ($tutorrole !== false) {
                $defaultvalues['tutorrolesgroup'][$tutorrole] = 1;
            }
        }

        if (isset($defaultvalues['flags'])) {
            $defaultvalues['enabledflags'] = explode(",", $defaultvalues['flags']);
        } else {
            $defaultvalues['enabledflags'] = array(flags::FAVOURITE,
                    flags::INSPIREDME,
                    flags::MADEMELAUGH,
                    flags::NEEDHELP,
                    flags::COMMENT_LIKE);
        }

        if ($defaultvalues['id'] > 0) {
            $themefeatures = $DB->get_field('openstudio', 'themefeatures', array('id' => $defaultvalues['id']));
            $defaultvalues['enablemodule'] = $themefeatures & feature::MODULE ? 1 : 0;
            $defaultvalues['enablecontenthtml'] = 1;
            $defaultvalues['enablecontentcommenthtml'] = 1;
            $defaultvalues['enablecontentcommentaudio'] = $themefeatures & feature::CONTENTCOMMENTUSESAUDIO ? 1 : 0;
            $defaultvalues['enablecontentusesfileupload'] = $themefeatures & feature::CONTENTUSESFILEUPLOAD ? 1 : 0;
            $defaultvalues['enablefolders'] = $themefeatures & feature::ENABLEFOLDERS ? 1 : 0;
            $defaultvalues['enablefoldersanycontent'] = $themefeatures & feature::ENABLEFOLDERSANYCONTENT ? 1 : 0;
            $defaultvalues['enablerss'] = $themefeatures & feature::ENABLERSS ? 1 : 0;
            $defaultvalues['enablesubscription'] = $themefeatures & feature::ENABLESUBSCRIPTION ? 1 : 0;
            $defaultvalues['enableexportimport'] = $themefeatures & feature::ENABLEEXPORTIMPORT ? 1 : 0;
            $defaultvalues['enablecontentusesweblink'] = $themefeatures & feature::CONTENTUSESWEBLINK ? 1 : 0;
            $defaultvalues['enablecontentusesembedcode'] = $themefeatures & feature::CONTENTUSESEMBEDCODE ? 1 : 0;
            $defaultvalues['enablecontentallownotebooks'] = $themefeatures & feature::CONTENTALLOWNOTEBOOKS ? 1 : 0;
            $defaultvalues['enablecontentreciprocalaccess'] = $themefeatures & feature::CONTENTRECIPROCALACCESS ? 1 : 0;
            $defaultvalues['enableparticipationsmiley'] = $themefeatures & feature::PARTICIPATIONSMILEY ? 1 : 0;
            $defaultvalues['enablelocking'] = $themefeatures & feature::ENABLELOCK ? 1 : 0;
        } else {
            $defaultvalues['enablemodule'] = 1;
            $defaultvalues['enablecontenthtml'] = 0;
            $defaultvalues['enablecontentcommenthtml'] = 0;
            $defaultvalues['enablecontentcommentaudio'] = 0;
            $defaultvalues['enablecontentusesfileupload'] = 1;
            $defaultvalues['enablefolders'] = 0;
            $defaultvalues['enablefoldersanycontent'] = 0;
            $defaultvalues['enablerss'] = 0;
            $defaultvalues['enablesubscription'] = 1;
            $defaultvalues['enableexportimport'] = 0;
            $defaultvalues['enablecontentusesweblink'] = 1;
            $defaultvalues['enablecontentusesembedcode'] = 1;
            $defaultvalues['enablecontentallownotebooks'] = 0;
            $defaultvalues['enablecontentreciprocalaccess'] = 0;
            $defaultvalues['enableparticipationsmiley'] = 0;
            $defaultvalues['enablelocking'] = 1;
        }
    }
}