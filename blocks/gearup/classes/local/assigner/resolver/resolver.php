<?php
// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

/**
 * Resolver.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\assigner\resolver;

use block_gearup\local\assigner\type\type;

/**
 * Resolver.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface resolver {

    /**
     * Get type by name.
     *
     * @param string $name The type name.
     * @return type
     */
    public function get_type($name): type;

    /**
     * Get the type's name.
     *
     * @param type $type The type instance.
     */
    public function get_type_name(type $type): string;

    /**
     * Get all types.
     *
     * @return type[]
     */
    public function get_types(): array;

    /**
     * Get the types available for the user.
     *
     * @param int $userid The user ID.
     * @param \context $context
     * @return type[]
     */
    public function get_types_available_for_user(int $userid, \context $context): array;

    /**
     * Get the types compatible with event.
     *
     * @param \core\event\base $event
     * @return type[]
     */
    public function get_types_compatible_with_event(\core\event\base $event): array;

}
