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

            t.handleFilter();
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
            $('#filter_block_activity').change(function() {

                t.redirectURL();

            });

        },

        /**
         * Handle when user filter by view number.
         *
         * @method handleViewSizeSwitcher
         */
        handleViewSizeSwitcher: function() {
            $('#filter_pagesize').change(function() {

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

        },

        /**
         * Handle actions for filter.
         *
         * @method handleFilter
         */
        handleFilter: function() {

            // By post types.
            $('#openstudio_filter_types_0').on('click', function (e) {
                var checkbox = $(this);

                // Prevent checkbox from unchecking when clicked.
                if (!checkbox.is(":checked")) {
                    e.preventDefault();
                    return false;
                }

                $('.openstudio-filter-types-checkbox').prop("checked", false);
                $('#openstudio_filter_types_0').prop("checked", true);
            });

            $('.openstudio-filter-types-checkbox').on("click", function (e) {
                var checkbox = $(this);

                if (checkbox.is(":checked") && checkbox.attr('id') != "openstudio_filter_types_0") {
                    $('#openstudio_filter_types_0').prop("checked", false);
                }

                var length = $('[name="ftypearray[]"]:checked').length;

                if (length == 0) {
                    $('#openstudio_filter_types_0').prop("checked", true);
                }
            });


            // By user flags.
            $('#openstudio_filter_user_flags_0').on("click", function (e) {
                var checkbox = $(this);

                // Prevent checkbox from unchecking when clicked.
                if (!checkbox.is(":checked")) {
                    e.preventDefault();
                    return false;
                }

                $('.openstudio-filter-user-flags-checkbox').prop("checked", false);
                $('#openstudio_filter_user_flags_0').prop("checked", true);
            });

            $('.openstudio-filter-user-flags-checkbox').on("click", function (e) {
                var checkbox = $(this);

                if (checkbox.is(":checked") && checkbox.attr('id') != "openstudio_filter_types_0") {
                    $('#openstudio_filter_user_flags_0').prop("checked", false);
                }

                var length = $('[name="fflagarray[]"]:checked').length;

                if (length == 0) {
                    $('#openstudio_filter_user_flags_0').prop("checked", true);
                }
            });

            // Reset button.
            $('#reset_filter_btn').on('click', function (e) {
                $('#reset_filter').val(1);
                $('#filteractive').val(0);
                $('#openstudio-filter-form').submit();

            });

            // Set Blocks option selected when a block checked.
            // When do not has any block checked, all option should be selected.
            $('.openstudio-filter-block').on('click', function (e) {
                var checkbox = $(this);
                var filter_area_activity_value = $('#filter_area_activity_value').val();
                var length = $('[name="fblockarray[]"]:checked').length;

                if (checkbox.is(":checked")) {
                    $('#filter_block').val(filter_area_activity_value);
                }

                if (length == 0) {
                    $('#filter_block').val(0);
                }
            });

            // Uncheck all blocks option when ALl/Pinboard selected.
            $('#filter_block').on('change', function (e) {
                var filter_block = $(this).val();
                var filter_area_activity_value = $('#filter_area_activity_value').val();

                if (parseInt(filter_block) == filter_area_activity_value) {
                    $('#filter_block').val(0);
                } else {
                    $('.openstudio-filter-block').prop("checked", false);
                }
            });
        }

    };

    return t;

});
