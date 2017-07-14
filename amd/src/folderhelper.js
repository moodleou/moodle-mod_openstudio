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
 * JavaScript to manage on view page.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/folderhelper
 */

define(['jquery', 'amd/build/isotope.pkgd.min.js'], function($, Isotope) {
    var t;

    t = {

        /**
         * Initialise this module.
         *
         */
        init: function() {

            t.handleIsotope();
        },

        /**
         * Isotope instance
         */
        isoTope: null,

        /**
         * Handle items will be shown via Isotope masonry template.
         *
         * @method handleTooltip
         */
        handleIsotope: function() {

            var containerCLass = '.openstudio-folder-items';
            var colWidth = 243;
            var gutters = 20;
            if ($(window).width() <= 1024) {
                colWidth = 175;
                gutters = 15;
            }
            t.isoTope = new Isotope(containerCLass, {
                layoutMode: 'masonry',
                itemSelector: '.openstudio-folder-item',
                masonry: {
                    columnWidth: colWidth,
                    gutter: gutters,
                    horizontalOrder: true
                }
            });

            // Once all images loaded, try to re-arrange all items.
            var imgs = $(containerCLass).find('img').not(function() {
                return this.complete;
            });

            var count = imgs.length;

            if (count) {
                imgs.on('load', function() {
                    count--;
                    if (!count) {
                        t.reArrangeItems();
                    }
                });
            } else {
                t.reArrangeItems();
            }

        },

        /**
         * Re-arrange items
         *
         * @method reArrangeItems
         */
        reArrangeItems: function() {
            setTimeout(function() {
                t.isoTope.layout();
            }, 1000);
        },
    };

    return t;

});
