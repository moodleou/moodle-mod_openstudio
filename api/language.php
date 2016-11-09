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
 * @package mod_studio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function studio_api_language_get_contenttype_name($contenttype) {
    $contenttypename = get_string('unknown', 'studio');

    switch ($contenttype) {
        case STUDIO_CONTENTTYPE_NONE:
            $contenttypename = get_string('reportcontenttypename1', 'studio');
            break;

        case STUDIO_CONTENTTYPE_TEXT:
            $contenttypename = get_string('reportcontenttypename2', 'studio');
            break;

        case STUDIO_CONTENTTYPE_IMAGE:
            $contenttypename = get_string('reportcontenttypename3', 'studio');
            break;

        case STUDIO_CONTENTTYPE_IMAGE_EMBED:
            $contenttypename = get_string('reportcontenttypename4', 'studio');
            break;

        case STUDIO_CONTENTTYPE_VIDEO:
            $contenttypename = get_string('reportcontenttypename5', 'studio');
            break;

        case STUDIO_CONTENTTYPE_VIDEO_EMBED:
            $contenttypename = get_string('reportcontenttypename6', 'studio');
            break;

        case STUDIO_CONTENTTYPE_AUDIO:
            $contenttypename = get_string('reportcontenttypename7', 'studio');
            break;

        case STUDIO_CONTENTTYPE_AUDIO_EMBED:
            $contenttypename = get_string('reportcontenttypename8', 'studio');
            break;

        case STUDIO_CONTENTTYPE_DOCUMENT:
            $contenttypename = get_string('reportcontenttypename9', 'studio');
            break;

        case STUDIO_CONTENTTYPE_DOCUMENT_EMBED:
            $contenttypename = get_string('reportcontenttypename10', 'studio');
            break;

        case STUDIO_CONTENTTYPE_PRESENTATION:
            $contenttypename = get_string('reportcontenttypename11', 'studio');
            break;

        case STUDIO_CONTENTTYPE_PRESENTATION_EMBED:
            $contenttypename = get_string('reportcontenttypename12', 'studio');
            break;

        case STUDIO_CONTENTTYPE_SPREADSHEET:
            $contenttypename = get_string('reportcontenttypename13', 'studio');
            break;

        case STUDIO_CONTENTTYPE_SPREADSHEET_EMBED:
            $contenttypename = get_string('reportcontenttypename14', 'studio');
            break;

        case STUDIO_CONTENTTYPE_URL:
            $contenttypename = get_string('reportcontenttypename15', 'studio');
            break;

        case STUDIO_CONTENTTYPE_URL_IMAGE:
            $contenttypename = get_string('reportcontenttypename16', 'studio');
            break;

        case STUDIO_CONTENTTYPE_URL_AUDIO:
            $contenttypename = get_string('reportcontenttypename17', 'studio');
            break;

        case STUDIO_CONTENTTYPE_URL_DOCUMENT:
            $contenttypename = get_string('reportcontenttypename18', 'studio');
            break;

        case STUDIO_CONTENTTYPE_URL_DOCUMENT_PDF:
            $contenttypename = get_string('reportcontenttypename19', 'studio');
            break;

        case STUDIO_CONTENTTYPE_URL_DOCUMENT_DOC:
            $contenttypename = get_string('reportcontenttypename20', 'studio');
            break;

        case STUDIO_CONTENTTYPE_URL_PRESENTATION:
            $contenttypename = get_string('reportcontenttypename21', 'studio');
            break;

        case STUDIO_CONTENTTYPE_URL_PRESENTATION_PPT:
            $contenttypename = get_string('reportcontenttypename22', 'studio');
            break;

        case STUDIO_CONTENTTYPE_URL_SPREADSHEET:
            $contenttypename = get_string('reportcontenttypename23', 'studio');
            break;

        case STUDIO_CONTENTTYPE_URL_SPREADSHEET_XLS:
            $contenttypename = get_string('reportcontenttypename24', 'studio');
            break;

        case STUDIO_CONTENTTYPE_COLLECTION:
            $contenttypename = get_string('reportcontenttypename25', 'studio');
            break;
    }

    return $contenttypename;
}

function studio_api_language_get_flagtype_name($flagtype) {
    $flagtypename = get_string('unknown', 'studio');

    switch ($flagtype) {
        case STUDIO_PARTICPATION_FLAG_ALERT:
            $flagtypename = get_string('reportflagtypename1', 'studio');
            break;

        case STUDIO_PARTICPATION_FLAG_FAVOURITE:
            $flagtypename = get_string('reportflagtypename2', 'studio');
            break;

        case STUDIO_PARTICPATION_FLAG_NEEDHELP:
            $flagtypename = get_string('reportflagtypename3', 'studio');
            break;

        case STUDIO_PARTICPATION_FLAG_MADEMELAUGH:
            $flagtypename = get_string('reportflagtypename4', 'studio');
            break;

        case STUDIO_PARTICPATION_FLAG_INSPIREDME:
            $flagtypename = get_string('reportflagtypename5', 'studio');
            break;

        case STUDIO_PARTICPATION_FLAG_READ_SLOT:
            $flagtypename = get_string('reportflagtypename6', 'studio');
            break;

        case STUDIO_PARTICPATION_FLAG_FOLLOW_SLOT:
            $flagtypename = get_string('reportflagtypename7', 'studio');
            break;

        case STUDIO_PARTICPATION_FLAG_FOLLOW_USER:
            $flagtypename = get_string('reportflagtypename8', 'studio');
            break;

        case STUDIO_PARTICPATION_FLAG_COMMENT:
            $flagtypename = get_string('reportflagtypename9', 'studio');
            break;
    }

    return $flagtypename;
}

function studio_api_language_get_visibilitytype_name($visibilitytype) {
    $visibilitytypename = get_string('unknown', 'studio');

    switch ($visibilitytype) {
        case STUDIO_VISIBILITY_PRIVATE:
            $visibilitytypename = get_string('reportvisibilitytypename1', 'studio');
            break;

        case STUDIO_VISIBILITY_GROUP:
            $visibilitytypename = get_string('reportvisibilitytypename2', 'studio');
            break;

        case STUDIO_VISIBILITY_MODULE:
            $visibilitytypename = get_string('reportvisibilitytypename3', 'studio');
            break;
    }

    if ($visibilitytype < 0) {
        $visibilitytypename = get_string('reportvisibilitytypename4', 'studio');
    }

    return $visibilitytypename;
}
