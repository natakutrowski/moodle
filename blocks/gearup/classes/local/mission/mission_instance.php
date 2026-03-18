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
 * Mission instance.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\mission;

use block_gearup\local\objective\objective_instance;

/**
 * Mission instance interface.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface mission_instance {

    /** Assigned, but not yet started. */
    const STATE_ASSIGNED = 0;
    /** Started. */
    const STATE_STARTED = 1;
    /** Completed. */
    const STATE_COMPLETED = 2;
    /** Ended. */
    const STATE_ENDED = 10;

    /**
     * Get the instance ID.
     *
     * @return int
     */
    public function get_id(): int;

    /**
     * Get the mission.
     *
     * @return mission
     */
    public function get_mission(): mission;

    /**
     * Get the completion ratio.
     *
     * This may not be 1 even when the mission is complete.
     *
     * @return float
     */
    public function get_completion_ratio(): float;

    /**
     * Get the counter.
     *
     * @return int
     */
    public function get_counter(): int;

    /**
     * Get the deadline.
     *
     * @return \DateTimeImmutable|null
     */
    public function get_deadline(): ?\DateTimeImmutable;

    /**
     * Get the objective instance by objective ID.
     *
     * @param int $objectiveid The objective ID.
     * @return objective_instance
     * @throws \moodle_exception When not found.
     */
    public function get_instance_of_objective(int $objectiveid): objective_instance;

    /**
     * Get the iteration number.
     *
     * @return int
     */
    public function get_iteration_number(): int;

    /**
     * Get the objective instances.
     *
     * @return objective_instance[]
     */
    public function get_objective_instances(): array;

    /**
     * Return the state.
     *
     * This will be one of the self::STATE_* constant value.
     *
     * @return integer
     */
    public function get_state(): int;

    /**
     * Get the subject ID.
     *
     * @return int
     */
    public function get_subject_id(): int;

    /**
     * Get the time at which the mission was assigned.
     *
     * @return \DateTimeImmutable
     * @throws \coding_exception When not assigned.
     */
    public function get_time_assigned(): \DateTimeImmutable;
    // TODO Do we really need to throw the exception here? This makes it a bit more complex for no reason.

    /**
     * Get the time at which the mission was completed.
     *
     * @return \DateTimeImmutable
     * @throws \coding_exception When not complete.
     */
    public function get_time_completed(): \DateTimeImmutable;

    /**
     * Get the time at which the mission was ended.
     *
     * @return \DateTimeImmutable
     * @throws \coding_exception When not ended.
     */
    public function get_time_ended(): \DateTimeImmutable;

    /**
     * Get the time at which the mission was started.
     *
     * @return \DateTimeImmutable
     * @throws \coding_exception When not started.
     */
    public function get_time_started(): \DateTimeImmutable;

    /**
     * Increment counter by amount.
     *
     * @param int $amount The amount.
     * @return void
     */
    public function increment_counter(int $amount);

    /**
     * Whether the instance needs attention from its subject.
     *
     * @return bool
     */
    public function needs_attention(): bool;

    /**
     * Reset the counter.
     *
     * @return void
     */
    public function reset_counter();

    /**
     * Set the completion ratio.
     *
     * Note that it is possible for a mission to be completed with
     * a ratio inferior to 1 when it was marked as complete.
     *
     * @param float $ratio The ratio (between 0 and 1 inclusives).
     */
    public function set_completion_ratio(float $ratio);

    /**
     * Get the deadline.
     *
     * @param \DateTimeImmutable|null $date The deadline.
     */
    public function set_deadline(?\DateTimeImmutable $date);

    /**
     * Set the iteration number.
     *
     * @param int $n The number.
     */
    public function set_iteration_number(int $n);

    /**
     * Whether the instance needs attention from its subject.
     *
     * @param bool $bool Whether it needs attention.
     * @return bool
     */
    public function set_needs_attention(bool $bool);

    /**
     * Set the objective instances.
     *
     * @param objective_instance[] $objinsts The objective instances.
     */
    public function set_objective_instances(array $objinsts);

    /**
     * Set the state.
     *
     * In theory, setting the state as completed should only be done when all the
     * objectives have been completed. Once a mission is complete, it should be
     * frozen and no longer acted upon. However, the state could be changed
     * arbitrarily.
     *
     * @param int $state One of the self::STATE_* constants.
     */
    public function set_state(int $state);

    /**
     * Set the time at which the mission was assigned.
     *
     * @param \DateTimeImmutable $date The date.
     */
    public function set_time_assigned(\DateTimeImmutable $date);

    /**
     * Set the time at which the mission was completed.
     *
     * @param \DateTimeImmutable $date The date.
     */
    public function set_time_completed(\DateTimeImmutable $date);

    /**
     * Set the time at which the mission was started.
     *
     * @param \DateTimeImmutable $date The date.
     */
    public function set_time_started(\DateTimeImmutable $date);

    /**
     * Set the time at which the mission was ended.
     *
     * @param \DateTimeImmutable $date The date.
     */
    public function set_time_ended(\DateTimeImmutable $date);

}
