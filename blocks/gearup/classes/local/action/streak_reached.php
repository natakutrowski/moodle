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

namespace block_gearup\local\action;

use block_gearup\local\mission\mission_instance;

/**
 * Action.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class streak_reached extends static_action {

    /** @var int The mission instance ID. */
    protected $missioninstid;
    /** @var int The streak. */
    protected $streak;

    public function __construct(mission_instance $missioninst) {
        $mission = $missioninst->get_mission();
        $this->missioninstid = $missioninst->get_id();
        $this->streak = $missioninst->get_counter();
        parent::__construct($missioninst->get_subject_id(), $mission->get_context(), $mission->get_id());
    }

    public function get_mission_id(): int {
        return $this->get_object_id();
    }

    public function get_mission_instance_id(): int {
        return $this->missioninstid;
    }

    public function get_streak(): int {
        return $this->streak;
    }

}
