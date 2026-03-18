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
 * Repository.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\visual;

use context;

/**
 * Repository.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface repository_with_context extends repository {

    /**
     * Get a visual from a context.
     *
     * @param string $id The ID.
     * @param context $context The context.
     * @return visual|null
     */
    public function get_visual_from_context(string $id, context $context): ?visual;

    /**
     * Get the list of visuals.
     *
     * @return visual[]
     */
    public function get_visuals_from_context(context $context): array;

    /**
     * Get the list of visuals in the context.
     *
     * @return visual[]
     */
    public function get_visuals_in_context(context $context): array;

}
