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
// See <https://levelup.plus>.

/**
 * Mock.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\tests\mock\mission;

use block_gearup\di;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective_instance;
use DateTimeImmutable;

/**
 * Mock.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_instance_mock implements mission_instance {
    /** @var mixed Data. */
    protected $data;
    /** @var mixed Mission. */
    protected $mission;

    /**
     * Constructor.
     *
     * @param mission $mission Mission.
     * @param object $data Data.
     */
    public function __construct(mission $mission, object $data) {
        $this->mission = $mission;
        $this->data = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function get_id(): int {
        return $this->data->id ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    public function get_mission(): mission {
        return $this->mission;
    }

    /**
     * {@inheritDoc}
     */
    public function get_completion_ratio(): float {
        return $this->data->completionratio ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    public function get_counter(): int {
        return $this->data->counter ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    public function get_deadline(): ?DateTimeImmutable {
        return $this->data->deadline ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function get_instance_of_objective(int $objectiveid): objective_instance {
        foreach ($this->get_objective_instances() as $objinst) {
            if ($objinst->get_objective()->get_id() === $objectiveid) {
                return $objinst;
            }
        }
        throw new \coding_exception('notfound');
    }

    /**
     * {@inheritDoc}
     */
    public function get_iteration_number(): int {
        return $this->data->iteration ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    public function get_objective_instances(): array {
        return $this->data->objinsts ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_state(): int {
        return $this->data->state ?? self::STATE_STARTED;
    }

    /**
     * {@inheritDoc}
     */
    public function get_subject_id(): int {
        return $this->data->subjectid ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    public function get_time_assigned(): \DateTimeImmutable {
        return $this->data->timeassigned ?? di::get('clock')->now();
    }

    /**
     * {@inheritDoc}
     */
    public function get_time_completed(): \DateTimeImmutable {
        return $this->data->timecompleted ?? di::get('clock')->now();
    }

    /**
     * {@inheritDoc}
     */
    public function get_time_ended(): \DateTimeImmutable {
        return $this->data->timeended ?? di::get('clock')->now();
    }

    /**
     * {@inheritDoc}
     */
    public function get_time_started(): \DateTimeImmutable {
        return $this->data->timestarted ?? di::get('clock')->now();
    }

    /**
     * {@inheritDoc}
     */
    public function increment_counter(int $amount) {
        $this->data->counter = ($this->data->counter ?? 0) + $amount;
    }

    /**
     * {@inheritDoc}
     */
    public function needs_attention(): bool {
        return (bool) ($this->data->needsattention ?? false);
    }

    /**
     * {@inheritDoc}
     */
    public function reset_counter() {
        $this->data->counter = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function set_completion_ratio(float $ratio) {
        $this->data->completion_ratio = $ratio;
    }

    /**
     * {@inheritDoc}
     */
    public function set_deadline(?DateTimeImmutable $date) {
        $this->data->deadline = $date;
    }

    /**
     * {@inheritDoc}
     */
    public function set_iteration_number(int $n) {
        $this->data->iteration = $n;
    }

    /**
     * {@inheritDoc}
     */
    public function set_needs_attention(bool $value) {
        $this->data->needsattention = (bool) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function set_objective_instances(array $objinsts) {
        $this->data->objinsts = $objinsts;
    }

    /**
     * {@inheritDoc}
     */
    public function set_state(int $state) {
        $this->data->state = $state;
    }

    /**
     * {@inheritDoc}
     */
    public function set_time_assigned(\DateTimeImmutable $date) {
        $this->data->time_assigned = $date;
    }

    /**
     * {@inheritDoc}
     */
    public function set_time_completed(\DateTimeImmutable $date) {
        $this->data->time_completed = $date;
    }

    /**
     * {@inheritDoc}
     */
    public function set_time_started(\DateTimeImmutable $date) {
        $this->data->time_started = $date;
    }

    /**
     * {@inheritDoc}
     */
    public function set_time_ended(\DateTimeImmutable $date) {
        $this->data->time_ended = $date;
    }
}
