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
    'core/notification',
    'core_form/changechecker',
    'core/fragment',
    'core/templates',
    'mod_openstudio/osdialogue',
    'core/modal_events',
    'core/pending',
    'core_form/events',
], function($, Ajax, Str, Scrollto, Notification, FormChangeChecker,
            Fragment, Templates, osDialogue, ModalEvents, Pending, FormEvents) {
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
         * Delete comment dialogue instance.
         */
        dialogue: null,

        /**
         * Undelete comment dialogue instance.
         */
        undeletedialogue: null,

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
            EDIT_BUTTON: '.openstudio-comment-edit-link',
            DELETE_CONFIRM_BUTTON: '.openstudio-comment-delete-btn',
            UNDELETE_BUTTON: '.openstudio-comment-undelete-link',
            UNDELETE_CONFIRM_BUTTON: '.openstudio-comment-undelete-btn',

            // Forms.
            COMMENT_FORM_CONTENT: '.openstudio-comment-form-content',
            COMMENT_FORM: '.openstudio-comment-form', // Comment form wrapper.
            COMMENT_REPLY_FORM: '.openstudio-comment-reply-form', // Reply form wrapper.
            COMMENT_ATTACHMENT: '.openstudio-comment-form-content .filepicker-filename > a', // Attachment.
            COMMENT_FORM_BODY: '.openstudio-comment-form-body',
            COMMENT_POST_BUTTON: '#id_postcomment',
            COMMENT_CANCEL_BUTTON: '#id_cancel',
            COMMENT_LOADING: '.openstudio-comment-loading',

            // Stream.
            COMMENT_THREAD: '.openstudio-comment-thread', // Comment thread wrapper.
            COMMENT_THREAD_BODY: '.openstudio-comment-thread-body', // Comment thread items wrapper.
            REPLY_THREAD: '.openstudio-comment-thread-replied-items', // Reply items wrapper.
            COMMENT_ITEM: '.openstudio-comment-thread-item', // Comment item.
            FLAG_COUNT: '.openstudio-comment-flag-count',
            FLAG_STATUS: '.openstudio-comment-flag-status',
            DELETED_COMMENT: '.openstudio-deleted-comment',
            COMMENT_ITEM_ID_PREFIX: '[id^="openstudio-comment"]',

            // Classes for handle visibility.
            LAST_VISIBLE_REPLY: 'openstudio-last-visible-reply',
            OPENSTUDIO_HIDDEN: 'openstudio-hidden',

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
        init: async function(options) {
            const pendingPromise = new Pending('mod_openstudio/comment:init');
            t.mconfig = options;

            t.dialogue = await t.createDeleteCommentDialogue();
            t.undeletedialogue = await t.createUndeleteCommentDialogue();

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
                .delegate(t.CSS.DELETE_CONFIRM_BUTTON, 'click', t.deleteComment)
                // Undelete button.
                .delegate(t.CSS.UNDELETE_BUTTON, 'click', t.undeleteConfirm)
                // Undelete confirm button.
                .delegate(t.CSS.UNDELETE_CONFIRM_BUTTON, 'click', t.undeleteComment)
                // Edit button.
                .delegate(t.CSS.EDIT_BUTTON, 'click', t.showReplyForm);

            // Form submit event.
            $(t.CSS.COMMENT_FORM).find('form').on('submit', t.postComment);

            pendingPromise.resolve();
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
         * Show reply form.
         *
         * @param {HTMLElement|jQuery|Event} [el] Optional element or event.
         * @method showReplyForm
         */
        showReplyForm: function(el) {
            el.preventDefault();
            // Hide all comment reply forms.
            $(t.CSS.COMMENT_REPLY_FORM).hide();
            $(t.CSS.COMMENT_FORM).find(t.CSS.COMMENT_FORM_CONTENT).hide();
            // Show add new comment button.
            $(t.CSS.ADD_NEW_BUTTON).show();
            // Determine the triggering element.
            let $trigger;
            if (el && el.currentTarget) {
                // Event object
                $trigger = $(el.currentTarget);
            } else if (el) {
                // DOM element or jQuery object.
                $trigger = $(el);
            } else {
                // Fallback to jQuery event context.
                $trigger = $(this);
            }
            let isEditing = false;
            let commentId = $trigger.data('comment-parent')?.trim();
            if (!commentId) {
                commentId = parseInt($trigger.attr('data-comment-id')?.trim());
                isEditing = true;
            }
            if (!commentId || isNaN(commentId)) {
                window.console.error('Invalid comment ID for reply/edit');
                return;
            }
            let threadItems = $(t.CSS.COMMENT_THREAD).filter(function() {
                if (isEditing) {
                    return String($(this).data('thread-items')) === String($trigger.data('comment-parent-id')?.trim());
                }
                return String($(this).data('thread-items')) === String(commentId);
            });
            let replyForm = threadItems.find(t.CSS.COMMENT_REPLY_FORM).first();
            replyForm.show();
            const currentEditId = replyForm.data('editing-comment-id');
            // Only reload fragment if:
            // - Not editing, or
            // - Editing a different comment, or
            // - Form is missing.
            if (!isEditing || !replyForm.find('form').length || currentEditId !== commentId) {
                const loading = $(t.CSS.COMMENT_LOADING).clone().show();
                // Show loading.
                replyForm.append(loading);
                // Load fragment form.
                const pendingPromise = new Pending('mod_openstudio/openstudioloadfragmentform');
                let appendSelector = replyForm.find(t.CSS.COMMENT_FORM_BODY);
                t.loadFragmentForm(commentId, isEditing).then(function(html, js) {
                    Templates.replaceNodeContents(appendSelector, html, js);
                    if (isEditing) {
                        appendSelector.find('form').data('edit-comment-id', commentId);
                    }
                    appendSelector.find('form').submit(function(e) {
                        e.preventDefault();
                    });

                    appendSelector.find(t.CSS.COMMENT_POST_BUTTON).on('click', function(e) {
                        e.preventDefault();
                        if (isEditing) {
                            t.updateComment.call($(this).closest('form'), e);
                        } else {
                            t.postComment.call($(this).closest('form'), e);
                        }
                    });
                    appendSelector.find(t.CSS.COMMENT_CANCEL_BUTTON).on('click', function(e) {
                        e.preventDefault();
                        // Hide form and show add button (UI only, no sensitive data exposed).
                        $(t.CSS.ADD_NEW_BUTTON).show();
                        $(t.CSS.COMMENT_FORM_CONTENT).hide();
                        $(t.CSS.COMMENT_REPLY_FORM).hide();
                        replyForm.removeData('editing-comment-id');
                        t.resetForm();
                    });
                    // Remove loading.
                    replyForm.find(t.CSS.COMMENT_LOADING).remove();
                    t.showReplyFormPostProcess(replyForm, commentId, isEditing);
                    pendingPromise.resolve();
                }).fail(function(ex) {
                    pendingPromise.resolve();
                    Notification.exception(ex);
                });
            } else {
                t.showReplyFormPostProcess(replyForm, commentId, isEditing);
            }
        },

        /**
         * Show reply form post process.
         *
         * @param {object} replyForm
         * @param {string} commentId
         * @param {boolean} isEditing
         * @method showReplyFormPostProcess
         */
        showReplyFormPostProcess: function(replyForm, commentId, isEditing = false) {
            // Adjust form state.
            replyForm.find(t.CSS.COMMENT_FORM_CONTENT).show();
            // Reset form.
            if (!isEditing) {
                t.resetForm();
            }
            // Assign comment id to inreplyto field.
            replyForm.find('form input[name="inreplyto"]').val(parseInt(commentId));
            // Scroll to form.
            Scrollto.scrollToEl(replyForm, t.HEIGHT_TO_TOP);
        },

        /**
         * Call web services to get the fragment form, append to the DOM then bind event.
         * @param {string} commentId
         * @param {boolean} isEditing
         * @return {Promise}
         * @method loadFragmentForm
         */
        loadFragmentForm: function(commentId, isEditing) {
            var params = [];
            params.id = t.mconfig.cmid;
            params.cid = t.mconfig.cid;
            params.max_bytes = t.mconfig.max_bytes;
            params.attachmentenable = t.mconfig.attachmentenable;
            params.isediting = isEditing;
            if (commentId) {
                params.replyid = commentId;
            }
            return Fragment.loadFragment('mod_openstudio', 'commentform', t.mconfig.contextid, params);
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
            setTimeout(function() {
                if (window.tinyMCE && window.tinyMCE.activeEditor) {
                    window.tinyMCE.activeEditor.setContent('');
                }
                $(t.CSS.COMMENT_FORM_CONTENT + ':visible').find('form').get(0).reset();
            }, 0);
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

            // Sync TinyMCE content to the underlying textarea before serializing.
            if (window.tinyMCE) {
                window.tinyMCE.triggerSave();
            }

            let $form = $(this);

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
                    commenttextitemid: formdata['commentext[itemid]'],
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
                        const $addedItem = $(`[data-thread-item="${res.commentid}"]`);
                        if ($addedItem.length) {
                            Scrollto.scrollToEl($addedItem, t.HEIGHT_TO_TOP);
                            t.updateLatestReplyButton($addedItem);
                        }

                    } else { // New comment added.

                        // Append new comment to comment thread.
                        $(t.CSS.COMMENT_THREAD_BODY).append(res.commenthtml);

                        // Scroll to added item.
                        setTimeout(function() {
                            Scrollto.scrollToEl($('[data-thread-items="' + res.commentid + '"]'), t.HEIGHT_TO_TOP);
                        }, 0);
                    }

                    $(t.CSS.COMMENT_THREAD).show();

                    // Set focus on comment form.
                    $('#openstudio_comment_form').focus();
                    t.focusTextArea(t.SELECTOR.COMMENT_BOX_ID);

                    // Trigger oumedia plugin to render audio attachment.
                    if (window.oump) {
                        window.oump.harvest();
                    }
                    // Notify that the form was submitted by Javascript so that the TinyMCE autosave
                    // plugin can clean up any previously saved draft records for this editor.
                    FormEvents.notifyFormSubmittedByJavascript($form[0]);
                    // Reset the 'dirty' flag of the comment form.
                    FormChangeChecker.resetFormDirtyState($(t.CSS.COMMENT_POST_BUTTON)[0]);
                })
                .always(function() {
                    M.util.js_complete('openstudioPostComment');
                })
                .fail(Notification.exception);
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
                    commentid: likebtn.data('comment-id'),
                    fid: t.mconfig.fid
                }
            }]);

            promises[0]
                .done(function(res) {
                    let likeicon = likebtn.siblings(t.CSS.FLAG_STATUS + '.flagged');
                    let notlikeicon = likebtn.siblings(t.CSS.FLAG_STATUS + '.unflagged');
                    likebtn
                        // Update flag count.
                        .siblings(t.CSS.FLAG_STATUS).children(t.CSS.FLAG_COUNT).text(res.count)
                        // Update flag status.
                        .parent().removeClass('openstudio-hidden')
                        .siblings(t.CSS.FLAG_STATUS).addClass('openstudio-hidden');
                    if (res.count === 0) {
                        let imageElement = likebtn.siblings(t.CSS.FLAG_STATUS).children('img');
                        if (imageElement) {
                            Str.get_string('contentcommentnotliked', 'mod_openstudio').then((value) => {
                                imageElement[1].alt = value;
                                imageElement[1].title = value;
                            });
                        }
                    }
                    // Show/hide opposite like/unlike links.
                    likebtn.children().each(function() {
                        var elem = $(this);
                        if (elem.hasClass('openstudio-comment-like-long-link') ||
                            elem.hasClass('openstudio-comment-like-short-link') ||
                            elem.hasClass('openstudio-comment-unlike-long-link') ||
                            elem.hasClass('openstudio-comment-unlike-short-link')) {
                            if (elem.hasClass('openstudio-hidden')) {
                                elem.removeClass('openstudio-hidden');
                                likeicon.removeClass('openstudio-hidden');
                                notlikeicon.addClass('openstudio-hidden');
                            } else {
                                elem.addClass('openstudio-hidden');
                                likeicon.addClass('openstudio-hidden');
                                notlikeicon.removeClass('openstudio-hidden');
                            }
                        }
                    }, this);
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

            if (!commentid || isNaN(commentid)) {
                window.console.error('Invalid comment ID for deletion');
                return;
            }

            t.handleCommentOperation(commentid, true, t.dialogue);
        },

        /**
         * Create delete comment dialogue and some events on it.
         *
         * @method createDeleteCommentDialogue
         * @returns {Promise<Modal>}
         */
        createDeleteCommentDialogue: async function() {
            let folderClass = '';
            if (t.mconfig.folder) {
                folderClass = 'openstudio-folder';
            }
            const dialogue = await osDialogue.create({
                isVerticallyCentered: true,
                templateContext: {
                    extraClasses: t.CSS.BOUNDING_BOX.replace('.', '') + ' ' + folderClass,
                },
            });

            // Button [Cancel].
            const cancelBtnProperty = {
                name: 'cancel',
                classNames: 'openstudio-cancel-btn',
                action: 'hide',
            };
            // Button [Delete].
            const deleteBtnProperty = {
                name: 'delete',
                classNames: t.CSS.DELETE_CONFIRM_BUTTON.replace('.', ''),
            };
            Str
                .get_strings([
                    {key: 'contentcommentsdelete', component: 'mod_openstudio'},
                    {key: 'modulejsdialogcommentdeleteconfirm', component: 'mod_openstudio'},
                    {key: 'modulejsdialogcancel', component: 'mod_openstudio'},
                    {key: 'modulejsdialogdelete', component: 'mod_openstudio'},
                ])
                .done(function(s) {
                    cancelBtnProperty.label = s[2];
                    deleteBtnProperty.label = s[3];

                    dialogue.setTitle('<span class="openstudio-dialogue-common-header ' + t.CSS.DIALOG_HEADER.replace('.', '') +
                        '">' + s[0] + '</span>');
                    dialogue.setBody(s[1]);
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
                // Show delete dialogue.
                t.dialogue.show();
                // Update comment id for delete button.
                $(t.CSS.DELETE_CONFIRM_BUTTON).attr('data-comment-id', $(this).data('comment-id'));
            }
        },

        /**
         * Undelete confirmation.
         *
         * @param {Object} e DomEvent
         * @method undeleteConfirm
         */
        undeleteConfirm: function(e) {
            e.preventDefault();
            if (t.undeletedialogue) {
                // Show undelete dialogue.
                t.undeletedialogue.show();
                // Update comment id for undelete button.
                $(t.CSS.UNDELETE_CONFIRM_BUTTON).attr('data-comment-id', $(this).data('comment-id'));
            }
        },

        /**
         * Undelete comment.
         *
         * @method undeleteComment
         */
        undeleteComment: function () {
            const commentid = parseInt($(this).attr('data-comment-id'));

            if (!commentid || isNaN(commentid)) {
                window.console.error('Invalid comment ID for undeletion');
                return;
            }

            t.handleCommentOperation(commentid, false, t.undeletedialogue);
        },

        /**
         * Update comment button visibility based on deletion state.
         *
         * @param {jQuery} $comment The comment element
         * @param {boolean} isDeleted Whether the comment is deleted
         * @method updateCommentButtons
         */
        updateCommentButtons: function($comment, isDeleted) {
            const $undeleteBtn = $comment.find(t.CSS.UNDELETE_BUTTON);
            const $standardBtns = $comment.find(`${t.CSS.DELETE_BUTTON}, ${t.CSS.LIKE_BUTTON}, ${t.CSS.EDIT_BUTTON}`);
            const $replyBtn = $comment.find(t.CSS.REPLY_BUTTON);
            const isReply = $comment.closest(t.CSS.REPLY_THREAD).length > 0;

            // Handle reply button visibility.
            if (isReply) {
                t.updateLatestReplyButton($comment);
            } else {
                $replyBtn.toggleClass(t.CSS.OPENSTUDIO_HIDDEN, isDeleted);
            }

            // Toggle button visibility based on deleted state.
            $undeleteBtn.toggleClass(t.CSS.OPENSTUDIO_HIDDEN, !isDeleted);
            $standardBtns.toggleClass(t.CSS.OPENSTUDIO_HIDDEN, isDeleted);
        },

        /**
         * Update the reply button visibility for the latest non-deleted reply in a thread.
         *
         * @param {jQuery} comment The thread element
         * @method updateLatestReply
         */
        updateLatestReplyButton: function(comment) {
            // Find all replies in the thread.
            const replies = comment.closest(t.CSS.REPLY_THREAD);
            // Remove current latest reply highlight.
            const allReplies = replies.find(t.CSS.COMMENT_ITEM_ID_PREFIX);
            allReplies.removeClass(t.CSS.LAST_VISIBLE_REPLY);
            // Find the latest non-deleted reply.
            const latestVisibleReply = allReplies.not(`:has(${t.CSS.DELETED_COMMENT})`).last();
            // If found, update its class and show reply button.
            if (latestVisibleReply.length) {
                latestVisibleReply.addClass(t.CSS.LAST_VISIBLE_REPLY)
                    .find(t.CSS.REPLY_BUTTON).removeClass(t.CSS.OPENSTUDIO_HIDDEN);
            }
        },

        /**
         * Handle comment deletion/undeletion operation.
         *
         * @param {number} commentid Comment ID
         * @param {boolean} isDelete True for delete, false for undelete
         * @param {Modal} dialogue The modal dialogue to hide after success
         * @method handleCommentOperation
         */
        handleCommentOperation: function(commentid, isDelete, dialogue) {
            const operation = isDelete ? 'Delete' : 'Undelete';
            const methodname = 'mod_openstudio_external_' + (isDelete ? 'delete' : 'undelete') + '_comment';
            const pendingKey = `openstudio${operation}Comment`;
            const pendingPromise = new Pending(pendingKey);

            Ajax.call([{
                methodname: methodname,
                args: {
                    cmid: t.mconfig.cmid,
                    commentid: commentid,
                }
            }])[0]
                .done(function(data) {
                    const $targetComment = $('[data-thread-item="' + commentid + '"]');

                    if (!$targetComment.length) {
                        window.console.error('Comment element not found: ' + commentid);
                        return;
                    }

                    const selector = isDelete ? '.openstudio-comment-text' : '.openstudio-deleted-comment';
                    const htmlContent = isDelete ? data.deletedcommenthtml : data.commenthtml;
                    const $commentText = $targetComment.find(selector);

                    if (!$commentText.length) {
                        window.console.error('Comment text element not found');
                        return;
                    }

                    if (!htmlContent) {
                        window.console.error('Invalid server response: missing HTML content');
                        return;
                    }

                    // Update comment content.
                    Templates.replaceNode($commentText, htmlContent, '');

                    // Update button visibility.
                    t.updateCommentButtons($targetComment, isDelete);

                    // Set focus on comment form when modal closes.
                    dialogue.getRoot().one(ModalEvents.hidden, function() {
                        $('#openstudio_comment_form').focus();
                    });

                    // Hide dialogue.
                    dialogue.hide();
                })
                .fail(function(ex) {
                    window.console.error(operation + ' comment request failed: ' + ex.message);
                    Notification.addNotification({
                        message: ex.message,
                        type: 'error'
                    });
                })
                .always(function() {
                    pendingPromise.resolve();
                });
        },

        /**
         * Create undelete comment dialogue and some events on it.
         *
         * @method createUndeleteCommentDialogue
         * @returns {Promise<Modal>}
         */
        createUndeleteCommentDialogue: async function() {
            const folderClass = t.mconfig.folder ? 'openstudio-folder' : '';

            const dialogue = await osDialogue.create({
                isVerticallyCentered: true,
                templateContext: {
                    extraClasses: t.CSS.BOUNDING_BOX.replace('.', '') + ' ' + folderClass,
                },
            });

            const cancelBtnProperty = {
                name: 'cancel',
                classNames: 'openstudio-cancel-btn',
                action: 'hide',
            };

            const undeleteBtnProperty = {
                name: 'undelete',
                classNames: t.CSS.UNDELETE_CONFIRM_BUTTON.replace('.', ''),
            };

            try {
                const strings = await Str.get_strings([
                    {key: 'contentcommentundelete', component: 'mod_openstudio'},
                    {key: 'modulejsdialogcommentundeleteconfirm', component: 'mod_openstudio'},
                    {key: 'modulejsdialogcancel', component: 'mod_openstudio'},
                    {key: 'undeletecomment', component: 'mod_openstudio'},
                ]);

                cancelBtnProperty.label = strings[2];
                undeleteBtnProperty.label = strings[3];

                dialogue.setTitle('<span class="openstudio-dialogue-common-header ' +
                    t.CSS.DIALOG_HEADER.replace('.', '') + '">' + strings[0] + '</span>');
                dialogue.setBody(strings[1]);
                dialogue.addButton(undeleteBtnProperty, ['footer']);
                dialogue.addButton(cancelBtnProperty, ['footer']);
            } catch (error) {
                window.console.error('Failed to load dialogue strings:', error);
            }

            return dialogue;
        },

        /**
         * Update comment submit handler.
         *
         * @param {Object} e DomEvent
         * @method updateComment
         */
        updateComment: function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            let form = $(this);
            let commentId = form.data('edit-comment-id');
            if (!commentId || isNaN(commentId)) {
                window.console.error('Invalid comment ID for update');
                return;
            }
            // Sync TinyMCE content to the underlying textarea before serializing.
            if (window.tinyMCE) {
                window.tinyMCE.triggerSave();
            }
            let formData = {};
            $.each(form.serializeArray(), function(i, field) {
                formData[field.name] = field.value;
            });
            let hasAttachment = $(t.CSS.COMMENT_ATTACHMENT).length > 0;
            if (!hasAttachment && (!formData['commentext[text]'] || formData['commentext[text]'].length === 0)) {
                return;
            }
            // Check this is the latest reply in the thread.
            const isLatestReply = $(`[data-thread-item="${commentId}"]`).hasClass(t.CSS.LAST_VISIBLE_REPLY);
            const pendingPromise = new Pending('mod_openstudio/openstudioUpdateComment');
            let promises = Ajax.call([{
                methodname: 'mod_openstudio_external_edit_comment',
                args: {
                    cmid: formData.cmid,
                    commentid: commentId,
                    commenttext: formData['commentext[text]'],
                    commenttextitemid: formData['commentext[itemid]'],
                    commentattachment: hasAttachment ? formData.commentattachment : 0,
                }
            }]);
            promises[0]
                .done(function(res) {
                    // Update comment in DOM
                    let commentItem = $('[data-thread-item="' + res.commentid + '"]');
                    if (commentItem.length) {
                        commentItem.replaceWith(res.commenthtml);
                        const $updatedComment = $(`[data-thread-item="${res.commentid}"]`);
                        // If this is the latest reply, update the reply button visibility.
                        $updatedComment.toggleClass(t.CSS.LAST_VISIBLE_REPLY, isLatestReply);
                        // Scroll to added item.
                        if ($updatedComment.length) {
                            Scrollto.scrollToEl($updatedComment, t.HEIGHT_TO_TOP);
                        }
                    }

                    // Trigger oumedia plugin to render audio attachment.
                    if (window.oump) {
                        window.oump.harvest();
                    }
                    // Notify that the form was submitted by Javascript so that the TinyMCE autosave
                    // plugin can clean up any previously saved draft records for this editor.
                    FormEvents.notifyFormSubmittedByJavascript(form[0]);
                    // Reset the 'dirty' flag of the comment form.
                    FormChangeChecker.resetFormDirtyState($(t.CSS.COMMENT_POST_BUTTON)[0]);

                    // Hide form, show add button
                    $(t.CSS.COMMENT_FORM_CONTENT).hide();
                    $(t.CSS.COMMENT_REPLY_FORM).hide();
                })
                .always(function() {
                    pendingPromise.resolve();
                })
                .fail(Notification.exception);
        },
    };

    return t;
});
