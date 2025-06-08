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
 * Watch video.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\video_watched;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\info;
use block_gearup\local\availability\info_stack;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\availability\static_info;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Watch video.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class watch_video implements type, has_availability_info {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof video_watched) {
            return;
        }
        $state = $this->get_normalized_state($instance);
        $instance->increment_counter(1);
        $state->vids[] = $action->get_video_id();
        $instance->set_type_state($state);
    }

    public function get_availability_info(): info {
        $stack = [new plugin_required_info('media_videojs', 'VieoJS')];
        if (get_config('media_videojs', 'useflash')) {
            $stack[] = new static_info(false, [new lang_string('infoincompatiblewithsetting', 'block_gearup',
                'media_videojs/useflash')]);
        }
        return new info_stack($stack);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new watch_video_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typewatchvideo', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typewatchvideodesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof video_watched;
    }

    public function is_action_passing_constraints(action $action, objective_instance $instance,
            mission_instance $missioninst): bool {
        if (!$action instanceof video_watched) {
            return false;
        }

        $state = $this->get_normalized_state($instance);
        if (in_array($action->get_video_id(), $state->vids)) {
            return false;
        }

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
        return $state ?? (object) ['vids' => []];
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
class watch_video_config_form_extender implements extender {

    public function definition($mform): array {
        $els = [];
        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
    }

}
