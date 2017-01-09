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
 * @package mod_oucontent
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_oucontent/contentedit
 */

define(['jquery'], function($) {
    var t;

        t = {

        init: function() {
            $.fx.off = true;

            $('#id_addfilebutton').on('click', t.toogleAddFile);
            $('#id_addlinkbutton').on('click', t.toogleAddLink);
         },

        /**
         * Toogle Add File button.
         *
         * @param {Event} event
         * @method toogleAddFile
         */
        toogleAddFile: function(event) {
            event.preventDefault();

            $('#openstudio_upload_content_add_link').hide();

            $('#openstudio_upload_content_add_file').toggle(function() {
                if ($(this).is(':visible')) {
                    $('#contentformoptionalmetadata').show();
                    $('input[name="contentuploadtype"]').val('addfile');

                    $('#id_addlinkbutton').removeClass('openstudio-button-active');
                    $('#id_addfilebutton').addClass('openstudio-button-active');
                } else {
                    $('#contentformoptionalmetadata').hide();
                    $('input[name="contentuploadtype"]').val('');

                    $('#id_addfilebutton').removeClass('openstudio-button-active');
                }
            });
        },

        /**
         * Toogle Add Link button.
         *
         * @param {Event} event
         * @method toogleAddLink
         */
        toogleAddLink: function(event) {
            event.preventDefault();

            $('#openstudio_upload_content_add_file').hide();

            $('#openstudio_upload_content_add_link').toggle(function() {
                if ($(this).is(':visible')) {
                    $('#contentformoptionalmetadata').show();
                    $('input[name="contentuploadtype"]').val('addlink');

                    $('#id_addfilebutton').removeClass('openstudio-button-active');
                    $('#id_addlinkbutton').addClass('openstudio-button-active');
                } else {
                    $('#contentformoptionalmetadata').hide();
                    $('input[name="contentuploadtype"]').val('');
                    $('#id_addlinkbutton').removeClass('openstudio-button-active');
                }
            });
        }
    };

    return t;

});
