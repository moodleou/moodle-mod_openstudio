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
 * @module mod_openstudio/folderhelper
 */

define([
    'jquery',
    'amd/build/isotope.pkgd.min.js',
    'core/str',
    'core/templates',
    'core/ajax'
], function($, Isotope, Str, Templates, Ajax) {
    var t;

    t = {

        mconfig: null,

        selectedposts: [],

        totalavailablepost: 0,

        /**
         * List out all of css selector used in this module.
         */
        CSS: {
            BROWSEPOSTLINK: '.openstudio-select-content',
            BROWSEPOSTSSELECTEDIDS: '#browse_posts_selected_ids',
            BROWSEPOSTSSELECTEDITEMS: '#browse_posts_selected_items',
            SELECTPOSTBTN: '.openstudio-folder-select-post-btn',
            BROWSEPOSTSSELECTED: '#browse_posts_selected',
            BROWSEPOSTSCOUNTSELECTED: '#openstudio-browseposts-countcurrent',
            REMOVELASTSELECTED: '.folder-browse-post-remove-last-selection',
            SAVECHANGEBUTTON: '.folder-browse-post-save-change',
            DIALOGHEADER: '.openstudio-dialogue-common-header',
            BROWSEPOSTDIALOGUECONTAINER: '.openstudio-folder-posts-dialogue',
            BOX_FOLDER_COMMENT: '#toggle_folder_view_folder_comments'
        },

        /**
         * Initialise this module.
         *
         * @method init
         * @param {JSON} options  The settings for this feature.
         */
        init: function(options) {

            /**
             * Module config options. Passed from server side.
             * {
             *     cmid: int Course module ID,
             *     folderid: int Folder ID
             * }
             */
            t.mconfig = options;

            // Create browse posts dialog.
            Y.use('moodle-core-notification-dialogue', function() {
                require(['mod_openstudio/osdialogue'], function(OSDialogue) {
                    t.dialogue = t.createBrowseDialogue(OSDialogue);

                });
            });

            // Click event on select existing post link.
            $(t.CSS.BROWSEPOSTLINK).on('click', function() {
                t.dialogue.show();
                Templates
                    .render('mod_openstudio/folder_browse_posts_header', {})
                    .done(function(html) {
                        t.selectedposts = [];
                        t.dialogue.set('bodyContent', html);
                        setTimeout(function() {
                            t.resize();
                        }, 200);
                        // Enter key in a search input field
                        $('#openstudio_search_post').keypress(function(e) {
                            var key = e.which;
                            if (key == 13) {
                                t.doBrowsePosts();
                                return false;
                            }

                            return true;
                        });

                        // Click event on search icon.
                        $('.browse-search-icon').on('click', function() {
                            t.doBrowsePosts();
                        });

                        t.doBrowsePosts();

                        // Click event on Remove last selection button.
                        $(t.CSS.REMOVELASTSELECTED).on('click', function() {
                            t.removeLastPostSelected();
                        });

                        return false;
                    });
            });

            // Responsive.
            $(window).resize(t.resize.bind(t));

            // Toggle box comment
            $(document).ready(this.toggleBoxComment);
        },

        /**
         * Do browse posts.
         *
         * @method doBrowsePosts
         */
        doBrowsePosts: function() {
            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_browse_posts',
                args: {
                    cmid: t.mconfig.cmid,
                    folderid: t.mconfig.folderid,
                    selectedposts: $(t.CSS.BROWSEPOSTSSELECTEDIDS).val(),
                    search: $('#openstudio_search_post').val()
                }
            }]);

            promises[0]
                .done(function(res) {
                    t.totalavailablepost = res.total;

                    $('#folder_total_browse_posts').html(res.foundnumberpostlabel);
                    $('#openstudio-browseposts-total').html(res.total);
                    $('#openstudio-browseposts-helpicon').html(res.helpicon);
                    $('#browse_posts_searched').html(res.html);

                    // Click event on select button.
                    $(t.CSS.SELECTPOSTBTN).on('click', function() {
                        t.selectPostToFolder(this);
                    });

                    // Click event on Save change button.
                    $(t.CSS.SAVECHANGEBUTTON).on('click', function() {
                        if (t.selectedposts.length) {
                            $('#browse_posts_selected_from').submit();
                        }
                    });

                    t.hideSaveButtons();

                    t.showHideSelectButton();
                    t.resize();

                    // Update odd/even background row.
                    t.updateBackgroundRow();
                })
                .fail(function(ex) {
                    window.console.error('Browse posts failed ' + ex.message);
                });
        },

        /**
         * Create browse posts dialogue and some events on it.
         *
         * @param {object} OSDialogue object
         * @return {object} OSDialogue instance
         * @method createBrowseDialogue
         */
        createBrowseDialogue: function(OSDialogue) {
            /**
             * Set header for dialog
             * @method setHeader
             */
            function setHeader() {
                var langstring = 'folderbrowseposts';
                Str
                    .get_string(langstring, 'mod_openstudio')
                    .done(function(s) {
                        dialogue.set('headerContent',
                            '<span class="' + t.CSS.DIALOGHEADER.replace('.', '') +
                            '">' + s + '</span>');
                    });
            }

            var dialogue = new OSDialogue({
                closeButton: true,
                visible: false,
                centered: true,
                modal: true,
                responsive: true,
                width: 640,
                responsiveWidth: 767,
                focusOnPreviousTargetAfterHide: true,
                extraClasses: [
                    t.CSS.BROWSEPOSTDIALOGUECONTAINER.replace('.', ''),
                    'openstudio-browseposts-dialogue'
                ]
            });

            setHeader();

            return dialogue;
        },

        /**
         * Select content to folder when click on select button.
         *
         * @param {event} event
         * @method selectPostToFolder
         */
        selectPostToFolder: function(event) {

            var postid = $(event).attr('value');
            var postimage = $('#browse_post_item_img_' + postid).attr('src');
            var closeicon = M.util.image_url('close_button_whitecross_rgb_32px', 'mod_openstudio');
            var itemTitle = $('#browse_post_item_name_' + postid).val();

            var buttonid = 'deselect-' + postid;
            Templates
                .render('mod_openstudio/folder_browse_selected_post',
                    {buttonid: buttonid, postid: postid, image: postimage, closeicon: closeicon, title: itemTitle})
                .done(function(html) {
                    $(t.CSS.BROWSEPOSTSSELECTEDITEMS).append(html);

                    // Click event on deselect button.
                    $('#deselect-' + postid).on('click', function() {
                        var postid = $(this).attr('value');

                        t.deSelectPostToFolder(postid);
                    });

                    // Show hide select button
                    t.showHideSelectButton();
                });

            $('#browse_post_item_' + postid).hide();
            t.selectedposts.push(postid);

            $(t.CSS.BROWSEPOSTSSELECTEDIDS).val(t.selectedposts);
            $(t.CSS.BROWSEPOSTSSELECTED).show();

            var totalselectedpost = t.selectedposts.length;

            $(t.CSS.BROWSEPOSTSCOUNTSELECTED).html(totalselectedpost);

            t.showSaveButtons();

            // Update odd/even background row.
            t.updateBackgroundRow();
        },

        /**
         * Remove post from selection.
         *
         * @param {int} postid
         * @method deSelectPostToFolder
         */
        deSelectPostToFolder: function(postid) {

            $('#browse_post_item_' + postid).show();
            $('#deselect-' + postid).remove();

            t.selectedposts.splice($.inArray(postid, t.selectedposts), 1);
            $(t.CSS.BROWSEPOSTSSELECTEDIDS).val(t.selectedposts);

            var totalselectedpost = t.selectedposts.length;

            $(t.CSS.BROWSEPOSTSCOUNTSELECTED).html(totalselectedpost);

            if (totalselectedpost == 0) {
                $(t.CSS.BROWSEPOSTSSELECTED).hide();
            }

            t.hideSaveButtons();

            // Show hide select button
            t.showHideSelectButton();

            // Update odd/even background row.
            t.updateBackgroundRow();
        },

        /**
         * Remove last post from selection.
         *
         * @method removeLastPostSelected
         */
        removeLastPostSelected: function() {
            var lastPostId = t.selectedposts[t.selectedposts.length - 1];
            t.deSelectPostToFolder(lastPostId);

        },

        /**
         * Hide Remove last selection/Save change button when no post added to selection.
         *
         * @method hideSaveButtons
         */
        hideSaveButtons: function() {
            var totalselectedpost = t.selectedposts.length;

            if (totalselectedpost == 0) {
                $('.openstudio-folder-browse-posts-header-result-buttons').hide();
                $('.openstudio-folder-browse-posts-header-result-buttons-bottom').hide();
            }
        },

        /**
         * Show Remove last selection/Save change button when post added to selection.
         *
         * @method showSaveButtons
         */
        showSaveButtons: function() {
            $('.openstudio-folder-browse-posts-header-result-buttons').show();
            $('.openstudio-folder-browse-posts-header-result-buttons-bottom').show();
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
         * Show/hide Select button added post equal available total post.
         *
         * @method showHideSelectButton
         */
        showHideSelectButton: function() {
            if (t.totalavailablepost == 0 || (t.selectedposts.length > 0 && t.totalavailablepost <= t.selectedposts.length)) {
                $('.openstudio-folder-select-post-btn').prop('disabled', true);
            } else {
                $('.openstudio-folder-select-post-btn').prop('disabled', false);
            }
        },

        /**
         * Update odd/even background color when show/hide a row.
         *
         * @method updateBackgroundRow
         */
        updateBackgroundRow: function() {
            $(".openstudio-browse-post-item").removeClass('browse-post-item-even').removeClass('browse-post-item-odd');
            $(".openstudio-browse-post-item:visible:even").addClass('browse-post-item-even');
            $(".openstudio-browse-post-item:visible:odd").addClass('browse-post-item-odd');
        },

        /**
         * Toggle function to show box comment.
         */
        toggleBoxComment: function() {
            $(t.CSS.BOX_FOLDER_COMMENT).ready(function() {
                const hash = window.location.hash;
                if (hash && hash.replace("#", "") === "id_addcomment") {
                    $(t.CSS.BOX_FOLDER_COMMENT).click();
                }
            });
        }
    };

    return t;

});
