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

/**
 * Hook callbacks.
 *
 * @package mod_oucontent
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Called when the system wants to find out if an activity is searchable, to decide whether to
     * display a search box in the header.
     *
     * @param \local_moodleglobalsearch\hook\activity_search_info $hook
     */
    public static function activity_search_info(\local_moodleglobalsearch\hook\activity_search_info $hook) {
        global $DB;

        // For OpenStudio, offer search on basically any page within the module.
        if ($hook->is_modname('openstudio') &&
                preg_match('~^mod-openstudio-~', $hook->get_page_type())) {

            // Placeholder text depends on view mode.
            $vid = optional_param('vid', content::VISIBILITY_MODULE, PARAM_INT);
            switch ($vid) {
                default:
                case content::VISIBILITY_MODULE:
                    $fieldname = 'thememodulename';
                    $strname = 'settingsthemehomesettingsmodule';
                    break;

                case content::VISIBILITY_GROUP:
                    $fieldname = 'themegroupname';
                    $strname = 'settingsthemehomesettingsgroup';
                    break;

                case content::VISIBILITY_WORKSPACE:
                case content::VISIBILITY_PRIVATE:
                    $fieldname = 'themestudioname';
                    $strname = 'settingsthemehomesettingsstudio';
                    break;

                case content::VISIBILITY_PRIVATE_PINBOARD:
                    $fieldname = 'themepinboardname';
                    $strname = 'settingsthemehomesettingspinboard';
                    break;
            }
            $placeholdertext = $DB->get_field('openstudio', $fieldname,
                ['id' => $hook->get_cm()->instance], MUST_EXIST);
            if (!$placeholdertext) {
                $placeholdertext = get_string($strname, 'openstudio');
            }
            $hook->enable_search($placeholdertext);

            // OpenStudio uses a custom search page.
            $hook->set_form('/mod/openstudio/search.php');
            $hook->add_param('id', $hook->get_cm()->id);
            $hook->add_param('vid', $vid);
            $hook->add_param('groupid', optional_param('groupid', 0, PARAM_INT));
        }
    }

}
