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
 * JavaScript to manage lock activity content.
 *
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/lockactivity
 */
define([
    'jquery',
    'core/ajax',
    'core/str',
    'mod_openstudio/osdialogue',
], function($, Ajax, Str, osDialogue) {
    var t;
    t = {

        /**
         * Module config. Passed from server side.
         * {
         *     cmid: int
         */
        mconfig: null,

        /**
         * List out all of css selector used in lock module.
         */
        CSS: {
            ACTIVITY_UNLOCK_ICON: 'span[name="activitylockbutton"]',
            ACTIVITY_UNLOCK_BUTTON: '.openstudio-unlock-ok-btn'
        },

        /**
         * Unlock activity dialogue instance.
         */
        dialogue: null,

        /**
         * Initialize module.
         *
         * @method init
         * @param {JSON} options  The settings for lock feature.
         */
        init: async function(options) {

            t.mconfig = options;

            // Create unlock activity dialog.
            t.dialogue = await t.createUnlockActivityDialogue();

            // Activity lock button.
            $(t.CSS.ACTIVITY_UNLOCK_ICON).on('click', function() {
                if (t.dialogue) {
                    $(t.CSS.ACTIVITY_UNLOCK_BUTTON)
                        .attr('data-level3id', $(this).attr('data-level3id'))
                        .attr('data-vuid', $(this).attr('data-vuid'));
                    t.dialogue.show();
                }
            });

            $('body').delegate(t.CSS.ACTIVITY_UNLOCK_BUTTON, 'click', t.unlockActivityContent);
        },

        /**
         * Unlock activity content then reload the page.
         * @method unlockActivityContent
         */
        unlockActivityContent: function() {
            var level3id = $(this).attr('data-level3id');
            var vuid = $(this).attr('data-vuid');

            M.util.js_pending('openstudioUnlockActivity');

            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_unlock_override_activity',
                args: {
                    cmid: t.mconfig.cmid,
                    level3id: level3id,
                    vuid: vuid
                }
            }]);

            promises[0]
                .done(function() {
                    window.location.reload();
                })
                .always(function() {
                    M.util.js_complete('openstudioUnlockActivity');
                })
                .fail(function(ex) {
                    window.console.error('Log request failed ' + ex.message);
                });
        },

        /**
         * Create unlock activity dialogue and some events on it.
         *
         * @returns {Promise<Modal>}
         * @method createUnlockActivityDialogue
         */
        createUnlockActivityDialogue: async function() {
            /**
             * Set header for dialog.
             *
             * @param {Object} dialogue
             * @method setHeader
             */
            function setHeader(dialogue) {
                Str
                    .get_string('contentactionunlockname', 'mod_openstudio')
                    .done(function(s) {
                        dialogue.setTitle('<span>' + s + '</span>');
                    });
            }

            /**
             * Set body for dialog.
             *
             * @param {Object} dialogue
             * @method setBody
             */
            function setBody(dialogue) {
                Str
                    .get_string('modulejsdialogcontentunlock', 'mod_openstudio')
                    .done(function(s) {
                        dialogue.setBody(s);
                    });
            }

            /**
             * Set footer for dialog.
             *
             * @param {Object} dialogue
             * @method setFooter
             */
            function setFooter(dialogue) {
                // Button [Cancel].
                const cancelBtnProperty = {
                    name: 'cancel',
                    action: 'hide',
                };

                // Button [Unlock].
                const unlockBtnProperty = {
                    name: 'lock',
                    classNames: t.CSS.ACTIVITY_UNLOCK_BUTTON.replace('.', ''),
                };

                Str
                    .get_strings([
                        {key: 'modulejsdialogcancel', component: 'mod_openstudio'},
                        {key: 'contentactionunlockname', component: 'mod_openstudio'},
                    ])
                    .done(function(s) {
                        cancelBtnProperty.label = s[0];
                        unlockBtnProperty.label = s[1];
                        dialogue.addButton(unlockBtnProperty, ['footer']);
                        dialogue.addButton(cancelBtnProperty, ['footer']);
                    });
            }

            const dialogue = await osDialogue.create({
                isVerticallyCentered: true,
            });

            setHeader(dialogue);
            setBody(dialogue);
            setFooter(dialogue);

            return dialogue;
        },
    };

    return t;
});
