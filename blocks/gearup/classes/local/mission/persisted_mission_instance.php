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
 * Persisted mission instance.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\mission;

use block_gearup\local\mission\mission;
use block_gearup\local\model\mission_inst;
use block_gearup\local\objective\objective_instance;
use DateTimeImmutable;

/**
 * Persisted mission instance.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class persisted_mission_instance implements mission_instance {

    protected $mission;
    protected $objectiveinsts;
    protected $persistent;

    public function __construct(mission_inst $persistent, mission $mission, array $objectiveinsts) {
        $this->persistent = $persistent;
        $this->mission = $mission;
        $this->objectiveinsts = $objectiveinsts;
    }

    public function get_id(): int {
        return (int) $this->persistent->get('id');
    }

    public function get_completion_ratio(): float {
        return (float) $this->persistent->get('completionratio');
    }

    public function get_counter(): int {
        return (int) $this->persistent->get('counter');
    }

    public function get_deadline(): ?\DateTimeImmutable {
        $deadline = $this->persistent->get('deadline');
        return $deadline ? new DateTimeImmutable('@' . $deadline) : null;
    }

    public function get_iteration_number(): int {
        return (int) $this->persistent->get('iteration');
    }

    public function get_instance_of_objective(int $objectiveid): objective_instance {
        foreach ($this->get_objective_instances() as $oi) {
            if ($oi->get_objective()->get_id() == $objectiveid) {
                return $oi;
            }
        }
        throw new \moodle_exception('Objective instance not found.');
    }

    public function get_mission(): mission {
        return $this->mission;
    }

    public function get_objective_instances(): array {
        return $this->objectiveinsts;
    }

    public function get_persistent(): mission_inst {
        return $this->persistent;
    }

    public function get_state(): int {
        return (int) $this->persistent->get('state');
    }

    public function get_subject_id(): int {
        return (int) $this->persistent->get('subjectid');
    }

    public function get_time_assigned(): \DateTimeImmutable {
        return new \DateTimeImmutable('@' . $this->persistent->get('timecreated'));
    }

    public function get_time_completed(): \DateTimeImmutable {
        $ts = $this->persistent->get('timecompleted');
        if (!$ts) {
            throw new \coding_exception('missionnotcompleted');
        }
        return new \DateTimeImmutable('@' . $ts);
    }

    public function get_time_ended(): \DateTimeImmutable {
        $ts = $this->persistent->get('timeended');
        if (!$ts) {
            throw new \coding_exception('missionnotended');
        }
        return new \DateTimeImmutable('@' . $ts);
    }

    public function get_time_started(): \DateTimeImmutable {
        $ts = $this->persistent->get('timestarted');
        if (!$ts) {
            throw new \coding_exception('missionnotstarted');
        }
        return new \DateTimeImmutable('@' . $ts);
    }

    public function increment_counter(int $amount) {
        // Possible race conditions here, but unlikely to be an issue at the moment.
        $this->persistent->set('counter', $this->get_counter() + $amount);
        $this->persistent->update();
    }

    public function needs_attention(): bool {
        return (bool) $this->persistent->get('needsattention');
    }

    public function reset_counter() {
        $this->persistent->set('counter', 0);
        $this->persistent->update();
    }

    public function set_completion_ratio(float $ratio) {
        $this->persistent->set('completionratio', $ratio);
        $this->persistent->update();
    }

    public function set_deadline(?\DateTimeImmutable $date) {
        $this->persistent->set('deadline', $date ? $date->getTimestamp() : 0);
        $this->persistent->update();
    }

    public function set_iteration_number(int $n) {
        $this->persistent->set('iteration', $n);
    }

    public function set_needs_attention(bool $bool) {
        $this->persistent->set('needsattention', (int) (bool) $bool);
        $this->persistent->update();
    }

    public function set_objective_instances(array $objinsts) {
        $this->objectiveinsts = $objinsts;
    }

    public function set_state(int $state) {
        $this->persistent->set('state', $state);
        $this->persistent->update();
    }

    public function set_time_assigned(\DateTimeImmutable $date) {
        // Nothing to do, this refers to the timecreated value.
    }

    public function set_time_completed(\DateTimeImmutable $date) {
        $this->persistent->set('timecompleted', $date->getTimestamp());
        $this->persistent->update();
    }

    public function set_time_ended(\DateTimeImmutable $date) {
        $this->persistent->set('timeended', $date->getTimestamp());
        $this->persistent->update();
    }

    public function set_time_started(\DateTimeImmutable $date) {
        $this->persistent->set('timestarted', $date->getTimestamp());
        $this->persistent->update();
    }

}
