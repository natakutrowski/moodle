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
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\has_availability_info_for_user;
use block_gearup\local\form\extender_with_default_data;
use block_gearup\local\form\extender_with_supporting_url_modes;
use block_gearup\local\mission\mission;
use block_gearup\local\model\objective;
use block_gearup\local\model\objective_inst;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\objective\type\type_with_supporting_url;
use block_gearup\local\utils\collection_utils;
use block_gearup\task\mission_evaluate_adhoc;
use block_gearup\task\mission_objectives_update_adhoc;
use core\output\notification;
use stdClass;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective_dynamic_form extends persistent_dynamic_form {

    protected static $persistentclass = objective::class;

    /** @var object|null|false A form extender, null, or false. */
    protected $typeconfigform = null;

    public function definition() {

        $mh = di::get('mission_helper');
        $mform = $this->_form;
        $mission = $this->get_mission();
        $extender = $this->get_type_config_form();

        $mform->addElement('hidden', 'id');
        $mform->setConstant('id', $this->get_persistent()->get('id'));

        $mform->addElement('hidden', 'missionid');
        $mform->setConstant('missionid', $mission->get_id());

        $mform->addElement('hidden', 'type');
        $mform->setConstant('type', $this->get_type_name());

        $mform->addElement('text', 'countneeded', get_string('countneeded', 'block_gearup'));
        $mform->addRule('countneeded', null, 'required', null, 'client');
        $mform->addHelpButton('countneeded', 'countneeded', 'block_gearup');

        $mform->addElement('hidden', 'addtypeoptionshere');
        $mform->setType('addtypeoptionshere', PARAM_BOOL);

        $mform->addElement(divider::register(), 'appearancedvd');

        if ($mh->is_an_achievement($mission) || $mh->is_a_streak($mission)) {
            $mform->addElement('hidden', 'label');
            $mform->setConstant('label', '');
        } else {
            $mform->addElement('text', 'label', get_string('displayas', 'block_gearup'));
            $mform->addHelpButton('label', 'displayas', 'block_gearup');
            $mform->addRule('label', null, 'required', null, 'client');
        }

        // We remove the supporting URL field on n+1 objectives for streaks and achievements.
        if (!($mh->is_an_achievement($mission) || $mh->is_a_streak($mission))
                || $this->is_first_objective()) {

            // Build the supporting URL modes.
            $urloptions = ['' => get_string('none')];
            if ($this->get_type() instanceof type_with_supporting_url && $extender instanceof extender_with_supporting_url_modes) {
                $extenderoptions = $extender->get_supporting_url_modes();
                foreach ($extenderoptions as $urlmode => $label) {
                    if (!is_number($urlmode) || $urlmode <= 0) {
                        throw new \coding_exception('The URL mode must be a positive integer.');
                    }
                    $urloptions[$urlmode] = $label;
                }
            }
            $urloptions[0] = get_string('custom', 'core_form');

            // Add the supporting URL field.
            $els = [
                $mform->createElement('select', 'supportingurlmode', get_string('supportingurl', 'block_gearup'), $urloptions),
                $mform->createElement('text', 'supportingurlcustom', get_string('supportingurl', 'block_gearup'),
                    ['placeholder' => 'https://...' ]),
            ];
            $mform->hideIf('supportingurlcustom', 'supportingurlmode', 'neq', 0);
            $mform->setType('supportingurlcustom', PARAM_URL);
            $mform->addElement('group', 'supportingurlgroup', get_string('supportingurl', 'block_gearup'), $els, '', false);
            $mform->addHelpButton('supportingurlgroup', 'supportingurl', 'block_gearup');
        } else {
            $mform->addElement('hidden', 'supportingurlmode');
            $mform->setConstant('supportingurlmode', '');
            $mform->setType('supportingurlmode', PARAM_ALPHANUMEXT);
        }

        // Extend the form.
        if ($extender) {
            $elements = $extender->definition($mform);
            foreach ($elements as $element) {
                $mform->insertElementBefore($mform->removeElement($element->getName(), false), 'addtypeoptionshere');
            }
        }
    }

    /**
     * After definition hook.
     *
     * Automatically try to set the types of simple fields using the persistent properties definition.
     * This only applies to hidden, text and url types. Groups are also ignored as they are most likely custom.
     *
     * @return void
     */
    protected function after_definition() {
        parent::after_definition();

        $mform = $this->_form;
        $persistent = $this->get_persistent();

        $mform->removeElement('addtypeoptionshere');
    }

    /**
     * Definition after data.
     *
     * @return void
     */
    public function definition_after_data() {
        parent::definition_after_data();

        $mform = $this->_form;
        $hasvisiblefield = false;
        $hasvisiblebeforedvd = false;
        $hasseenappearancedvd = false;
        foreach ($mform->_elements as $el) {
            $hasseenappearancedvd = $hasseenappearancedvd || $el->getName() === 'appearancedvd';
            if ($el->getType() !== 'hidden') {
                $hasvisiblefield = true;
                $hasvisiblebeforedvd = $hasvisiblebeforedvd || !$hasseenappearancedvd;
            }
        }

        if (!$hasvisiblebeforedvd) {
            $mform->removeElement('appearancedvd');
        }

        if (!$hasvisiblefield) {
            $output = di::get('renderer');

            $this->set_display_vertical(true);
            $mform->setRequiredNote('');
            $mform->addElement('static', 'nofields', '',
                $output->notification(get_string('noconfigurationsettings', 'block_gearup'), notification::NOTIFY_INFO, false));
        }

    }

    /**
     * Validation.
     *
     * @param stdClass $data Data to validate.
     * @param array $files Array of files.
     * @param array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     */
    protected function extra_validation($data, $files, array &$errors) {
        if (isset($errors['configdata'])) {
            // Config data validation does not apply to the form itself. The extender will either validate
            // fields individually, or the create/update function of the persistent will catch
            // any validation issue. This type of error can happen when the type config structure has
            // changed and we are dealing with older objectives.
            unset($errors['configdata']);
        }

        $newerrors = [];
        if ($data) {

            // Require that the custom URL provided is prefixed as PARAM_URL is too permissive.
            if (in_array($data->supportingurlmode, ['0', 0]) && !empty($data->supportingurlcustom)) {
                if (!preg_match('@^https?://@', $data->supportingurlcustom, $matches)) {
                    $newerrors['supportingurlgroup'] = get_string('invaliddata', 'core_error');
                }
            }

            $extender = $this->get_type_config_form();
            if ($extender) {
                $extraerrors = $extender->validation((object) $data, $files);
                $newerrors = array_merge($newerrors, (array) $extraerrors);
            }
        }

        return $newerrors;
    }

    /**
     * Filter the data to what the persistent understands.
     *
     * @param stdClass $data The data to filter the fields out of.
     * @return stdClass
     */
    protected function filter_data_for_persistent($data) {
        $data = parent::filter_data_for_persistent($data);
        $expectedprops = $this->get_persistent()::properties_definition();
        return (object) array_intersect_key((array) $data, $expectedprops);
    }

    /**
     * Get form data.
     *
     * @return object|null
     */
    public function get_data() {
        $data = parent::get_data();

        if (is_object($data)) {

            // Normalise the supporting URL data.
            $supportingurlmode = $data->supportingurlmode ?? null;
            $data->supportingurl = null;
            if (in_array($supportingurlmode, ['0', 0]) && !empty($data->supportingurlcustom)) {
                $data->supportingurl = (string) $data->supportingurlcustom;
            } else if (is_number($supportingurlmode)) {
                $data->supportingurl = (int) $supportingurlmode;
            }
            unset($data->supportingurlmode);
            unset($data->supportingurlcustom);

            $type = $this->get_type();
            $extender = $this->get_type_config_form();
            if ($type && $extender) {
                $extendeddata = $extender->get_data($data);

                $configdata = collection_utils::unprefix_data(
                    collection_utils::filter_data_with_prefix($extendeddata, 'cd_'), 'cd_');

                $data = collection_utils::exclude_data_with_prefix($extendeddata, 'cd_');
                $data->configdata = $configdata; // Return as an object, not JSON.
            }
        }

        return collection_utils::exclude_data_with_prefix($data, 'cd_');
    }

    /**
     * Get the default data.
     *
     * @return stdClass
     */
    protected function get_default_data() {
        $data = parent::get_default_data();

        // Convert the supporting URL.
        if (is_number($data->supportingurl)) {
            $data->supportingurlmode = (int) $data->supportingurl;
        } else if (is_string($data->supportingurl)) {
            $data->supportingurlmode = 0;
            $data->supportingurlcustom = $data->supportingurl;
        }

        unset($data->configdata);
        $configdata = $this->get_persistent()->get('configdata');
        if ($configdata) {
            $data = (object) array_merge((array) $data, (array) collection_utils::prefix_data($configdata, 'cd_'));
        }

        $type = $this->get_type();
        $extender = $this->get_type_config_form();
        if ($type && $extender && $extender instanceof extender_with_default_data) {
            $data = $extender->get_default_data($data);
        }

        return $data;
    }

    /**
     * Get mission.
     *
     * @return block_gearup\local\mission\mission
     */
    protected function get_mission() {
        return $this->_dynamicdata['mission'];
    }

    /**
     * Get objective.
     *
     * @return block_gearup\local\objective\objective|null
     */
    protected function get_objective() {
        return $this->_dynamicdata['objective'] ?? null;
    }

    /**
     * Get type.
     *
     * @return type
     */
    protected function get_type() {
        return $this->get_type_resolver()->get_type($this->get_type_name());
    }

    /**
     * Get type.
     *
     * @return string
     */
    protected function get_type_name() {
        return $this->get_persistent()->get('type');
    }

    /**
     * Get type resolver.
     *
     * @return object
     */
    protected function get_type_resolver() {
        return di::get('objective_type_resolver');
    }

    /**
     * Get type config form.
     *
     * @return type
     */
    protected function get_type_config_form() {
        if ($this->typeconfigform === null) {
            $type = $this->get_type();
            $this->typeconfigform = $type ? $type->get_config_form_extender($this->get_mission(), $this->get_objective()) : false;
        }
        return $this->typeconfigform;
    }

    /**
     * Whether this is the first objective.
     *
     * @return bool
     */
    protected function is_first_objective() {
        $objs = $this->get_mission()->get_objectives();
        if (empty($objs)) {
            return true;
        } else if (!$this->get_objective()) {
            return false;
        }
        return reset($objs)->get_id() === $this->get_objective()->get_id();
    }

    /**
     * Initialise for dynamic submission.
     *
     * @return void
     */
    protected function initialise_for_dynamic_submission(): void {
        $missionid = $this->optional_param('missionid', 0, PARAM_INT);
        $mission = di::get('repository')->get_mission($missionid);
        if (!$mission) {
            throw new \moodle_exception('notfound');
        }

        $this->_dynamicdata['mission'] = $mission;
        $this->_dynamicdata['context'] = $mission->get_context();

        $objective = null;
        $id = $this->optional_param('id', null, PARAM_INT);
        if (!$id) {
            $type = $this->optional_param('type', null, PARAM_RAW);
            $persistent = new static::$persistentclass(0, (object) [
                'missionid' => $mission->get_id(),
                'type' => $type,
            ]);
        } else {
            $objective = $mission->get_objective($id);
            if (!$objective instanceof persisted_objective) {
                throw new \coding_exception('Expected a persisted objective.');
            }
            $persistent = $objective->get_persistent();
        }

        $this->_dynamicdata['objective'] = $objective;
        $this->_dynamicdata['persistent'] = $persistent;
    }

    /**
     * Check permissions.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        global $USER;

        // Check the global access permissions.
        $context = $this->get_mission()->get_context();
        $ap = di::get('access_permissions_factory')->get_permissions_for_context($context);
        $ap->require_manage();

        // Validate that the mission is not archived.
        if (di::get('mission_helper')->is_archived($this->get_mission())) {
            throw new \moodle_exception('cannoteditarchivedmission', 'block_gearup');
        }

        // The following may prevent edits of existing objectves if the availabilty changed.
        $type = $this->get_type();
        if (is_subclass_of($type, has_availability_info::class)) {
            if (!$type->get_availability_info()->is_available()) {
                throw new \moodle_exception('nopermissiontoeditthis', 'block_gearup');
            }
        }
        if (is_subclass_of($type, has_availability_info_for_context::class)) {
            if (!$type->get_availability_info_for_context($context)->is_available()) {
                throw new \moodle_exception('nopermissiontoeditthis', 'block_gearup');
            }
        }
        if (is_subclass_of($type, has_availability_info_for_user::class)) {
            if (!$type->get_availability_info_for_user($USER->id, $context)->is_available()) {
                throw new \moodle_exception('nopermissiontoeditthis', 'block_gearup');
            }
        }
    }

    /**
     * Process the form submission.
     *
     * @return mixed
     */
    protected function process_dynamic_save() {
        $missionhelper = di::get('mission_helper');
        $mission = $this->get_mission();

        if ($data = $this->get_data()) {
            $configdata = $data->configdata;
            unset($data->configdata);

            // TODO Move all of this to an operator?
            $model = $this->get_persistent();
            $model->set('configdata', $configdata);

            if (!$model->get('id')) {
                $model->from_record($data);
                $model->create();

            } else {
                $model->from_record((object) array_diff_key((array) $data, ['id' => true, 'missionid' => true, 'type' => true]));
                $model->update();
            }

            // Schedule a task to update all mission instances. In theory we only need to do this when
            // we have added a new objective, or modified an objective's config that can influence the
            // completion rate of the mission.
            // TODO Should the stale from and dormant until values be refreshed, cleared or reevaluated?
            if ($mission->get_state() === mission::STATE_ACTIVE) {
                $task = new mission_objectives_update_adhoc();
                $task->set_custom_data(['missionid' => $this->get_mission()->get_id()]);
                $task->set_component('block_gearup');
                \core\task\manager::queue_adhoc_task($task, true);
            }
        }
    }

    /**
     * Returns url to set in $PAGE->set_url().
     *
     * @return \moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return $this->get_url_resolver()->reverse('mission', ['missionid' => $this->get_mission()->get_id()]);
    }

    /**
     * Whether we can delete this.
     *
     * @return bool
     */
    protected function can_delete(): bool {
        if (!$this->get_persistent()->get('id')) {
            return false;
        }

        // Deleting the objective for missions that are already completed will cause issues
        // because we would leave the objective instance orphan of its own objective.
        $repository = di::get('repository');
        return $repository->count_instances_completed($this->get_mission()->get_id()) <= 0;
    }

    /**
     * Whether we can delete this.
     *
     * @return bool
     */
    protected function is_deletion_supported(): bool {
        return true;
    }

    /**
     * Process the deletion.
     *
     * @return void
     */
    protected function process_dynamic_deletion() {
        // TODO Move this to an operator?
        // TODO Move to an API responsible for this.
        // TODO Wrap in a transaction.

        // Delete all the objective instances in bulk.
        $objective = $this->get_persistent();
        objective_inst::delete_by_objective_id($objective->get('id'));
        $objective->delete();

        // Queue the task to re-evaluate all the mission instances.
        if ($this->get_mission()->get_state() === mission::STATE_ACTIVE) {
            $task = new mission_evaluate_adhoc();
            $task->set_custom_data(['missionid' => $this->get_mission()->get_id()]);
            $task->set_component('block_gearup');
            \core\task\manager::queue_adhoc_task($task, true);
        }
    }

}
