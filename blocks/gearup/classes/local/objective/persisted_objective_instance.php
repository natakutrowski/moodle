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
 * Persisted objective instance.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective;

use block_gearup\local\model\objective_inst;
use coding_exception;


/**
 * Persisted objective instance.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class persisted_objective_instance implements objective_instance {
    /** @var mixed Persistent. */
    protected $persistent;
    /** @var mixed Objective. */
    protected $objective;

    /**
     * Constructor.
     *
     * @param objective_inst $persistent Persistent.
     * @param objective $objective Objective.
     */
    public function __construct(objective_inst $persistent, objective $objective) {
        $this->persistent = $persistent;
        $this->objective = $objective;
    }

    /**
     * {@inheritDoc}
     */
    public function get_counter(): int {
        return (int) $this->persistent->get('counter');
    }

    /**
     * {@inheritDoc}
     */
    public function get_dormant_until(): ?\DateTimeImmutable {
        $ts = $this->persistent->get('dormantuntil');
        return $ts === null ? null : new \DateTimeImmutable('@' . $ts);
    }

    /**
     * {@inheritDoc}
     */
    public function get_mission_instance_id(): int {
        return (int) $this->persistent->get('missioninstid');
    }

    /**
     * {@inheritDoc}
     */
    public function get_objective(): objective {
        return $this->objective;
    }

    /**
     * Get persistent.
     *
     * @return objective_inst
     */
    public function get_persistent(): objective_inst {
        return $this->persistent;
    }

    /**
     * {@inheritDoc}
     */
    public function get_stale_from(): ?\DateTimeImmutable {
        $ts = $this->persistent->get('stalefrom');
        return $ts === null ? null : new \DateTimeImmutable('@' . $ts);
    }

    /**
     * {@inheritDoc}
     */
    public function get_type_state() {
        return $this->persistent->get('statedata');
    }

    /**
     * {@inheritDoc}
     */
    public function get_subject_id(): int {
        return (int) $this->persistent->get('subjectid');
    }

    /**
     * {@inheritDoc}
     */
    public function increment_counter(int $amount) {
        if ($this->is_completed()) {
            throw new coding_exception('Objective already completed.');
        }
        $this->persistent->increment_counter($amount, $this->objective->get_count_needed());
    }

    /**
     * {@inheritDoc}
     */
    public function is_completed(): bool {
        return $this->persistent->get('state') != 0;
    }

    /**
     * {@inheritDoc}
     */
    public function mark_complete() {
        // Future work: Record time at which objective is completed?
        $this->persistent->set('state', 1);
        $this->persistent->update();
    }

    /**
     * {@inheritDoc}
     */
    public function reset() {
        $this->persistent->set('counter', 0);
        $this->persistent->set('dormantuntil', null);
        $this->persistent->set('stalefrom', null);
        $this->persistent->set('state', 0);
        $this->persistent->set('statedata', null);
        $this->persistent->update();
    }

    /**
     * {@inheritDoc}
     */
    public function reset_counter() {
        if ($this->is_completed()) {
            throw new coding_exception('Objective already completed.');
        }
        $this->persistent->set('counter', 0);
        $this->persistent->update();
    }

    /**
     * {@inheritDoc}
     */
    public function set_dormant_until(?\DateTimeImmutable $date = null) {
        $this->persistent->set('dormantuntil', $date ? $date->getTimestamp() : null);
        $this->persistent->update();
    }

    /**
     * {@inheritDoc}
     */
    public function set_stale_from(?\DateTimeImmutable $date = null) {
        $this->persistent->set('stalefrom', $date ? $date->getTimestamp() : null);
        $this->persistent->update();
    }

    /**
     * {@inheritDoc}
     */
    public function set_type_state($state) {
        $this->persistent->set('statedata', $state);
        $this->persistent->update();
    }
}
