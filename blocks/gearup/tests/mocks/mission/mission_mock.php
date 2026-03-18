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

    protected $data;

    public function __construct(object $data) {
        $this->data = $data;
    }

    public function get_id(): int {
        return $this->data->id ?? 0;
    }

    public function get_context(): context {
        return $this->data->context ?? context_system::instance();
    }

    public function get_state(): int {
        return $this->data->state ?? self::STATE_ACTIVE;
    }

    public function get_title(): string {
        return $this->data->title ?? 'Mission title';
    }

    public function get_description(): string {
        return $this->data->description ?? 'Mission description';
    }

    public function get_feedback(): string {
        return $this->data->feedback ?? 'Mission feedback';
    }

    public function get_instructions(): string {
        return $this->data->instructions ?? 'Mission instructions';
    }

    public function get_objective(int $id): objective {
        foreach ($this->get_objectives() as $obj) {
            if ($obj->get_id() === $id) {
                return $obj;
            }
        }
        throw new \coding_exception('notfound');
    }

    public function get_objectives(): array {
        return $this->data->objectives ?? [];
    }

    public function get_repeat_count(): int {
        return $this->data->repeatcount ?? self::REPEAT_NEVER;
    }

    public function get_start_mode(): int {
        return $this->data->start_mode ?? self::START_ALWAYS;
    }

    public function get_secret(): string {
        return $this->data->secret ?? 'abcdefg';
    }

    public function get_time_limit(): int {
        return $this->data->timelimit ?? 0;
    }

    public function get_time_modified(): int {
        return $this->data->timemodified ?? 0;
    }

    public function get_visibility(): int {
        return $this->data->visibility ?? self::VISIBLE_ALWAYS;
    }

    public function get_visual(): ?visual {
        return null;
    }

    public function get_voice_id(): ?string {
        return $this->data->voiceid ?? null;
    }

}
