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

namespace local_xp\local\routing;

use block_xp\local\routing\route_definition;
use block_xp\local\routing\static_routes_config;

/**
 * OAuth routes config.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth_routes_config extends static_routes_config {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct([
            'authorize' => new route_definition('authorize', '/authorize', '~^/authorize/?$~', 'authorize'),
            'token' => new route_definition('token', '/token', '~^/token/?$~', 'token'),
            'resource' => new route_definition('resource', '/resource', '~^/resource/?$~', 'resource'),
            'jwks' => new route_definition('jwks', '/jwks.json', '~^/jwks\.json$~', 'jwks'),
            'metadata' => new route_definition(
                'metadata',
                '/.well-known/oauth-authorization-server',
                '~^/\.well\-known/oauth\-authorization\-server/?$~',
                'metadata'
            ),
            'wellknown' => new route_definition(
                'wellknown',
                '/.well-known/openid-configuration',
                '~^/\.well\-known/openid\-configuration/?$~',
                'metadata'
            ),
        ]);
    }
}
