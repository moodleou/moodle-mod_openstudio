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
 * Extend Moodle dialogue.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/osdialogue
 */
define([
    'jquery',
    'core/yui'
], function($, Y) {
    var OSDIALOGUE_NAME = 'Open Studio dialogue',
        DIALOGUE_PREFIX = 'moodle-dialogue';
    var t = function() {
        // Set bouding class for Open Studio dialogue to be distinguishable with other Moodle dialogues.
        arguments[0].extraClasses = arguments[0].extraClasses || [];
        arguments[0].extraClasses.push('openstudio-dialogue');

        t.superclass.constructor.apply(this, arguments);
    };

    Y.use('moodle-core-notification-dialogue', function() {
        Y.extend(t, M.core.dialogue, {
            /**
             * Override addButton function of super class.
             * @param {object} property
             * @param {array} sections
             */
            addButton: function (property, sections) {
                var self = this;
                var button = '<button class="' + property.classNames + ' btn">' + property.label + '</button>';
                var sectionNode;

                $.each(sections, function (key, value) {
                    switch (value) {
                        case 'footer':
                            sectionNode = self.footerNode;
                            break;
                        default:
                            return;
                            break;
                    }

                    sectionNode.append(button);

                    var buttonNode = sectionNode.all('.' + property.classNames);
                    if (property.action == 'hide') {
                        buttonNode.on('click', self.hide.bind(self));
                    }

                    if (property.events) {
                        $.each(property.events, function (key, value) {
                            buttonNode.on(key, function () {
                                value.apply(self, arguments);
                            });
                        });
                    }
                });
            }
        }, {
            NAME: OSDIALOGUE_NAME,
            CSS_PREFIX: DIALOGUE_PREFIX
        });
    });

    return t;
});
