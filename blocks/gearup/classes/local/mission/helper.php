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
 * Helper.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\mission;

// TODO This is to access the TYPE_ constants, but we should not be doing it like this.
use block_gearup\local\model\mission as mission_model;

/**
 * Helper.
 *
 * /!\ Only read-only methods here! Any operation should be done via the operator.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Calculate the completion ratio.
     *
     * /!\ Do not use directly, this is placed here temporarily. You should instead use the
     * object returned by the DI container for 'completion_ratio_calculator'.
     *
     * @param mission_instance $missioninst
     */
    public function calculate_completion_ratio(mission_instance $missioninst) {
        $objinsts = $missioninst->get_objective_instances();
        $current = array_sum(array_map(function($objinst) {
            if ($objinst->is_completed()) {
                return 1;
            }
            return min(1, $objinst->get_counter() / $objinst->get_objective()->get_count_needed());
        }, $objinsts));
        $total = count($objinsts);

        return $total > 0 ? max(0, min($total, $current)) / $total : 0;
    }

    /**
     * Whether the mission has completed.
     *
     * This includes when the state is after the completed state.
     *
     * @param mission_instance $missioninst The instance.
     * @return bool
     */
    public function has_completed(mission_instance $missioninst): bool {
        $state = $missioninst->get_state();
        return in_array($state, [
            $missioninst::STATE_COMPLETED,
            $missioninst::STATE_ENDED,
        ]);
    }

    /**
     * Whether the mission has started.
     *
     * This includes when the state is after the started state.
     *
     * @param mission_instance $missioninst The instance.
     * @return bool
     */
    public function has_started(mission_instance $missioninst): bool {
        $state = $missioninst->get_state();
        return in_array($state, [
            $missioninst::STATE_STARTED,
            $missioninst::STATE_COMPLETED,
            $missioninst::STATE_ENDED,
        ]);
    }

    /**
     * Return self, or the instance's mission.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return mission
     */
    public function get_mission($missionorinstance): mission {
        $mission = $missionorinstance;
        if ($mission instanceof mission_instance) {
            $mission = $mission->get_mission();
        }
        return $mission;
    }

    /**
     * Return the mission type as an integer.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return int
     */
    public function get_type($missionorinstance): int {
        $mission = $missionorinstance;
        if ($mission instanceof mission_instance) {
            $mission = $mission->get_mission();
        }

        // The values should be stored elsewhere really!
        if ($this->is_an_achievement($mission)) {
            return 0;
        } else if ($this->is_a_quest($mission)) {
            return 1;
        } else if ($this->is_a_challenge($mission)) {
            return 2;
        } else if ($this->is_a_streak($mission)) {
            return mission_model::TYPE_STREAK;
        }

        throw new \coding_exception('Unknown mission type');
    }

    /**
     * Whether the mission is archive.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_active($missionorinstance): bool {
        $mission = $this->get_mission($missionorinstance);
        return $mission->get_state() === $mission::STATE_ACTIVE;
    }

    /**
     * Whether the mission can be archived.
     *
     * This should only validate that it is in a state that allows for its archival.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_archivable($missionorinstance): bool {
        $mission = $this->get_mission($missionorinstance);
        return $mission->get_state() === mission::STATE_ACTIVE;
    }

    /**
     * Whether the mission is archived.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_archived($missionorinstance): bool {
        $mission = $this->get_mission($missionorinstance);
        return $mission->get_state() === $mission::STATE_ARCHIVED;
    }

    /**
     * Whether the mission is compulsory.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_compulsory($missionorinstance): bool {
        if (!$this->is_a_quest($missionorinstance)) {
            return true;
        }
        $mission = $this->get_mission($missionorinstance);
        return $mission->get_visibility() === $mission::VISIBLE_ALWAYS
            && $mission->get_start_mode() === $mission::START_ALWAYS;
    }

    /**
     * Whether the mission is discoverable.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_discoverable($missionorinstance): bool {
        if (!$this->is_a_quest($missionorinstance)) {
            return false;
        }
        $mission = $this->get_mission($missionorinstance);
        return $mission->get_visibility() === $mission::VISIBLE_SECRET
            && $mission->get_start_mode() === $mission::START_OPTIN;
    }

    /**
     * Whether the mission is finishable.
     *
     * A mission is finishable when it does not automatically end.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_finishable($missionorinstance): bool {
        if ($this->is_an_achievement($missionorinstance)) {
            return false;
        }
        return true;
    }

    /**
     * Whether the mission is optional.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_optional($missionorinstance): bool {
        if (!$this->is_a_quest($missionorinstance)) {
            return false;
        }
        $mission = $this->get_mission($missionorinstance);
        return $mission->get_visibility() === $mission::VISIBLE_ALWAYS
            && $mission->get_start_mode() === $mission::START_OPTIN;
    }

    /**
     * Whether the mission is repeatable.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_repeating($missionorinstance): bool {
        if ($this->is_a_streak($missionorinstance)) {
            return true;
        } else if (!$this->is_a_challenge($missionorinstance)) {
            return false;
        }
        $mission = $this->get_mission($missionorinstance);
        return $mission->get_repeat_count() !== $mission::REPEAT_NEVER;
    }

    /**
     * Whether the mission, or instance, is an achievement.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_an_achievement($missionorinstance): bool {
        $mission = $this->get_mission($missionorinstance);
        return $mission instanceof achievement;
    }

    /**
     * Whether the mission, or instance, is a challenge.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_a_challenge($missionorinstance): bool {
        $mission = $this->get_mission($missionorinstance);
        return $mission instanceof challenge;
    }

    /**
     * Whether the mission, or instance, is a quest.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_a_quest($missionorinstance): bool {
        $mission = $this->get_mission($missionorinstance);
        return $mission instanceof quest;
    }

    /**
     * Whether the mission, or instance, is a streak.
     *
     * @param mission|mission_instance $missionorinstance The mission, or instance.
     * @return bool
     */
    public function is_a_streak($missionorinstance): bool {
        $mission = $this->get_mission($missionorinstance);
        return $mission instanceof streak;
    }

    /**
     * Whether the mission is in the wizard.
     *
     * @param mission $mission The mission.
     * @return bool
     */
    public function is_in_wizard(mission $mission): bool {
        $state = $mission->get_state();
        return $state === $mission::STATE_WIZARD;
    }

    /**
     * Whether the mission is assigned.
     *
     * This only returns true when the state is strictly assigned.
     *
     * There is no `has_been_assigned` version of this because assigned
     * is the starting state and thus it would always be assigned,
     * or in later state.
     *
     * @param mission_instance $missioninst The instance.
     * @return bool
     */
    public function is_assigned(mission_instance $missioninst): bool {
        $state = $missioninst->get_state();
        return $state === $missioninst::STATE_ASSIGNED;
    }

    /**
     * Whether the mission is completed.
     *
     * This only returns true when the state is strictly completed.
     *
     * @param mission_instance $missioninst The instance.
     * @return bool
     */
    public function is_completed(mission_instance $missioninst): bool {
        $state = $missioninst->get_state();
        return $state === $missioninst::STATE_COMPLETED;
    }

    /**
     * Whether the mission is ended.
     *
     * This only returns true when the state is strictly ended.
     *
     * There is no `has_ended` version of this because ended
     * is the last state and thus it would always be ended, or
     * in an earlier state.
     *
     * @param mission_instance $missioninst The instance.
     * @return bool
     */
    public function is_ended(mission_instance $missioninst): bool {
        $state = $missioninst->get_state();
        return $state === $missioninst::STATE_ENDED;
    }

    /**
     * Whether the mission is incomplete.
     *
     * This only returns true when the mission has not been fully complete, even if completed.
     *
     * @param mission_instance $missioninst The instance.
     * @return bool
     */
    public function is_incomplete(mission_instance $missioninst): bool {
        return !($this->has_completed($missioninst) && $missioninst->get_completion_ratio() >= 1);
    }

    /**
     * Whether the mission is assigned.
     *
     * This only returns true when the state is strictly assigned.
     *
     * @param mission_instance $missioninst The instance.
     * @return bool
     */
    public function is_started(mission_instance $missioninst): bool {
        $state = $missioninst->get_state();
        return $state === $missioninst::STATE_STARTED;
    }

}
