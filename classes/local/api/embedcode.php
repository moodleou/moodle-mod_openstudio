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
 * Embedcode API
 *
 * @package    mod_openstudio
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Embedcode API functions
 *
 * @package mod_openstudio\local\api
 */
class embedcode {

    /**
     * Call to check if embed plugin exists
     *
     * @return bool True if OU embed extension is installed.
     */
    public static function is_ouembed_installed() {
        global $CFG;
        return file_exists("{$CFG->dirroot}/filter/ouembed/version.php");
    }

    /**
     * Generate an instance of the OUembed filter API to pass to the parse() method.
     *
     * @param array $params Options to pass into the oembed request.
     * @return \filter_ouembed\embed_api
     */
    public static function get_ouembed_api($params = ['width' => 520]) {
        $config = [
            'params' => $params
        ];
        return new \filter_ouembed\embed_api($config);
    }

    /**
     * Helper function that accepts the web link or embed code and
     * processes it using the \filter_ouembed\embed_api class.
     * If the web link/embed code mataches supported third-paty providers,
     * then a formatted embed code and data will be returned.
     *
     * @param \filter_ouembed\embed_api $embedapi Instance of the filter_ouembed API.
     * @param string $content Web link or embed code to parse and process.
     * @param string $type The type of content being passed - 'link' for plain URL or 'embed' for HTML embed code.
     * @return object|false Return the parsed result, or false if there's nothing to return.
     */
    public static function parse(\filter_ouembed\embed_api $embedapi, $content, $type = 'link') {
        // Only continue if there is content to process.
        $content = trim($content);
        if ($content === '') {
            return false;
        }

        switch ($type) {
            case 'embed':
                // If it's embed code, pass it through HTML Tidy to clean the markup.
                $tidy = new \tidy;
                $config = [
                    'output-xhtml' => true,
                    'show-body-only' => true,
                    'show-errors' => 0,
                    'show-warnings' => 0
                ];
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

        // The parser returns false if there is a problem with the data.
        $result = $embedapi->get_embed_data_for_url($content);
        if ($result === false) {
            return false;
        }

        // Determine the Open Studio content type based on the service provider.
        switch ($result->type) {
            case $embedapi::EMBED_PHOTO:
                $contenttype = content::TYPE_URL_IMAGE;
                break;
            case $embedapi::EMBED_VIDEO:
                $contenttype = content::TYPE_URL_VIDEO;
                break;
            case $embedapi::EMBED_LINK:
                $contenttype = content::TYPE_URL;
                break;
            case $embedapi::EMBED_RICH:
                $contenttype = content::TYPE_URL_DOCUMENT;
                break;
            case $embedapi::EMBED_OTHER:
            default:
                $contenttype = content::TYPE_URL;
                break;
        }

        // Package the various data into an object result structure.
        $returndata = [
            'service' => $result->service,
            'type' => $contenttype,
            'title' => $result->title,
            'url' => $result->url,
            'authorname' => $result->authorname,
            'authorurl' => $result->authorurl,
            'html' => $result->html,
            'thumbnailurl' => $result->thumbnailurl,
        ];

        return (object) $returndata;
    }
}
