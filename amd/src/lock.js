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
 * JavaScript to manage lock/unlock feature.
 *
 * @package mod_openstudio
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_openstudio/lock
 */
define([
    'jquery',
    'core/ajax',
    'core/str'
], function($, Ajax, Str) {
    var t;
    t = {

        /**
         * Module config. Passed from server side.
         * {
         *     cmid: int,
         *     cid: int,
         *     isfolder: bool,
         *     CONST: {
         *         ALL: int,
         *         NONE: int
         *     }
         */
        mconfig: null,

        /**
         * List out all of css selector used in lock module.
         */
        CSS: {
            LOCKBUTTON: '#id_lockbutton',
            ACTIONITEMS: '.openstudio-content-actions'
        },

        /**
         * Initialize module.
         *
         * @method init
         * @param {JSON} options  The settings for lock feature.
         */
        init: function(options) {

            t.mconfig = options;

            // Click event on lock/unlock content buttons.
            $(t.CSS.LOCKBUTTON).on('click', t.toggleLockContent.bind(t));
        },

        /**
         * Lock/Unlock content.
         *
         * @method toggleLockContent
         */
        toggleLockContent: function() {
            var isLocked = $(t.CSS.LOCKBUTTON).attr('data-lockstate') == 'locked';
            if (isLocked) {
                t.unlockContent();
            } else {
                t.lockContent();
            }
        },

        /**
         * Lock content
         * @method lockContent
         */
        lockContent: function() {

            M.util.js_pending('openstudioLockContent');

            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_lock',
                args: {
                    cmid: t.mconfig.cmid,
                    cid: t.mconfig.cid,
                    locktype: t.mconfig.CONST.ALL
                }
            }]);

            promises[0]
                .done(function() {
                    var unlockstring = t.mconfig.isfolder ? 'folderunlockfolder' : 'contentactionunlockname';
                    Str
                        .get_string(unlockstring, 'mod_openstudio')
                        .done(function(s) {
                            $(t.CSS.LOCKBUTTON)
                                .attr('value', s)
                                .attr('data-lockstate', 'locked');
                        });

                    // Hide othes action items like flags, edit, add new comment, ...
                    $(t.CSS.ACTIONITEMS).addClass('locked');

                    // Show lock banner.
                    $('#openstudio_item_lock').removeClass('openstudio-item-unlock').addClass('openstudio-item-lock');
                })
                .always(function() {
                    M.util.js_complete('openstudioLockContent');
                })
                .fail(function(ex) {
                    window.console.error('Log request failed ' + ex.message);
                });
        },

        /**
         * Unlock content.
         * @method unlockContent
         */
        unlockContent: function() {

            M.util.js_pending('openstudioUnlockContent');

            var promises = Ajax.call([{
                methodname: 'mod_openstudio_external_unlock',
                args: {
                    cmid: t.mconfig.cmid,
                    cid: t.mconfig.cid,
                    locktype: t.mconfig.CONST.NONE
                }
            }]);

            promises[0]
                .done(function() {
                    var lockstring = t.mconfig.isfolder ? 'folderlockfolder' : 'contentactionlockname';
                    Str
                        .get_string(lockstring, 'mod_openstudio')
                        .done(function(s) {
                            $(t.CSS.LOCKBUTTON)
                                .attr('value', s)
                                .attr('data-lockstate', 'unlocked');
                        });

                    // Show othes action items like flags, edit, delete, ...
                    $(t.CSS.ACTIONITEMS).removeClass('locked');

                    // Hide lock banner.
                    $('#openstudio_item_lock').addClass('openstudio-item-unlock').removeClass('openstudio-item-lock');
                })
                .always(function() {
                    M.util.js_complete('openstudioUnlockContent');
                })
                .fail(function(ex) {
                    window.console.error('Log request failed ' + ex.message);
                });
        }
    };

    return t;
});
