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

use backup;
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
use block_gearup\local\outcome\outcome;
use block_gearup\local\outcome\persisted_outcome;
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
class stash_item implements
    has_availability_info,
    has_availability_info_for_context,
    has_availability_info_for_user,
    type_with_update_after_restore,
    user_facing_type {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        $context = $missioninst->get_mission()->get_context();
        try {
            $manager = $this->get_manager($context);
        } catch (\Exception $e) {
            return;
        }

        $config = $outcome->get_type_config();
        $userid = $missioninst->get_subject_id();
        $itemid = $config->itemid;
        $quantity = $config->qty;

        try {
            $ui = $manager->get_user_item($userid, $config->itemid);
            $currentquantity = intval($ui->get_quantity());
            $ui->set_quantity($currentquantity + $quantity);
            $ui->update();
            $event = \block_stash\event\item_acquired::create([
                'context' => $manager->get_context(),
                'userid' => $userid,
                'courseid' => $manager->get_courseid(),
                'objectid' => $itemid,
                'relateduserid' => $userid,
                'other' => ['quantity' => $quantity],
            ]);
            $event->trigger();
        } catch (\Exception $e) {
            return;
        }
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

        $reasons = [];
        $manager = $this->get_manager($context);
        $isavailable = $manager->is_enabled();
        if (!$isavailable) {
            $reasons[] = new lang_string('stashdisabled', 'block_stash');
        }

        return new static_info($isavailable, $reasons);
    }

    public function get_availability_info_for_user(int $userid, \context $context): info {
        return new permission_required_info('block/stash:addinstance', $context, $userid);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new stash_item_form_extender($mission->get_context());
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomestashitem', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomestashitemdesc', 'block_gearup');
    }

    protected function get_manager(\context $context) {
        return \block_stash\manager::get($context->get_course_context()->instanceid);
    }

    public function update_after_restore(restore_context $restore, outcome $outcome, mission $mission) {
        if (!$outcome instanceof persisted_outcome) {
            $restore->get_logger()->process("Cannot process after_restore of outcome " . $outcome->get_id(), backup::LOG_WARNING);
            return;
        }

        $config = $outcome->get_type_config();
        $itemid = $config->itemid;

        $newitemid = $restore->get_mapping_id('block_stash_item', $itemid);
        if (!$newitemid) {
            $restore->get_logger()->process("Stash item ID $itemid not found", backup::LOG_INFO);
            return;
        } else if ($newitemid == $itemid) {
            return;
        }

        try {
            $config->itemid = $newitemid;
            $outcome->get_persistent()->set('configdata', $config);
            $outcome->get_persistent()->update();
        } catch (\moodle_exception $e) {
            $restore->get_logger()->process("Error while updating outcome " . $outcome->get_id(), backup::LOG_WARNING);
        }
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
class stash_item_form_extender implements extender {

    /** @var \context The context. */
    protected $context;
    /** @var int The course ID. */
    protected $courseid;

    public function __construct(\context $context) {
        $this->context = $context;
        $this->courseid = $context->get_course_context()->instanceid;
    }

    public function definition($mform): array {
        $els = [];

        $manager = \block_stash\manager::get($this->courseid);

        $items = $manager->get_items();
        $options = array_reduce($items, function ($carry, $item) use ($manager) {
            $carry[$item->get_id()] = format_string($item->get_name(), true, ['context' => $manager->get_context()]);
            return $carry;
        }, []);

        $els[] = $mform->addElement('select', 'cd_itemid', get_string('item', 'block_stash'), $options);
        $els[] = $mform->addElement('text', 'cd_qty', get_string('quantity', 'block_stash'));
        $mform->setType('cd_qty', PARAM_INT);

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];
        if (empty($data->cd_itemid)) {
            $errors['cd_itemid'] = get_string('invaliddata', 'core_error');
        }
        if ($data->cd_qty <= 0 || $data->cd_qty > 99) {
            $errors['cd_qty'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
