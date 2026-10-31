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

use block_xp\local\course_world;
use block_xp\local\utils\world_utils;
use block_xp\local\world\world_with_features;
use local_xp\local\mcp\invalid_params_exception;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;
use local_xp\local\mcp\tool_exception;

/**
 * Get Level Up XP drops MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_levelupxp_drops extends world_base {
    /**
     * Get the drops tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'get_levelupxp_drops',
            'Get the drops',
            'Returns the drops available in a Level Up XP context.',
            [CONTEXT_SYSTEM, CONTEXT_COURSE],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the drops input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema([]);
    }

    /**
     * Get the drops output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'items' => schema::array_of(
                schema::object([
                    'name' => schema::string('The drop name.'),
                    'points' => schema::integer('Points awarded when the drop is encountered.'),
                    'shortcode_with_secret' => schema::string([
                        'The drop shortcode.',
                        'Contains an embedding secret;',
                        'Do not disclose the secret unless the user needs to place or embed the drop.',
                    ]),
                ]),
                'Drops available in the context.'
            ),
        ]);
    }

    /**
     * Check permissions.
     *
     * @return void
     */
    protected function permission_checks(): void {
        parent::permission_checks();

        // Validate that the feature is enabled.
        $world = $this->get_world();
        if (!world_utils::supports_local_points_management($world)) {
            throw new tool_exception('This tool is not available in this context.');
        }
    }

    /**
     * List the drops available in the selected world.
     *
     * @return array
     */
    protected function execute() {
        $db = \block_xp\di::get('db');
        $world = $this->get_world();
        if (!$world instanceof course_world) {
            throw new invalid_params_exception('Drops are not available in the overall context. Select a course context.');
        }
        $context = $world->get_context();

        // The drop repository resolves individual drops only, we need to query the DB directly.
        $drops = [];
        $records = $db->get_records('local_xp_drops', ['courseid' => $world->get_courseid()], 'name ASC, id ASC');
        foreach ($records as $record) {
            $drops[] = [
                'name' => format_string($record->name, true, ['context' => $context]),
                'points' => (int) $record->points,
                'shortcode_with_secret' => self::levelupxp_make_drop_shortcode((int) $record->id, $record->secret),
            ];
        }

        return ['items' => $drops];
    }

    /**
     * Make a drop shortcode.
     *
     * @param int $id Drop ID.
     * @param string $secret Drop secret.
     * @return string
     */
    protected static function levelupxp_make_drop_shortcode(int $id, string $secret): string {
        return "[xpdrop id={$id} secret={$secret}]";
    }
}
