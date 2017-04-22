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
 * @package mod_openstudio
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
    'core/templates',
    'require'
], function($, Ajax, Str, Templates, require) {
    var t;
    t = {

        /**
         * Module config. Passed from server side.
         * {
         *     deletedposts: [
         *         thumnail: string,
          *        title: string,
          *        date: string
         *     ]
         * }
         */
        mconfig: null,

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
        init: function(options) {

            t.mconfig = options;

            // Create delete dialog.
            Y.use('moodle-core-notification-dialogue', function() {
                require(['mod_openstudio/osdialogue'], function(osDialogue) {
                    t.dialogue = t.createDeletedPostDialogue(osDialogue);
                });
            });

            // Click event on delete button.
            $(t.CSS.VIEW_DELETED_BUTTON).on('click', function() {
                if (t.dialogue) {
                    t.dialogue.show();
                    t.resize();
                }
            });

            $('body')
                .delegate(t.CSS.RESTORE_BUTTON, 'click', t.restoreContent);

            // Responsive.
            $(window).resize(t.resize.bind(t));
        },

        /**
         * Create delete dialogue and some events on it.
         *
         * @param {object} osDialogue object
         * @return {object} OSDialogue instance
         * @method createDeletedPostDialogue
         */
        createDeletedPostDialogue: function(osDialogue) {
            /**
             * Set header for dialog
             * @method setHeader
             */
            function setHeader() {
                Str
                    .get_string('folderdeletedposts', 'mod_openstudio')
                    .done(function(s) {
                        dialogue.set('headerContent', '<span>' + s + '</span>');
                    });
            }

            /**
             * Set body for dialog
             * @method setBody
             */
            function setBody() {
                Templates
                    .render('mod_openstudio/viewdeleted_dialog', {
                        'deletedposts': t.mconfig.deletedposts
                    })
                    .done(function(html) {
                        dialogue.set('bodyContent', html);
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
                focusOnPreviousTargetAfterHide: true,
                extraClasses: [t.CSS.BOUNDINGCLASS.replace('.', ''), 'openstudio-folder-posts-dialogue']
            });

            setHeader();
            setBody();

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

        /**
         * Resize and update dialogue position.
         * @method resize
         */
        resize: function() {
            if (!t.dialogue) {
                return;
            }

            if (t.dialogue.get('visible')) {
                if (Y.one('body').get('winWidth') < t.dialogue.get('responsiveWidth')) {
                    t.dialogue.makeResponsive();
                    $(t.CSS.RESTORE_BUTTON).removeClass('osep-smallbutton');
                } else {
                    $(t.CSS.RESTORE_BUTTON).addClass('osep-smallbutton');
                    if (Y.one('body').get('winWidth') >= 1000) {
                        t.dialogue.set('width', 640);
                    } else {
                        t.dialogue.set('width', 500);
                    }
                    t.dialogue.centerDialogue();
                }
            }
        }
    };

    return t;
});
