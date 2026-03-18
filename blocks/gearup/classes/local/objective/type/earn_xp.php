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
 * Earn XP.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\xp_gained;
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
use lang_string;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Earn XP.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class earn_xp implements has_availability_info, has_availability_info_for_context, type, type_with_state_initialisation {

    const MODE_START_AT_ZERO = 0;
    const MODE_START_AT_CURRENT = 1;

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof xp_gained) {
            return;
        }
        $instance->increment_counter($action->get_xp());
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

    public function get_config_form_extender(mission $mission, ?objective $objective): extender {
        return new earn_xp_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typeearnxp', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typeearnxpdesc', 'block_gearup');
    }

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $coursecontext = $missioninst->get_mission()->get_context()->get_course_context(false);
        $world = \block_xp\di::get('course_world_factory')->get_world($coursecontext ? $coursecontext->instanceid : SITEID);
        $state = $world->get_store()->get_state($instance->get_subject_id());
        if (!$state) {
            return;
        }

        $config = $instance->get_objective()->get_type_config();
        if ($config->mode == static::MODE_START_AT_CURRENT) {
            $instance->increment_counter($state->get_xp());
        }
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof xp_gained;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {

        // When Level up! is used for the whole site, we can collect from anywhere
        // as everything would reference the global store of points.
        $xpconfig = \block_xp\di::get('config');
        if ($xpconfig->get('context') == CONTEXT_SYSTEM) {
            return true;
        }

        $xpcontextids = $action->get_context()->get_parent_context_ids(true);
        $context = $missioninst->get_mission()->get_context();

        // The context in which we earn the points must be a descendant or equal to the
        // context in which the mission is defined. So that a system mission will see
        // points earned anywhere. But a mission in a course will only see xp earned
        // within the course.
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
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class earn_xp_config_form_extender implements extender {

    public function definition($mform): array {
        $els = [];

        // TODO State that changing cd_mode will not affect the counter of existing instances, or
        // lock the setting so that the user cannot change it when there are instances.
        $els[] = $mform->addElement('advcheckbox', 'cd_mode', get_string('includepointscurrentlyheld', 'block_gearup'));

        return $els;
    }

    public function get_data($data) {
        if (!empty($data->cd_mode)) {
            $data->cd_mode = earn_xp::MODE_START_AT_CURRENT;
        } else {
            $data->cd_mode = earn_xp::MODE_START_AT_ZERO;
        }
        return $data;
    }

    public function validation($data, $files) {
    }

}
