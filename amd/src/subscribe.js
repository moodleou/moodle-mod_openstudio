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
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/subscribe
 */
define([
    'jquery',
    'core/yui',
    'core/templates',
    'core/str',
], function($, Y, Templates, Str) {
    var t;
    t = {

        /**
         * M.core.dialog instance
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
        init: function(options) {
            t.mconfig = options;
            t.dialogue = t.createSubscriptionDialogue(t.CSS.BOUNDINGBOX);

            // Click event on subscription button.
            $(t.CSS.SUBSCRIBEBUTTON).on('click', function() {
                if (t.issubscribed()) {
                    t.unsubscribe();
                } else {
                    t.dialogue.show();
                    t.resize();
                }
            });

            $(window).resize(t.resize.bind(t));
        },

        /**
         * Create subscription dialogue and some events on it.
         *
         * @param {string} boundingBoxClass The class wrapping the subscription dialog
         * @return M.core.dialog instance
         * @method createSubscriptionDialogue
         */
        createSubscriptionDialogue: function(boundingBoxClass) {
            /**
             * Set header for dialog
             * @method setHeader
             */
            function setHeader() {
                var headerstring = 'Subscription settings';
                Str
                    .get_string('subscriptiondialogheader', 'mod_openstudio')
                    .done(function(s) {
                        headerstring = s;
                    })
                    .always(function() {
                        dialogue.set('headerContent',
                            '<span class="' + t.CSS.DIALOGHEADER.replace('.', '') +
                            '">' + headerstring + '</span>');
                    });
            }

            /**
             * Set body for dialog
             * @method setBody
             */
            function setBody() {
                Templates
                    .render('mod_openstudio/subscribe_dialog', t.mconfig.constants)
                    .done(function(html) {
                        dialogue.set('bodyContent', html);
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
                    label: 'Cancel',
                    classNames: 'openstudio-cancel-btn',
                    action: 'hide'
                };
                Str
                    .get_string('subscriptiondialogcancel', 'mod_openstudio')
                    .done(function(s) {
                        cancelBtnProperty.label = s;
                    })
                    .always(function() {
                        dialogue.addButton(cancelBtnProperty, ['footer']);
                    });

                // Button [Subscribe]
                var subscribeBtnProperty = {
                    name: 'subscribe',
                    label: 'Subscribe',
                    classNames: 'openstudio-subscript-btn',
                    events: {
                        click: t.subscribe.bind(t)
                    }
                };

                Str
                    .get_string('subscribe', 'mod_openstudio')
                    .done(function(s) {
                        subscribeBtnProperty.label = s;
                    })
                    .always(function() {
                        dialogue.addButton(subscribeBtnProperty, ['footer']);
                    });
            }

            var dialogue = new M.core.dialogue({
                closeButton: true,
                visible: false,
                centered: false,
                responsive: true,
                focusOnPreviousTargetAfterHide: true,
                extraClasses: [boundingBoxClass.replace('.', '')],
            });

            setHeader();
            setBody();
            setFooter();

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
                require(['core/ajax'], function(ajax) {
                    var args = {
                        openstudioid: t.mconfig.openstudioid,
                        emailformat: $('select[name="openstudio-subscription-email-format"]').val(),
                        frequency: $('select[name="openstudio-subscription-email-frequency"]').val(),
                        userid: t.mconfig.userid
                    };

                    var promises = ajax.call([{
                        methodname: 'mod_openstudio_external_subscribe',
                        args: args
                    }]);

                    promises[0]
                        .done(function(res) {
                            if (res.success) {
                                updateSubscriptionButtonState(res.subscriptionid);
                            }
                        })
                        .fail(function(ex) {
                            window.console.error('Log request failed ' + ex.message);
                        });
                });
            }

            /**
             * Set Subscribe button text.
             * @param {int} subscriptionid
             * @method updateSubscriptionButtonState
             */
            function updateSubscriptionButtonState(subscriptionid) {
                var unsubscribestr = 'Unsubscribe';
                Str
                    .get_string('unsubscribe', 'mod_openstudio')
                    .done(function(s) {
                        unsubscribestr = s;
                    })
                    .always(function() {
                        $(t.CSS.SUBSCRIBEBUTTON).text(unsubscribestr);
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
                require(['core/ajax'], function(ajax) {
                    var args = {
                        subscriptionid: $(t.CSS.SUBSCRIBEBUTTON).attr('subscriptionid'),
                        userid: t.mconfig.userid,
                        cmid: t.mconfig.cmid,
                    };

                    var promises = ajax.call([{
                        methodname: 'mod_openstudio_external_unsubscribe',
                        args: args
                    }]);

                    promises[0]
                        .done(function(res) {
                            if (res.success) {
                                updateSubscriptionButtonState();
                            }
                        })
                        .fail(function(ex) {
                            window.console.warn('Log request failed ' + ex.message);
                        });
                });
            }

            /**
             * Set Subscribe button text.
             * @method updateSubscriptionButtonState
             */
            function updateSubscriptionButtonState() {
                var subscribestr = 'Subscribe to my studio';
                Str
                    .get_string('subscribetothisstudio', 'mod_openstudio')
                    .done(function(s) {
                        subscribestr = s;
                    })
                    .always(function() {
                        $(t.CSS.SUBSCRIBEBUTTON).text(subscribestr);
                    });

                $(t.CSS.SUBSCRIBEBUTTON).removeAttr('subscriptionid');
            }

            doUnsubscribe();
        },

        /**
         * Check if Studio is subscribed.
         * @method issubscribed
         */
        issubscribed: function() {
            return ($(t.CSS.SUBSCRIBEBUTTON)[0].hasAttribute('subscriptionid'));
        },

        /**
         * Resize and update dialogue position.
         * @method resize
         */
        resize: function() {
            var visible = t.dialogue.get('visible');

            // Is mobile view.
            if (Y.one('body').get('winWidth') > 767) {
                t.dialogue
                    .get('boundingBox')
                    .setStyles({
                       top: 3,
                       right: 200,
                       left: 'auto',
                       width: 316
                    });

                if (t.sizing == 'large') {
                    return;
                }

                // Update dialog sizing.
                t.dialogue.hide();
                t.sizing = 'large';
                $(t.CSS.SUBSCRIBESETTING).after($(t.CSS.BOUNDINGBOX).parent());

                if (visible) {
                    t.dialogue.show();
                }

                t.dialogue
                    .get('boundingBox')
                    .setStyles({
                       top: 3,
                       right: 200,
                       left: 'auto',
                       width: 316
                    });
            } else {
                if (t.sizing == 'small') {
                    return;
                }

                // Update dialog sizing.
                t.sizing = 'small';

                t.dialogue.hide();

                if (visible) {
                    t.dialogue.show();
                }

                $('body').after($(t.CSS.BOUNDINGBOX).parent());
            }
        },
    };

    return t;
});
