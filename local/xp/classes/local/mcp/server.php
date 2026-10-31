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

use block_xp\local\plugin\addon;

/**
 * MCP JSON-RPC server.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class server {
    /** @var string[] Supported MCP protocol versions, in preference order. */
    protected const SUPPORTED_PROTOCOL_VERSIONS = [
        '2025-11-25',
        '2024-11-05', // For Copilot Studio, stuck in the past...
    ];

    /** @var tool_registry Tool registry. */
    protected $registry;

    /**
     * Constructor.
     *
     * @param tool_registry $registry The tool registry.
     */
    final public function __construct(tool_registry $registry) {
        $this->registry = $registry;
    }

    /**
     * Handle a JSON-RPC request.
     *
     * @param array $request The request.
     * @return array|null
     */
    final public function handle(array $request): ?array {
        $this->validate_request($request);

        $id = $request['id'] ?? null;
        $method = $request['method'];
        $params = $request['params'] ?? [];

        if ($this->is_notification($request)) {
            if ($method === 'notifications/initialized') {
                return null;
            }

            // JSON-RPC notifications cannot receive a response, so unsupported notifications are accepted silently.
            return null;
        }

        if ($method === 'initialize') {
            return $this->response($id, $this->initialize_result($params, $id));
        } else if ($method === 'ping') {
            return $this->response($id, new \stdClass());
        } else if ($method === 'tools/list') {
            $definitions = $this->registry->get_definitions();
            return $this->response($id, [
                'tools' => array_values(array_map(function (tool_definition $def) {
                    $tool = [
                        'name' => $def->get_name(),
                        'title' => $def->get_title(),
                        'description' => $def->get_description(),
                        'inputSchema' => $def->get_input_schema()->to_object(),
                    ];

                    $outputschema = $def->get_output_schema();
                    if ($outputschema !== null) {
                        $tool['outputSchema'] = $outputschema->to_object();
                    }

                    $risk = $def->get_risk();
                    $isreadonly = !($risk & (tool_definition::RISK_WRITE | tool_definition::RISK_DELETE));
                    $annotations = [
                        'readOnlyHint' => $isreadonly,
                        // Some providers consider 'destructive' operations that cannot be undone, such as
                        // sending messages or notifications.
                        'destructiveHint' => (bool) ($risk & tool_definition::RISK_DELETE)
                            || (bool) ($risk & tool_definition::RISK_COMMS),
                        // Sending communications that could leave the site (email, notification) are defined
                        // as open world by some providers.
                        'openWorldHint' => (bool) ($risk & tool_definition::RISK_COMMS),
                    ];
                    $tool['annotations'] = $annotations;

                    return $tool;
                }, $definitions)),
            ]);
        } else if ($method === 'tools/call') {
            return $this->response($id, $this->call_tool($params, $id));
        }

        throw new jsonrpc_exception(-32601, 'Method not found', $id, 404);
    }

    /**
     * Get the preferred protocol version.
     *
     * @return string
     */
    final public static function get_preferred_protocol_version(): string {
        return static::SUPPORTED_PROTOCOL_VERSIONS[0];
    }

    /**
     * Whether a protocol version is supported.
     *
     * @param string $version The protocol version.
     * @return bool
     */
    final public static function is_supported_protocol_version(string $version): bool {
        return in_array($version, static::SUPPORTED_PROTOCOL_VERSIONS, true);
    }

    /**
     * Whether a protocol version is valid.
     *
     * @param string $version The protocol version.
     * @return bool
     */
    final public static function is_valid_protocol_version(string $version): bool {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $version, $matches)) {
            return false;
        }
        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }

    /**
     * Validate a JSON-RPC request.
     *
     * @param array $request The request.
     * @return void
     */
    final public function validate_request(array $request): void {
        $id = $this->get_valid_error_id($request);
        if (
            ($request['jsonrpc'] ?? '') !== '2.0' || isset($request[0]) ||
                !array_key_exists('method', $request) || !is_string($request['method'])
        ) {
            throw new jsonrpc_exception(-32600, 'Invalid Request', $id, 400);
        }
        if (array_key_exists('id', $request) && !is_int($request['id']) && !is_string($request['id'])) {
            throw new jsonrpc_exception(-32600, 'Invalid Request', null, 400);
        }
    }

    /**
     * Whether a request is a notification.
     *
     * @param array $request The request.
     * @return bool
     */
    final public function is_notification(array $request): bool {
        return !array_key_exists('id', $request);
    }

    /**
     * Get a valid request ID for an error response.
     *
     * @param array $request The request.
     * @return int|string|null
     */
    final protected function get_valid_error_id(array $request) {
        if (!array_key_exists('id', $request)) {
            return null;
        }
        return is_int($request['id']) || is_string($request['id']) ? $request['id'] : null;
    }

    /**
     * Call tool.
     *
     * @param array $params
     * @param string|int $id
     * @return array
     */
    final protected function call_tool($params, $id): array {
        if (!is_array($params)) {
            throw new jsonrpc_exception(-32602, 'Invalid params', $id, 400, [
                'errors' => [[
                    'path' => 'params',
                    'message' => 'must be an object.',
                ]],
            ]);
        }
        $name = $params['name'] ?? null;
        $args = $params['arguments'] ?? [];
        if (!is_string($name) || $name === '') {
            throw new jsonrpc_exception(-32602, 'Invalid params', $id, 400, [
                'errors' => [[
                    'path' => 'params.name',
                    'message' => 'must be a non-empty string.',
                ]],
            ]);
        }
        if (!is_array($args)) {
            throw new jsonrpc_exception(-32602, 'Invalid params', $id, 400, [
                'errors' => [[
                    'path' => 'params.arguments',
                    'message' => 'must be an object.',
                ]],
            ]);
        }

        if (!$this->registry->has_tool($name)) {
            throw new jsonrpc_exception(-32602, 'Unknown tool: ' . $name, $id, 404, [
                'errors' => [[
                    'path' => 'params.name',
                    'message' => 'does not identify an available tool.',
                ]],
            ]);
        }

        try {
            $tool = $this->registry->create_tool($name);
            $result = $tool->handle($args);
        } catch (\required_capability_exception $e) {
            return $this->tool_error_result($e->getMessage());
        } catch (invalid_params_exception $e) {
            if ($this->is_result_validation_error($e->get_data())) {
                return $this->tool_error_result('The tool returned an invalid result.');
            }
            return $this->tool_error_result($e->getMessage(), $e->get_data());
        } catch (\invalid_parameter_exception $e) {
            return $this->tool_error_result('Invalid tool arguments.');
        } catch (tool_exception $e) {
            return $this->tool_error_result($e->getMessage());
        }

        return [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($result),
            ]],
            'structuredContent' => $result,
        ];
    }

    /**
     * Build a tool error result.
     *
     * @param string $message Error message.
     * @param array $data Error data.
     * @return array
     */
    final protected function tool_error_result(string $message, array $data = []): array {
        if (!empty($data['errors'])) {
            $lines = [$message];
            foreach ($data['errors'] as $error) {
                $lines[] = '- ' . $error['path'] . ': ' . $error['message'];
            }
            $message = implode("\n", $lines);
        }

        return [
            'content' => [[
                'type' => 'text',
                'text' => $message,
            ]],
            'isError' => true,
        ];
    }

    /**
     * Whether validation errors are for a tool result.
     *
     * @param array $data Error data.
     * @return bool
     */
    final protected function is_result_validation_error(array $data): bool {
        foreach ($data['errors'] ?? [] as $error) {
            if (isset($error['path']) && strpos($error['path'], 'result') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Initialise result.
     *
     * @param array $params
     * @param string|int $id
     * @return array
     */
    final protected function initialize_result($params, $id): array {
        if (!is_array($params)) {
            throw new jsonrpc_exception(-32602, 'Invalid params', $id, 400, [
                'errors' => [[
                    'path' => 'params',
                    'message' => 'must be an object.',
                ]],
            ]);
        }

        $requestedversion = $params['protocolVersion'] ?? null;
        if (!is_string($requestedversion) || !static::is_valid_protocol_version($requestedversion)) {
            throw new jsonrpc_exception(-32602, 'Invalid params', $id, 400, [
                'errors' => [[
                    'path' => 'params.protocolVersion',
                    'message' => 'must be a valid MCP protocol version.',
                ]],
            ]);
        }

        $protocolversion = static::is_supported_protocol_version($requestedversion)
            ? $requestedversion
            : static::get_preferred_protocol_version();

        return [
            'protocolVersion' => $protocolversion,
            'serverInfo' => [
                'name' => 'levelup_xp',
                'version' => addon::get_plugin_info()->release,
            ],
            'capabilities' => [
                'tools' => new \stdClass(),
            ],
            'instructions' => <<<'MCP_INSTRUCTIONS'

# Level Up XP MCP instructions

Level Up XP is a gamification product that adds points, levels, progress, and leaderboards to Moodle. This MCP
supports teachers, instructors, and staff in their use of Level Up XP. It is not designed for students and learners.

Users may refer to:
- Level Up XP or Level Up XP+ as “Level Up”, “XP”, or “XP+”
- Moodle as the LMS, learning platform, portal, or an institution-specific name
- courses as units, modules, subjects, classes, papers, shells, or offerings

Internal identifiers such as context_id, user_id, group_id, and rule_id are for tool calls only. Do not mention them
to the user unless explicitly asked. Prefer human-readable names in responses.

When a course or user is not uniquely identified, resolve it first using the relevant resolver tool. If multiple
matches are returned and the conversation does not clearly disambiguate them, ask the user which one they mean. Do
not guess.

If a requested action is not supported by the available tools, check the
[Level Up XP documentation](https://docs.levelup.plus/xp/) first and guide the user as per the docs. If the docs do
not cover it, explain that clearly and do not invent unsupported features or workflows.
MCP_INSTRUCTIONS
            ,
        ];
    }

    /**
     * Initialise result.
     *
     * @param string|int $id
     * @param array $result
     * @return array
     */
    final protected function response($id, $result): array {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }
}
