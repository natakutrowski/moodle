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

namespace block_gearup\form\streak;

use block_gearup\di;
use block_gearup\form\mission_dynamic_form_base;
use block_gearup\form\radiogroup;
use block_gearup\local\mission\streak;
use block_gearup\local\utils\time_utils;
use moodle_exception;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timing_dynamic_form extends mission_dynamic_form_base {

    protected function definition() {
        parent::definition();
        $mform = $this->_form;

        $mform->addElement(radiogroup::register(),
            'timelimit',
            get_string('streaktimeinterval', 'block_gearup'),
            self::get_time_options()
        );
        $mform->addHelpButton('timelimit', 'streaktimeinterval', 'block_gearup');
    }

    protected function check_access_for_mission(): void {
        $mh = di::get('mission_helper');
        $mission = $this->get_mission();
        if (!$mh->is_a_streak($mission)) {
            throw new moodle_exception('nopermissiontoeditthis', 'block_gearup');
        }
    }

    public function get_data() {
        $data = parent::get_data();
        if (is_object($data)) {
            $data->repeatcount = streak::REPEAT_ALWAYS;
            $data->timelimit = (int) $data->timelimit;
        }
        return $data;
    }

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {
            unset($data->id);
            $model = $this->get_persistent();
            $model->from_record($data);
            $model->update();
        }
    }

    /**
     * Get the time options.
     *
     * @return array
     */
    public static function get_time_options() {
        $daily = DAYSECS;
        $dailyweekday = time_utils::DAILY_WEEKDAY;
        $weekly = WEEKSECS;
        $fortnightly = 2 * WEEKSECS;
        $monthly = DAYSECS * 30;
        return [
            [
                'value' => $daily,
                'label' => get_string('daily', 'block_gearup'),
                'description' => get_string('streakdailydesc', 'block_gearup'),
            ],
            [
                'value' => $dailyweekday,
                'label' => get_string('dailyweekdays', 'block_gearup'),
                'description' => get_string('streakdailyweekdaydesc', 'block_gearup'),
            ],
            [
                'value' => $weekly,
                'label' => get_string('weekly', 'block_gearup'),
                'description' => get_string('streakweeklydesc', 'block_gearup'),
            ],
            [
                'value' => $fortnightly,
                'label' => get_string('fortnightly', 'block_gearup'),
                'description' => get_string('streakfortnightlydesc', 'block_gearup'),
            ],
            [
                'value' => $monthly,
                'label' => get_string('monthly', 'block_gearup'),
                'description' => get_string('streakmonthlydesc', 'block_gearup'),
            ],
        ];
    }

}
