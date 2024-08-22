<?php
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
 * Class for feature flags
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\util;

/**
 * Feature flags.  Many of these are now obsolete as the feature is non-optional.
 *
 * TODO: Remove obsolete flags
 *
 * @package mod_openstudio\local\util
 */
class feature {
    const MODULE = 1;
    const GROUP = 2;
    const STUDIO = 4;
    const PINBOARD = 8;
    const CONTENTTEXTUSESHTML = 16; // Obsolete
    const CONTENCOMMENTUSESHTML = 32; // Obsolete
    const CONTENTCOMMENTUSESAUDIO = 64;
    const CONTENTUSESFILEUPLOAD = 128; // Obsolete
    const ENABLERSS = 512; // Obsolete
    const ENABLESUBSCRIPTION = 1024; // Obsolete
    const ENABLEEXPORTIMPORT = 2048; // Obsolete
    const CONTENTUSESWEBLINK = 4096; // Obsolete
    const CONTENTUSESEMBEDCODE = 8192; // Obsolete
    const CONTENTRECIPROCALACCESS = 16384; // Obsolete
    const ENABLELOCK = 32768; // Obsolete
    const ENABLEFOLDERS = 65536;
    const CONTENTALLOWNOTEBOOKS = 131072;
    const ENABLEFOLDERSANYCONTENT = 262144;
    const PARTICIPATIONSMILEY = 524288;
    const LATESUBMISSIONS = 1048576;
    const UNIQUECOMMENTCOUNT = 2097152;
}
