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

use block_gearup\form\wizard\quest_assignment_behaviour_form;
use block_gearup\local\controller\utils\mission_route_base;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_wizard_assignbehaviour extends mission_route_base {
    use utils\mission_wizard_trait;

    protected $currentstep = 'assignbehaviour';

    protected $form;

    protected function get_form() {
        if (!$this->form) {
            $this->form = new quest_assignment_behaviour_form($this->pageurl->out(false), [
                'persistent' => $this->mission->get_persistent(),
            ]);
        }
        return $this->form;
    }

    protected function get_subpage_html_head_title() {
        return get_string('assignmentbehaviour', 'block_gearup');
    }

    protected function get_wizard_title() {
        return get_string('isquestcompulsoryoroptional', 'block_gearup');
    }

    protected function pre_wizard_content() {
        $form = $this->get_form();
        if ($data = $form->get_data()) {
            $mission = $this->mission->get_persistent();
            $mission->from_record($data);
            $mission->update();
            $this->redirect($this->get_wizard_next_url($mission));

        } else if ($form->is_cancelled()) {
            $this->redirect($this->urlresolver->reverse('missions'));
        }
    }

    protected function wizard_content() {
        $form = $this->get_form();
        $form->set_display_vertical();
        $form->display();
    }

}
