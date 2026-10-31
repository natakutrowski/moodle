<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\local\observer;

use block_xp\di;
use block_xp\local\observer\default_state_store_observer as xp_default_state_store_observer;
use block_xp\local\observer\points_changed_state_store_observer;
use block_xp\local\xp\state_store;
use context_system;
use local_xp\local\world\global_world;

/**
 * Default state store observer.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_state_store_observer extends xp_default_state_store_observer implements points_changed_state_store_observer {
    /** @var ?bool Whether to check global world. */
    protected $shouldcheckglobalworld;
    /** @var ?global_world The global world. */
    protected $globalworld;

    /**
     * The recipient points changed.
     *
     * @param state_store $store The store.
     * @param int $id The recipient.
     * @param int $beforexp The points before.
     * @param int $afterxp The points after.
     */
    public function points_changed(state_store $store, $id, $beforexp, $afterxp) {
        $pointsamount = $afterxp - $beforexp;

        if ($pointsamount <= 0) {
            return;
        } else if (!$this->should_check_global_world()) {
            return;
        }

        $world = $this->get_global_world();
        if (!$world) {
            return;
        }

        $state = $world->get_store()->get_state($id);
        $beforelevel = $world->get_levels_info()->get_level_from_xp(max(0, $state->get_xp() - $pointsamount));
        $afterlevel = $state->get_level();
        if ($beforelevel->get_level() >= $afterlevel->get_level()) {
            return;
        }

        // Handle each level that the user went up by.
        for ($level = $beforelevel->get_level() + 1; $level <= $afterlevel->get_level(); $level++) {
            \block_xp\event\user_leveledup::create([
                'context' => $world->get_context(),
                'relateduserid' => $id,
                'other' => ['level' => $level],
                ])->trigger();

            if ($world->get_config()->get('enablelevelupnotif')) {
                $service = di::get('world_level_up_notification_service_factory')->get_for_world($world);
                $service->notify($id, $level);
            }
        }
    }

    /**
     * Get the global world.
     *
     * @return ?global_world
     */
    protected function get_global_world() {
        if ($this->globalworld === null) {
            $world = di::get('context_world_factory')->get_world_from_context(context_system::instance());
            $this->globalworld = $world instanceof global_world ? $world : null;
        }
        return $this->globalworld;
    }

    /**
     * Whether the course is contributing to the global world.
     *
     * @return bool
     */
    protected function is_course_contributing_to_global_world() {
        if ($this->context->contextlevel != CONTEXT_COURSE) {
            return false;
        }
        return di::get('db')->record_exists('local_xp_source', [
            'contextid' => context_system::instance()->id,
            'sourcecontextid' => $this->context->id,
        ]);
    }

    /**
     * Should we check the global world?
     *
     * @return bool
     */
    protected function should_check_global_world(): bool {
        if ($this->shouldcheckglobalworld === null) {
            $config = di::get('config');
            $this->shouldcheckglobalworld = $config->get('context') == CONTEXT_COURSE
                && (bool) $config->get('enableglobalworld')
                && $this->is_course_contributing_to_global_world()
                && $this->get_global_world() instanceof global_world
                && (bool) $this->get_global_world()->get_config()->get('enabled');
        }
        return $this->shouldcheckglobalworld;
    }
}
