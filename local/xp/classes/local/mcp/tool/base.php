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

namespace local_xp\local\mcp\tool;

use core\context;
use core\exception\coding_exception;
use local_xp\local\mcp\tool_definition;
use local_xp\local\mcp\validator;

/**
 * Base MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var array Arguments. */
    protected $arguments = [];
    /** @var context|null Context. */
    protected $context = null;
    /** @var tool_definition|null Definition used for the current call. */
    protected $definition = null;

    /**
     * Get the tool definition.
     *
     * @return tool_definition
     */
    abstract public static function get_definition(): tool_definition;

    /**
     * Handle the tool execution.
     *
     * @param array $rawarguments
     * @return mixed
     */
    final public function handle(array $rawarguments) {
        $this->definition = static::get_definition();
        $this->arguments = $this->validate_arguments($rawarguments);
        $this->context = $this->resolve_context();
        if (!$this->context instanceof context) {
            throw new coding_exception('MCP tool context resolution must return a context.');
        }

        $this->require_login();
        $this->check_mcp_permission();
        $this->permission_checks();
        $rawresult = $this->execute();
        $cleanedresult = $this->validate_result($rawresult);
        return $cleanedresult;
    }

    /**
     * Check if the user has permission to use MCP.
     *
     * @return void
     */
    final protected function check_mcp_permission(): void {
        // This check applies to the current context (possible course context) so that we allow
        // for forbidding usage of the MCP tools in this context. This is not a workaround the need
        // for the permission to be assigned at the system level, which is needed for MCP connection.
        require_capability('local/xp:usemcp', $this->get_context());
    }

    /**
     * Check the permissions to invoke this tool.
     *
     * @return void
     */
    abstract protected function permission_checks(): void;

    /**
     * Execute the tool.
     *
     * @return mixed The result.
     */
    abstract protected function execute();

    /**
     * Get the context.
     *
     * @return context
     */
    final protected function get_context(): context {
        if (!$this->context instanceof context) {
            throw new coding_exception('MCP tool context used before it was resolved.');
        }
        return $this->context;
    }

    /**
     * Get a parameter.
     *
     * @param string $name The parameter name.
     * @param mixed|null $default The default value if the parameter is not set.
     * @return mixed The parameter value.
     */
    final protected function get_param(string $name, $default = null) {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        if (func_num_args() >= 2) {
            return $default;
        }
        throw new coding_exception('Unknown MCP tool parameter: ' . $name);
    }

    /**
     * Resolve the tool context.
     *
     * @return context
     */
    abstract protected function resolve_context(): context;

    /**
     * Whether the global world is enabled.
     *
     * @return bool
     */
    protected static function is_global_world_enabled(): bool {
        return !static::is_sitewide() && (bool) \block_xp\di::get('config')->get('enableglobalworld');
    }

    /**
     * Whether Level Up XP is configured sitewide.
     *
     * @return bool
     */
    protected static function is_sitewide(): bool {
        return ((int) \block_xp\di::get('config')->get('context')) === CONTEXT_SYSTEM;
    }

    /**
     * Validate arguments.
     *
     * @param array $arguments
     * @return array
     */
    final protected function validate_arguments(array $arguments): array {
        return validator::validate($this->get_current_definition()->get_input_schema(), $arguments, 'arguments');
    }

    /**
     * Require login, which checks context access.
     *
     * @return void
     */
    final protected function require_login(): void {
        $coursecontext = $this->get_context()->get_course_context(false);
        $courseid = $coursecontext ? $coursecontext->instanceid : null;
        require_login($courseid, false, null, false, true);
    }

    /**
     * Validate the result
     *
     * @param mixed $result
     * @return mixed
     */
    final protected function validate_result($result) {
        $outputschema = $this->get_current_definition()->get_output_schema();
        if ($outputschema === null) {
            return null;
        }
        return validator::validate($outputschema, $result, 'result');
    }

    /**
     * Get the definition used for the current call.
     *
     * @return tool_definition
     */
    final protected function get_current_definition(): tool_definition {
        if (!$this->definition instanceof tool_definition) {
            throw new coding_exception('MCP tool definition used before it was resolved.');
        }
        return $this->definition;
    }
}
