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
use moodle_exception;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quest_feedback_dynamic_form extends mission_dynamic_form_base {

    protected function definition() {
        parent::definition();
        $mform = $this->_form;

        $mform->addElement('textarea', 'feedback', get_string('questfeedback', 'block_gearup'), ['rows' => 5]);
        $mform->addHelpButton('feedback', 'storylinefeedback', 'block_gearup');
        $mform->addRule('feedback', null, 'required', null, 'client');
    }

    protected function check_access_for_mission(): void {
        $mh = di::get('mission_helper');
        $mission = $this->get_mission();
        if (!$mission->get_id()) {
            throw new moodle_exception('nopermissiontoeditthis', 'block_gearup');
        } else if (!$mh->is_a_quest($mission)) {
            throw new moodle_exception('nopermissiontoeditthis', 'block_gearup');
        }
    }

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {
            unset($data->id);

            // TODO Move all of this to an operator?
            $model = $this->get_persistent();
            $model->from_record($data);
            $model->update();
        }
    }
}
