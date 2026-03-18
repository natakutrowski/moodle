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
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\controller\utils\route_base;
use block_gearup\local\exporter\visual_exporter;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\utils\human_utils;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_achievements extends route_base {

    protected $requiremanage = false;

    protected function define_optional_params() {
        return [
            ['returnurl', false, PARAM_LOCALURL],
        ];
    }

    protected function get_page_html_head_title() {
        return get_string('achievements', 'block_gearup');
    }

    protected function content() {
        global $USER;

        $mr = di::get('repository');
        $mh = di::get('mission_helper');
        $output = $this->get_renderer();

        $context = $this->context;
        $achievements = $mr->get_achievement_instances_by_subject_id(
            $USER->id,
            null,
            $context,
            ['m.title ASC']
        );
        $ncompleted = $mr->count_achievement_instances_by_subject_id(
            $USER->id,
            mission_instance::STATE_ENDED,
            $context,
        );

        $total = count($achievements);
        $ratio = $total ? $ncompleted / $total : 0;

        $paramurl = $this->get_param('returnurl');
        $returnurl = new \moodle_url($paramurl ?: '/');
        $returnstr = get_string('back', 'core');
        if (empty($paramurl)) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $returnstr = get_string('returntocourse', 'block_gearup');
                $returnurl = new \moodle_url('/course/view.php', ['id' => $context->instanceid]);
            }
        }

        $tplcontext = [
            'returnstr' => $returnstr,
            'returnurl' => $returnurl->out(false),

            'instances' => array_values(array_map(function ($missioninst) use ($output, $mh) {
                $mission = $mh->get_mission($missioninst);
                $visual = $mission->get_visual();
                return [
                    'id' => $missioninst->get_id(),
                    'hascompleted' => $mh->has_completed($missioninst),
                    'mission' => [
                        'title' => $mission->get_title(),
                        'visual' => $visual ? (new visual_exporter($visual))->export($output) : null,
                    ],
                ];
            }, $achievements)),
            'hasinstances' => !empty($achievements),

            'total' => count($achievements),
            'totalcompleted' => $ncompleted,
            'totalcompletionratiopc' => human_utils::percentage($ratio),
        ];

        echo $output->render_from_template('block_gearup/my_achievements', $tplcontext);
    }

}
