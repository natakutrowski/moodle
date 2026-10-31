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

namespace local_xp\local\mcp;

use coding_exception;
use context_system;
use core_user;
use local_xp\local\oauth\service;

/**
 * OAuth authenticator.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth_authenticator implements authenticator {
    /** @var service The OAuth service. */
    protected $service;

    /**
     * Constructor.
     *
     * @param service $service The OAuth service.
     */
    public function __construct(service $service) {
        $this->service = $service;
    }

    /**
     * Authenticate the current request.
     *
     * @return void
     */
    public function authenticate(): void {
        global $CFG;

        $cookiessupported = $CFG->branch >= 503
            ? \core\session\manager::supports_cookies()
            : (!defined('NO_MOODLE_COOKIES') || !NO_MOODLE_COOKIES);

        if ($cookiessupported) {
            throw new coding_exception('Cookies must be disabled for OAuth!');
        }
        \core\session\manager::write_close();

        $token = $this->get_bearer_token();
        if ($token === null) {
            $this->unauthorized();
        }

        $user = $this->service->authenticate_bearer($token);
        if (!$user) {
            $this->unauthorized();
        }

        try {
            core_user::require_active_user($user, true, true);
        } catch (\Throwable $e) {
            $this->unauthorized();
        }

        enrol_check_plugins($user, false);
        \core\session\manager::set_user($user);

        // Validate that the user still has the permission to use the MCP.
        if (!has_capability('local/xp:usemcp', context_system::instance(), $user)) {
            throw new jsonrpc_exception(-32011, 'Forbidden', null, 403);
        }

        // Validate the site maintenance status.
        $hasmaintenanceaccess = has_capability('moodle/site:maintenanceaccess', context_system::instance(), $user);
        if (!empty($CFG->maintenance_enabled) && !$hasmaintenanceaccess) {
            throw new jsonrpc_exception(-32010, get_string('sitemaintenance', 'core_admin'), null, 503);
        }
    }

    /**
     * Get the bearer token from the Authorization header.
     *
     * @return string|null
     */
    protected function get_bearer_token(): ?string {
        $header = $this->get_authorization_header();
        if ($header === null) {
            return null;
        }

        $matches = [];
        if (!preg_match('/^\s*Bearer\s+([A-Za-z0-9_-]{43,128})\s*$/i', $header, $matches)) {
            return null;
        }
        return $matches[1];
    }

    /**
     * Get the Authorization header.
     *
     * @return string|null
     */
    protected function get_authorization_header(): ?string {
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
            if (!empty($_SERVER[$key]) && is_string($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    return (string) $value;
                }
            }
        }

        return null;
    }

    /**
     * Reject the request.
     *
     * @return never
     */
    protected function unauthorized() {
        header('WWW-Authenticate: Bearer resource_metadata="' . $this->quote_header_value(
            $this->service->get_resource_url()->out(false)
        ) . '"');
        throw new jsonrpc_exception(-32001, 'Unauthorized', null, 401);
    }

    /**
     * Quote a header value.
     *
     * @param string $value The header value.
     * @return string
     */
    protected function quote_header_value(string $value): string {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
