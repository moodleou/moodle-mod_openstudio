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
 * Mock filter_ouembed API for unit tests.
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\local\tests;

defined('MOODLE_INTERNAL') || die();

class mock_filter_ouembed_api extends \filter_ouembed\embed_api {

    private $config;

    /**
     * Mock constructor, just stored the passed config.
     *
     * You can optionally pass data as $config to be returned by the get_embed_data_for_url method, instead of the generated data.
     *
     * @param array $config
     */
    public function __construct(array $config = []) {
        $this->config = $config;
    }

    /**
     * Mock embed response, just returns whatever URL you give it as a link.
     *
     * @param string $url
     * @param array $options
     * @return \stdClass
     */
    public function get_embed_data_for_url($url, $options = array()) {
        return (object) [
            'type' => isset($this->config['type']) ? $this->config['type'] : self::EMBED_LINK,
            'service' => isset($this->config['service']) ? $this->config['service'] : 'URLs r Us',
            'title' => isset($this->config['title']) ? $this->config['title'] : random_string(),
            'url' => $url,
            'authorname' => isset($this->config['authorname']) ? $this->config['authorname'] : random_string(),
            'authorurl' => isset($this->config['authorurl']) ? $this->config['authorurl'] : 'http://example.com',
            'html' => '<a href=" ' . $url . '">Embed</a>',
            'thumbnailurl' => isset($this->config['thumbnailurl']) ? $this->config['thumbnailurl'] : 'http://placekitten.com/50/50'
        ];
    }

}
