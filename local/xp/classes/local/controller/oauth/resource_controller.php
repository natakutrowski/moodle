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

/**
 * OAuth protected resource metadata controller.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resource_controller extends base_controller {
    /**
     * Output the protected resource metadata.
     *
     * @param request $request The request.
     * @return void
     */
    public function handle(request $request) {
        parent::handle($request);

        echo json_encode([
            // OAuth 2.0 Protected Resource Metadata (RFC 9728).
            'resource' => $this->service->get_mcp_url()->out(false),
            'authorization_servers' => [$this->service->get_issuer()],
            'bearer_methods_supported' => ['header'],
        ]);
    }
}
