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
         * Isotope instance.
         * There are more than a grid in a page
         */
        isotope: [],

        /**
         * Module config. Passed from server side.
         * {
         *     searchtext: string
         * }
         */
        mconfig: null,

        /**
         * List out all of css selector used in this module.
         */
        CSS: {
            SEARCHBOX: '#oustudyplan-query'
        },

        /**
         * Initialise this module.
         *
         * @param {JSON} options  The settings for this feature.
         */
        init: function(options) {

            t.mconfig = options || {searchtext: ''};

            t.handleFilter();
            t.handleIsotope();
            t.handleGroupSwitcher();
            t.handleViewSizeSwitcher();
            t.handleBlockSwitcher();
            t.initSearchForm();
            t.handleCollapseClick();
        },

        /**
         * Handle items will be shown via Isotope masonry template.
         *
         * @method handleTooltip
         */
        handleIsotope: function() {
            var container = $('.openstudio-grid');
            container.each(function() {
                t.isotope.push(new Isotope('#' + this.id, {
                    layoutMode: 'masonry',
                    itemSelector: '.openstudio-grid-item',
                    masonry: {
                        columnWidth: 243,
                        gutter: 20,
                        horizontalOrder: true
                    }
                }));
            });

            // Once all images loaded, try to re-arrange all items.
            var imgs = container.find('img').not(function() {
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
                $.each(t.isotope, function(i, grid) {
                    grid.layout();
                });
            }, 1000);
        },

        /**
         * Handle when user filter by group.
         *
         * @method handleGroupSwitcher
         */
        handleGroupSwitcher: function() {
            // This should work for mobile also.
            $(document).on('change','#filter_groupid',function(){
                t.redirectURL();
            });
        },

        /**
         * Handle when user filter by block.
         *
         * @method handleBlockSwitcher
         */
        handleBlockSwitcher: function() {
            // This should work for mobile also.
            $(document).on('change','#filter_block_activity',function(){
                t.redirectURL();
            });
        },

        /**
         * Handle when user filter by view number.
         *
         * @method handleViewSizeSwitcher
         */
        handleViewSizeSwitcher: function() {
            // This should work for mobile also.
            $(document).on('change','#filter_pagesize',function(){
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

            $('.openstudio-filter-select-box:visible').each(function() {
                var name = $(this).attr('name');
                url = url + '&' + name + '=' + $(this).val();
            });

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

            $('.openstudio-filter-types-checkbox').on("click", function() {
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
            $('#openstudio_filter_user_flags_0').on("click", function(e) {
                var checkbox = $(this);

                // Prevent checkbox from unchecking when clicked.
                if (!checkbox.is(":checked")) {
                    e.preventDefault();
                    return false;
                }

                $('.openstudio-filter-user-flags-checkbox').prop("checked", false);
                $('#openstudio_filter_user_flags_0').prop("checked", true);
            });

            $('.openstudio-filter-user-flags-checkbox').on("click", function() {
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
            $('#reset_filter_btn').on('click', function() {
                $('#reset_filter').val(1);
                $('#filteractive').val(0);
                $('#openstudio-filter-form').submit();

            });

            // Set Blocks option selected when a block checked.
            // When do not has any block checked, all option should be selected.
            $('.openstudio-filter-block').on('click', function() {
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
            $('#filter_block').on('change', function() {
                var filter_block = $(this).val();
                var filter_area_activity_value = $('#filter_area_activity_value').val();

                if (parseInt(filter_block) == filter_area_activity_value) {
                    $('#filter_block').val(0);
                } else {
                    $('.openstudio-filter-block').prop("checked", false);
                }
            });

            // Some works to help page accessible.
            // Because collapse/expand mechanism of bootstrap is that setting the height of target element to 0
            // and overflow to hidden. So target element is invisible but focusable.
            // So when pressing tab key, the invisible elements is focused also.
            $('.openstudio-filter-link a').on('click', function() {
                var isCollapsed = !($(this).hasClass('collapsed'));
                $($(this).data('target')).find('input, select').attr('tabindex', isCollapsed ? -1 : 0);
            });

            $('.openstudio-filter-link a.collapsed').each(function() {
                $($(this).data('target')).find('input, select').attr('tabindex', -1);
            });
        },
        handleCollapseClick: function() {
            var myprofile = document.querySelector('.openstudio-profile-mypaticipation-content');
            var fullusername = document.getElementById('openstudio_profile_fullusername');
            var profilleparticipate = document.querySelector('#openstudio_profile_bar .openstudio-profile-bar-participation-content');
            var profilleparticipatemobile = document.querySelector('#openstudio_profile_bar_mobile .openstudio-profile-bar-participation-content');
            var progressbar = document.getElementsByClassName('openstudio-profile-progress-content');
            var showprofile = null;
            var showparticipate = null;
            var body = document.querySelector('body');
            for(var i = 0; i < progressbar.length; i++) {
                if (progressbar && progressbar[i] && progressbar[i].clientHeight > 0) {
                    showprofile = progressbar[i];
                }
            }
            if (body.classList.contains('ios') || body.classList.contains('android')) {
                showparticipate = profilleparticipatemobile;
            } else {
                showparticipate = profilleparticipate;
            }
            if (fullusername) {
                var originalmarginTop = showprofile.style.marginTop ? showprofile.style.marginTop : '0px';
                var originalpaddingTop = showprofile.style.paddingTop ? showprofile.style.paddingTop : '0px';
                fullusername.addEventListener('click', function() {
                    var w = window.innerWidth;
                    if ((w > 767 && w < 1204 && (!body.classList.contains('ios') && !body.classList.contains('android')))
                        || (w > 767 && w < 940 && (body.classList.contains('ios') || body.classList.contains('android')))) {
                        if(fullusername.classList.contains('collapsed')) {
                            setTimeout(function() {
                                // Two line
                                if (showparticipate && fullusername.clientHeight > 21) {
                                    if (myprofile.getBoundingClientRect().height > showparticipate.getBoundingClientRect().height) {
                                        if (showprofile.getBoundingClientRect().top > myprofile.getBoundingClientRect().bottom) {
                                            showprofile.style.marginTop = originalmarginTop + 3 + 'px';
                                        } else {
                                            showprofile.style.marginTop = myprofile.getBoundingClientRect().bottom - showprofile.getBoundingClientRect().top + 'px';
                                        }
                                    } else {
                                        if (showprofile.getBoundingClientRect().top > showparticipate.getBoundingClientRect().bottom) {
                                            showprofile.style.marginTop = originalmarginTop + 3 + 'px';
                                        } else {
                                            showprofile.style.marginTop = showparticipate.getBoundingClientRect().bottom - showprofile.getBoundingClientRect().top + 'px';
                                        }
                                    }
                                    if (body.classList.contains('ios') || body.classList.contains('android')) {
                                        showprofile.style.paddingTop = '5px';
                                    } else {
                                        showprofile.style.paddingTop = '9px';
                                    }
                                }

                            }, 500);
                        } else {
                            showprofile.style.paddingTop = originalpaddingTop;
                            showprofile.style.marginTop = originalmarginTop;
                        }
                     }
                });
            }
        },

        /**
         * Init query string for search form.
         * Because oustudyplan renderer does not allow us to set value for input while rendering search form.
         *
         * @method initSearchForm
         */
        initSearchForm: function() {
            $(t.CSS.SEARCHBOX).val(t.mconfig.searchtext);
        }

    };

    return t;

});
