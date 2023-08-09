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
 * JavaScript to manage content of social block.
 *
 * @package
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/content_block_social
 */
define([
    'jquery',
    'core/templates',
    'core/notification',
    'core/ajax'
], function($, Templates, displayException, Ajax) {
    var t;
    t = {

        /**
         * List out all of css selector used in this module.
         */
        CSS: {
            // Buttons.
            POPUP_COMMENT_CONTENT: '.show-popup-comment',
            POPUP_COMMENT_TOGGLE: '.comments-content-popup',
            COMMENT_BLOCK_LIST: '.comment-block-list',
            FLAG_CONTENT_BLOCK: '.flag_content_block',
            FLAG_CONTENT_TEXT: '.openstudio-grid-item-content-detail-info-text',
            FLAG_CONTENT_TEXT_NEW: '.openstudio-grid-item-content-detail-info-text-new',
            FLAG_CONTENT_ICON_IMAGE: 'img',
            FLAG_CONTENT_ICON: '.openstudio-grid-item-content-detail-info-box'
        },

        /**
         * Initialize module.
         * @method init
         */
        init: function() {
            // Click comment button.
            $(t.CSS.POPUP_COMMENT_CONTENT).bind('click', function() {
                t.getListComment($(this));
            });
            // Click emoticons button.
            $(t.CSS.FLAG_CONTENT_BLOCK).bind('click', function() {
                t.flagContent($(this));
            });
            // Keypress enter event when user tabbing.
            $(t.CSS.FLAG_CONTENT_ICON).bind("keypress", function(e) {
                if (e.which == 13) {
                    t.userOnPressFlagContent($(this));
                }
            });
        },

        /**
         * Get list comment.
         *
         * @param {Object} e DomEvent
         * @method getListComment
         */
        getListComment: function(e) {
            let element = e;
            let contentid = element.attr('data-contentid');
            let cmid = element.attr('data-cmid');
            if (contentid && cmid) {
                $(t.CSS.COMMENT_BLOCK_LIST).empty();
                const promises = Ajax.call([{
                    methodname: 'mod_openstudio_external_get_comment_by_contentid',
                    args: {
                        cmid: cmid,
                        contentid: contentid
                    }
                }]);
                promises[0]
                    .done(function(res) {
                        if (res) {
                            const context = {
                                commentlist: res.comments || []
                            };
                            Templates.renderForPromise('mod_openstudio/commentlist_popup', context)
                                .then(({html, js}) => {
                                    return Templates.appendNodeContents(t.CSS.COMMENT_BLOCK_LIST, html, js);
                                })
                                .catch(err => displayException(err));
                        }
                    })
                    .fail(displayException.exception);
            }
        },

        /**
         * Flag an action for a content.
         *
         * @param {Object} e DomEvent
         * @method flagContent
         */
        flagContent: function(e) {
            const cid = e.attr('data-contentid');
            const cmid = e.attr('data-cmid');
            const fid = e.attr('data-fid');
            const mode = e.attr('data-mode');
            const promises = Ajax.call([{
                methodname: 'mod_openstudio_external_flag_content',
                args: {cmid, cid, fid, mode}
            }]);
            promises[0]
                .done(function(res) {
                    if (res) {
                        $(e).attr('data-mode', res.mode);
                        let flagelement = $(e).find(t.CSS.FLAG_CONTENT_TEXT);
                        if (flagelement) {
                            flagelement.text(res.flagvalue || "");
                        }
                        $(e).find(t.CSS.FLAG_CONTENT_ICON_IMAGE).attr('src', res.flagiconimage);
                    }
                })
                .fail(displayException.exception);
        },

        /**
         * User on press flag icon to react emoticon.
         *
         * @param {Object} e DomEvent
         * @method userOnPressFlagContent
         */
        userOnPressFlagContent: function(e) {
            const cid = e.attr('data-contentid');
            const cmid = e.attr('data-cmid');
            const fid = e.attr('data-fid');
            const mode = e.attr('data-mode');
            if (cid && cmid && fid && mode) {
                t.flagContent(e);
            }
        }
    };

    return t;
});
