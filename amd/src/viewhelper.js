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
 * @module mod_openstudio/viewhelper
 */

define(['jquery', 'amd/build/isotope.pkgd.min.js'], function($, Isotope) {
    var t;

    t = {

        /**
         * Initialise this module.
         *
         */
        init: function() {

            t.handleTooltip();
            t.handleIsotope();

        },

        /**
         * Handle tooltip for each block with Bootstrap when hover.
         *
         * @method handleTooltip
         */
        handleTooltip: function() {

            $('[data-toggle="tooltip"]').tooltip();

        },

        /**
         * Handle items will be shown via Isotope masonry template.
         *
         * @method handleTooltip
         */
        handleIsotope: function() {

            $('.openstudio-grid').each(function() {
                new Isotope('#' + this.id, {
                    layoutMode: 'masonry',
                    itemSelector: '.openstudio-grid-item',
                    masonry: {
                        columnWidth: 243,
                        gutter: 23
                    }
                });
            });

        }

    };

    return t;

});
