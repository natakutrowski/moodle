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
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\form;

use block_gearup\di;
use block_gearup\local\utils\course_utils;
use block_gearup\local\utils\user_utils;
use stdClass;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective_instance_increment_counter_dynamic_form extends improved_dynamic_form {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'missioninstid');
        $mform->setConstant('missioninstid', $this->get_mission_instance()->get_id());
        $mform->setType('missioninstid', PARAM_INT);

        $mform->addElement('hidden', 'objectiveid');
        $mform->setConstant('objectiveid', $this->get_objective_instance()->get_objective()->get_id());
        $mform->setType('objectiveid', PARAM_INT);

        if ($this->get_objective_instance()->get_objective()->get_count_needed() > 1) {
            $mform->addElement('text', 'quantity', get_string('incrementby', 'block_gearup'));
            $mform->addRule('quantity', null, 'required', null, 'client');
            $mform->addElement('static', 'note', '', 'Please note that this may mark the objective as complete.');
        } else {
            $mform->addElement(html::register(), 'note', get_string('confirmmarkobjectivecomplete', 'block_gearup'));
            $mform->addElement('hidden', 'quantity', 1);
            $mform->setConstant('quantity', 1);
        }
        $mform->setType('quantity', PARAM_INT);
    }

    /**
     * Validation.
     *
     * @param stdClass $data Data to validate.
     * @param array $files Array of files.
     * @param array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     */
    public function extra_validation($data, $files, &$errors) {
        $errors = parent::extra_validation($data, $files, $errors);
        if ($data->quantity <= 0) {
            $errors['quantity'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

    /**
     * Get mission instance.
     *
     * @return block_gearup\local\mission\mission_instance
     */
    protected function get_mission_instance() {
        return $this->_dynamicdata['missioninst'];
    }

    /**
     * Get objective instance.
     *
     * @return block_gearup\local\mission\objective_instance
     */
    protected function get_objective_instance() {
        return $this->_dynamicdata['objinst'];
    }

    /**
     * Initialise for dynamic submission.
     *
     * @return void
     */
    protected function initialise_for_dynamic_submission(): void {
        $missioninstid = $this->optional_param('missioninstid', 0, PARAM_INT);
        $objectiveid = $this->optional_param('objectiveid', 0, PARAM_INT);

        $missioninst = di::get('repository')->get_instance($missioninstid);
        $objinst = $missioninst->get_instance_of_objective($objectiveid);

        $this->_dynamicdata['missioninst'] = $missioninst;
        $this->_dynamicdata['objinst'] = $objinst;
        $this->_dynamicdata['context'] = $missioninst->get_mission()->get_context();
    }

    /**
     * Check permissions.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        global $USER;

        // Check the global access permissions.
        $missioninst = $this->get_mission_instance();
        $ap = di::get('access_permissions_factory')->get_permissions_for_context($missioninst->get_mission()->get_context());
        $ap->require_manage();

        // Validate that the user is not at risk of affecting recruits they should not see.
        $context = $missioninst->get_mission()->get_context();
        if (course_utils::uses_group_mode($context) && !user_utils::can_view_all_participants($context, $USER->id)) {
            throw new \moodle_exception('accessnotpermittedcannotviewallparticipants', 'block_gearup');
        }

        // Validate that the mission is not archived.
        if (di::get('mission_helper')->is_archived($missioninst)) {
            throw new \moodle_exception('cannoteditarchivedmission', 'block_gearup');
        }

        // The mission cannot be completed.
        $missionhelper = di::get('mission_helper');
        if (!$missionhelper->has_started($missioninst) || $missionhelper->has_completed($missioninst)) {
            throw new \moodle_exception('invalidstate', 'block_gearup');
        }

        // The objective cannot be completed.
        $objinst = $this->get_objective_instance();
        if ($objinst->is_completed()) {
            throw new \moodle_exception('invalidstate', 'block_gearup');
        }
    }

    /**
     * Process the form submission.
     *
     * @return mixed
     */
    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {
            $objoperator = di::get('objective_operator');
            $objoperator->increment_instance_counter($this->get_objective_instance(), $data->quantity);
            $missionoperator = di::get('mission_operator');
            $missionoperator->evaluate_instance($this->get_mission_instance());
        }
    }

    /**
     * Returns url to set in $PAGE->set_url().
     *
     * @return \moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return $this->get_url_resolver()->reverse('mission',
            ['missionid' => $this->get_mission_instance()->get_mission()->get_id()]);
    }

}
