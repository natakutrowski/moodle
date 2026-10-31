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

/**
 * JSON-RPC exception.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class jsonrpc_exception extends \Exception {
    /** @var int|string|null Request ID. */
    protected $id;
    /** @var mixed|null Error data. */
    protected $data;
    /** @var int HTTP status. */
    protected $httpstatus;
    /** @var int JSON-RPC code. */
    protected $rpccode;

    /**
     * Constructor.
     *
     * @param int $code JSON-RPC error code.
     * @param string $message Error message.
     * @param int|string|null $id Request ID.
     * @param int $httpstatus HTTP status.
     * @param mixed|null $data Optional error data.
     */
    public function __construct(int $code, string $message, $id = null, int $httpstatus = 400, $data = null) {
        parent::__construct($message, $code);
        $this->data = $data;
        $this->id = $id;
        $this->httpstatus = $httpstatus;
        $this->rpccode = $code;
    }

    /**
     * Get error data.
     *
     * @return mixed|null
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Get the HTTP status.
     *
     * @return int
     */
    public function get_http_status(): int {
        return $this->httpstatus;
    }

    /**
     * Get the request ID.
     *
     * @return int|string|null
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get the JSON-RPC error code.
     *
     * @return int
     */
    public function get_rpc_code(): int {
        return $this->rpccode;
    }
}
