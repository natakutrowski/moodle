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

namespace block_gearup\form\wizard;

use block_gearup\form\radiogroup;
use block_gearup\form\streak\timing_dynamic_form;
use block_gearup\local\mission\mission;
use block_gearup\local\model\mission as mission_model;
use block_gearup\local\utils\time_utils;
use core\form\persistent;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class streak_timing_form extends persistent {

    protected static $persistentclass = mission_model::class;

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'repeatcount');
        $mform->setConstant('repeatcount', mission::REPEAT_ALWAYS);

        $mform->addElement(radiogroup::register(),
            'timelimit',
            get_string('streaktimeinterval', 'block_gearup'),
            timing_dynamic_form::get_time_options()
        );
        $mform->addHelpButton('timelimit', 'streaktimeinterval', 'block_gearup');

        $this->add_action_buttons(false, get_string('continue'));
    }

    protected function get_default_data() {
        $data = parent::get_default_data();
        $data->timelimit = WEEKSECS;
        return $data;
    }

    public function get_data() {
        $data = parent::get_data();
        if (is_object($data)) {
            $data->repeatcount = (int) $data->repeatcount;
            $data->timelimit = (int) $data->timelimit;
        }
        return $data;
    }

}
