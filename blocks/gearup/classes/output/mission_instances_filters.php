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

namespace block_gearup\output;

use block_gearup\di;
use block_gearup\local\mission\mission;
use block_gearup\local\utils\render_utils;
use block_gearup\local\utils\user_utils;
use core\output\named_templatable;
use core_table\local\filter\filterset;
use renderable;
use renderer_base;
use templatable;

/**
 * Renderable.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_instances_filters implements named_templatable, renderable, templatable {

    /** @var mission The mission. */
    public $mission;
    /** @var filterset The filterset. */
    public $filterset;
    /** @var array The form params. */
    public $formparams;
    /** @var bool With the state. */
    public $withstatus = true;

    /**
     * Constructor.
     *
     * @param mission $mission The mission.
     * @param array $formparams The form params.
     * @param filterset|null $filterset The filterset.
     */
    public function __construct(mission $mission, array $formparams, filterset $filterset) {
        $this->mission = $mission;
        $this->formparams = $formparams;
        $this->filterset = $filterset;
    }

    public function export_for_template(renderer_base $output) {
        global $USER;

        $status = null;
        if ($this->filterset->has_filter('status')) {
            $status = $this->filterset->get_filter('status')->current();
        }

        $term = null;
        if ($this->filterset->has_filter('subject:term')) {
            $term = $this->filterset->get_filter('subject:term')->current();
        } else if ($this->filterset->has_filter('term')) {
            $term = $this->filterset->get_filter('term')->current();
        }

        $groupid = null;
        if ($this->filterset->has_filter('groupid')) {
            $groupid = $this->filterset->get_filter('groupid')->current();
        }

        $formfields = [];
        foreach ($this->formparams as $key => $value) {
            if ($this->filterset->has_filter($key)) {
                continue;
            }
            $formfields[] = ['name' => $key, 'value' => $value];
        }

        $missionhelper = di::get('mission_helper');
        $selectoptions = user_utils::get_group_select_options($this->mission->get_context(), $USER->id);
        return [
            'withstatus' => $this->withstatus,
            'ischallenge' => $missionhelper->is_a_challenge($this->mission),
            'isachievement' => $missionhelper->is_a_challenge($this->mission),
            'iscompulsory' => $missionhelper->is_compulsory($this->mission),
            'isfinishable' => $missionhelper->is_finishable($this->mission),
            'isquest' => $missionhelper->is_a_quest($this->mission),
            'isstreak' => $missionhelper->is_a_streak($this->mission),
            'term' => $term,
            'statusis_' . ($status ?: 'any') => true,
            'hiddenfields' => $formfields,

            'groupid' => $groupid,
            'withgroup' => !empty($selectoptions),
            'groupoptions' => render_utils::flatten_select_options($selectoptions, $groupid),
        ];
    }

    public function get_template_name(renderer_base $renderer): string {
        return 'block_gearup/table/mission_instances_filters';
    }

    public function with_state(bool $withstate): self {
        $this->withstatus = $withstate;
        return $this;
    }

}
