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

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

/**
 * Helper function that accepts the web link or embed code and
 * processes it using the \filter_ouembed\embed_api class.
 * If the web link/embed code mataches supported third-paty providers,
 * then a formatted embed code and data will be returned.
 *
 * @param string $content Web link or embed code to parse and process.
 * @param array $params Options to pass into the oembed request.
 * @return object Return the parsed result.
 */
function studio_api_embedcode_parse($content, $type = 'link', $params = array('width' => 520)) {
    global $CFG;

    // Only continue if there is content to process.
    $content = trim($content);
    if ($content === '') {
        return false;
    }

    switch ($type) {
        case 'embed':
            // If it's embed code, pass it through HTML Tidy to clean the markup.
            $tidy = new tidy;
            $config = array(
                    'output-xhtml' => true,
                    'show-body-only' => true,
                    'show-errors' => 0,
                    'show-warnings' => 0);
            $tidy->parseString($content, $config, 'utf8');
            $tidy->cleanRepair();
            if (!$tidy->cleanRepair()) {
                return false;
            }
            $content = $tidy;
            break;

        case 'link':
            // Do nothing.
            break;

        default:
            return false;
    }

    // Parse the content using the \filter_ouembed\embed_api class.
    $config = array(
        'params' => $params,
    );
    $embedapi = new \filter_ouembed\embed_api($config);

    // The parser returns false if there is a problem with the data.
    $result = $embedapi->get_embed_data_for_url($content);
    if ($result === false) {
        return false;
    }

    // Determine the Open Studio content type based on the service provider.
    switch ($result->type) {
        case $embedapi::EMBED_PHOTO:
            $contenttype = STUDIO_CONTENTTYPE_URL_IMAGE;
            break;
        case $embedapi::EMBED_VIDEO:
            $contenttype = STUDIO_CONTENTTYPE_URL_VIDEO;
            break;
        case $embedapi::EMBED_LINK:
            $contenttype = STUDIO_CONTENTTYPE_URL;
            break;
        case $embedapi::EMBED_RICH:
            $contenttype = STUDIO_CONTENTTYPE_URL_DOCUMENT;
            break;
        case $embedapi::EMBED_OTHER:
        default:
            $contenttype = STUDIO_CONTENTTYPE_URL;
            break;
    }

    // Package the various data into an object result structure.
    $returndata = array(
        'service' => $result->service,
        'type' => $contenttype,
        'title' => $result->title,
        'url' => $result->url,
        'authorname' => $result->authorname,
        'authorurl' => $result->authorurl,
        'html' => $result->html,
        'thumbnailurl' => $result->thumbnailurl,
    );

    return (object) $returndata;
}
