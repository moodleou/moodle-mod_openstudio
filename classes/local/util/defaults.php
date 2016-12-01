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
 * Class for default values
 *
 * @package    mod_openstudio
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\util;

/**
 * Class for holding default value constants.
 *
 * Plural "defaults" used because "default" is a reserved word.
 */
class defaults {
    const MAXBYTES = 10485760; // Default to 10MB.
    const SITENAME_LENGTH = 64;
    const NAVITEM_LENGTH = 15;
    const BLOCKNAME = 'block';
    const BLOCKSNAME = 'blocks';
    const ACTIVITYNAME = 'activity';
    const ACTIVITIESNAME = 'activities';
    const CONTENTNAME = 'content';
    const CONTENTSNAME = 'contents';
    const BLOCKLEVELCONTAINER = 1;
    const ACTIVITYLEVELCONTAINER = 2;
    const CONTENTLEVELCONTAINER = 3;
    const CONTENTTHUMBNAIL_WIDTH = 542;
    const STREAMPAGESIZE = 12;
    const ACIVITYPAGESIZE = 20;
    const PEOPLEPAGESIZE = 10;
    const CONTENTVERSIONSTOSHOW = 4;
    const EXPORTPAGESIZE = 10;
    const MAXPINBOARDCONTENTS = 10;
    const MAXCONTENTVERSIONS = 5;
    const MAXPINBOARDCONTENTSLLENGTH = 3; // 3 = 999 maximum.
    const MAXCONTENTVERSIONSLENGTH = 2; // 2 = 99 maximum.
    const CONTENTCOMMENTLENGTH = 5000;
    const CONTENTVIEWIMAGESIZELIMIT = 524288; // Maximum size image: 524288b or 1048576b.
    const SUBSCRIPTIONEMAILTOSENDPERSTUDIOPERCRONRUN = 100;
    const SUBSCRIPTIONTOPROCESSPERCRONRUN = 1000;
    const MAXPINBOARDFOLDERSCONTENTS = 10;
    const MAXPINBOARDFOLDERSCONTENTSLENGTH = 3; // 3 = 999 maximum.
    const FOLDERTEMPLATEADDITIONALCONTENTS = 10;
}