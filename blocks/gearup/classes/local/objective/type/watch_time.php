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
 * Watch time.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\time_watched;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\info;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\form\extender;
use block_gearup\local\form\extender_with_default_data;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use lang_string;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Watch time.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class watch_time implements has_availability_info, type {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof time_watched) {
            return;
        }

        $config = $instance->get_objective()->get_type_config();
        $state = $this->get_normalized_state($instance);

        $unit = $config->unit ?? 60;
        if (!in_array($unit, [60, 3600])) {
            $unit = 60;
        }

        $incrby = 0;
        $buffer = $state->buffer;

        // Buffer the duration if too small. Note that this is not atomic, and thus
        // two operations could negate a buffer increase, or double the increase.
        $totalbuffer = $buffer + $action->get_duration();
        if ($totalbuffer >= $unit) {
            $incrby = floor($totalbuffer / $unit);
            $buffer = $totalbuffer - $incrby * $unit;
        } else {
            $buffer = $totalbuffer;
        }

        // Increment the counter.
        if ($incrby > 0) {
            $instance->increment_counter($incrby);
        }

        // Save the watch buffer.
        $state->buffer = $buffer;
        $instance->set_type_state($state);
    }

    public function get_availability_info(): info {
        return new plugin_required_info('media_videojs', 'VideoJS');
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new watch_time_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typewatchtime', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typewatchtimedesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof time_watched;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {
        return true;
    }

    /**
     * Get the state.
     *
     * @param objective_instance $instance The instance.
     * @return object
     */
    protected function get_normalized_state(objective_instance $instance) {
        $state = $instance->get_type_state();
        return $state ?? (object) ['buffer' => 0];
    }

}

/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class watch_time_config_form_extender implements extender, extender_with_default_data {

    public function definition($mform): array {
        $els = [];

        $mform->removeElement('countneeded');

        $els[] = $mform->addElement('duration',
            'countneeded',
            get_string('howmuchtime', 'block_gearup'),
            ['defaultunit' => HOURSECS, 'units' => [MINSECS, HOURSECS]]
        );

        return $els;
    }

    public function get_data($data) {
        // Save in minutes.
        $data->countneeded = floor($data->countneeded / 60);
        return $data;
    }

    public function get_default_data($data) {
        $data->countneeded = $data->countneeded * 60;
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];
        if ($data->countneeded < 60) {
            $errors['countneeded'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
