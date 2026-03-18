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

namespace block_gearup\form;

use block_gearup\di;
use block_gearup\local\operator\mission_operator;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class increment_mission_instance_counter_dynamic_form extends mission_instance_dynamic_form_base {

    protected function definition() {
        parent::definition();

        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        $mform->addElement('text', 'quantity', get_string('incrementby', 'block_gearup'));
        $mform->addRule('quantity', null, 'required', null, 'client');
        $mform->setType('quantity', PARAM_INT);
    }

    protected function check_access_for_mission(): void {
        $missioninst = $this->get_mission_instance();
        $missionhelper = di::get('mission_helper');
        if (!$missionhelper->has_started($missioninst) || $missionhelper->has_completed($missioninst)) {
            throw new \moodle_exception('notfound', 'block_gearup');
        }
    }

    /**
     * Validation.
     *
     * @param stdClass $data Data to validate.
     * @param array $files Array of files.
     * @param array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     */
    public function extra_validation($data, $files, &$errors) {
        $errors = parent::extra_validation($data, $files, $errors);
        if ($data->quantity <= 0) {
            $errors['quantity'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {
            $mo = di::get('mission_operator');
            $mo->increment_counter($this->get_mission_instance(), $data->quantity);
            $mo->evaluate_instance($this->get_mission_instance());
        }
    }
}
