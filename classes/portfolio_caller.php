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

namespace mod_openstudio;

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\export;
use mod_openstudio\local\api\stream;
use mod_openstudio\local\api\contentversion;
use mod_openstudio\local\api\folder;
use mod_openstudio\local\api\lock;
use mod_openstudio\local\util;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/portfolio/caller.php');

/**
 * Portfolio callback class for openstudio exports.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class portfolio_caller extends \portfolio_module_caller_base {

    protected $studioid;
    protected $contentids;
    protected $contents = [];
    protected $foldercontents = [];
    protected $files = [];
    protected $fs;
    protected $context;

    public static function expected_callbackargs() {
        return ['studioid' => true, 'contentids' => true];
    }

    public static function display_name() {
        return get_string('modulename', 'openstudio');
    }

    public function __construct(array $callbackargs) {
        parent::__construct($callbackargs);
        $this->cm = get_coursemodule_from_instance('openstudio', $this->studioid);
        $this->contentids = export::decode_ids($this->contentids);
        $this->supportedformats = [PORTFOLIO_FORMAT_RICHHTML];
        $this->fs = get_file_storage();
        $this->context = \context_module::instance($this->cm->id);
    }

    public function load_data() {
        global $PAGE, $USER;

        $contents = array();

        $coursedata = util::render_page_init($this->cm->id);
        $renderer = $PAGE->get_renderer('mod_openstudio');

        $showdeletedcontentversions = ($coursedata->permissions->viewdeleted || $coursedata->permissions->managecontent);

        foreach ($this->contentids as $contentid) {
            // Get content data.
            $contentandversions = contentversion::get_content_and_versions($contentid, $USER->id, $showdeletedcontentversions);
            $contentdata = lock::determine_lock_status($contentandversions->contentdata);
            $contentdata->contentversions = array_values($contentandversions->contentversions);
            $contentdata->vid = $contentdata->visibilitycontext;
            $contentdata->iscontentversion = false;
            $contentdata->isarchiveversion = false;
            $folderid = 0;
            $containingfolder = folder::get_containing_folder($contentdata->id);
            if ($containingfolder) {
                $folderid = $containingfolder->id;
            }
            $contentdata->folderid = $folderid;
            // After all, call renderer to get content page.
            $contentdata->contentpage = $renderer->content_page($coursedata->cm->id, $coursedata->permissions,
                    $contentdata, $coursedata->cminstance);
            $contents[] = $contentdata;
        }

        $this->load_contents($contents);
        if (empty($this->files)) {
            // No attached files to export, we'll just be generating HTML pages for the content.  Create a dummy file to
            // make sure $this->multifiles is set up correctly.  It's in the user's draft area, so cleaned up by cron.
            $dummyfile = [
                'contextid' => \context_user::instance($USER->id)->id,
                'component' => 'user',
                'filearea' => 'draft',
                'filename' => 'dummyfile',
                'filepath' => '/',
                'itemid' => file_get_unused_draft_itemid()
            ];
            $this->files[] = $this->fs->create_file_from_string($dummyfile, '');
        }
        $this->set_file_and_format_data($this->files);
    }

    public function prepare_package() {
        global $OUTPUT;
        if (empty($this->multifiles) && !empty($this->singlefile)) {
            $this->multifiles = array($this->singlefile);
        }
        if (!empty($this->multifiles)) {
            foreach ($this->multifiles as $file) {
                if ($file->get_filename() !== 'dummyfile') {
                    $this->exporter->copy_existing_file($file);
                }
            }
        }
        $filedir = $this->exporter->get('format')->get_file_directory();
        foreach ($this->contents as $content) {
            $content->content = $filedir . $content->content;
            $contentpage = $OUTPUT->render_from_template('mod_openstudio/export', ['content' => $content]);
            if (!empty($content->folderid)) {
                $suffix = ' - Folder ' . $content->folderid;
            } else {
                $suffix = '';
            }
            $contentfile = $this->exporter->write_new_file($contentpage, $this->generate_filename($content, 'html', $suffix));
            $this->multifiles[] = $contentfile;
            if ($content->contenttype === content::TYPE_FOLDER) {
                $folderlinks = [];
                foreach ($this->foldercontents[$content->id] as $foldercontent) {
                    $folderlinks[] = ['name' => $foldercontent->name, 'url' => $this->generate_filename($foldercontent, 'html')];
                }
                $templatedata = ['title' => $content->name . ' Contents', 'links' => $folderlinks];
                $folderpage = $OUTPUT->render_from_template('mod_openstudio/folderexport', $templatedata);
                $folderfile = $this->exporter->write_new_file($folderpage, $this->generate_filename($content, 'html', ' Contents'));
                $this->multifiles[] = $folderfile;
            }
        }
    }

    public function expected_time() {
        if (empty($this->multifiles)) {
            return portfolio_expected_time_file($this->singlefile);
        } else {
            return portfolio_expected_time_file($this->multifiles);
        }
    }

    public function check_permissions() {
        return has_capability('mod/openstudio:export', \context_module::instance($this->cm->id));
    }

    public function get_sha1() {
        return sha1($this->get_sha1_file());
    }

    public static function base_supported_formats() {
        return [PORTFOLIO_FORMAT_RICHHTML];
    }

    private function load_contents($contents, $folderid = null) {
        foreach ($contents as $content) {
            if ($content->userid != $this->user->id) {
                throw new \portfolio_caller_exception('exportwronguser', 'openstudio', '', $content->name);
            }
            if ($content->contenttype == content::TYPE_FOLDER) {
                $foldercontents = folder::get_contents($content->id);
                $this->load_contents($foldercontents, $content->id);
            } else {
                if (!empty($content->fileid)) {
                    $this->files[] = $this->fs->get_file($this->context->id, 'mod_openstudio', 'content',
                            $content->fileid, '/', $content->content);
                }
            }
            $content = util::add_additional_content_data($content, true);
            $content->folderid = $folderid;
            $this->contents[] = $content;
            if ($folderid) {
                $this->foldercontents[$folderid] = $content;
            }
        }
    }

    private function generate_filename($content, $extension, $suffix = '') {
        return $content->id . ' - ' . $content->name . $suffix . '.' . $extension;
    }
}
