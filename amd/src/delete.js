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
 * JavaScript to manage delete content feature.
 *
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/delete
 */
define([
    'jquery',
    'core/ajax',
    'core/str',
    'core/config',
    'mod_openstudio/osdialogue',
], function($, Ajax, Str, SiteConfig, osDialogue) {
    var t;
    t = {

        /**
         * Module config. Passed from server side.
         * {
         *     id: int,
         *     folderid: int,
         *     isfolder: bool,
         *     folderid: int (optional),
         *     isactivitycontent: bool
         *
         * }
         */
        mconfig: null,

        /**
         * Delete dialogue instance.
         */
        dialogue: null,

        /**
         * List out all of css selector used in this module.
         */
        CSS: {
            DELETEBUTTON: '#id_deletebutton',
            DIALOGHEADER: '.openstudio-dialogue-common-header',
            DELETEDIALOGUECONTAINER: '.openstudio-delete-dialogue'
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
            t.dialogue = await t.createDeleteDialogue();

            // Click event on delete button.
            $(t.CSS.DELETEBUTTON).on('click', function() {
                if (t.dialogue) {
                    t.dialogue.show();
                }
            });
        },

        /**
         * Create delete dialogue and some events on it.
         *
         * @method createDeleteDialogue
         * @returns {Promise<Modal>}
         */
        createDeleteDialogue: async function() {
            /**
             * Set header for dialog.
             *
             * @param {Object} dialogue
             * @method setHeader
             */
            function setHeader(dialogue) {
                const langString = (t.mconfig.isfolder) ? 'folderdeletedfolder' : 'contentdeledialogueteheader';
                Str
                    .get_string(langString, 'mod_openstudio')
                    .done(function(s) {
                        dialogue.setTitle('<span class="' + t.CSS.DIALOGHEADER.replace('.', '') +
                            '">' + s + '</span>');
                    });
            }

            /**
             * Set body for dialog.
             *
             * @param {Object} dialogue
             * @method setBody
             */
            function setBody(dialogue) {
                const langString = (t.mconfig.isfolder) ? 'deleteconfirmfolder' : 'deleteconfirmcontent';
                Str
                    .get_string(langString, 'mod_openstudio')
                    .done(function(s) {
                        dialogue.setBody(s);
                    });
            }

            /**
             * Set body for dialog.
             *
             * @param {Object} dialogue
             * @method setBody
             */
            function setFooter(dialogue) {
                // Button [Cancel].
                const cancelBtnProperty = {
                    name: 'cancel',
                    classNames: 'openstudio-delete-cancel-btn',
                    action: 'hide',
                };

                // Button [Delete].
                const deleteBtnProperty = {
                    name: 'delete',
                    classNames: 'openstudio-delete-ok-btn',
                    events: {
                        click: t.deleteContent.bind(t),
                    },
                };

                Str
                    .get_strings([
                        {key: 'modulejsdialogcancel', component: 'mod_openstudio'},
                        {key: 'deletelevel', component: 'mod_openstudio'},
                    ])
                    .done(function(s) {
                        cancelBtnProperty.label = s[0];
                        deleteBtnProperty.label = s[1];
                        dialogue.addButton(deleteBtnProperty, ['footer']);
                        dialogue.addButton(cancelBtnProperty, ['footer']);
                    });
            }
            let hasFolderClass = '';
            if (t.mconfig.isfolder) {
                hasFolderClass = 'openstudio-folder';
            } else if(t.mconfig.folderid) {
                hasFolderClass = 'openstudio-is-belong-to-folder';
            }

            const dialogue = await osDialogue.create({
                isVerticallyCentered: true,
                templateContext: {
                    extraClasses: t.CSS.DELETEDIALOGUECONTAINER.replace('.', '') + ' ' + hasFolderClass
                },
            });

            setHeader(dialogue);
            setBody(dialogue);
            setFooter(dialogue);

            return dialogue;
        },

        /**
         * Delete content
         * @method deleteContent
         */
        deleteContent: function() {

            M.util.js_pending('openstudioDeleteContent');

            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_delete_content',
                args: {
                    id: t.mconfig.id,
                    cid: t.mconfig.cid,
                    containingfolder: t.mconfig.folderid
                }
            }]);

            promises[0]
                .done(function(res) {
                    var url = '';
                    if (t.mconfig.folderid) {
                        url = SiteConfig.wwwroot + '/mod/openstudio/folder.php?sid=' + t.mconfig.folderid;
                    } else {
                        if (t.mconfig.isactivitycontent) {
                            url = window.location.href;
                        } else {
                            url = SiteConfig.wwwroot + '/mod/openstudio/view.php?vid=' + res.vid;
                        }
                    }

                    url += '&id=' + t.mconfig.id;

                    // Redirect to containing view.
                    window.location.href = url;
                })
                .always(function() {
                    M.util.js_complete('openstudioDeleteContent');
                })
                .fail(function(ex) {
                    window.console.error('Log request failed ' + ex.message);
                });
        },
    };

    return t;
});
