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

/**
 * OAuth metadata controller.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata_controller extends base_controller {
    /**
     * Output the authorization server metadata.
     *
     * @param request $request The request.
     * @return void
     */
    public function handle(request $request) {
        parent::handle($request);

        // OAuth 2.0 Authorization Server Metadata (RFC 8414).
        echo json_encode([
            'issuer' => $this->service->get_issuer(),
            'authorization_endpoint' => $this->service->get_authorize_url()->out(false),
            'token_endpoint' => $this->service->get_token_url()->out(false),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => $this->service->get_code_challenge_methods_supported(),
            'token_endpoint_auth_methods_supported' => [oauth_client::TOKEN_ENDPOINT_AUTH_NONE],
            'jwks_uri' => $this->service->get_jwks_url()->out(false),
            'id_token_signing_alg_values_supported' => ['RS256'],

            // OAuth Client ID Metadata Document.
            'client_id_metadata_document_supported' => true,

            // OIDC properties.
            'subject_types_supported' => ['public'],
        ]);
    }
}
