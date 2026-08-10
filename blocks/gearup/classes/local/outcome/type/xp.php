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
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\outcome\type;

use block_gearup\di;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\has_availability_info_for_user;
use block_gearup\local\availability\info;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\availability\static_info;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\outcome\outcome;
use block_gearup\local\xp\achievement_unlocked_reason;
use block_gearup\local\xp\challenge_completed_reason;
use block_gearup\local\xp\quest_completed_reason;
use block_xp\local\xp\state_store_with_reason;
use context_system;
use lang_string;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xp implements has_availability_info, has_availability_info_for_context, has_availability_info_for_user, user_facing_type {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        $config = $outcome->get_type_config();
        if (!class_exists('block_xp\di')) {
            return;
        }

        $mh = di::get('mission_helper');
        $context = $missioninst->get_mission()->get_context();
        $world = $this->resolve_world_from_context($context);
        $store = $world->get_store();

        $reason = null;
        if ($store instanceof state_store_with_reason) {
            if ($mh->is_a_quest($missioninst)) {
                $reason = quest_completed_reason::from_mission_instance($missioninst);
            } else if ($mh->is_an_achievement($missioninst)) {
                $reason = achievement_unlocked_reason::from_mission_instance($missioninst);
            } else if ($mh->is_a_challenge($missioninst)) {
                $reason = challenge_completed_reason::from_mission_instance($missioninst);
            }

            // Fail-safe due in relation to block_gearup\local\xp\compat\reason.
            if (!$reason instanceof \block_xp\local\reason\reason) {
                $reason = null;
            }
        }

        $userid = $missioninst->get_subject_id();
        $points = $config->points ?? 1;
        if ($reason) {
            $store->increase_with_reason($userid, $points, $reason);
        } else {
            $store->increase($userid, $points);
        }
    }

    public function get_availability_info(): info {
        return new plugin_required_info('block_xp', 'Level Up XP', 2022112600, 'v3.13');
    }

    public function get_availability_info_for_context(\context $context): info {
        if (!class_exists('block_xp\di')) {
            return new static_info(false);
        }

        $config = \block_xp\di::get('config');
        $blockresolver = \block_xp\di::get('course_world_block_any_instance_finder_in_context');

        // Configured for the whole site.
        if ($config->get('context') == CONTEXT_SYSTEM) {
            if (!$blockresolver->get_any_instance_in_context('block_xp', context_system::instance())) {
                return new static_info(false, [new lang_string('xprequiresblocknotfound', 'block_gearup')]);
            }
            return new static_info(true);
        }

        // Requires a block found in the course.
        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext) {
            return new static_info(false, [new lang_string('requirestobeincourse', 'block_gearup')]);
        } else if (!$blockresolver->get_any_instance_in_context('block_xp', $coursecontext)) {
            return new static_info(false, [new lang_string('xprequiresblocknotfound', 'block_gearup')]);
        }

        return new static_info(true);
    }

    public function get_availability_info_for_user(int $userid, \context $context): info {
        if (!class_exists('block_xp\di')) {
            return new static_info(false);
        }
        $reasons = [];

        $accessperms = $this->resolve_world_from_context($context)->get_access_permissions();
        $cando = $accessperms->can_manage($userid);
        if (!$cando) {
            $reasons[] = new lang_string('requirespermission', 'block_gearup', [
                'label' => get_string('xp:manage', 'block_xp'),
                'name' => 'block/xp:manage',
            ]);
        }

        return new static_info($cando, $reasons);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new xp_config_form_extender();
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomexp', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomexpdesc', 'block_gearup');
    }

    protected function resolve_world_from_context(\context $context) {
        $coursecontext = $context->get_course_context(false);
        $courseid = $coursecontext ? $coursecontext->instanceid : SITEID;
        $factory = \block_xp\di::get('course_world_factory');
        return $factory->get_world($courseid);
    }
}

/**
 * Form extender.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xp_config_form_extender implements extender {

    public function definition($mform): array {
        $els = [];
        // TODO Explain where points will be awarded.
        $els[] = $mform->addElement('text', 'cd_points', get_string('xp', 'block_xp'));
        $mform->addRule('cd_points', get_string('err_required', 'core_form'), 'required', null, 'client');
        $mform->setType('cd_points', PARAM_INT);
        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];
        if ($data->cd_points <= 0) {
            $errors['cd_points'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
