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

namespace local_xp\local\controller\mcp;

use block_xp\di;
use block_xp\local\controller\controller;
use block_xp\local\routing\request;
use local_xp\local\mcp\jsonrpc_exception;
use local_xp\local\mcp\server;
use local_xp\local\mcp\tool_registry;

/**
 * MCP controller.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mcp_controller implements controller {
    /**
     * Execute the controller.
     *
     * @return void
     */
    public function handle(request $request) {
        if (!defined('AJAX_SCRIPT') || !AJAX_SCRIPT) {
            http_response_code(500);
            return;
        }

        $this->handle_cors($request);

        if ($request->get_method() !== 'POST') {
            http_response_code(405);
            header('Allow: POST, OPTIONS');
            return;
        }

        if (!((bool) di::get('config')->get('enablemcp'))) {
            $this->error(-32000, 'MCP access is disabled.', null, 403);
        }

        $authenticator = di::get('mcp_authenticator');
        $server = $this->make_server();

        $id = null;
        try {
            $authenticator->authenticate();

            $request = $this->get_request();
            if ($server->is_notification($request)) {
                http_response_code(202);
                return;
            }

            $this->validate_protocol_version($request);
            $server->validate_request($request);
            $id = $request['id'] ?? null;
            $response = $server->handle($request);
        } catch (jsonrpc_exception $e) {
            $this->error($e->get_rpc_code(), $e->getMessage(), $e->get_id(), $e->get_http_status(), $e->get_data());
        } catch (\moodle_exception $e) {
            $this->error(-32000, $e->getMessage(), $id, 200, $this->get_exception_data($e));
        } catch (\Throwable $e) {
            $this->error(-32603, 'Internal error', $id, 500);
        }

        if ($response === null) {
            http_response_code(202);
            return;
        }

        echo json_encode($response);
    }

    /**
     * Get the JSON-RPC request.
     *
     * @return array
     */
    protected function get_request(): array {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new jsonrpc_exception(-32600, 'Invalid Request', null, 405);
        }

        $rawbody = file_get_contents('php://input');
        $request = json_decode($rawbody, true);
        if ($request === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new jsonrpc_exception(-32700, 'Parse error', null, 400);
        }
        if (!is_array($request)) {
            throw new jsonrpc_exception(-32600, 'Invalid Request', null, 400);
        }
        return $request;
    }

    /**
     * Validate the MCP protocol version for non-initialize requests.
     *
     * @param array $request JSON-RPC request.
     * @return void
     */
    protected function validate_protocol_version(array $request): void {
        $method = $request['method'] ?? null;
        if (!is_string($method) || $method === 'initialize') {
            return;
        }

        $version = $this->get_protocol_version_header();

        if (!server::is_valid_protocol_version($version)) {
            throw new jsonrpc_exception(-32600, 'Invalid MCP protocol version.', $request['id'] ?? null, 400);
        }
        if (!server::is_supported_protocol_version($version)) {
            throw new jsonrpc_exception(-32600, 'Unsupported MCP protocol version.', $request['id'] ?? null, 400);
        }
    }

    /**
     * Get the MCP protocol version header.
     *
     * @return string|null
     */
    protected function get_protocol_version_header(): ?string {
        $fallback = '2025-03-26'; // Fallback as per specs.
        if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'CopilotStudio PowerFx') !== false) {
            $fallback = '2024-11-05'; // Fallback for Copilot Studio which initialized with that version.
        }
        $version = trim($_SERVER['HTTP_MCP_PROTOCOL_VERSION'] ?? '');
        return $version ?: $fallback;
    }

    /**
     * Get exception data for a JSON-RPC error.
     *
     * @param \moodle_exception $exception Exception.
     * @return array
     */
    protected function get_exception_data(\moodle_exception $exception): array {
        return [
            'errorcode' => $exception->errorcode,
        ];
    }

    /**
     * Make the server.
     *
     * @return server
     */
    protected function make_server(): server {
        $registry = di::get('mcp_tool_registry');
        return new server($registry);
    }

    /**
     * Send a JSON-RPC error and terminate.
     *
     * @param int $code JSON-RPC error code.
     * @param string $message Error message.
     * @param int|string|null $id JSON-RPC request ID.
     * @param int $httpstatus HTTP status.
     * @param mixed|null $data Error data.
     * @return never
     */
    protected function error(int $code, string $message, $id = null, int $httpstatus = 400, $data = null) {
        http_response_code($httpstatus);
        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
        if ($data !== null) {
            $response['error']['data'] = $data;
        }
        echo json_encode($response);
        exit;
    }

    /**
     * Handle CORS.
     */
    protected function handle_cors(request $request) {
        if (!defined('AJAX_SCRIPT') || !AJAX_SCRIPT) {
            return;
        }

        $origin = clean_param($_SERVER['HTTP_ORIGIN'] ?? '', PARAM_URL);
        $origins = explode("\n", (string) di::get('config')->get('mcpcorsorigins'));
        if ($origin && in_array($origin, $origins, true) || in_array('*', $origins)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Headers: Authorization, Content-Type, MCP-Protocol-Version, MCP-Session-Id');
            header('Access-Control-Allow-Methods: OPTIONS, POST');
            header('Access-Control-Expose-Headers: Content-Type, WWW-Authenticate');
        }

        if ($request->get_method() !== 'OPTIONS') {
            return;
        }
        die();
    }
}
