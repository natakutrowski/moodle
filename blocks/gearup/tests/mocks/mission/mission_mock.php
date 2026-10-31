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

use block_gearup\local\mission\mission;
use block_gearup\local\objective\objective;
use block_gearup\local\visual\visual;
use context;
use context_system;

/**
 * Mock.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_mock implements mission {
    /** @var mixed Data. */
    protected $data;

    /**
     * Constructor.
     *
     * @param object $data Data.
     */
    public function __construct(object $data) {
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
    public function get_context(): context {
        return $this->data->context ?? context_system::instance();
    }

    /**
     * {@inheritDoc}
     */
    public function get_state(): int {
        return $this->data->state ?? self::STATE_ACTIVE;
    }

    /**
     * {@inheritDoc}
     */
    public function get_title(): string {
        return $this->data->title ?? 'Mission title';
    }

    /**
     * {@inheritDoc}
     */
    public function get_description(): string {
        return $this->data->description ?? 'Mission description';
    }

    /**
     * {@inheritDoc}
     */
    public function get_feedback(): string {
        return $this->data->feedback ?? 'Mission feedback';
    }

    /**
     * {@inheritDoc}
     */
    public function get_instructions(): string {
        return $this->data->instructions ?? 'Mission instructions';
    }

    /**
     * {@inheritDoc}
     */
    public function get_objective(int $id): objective {
        foreach ($this->get_objectives() as $obj) {
            if ($obj->get_id() === $id) {
                return $obj;
            }
        }
        throw new \coding_exception('notfound');
    }

    /**
     * {@inheritDoc}
     */
    public function get_objectives(): array {
        return $this->data->objectives ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_repeat_count(): int {
        return $this->data->repeatcount ?? self::REPEAT_NEVER;
    }

    /**
     * {@inheritDoc}
     */
    public function get_start_mode(): int {
        return $this->data->start_mode ?? self::START_ALWAYS;
    }

    /**
     * {@inheritDoc}
     */
    public function get_secret(): string {
        return $this->data->secret ?? 'abcdefg';
    }

    /**
     * {@inheritDoc}
     */
    public function get_time_limit(): int {
        return $this->data->timelimit ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    public function get_time_modified(): int {
        return $this->data->timemodified ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    public function get_visibility(): int {
        return $this->data->visibility ?? self::VISIBLE_ALWAYS;
    }

    /**
     * {@inheritDoc}
     */
    public function get_visual(): ?visual {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function get_voice_id(): ?string {
        return $this->data->voiceid ?? null;
    }
}
