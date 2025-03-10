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
 * JavaScript to view deleted contents feature.
 *
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/viewdeleted
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
         *     cmid: int,
         *     folderid: int
         * }
         */
        mconfig: null,

        /**
         * Deleted posts dialogue instance.
         */
        dialogue: null,

        /**
         * List out all of css selector used in this module.
         */
        CSS: {
            BOUNDINGCLASS: '.openstudio-view-deleted-dialogue',
            VIEW_DELETED_BUTTON: '#id_viewdeletedpostbutton',
            RESTORE_BUTTON: '.openstudio-restore-deleted-button'
        },

        /**
         * Initialize module.
         *
         * @method init
         * @param {JSON} options  The settings for this feature.
         */
        init: async function(options) {

            t.mconfig = options;

            // Create delete dialog.
            t.dialogue = await t.createDeletedPostDialogue();

            // Click event on delete button.
            $(t.CSS.VIEW_DELETED_BUTTON).on('click', function() {
                if (t.dialogue) {
                    t.dialogue.show();
                }
            });

            $('body')
                .delegate(t.CSS.RESTORE_BUTTON, 'click', t.restoreContent);
        },

        /**
         * Create delete dialogue and some events on it.
         *
         * @returns {Promise<Modal>}
         * @method createDeletedPostDialogue
         */
        createDeletedPostDialogue: async function() {
            /**
             * Set header for dialog.
             *
             * @param {Object} dialogue
             * @method setHeader
             */
            function setHeader(dialogue) {
                Str
                    .get_string('folderdeletedposts', 'mod_openstudio')
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

                M.util.js_pending('openstudioFetchDeletedContent');

                const promises = Ajax.call([{
                    methodname: 'mod_openstudio_external_fetch_deleted_posts_in_folder',
                    args: {
                        cmid: t.mconfig.cmid,
                        folderid: t.mconfig.folderid,
                    },
                }]);

                promises[0]
                    .done(function(html) {
                        dialogue.setBody(html);
                    })
                    .always(function() {
                        M.util.js_complete('openstudioFetchDeletedContent');
                    })
                    .fail(function(ex) {
                        window.console.error('Log request failed ' + ex.message);
                    });
            }
            const dialogue = await osDialogue.create({
                isVerticallyCentered: true,
                templateContext: {
                    extraClasses: t.CSS.BOUNDINGCLASS.replace('.', '') + ' openstudio-folder-posts-dialogue',
                },
            });

            setHeader(dialogue);
            setBody(dialogue);

            return dialogue;
        },

        /**
         * Restore content
         * @method restoreContent
         */
        restoreContent: function() {
            var cvid = $(this).data('content-version-id');
            cvid = parseInt(cvid);

            M.util.js_pending('openstudioRestoreContent');

            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_restore_content_in_folder',
                args: {
                    cmid: t.mconfig.cmid,
                    cvid: cvid,
                    folderid: t.mconfig.folderid
                }
            }]);

            promises[0]
                .done(function() {
                    window.location.reload();
                })
                .always(function() {
                    M.util.js_complete('openstudioRestoreContent');
                })
                .fail(function(ex) {
                    window.console.error('Log request failed ' + ex.message);
                });
        },
    };

    return t;
});
