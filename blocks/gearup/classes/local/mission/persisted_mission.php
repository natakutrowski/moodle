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
 * Persisted quest.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\mission;

use block_gearup\di;
use block_gearup\local\model\mission as mission_model;
use block_gearup\local\mission\mission;
use block_gearup\local\objective\objective;
use block_gearup\local\visual\repository_with_context;
use block_gearup\local\visual\static_visual;
use block_gearup\local\visual\visual;
use context;
use moodle_exception;

/**
 * Persisted quest.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class persisted_mission implements mission {

    protected $objectivescallable;
    protected $objectives;
    protected $persistent;

    /** @var \block_gearup\local\visual\repository */
    protected $visualrepo;

    public function __construct(mission_model $persistent, $objectivesorcallable, $visualrepo = null) {
        $this->persistent = $persistent;
        $this->objectives = is_callable($objectivesorcallable) ? null : $objectivesorcallable;
        $this->objectivescallable = is_callable($objectivesorcallable) ? $objectivesorcallable : null;

        // We probably should not be doing this like this.
        $this->visualrepo = null;
        if ($persistent->get('type') == mission_model::TYPE_ACHIEVEMENT) {
            $this->visualrepo = di::get('achievement_badges_repository');
        } else if ($persistent->get('type') == mission_model::TYPE_QUEST) {
            $this->visualrepo = di::get('quest_narrator_visuals_repository');
        }
    }

    public function get_context(): context {
        return context::instance_by_id($this->persistent->get('contextid'));
    }

    public function get_id(): int {
        return (int) $this->persistent->get('id');
    }

    public function get_state(): int {
        return (int) $this->persistent->get('state');
    }

    public function get_title(): string {
        return $this->persistent->get('title');
    }

    public function get_description(): string {
        return (string) $this->persistent->get('description');
    }

    public function get_feedback(): string {
        return (string) $this->persistent->get('feedback');
    }

    public function get_instructions(): string {
        return (string) $this->persistent->get('instructions');
    }

    public function get_objective(int $id): objective {
        foreach ($this->get_objectives() as $objective) {
            if ($objective->get_id() == $id) {
                return $objective;
            }
        }
        throw new moodle_exception('Unknown objective');
    }

    public function get_objectives(): array {
        if ($this->objectives === null) {
            $fn = $this->objectivescallable;
            $this->objectives = $fn();
        }
        return $this->objectives;
    }

    public function get_persistent(): mission_model {
        return $this->persistent;
    }

    public function get_repeat_count(): int {
        return (int) $this->persistent->get('repeatcount');
    }

    public function get_secret(): string {
        return $this->persistent->get('secret');
    }

    public function get_start_mode(): int {
        return (int) $this->persistent->get('startmode');
    }

    public function get_time_limit(): int {
        return (int) $this->persistent->get('timelimit');
    }

    public function get_visibility(): int {
        return (int) $this->persistent->get('visibility');
    }

    public function get_visual(): ?visual {
        $visual = $this->persistent->get('visual');
        if ($this instanceof streak) {
            $output = di::get('renderer');
            return new static_visual('streak', $output->image_url('streak', 'block_gearup'),
                new \lang_string('streak', 'block_gearup'));
        }
        if (!empty($visual) && $this->visualrepo) {
            if ($this->visualrepo instanceof repository_with_context) {
                return $this->visualrepo->get_visual_from_context($visual, $this->get_context());
            }
            return $this->visualrepo->get_visual($visual);
        }
        return null;
    }

}
