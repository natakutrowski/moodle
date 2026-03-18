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
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\form;

use block_gearup\di;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\mission\mission;
use block_gearup\task\mission_assignmentbehaviour_update_adhoc;
use moodle_exception;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quest_assignment_behaviour_dynamic_form extends mission_dynamic_form_base {

    protected function definition() {
        parent::definition();
        $mform = $this->_form;

        $mform->addElement(radiogroup::register(), 'assignbehaviour', get_string('assignmentbehaviour', 'block_gearup'), [
            [
                'value' => 0,
                'label' => get_string('compulsoryquest', 'block_gearup'),
                'description' => get_string('compulsoryquestdesc', 'block_gearup'),
            ],
            [
                'value' => 1,
                'label' => get_string('optionalquest', 'block_gearup'),
                'description' => get_string('optionalquestdesc', 'block_gearup'),
            ],
            [
                'value' => 2,
                'label' => get_string('discoverablequest', 'block_gearup'),
                'description' => get_string('discoverablequestdesc', 'block_gearup'),
                'availability' => new plugin_required_info('filter_shortcodes', get_string('shortcodes', 'block_gearup')),
            ],
        ]);

        if ($this->requires_change_confirmation()) {
            $mform->addElement('advcheckbox', 'confirm', get_string('confirmquestwillstartforall', 'block_gearup'));
            $mform->hideIf('confirm', 'assignbehaviour', 'neq', 0);
        }
    }

    protected function convert_fields(\stdClass $data) {
        $data = parent::convert_fields($data);

        $assignbehaviour = (int) ($data->assignbehaviour ?? 0);
        unset($data->assignbehaviour);

        if ($assignbehaviour === 0) {
            $data->visibility = mission::VISIBLE_ALWAYS;
            $data->startmode = mission::START_ALWAYS;
        } else if ($assignbehaviour === 1) {
            $data->visibility = mission::VISIBLE_ALWAYS;
            $data->startmode = mission::START_OPTIN;
        } else if ($assignbehaviour === 2) {
            $data->visibility = mission::VISIBLE_SECRET;
            $data->startmode = mission::START_OPTIN;
        }

        return $data;
    }

    protected function get_default_data() {
        $data = parent::get_default_data();

        $data->assignbehaviour = null;
        if ($data->visibility === mission::VISIBLE_ALWAYS && $data->startmode === mission::START_ALWAYS) {
            $data->assignbehaviour = 0;
        } else if ($data->visibility === mission::VISIBLE_ALWAYS && $data->startmode === mission::START_OPTIN) {
            $data->assignbehaviour = 1;
        } else if ($data->visibility === mission::VISIBLE_SECRET && $data->startmode === mission::START_OPTIN) {
            $data->assignbehaviour = 2;
        }

        return $data;
    }

    protected function extra_validation($data, $files, array &$errors) {
        if (isset($errors['startmode'])) {
            $errors['assignbehaviour'] = $errors['startmode'];
        } else if (isset($errors['visibility'])) {
            $errors['assignbehaviour'] = $errors['visibility'];
        }

        if ($this->requires_change_confirmation($data->startmode) && empty($data->confirm)) {
            $errors['confirm'] = get_string('confirmationrequired', 'block_gearup');
        }

        return [];
    }

    protected function check_access_for_mission(): void {
        $mh = di::get('mission_helper');
        $mission = $this->get_mission();
        if (!$mission->get_id()) {
            throw new moodle_exception('nopermissiontoeditthis', 'block_gearup');
        } else if (!$mh->is_a_quest($mission)) {
            throw new moodle_exception('nopermissiontoeditthis', 'block_gearup');
        }
    }

    protected function requires_change_confirmation($newstart = null) {
        $missionhelper = di::get('mission_helper');
        $repository = di::get('repository');

        // Before we know the value that is picked, we assume that a confirmation will be needed
        // when the mission is not already compulsory.
        $ismissioncompulsory = $missionhelper->is_compulsory($this->get_mission());
        $needsconfirmation = !$ismissioncompulsory;

        // When we know what the next value will be. We check that its compulsory state has
        // changed, and that the mission will become compulsory.
        if ($newstart !== null) {
            $willbecomecompulsory = (int) $newstart === mission::START_ALWAYS;
            $needsconfirmation = $ismissioncompulsory != $willbecomecompulsory && $willbecomecompulsory;
        }

        return $needsconfirmation && $repository->has_non_started_instances($this->get_mission()->get_id());
    }

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {
            unset($data->id);

            // TODO Move all of this to an operator?
            $model = $this->get_persistent();
            $model->from_record($data);
            $model->update();

            // Schedule the task to update instances based on the assignment behaviour change.
            $task = new mission_assignmentbehaviour_update_adhoc();
            $task->set_custom_data(['missionid' => $this->get_mission()->get_id()]);
            $task->set_component('block_gearup');
            \core\task\manager::queue_adhoc_task($task, true);
        }
    }
}
