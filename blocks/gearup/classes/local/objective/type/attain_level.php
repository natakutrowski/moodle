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
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\level_attained;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\info;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\availability\static_info;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_xp\local\course_world;
use block_xp\local\xp\level_with_name;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attain_level implements type, type_with_state_initialisation, has_availability_info, has_availability_info_for_context {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof level_attained) {
            return;
        }

        $config = $instance->get_objective()->get_type_config();
        if ($config->level <= $action->get_level()) {
            $instance->increment_counter(1);
        }
    }

    public function get_availability_info(): info {
        return new plugin_required_info('block_xp', 'Level Up XP', 2022112600, 'v3.13');
    }

    public function get_availability_info_for_context(\context $context): info {
        if (!class_exists('block_xp\di')) {
            return new static_info(false);
        }

        // Always OK, when configured for the whole site.
        $config = \block_xp\di::get('config');
        if ($config->get('context') == CONTEXT_SYSTEM) {
            return new static_info(true);
        }

        // Requires to be in a course.
        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext) {
            return new static_info(false, [new lang_string('requirestobeincourse', 'block_gearup')]);
        }

        return new static_info(true);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        $coursecontext = $mission->get_context()->get_course_context(false);
        $world = \block_xp\di::get('course_world_factory')->get_world($coursecontext ? $coursecontext->instanceid : SITEID);
        return new attain_level_config_form_extender($world);
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typeattainlevel', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typeattainleveldesc', 'block_gearup');
    }

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $coursecontext = $missioninst->get_mission()->get_context()->get_course_context(false);
        $world = \block_xp\di::get('course_world_factory')->get_world($coursecontext ? $coursecontext->instanceid : SITEID);
        $state = $world->get_store()->get_state($instance->get_subject_id());
        if (!$state) {
            return;
        }

        $config = $instance->get_objective()->get_type_config();
        if ($config->level <= $state->get_level()->get_level()) {
            $instance->increment_counter(1);
        }
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof level_attained;
    }

    public function is_action_passing_constraints(action $action, objective_instance $instance,
            mission_instance $missioninst): bool {

        // When Level up! is used for the whole site, we can observe this action anywhere
        // as everything would reference the global instance of Level up!.
        $xpconfig = \block_xp\di::get('config');
        if ($xpconfig->get('context') == CONTEXT_SYSTEM) {
            return true;
        }

        $xpcontextids = $action->get_context()->get_parent_context_ids(true);
        $context = $missioninst->get_mission()->get_context();

        // The context in which we earn the points must be a descendant or equal to the
        // context in which the mission is defined. So that a system mission will see
        // this anywhere. But a mission in a course will only see this within the course.
        if (!in_array($context->id, $xpcontextids)) {
            return false;
        }

        return true;
    }
}

/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attain_level_config_form_extender implements extender {

    public function __construct(course_world $world) {
        $this->world = $world;
    }

    public function definition($mform): array {
        $els = [];

        $mform->removeElement('countneeded', true);

        $els[] = $mform->addElement('hidden', 'countneeded');
        $mform->setType('countneeded', PARAM_INT);
        $mform->setConstant('countneeded', 1);

        $levels = array_reduce($this->world->get_levels_info()->get_levels(), function($carry, $level) {
            $label = get_string('levelx', 'block_xp', $level->get_level());
            if ($level instanceof level_with_name) {
                $name = $level->get_name();
                if (!empty($name)) {
                    $label = $level->get_level() . ' - ' . $name;
                }
            }
            $carry[$level->get_level()] = $label;
            return $carry;
        }, []);
        $els[] = $mform->addElement('select', 'cd_level', get_string('leveltoattain', 'block_gearup'), $levels);

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
    }

}
