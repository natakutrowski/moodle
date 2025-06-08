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
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\form\wizard;

use block_gearup\form\challenge_timing_dynamic_form;
use block_gearup\form\radiogroup;
use block_gearup\local\mission\mission;
use block_gearup\local\model\mission as mission_model;
use core\form\persistent;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class challenge_timing_form extends persistent {

    protected static $persistentclass = mission_model::class;

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('select', 'timelimit', get_string('timetocomplete', 'block_gearup'),
            challenge_timing_dynamic_form::get_time_options());

        $mform->addElement(radiogroup::register(), 'repeatcount', get_string('repeatchallenge', 'block_gearup'), [
            [
                'value' => mission::REPEAT_ALWAYS,
                'label' => get_string('repeatalways', 'block_gearup'),
                'description' => get_string('repeatalwayschallengedesc', 'block_gearup'),
            ],
            [
                'value' => mission::REPEAT_NEVER,
                'label' => get_string('repeatnever', 'block_gearup'),
                'description' => get_string('repeatneverchallengedesc', 'block_gearup'),
            ],
        ]);
        $mform->hideIf('repeatcount', 'timelimit', 'eq', '0');

        $this->add_action_buttons(false, get_string('continue'));
    }

    protected function get_default_data() {
        $data = parent::get_default_data();

        $data->timelimit = WEEKSECS;
        $data->repeatcount = mission::REPEAT_ALWAYS;

        return $data;
    }

    public function get_data() {
        $data = parent::get_data();
        if (is_object($data)) {
            if (!$data->timelimit) {
                $data->repeatcount = mission::REPEAT_NEVER;
            }
            $data->repeatcount = (int) $data->repeatcount;
            $data->timelimit = (int) $data->timelimit;
        }
        return $data;
    }

}
