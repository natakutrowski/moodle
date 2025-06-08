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
use block_gearup\local\controller\utils\mission_route_base;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_assign extends mission_route_base {

    protected $missionnavname = 'mission_assign';

    protected function get_subpage_html_head_title() {
        return get_string('recruitverb', 'block_gearup');
    }

    protected function content() {
        $mission = $this->mission;
        $output = $this->get_renderer();
        $mr = di::get('mission_helper');
        $missionexporter = di::get('exporter_factory')->get_mission_exporter($mission);

        $this->page_mission_header();
        $this->page_mission_navigation();

        if ($mr->is_a_quest($mission)) {
            echo $output->render_from_template('block_gearup/quest_assign', $missionexporter->export($output));
        } else if ($mr->is_an_achievement($mission)) {
            echo $output->render_from_template('block_gearup/achievement_assign', $missionexporter->export($output));
        } else if ($mr->is_a_challenge($mission)) {
            echo $output->render_from_template('block_gearup/challenge_assign', $missionexporter->export($output));
        } else if ($mr->is_a_streak($mission)) {
            echo $output->render_from_template('block_gearup/streak/assign', $missionexporter->export($output));
        }
    }

}
