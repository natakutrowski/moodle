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
 * Objective instance.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective;

/**
 * Objective instance interface.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface objective_instance {

    // /**
    // * Complete the objective.
    // *
    // * @return void
    // */
    // public function complete();

    // /**
    // * Compute the value of the action.
    // *
    // * @param action $action The action.
    // * @return integer
    // */
    // public function compute_action_value(action $action): int;

    /**
     * Get the counter.
     *
     * @return integer
     */
    public function get_counter(): int;

    /**
     * Get dormant until date.
     */
    public function get_dormant_until(): ?\DateTimeImmutable;

    /**
     * Get the mission instance ID.
     *
     * @return int
     */
    public function get_mission_instance_id(): int;

    /**
     * Get the objective.
     *
     * @return objective
     */
    public function get_objective(): objective;

    /**
     * Get stale from date.
     */
    public function get_stale_from(): ?\DateTimeImmutable;

    /**
     * Get the subject ID.
     *
     * @return int
     */
    public function get_subject_id(): int;

    /**
     * Get the type state.
     *
     * @return null|object
     */
    public function get_type_state();

    /**
     * Increment counter by amount.
     *
     * @param int $amount The amount.
     * @return void
     */
    public function increment_counter(int $amount);

    /**
     * Whether the objective is completed.
     *
     * @return bool
     */
    public function is_completed(): bool;

    /**
     * Mark the objective as complete.
     *
     * In theory, this should only be called when the counter has met the
     * requirements, but we can call this earlier, for instance when an
     * objective is manually marked as complete.
     *
     * @return bool
     */
    public function mark_complete();

    /**
     * Resets the objective.
     *
     * This resets the counter, the type state, and marks the objective as
     * incomplete. In most cases, you would only want to reset the counter.
     *
     * @return void
     */
    public function reset();

    /**
     * Reset the counter.
     *
     * @return void
     */
    public function reset_counter();

    /**
     * Set dormant until.
     *
     * When an instance is dormant, it indicates that it will not match
     * any conditions and can be ignored when processing actions, etc.
     *
     * Type must declare this value, but they are not meant to use it at
     * this stage. Types should be able to re-evaluate the state, or process
     * an action even if the instance is technically dormant.
     *
     * @param \DateTimeImmutable|null $date When null, removes the value.
     */
    public function set_dormant_until(?\DateTimeImmutable $date = null);

    /**
     * Set stale from.
     *
     * Indicates that an instance's state should be re-evaluated from a
     * certain point in time. This is useful for objectives that require
     * the user to perform actions within a time interval, whereby after
     * this interval their state should be adjusted.
     *
     * Type must declare this value, but they are not meant to use it at
     * this stage. Types should be able to identify when an instance is
     * to be considered stale.
     *
     * @param \DateTimeImmutable|null $date When null, removes the value.
     */
    public function set_stale_from(?\DateTimeImmutable $date = null);

    /**
     * Set the type state.
     *
     * @param null|object $state The state.
     */
    public function set_type_state($state);

}
