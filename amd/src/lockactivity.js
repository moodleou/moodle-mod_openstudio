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
    'core/str'
], function($, Ajax, Str) {
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
         * Initialize module.
         *
         * @method init
         * @param {JSON} options  The settings for lock feature.
         */
        init: function(options) {

            t.mconfig = options;

            // Create unlock activity dialog.
            Y.use('moodle-core-notification-dialogue', function() {
                require(['mod_openstudio/osdialogue'], function(osDialogue) {
                    t.dialogue = t.createUnlockActivityDialogue(osDialogue);
                });
            });

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

            // Responsive.
            $(window).resize(t.resize.bind(t));
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
         * @param {object} osDialogue object
         * @return {object} OSDialogue instance
         * @method createUnlockActivityDialogue
         */
        createUnlockActivityDialogue: function(osDialogue) {
            /**
             * Set header for dialog
             * @method setHeader
             */
            function setHeader() {
                Str
                    .get_string('contentactionunlockname', 'mod_openstudio')
                    .done(function(s) {
                        dialogue.set('headerContent', '<span>' + s + '</span>');
                    });
            }

            /**
             * Set body for dialog
             * @method setBody
             */
            function setBody() {
                Str
                    .get_string('modulejsdialogcontentunlock', 'mod_openstudio')
                    .done(function(s) {
                        dialogue.set('bodyContent', s);
                    });
            }

            /**
             * Set body for dialog
             * @method setBody
             */
            function setFooter() {
                // Button [Cancel]
                var cancelBtnProperty = {
                    name: 'cancel',
                    action: 'hide'
                };

                // Button [Unlock]
                var unlockBtnProperty = {
                    name: 'lock',
                    classNames: t.CSS.ACTIVITY_UNLOCK_BUTTON.replace('.', '')
                };

                Str
                    .get_strings([
                        {key: 'modulejsdialogcancel', component: 'mod_openstudio'},
                        {key: 'contentactionunlockname', component: 'mod_openstudio'}
                    ])
                    .done(function(s) {
                        cancelBtnProperty.label = s[0];
                        unlockBtnProperty.label = s[1];
                        dialogue.addButton(unlockBtnProperty, ['footer']);
                        dialogue.addButton(cancelBtnProperty, ['footer']);
                    });
            }

            var dialogue = new osDialogue({
                closeButton: true,
                visible: false,
                centered: true,
                modal: true,
                responsive: true,
                width: 640,
                responsiveWidth: 767,
                focusOnPreviousTargetAfterHide: true
            });

            setHeader();
            setBody();
            setFooter();

            return dialogue;
        },

        /**
         * Resize and update dialogue position.
         * @method resize
         */
        resize: function() {
            if (!t.dialogue) {
                return;
            }

            if (t.dialogue.get('visible')) {
                if (Y.one('body').get('winWidth') <= t.dialogue.get('responsiveWidth')) {
                    t.dialogue.makeResponsive();
                } else {
                    t.dialogue.centerDialogue();
                }
            }
        }
    };

    return t;
});
