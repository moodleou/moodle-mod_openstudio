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
 * JavaScript to manage folders.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/managefolders
 */

define(['jquery'], function($) {
    var t;

    t = {

        /**
         * Handle move up, move down and delete icon when content loaded.
         *
         * Move up icon of first content will not visible.
         * Move down icon of last content will not visible.
         * When add new content (not saved) then move up, move down and delete icon will not visible.
         *
         */
        init: function() {

            $('.moveup:first').hide();

            $('.movedown').each(function(index, value) {
                var id = this.id;
                var index = id.split(/[_ ]+/).pop();

                var contentid = $('input[name="contentid[' + index + ']"]').val();
                if (contentid === '' || contentid === "0") {
                    $('#id_contentmovedown_' + index).hide();
                    $('#id_contentmoveup_' + index).hide();
                    $('#id_contentdelete_' + index).hide();
                }

            });

            $('.movedown:visible').last().hide();

        }

    };

    return t;

});
