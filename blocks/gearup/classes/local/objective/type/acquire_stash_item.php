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
 * Acquire stash item.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use backup;
use block_gearup\local\action\action;
use block_gearup\local\action\stash_item_acquired;
use block_gearup\local\availability\course_context_required_info;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\has_availability_info_for_user;
use block_gearup\local\availability\info;
use block_gearup\local\availability\permission_required_info;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\availability\static_info;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\objective\persisted_objective;
use context;
use lang_string;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Acquire stash item.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class acquire_stash_item implements
    has_availability_info,
    has_availability_info_for_context,
    has_availability_info_for_user,
    type,
    type_with_state_initialisation,
    type_with_update_after_restore {

    /** Any course. */
    const WHICH_ANY = 0;
    /** One specific. */
    const WHICH_SPECIFIC = 1;

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof stash_item_acquired) {
            return;
        }
        $instance->increment_counter($action->get_quantity());
    }

    public function get_availability_info(): info {
        return new plugin_required_info('block_stash', 'Stash', 2022042100, 'v1.3.4');
    }

    public function get_availability_info_for_context(\context $context): info {
        if (!class_exists('block_stash\manager')) {
            return new static_info(false);
        }

        $contextinfo = new course_context_required_info($context);
        if (!$contextinfo->is_available()) {
            return $contextinfo;
        }

        // Requires stash to be enabled.
        $manager = \block_stash\manager::get($context->get_course_context()->instanceid);
        if (!$manager->is_enabled()) {
            return new static_info(false, [new lang_string('stashdisabled', 'block_stash')]);
        }

        return new static_info(true);
    }

    public function get_availability_info_for_user(int $userid, context $context): info {
        return new permission_required_info('block/stash:addinstance', $context, $userid);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): extender {
        return new acquire_stash_item_config_form_extender($mission->get_context());
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typeacquirestashitem', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typeacquirestashitemdesc', 'block_gearup');
    }

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        global $DB;
        if (!class_exists('block_stash\user_item')) {
            return;
        }

        $objective = $instance->get_objective();
        $config = $objective->get_type_config();
        $which = $config->which ?? static::WHICH_ANY;
        $itemid = $config->itemid ?? 0;

        if ($which == static::WHICH_ANY) {
            return;
        }

        // Get the number of items.
        $count = (int) $DB->get_field(\block_stash\user_item::TABLE, 'quantity', [
            'itemid' => $itemid,
            'userid' => $missioninst->get_subject_id(),
        ], IGNORE_MISSING);
        if ($count > 0) {
            $instance->increment_counter($count);
        }
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof stash_item_acquired;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {
        if (!$action instanceof stash_item_acquired) {
            return false;
        }

        $objective = $instance->get_objective();
        $config = $objective->get_type_config();
        $which = $config->which ?? static::WHICH_ANY;
        $itemid = $config->itemid ?? 0;

        if ($which == static::WHICH_SPECIFIC && $action->get_item_id() != $itemid) {
            return false;
        }

        return true;
    }

    public function update_after_restore(restore_context $restore, objective $objective, mission $mission) {
        if (!$objective instanceof persisted_objective) {
            $restore->get_logger()->process("Cannot process after_restore of objective " . $objective->get_id(),
                backup::LOG_WARNING
            );
            return;
        }

        $config = $objective->get_type_config();
        $itemid = $config->itemid ?? 0;
        if (empty($itemid)) {
            return;
        }

        $newitemid = $restore->get_mapping_id('block_stash_item', $itemid);
        if (!$newitemid) {
            $restore->get_logger()->process("Stash item ID $itemid not found", backup::LOG_INFO);
            return;
        }
        // Commit the change.
        try {
            if ($config->itemid == $newitemid) {
                return;
            }
            $config->itemid = $newitemid;
            $objective->get_persistent()->set('configdata', $config);
            $objective->get_persistent()->update();
        } catch (\moodle_exception $e) {
            $restore->get_logger()->process("Error while updating objective " . $objective->get_id(), backup::LOG_WARNING);
        }
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
class acquire_stash_item_config_form_extender implements extender {

    protected $manager;

    /**
     * Constructor.
     *
     * @param \context $context The mission's context.
     */
    public function __construct(\context $context) {
        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext) {
            throw new \coding_exception('The context must be a course context.');
        }
        $this->manager = \block_stash\manager::get($coursecontext->instanceid);
    }

    public function definition($mform): array {
        $els = [];

        $els[] = $mform->addElement('select', 'cd_which', get_string('elligibleitem', 'block_gearup'), [
            acquire_stash_item::WHICH_ANY => get_string('any', 'block_gearup'),
            acquire_stash_item::WHICH_SPECIFIC => get_string('specificone', 'block_gearup'),
        ]);

        $items = $this->manager->get_items();
        $options = array_reduce($items, function ($carry, $item) {
            $carry[$item->get_id()] = format_string($item->get_name(), true, ['context' => $this->manager->get_context()]);
            return $carry;
        }, []);

        $els[] = $mform->addElement('select', 'cd_itemid', get_string('item', 'block_gearup'), $options);
        $mform->hideIf('cd_itemid', 'cd_which', 'eq', acquire_stash_item::WHICH_ANY);

        $els[] = $mform->addElement($mform->removeElement('countneeded'));

        return $els;
    }

    public function get_data($data) {
        if ($data->cd_which == acquire_stash_item::WHICH_SPECIFIC) {
            $data->cd_itemid = (int) $data->cd_itemid;
        } else {
            unset($data->cd_itemid);
        }
        $data->cd_which = (int) $data->cd_which;
        $data->countneeded = (int) $data->countneeded;
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];
        if (!isset($data->cd_which)) {
            $errors['cd_which'] = get_string('invaliddata', 'core_error');
        }
        if ($data->cd_which == acquire_stash_item::WHICH_SPECIFIC && !isset($data->cd_itemid)) {
            $errors['cd_itemid'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
