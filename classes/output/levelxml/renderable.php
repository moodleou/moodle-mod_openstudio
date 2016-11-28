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
 *
 *
 * @package
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\output;

use mod_openstudio\local\api\content;
use mod_openstudio\local\api\levels;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

class levelxml implements \templatable {

    /**
     * Hierachical data structure representing levels for import/export.
     *
     * Multi dimensional array:
     * ['blocks' => ['activities' => ['contents' => ['template' => ['contents' => []]]]]]
     *
     * @var array
     */
    private $levels = array();

    /**
     * Create a hierachical data structure based on the level records for the given studio ID.
     *
     * @param int $studioid The ID of the studio to search for level records.
     */
    public function create_from_data($studioid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/openstudio/api/set.php'); // TODO: Remove once this API is refactored.

        $this->levels = array('blocks' => array());

        $blockrecords = levels::get_records(1, $studioid);

        foreach ($blockrecords as $blockrecord) {
            $block = ['name' => $blockrecord->name, 'activities' => array()];

            $activityrecords = levels::get_records(2, $blockrecord->id);
            foreach ($activityrecords as $activityrecord) {
                $activity = [
                    'name' => $activityrecord->name,
                    'hidelevel?' => (bool) $activityrecord->hidelevel,
                    'contents' => array()
                ];
                $contentrecords = levels::get_records(3, $activityrecord->id);
                foreach ($contentrecords as $contentrecord) {
                    $content = [
                        'name' => $contentrecord->name,
                        'required?' => (bool) $contentrecord->required,
                        'contenttype' => $contentrecord->contenttype
                    ];
                    if ($contentrecord->contenttype == content::TYPE_FOLDER) {
                        $templaterecord = studio_api_set_template_get_by_levelid($contentrecord->id);
                        if ($templaterecord) {
                            $template = [
                                'guidance' => $templaterecord->guidance,
                                'additionalcontents' => $templaterecord->additionalcontents,
                                'contents' => array()
                            ];
                            $templatecontentrecords = studio_api_set_template_slots_get($template->id);
                            if (!empty($templatecontentrecords)) {
                                foreach ($templatecontentrecords as $templatecontentrecord) {
                                    $template->contents[] = [
                                        'name' => $templatecontentrecord->name,
                                        'permissions' => $templatecontentrecords->permissions,
                                        'contentorder' => $templatecontentrecord->contentorder
                                    ];
                                }
                            }
                            $content['template'] = $template;
                        }
                    }
                    $activity['contents'][] = $content;
                }
                $block['activities'][] = $activity;
            }
            $this->levels['blocks'][] = $block;
        }
    }

    /**
     * Parse an XML string and create a hierachical data structure representing levels.
     *
     * @param $xml
     * @return bool false if the XML is invalid.
     */
    public function create_from_xml($xml) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml);
        if ($xml === false) {
            return false;
        }

        $this->levels = array('blocks' => array());

        foreach ($xml->block as $block) {
            if (!isset($block->name) || (trim($block->name) == '')) {
                continue;
            }
            $blockdata = array('name' => $block->name);
            if (isset($block->activities)) {
                $blockdata['activities'] = array();
                if (isset($block->activities->activity)) {
                    foreach ($block->activities->activity as $activity) {
                        if (!isset($activity->name) || (trim($activity->name) == '')) {
                            continue;
                        }
                        $activitydata = array('name' => $activity->name);
                        if (isset($activity->hidelevel)) {
                            $activitydata['hidelevel'] = $activity->hidelevel;
                        }
                        if (isset($activity->contents)) {
                            if (isset($activity->contents->content)) {
                                $activitydata['contents'] = array();
                                foreach ($activity->contents->content as $content) {
                                    if (!isset($content->name) || (trim($content->name) == '')) {
                                        continue;
                                    }

                                    $contentdata = array('name' => $content->name);
                                    if (isset($content->required)) {
                                        $contentdata['required?'] = $content->required;
                                    }
                                    if (isset($content->contenttype)) {
                                        $contentdata['contentype'] = $content->contenttype;
                                        if ($content->contenttype == content::TYPE_FOLDER) {
                                            if (isset($content->template)) {
                                                $templatedata = array(
                                                    'guidance' => $content->template->guidance,
                                                    'additionalcontents' => $content->template->additionalcontents
                                                );
                                                if (isset($content->template->contents)) {
                                                    $contenttemplatedata = array();
                                                    foreach ($content->template->contents->content as $contenttemplate) {
                                                        $contenttemplatedata[] = array(
                                                            'name' => $contenttemplate->name,
                                                            'guidance' => $contenttemplate->guidance,
                                                            'permissions' => $contenttemplate->permissions,
                                                            'contentorder' => $contenttemplate->contentorder
                                                        );
                                                    }
                                                    $templatedata['contents'] = $contenttemplatedata;
                                                }
                                                $contentdata['template'] = $templatedata;
                                            }
                                        }
                                    }
                                    $activitydata['contents'][] = $contentdata;
                                }
                            }
                        }
                        $blockdata['activities'][] = $activitydata;
                    }
                }
            }
            $this->levels['blocks'][] = $blockdata;
        }
        return true;
    }

    /**
     * Return level data
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        return $this->levels;
    }
}
