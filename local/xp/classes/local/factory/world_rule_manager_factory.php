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
// https://levelup.plus

namespace local_xp\local\factory;

use block_xp\local\rule\world_rule_manager;
use block_xp\local\world;
use local_xp\local\rule\world_rule_manager as local_xp_world_rule_manager;

/**
 * World rule manager factory.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class world_rule_manager_factory extends \block_xp\local\factory\world_rule_manager_factory {

    /**
     * Get a rule manager for a world.
     *
     * @param world $world The world.
     * @return world_rule_manager
     */
    public function get_rule_manager(world $world): world_rule_manager {
        return new local_xp_world_rule_manager($this->db, $world, $this->adminrulemanager);
    }

}
