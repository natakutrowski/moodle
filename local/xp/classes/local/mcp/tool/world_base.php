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

use block_xp\di;
use block_xp\local\course_world;
use block_xp\local\world;
use core\context;
use core\context\course;
use core\context\system;
use local_xp\local\mcp\invalid_params_exception;
use local_xp\local\mcp\schema;

/**
 * Base MCP tool for tools that target an XP world.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class world_base extends base {
    /** @var world */
    private $world;

    /**
     * Check world management permission.
     *
     * @return void
     */
    protected function permission_checks(): void {
        $world = $this->get_world();
        $accessperms = $world->get_access_permissions();

        // For now, all tools require manage permissions. We may include additional options in the future
        // but for now tools that require lower levels of permissions should overide this method.
        $accessperms->require_manage();
    }

    /**
     * Resolve the XP world context.
     *
     * @return context
     */
    final protected function resolve_context(): context {
        if (static::is_sitewide()) {
            return system::instance();
        }

        // Prevent world factory from normalising the context.
        $context = context::instance_by_id($this->get_param('context_id'));
        $iscourse = $context instanceof course;
        $issystem = $context instanceof system;
        $isvalid = $iscourse || ($issystem && static::is_global_world_enabled());
        if (!$isvalid) {
            throw new invalid_params_exception('Invalid context_id (level).');
        }

        return $context;
    }

    /**
     * Create an input schema scoped to a Level Up XP world.
     *
     * @param array $properties Schema properties.
     * @param string|string[] $description Description.
     * @return schema
     */
    protected static function world_scoped_schema(array $properties = [], $description = ''): schema {
        $schema = schema::object($properties, $description);

        if (static::is_sitewide()) {
            return $schema;
        }

        return $schema->unshift_property(
            'context_id',
            schema::integer('The Level Up XP context ID, obtainable by calling resolve_levelupxp_context.')
        );
    }

    /**
     * Get the world.
     *
     * @return world
     */
    final protected function get_world(): world {
        if (!$this->world) {
            $world = di::get('context_world_factory')->get_world_from_context($this->get_context());
            if ($world->get_context()->id != $this->get_context()->id) {
                throw new invalid_params_exception('Invalid context_id (mismatch).');
            } else if (!static::is_sitewide() && $world instanceof course_world && $world->get_courseid() == SITEID) {
                throw new invalid_params_exception('Invalid context_id (frontpage).');
            }
            $this->world = $world;
        }
        return $this->world;
    }
}
