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
 * Javascript functionality for the notifications list in the header.
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or lat
 */

/**
 * @module mod_openstudio/notificationlist
 */
define(['jquery', 'core/ajax', 'core/modal', 'core/templates'],
        function ($, Ajax, Modal, Templates) {
    var SELECTORS = {
        STOPBUTTONS: '.openstudio-notification-stopbutton',
        NOTIFICATIONBUTTON: '.openstudio-nav-primary .dropdown.notifications',
        UNREAD: '.openstudio-notifications-list .openstudio-notification-unread',
    };

    var priv = {
        cmid: null,
        followflag: null
    };

    var t = {
        modal: null,

        /**
         * Set up event handlers
         *
         * @param {Integer} cmid
         * @param {Integer} followflag
         */
        init: function(cmid, followflag) {
            priv.cmid = cmid;
            priv.followflag = followflag;
            $(SELECTORS.STOPBUTTONS).on('click', t.stopNotifications);
            // $('[data-toggle="tooltip"]').tooltip();
            if ($(SELECTORS.UNREAD).length > 0) {
                $(SELECTORS.NOTIFICATIONBUTTON).on('click', t.readNotifications);
            }
            // Pre-fetch templates to make dialogue display quicker.
            Templates.render('mod_openstudio/notification_modal', {name: ''});
            Templates.render('core/modal_backdrop', {});
        },

        /**
         * Re-show the notification list if it's hidden by clicking another control.
         *
         * This uses setTimeout() to push the event to the end of the queue, so it's always processed
         * after the function to hide the list.
         */
        reShowList: function() {
            window.setTimeout(function() { $(SELECTORS.NOTIFICATIONBUTTON).addClass('open'); }, 0);
        },

        /**
         * Display the confirmation modal for stopping notifications on a piece of content.
         *
         * @param {Event} e
         */
        stopNotifications: function(e) {
            e.preventDefault();
            t.reShowList();
            var template;
            var notification = $(e.target).closest('.openstudio-notification');
            var contentid = $(e.currentTarget).data('contentid');
            var commentid = $(e.currentTarget).data('commentid');
            if (commentid) {
                template = 'mod_openstudio/commentnotification_modal';
            } else {
                template = 'mod_openstudio/notification_modal';
            }
            Templates
                .render(template, {
                    name: notification.find('.openstudio-notification-message a').text(),
                    contentid: contentid,
                    commentid: commentid
                })
                .done(function(html) {
                    t.modal = new Modal(html);
                    t.modal.body.find('button').on('click', t.handleModalButton);
                    t.modal.show();
                });
        },

        /**
         * Handle the confirmation and cancel buttons from the stop modal.
         * @param {Event} e
         */
        handleModalButton: function(e) {
            t.reShowList();
            var target = $(e.target);
            if (target.data('action') === "yes") {
                M.util.js_pending('openstudioStopNotifications');
                var method;
                var commentid = target.data('commentid');
                var args = {
                    cmid: priv.cmid,
                    cid: target.data('contentid'),
                    fid: priv.followflag
                };
                if (commentid) {
                    args.commentid = commentid;
                    method = 'mod_openstudio_external_flag_comment';
                } else {
                    args.mode = 'off';
                    method = 'mod_openstudio_external_flag_content';
                }
                var flagpromises = Ajax.call([{
                    methodname: method,
                    args: args
                }]);
                flagpromises[0]
                    .done(function() {
                        $('.openstudio-notifications-list button[data-contentid="' + target.data('contentid') + '"]').remove();
                        M.util.js_complete('openstudioStopNotifications');
                    })
                    .fail(function() {
                        window.console.error('Could not un-follow content');
                        M.util.js_complete('openstudioStopNotifications');
                    });
            }
            t.modal.hide();
        },

        readNotifications: function(e) {
            e.preventDefault();
            var ids = $(SELECTORS.UNREAD).map(function() {
                return $(this).data("id");
            }).get();

            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_read_notifications',
                args: {
                    cmid: priv.cmid,
                    ids: ids
                }
            }]);

            promises[0]
                .done(function() {
                    $('.openstudio-navigation-notification-text').hide();
                    // Only mark read once per page load.
                    $(SELECTORS.NOTIFICATIONBUTTON).off('click', t.readNotifications);
                })
                .fail(function(ex) {
                    window.console.error('Could not mark notifications read. ' + ex.message);
                });
        }
    };

    return t;
});
