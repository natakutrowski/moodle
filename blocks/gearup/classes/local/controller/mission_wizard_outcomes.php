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

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\controller\utils\mission_route_base;
use html_writer;
use single_button;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_wizard_outcomes extends mission_route_base {
    use utils\mission_wizard_trait;

    protected $currentstep = 'outcomes';

    protected function get_subpage_html_head_title() {
        return get_string('outcomes', 'block_gearup');
    }

    protected function get_wizard_title() {
        if ($this->is_challenge()) {
            return get_string('whathappenswhencompletedchallenge', 'block_gearup');
        }
        return get_string('whathappenswhencompletedquest', 'block_gearup');
    }

    protected function wizard_content() {
        $output = $this->get_renderer();

        $data = di::get('exporter_factory')->get_mission_exporter($this->mission)->export($output);
        echo $output->render_from_template('block_gearup/quest_outcomes_list', $data);

        $hasoutcomes = (bool) di::get('repository')->has_outcomes($this->mission->get_id());
        $nexturl = $this->get_wizard_next_url($this->mission)->get_compatible_url();

        if ($hasoutcomes) {
            $button = $output->single_button_primary($nexturl, get_string('continue', 'core'));
        } else {
            $button = $output->render(new single_button($nexturl, get_string('skip', 'block_gearup'), 'get'));
        }

        echo html_writer::div($button, 'gu-mt-4');
    }

}
