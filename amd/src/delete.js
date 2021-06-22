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
 * @package mod_openstudio
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
    'require'
], function($, Ajax, Str, SiteConfig, require) {
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
        init: function(options) {

            t.mconfig = options;

            // Create delete dialog.
            Y.use('moodle-core-notification-dialogue', function() {
                require(['mod_openstudio/osdialogue'], function(osDialogue){
                    t.dialogue = t.createDeleteDialogue(osDialogue);
                });
            });

            // Click event on delete button.
            $(t.CSS.DELETEBUTTON).on('click', function() {
                if (t.dialogue) {
                    t.dialogue.show();
                }
            });

            // Responsive.
            $(window).resize(t.resize.bind(t));
        },

        /**
         * Create delete dialogue and some events on it.
         *
         * @param {object} osDialogue object
         * @return {object} OSDialogue instance
         * @method createDeleteDialogue
         */
        createDeleteDialogue: function(osDialogue) {
            /**
             * Set header for dialog
             * @method setHeader
             */
            function setHeader() {
                var langstring = (t.mconfig.isfolder) ? 'folderdeletedfolder' : 'contentdeledialogueteheader';
                Str
                    .get_string(langstring, 'mod_openstudio')
                    .done(function(s) {
                        dialogue.set('headerContent',
                            '<span class="' + t.CSS.DIALOGHEADER.replace('.', '') +
                            '">' + s + '</span>');
                    });
            }

            /**
             * Set body for dialog
             * @method setBody
             */
            function setBody() {
                var langstring = (t.mconfig.isfolder) ? 'deleteconfirmfolder' : 'deleteconfirmcontent';
                Str
                    .get_string(langstring, 'mod_openstudio')
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
                    classNames: 'openstudio-delete-cancel-btn',
                    action: 'hide'
                };

                // Button [Delete]
                var deleteBtnProperty = {
                    name: 'delete',
                    classNames: 'openstudio-delete-ok-btn',
                    events: {
                        click: t.deleteContent.bind(t)
                    }
                };

                Str
                    .get_strings([
                        {key: 'modulejsdialogcancel', component: 'mod_openstudio'},
                        {key: 'deletelevel', component: 'mod_openstudio'}
                    ])
                    .done(function(s) {
                        cancelBtnProperty.label = s[0];
                        deleteBtnProperty.label = s[1];
                        dialogue.addButton(deleteBtnProperty, ['footer']);
                        dialogue.addButton(cancelBtnProperty, ['footer']);
                    });
            }
            var hasFolderClass = '';
            if (t.mconfig.isfolder) {
                hasFolderClass = 'openstudio-folder';
            } else if(t.mconfig.folderid) {
                hasFolderClass = 'openstudio-is-belong-to-folder';
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
                extraClasses: [t.CSS.DELETEDIALOGUECONTAINER.replace('.', ''), hasFolderClass]
            });

            setHeader();
            setBody();
            setFooter();

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
