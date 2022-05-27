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
 * JavaScript to allow editing content.
 *
 * @package
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_oucontent/contentedit
 */

define(['jquery', 'mod_openstudio/scrollto'], function ($, Scrollto) {
    var t;

    t = {

        init: function () {
            $.fx.off = true;

            $('#id_openstudio_upload_content_buttons_addfilebutton').on('click', t.toogleAddFile);
            $('#id_openstudio_upload_content_buttons_addlinkbutton').on('click', t.toogleAddLink);
        },

        /**
         * Height from DOMElement to top browser.
         */
        HEIGHT_TO_TOP: 38,

        /**
         * Toogle Add File button.
         *
         * @param {Event} event
         * @method toogleAddFile
         */
        toogleAddFile: function (event) {
            event.preventDefault();

            $('#openstudio_upload_content_add_file').toggle(function () {
                t.showAddFile(false);
            });
        },

        /**
         * Show Add File button.
         *
         * @param {bool} force
         * @method showAddFile
         */
        showAddFile: function (force) {
            $('#openstudio_upload_content_add_link').hide();

            if (force) {
                $('#openstudio_upload_content_add_file').show();
            }

            if ($('#openstudio_upload_content_add_file').is(':visible')) {
                $('#contentformoptionalmetadata').show();
                $('input[name="contentuploadtype"]').val('addfile');

                $('#id_openstudio_upload_content_buttons_addlinkbutton').removeClass('openstudio-button-active');
                $('#id_openstudio_upload_content_buttons_addfilebutton').addClass('openstudio-button-active');

                Scrollto.scrollToEl($('#id_openstudio_upload_content_buttons_addfilebutton'), t.HEIGHT_TO_TOP);
            } else {
                $('#contentformoptionalmetadata').hide();
                $('input[name="contentuploadtype"]').val('');

                $('#id_openstudio_upload_content_buttons_addfilebutton').removeClass('openstudio-button-active');
            }
        },

        /**
         * Toogle Add Link button.
         *
         * @param {Event} event
         * @method toogleAddLink
         */
        toogleAddLink: function (event) {
            event.preventDefault();

            $('#openstudio_upload_content_add_link').toggle(function () {
                $('#openstudio_upload_content_add_file').hide();

                if ($(this).is(':visible')) {
                    $('#contentformoptionalmetadata').show();
                    $('input[name="contentuploadtype"]').val('addlink');

                    $('#id_openstudio_upload_content_buttons_addfilebutton').removeClass('openstudio-button-active');
                    $('#id_openstudio_upload_content_buttons_addlinkbutton').addClass('openstudio-button-active');

                    Scrollto.scrollToEl($('#id_openstudio_upload_content_buttons_addlinkbutton'), t.HEIGHT_TO_TOP);
                } else {
                    $('#contentformoptionalmetadata').hide();
                    $('input[name="contentuploadtype"]').val('');
                    $('#id_openstudio_upload_content_buttons_addlinkbutton').removeClass('openstudio-button-active');
                }
            });
        }
    };

    return t;

});
