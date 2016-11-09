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

class feature {
    const MODULE = 1;
    const GROUP = 2;
    const STUDIO = 4;
    const PINBOARD = 8;
    const CONTENTCOMMENTUSESAUDIO = 64;
    const CONTENTUSESFILEUPLOAD = 128;
    const ENABLERSS = 512;
    const ENABLESUBSCRIPTION = 1024;
    const ENABLEEXPORTIMPORT = 2048;
    const CONTENTUSESWEBLINK = 4096;
    const CONTENTUSESEMBEDCODE = 8192;
    const CONTENTRECIPROCALACCESS = 16384;
    const ENABLELOCK = 32768;
    const ENABLEFOLDERS = 65536;
    const CONTENTALLOWNOTEBOOKS = 131072;
    const ENABLEFOLDERSANYCONTENT = 262144;
    const PARTICIPATIONSMILEY = 524288;
}
