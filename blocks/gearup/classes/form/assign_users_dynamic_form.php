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
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\form;

use block_gearup\di;
use block_gearup\local\mission\mission;
use html_writer;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_users_dynamic_form extends mission_dynamic_form_base {

    protected $paramname = 'missionid';

    public function definition() {
        parent::definition();

        $mform = $this->_form;
        $mission = $this->get_mission();

        $mform->addElement(user_selector::register(), 'userids', get_string('userstorecruit', 'block_gearup'), [
            'multiple' => true,
            'context' => $mission->get_context(),
        ]);
        $mform->addHelpButton('userids', 'userstorecruit', 'block_gearup');

        // Workaround to leave room for the dropdown until MDL-70180 is integrated.
        $mform->addElement('static', 'cd_spaced', '', html_writer::div('', 'gu-h-20'));
    }

    protected function check_access_for_mission(): void {
        if ($this->get_mission()->get_state() !== mission::STATE_ACTIVE) {
            throw new \moodle_exception('invalidmissionstate', 'block_gearup');
        }
    }

    /**
     * Process the form submission.
     *
     * @return mixed
     */
    public function process_dynamic_save() {
        if ($data = $this->get_data()) {
            $userids = $data->userids ?? [];
            $mr = di::get('repository');
            $mo = di::get('mission_operator');
            $mission = $this->get_mission();

            foreach ($userids as $userid) {
                if ($mr->is_assigned_mission($userid, $mission->get_id())) {
                    continue;
                }
                $mo->assign_mission($mission, $userid);
            }
        }
    }

}
