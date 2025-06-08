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
use block_gearup\local\objective\objective_instance;

/**
 * Mock.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective_instance_mock implements objective_instance {

    protected $data;
    protected $objective;

    public function __construct(objective $objective, object $data) {
        $this->objective = $objective;
        $this->data = (object) array_merge([
            'counter' => 0,
            'missioninstid' => 0,
            'subjectid' => 0,
            'typestate' => null,
            'completed' => false,
            'dormantuntil' => null,
            'stalefrom' => null,
        ], (array) $data);
    }

    public function get_counter(): int {
        return $this->data->counter;
    }

    public function get_dormant_until(): ?\DateTimeImmutable {
        return $this->data->dormantuntil === null ? null : $this->data->dormantuntil;
    }

    public function get_mission_instance_id(): int {
        return $this->data->missioninstid;
    }

    public function get_objective(): objective {
        return $this->objective;
    }

    public function get_stale_from(): ?\DateTimeImmutable {
        return $this->data->stalefrom === null ? null : $this->data->stalefrom;
    }

    public function get_subject_id(): int {
        return $this->data->subjectid;
    }

    public function get_type_state() {
        return $this->data->typestate;
    }

    public function increment_counter(int $amount) {
        $this->data->counter += $amount;
    }

    public function is_completed(): bool {
        return $this->data->completed;
    }

    public function mark_complete() {
        $this->data->completed = true;
    }

    public function reset() {
        $this->data->counter = 0;
        $this->data->completed = false;
        $this->data->typestate = null;
        $this->data->dormantuntil = null;
        $this->data->stalefrom = null;
    }

    public function reset_counter() {
        $this->data->counter = 0;
    }

    public function set_dormant_until(?\DateTimeImmutable $date = null) {
        $this->data->dormantuntil = $date;
    }

    public function set_stale_from(?\DateTimeImmutable $date = null) {
        $this->data->stalefrom = $date;
    }

    public function set_type_state($state) {
        $this->data->typestate = $state;
    }

    /**
     * Mock-specific methods.
     */

    public function access_dormant_until() {
        return $this->data->dormantuntil;
    }

    public function access_stale_from() {
        return $this->data->stalefrom;
    }

}
