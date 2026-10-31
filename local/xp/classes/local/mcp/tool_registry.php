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

use core\exception\coding_exception;
use local_xp\local\mcp\tool\base;

/**
 * MCP tool registry.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_registry {
    /** @var string[] Tool classes. */
    protected const TOOL_CLASSES = [
        tool\award_levelupxp_points::class,
        tool\get_levelupxp_action_rules::class,
        tool\get_levelupxp_completion_rules::class,
        tool\get_levelupxp_context_config::class,
        tool\get_levelupxp_gamification_capabilities_in_context::class,
        tool\get_levelupxp_drops::class,
        tool\get_levelupxp_leaderboard::class,
        tool\get_levelupxp_levels::class,
        tool\get_levelupxp_team_leaderboard::class,
        tool\get_levelupxp_user::class,
        tool\list_course_contents::class,
        tool\list_levelupxp_groups::class,
        tool\list_levelupxp_logs::class,
        tool\list_levelupxp_user_states::class,
        tool\resolve_levelupxp_context::class,
        tool\resolve_course::class,
        tool\resolve_levelupxp_user::class,
    ];

    /** @var ?int The allowed risks. */
    protected $allowedrisks;
    /** @var array|null Tool metadata indexed by tool name. */
    protected $metadata;

    /**
     * Create a tool.
     *
     * @param string $name The tool name.
     * @return base
     */
    public function create_tool(string $name): base {
        $metadata = $this->get_metadata_by_name();
        if (!isset($metadata[$name])) {
            throw new invalid_params_exception('Unknown MCP tool: ' . $name);
        }
        $class = $metadata[$name]['class'];
        return new $class();
    }

    /**
     * Get all definitions.
     *
     * @return array
     */
    public function get_all_definitions(): array {
        $defs = [];
        foreach (static::TOOL_CLASSES as $class) {
            $reflection = new \ReflectionClass($class);
            if (!$reflection->isSubclassOf(base::class)) {
                throw new coding_exception('MCP tool class must extend base_tool.');
            }

            $definition = $class::get_definition();
            if (!$definition instanceof tool_definition) {
                throw new coding_exception('MCP tool class must return a tool definition.');
            }

            $defs[] = $definition;
        }
        return $defs;
    }

    /**
     * Get the enabled tool definitions.
     *
     * @return tool_definition[]
     */
    public function get_definitions(): array {
        return array_values(array_map(function ($metadata) {
            return $metadata['definition'];
        }, $this->get_metadata_by_name()));
    }

    /**
     * Whether a tool exists.
     *
     * @param string $name The tool name.
     * @return bool
     */
    public function has_tool(string $name): bool {
        return isset($this->get_metadata_by_name()[$name]);
    }

    /**
     * Get the configured context level.
     *
     * @return int
     */
    protected function get_config_context(): int {
        return (int) \block_xp\di::get('config')->get('context');
    }

    /**
     * Get tool metadata indexed by name.
     *
     * @return array
     */
    protected function get_metadata_by_name(): array {
        if ($this->metadata !== null) {
            return $this->metadata;
        }

        $configcontext = $this->get_config_context();
        $this->metadata = [];
        foreach (static::TOOL_CLASSES as $class) {
            $reflection = new \ReflectionClass($class);
            if (!$reflection->isSubclassOf(base::class)) {
                throw new coding_exception('MCP tool class must extend base_tool.');
            }

            $definition = $class::get_definition();
            if (!$definition instanceof tool_definition) {
                throw new coding_exception('MCP tool class must return a tool definition.');
            }

            if (!$this->is_tool_enabled($definition)) {
                continue;
            }

            // In the future we may want to discard the tools that we are certain cannot be used. We can
            // do this by attaching tools to permissions, and checking if that permission is given anywhere.
            // However, at the moment the manage permission is required to be aware of the MCP, so most
            // users should have them. We only expect tools related to logs and reports to occasionally be
            // visible while the user will not have the permission to use them. Users without the manage
            // permission could theoretically authenticate and connect to the MCP, but most tools won't work.
            // We also note that if a user has teacher access anywhere (such as in a sandbox course), they could
            // then discover all the tools.

            $name = $definition->get_name();
            if (isset($this->metadata[$name])) {
                throw new coding_exception('Duplicate MCP tool name.');
            }

            $this->metadata[$name] = [
                'class' => $class,
                'definition' => $definition,
            ];
        }
        return $this->metadata;
    }

    /**
     * Whether the tool is enabled.
     *
     * @param tool_definition $definition
     * @return bool
     */
    public function is_tool_enabled(tool_definition $definition) {
        $configcontext = $this->get_config_context();

        // Filter as per sitewide vs. per course.
        if (!in_array($configcontext, $definition->get_supported_modes(), true)) {
            return false;
        }

        // Filter out risks not permitted.
        if ($this->allowedrisks !== null && ($definition->get_risk() & ~$this->allowedrisks)) {
            return false;
        }

        return true;
    }

    /**
     * Set allowed risks.
     *
     * @param int|null $risks
     */
    public function set_allowed_risks(?int $risks) {
        $this->allowedrisks = $risks;
    }
}
