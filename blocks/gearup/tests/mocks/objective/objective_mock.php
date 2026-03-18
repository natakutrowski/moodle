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
 * Mock.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\tests\mock\objective;

use block_gearup\local\objective\objective;
use block_gearup\local\objective\type\type;

/**
 * Mock.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective_mock implements objective {

    protected $data;
    protected $type;

    public function __construct(type $type, object $data) {
        $this->type = $type;
        $this->data = $data;
    }

    public function get_id(): int {
        return $this->data->id ?? 0;
    }

    public function get_count_needed(): int {
        return $this->data->countneeded ?? 1;
    }

    public function get_label(): string {
        return $this->data->label ?? 'Objective label';
    }

    public function get_mission_id(): int {
        return $this->data->missionid ?? 0;
    }

    public function get_type(): type {
        return $this->type;
    }

    public function get_type_config() {
        return $this->data->typeconfig ?? null;
    }

}
