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
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/viewhelper
 */

define(['jquery', 'js/isotope.pkgd.min.js'], function($, Isotope) {
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
         * Element selectors.
         */
        SELECTOR: {
            FILTER_CONTAINER: '#filter_container',
            FILTER_FORM: '#openstudio-filter-form',
            SELECT_FROM: '#openstudio-filter-select-from',
            AREA_ALL: '#filter_block_0',
            AREA_ACTIVITIES_ID: '#filter_block_2',
            AREA_INPUT: '#openstudio-filter-select-from .openstudio-filter-area-input',
            BLOCK_ITEM: '#openstudio-filter-select-from .openstudio-filter-block',
            ACTIVITY_ITEM: '#openstudio-filter-select-from .openstudio-filter-block-activity',
            FILTER_SORT_BY: '.openstudio_filter_sort_by',
            FILTER_QUICK_SELECT: '.openstudio_filter_quick_select',
            FILTER_LINK: '.openstudio-filter-link a',
        },

        /**
         * Default config.
         */
        CONFIG: {
            searchtext: '',
            hasselectfrom: false,
            hasfilterform: false,
        },

        /**
         * Initialise this module.
         *
         * @param {JSON} options  The settings for this feature.
         */
        init: function(options) {
            t.mconfig = options || t.CONFIG;

            t.handleFilter();
            t.handleIsotope();
            t.handleGroupSwitcher();
            t.handleViewSizeSwitcher();
            t.handleBlockSwitcher();
            t.initSearchForm();
            t.handleCollapseClick();

            if (t.mconfig.hasOwnProperty('hasselectfrom') && t.mconfig.hasselectfrom === true) {
                t.handleSelectFrom(t.SELECTOR.SELECT_FROM);
            }
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
            $(window).on('load', function() {
                t.reArrangeItems();
            });
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
            $(document).on('change', '#filter_groupid', function() {
                t.redirectURL();
            });
        },

        /**
         * Handle when user filter by block.
         *
         * @method handleBlockSwitcher
         */
        handleBlockSwitcher: function() {

            $(t.SELECTOR.AREA_INPUT).on('click', function() {
                $(t.SELECTOR.AREA_INPUT).not(this).prop('checked', false);
            });

            $(t.SELECTOR.AREA_INPUT).on('change', function() {
                var value = $(this).val();
                var checked = $(this).is(':checked');
                var areaActivityValue = $('#filter_area_activity_value').val();
                var checkBlocks = false;

                if (checked) {
                    // If we check on activity, then all blocks and activities should be checked all.
                    if (parseInt(value) === parseInt(areaActivityValue)) {
                        checkBlocks = true;
                    }
                } else {
                    value = 0;
                    $(t.SELECTOR.AREA_ALL).prop('checked', true);
                }

                $(t.SELECTOR.BLOCK_ITEM).prop('checked', checkBlocks);
                $(t.SELECTOR.ACTIVITY_ITEM).prop('checked', checkBlocks);

                t.updateFilterBlock(value);
            });
        },

        /**
         * Update to the old filter block hidden input + change placeholder.
         *
         * @param {Integer} value
         */
        updateFilterBlock: function(value) {
            // Apply to old filter_block.
            $('#filter_block').val(value);

            // Change the placeholder.
            $(t.SELECTOR.SELECT_FROM + '-placeholder').html($('#filter_block option:selected').text());
        },

        /**
         * Handle when user filter by view number.
         *
         * @method handleViewSizeSwitcher
         */
        handleViewSizeSwitcher: function() {
            // This should work for mobile also.
            $(document).on('change', '#filter_pagesize', function() {
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
            var groupidelement = $('#groupid');
            if (groupidelement.length > 0) {
                var groupid = groupidelement.val();
                url = url + '&groupid=' + groupid;
            }
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
            $('#openstudio_filter_types_0').on('click', function(e) {
                var checkbox = $(this);

                // Prevent checkbox from unchecking when clicked.
                if (!checkbox.is(":checked")) {
                    e.preventDefault();
                    return false;
                }

                $('.openstudio-filter-types-checkbox').prop("checked", false);
                $('#openstudio_filter_types_0').prop("checked", true);

                // Clear quick select.
                t.updateQuickSelect(null);
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

                // Clear quick select.
                t.updateQuickSelect(null);
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

                // Clear quick select.
                t.updateQuickSelect(null);
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

                // Clear quick select.
                t.updateQuickSelect(null);
            });

            // Select status.
            $('input[name="fstatus"]').on('change', function() {
                // Clear quick select.
                t.updateQuickSelect(null);
            });

            // By author.
            $('input[name="fscope"]').on('change', function() {
                // Clear quick select.
                t.updateQuickSelect(null);
            });

            // Reset button.
            $('#reset_filter_btn').on('click', function() {
                $('#reset_filter').val(1);
                $('#filteractive').val(0);
                $(t.SELECTOR.FILTER_FORM).submit();
            });

            // Set Blocks option selected when a block checked.
            // When do not has any block checked, all option should be selected.
            $(t.SELECTOR.BLOCK_ITEM).on('click', function() {
                var checkbox = $(this);
                var value = $(this).val();
                var checkActivities = false;
                var className = '.openstudio-filter-block-' + value + '-activity';

                if (checkbox.is(":checked")) {
                    t.checkAreaInput(t.SELECTOR.AREA_ACTIVITIES_ID, true);
                    checkActivities = true;
                }

                $(className).prop("checked", checkActivities);

                var length = $('[name="fblockarray[]"]:checked').length;
                var lengthActivity = $('[name="factivityarray[]"]:checked').length;

                if (length === 0 && lengthActivity === 0) {
                    t.checkAreaInput(t.SELECTOR.AREA_ALL, true);
                }
            });

            // Set Activities option selected when a activity checked.
            $(t.SELECTOR.ACTIVITY_ITEM).on('click', function() {
                var checkbox = $(this);
                var blockId = $(this).attr('data-block-id');
                var blockElementId = '#openstudio_filter_block_' + blockId;
                var className = '.openstudio-filter-block-' + blockId + '-activity';
                var hasUnchecked = $(className + ':not(:checked)').length;

                if (checkbox.is(":checked")) {
                    t.checkAreaInput(t.SELECTOR.AREA_ACTIVITIES_ID, true);
                    $(blockElementId).prop("checked", true);
                }

                // If all activities are all un-checked, then block should be un-checked.
                if (hasUnchecked === 0) {
                    $(blockElementId).prop("checked", true);
                } else {
                    $(blockElementId).prop("checked", false);
                }

                var hasCheck = $(t.SELECTOR.ACTIVITY_ITEM + ':checked').length;
                if (hasCheck === 0) {
                    t.checkAreaInput(t.SELECTOR.AREA_ALL, true);
                }
            });

            // Sort by.
            t.handleSortBy();

            // Handle Quick Select.
            t.handleQuickSelect();
        },
        /**
         * Handle collapse click for profile bar.
         *
         * @method handleCollapseClick
         */
        handleCollapseClick: function() {
            var myprofile = document.querySelector('.openstudio-profile-mypaticipation-content');
            var fullusername = document.getElementById('openstudio_profile_fullusername');
            var profilleparticipate =
                document.querySelector('#openstudio_profile_bar .openstudio-profile-bar-participation-content');
            var profilleparticipatemobile =
                document.querySelector('#openstudio_profile_bar_mobile .openstudio-profile-bar-participation-content');
            var progressbar = document.getElementsByClassName('openstudio-profile-progress-content');
            var showprofile = null;
            var showparticipate = null;
            var body = document.querySelector('body');
            for (var i = 0; i < progressbar.length; i++) {
                if (progressbar && progressbar[i] && progressbar[i].clientHeight > 0) {
                    showprofile = progressbar[i];
                }
            }
            if (body.classList.contains('ios') || body.classList.contains('android')) {
                showparticipate = profilleparticipatemobile;
            } else {
                showparticipate = profilleparticipate;
            }
            if (fullusername && showprofile) {
                var originalmarginTop = showprofile.style.marginTop ? showprofile.style.marginTop : '0px';
                var originalpaddingTop = showprofile.style.paddingTop ? showprofile.style.paddingTop : '0px';
                fullusername.addEventListener('click', function() {
                    var w = window.innerWidth;
                    if ((w > 767 && w < 1204 && (!body.classList.contains('ios') && !body.classList.contains('android')))
                        || (w > 767 && w < 940 && (body.classList.contains('ios') || body.classList.contains('android')))) {
                        if (fullusername.classList.contains('collapsed')) {
                            setTimeout(function() {
                                // Two line
                                if (showparticipate && fullusername.clientHeight > 21) {
                                    if (myprofile && myprofile.getBoundingClientRect().height >
                                        showparticipate.getBoundingClientRect().height) {
                                        if (showprofile.getBoundingClientRect().top > myprofile.getBoundingClientRect().bottom) {
                                            showprofile.style.marginTop = originalmarginTop + 3 + 'px';
                                        } else {
                                            showprofile.style.marginTop = myprofile.getBoundingClientRect().bottom -
                                                showprofile.getBoundingClientRect().top + 'px';
                                        }
                                    } else if (showparticipate) {
                                        if (showprofile.getBoundingClientRect().top >
                                            showparticipate.getBoundingClientRect().bottom) {
                                            showprofile.style.marginTop = originalmarginTop + 3 + 'px';
                                        } else {
                                            showprofile.style.marginTop = showparticipate.getBoundingClientRect().bottom -
                                                showprofile.getBoundingClientRect().top + 'px';
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
        },

        /**
         * Handle select from dropdown.
         *
         * @param {String} id
         */
        handleSelectFrom: function(id) {
            // Get the placeholder at the begining.
            var placeholder = $(id + '-placeholder');
            placeholder.text($('#filter_block option:selected').text());

            // Build select dropdown.
            var area = $(id);
            var anchor = $(id + '-anchor');
            var contentArea = $(id + '-area');

            anchor.on('click', function() {
                if (contentArea.hasClass('visible')) {
                    contentArea.removeClass('visible');
                } else {
                    contentArea.addClass('visible');
                }
            });

            // Support for tab.
            anchor.on('keydown', function(e) {
                // Work with Space + Enter.
                if (e.keyCode === 32 || e.keyCode === 13) {
                    e.preventDefault();
                    anchor.trigger('click');
                }
            });

            // Handle click outside should close content area.
            $(window).click(function(e) {
                var target = $(e.target);
                var areaHTML = area.get(0);
                var targetHTML = target.get(0);
                // If target is on area => skip.
                // If target is area itself => skip.
                if ($.contains(areaHTML, targetHTML) || this === areaHTML) {
                    return;
                }
                if (contentArea.hasClass('visible')) {
                    contentArea.removeClass('visible');
                }
            });
        },

        /**
         * Check event for area input.
         *
         * @param {String} input
         * @param {Bool} checked
         */
        checkAreaInput: function(input, checked = true) {
            $(input).prop('checked', checked);
            $(t.SELECTOR.AREA_INPUT).not(input).prop('checked', !checked);
            t.updateFilterBlock($(input).val());
        },

        /**
         * Check if the main filter is expanded.
         *
         * @returns {boolean}
         */
        isFilterExpanded: function() {
            return $(t.SELECTOR.FILTER_CONTAINER).hasClass('show');
        },

        /**
         * Handle sort by filter.
         */
        handleSortBy: function() {
            $(t.SELECTOR.FILTER_SORT_BY).on('change', function() {
                t.redirectURL();
            });
        },

        /**
         * Handle Quick Select filter.
         */
        handleQuickSelect: function() {
            $(t.SELECTOR.FILTER_QUICK_SELECT).on('change', function() {
                var params = $(this).find(':selected').data('params');
                var value = $(this).val();
                var isMobile = $(this).data('mobile');

                // If filter is not expand, open it.
                if (!t.isFilterExpanded() && !isMobile) {
                    $(t.SELECTOR.FILTER_LINK).trigger('click');
                }

                if (typeof params !== 'object' || params.length === 0) {
                    return;
                }

                // Apply quick select.
                t.updateQuickSelect(value);

                // Apply post types.
                if (params.hasOwnProperty('ftypearray')) {
                    $('[name="ftypearray[]"]').val(params.ftypearray);
                }

                // Apply user flags.
                if (params.hasOwnProperty('fflagarray')) {
                    $('[name="fflagarray[]"]').val(params.fflagarray);
                }

                // Apply user status.
                if (params.hasOwnProperty('fstatus')) {
                    $('[name="fstatus"]').val([params.fstatus]);
                }

                // Apply scope.
                if (params.hasOwnProperty('fscope')) {
                    $('[name="fscope"]').val([params.fscope]);
                }

                if (isMobile) {
                    $(t.SELECTOR.FILTER_FORM).submit();
                }
            });
        },

        /**
         * Update quick select input.
         *
         * @param {?Integer} value
         */
        updateQuickSelect: function(value = null) {
            $('[name="quickselect"]').val(value);
        }
    };

    return t;

});
