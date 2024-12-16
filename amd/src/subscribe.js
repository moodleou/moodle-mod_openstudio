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
 * JavaScript to manage subscribe/unsubscribe feature.
 *
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/subscribe
 */
define([
    'jquery',
    'core/templates',
    'core/str',
    'core/ajax',
    'mod_openstudio/osdialogue',
], function($, Templates, Str, ajax, osDialogue) {
    var t;
    t = {

        /**
         * Subscription dialogue instance.
         */
        dialogue: null,

        /**
         * Module config. Passed from server side.
         */
        mconfig: null,

        /**
         * List out all of css selector used in subscription module.
         */
        CSS: {
            SUBSCRIBESETTING: '.openstudio-subscribe-button',
            BOUNDINGBOX: '.openstudio-subscribe-dialog-container',
            DIALOGHEADER: '.openstudio-subscription-header',
            SUBSCRIBEBUTTON: 'button[name="subscribebutton"]'
        },

        /**
         * Initialize module.
         *
         * @param {JSON} options  The settings for subscription feature.
         * @method init
         */
        init: async function(options) {
            t.mconfig = options;

            // Create subscription dialogue.
            t.dialogue = await t.createSubscriptionDialogue();

            // Click event on subscription button.
            $(t.CSS.SUBSCRIBEBUTTON).on('click', function() {
                if (t.issubscribed()) {
                    t.unsubscribe();
                } else {
                    t.dialogue.show();
                }
            });
        },

        /**
         * Create subscription dialogue and some events on it.
         *
         * @returns {Promise<Modal>}
         * @method createSubscriptionDialogue
         */
        createSubscriptionDialogue: async function() {
            /**
             * Set header for dialog.
             *
             * @param {Object} dialogue
             * @method setHeader
             */
            function setHeader(dialogue) {
                Str
                    .get_string('subscriptiondialogheader', 'mod_openstudio')
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
                Templates
                    .render('mod_openstudio/subscribe_dialog', t.mconfig.constants)
                    .done(function(html) {
                        dialogue.setBody(html);
                    });
            }

            /**
             * Set footer for dialog.
             *
             * @param {Object} dialogue
             * @method setFooter
             */
            function setFooter(dialogue) {
                Str
                    .get_strings([
                        {key: 'subscriptiondialogcancel', component: 'mod_openstudio'},
                        {key: 'subscribe', component: 'mod_openstudio'},
                    ])
                    .done(function(s) {
                        // Button [Cancel].
                        const cancelBtnProperty = {
                            name: 'cancel',
                            label: s[0],
                            classNames: 'openstudio-cancel-btn',
                            action: 'hide',
                        };
                        dialogue.addButton(cancelBtnProperty, ['footer']);

                        // Button [Subscribe].
                        const subscribeBtnProperty = {
                            name: 'subscribe',
                            label: s[1],
                            classNames: 'openstudio-subscript-btn',
                            events: {
                                click: t.subscribe.bind(t),
                            },
                        };
                        dialogue.addButton(subscribeBtnProperty, ['footer']);
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
         * Subscribe
         * @method subscribe
         */
        subscribe: function() {
            /**
             * Do subscribe.
             * @method doSubscribe
             */
            function doSubscribe() {

                var args = {
                    openstudioid: t.mconfig.openstudioid,
                    emailformat: $('select[name="openstudio-subscription-email-format"]').val(),
                    frequency: $('select[name="openstudio-subscription-email-frequency"]').val(),
                    userid: t.mconfig.userid
                };

                M.util.js_pending('openstudioSubscribe');

                var promises = ajax.call([{
                    methodname: 'mod_openstudio_external_subscribe',
                    args: args
                }]);

                promises[0]
                    .done(function(res) {
                        updateSubscriptionButtonState(res.subscriptionid);
                    })
                    .always(function() {
                        M.util.js_complete('openstudioSubscribe');
                    })
                    .fail(function(ex) {
                        window.console.error('Log request failed ' + ex.message);
                    });
            }

            /**
             * Set Subscribe button text.
             * @param {int} subscriptionid
             * @method updateSubscriptionButtonState
             */
            function updateSubscriptionButtonState(subscriptionid) {
                Str
                    .get_string('unsubscribe', 'mod_openstudio')
                    .done(function(s) {
                        $(t.CSS.SUBSCRIBEBUTTON).text(s);
                        $('#openstudio_subscribe_button').focus();
                    });

                $(t.CSS.SUBSCRIBEBUTTON).attr('subscriptionid', subscriptionid);
            }

            t.dialogue.hide();
            doSubscribe();
        },

        /**
         * Unsubscribe.
         * @method unsubscribe
         */
        unsubscribe: function() {
            /**
             * Do subscribe.
             * @method doUnsubscribe
             */
            function doUnsubscribe() {
                var args = {
                    subscriptionid: $(t.CSS.SUBSCRIBEBUTTON).attr('subscriptionid'),
                    userid: t.mconfig.userid,
                    cmid: t.mconfig.cmid
                };

                M.util.js_pending('openstudioUnsubscribe');

                var promises = ajax.call([{
                    methodname: 'mod_openstudio_external_unsubscribe',
                    args: args
                }]);

                promises[0]
                    .done(function() {
                        updateSubscriptionButtonState();
                    })
                    .always(function() {
                        M.util.js_complete('openstudioUnsubscribe');
                    })
                    .fail(function(ex) {
                        window.console.warn('Log request failed ' + ex.message);
                    });
            }

            /**
             * Set Subscribe button text.
             * @method updateSubscriptionButtonState
             */
            function updateSubscriptionButtonState() {
                Str
                    .get_string('subscribetothisstudio', 'mod_openstudio')
                    .done(function(s) {
                        $(t.CSS.SUBSCRIBEBUTTON).text(s);
                    });

                $(t.CSS.SUBSCRIBEBUTTON).removeAttr('subscriptionid');
            }

            doUnsubscribe();
        },

        /**
         * Check if Studio is subscribed.
         * @method issubscribed
         * @return {boolean}
         */
        issubscribed: function() {
            return ($(t.CSS.SUBSCRIBEBUTTON)[0].hasAttribute('subscriptionid'));
        },
    };

    return t;
});
