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
use block_gearup\local\mission\mission_instance;
use block_gearup\local\routing\url;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_quests extends route_base {

    protected $requiremanage = false;

    protected function define_optional_params() {
        return [
            ['returnurl', false, PARAM_LOCALURL],
            ['mode', 0, PARAM_INT],
        ];
    }

    protected function get_page_html_head_title() {
        return get_string('quests', 'block_gearup');
    }

    protected function content() {
        global $USER;

        $mr = di::get('repository');
        $output = $this->get_renderer();
        $mode = $this->get_param('mode');

        $context = $this->context;
        $ongoingquests = $mr->get_quest_instances_by_subject_id($USER->id,
            [mission_instance::STATE_STARTED, mission_instance::STATE_COMPLETED], $context,
            ['mi.timecompleted DESC', 'mi.completionratio DESC']);
        $finishedquests = $mr->get_quest_instances_by_subject_id(
            $USER->id,
            mission_instance::STATE_ENDED,
            $context,
            ['mi.timeended DESC']
        );
        $instances = !$mode ? $ongoingquests : $finishedquests;

        $paramurl = $this->get_param('returnurl');
        $returnurl = new \moodle_url($paramurl ?: '/');
        $returnstr = get_string('back', 'core');
        if (empty($paramurl)) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $returnstr = get_string('returntocourse', 'block_gearup');
                $returnurl = new \moodle_url('/course/view.php', ['id' => $context->instanceid]);
            }
        }

        $exporterfactory = di::get('exporter_factory');
        $tplcontext = [
            'returnstr' => $returnstr,
            'returnurl' => $returnurl->out(false),

            'instances' => array_values(array_map(function($missioninst) use ($output, $exporterfactory) {
                return $exporterfactory->get_mission_instance_exporter($missioninst)->export($output);
            }, $instances)),

            'isongoingmode' => !$mode,
            'isfinishedmode' => (bool) $mode,

            'hasinstances' => !empty($instances),
            'hasany' => !empty($ongoingquests) || !empty($finishedquests),

            'nav' => $this->render_nav($output, !$mode ? 'ongoing' : 'finished'),
        ];

        echo $output->render_from_template('block_gearup/my_quests', $tplcontext);
    }

    protected function render_nav($output, $page) {
        $ongoingurl = new url($this->pageurl);
        $ongoingurl->param('mode', 0);
        $finishedurl = new url($this->pageurl);
        $finishedurl->param('mode', 1);
        $tabs = array_map(
            function($link) {
                return new \tabobject($link['id'], $link['url'], $link['text'], clean_param($link['text'], PARAM_NOTAGS));
            }, [
                [
                    'id' => 'ongoing',
                    'url' => $ongoingurl,
                    'text' => get_string('ongoingquests', 'block_gearup'),
                ],
                [
                    'id' => 'finished',
                    'url' => $finishedurl,
                    'text' => get_string('questscompleted', 'block_gearup'),
                ],
            ]
        );

        return $output->tabtree($tabs, $page);
    }

}
