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

namespace block_gearup\local\repository;

use context;

/**
 * Query.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface query {

    /**
     * Get the acting context.
     *
     * @return context The acting context.
     */
    public function get_acting_context(): context;

    /**
     * The annotations.
     *
     * @return array
     */
    public function get_annotations(): array;

    /**
     * Get a condition.
     *
     * @param string $name The condition name.
     * @return mixed
     */
    public function get_condition(string $name);

    /**
     * The conditions.
     *
     * @return array Where the keys are the condition names.
     */
    public function get_conditions(): array;

    /**
     * The order by.
     *
     * @return array Or arrays with order by and direction.
     */
    public function get_order_by(): array;

    /**
     * Whether we have a annotation.
     *
     * @param string $name The annotation name.
     * @return bool
     */
    public function has_annotation(string $name): bool;

    /**
     * Whether we have a condition.
     *
     * @param string $name The condition name.
     * @return bool
     */
    public function has_condition(string $name): bool;

}
