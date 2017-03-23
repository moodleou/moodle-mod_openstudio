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
 *
 *
 * @package
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openstudio\output;

defined('MOODLE_INTERNAL') || die();

class email_renderer extends \plugin_renderer_base {

    /**
     * Generate the email body for a subscription email.
     *
     * @param subscription_email $email
     * @return bool|string
     */
    public function render_subscription_email(subscription_email $email) {
        $context = $email->export_for_template($this);
        $emailbody = ['plain' => $this->render_from_template('mod_openstudio/plain_subscription_email', $context)];
        if ($email->include_html()) {
            $emailbody['html'] = $this->render_from_template('mod_openstudio/html_subscription_email', $context);
        } else {
            $emailbody['html'] = '';
        }
        return $emailbody;
    }
}
