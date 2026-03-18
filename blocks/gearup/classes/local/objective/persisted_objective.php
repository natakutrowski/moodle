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
 * Persisted objective.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective;

use block_gearup\local\model\objective as objective_persistent;
use block_gearup\local\objective\type\type;
use block_gearup\local\objective\type\type_with_supporting_url;
use moodle_url;

/**
 * Persisted objective.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class persisted_objective implements objective, objective_with_supporting_url {

    protected $mission;
    protected $persistent;
    protected $objtyperesolver;

    public function __construct(objective_persistent $persistent, $objtyperesolver) {
        $this->persistent = $persistent;
        $this->objtyperesolver = $objtyperesolver;
    }

    public function get_id(): int {
        return $this->persistent->get('id');
    }

    public function get_count_needed(): int {
        return $this->persistent->get('countneeded');
    }

    public function get_label(): string {
        return $this->persistent->get('label');
    }

    public function get_mission_id(): int {
        return $this->persistent->get('missionid');
    }

    public function get_persistent(): objective_persistent {
        return $this->persistent;
    }

    public function get_supporting_url(): ?moodle_url {
        $supportingurl = $this->persistent->get('supportingurl');
        if ($supportingurl === null) {
            return null;
        } else if (is_string($supportingurl)) {
            return new moodle_url($supportingurl);
        }
        $type = $this->get_type();
        if ($type instanceof type_with_supporting_url) {
            return $type->get_supporting_url($this, $supportingurl);
        }
        return null;
    }

    public function get_type(): type {
        $type = $this->persistent->get('type');
        return $this->objtyperesolver->get_type($type);
    }

    public function get_type_config() {
        return $this->persistent->get('configdata');
    }

}
