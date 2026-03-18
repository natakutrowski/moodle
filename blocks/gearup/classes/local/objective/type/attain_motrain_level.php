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
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\motrain_level_attained;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\info;
use block_gearup\local\availability\permission_required_info;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\availability\static_info;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_motrain;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attain_motrain_level implements
    has_availability_info,
    has_availability_info_for_context,
    type,
    type_with_state_initialisation {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof motrain_level_attained) {
            return;
        }
        $instance->increment_counter(1);
    }

    public function get_availability_info(): info {
        return new plugin_required_info('block_motrain', 'Motrain', 2023052800, 'v1.8.1');
    }

    public function get_availability_info_for_context(\context $context): info {
        global $USER;
        if (!class_exists('block_motrain\manager')) {
            return new static_info(false);
        }
        return new permission_required_info('block/motrain:manage', $context, $USER->id);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new attain_motrain_level_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typeattainmotrainlevel', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typeattainmotrainleveldesc', 'block_gearup');
    }

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $manager = block_motrain\manager::instance();
        if (!$manager->is_player($instance->get_subject_id())) {
            return;
        }

        $level = $manager->get_level_proxy()->get_level($instance->get_subject_id());
        if (!$level) {
            return;
        }

        $config = $instance->get_objective()->get_type_config();
        $levelrequired = (int) $config->level;

        if ($level->level >= $levelrequired) {
            $instance->increment_counter(1);
        }
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof motrain_level_attained;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {

        if (!$action instanceof motrain_level_attained) {
            return false;
        }

        $config = $instance->get_objective()->get_type_config();
        $levelrequired = (int) $config->level;
        return $action->get_level() >= $levelrequired;
    }
}

/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attain_motrain_level_config_form_extender implements extender {

    protected $manager;

    public function __construct() {
        $this->manager = block_motrain\manager::instance();
    }

    public function definition($mform): array {
        $els = [];

        $mform->removeElement('countneeded', true);

        $els[] = $mform->addElement('hidden', 'countneeded');
        $mform->setType('countneeded', PARAM_INT);
        $mform->setConstant('countneeded', 1);

        $rawlevels = $this->manager->get_metadata_reader()->get_levels();
        $levels = array_reduce($rawlevels, function ($carry, $level) {
            $label = get_string('leveln', 'block_motrain', $level->level);
            $carry[$level->level] = $label;
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
