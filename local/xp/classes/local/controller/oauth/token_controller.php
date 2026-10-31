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

namespace local_xp\local\controller\oauth;

use block_xp\local\routing\request;
use local_xp\local\model\oauth_client;
use local_xp\local\oauth\oauth_exception;

/**
 * OAuth token controller.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token_controller extends base_controller {
    /**
     * Process the token request.
     *
     * @param request $request The request.
     * @return void
     */
    public function handle(request $request) {
        parent::handle($request);

        if ($request->get_method() !== 'POST') {
            throw new oauth_exception('invalid_request', 405, 'The token endpoint only accepts POST requests.');
        }

        $contenttype = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (strpos($contenttype, 'application/x-www-form-urlencoded') !== 0) {
            throw new oauth_exception('invalid_request', 400, 'The token endpoint only accepts form-encoded requests.');
        }

        foreach (['code', 'refresh_token', 'access_token'] as $forbidden) {
            if (array_key_exists($forbidden, $_GET)) {
                throw new oauth_exception('invalid_request', 400, 'OAuth credentials are not accepted in the query string.');
            }
        }

        [$clientid, $clientsecret] = $this->get_client_id_and_secret();
        $granttype = required_param('grant_type', PARAM_ALPHANUMEXT);
        $resource = optional_param('resource', $this->service->get_mcp_url()->out(false), PARAM_RAW);

        if (\core_text::strlen($resource) > 2048) {
            throw new oauth_exception('invalid_request', 400, 'The resource is invalid.');
        } else if (\core_text::strlen($clientid) > 255) {
            throw new oauth_exception('invalid_request', 400, 'The client_id is invalid.');
        }

        $service = $this->service;
        $client = $service->get_client($clientid);
        if (!$client || !$client->is_active()) {
            throw new oauth_exception('invalid_request', 400, 'The client_id is invalid.');
        }

        if ($client->get_token_endpoint_auth_method() === oauth_client::TOKEN_ENDPOINT_AUTH_CLIENT_SECRET_BASIC) {
            if (
                !$clientsecret || \core_text::strlen($clientsecret) < 16
                    || !hash_equals((string) $client->get('clientsecret'), $clientsecret)
            ) {
                throw new oauth_exception('invalid_client', 401, 'The client authentication failed.');
            }
        } else if ($client->get_token_endpoint_auth_method() !== oauth_client::TOKEN_ENDPOINT_AUTH_NONE) {
            throw new oauth_exception('invalid_request', 400, 'The client does not support this authentication method.');
        }

        $tokens = [];
        if ($granttype === 'authorization_code') {
            $code = required_param('code', PARAM_RAW);
            $redirecturi = required_param('redirect_uri', PARAM_RAW);
            $verifier = null;

            if ($client->is_pkce_required()) {
                $verifier = required_param('code_verifier', PARAM_RAW);
                if (!preg_match('/^[A-Za-z0-9_-]{43,128}$/', $code) || \core_text::strlen($redirecturi) > 2048) {
                    throw new oauth_exception('invalid_request', 400, 'Invalid PKCE verifier or redirect URL.');
                }
            }

            $tokens = $this->service->exchange_code($client, $code, $redirecturi, $verifier, $resource);
        } else if ($granttype === 'refresh_token') {
            $refreshtoken = required_param('refresh_token', PARAM_RAW);
            if (!preg_match('/^[A-Za-z0-9_-]{43,128}$/', $refreshtoken)) {
                throw new oauth_exception('invalid_request', 400, 'Invalid refresh token.');
            }
            $tokens = $this->service->refresh($refreshtoken, $clientid, $resource);
        } else {
            throw new oauth_exception('invalid_request', 400, 'Unsupported grant_type.');
        }

        if (empty($tokens)) {
            throw new oauth_exception('server_error', 503, 'Invalid token generation.');
        }

        echo json_encode($tokens);
    }

    /**
     * Get the request's client ID and secret.
     *
     * @return array
     */
    protected function get_client_id_and_secret(): array {
        if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
            return [
                $_SERVER['PHP_AUTH_USER'],
                $_SERVER['PHP_AUTH_PW'],
            ];
        }

        $authheader = null;
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
            if (!empty($_SERVER[$key]) && is_string($_SERVER[$key])) {
                $authheader = $_SERVER[$key];
                break;
            }
        }

        $matches = [];
        if ($authheader && preg_match('/^\s*Basic\s+(.+)\s*$/i', $authheader, $matches)) {
            $decoded = base64_decode($matches[1], true);
            if ($decoded !== false && strpos($decoded, ':') !== false) {
                return explode(':', $decoded, 2);
            }
        }

        return [required_param('client_id', PARAM_RAW), null];
    }
}
