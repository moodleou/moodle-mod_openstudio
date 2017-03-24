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

            t.handleIsotope();
            t.handleGroupSwitcher();
            t.handleViewSizeSwitcher();
            t.handleBlockSwitcher();
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
                        gutter: 20
                    }
                });
            });

        },

        /**
         * Handle when user filter by group.
         *
         * @method handleGroupSwitcher
         */
        handleGroupSwitcher: function() {
            $('#filter_groupid').change(function() {

                t.redirectURL();

            });

        },

        /**
         * Handle when user filter by block.
         *
         * @method handleBlockSwitcher
         */
        handleBlockSwitcher: function() {
            $('#filter_groupid').change(function() {

                t.redirectURL();

            });

        },

        /**
         * Handle when user filter by view number.
         *
         * @method handleViewSizeSwitcher
         */
        handleViewSizeSwitcher: function() {
            $('#filter_block_activity').change(function() {

                t.redirectURL();

            });

        },

        /**
         * Handle redirect URL.
         *
         * @method redirectURL
         */
        redirectURL: function() {
            var url = $('#view_sort_action_url').val();
            var vid = $('#vid').val();

            url = url + '&vid=' + vid;

            if ($('#filter_groupid').length) {
                var groupid = $('#filter_groupid').val();
                url = url + '&groupid=' + groupid;
            }

            if ($('#filter_pagesize').length) {
                var pagesize = $('#filter_pagesize').val();
                url = url + '&pagesize=' + pagesize;
            }

            if ($('#filter_block_activity').length) {
                var blockid = $('#filter_block_activity').val();
                url = url + '&blockid=' + blockid;
            }

            window.location.href = url;

        }

    };

    return t;

});
