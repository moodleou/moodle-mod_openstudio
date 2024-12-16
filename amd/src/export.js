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
 * JavaScript to manage export feature.
 *
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/export
 */
define([
    'jquery',
    'core/str',
    'core/config',
    'mod_openstudio/osdialogue'
], function($, Str, SiteConfig, osDialogue) {
    var t;
    t = {

        /**
         * Export dialogue instance.
         */
        dialogue: null,

        /**
         * Module config. Passed from server side.
         */
        mconfig: null,

        /**
         * List out all of css selector used in export module.
         */
        CSS: {
            DIALOGHEADER: '.openstudio-export-header',
            BOUNDINGBOX: '.openstudio-export-dialog-container',
            EXPORTBUTTON: '#osep-bottombutton-export',
            EXPORTMESSAGE: '.openstudio-export-message',
            SELECTALLPOST: 'button[name="openstudio-export-select-all"]',
            SELECTNONE: 'button[name="openstudio-export-select-none"]',
            EXPORTSELECTED: 'button[name="openstudio-export-selected"]',
            CHECKBOXS: '.openstudio-export-items input[type="checkbox"][data-contentid]'
        },

        /**
         * Initialize module.
         *
         * @param {JSON} options  The settings for module
         * @method init
         */
        init: async function(options) {
            t.mconfig = options;

            t.dialogue = await t.createExportDialogue();

            // Click event on export bottom button.
            $(t.CSS.EXPORTBUTTON).on('click', function(e) {
                e.preventDefault();
                t.dialogue.show();
            });

            $(t.CSS.SELECTALLPOST).on('click', t.selectAll.bind(t));
            $(t.CSS.SELECTNONE).on('click', t.selectNone.bind(t));
            $(t.CSS.EXPORTSELECTED).on('click', t.exportSelected.bind(t));
            $(t.CSS.CHECKBOXS).on('click', t.selectPost.bind(t));
        },

        /**
         * Create export dialogue and some events on it.
         *
         * @method createExportDialogue
         * @returns {Promise<Modal>}
         */
        createExportDialogue: async function() {
            /**
             * Set header for dialog.
             *
             * @param {Object} dialogue
             * @method setHeader
             */
            function setHeader(dialogue) {
                let headerString;
                Str
                    .get_string('exportdialogheader', 'mod_openstudio')
                    .done(function(s) {
                        headerString = s;
                    })
                    .always(function() {
                        dialogue.setTitle('<span class="' + t.CSS.DIALOGHEADER.replace('.', '') +
                            '">' + headerString + '</span>');
                    });
            }

            /**
             * Set body for dialog.
             *
             * @param {Object} dialogue
             * @method setBody
             */
            function setBody(dialogue) {
                let bodyString;
                let message;
                Str
                    .get_strings([
                        {key: 'exportdialogcontent', component: 'mod_openstudio'},
                        {key: 'export:emptycontent', component: 'mod_openstudio'}
                    ])
                    .done(function(s) {
                        bodyString = s[0];
                        message = s[1];
                    })
                    .always(function() {
                        bodyString += '<div class="' + t.CSS.EXPORTMESSAGE.replace('.', '') + '"></div>';
                        dialogue.setBody(bodyString);

                        $(t.CSS.EXPORTMESSAGE).text(message);
                    });
            }

            /**
             * Set body for dialog.
             *
             * @param {Object} dialogue
             * @method setBody
             */
            function setFooter(dialogue) {
                // Button [All content shown].
                const exportAllBtnProperty = {
                    name: 'exportall',
                    classNames: 'openstudio-exportall-btn',
                    events: {
                        click: t.exportAll.bind(t)
                    }
                };

                // Button [Selected posted].
                const exportSelectedBtnProperty = {
                    name: 'exportselected',
                    classNames: 'openstudio-exportselected-btn',
                    events: {
                        click: function() {
                            const contentIds = t.mconfig.contentids.join();
                            if (contentIds) {
                                t.redirectPostRequest({
                                    url: SiteConfig.wwwroot + '/mod/openstudio/exportposts.php',
                                    data: {
                                        id: t.mconfig.id,
                                        vid: t.mconfig.vid,
                                        contentids: contentIds
                                    }
                                });
                            } else {
                                $(t.CSS.EXPORTMESSAGE).show();
                            }
                        }
                    }
                };

                // Button [Cancel]
                const cancelBtnProperty = {
                    name: 'cancel',
                    classNames: 'openstudio-cancelexport-btn',
                    action: 'hide'
                };

                Str
                    .get_strings([
                        {key: 'exportall', component: 'mod_openstudio'},
                        {key: 'exportselectedpost', component: 'mod_openstudio'},
                        {key: 'modulejsdialogcancel', component: 'mod_openstudio'},
                    ])
                    .done(function(s) {
                        exportAllBtnProperty.label = s[0];
                        exportSelectedBtnProperty.label = s[1];
                        cancelBtnProperty.label = s[2];
                    })
                    .always(function() {
                        dialogue.addButton(exportAllBtnProperty, ['footer']);
                        dialogue.addButton(exportSelectedBtnProperty, ['footer']);
                        dialogue.addButton(cancelBtnProperty, ['footer']);
                    });
            }

            const dialogue = await osDialogue.create({
                isVerticallyCentered: true,
                templateContext: {
                    extraClasses: t.CSS.BOUNDINGBOX.replace('.', ''),
                },
            });

            setHeader(dialogue);
            setBody(dialogue);
            setFooter(dialogue);

            return dialogue;
        },

        /**
         * Export all
         * @method exportAll
         */
        exportAll: function() {
            var contentids = t.mconfig.contentids.join();
            if (contentids) {
                t.redirectPostRequest({
                    url: SiteConfig.wwwroot + '/mod/openstudio/export.php',
                    data: {
                        id: t.mconfig.id,
                        contentids: contentids
                    }
                });
            } else {
                $(t.CSS.EXPORTMESSAGE).show();
            }
        },

        /**
         * Export selected posts
         * @method exportSelected
         */
        exportSelected: function() {
            var contentids = [];
            $(t.CSS.CHECKBOXS).each(function() {
                if ($(this).prop('checked') == true) {
                    contentids.push($(this).data('contentid'));
                }
            });

            if (contentids.length > 0) {
                t.redirectPostRequest({
                    url: SiteConfig.wwwroot + '/mod/openstudio/export.php',
                    data: {
                        id: t.mconfig.id,
                        contentids: contentids.join()
                    }
                });
            }
        },

        /**
         * Redirect to url with post method.
         * @method redirectPostRequest
         * @param {JSON} options Request data
         */
        redirectPostRequest: function(options) {
            var form = $('<form></form>');
            form.attr({
                'method': 'POST',
                'action': options.url,
                'style': 'display: none;'
            });

            $.each(options.data, function(key, value) {
                var field = $('<input/>');
                field.attr("type", "hidden");
                field.attr("name", key);
                field.attr("value", value);

                form.append(field);
            });

            $(form).appendTo('body').submit();
        },

        /**
         * Select all posts
         * @method selectAll
         */
        selectAll: function() {
            $(t.CSS.CHECKBOXS).prop('checked', true);
            $(t.CSS.SELECTALLPOST).addClass('disabled');
            $(t.CSS.EXPORTSELECTED).removeClass('disabled');
            $(t.CSS.SELECTNONE).removeClass('disabled');
        },

        /**
         * Remove all selected posts
         * @method selectNone
         */
        selectNone: function() {
            $(t.CSS.CHECKBOXS).prop('checked', false);
            $(t.CSS.SELECTALLPOST).removeClass('disabled');
            $(t.CSS.EXPORTSELECTED).addClass('disabled');
            $(t.CSS.SELECTNONE).addClass('disabled');
        },

        /**
         * Select a post
         * @method selectPost
         */
        selectPost: function() {
            var uncheckBoxes = 0;
            var boxquantity = 0;
            $(t.CSS.CHECKBOXS).each(function() {
                if ($(this).prop('checked') == false) {
                    uncheckBoxes++;
                }

                boxquantity++;
            });
            $(t.CSS.SELECTALLPOST).toggleClass('disabled', uncheckBoxes == 0);
            $(t.CSS.SELECTNONE).toggleClass('disabled', uncheckBoxes == boxquantity);
            $(t.CSS.EXPORTSELECTED).toggleClass('disabled', uncheckBoxes == boxquantity);
        }
    };

    return t;
});