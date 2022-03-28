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
 * JavaScript to manage folder activity guidance.
 *
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/folderactivityguidance
 */

define(['jquery','core/str', 'core/modal_factory'], function($, Str, ModalFactory) {
    var t;
    var dialogue;

    t = {

        CSS: {
            FOLDER_ACTIVITY_GUIDANCE: '#folder_activity_guidance',
            ACTIVITY_GUIDANCE_BUTTON: '#id_activity_guidance'
        },

        /**
         * Initialise this module.
         *
         * @method init
         * @param {JSON} options  The settings for this feature.
         */
        init: function(options) {

            t.mconfig = options;

            $(t.CSS.ACTIVITY_GUIDANCE_BUTTON).on('click', function() {
                // Create activity guidance dialogue.
                if (dialogue) {
                    dialogue.show();
                } else {
                    Str
                        .get_string('folderactivityguidance', 'mod_openstudio')
                        .done(function(s) {
                            ModalFactory.create({
                                title: s,
                                body: $(t.CSS.FOLDER_ACTIVITY_GUIDANCE).html()
                            }).done(function (modal) {
                                dialogue = modal;

                                // Display the dialogue.
                                dialogue.show();
                            });
                        });
                }
            });

        }
    };

    return t;

});
