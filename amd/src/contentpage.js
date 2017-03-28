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
 * JavaScript to manage content detail page.
 *
 * @package mod_oucontent
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_oucontent/contentpage
 */
define(['jquery', 'core/ajax', 'core/str', 'core/modal', 'core/templates'],
    function($, Ajax, Str, Modal, Templates) {
    var t;

    t = {

        init: function() {
            $.fx.off = true;

            // Maximize feature.
            t.createMaximizeModal();
            $('#openstudio_content_view_maximize').on('click', t.toogleContentModal);
            $('body').delegate('#openstudio_content_view_minimize', 'click', t.toogleContentModal);
            $('body').delegate('.openstudio-modal-content-close', 'click', t.toogleContentModal);

            var contentViewMapCanvas = $('#openstudio_content_view_map_canvas');

            if (contentViewMapCanvas.length) {
                t.showGoogleMap(contentViewMapCanvas);
            }

            $(".openstudio-content-view-flag-icon").bind('click', function() {
                t.doFlagContent($(this));
            });

            $(".openstudio-request-feedback-button").bind('click', function() {
                t.doFlagContent($(this));
            });
        },

        /**
         * Create modal to display content inside it when click maximize button.
         *
         * @method createMaximizeModal
         */
        createMaximizeModal: function() {
            Templates
                .render('mod_openstudio/content_modal', {
                    title: $('.openstudio-content-view-title').text(),
                    body: $('.openstudio-content-view-file').html(),
                    date: $('.openstudio-content-view-date').html(),
                    isiframecontent: $('.openstudio-content-view-file').find('iframe').length > 0
                })
                .done(function(html) {
                    t.modal = new Modal(html);
                    t.modal.setLarge();
                });

        },

        /**
         * Toggle content modal
         *
         * @method toogleContentModal
         */
        toogleContentModal: function() {
            if (!t.modal) {
              return;
            }

            if (t.modal.isVisible()) {
                t.modal.hide();
            } else {
                t.modal.show();
            }

            // Lock page scroll.
            $('body').toggleClass('openstudio-lockscroll');
        },

        /**
         * Show Google map if user requested it and it is available.
         *
         * @param {Event} contentViewMapCanvas
         * @method showGoogleMap
         */
        showGoogleMap: function(contentViewMapCanvas) {
            var gpslat = contentViewMapCanvas.attr('data-gpslat');
            var gpslng = contentViewMapCanvas.attr('data-gpslng');

            if (gpslat && gpslng) {
                var myLatLng = {lat: parseFloat(gpslat), lng: parseFloat(gpslng)};
                map = new google.maps.Map(document.getElementById('openstudio_content_view_map_canvas'), {
                    center: myLatLng,
                    zoom: 14
                });

                new google.maps.Marker({
                    position: myLatLng,
                    map: map
                });
            }
        },

        /**
         * Flag icons on content page.
         *
         * @param {Event} event
         * @method doFlagContent
         */
        doFlagContent: function(event) {
            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_flag_content',
                args: {
                    cmid: event.attr('data-cmid'),
                    cid: event.attr('data-cid'),
                    fid: event.attr('data-fid'),
                    mode: event.attr('data-mode')
                }
            }]);

            promises[0]
                .done(function(res) {
                    if (res.success) {
                        // Update new flag content.
                        var flagcontainer = $('#content_view_icon_' + res.fid);
                        flagcontainer.attr('data-mode', res.mode);

                        // Check if flag a request feedback.
                        if (res.iscontentflagrequestfeedback) {
                            flagcontainer.html(res.flagtext);
                            $('#openstudio_item_request_feedback')
                                .removeClass(res.flagremoveclass)
                                .addClass(res.flagaddclass);
                        } else {
                            flagcontainer.html(res.flagtext);
                            flagcontainer.removeClass(res.flagremoveclass).addClass(res.flagaddclass);
                        }
                    }
                })
                .fail(function(ex) {
                    window.console.error('Error saving social flag ' + ex.message);
                });
        }
    };

    return t;

});
