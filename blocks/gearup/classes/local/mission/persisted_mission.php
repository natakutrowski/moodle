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
use block_gearup\local\objective\persisted_objective;
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
    /** @var mixed Objectivescallable. */
    protected $objectivescallable;
    /** @var mixed Objectives. */
    protected $objectives;
    /** @var mixed Persistent. */
    protected $persistent;

    /** @var \block_gearup\local\visual\repository */
    protected $visualrepo;

    /**
     * Constructor.
     *
     * @param mission_model $persistent Persistent.
     * @param mixed $objectivesorcallable Objectivesorcallable.
     * @param mixed $visualrepo Visualrepo.
     */
    public function __construct(mission_model $persistent, $objectivesorcallable, $visualrepo = null) {
        $this->persistent = $persistent;
        $this->objectivescallable = is_callable($objectivesorcallable) ? $objectivesorcallable : null;
        if ($this->objectivescallable === null) {
            $this->set_objectives($objectivesorcallable);
        }

        // We probably should not be doing this like this.
        $this->visualrepo = null;
        if ($persistent->get('type') == mission_model::TYPE_ACHIEVEMENT) {
            $this->visualrepo = di::get('achievement_badges_repository');
        } else if ($persistent->get('type') == mission_model::TYPE_QUEST) {
            $this->visualrepo = di::get('quest_narrator_visuals_repository');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get_context(): context {
        return context::instance_by_id($this->persistent->get('contextid'));
    }

    /**
     * {@inheritDoc}
     */
    public function get_id(): int {
        return (int) $this->persistent->get('id');
    }

    /**
     * {@inheritDoc}
     */
    public function get_state(): int {
        return (int) $this->persistent->get('state');
    }

    /**
     * {@inheritDoc}
     */
    public function get_title(): string {
        return $this->persistent->get('title');
    }

    /**
     * {@inheritDoc}
     */
    public function get_description(): string {
        return (string) $this->persistent->get('description');
    }

    /**
     * {@inheritDoc}
     */
    public function get_feedback(): string {
        return (string) $this->persistent->get('feedback');
    }

    /**
     * {@inheritDoc}
     */
    public function get_instructions(): string {
        return (string) $this->persistent->get('instructions');
    }

    /**
     * {@inheritDoc}
     */
    public function get_objective(int $id): objective {
        foreach ($this->get_objectives() as $objective) {
            if ($objective->get_id() == $id) {
                return $objective;
            }
        }
        throw new moodle_exception('Unknown objective');
    }

    /**
     * {@inheritDoc}
     */
    public function get_objectives(): array {
        if ($this->objectives === null) {
            $fn = $this->objectivescallable;
            $this->set_objectives($fn());
        }
        return $this->objectives;
    }

    /**
     * Get persistent.
     *
     * @return mission_model
     */
    public function get_persistent(): mission_model {
        return $this->persistent;
    }

    /**
     * {@inheritDoc}
     */
    public function get_repeat_count(): int {
        return (int) $this->persistent->get('repeatcount');
    }

    /**
     * {@inheritDoc}
     */
    public function get_secret(): string {
        return $this->persistent->get('secret');
    }

    /**
     * {@inheritDoc}
     */
    public function get_start_mode(): int {
        return (int) $this->persistent->get('startmode');
    }

    /**
     * {@inheritDoc}
     */
    public function get_time_limit(): int {
        return (int) $this->persistent->get('timelimit');
    }

    /**
     * {@inheritDoc}
     */
    public function get_time_modified(): int {
        return (int) $this->persistent->get('timemodified');
    }

    /**
     * {@inheritDoc}
     */
    public function get_visibility(): int {
        return (int) $this->persistent->get('visibility');
    }

    /**
     * {@inheritDoc}
     */
    public function get_visual(): ?visual {
        $visual = $this->persistent->get('visual');
        if ($this instanceof streak) {
            $output = di::get('renderer');
            return new static_visual(
                'streak',
                $output->image_url('streak', 'block_gearup'),
                new \lang_string('streak', 'block_gearup')
            );
        }
        if (!empty($visual) && $this->visualrepo) {
            if ($this->visualrepo instanceof repository_with_context) {
                return $this->visualrepo->get_visual_from_context($visual, $this->get_context());
            }
            return $this->visualrepo->get_visual($visual);
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function get_voice_id(): ?string {
        return $this->persistent->get('voiceid');
    }

    /**
     * Set objectives.
     *
     * @param objective[] $objectives The objectives.
     * @return void
     */
    protected function set_objectives(array $objectives): void {
        uasort($objectives, function (objective $a, objective $b) {
            $aorder = $a instanceof persisted_objective ? $a->get_persistent()->get('sortorder') : $a->get_id();
            $border = $b instanceof persisted_objective ? $b->get_persistent()->get('sortorder') : $b->get_id();
            return [$aorder, $a->get_id()] <=> [$border, $b->get_id()];
        });
        $this->objectives = $objectives;
    }
}
