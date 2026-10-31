<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\local\exporter;

use block_xp\di;
use context_system;
use core\external\persistent_exporter;
use local_xp\local\model\oauth_client;
use moodle_url;
use renderer_base;

/**
 * OAuth client exporter.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth_client_exporter extends persistent_exporter {
    /**
     * Return the list of additional properties used only for display.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'hasredirecturis' => [
                'type' => PARAM_BOOL,
            ],
            'istypecimd' => [
                'type' => PARAM_BOOL,
            ],
            'istypemanual' => [
                'type' => PARAM_BOOL,
            ],
            'redirecturisarray' => [
                'type' => PARAM_URL,
                'multiple' => true,
            ],
            'logofileurl' => [
                'type' => PARAM_URL,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'typename' => [
                'type' => PARAM_RAW,
            ],
            'userfullname' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'lastmetadatacheckformatted' => [
                'type' => PARAM_RAW,
            ],
            'timecreatedformatted' => [
                'type' => PARAM_RAW,
            ],
            'timemodifiedformatted' => [
                'type' => PARAM_RAW,
            ],
            'oauthauthorizeurl' => [
                'type' => PARAM_URL,
            ],
            'oauthtokenurl' => [
                'type' => PARAM_URL,
            ],
            'oauthpkcerequired' => [
                'type' => PARAM_BOOL,
            ],
        ];
    }

    /**
     * Get the persistent class.
     *
     * @return string
     */
    protected static function define_class() {
        return oauth_client::class;
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array
     */
    protected function get_other_values(renderer_base $output) {
        $client = $this->persistent;
        $types = [
            oauth_client::TYPE_MANUAL => get_string('manual', 'block_xp'),
            oauth_client::TYPE_CIMD => get_string('cimd', 'block_xp'),
        ];

        $service = di::get('oauth_service');
        $user = di::get('db')->get_record('user', ['id' => $client->get('usermodified'), 'deleted' => 0]);
        $redirecturls = $client->get_redirect_urls();

        return [
            'hasredirecturis' => !empty($redirecturls),
            'istypecimd' => $client->get('type') === oauth_client::TYPE_CIMD,
            'istypemanual' => $client->get('type') === oauth_client::TYPE_MANUAL,
            'redirecturisarray' => $redirecturls,
            'logofileurl' => $this->get_logo_file_url(),
            'typename' => $types[$client->get('type')] ?? get_string('unknowntypea', 'block_xp', $client->get('type')),
            'userfullname' => $user ? fullname($user) : null,
            'lastmetadatacheckformatted' => $this->format_date($client->get('lastmetadatacheck')),
            'timecreatedformatted' => $this->format_date($client->get('timecreated')),
            'timemodifiedformatted' => $this->format_date($client->get('timemodified')),

            'oauthauthorizeurl' => $service->get_authorize_url()->out(false),
            'oauthtokenurl' => $service->get_token_url()->out(false),
            'oauthpkcerequired' => $client->is_pkce_required(),
        ];
    }

    /**
     * Format a timestamp.
     *
     * @param int $timestamp The timestamp.
     * @return string
     */
    protected function format_date(int $timestamp): string {
        if ($timestamp <= 0) {
            return '-';
        }
        return userdate($timestamp, get_string('strftimedatetimeshortaccurate', 'core_langconfig'));
    }

    /**
     * Get the logo file URL.
     *
     * @return string|null
     */
    protected function get_logo_file_url(): ?string {
        $filename = $this->persistent->get('logofilename');
        if (!$filename || $filename === '.') {
            return null;
        }

        return moodle_url::make_pluginfile_url(
            context_system::instance()->id,
            'local_xp',
            'oauthclient',
            $this->persistent->get('id'),
            '/',
            $filename,
            false
        )->out(false);
    }
}
