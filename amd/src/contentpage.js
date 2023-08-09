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
 * JavaScript to manage content detail page.
 *
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*global google: true*/

/**
 * @module mod_oucontent/contentpage
 */
define(['jquery', 'core/ajax', 'core/str', 'core/modal', 'core/modal_events', 'core/templates', 'core/config', 'require'],
    function($, Ajax, Str, Modal, ModalEvents, Templates, Config, require) {
        var t;

        t = {

            /**
             * M.core.dialog instance
             */
            dialogue: null,

            delete_item: null,

            /**
             * Module config. Passed from server side.
             */
            mconfig: null,

            init: function(options) {
                $.fx.off = true;

                t.mconfig = options;

                Y.use('moodle-core-notification-dialogue', function() {
                    require(['mod_openstudio/osdialogue'], function(osDialogue){
                        t.archiveDialogue = t.createArchiveDialogue(osDialogue);
                        t.deleteContentDialogue = t.createDeleteContentDialogue(osDialogue);
                    });
                });

                // Maximize feature.
                t.createMaximizeModal();
                $('#openstudio_content_view_maximize').on('click', t.toogleContentModal);
                $('body').delegate('#openstudio_content_view_minimize', 'click', t.toogleContentModal);
                $('body').delegate('.openstudio-modal-content-close', 'click', t.toogleContentModal);

                var contentViewMapCanvas = $('#openstudio_content_view_map_canvas');

                if (contentViewMapCanvas.length) {
                    t.showGoogleMap(contentViewMapCanvas);
                }

                // Click event on archive button.
                $('#id_archivebutton').on('click', function(e) {
                    e.preventDefault();
                    t.archiveDialogue.show();
                });

                // Click event on delete button.
                $('.openstudio-delete-content-version').on('click', function(e) {
                    e.preventDefault();
                    t.delete_item = $(this);
                    t.deleteContentDialogue.show();
                });

                $(".openstudio-content-view-flag-icon").bind('click', function() {
                    t.doFlagContent($(this));
                });
                $(".openstudio-content-view-icon-active-image").bind('mouseover', function() {
                    t.doHoverFlagIcon($(this));
                });

                $(".openstudio-request-feedback-button").bind('click', function() {
                    t.doFlagContent($(this));
                });

                $(document).ready(t.showCommentBox);
        },

        /**
         * Create modal to display content inside it when click maximize button.
         *
         * @method createMaximizeModal
         */
        createMaximizeModal: function() {
            Templates
                .render('mod_openstudio/content_modal', {
                    title: $('.openstudio-content-view-title').text(),
                    body: $('.openstudio-content-view-file').html(),
                    date: $('.openstudio-content-view-date').html(),
                    isiframecontent: $('.openstudio-content-view-file').find('iframe').length > 0
                })
                .done(function(html) {
                    t.modal = new Modal(html);
                    t.modal.setLarge();
                });

        },

        /**
         * Toggle content modal
         *
         * @method toogleContentModal
         */
        toogleContentModal: function() {
            if (!t.modal) {
              return;
            }

            if (t.modal.isVisible()) {
                t.modal.hide();
            } else {
                t.modal.show();

                // Auto scroll to close button when open content model.
                t.modal.getRoot().on(ModalEvents.shown, function(e) {
                    if (e.type === 'modal:shown')          {
                         $('.openstudio-modal-content-close')[0].scrollIntoView();
                    }
                    $('.openstudio-modal-content-close, #openstudio_content_view_minimize').keydown(function (e) {
                        if (e.keyCode == 13) {
                            t.modal.hide();
                            $('#openstudio_content_view_maximize').focus();

                            return false;
                        }
                    });
                });
            }

            // Lock page scroll.
            $('body').toggleClass('openstudio-lockscroll');
        },

        /**
         * Show Google map if user requested it and it is available.
         *
         * @param {Event} contentViewMapCanvas
         * @method showGoogleMap
         */
        showGoogleMap: function(contentViewMapCanvas) {
            var gpslat = contentViewMapCanvas.attr('data-gpslat');
            var gpslng = contentViewMapCanvas.attr('data-gpslng');

            if (gpslat && gpslng) {
                var myLatLng = {lat: parseFloat(gpslat), lng: parseFloat(gpslng)};
                if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                    var map = new google.maps.Map(document.getElementById('openstudio_content_view_map_canvas'), {
                        center: myLatLng,
                        zoom: 14
                    });

                    new google.maps.Marker({
                        position: myLatLng,
                        map: map
                    });
                }
            }
        },

        /**
         * Flag icons on content page.
         *
         * @param {Event} event
         * @method doFlagContent
         */
        doFlagContent: function(event) {
            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_flag_content',
                args: {
                    cmid: event.attr('data-cmid'),
                    cid: event.attr('data-cid'),
                    fid: event.attr('data-fid'),
                    mode: event.attr('data-mode')
                }
            }]);

            promises[0]
                .done(function(res) {
                    if (res.success) {
                        // Update new flag content.
                        var flagcontainer = $('#content_view_icon_' + event.attr('data-cid') + '_' + res.fid);
                        flagcontainer.attr('data-mode', res.mode);

                        // Check if flag a request feedback.
                        if (res.iscontentflagrequestfeedback) {
                            flagcontainer.html(res.flagtext);
                            $('#openstudio_item_request_feedback')
                                .removeClass(res.flagremoveclass)
                                .addClass(res.flagaddclass);
                        } else {
                            flagcontainer.find('.openstudio-content-view-icon-text').text(res.flagtext);
                            flagcontainer.toggleClass('openstudio-content-view-icon-active', res.mode == 'off');
                            flagcontainer.attr('title', res.accessiblemessage);
                        }
                    }
                })
                .fail(function(ex) {
                    window.console.error('Error saving social flag ' + ex.message);
                });
        },
        /**
         * Hover flag icons on content page.
         *
         * @param {Event} event
         * @method doHoverFlagIcon
         */
        doHoverFlagIcon: function(event) {
            var parent = event.parents('.openstudio-content-view-flag-icon');
            var activeImage = event.find('img');
            activeImage.attr('title', parent.attr('title'));
        },

        /**
         * Set header for dialog
         *
         * @method setHeader
         * @param {Element} dialogue
         * @param {Element} label
         */
        setHeader: function(dialogue, label) {
            Str
                .get_string(label, 'mod_openstudio')
                .done(function(s) {
                    dialogue.set('headerContent',
                        '<span class="openstudio-dialogue-common-header">' + s + '</span>');
                });
        },

        /**
         * Set body for dialog
         *
         * @method setBody
         * @param {Element} dialogue
         * @param {Element} label
         */
        setBody: function(dialogue, label) {
            Str
                .get_string(label, 'mod_openstudio')
                .done(function(s) {
                    dialogue.set('bodyContent',
                        '<span>' + s + '</span>');
                });
        },

        /**
         * Create archive dialogue and some events on it.
         *
         * @method createArchiveDialogue
         * @param {Element} osDialogue
         * @return M.core.dialogue
         */
        createArchiveDialogue: function(osDialogue) {
            var dialogue = new osDialogue({
                closeButton: true,
                visible: false,
                centered: true,
                responsive: true,
                responsiveWidth: 767,
                modal: true,
                focusOnPreviousTargetAfterHide: true,
                width: 521
            });


            t.setHeader(dialogue, 'archivedialogheader');
            t.setBody(dialogue, 'modulejsdialogcontentarchiveconfirm');

            // Button [Archive]
            var archiveBtnProperty = {
                name: 'archive',
                classNames: 'openstudio-archive-yes-btn',
                events: {
                    click: function() {
                        t.redirectPostRequest({
                            url: $('#contentversionlink').val(),
                            data: {
                                archiveversion: 1,
                            }
                        });
                    }
                }
            };

            // Button [cancel]
            var cancelBtnProperty = {
                name: 'cancel',
                classNames: 'openstudio-archive-no-btn',
                action: 'hide'
            };

            Str
                .get_strings([
                    {key: 'contentactionarchivepost', component: 'mod_openstudio'},
                    {key: 'modulejsdialogcancel', component: 'mod_openstudio'}
                ])
                .done(function(s) {
                    archiveBtnProperty.label = s[0];
                    cancelBtnProperty.label = s[1];

                    dialogue.addButton(archiveBtnProperty, ['footer']);
                    dialogue.addButton(cancelBtnProperty, ['footer']);
                });

            return dialogue;
        },

        /**
         * Create delete content dialogue and some events on it.
         *
         * @method createDeleteContentDialogue
         * @param {Element} osDialogue
         * @return M.core.dialogue
         */
        createDeleteContentDialogue: function(osDialogue) {
            var dialogue = new osDialogue({
                closeButton: true,
                visible: false,
                centered: true,
                responsive: true,
                responsiveWidth: 767,
                modal: true,
                focusOnPreviousTargetAfterHide: true,
                width: 521
            });

            t.setHeader(dialogue, 'deletearchiveversionheader');
            t.setBody(dialogue, 'deletearchiveversionheaderconfirm');

            // Button [delete]
            var deleteBtnProperty = {
                name: 'delete',
                classNames: 'openstudio-delete-btn',
                events: {
                    click: function() {
                        var delete_item = t.delete_item;
                        var promises = Ajax.call([{
                            methodname: 'mod_openstudio_external_delete_version',
                            args: {
                                cmid: delete_item.attr('data-cmid'),
                                cid: delete_item.attr('data-cid'),
                                cvid: delete_item.attr('data-cvid')
                            }
                        }]);

                        promises[0]
                            .done(function() {
                                $('.openstudio-cancel-btn').trigger('click');

                                // Redirect to current version content when delete from view version detail.
                                if ($('#contentcurrenteversionurl').length > 0) {
                                    window.location.href = $('#contentcurrenteversionurl').val();
                                } else {
                                    // Remove from post type list.
                                    $('#content_version_id_' + delete_item.attr('data-cvid')).remove();

                                    // Check if do not has any post archive we will remove Archive panel.
                                    if ($('.openstudio-content-view-version-detail').length == 0) {
                                        $('#openstudio_content_view_post_archive_panel').remove();
                                    }
                                }
                            })
                            .fail(function(ex) {
                                window.console.error('Error delete content version' + ex.message);
                            });
                    }
                }
            };

            // Button [cancel]
            var cancelBtnProperty = {
                name: 'cancel',
                classNames: 'openstudio-cancel-btn',
                action: 'hide'
            };

            Str
                .get_strings([
                    {key: 'modulejsdialogdelete', component: 'mod_openstudio'},
                    {key: 'modulejsdialogcancel', component: 'mod_openstudio'}
                ])
                .done(function(s) {
                    deleteBtnProperty.label = s[0];
                    cancelBtnProperty.label = s[1];

                    dialogue.addButton(deleteBtnProperty, ['footer']);
                    dialogue.addButton(cancelBtnProperty, ['footer']);
                });

            return dialogue;
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
         * Show comment box when clicking on the "Add comment" from My module page.
         *
         * @method showCommentBox
         */

        showCommentBox: function() {
            // Due to the current code still using Bootstrap 4, thus Jquery can work better than Javascript.
            const urlParams = new URLSearchParams(window.location.search);
            const idAddComment = $('#id_addcomment');

            if (urlParams.get('addcomment') == 1) {
                const folderComment = $('#toggle_folder_view_folder_comments');
                if (folderComment) {
                    folderComment.click();

                }

                idAddComment.click();
                if (folderComment) {
                    $('#openstudio_folder_view_folder_comments').on("shown.bs.collapse", function() {
                        t.scrollToEle();
                    });
                } else {
                    t.scrollToEle();
                }
            }
        },

        scrollToEle: function() {
            const element = $('#openstudio-comment-form-body');
            $('html, body').animate({
                scrollTop: $(element).offset().top
            }, 800);
        },
        };

    return t;

});
