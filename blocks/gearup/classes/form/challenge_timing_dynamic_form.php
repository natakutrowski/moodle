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

namespace block_gearup\form;

use block_gearup\local\mission\mission;
use moodle_exception;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class challenge_timing_dynamic_form extends mission_dynamic_form_base {

    protected function definition() {
        parent::definition();
        $mform = $this->_form;

        $mform->addElement('select',
            'timelimit',
            get_string('timetocomplete', 'block_gearup'),
            self::get_time_options()
        );

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
    }

    protected function check_access_for_mission(): void {
        $mission = $this->get_mission();
        if (!$mission->get_id()) {
            throw new moodle_exception('nopermissiontoeditthis', 'block_gearup');
        }
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

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {
            unset($data->id);

            // TODO Move all of this to an operator?
            $model = $this->get_persistent();
            $model->from_record($data);
            $model->update();
        }
    }

    /**
     * Get the time options.
     */
    public static function get_time_options() {
        $day = DAYSECS;
        $week = WEEKSECS;
        $fornight = WEEKSECS * 2;
        $month = DAYSECS * 30;
        return [
            0 => get_string('nolimit', 'block_gearup'),
            $day => get_string('numday', 'core', 1),
            $week => get_string('numweek', 'core', 1),
            $fornight => get_string('numweeks', 'core', 2),
            $month => get_string('nummonth', 'core', 1),
        ];
    }
}
