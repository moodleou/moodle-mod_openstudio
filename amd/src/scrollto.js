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
 * JavaScript for common use.
 *
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/scrollto
 */
define([], function() {
    var t;
    t = {

        /**
         * Scroll to element.
         * @param {Object} $el DOMElement.
         * @param {Int} heightTop Distance from DOMElement to top of browser
         * @method scrollToEl
         */
        scrollToEl: function($el, heightTop) {
            var pos = document.documentElement.scrollTop || window.scrollY;
            if (pos === undefined) {
                pos = 0;
            }
            var int = setInterval(function() {
                pos += 20;
                window.scrollTo(0, pos);
                if (pos >= ($el.offset().top - heightTop)) {
                    clearInterval(int);
                }
            }, 10);
        }
    };

    return t;
});
