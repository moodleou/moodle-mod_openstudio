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
 * JavaScript to order posts on folder.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/orderposts
 */
define([
    'jquery',
    'core/ajax',
    'core/str',
    'core/templates',
    'require'
], function($, Ajax, Str, Templates, require) {
    var t;
    t = {

        /**
         * Module config. Passed from server side.
         * {
         *     contents: [
         *         {
         *             fullname: string,
         *             date: string,
         *             userpictureurl: string,
         *             order: int,
         *             orderstring: string,
         *             locked: bool
         *         }
         *     ]
         * }
         */
        mconfig: null,

        /**
         * List out all of css selector used in this module.
         */
        CSS: {
            // Buttons.
            ORDER_POSTS_BUTTON: '#id_orderpost',
            MOVE_UP_BUTTON: '.openstudio-orderpost-item-moveup-button',
            MOVE_DOWN_BUTTON: '.openstudio-orderpost-item-movedown-button',
            SAVE_ORDER_BUTTON: '.openstudio-orderpost-save-button',

            // Input.
            ORDER_NUMBER_INPUT: '.openstudio-orderpost-item-order-number-input',

            // Post item.
            ITEM_CONTAINER: '.openstudio-orderpost-item',
            ITEM_ORDER: '.openstudio-orderpost-item-order',

            // Dialogue.
            ORDER_POSTS_DIALOGUE_CONTAINER: '.openstudio-orderposts-dialogue'
        },

        /**
         * Initialize module.
         *
         * @method init
         * @param {JSON} options  The settings for this feature.
         */
        init: function(options) {

            t.mconfig = options;

            // Create order posts dialogue.
            Y.use('moodle-core-notification-dialogue', function() {
                require(['mod_openstudio/osdialogue'], function(osDialogue) {
                    t.dialogue = t.createOrderPostsDialogue(osDialogue);
                });
            });
            // Register events.
            $(t.CSS.ORDER_POSTS_BUTTON).on('click', function() {
                if (t.dialogue) {
                    $('.openstudio-folder-posts-dialogue .openstudio-orderpost').remove();
                    t.setBody(t.dialogue);
                    t.dialogue.show();
                    var listorder = t.getListOrderPost();
                    var sortedlistorderpost = [];
                    $.each(listorder, function(index, value) {
                        sortedlistorderpost[index] = value[0];
                    });
                    t.mconfig.listorder = sortedlistorderpost.join(',');
                    setTimeout(function() {
                        t.resize();
                    }, 200);
                }
            });

            $('body')
                .delegate(t.CSS.SAVE_ORDER_BUTTON, 'click', t.saveOrder)
                .delegate(t.CSS.MOVE_UP_BUTTON, 'click', t.moveUp)
                .delegate(t.CSS.MOVE_DOWN_BUTTON, 'click', t.moveDown)
                .delegate(t.CSS.ORDER_NUMBER_INPUT, 'keyup', t.moveTo);

            // Responsive.
            $(window).resize(t.resize.bind(t));
        },

        /**
         * Create order posts dialogue and some events on it.
         *
         * @param {object} osDialogue object
         * @return {object} OSDialogue instance
         * @method createDeleteDialogue
         */
        createOrderPostsDialogue: function(osDialogue) {
            /**
             * Set header for dialog
             * @method setHeader
             */
            function setHeader() {
                Str
                    .get_string('folderorderpost', 'mod_openstudio')
                    .done(function(s) {
                        dialogue.set('headerContent', '<span>' + s + '</span>');
                    });
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
                extraClasses: [
                    t.CSS.ORDER_POSTS_DIALOGUE_CONTAINER.replace('.', ''),
                    'openstudio-folder-posts-dialogue'
                ]
            });

            setHeader();
            t.setBody(dialogue);

            return dialogue;
        },

        /**
         * Set body for dialog
         * @param {object} dialogue object
         * @method setBody
         */
        setBody: function(dialogue) {

            M.util.js_pending('openstudioGetOrderPostsFolderContent');
            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_get_order_posts',
                args: {
                    cmid: t.mconfig.cmid,
                    folderid: t.mconfig.folderid
                }
            }]);

            promises[0]
                .done(function(res) {
                   dialogue.set('bodyContent', res.html);
                    // Disable first move up button and last move down button.
                    t.disableFirstLastButton();
                    t.disableContentButton();
                    t.checkReorder();
                    t.inputPosition();
                    $(t.CSS.SAVE_ORDER_BUTTON).attr("disabled", "disabled");
                })
                .always(function() {
                    M.util.js_complete('openstudioGetOrderPostsFolderContent');
                })
                .fail(function(ex) {
                    window.console.error('Log request failed ' + ex.message);
                });
        },

        /**
         * Move item up.
         *
         * @method moveUp
         */
        moveUp: function() {
            var item = $(this).closest(t.CSS.ITEM_CONTAINER);
            var itemOrder = item.find(t.CSS.ITEM_ORDER).attr('data-order');
            itemOrder = parseInt(itemOrder);

            // Swap items.
            t.swapItems(itemOrder, itemOrder - 1, item);
        },

        /**
         * Move item down.
         *
         * @method moveDown
         */
        moveDown: function() {
            var item = $(this).closest(t.CSS.ITEM_CONTAINER);
            var itemOrder = item.find(t.CSS.ITEM_ORDER).attr('data-order');
            itemOrder = parseInt(itemOrder);

            // Swap items.
            t.swapItems(itemOrder, itemOrder + 1, item);
        },

        /**
         * Move item to certain position.
         *
         * @param {object} e Dom Event
         * @method moveTo
         */
        moveTo: function(e) {
            if (e.which != 13) { // Press enter key.
                return;
            }

            var desiredOrder = $(this).val();

            var item = $(this).closest(t.CSS.ITEM_CONTAINER);
            var itemOrder = item.find(t.CSS.ITEM_ORDER).attr('data-order');
            itemOrder = parseInt(itemOrder);

            var lastOrder = $(t.CSS.ITEM_CONTAINER).last().find(t.CSS.ITEM_ORDER).attr('data-order');
            lastOrder = parseInt(lastOrder);

            var currentorder = $('div[data-order=' + $(this).attr('data-order') + ']').hasClass('openstudio-orderpost-item-canreorder');
            var itemMove = $('div[data-order=' + desiredOrder + ']').hasClass('openstudio-orderpost-item-canreorder');
            if (currentorder && itemMove) {
                Str
                .get_string('foldercontentcannotreorder', 'mod_openstudio')
                .done(function(s) {
                   t.showErrorMessage(s);
                });
                return;
            }

            // To cover the case that user's input is out of boundary.
            if (desiredOrder > lastOrder) {
                desiredOrder = lastOrder;
            }

            if (desiredOrder < 1) {
                desiredOrder = 1;
            }

            // Swap items.
            t.swapItems(itemOrder, desiredOrder, item);
        },

        /**
         * Save order to database.
         *
         * @method saveOrder
         */
        saveOrder: function() {
            var saveAllowed = true;
            $(t.CSS.ORDER_NUMBER_INPUT).each(function() {
                var order = $(this).val().trim();
                var item = $(this).closest(t.CSS.ITEM_CONTAINER);
                var itemOrder = item.find(t.CSS.ITEM_ORDER).attr('data-order');
                itemOrder = parseInt(itemOrder);
                if (order != itemOrder) {
                    var currentorder = $('div[data-order=' + $(this).attr('data-order') + ']').hasClass('openstudio-orderpost-item-canreorder');
                    var itemMove = $('div[data-order=' + order + ']').hasClass('openstudio-orderpost-item-canreorder');
                    if (currentorder && itemMove) {
                        saveAllowed = false;
                        Str
                        .get_string('foldercontentcannotreorder', 'mod_openstudio')
                        .done(function(s) {
                           t.showErrorMessage(s);
                        });
                    }
                    t.swapItems(itemOrder, order, item);
                }
            });

            if (!saveAllowed) {
                return;
            }
            M.util.js_pending('openstudioOrderPostsFolderContent');
            var textlistcontent = t.getListOrderPost(true);
            var newlistcontent = {};
            var currentorder = '';
            var neworder = '';

            // Get new order list.
            $.each(textlistcontent, function(index, value) {
                neworder = value[0];
                currentorder = value[1];
                if (neworder != currentorder) {
                    newlistcontent[currentorder - 1] = neworder;
                }
            });

            textlistcontent = '';
            $.each(newlistcontent, function(index, value) {
                textlistcontent += index + '-' + value + ',';
            });

            textlistcontent = textlistcontent.substr(0, textlistcontent.length - 1);

            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_order_posts',
                args: {
                    cmid: t.mconfig.cmid,
                    folderid: t.mconfig.folderid,
                    listorderporst: textlistcontent
                }
            }]);

            promises[0]
                .done(function() {
                    window.location.reload();
                })
                .always(function() {
                    M.util.js_complete('openstudioOrderPostsFolderContent');
                })
                .fail(function(ex) {
                    window.console.error('Log request failed ' + ex.message);
                });
        },

        /**
         * Swap position between 2 items.
         *
         * @param {int} fromOrderNumber Original order
         * @param {int} toOrderNumber Desired order
         * @param {object} element Dom Element
         * @method swapItems
         */
        swapItems: function(fromOrderNumber, toOrderNumber, element) {
            var targetElement = $(t.CSS.ITEM_ORDER + '[data-order="' + toOrderNumber + '"]');
            if (targetElement.length > 0) {
                if (fromOrderNumber < toOrderNumber) {
                    // Move down
                    targetElement.closest(t.CSS.ITEM_CONTAINER).after(element);
                } else {
                    // Move up
                    targetElement.closest(t.CSS.ITEM_CONTAINER).before(element);
                }

                // Re-range the order numbers.
                $(t.CSS.ITEM_CONTAINER).each(function(index) {
                    index = index + 1;
                    var orderNumberString = (index < 10) ? '0' + index : '' + index;
                    $(this).find(t.CSS.ITEM_ORDER)
                        .attr('data-order', index)
                        .text(orderNumberString);

                    $(this).find(t.CSS.ORDER_NUMBER_INPUT)
                        .attr('value', index);
                });
            }

            // Handle Save Order Button
            var currentorder = t.mconfig.listorder;
            var sortable = t.getListOrderPost();
            var sortedlistorderpost = [];
            $.each(sortable, function(index, value) {
                sortedlistorderpost[index] = value[0];
            });
            var neworder = sortedlistorderpost.join(',');

            t.enableSaveOrder(currentorder, neworder);
            t.checkReorder();
            t.disableContentButton();
            t.disableFirstLastButton();
            t.inputPosition();
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
                if (Y.one('body').get('winWidth') < t.dialogue.get('responsiveWidth')) {
                    t.dialogue.makeResponsive();
                    $(t.CSS.SAVE_ORDER_BUTTON).removeClass('osep-smallbutton');
                } else {
                    $(t.CSS.SAVE_ORDER_BUTTON).addClass('osep-smallbutton');
                    if (Y.one('body').get('winWidth') >= 767) {
                        t.dialogue.set('width', 640);
                    } else {
                        t.dialogue.set('width', 500);
                    }
                    t.dialogue.centerDialogue();
                }
            }
        },

        /**
         * Get list order porst according to their new positions
         *
         * @return {string} textlistcontent list of order post
         * @method getListOrderPost
         */
        getListOrderPost: function(filterBookContent) {
            var listorderporst = {};
            // Re-range the order numbers.
            $(t.CSS.ITEM_CONTAINER).each(function() {
                var orderElement = $(this).find(t.CSS.ITEM_ORDER);
                if (filterBookContent) {
                    if (orderElement.hasClass('openstudio-orderpost-item-book')) {
                        return;
                    }
                }
                listorderporst[orderElement.attr('data-order')]
                    = orderElement.attr('data-original-order');
            });

            var sortable = [];
            for (var order in listorderporst) {
                sortable.push([order, listorderporst[order]]);
            }

            sortable.sort(function(a, b) {
                return a[1] - b[1];
            });

            return sortable;
        },

        /**
         * Enable save order .
         *
         * @param {string} currentorder list of current order
         * @param {string} neworder list of new order
         * @method enableSaveOrder
         */
        enableSaveOrder: function(currentorder, neworder) {
            if (currentorder != neworder) {
                $(t.CSS.SAVE_ORDER_BUTTON).removeAttr("disabled");
            } else {
               $(t.CSS.SAVE_ORDER_BUTTON).attr("disabled", "disabled");
            }
        },

        /**
         * Input move item to certain position.
         *
         * @method enableSaveOrder
         */
        inputPosition: function() {
            $(document).ready(function() {
                $(t.CSS.ORDER_NUMBER_INPUT).keyup(function() {
                    t.enableSaveOrder($(this).val().length, 0);
                    var order = $(this).val();
                    if (order && !$.isNumeric(order)) {
                        Str
                        .get_string('errormoveslotnumeric', 'mod_openstudio')
                        .done(function(s) {
                           t.showErrorMessage(s);
                        });
                    } else {
                        if (parseInt(order) <= 0) {
                            Str
                            .get_string('errormoveslotduplicate', 'mod_openstudio')
                            .done(function(s) {
                               t.showErrorMessage(s);
                            });
                        } else if (parseInt(order) > t.mconfig.total) {
                            Str
                            .get_string('errormoveslotoutofrange', 'mod_openstudio')
                            .done(function(s) {
                               t.showErrorMessage(s);
                            });
                        } else {
                            t.showErrorMessage('');
                        }
                    }
                    $(this).val(order);
                });
            });
        },

        /**
         * Disable first move up button and last move down button.
         *
         * @method disableFirstLastButton
         */
        disableFirstLastButton: function() {
            // Enable all buttons first when move one button.
            $(t.CSS.MOVE_UP_BUTTON).removeAttr("disabled");
            $(t.CSS.MOVE_DOWN_BUTTON).removeAttr("disabled");

            $(t.CSS.MOVE_UP_BUTTON + ':first').attr("disabled", "disabled");
            $(t.CSS.MOVE_DOWN_BUTTON + ':last').attr("disabled", "disabled");

        },

        /**
         * Disable move up button and last move down button.
         *
         * @method enableSaveOrder
         */
        disableContentButton: function() {
            $(document).ready(function() {
                $('.openstudio-orderpost-item-book').attr("disabled", "disabled");
                $('.openstudio-orderpost-item-disabled').click(function() {
                    Str
                    .get_string('foldercontentcannotreorder', 'mod_openstudio')
                    .done(function(s) {
                       t.showErrorMessage(s);
                    });
                    return false;
                });
            });
        },

        /**
         * Disable move up button and move down button.
         *
         * @method checkReorder
         */
        checkReorder: function() {
            $(t.CSS.ITEM_CONTAINER).each(function() {
                var classCheck = 'openstudio-orderpost-item-canreorder';
                if ($(this).find(t.CSS.ITEM_ORDER).hasClass('openstudio-orderpost-item-canreorder')) {
                    var nextItem = $(this).next();
                    if (nextItem.find(t.CSS.ITEM_ORDER).hasClass(classCheck)) {
                        t.disableContentButton();
                    } else {
                        t.showErrorMessage('');
                    }
                }

            });
        },

        /**
         * Show error message in the title next  label text.
         *
         * @method showErrorMessage
         * @param {string} message The error text to use
         */
        showErrorMessage: function(message) {
            $('.openstudio-message-error').text(message);
        }
    };

    return t;
});
