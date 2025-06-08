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
use html_writer;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_wizard_objectives extends mission_route_base {

    use utils\mission_wizard_trait;
    protected $currentstep = 'objectives';

    protected function get_subpage_html_head_title() {
        return get_string('objectives', 'block_gearup');
    }

    protected function get_wizard_title() {
        if ($this->is_achievement()) {
            return get_string('whatareobjectivesofachievement', 'block_gearup');
        } else if ($this->is_challenge()) {
            return get_string('whatareobjectivesofchallenge', 'block_gearup');
        } else if ($this->is_streak()) {
            return get_string('whatareobjectivesofstreak', 'block_gearup');
        } else if ($this->is_quest()) {
            return get_string('whatareobjectivesofquest', 'block_gearup');
        }
        throw new \coding_exception('Unknown mission type');
    }

    protected function wizard_content() {
        $output = $this->get_renderer();

        $data = di::get('exporter_factory')->get_mission_exporter($this->mission)->export($output);
        echo $output->render_from_template('block_gearup/quest_objectives_list', $data);

        if ($this->mission->get_objectives()) {
            $nexturl = $this->get_wizard_next_url($this->mission);
            $button = $output->single_button_primary($nexturl->get_compatible_url(), get_string('continue', 'core'));
            echo html_writer::div($button, 'gu-mt-6');
        }
    }

}
