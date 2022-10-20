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
 * JavaScript to manage comment feature.
 *
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/comment
 */
define([
    'jquery',
    'core/ajax',
    'core/str',
    'mod_openstudio/scrollto',
    'require'
], function($, Ajax, Str, Scrollto, require) {
    var t;
    t = {

        /**
         * Module config. Passed from server side.
         * {
         *     cmid: int,
         *     cid: int
         * }
         */
        mconfig: null,

        /**
         * Height from DOMElement to top browser.
         */
        HEIGHT_TO_TOP: 100,

        /**
         * List out all of css selector used in this module.
         */
        CSS: {
            // Buttons.
            ADD_NEW_BUTTON: '#id_addcomment',
            REPLY_BUTTON: 'input[name="replycommentbutton"]',
            LIKE_BUTTON: '.openstudio-comment-flag-link',
            DELETE_BUTTON: '.openstudio-comment-delete-link',
            DELETE_CONFIRM_BUTTON: '.openstudio-comment-delete-btn',

            // Forms.
            COMMENT_FORM_CONTENT: '.openstudio-comment-form-content',
            COMMENT_FORM: '.openstudio-comment-form', // Comment form wrapper.
            COMMENT_REPLY_FORM: '.openstudio-comment-reply-form', // Reply form wrapper.
            COMMENT_ATTACHMENT: '.openstudio-comment-form-content .filepicker-filename > a', // Attachment.

            // Stream.
            COMMENT_THREAD: '.openstudio-comment-thread', // Comment thread wrapper.
            COMMENT_THREAD_BODY: '.openstudio-comment-thread-body', // Comment thread items wrapper.
            REPLY_THREAD: '.openstudio-comment-thread-replied-items', // Reply items wrapper.
            COMMENT_ITEM: '.openstudio-comment-thread-item', // Comment item.
            FLAG_COUNT: '.openstudio-comment-flag-count',
            FLAG_STATUS: '.openstudio-comment-flag-status',

            // Dialogue.
            DIALOG_HEADER: '.openstudio-comment-delete-header',
            BOUNDING_BOX: '.openstudio-comment-dialogue-container' // Dialogue wrapper.

        },

        SELECTOR: {
            COMMENT_BOX_ID: '#id_commentext', // Comment box ID.
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
                    t.dialogue = t.createDeleteCommentDialogue(osDialogue);
                });
            });

            // Click event on buttons.
            // Add new button.
            $(t.CSS.ADD_NEW_BUTTON).on('click', t.showCommentForm.bind(t));

            $('body')
                // Reply button.
                .delegate(t.CSS.REPLY_BUTTON, 'click', t.showReplyForm)
                // Like button.
                .delegate(t.CSS.LIKE_BUTTON, 'click', t.likeComment)
                // Delete button.
                .delegate(t.CSS.DELETE_BUTTON, 'click', t.deleteConfirm)
                // Delete confirm button.
                .delegate(t.CSS.DELETE_CONFIRM_BUTTON, 'click', t.deleteComment);

            // Form submit event.
            $(t.CSS.COMMENT_FORM).find('form').on('submit', t.postComment);

            // Resize event.
            $(window).resize(t.resize);
        },

        /**
         * Focus textarea.
         *
         * @param {String} textAreaId
         */
        focusTextArea: function(textAreaId) {
            const atto = $(textAreaId + 'editable');
            atto.attr('contenteditable', 'true');
            atto.focus();
        },

        /**
         * Show comment form
         *
         * @method showCommentForm
         */
        showCommentForm: function() {
            // Append form to comment form wrapper.
            $(t.CSS.COMMENT_FORM_CONTENT).appendTo(t.CSS.COMMENT_FORM);

            // Adjust form state.
            $(t.CSS.COMMENT_FORM_CONTENT).show(); // Show form content.
            t.resetForm(); // Reset form.
            $(t.CSS.ADD_NEW_BUTTON).hide(); // Hide add new comment button.
            $(t.CSS.COMMENT_REPLY_FORM).hide(); // Hide comment reply forms.

            // Set focus on comment form.
            $('#openstudio_comment_form').focus();
            t.focusTextArea(t.SELECTOR.COMMENT_BOX_ID);
        },

        /**
         * Show reply form
         *
         * @method showReplyForm
         */
        showReplyForm: function() {
            // Append form to reply form wrapper.
            var commentid = $(this).data('comment-parent').trim();
            var replyform = $(t.CSS.COMMENT_THREAD)
                .filter(function() {
                    return $(this).data('thread-items') == commentid;
                })
                .find(t.CSS.COMMENT_REPLY_FORM);
            $(t.CSS.COMMENT_FORM_CONTENT).appendTo(replyform);
            replyform.show();

            // Adjust form state.
            $(t.CSS.COMMENT_FORM_CONTENT).show(); // Show form content.
            t.resetForm(); // Reset form.
            $(t.CSS.ADD_NEW_BUTTON).show(); // Show add new comment button.
            // Assign comment id to inreplyto field.
            $(t.CSS.COMMENT_FORM_CONTENT).find('form input[name="inreplyto"]').val(parseInt(commentid));

            // Scroll to form.
            Scrollto.scrollToEl(replyform, t.HEIGHT_TO_TOP);
        },

        /**
         * Reset form.
         *
         * @method resetForm
         */
        resetForm: function() {
            // Reset comment text field.
            $(t.CSS.COMMENT_FORM_CONTENT).find('.editor_atto_content').html('');
            // Reset inreplyto field.
            $(t.CSS.COMMENT_FORM_CONTENT).find('form input[name="inreplyto"]').val(0);
            $(t.CSS.COMMENT_FORM_CONTENT).find('form').get(0).reset();
            // Reset attachment field. Just a hack on UI.
            $(t.CSS.COMMENT_ATTACHMENT).remove();
        },

        /**
         * Add a new comment or reply a comment.
         *
         * @param {Object} e DomEvent
         * @method postComment
         */
        postComment: function(e) {
            // Prevent default form submit event.
            e.preventDefault();
            e.stopImmediatePropagation();

            // Get form data.
            var formdata = {};
            $.each($(this).serializeArray(), function(i, field) {
                formdata[field.name] = field.value;
            });
            var hasAttachment = $(t.CSS.COMMENT_ATTACHMENT).length > 0;

            // Prevent from submitting to server if no content of comment is found.
            if (!hasAttachment && formdata['commentext[text]'].length == 0) {
                return;
            }

            M.util.js_pending('openstudioPostComment');
            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_add_comment',
                args: {
                    cmid: formdata.cmid,
                    cid: formdata.cid,
                    inreplyto: parseInt(formdata.inreplyto.trim()),
                    commenttext: formdata['commentext[text]'],
                    commentattachment: hasAttachment ? formdata.commentattachment : 0
                }
            }]);

            promises[0]
                .done(function(res) {
                    // Adjust form state.
                    $(t.CSS.ADD_NEW_BUTTON).show(); // Show add new comment button.
                    $(t.CSS.COMMENT_FORM_CONTENT).hide(); // Hide comment form.
                    $(t.CSS.COMMENT_REPLY_FORM).hide(); // Show reply form.

                    var commentparent = parseInt(formdata.inreplyto.trim());
                    if (commentparent) { // New reply for a certain comment thread.

                        // Append new reply to comment thread.
                        $(t.CSS.COMMENT_THREAD)
                            .filter(function() {
                                return $(this).data('thread-items') == commentparent;
                            })
                            .find(t.CSS.REPLY_THREAD)
                            .find(t.CSS.COMMENT_ITEM)
                            .last()
                            .before(res.commenthtml);

                        // Scroll to added item.
                        Scrollto.scrollToEl($('[data-thread-item="' + res.commentid + '"]'), t.HEIGHT_TO_TOP);

                    } else { // New comment added.

                        // Append new comment to comment thread.
                        $(t.CSS.COMMENT_THREAD_BODY).append(res.commenthtml);

                        // Scroll to added item.
                        Scrollto.scrollToEl($('[data-thread-items="' + res.commentid + '"]'), t.HEIGHT_TO_TOP);
                    }

                    t.resize();

                    $(t.CSS.COMMENT_THREAD).show();

                    // Set focus on comment form.
                    $('#openstudio_comment_form').focus();

                    // Trigger oumedia plugin to render audio attachment.
                    if (window.oump) {
                        window.oump.harvest();
                    }
                })
                .always(function() {
                    M.util.js_complete('openstudioPostComment');
                })
                .fail(function(ex) {
                    window.console.error('Log request failed ' + ex.message);
                });
        },

        /**
         * Like comment
         *
         * @param {object} e DomEvent
         * @method likeComment
         */
        likeComment: function(e) {
            e.preventDefault();
            var likebtn = $(this);

            M.util.js_pending('openstudioLikeComment');
            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_flag_comment',
                args: {
                    cmid: t.mconfig.cmid,
                    cid: t.mconfig.cid,
                    commentid: likebtn.data('comment-id')
                }
            }]);

            promises[0]
                .done(function(res) {
                    likebtn
                        .hide()
                        // Update flag count.
                        .siblings(t.CSS.FLAG_STATUS + '.flagged').children(t.CSS.FLAG_COUNT).addClass('flagged').text(res.count)
                        // Update flag status.
                        .parent().removeClass('openstudio-hidden')
                        .siblings(t.CSS.FLAG_STATUS + '.unflagged').addClass('openstudio-hidden');
                })
                .always(function() {
                    M.util.js_complete('openstudioLikeComment');
                })
                .fail(function(ex) {
                    window.console.error('Log request failed ' + ex.message);
                });
        },

        /**
         * Delete comment
         *
         * @method deleteComment
         */
        deleteComment: function() {
            var commentid = parseInt($(this).attr('data-comment-id'));

            M.util.js_pending('openstudioDeleteComment');
            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_delete_comment',
                args: {
                    cmid: t.mconfig.cmid,
                    commentid: commentid
                }
            }]);

            promises[0]
                .done(function() {
                    // Move comment form to another place to avoid form removed.
                    $(t.CSS.COMMENT_FORM_CONTENT).appendTo(t.CSS.COMMENT_FORM);

                    var commenttream = $('[data-thread-items="' + commentid + '"]');
                    var replyitem = $('[data-thread-item="' + commentid + '"]');

                    if (commenttream.length > 0) {
                        // Removing comment thread means reply items of it removed also.
                        commenttream.remove();
                    } else {
                        // Remove reply item.
                        replyitem.remove();
                    }

                    // Check if there is a comment thread at least.
                    if ($('div[data-thread-items]').length == 0) {
                        // If there is no comment thread, then hide comment feature in UI.
                        $(t.CSS.COMMENT_THREAD).hide();
                    }

                    // Hide delete dialogue.
                    t.dialogue.hide();

                    // Set focus on comment form.
                    t.dialogue.after('visibleChange', function() {
                        $('#openstudio_comment_form').focus();
                    }, t.dialogue);
                })
                .always(function() {
                    M.util.js_complete('openstudioDeleteComment');
                })
                .fail(function(ex) {
                    window.console.error('Log request failed ' + ex.message);
                });
        },

        /**
         * Create delete comment dialogue and some events on it.
         *
         * @param {object} osDialogue object
         * @return {object} OSDialogue instance
         * @method createDeleteCommentDialogue
         */
        createDeleteCommentDialogue: function(osDialogue) {
            var folderClass = '';
            if (t.mconfig.folder) {
                folderClass = 'openstudio-folder';
            }
            var dialogue = new osDialogue({
                closeButton: true,
                visible: false,
                centered: true,
                responsive: true,
                responsiveWidth: 767,
                modal: true,
                focusOnPreviousTargetAfterHide: true,
                width: 521,
                extraClasses: [t.CSS.BOUNDING_BOX.replace('.', ''), folderClass]
            });

            // Button [Cancel]
            var cancelBtnProperty = {
                name: 'cancel',
                classNames: 'openstudio-cancel-btn',
                action: 'hide'
            };
            // Button [Delete]
            var deleteBtnProperty = {
                name: 'delete',
                classNames: t.CSS.DELETE_CONFIRM_BUTTON.replace('.', '')
            };
            Str
                .get_strings([
                    {key: 'contentcommentsdelete', component: 'mod_openstudio'},
                    {key: 'modulejsdialogcommentdeleteconfirm', component: 'mod_openstudio'},
                    {key: 'modulejsdialogcancel', component: 'mod_openstudio'},
                    {key: 'modulejsdialogdelete', component: 'mod_openstudio'}
                ])
                .done(function(s) {
                    cancelBtnProperty.label = s[2];
                    deleteBtnProperty.label = s[3];

                    dialogue.set('headerContent',
                        '<span class="openstudio-dialogue-common-header ' + t.CSS.DIALOG_HEADER.replace('.', '') +
                        '">' + s[0] + '</span>');
                    dialogue.set('bodyContent', s[1]);
                    dialogue.addButton(deleteBtnProperty, ['footer']);
                    dialogue.addButton(cancelBtnProperty, ['footer']);
                });

            return dialogue;
        },

        /**
         * Delete confirmation.
         *
         * @param {Object} e DomEvent
         * @method deleteConfirm
         */
        deleteConfirm: function(e) {
            e.preventDefault();
            if (t.dialogue) {
                // Update comment id for delete button.
                $(t.CSS.DELETE_CONFIRM_BUTTON).attr('data-comment-id', $(this).data('comment-id'));
                // Show delete dialogue.
                t.dialogue.show();
            }
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
