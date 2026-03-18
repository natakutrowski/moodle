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
use block_gearup\local\model\assigner;
use block_gearup\local\utils\collection_utils;
use block_gearup\task\assigner_sync_adhoc;
use stdClass;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assigner_dynamic_form extends persistent_dynamic_form {

    protected static $persistentclass = assigner::class;

    /** @var object|null|false A form extender, null, or false. */
    protected $typeconfigform = null;

    public function definition() {

        $mform = $this->_form;
        $mission = $this->get_mission();
        $type = $this->get_type();

        $mform->addElement('hidden', 'id');
        $mform->setConstant('id', $this->get_persistent()->get('id'));

        $mform->addElement('hidden', 'missionid');
        $mform->setConstant('missionid', $mission->get_id());

        $mform->addElement('hidden', 'type');
        $mform->setConstant('type', $this->get_type_name());

        $mform->addElement('hidden', 'addtypeoptionshere');
        $mform->setType('addtypeoptionshere', PARAM_BOOL);

        $extender = $this->get_type_config_form();
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
        $mform->removeElement('addtypeoptionshere');
    }

    /**
     * Validation.
     *
     * @param array $data The data.
     * @param array $files The files.
     * @return array With the errors found.
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
            $extender = $this->get_type_config_form();
            if ($extender) {
                $extraerrors = $extender->validation((object) $data, $files);
                $newerrors = array_merge($errors, (array) $extraerrors);
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
            $type = $this->get_type();
            $extender = $this->get_type_config_form();
            if ($type && $extender) {
                $configdata = collection_utils::unprefix_data(
                    collection_utils::filter_data_with_prefix($extender->get_data($data), 'cd_'),
                    'cd_'
                );
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

        unset($data->configdata);
        $configdata = $this->get_persistent()->get('configdata');
        if ($configdata) {
            $data = (object) array_merge((array) $data, (array) collection_utils::prefix_data($configdata, 'cd_'));
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
        return di::get('assigner_type_resolver');
    }

    /**
     * Get type config form.
     *
     * @return type
     */
    protected function get_type_config_form() {
        if ($this->typeconfigform === null) {
            $type = $this->get_type();
            $this->typeconfigform = $type ? $type->get_config_form_extender($this->get_mission()) : false;
        }
        return $this->typeconfigform;
    }

    /**
     * Initialise for dynamic submission.
     *
     * @return void
     */
    protected function initialise_for_dynamic_submission(): void {
        $missionid = $this->optional_param('missionid', 0, PARAM_INT);
        $mr = di::get('repository');
        $mh = di::get('mission_helper');

        $mission = $mr->get_mission($missionid);
        if (!$mission) {
            throw new \moodle_exception('notfound');
        }

        // TODO Move this elsewhere where we can extend it?
        $this->_dynamicdata['mission'] = $mission;
        $this->_dynamicdata['context'] = $mission->get_context();

        $id = $this->optional_param('id', null, PARAM_INT);
        if (!$id) {
            $type = $this->optional_param('type', null, PARAM_RAW);
            $persistent = new static::$persistentclass(0, (object) [
                'missionid' => $mission->get_id(),
                'type' => $type,
            ]);
        } else {
            $assigners = $mr->get_assigners($mission->get_id());
            $assigner = null;
            foreach ($assigners as $candidate) {
                if ($candidate->get_id() == $id) {
                    $assigner = $candidate;
                    break;
                }
            }
            if (!$assigner) {
                throw new \coding_exception('Unknown assigner.');
            }
            $persistent = $assigner->get_persistent();
        }

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

        // Note that multiple assigners of the same type are always allowed at the moment.
        $type = $this->get_type();

        // The following may prevent edits of existing assigners if the availabilty changed, but that's OK for now.
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
        if ($data = $this->get_data()) {
            $configdata = $data->configdata;
            unset($data->configdata);

            // TODO Move all of this to an operator?
            $model = $this->get_persistent();
            $model->set('configdata', $configdata);

            if (!$model->get('id')) {
                $model->from_record($data);
                $model->create();

                // New assigners are automatically synced.
                $task = new assigner_sync_adhoc();
                $task->set_component('block_gearup');
                $task->set_custom_data([
                    'missionid' => $model->get('missionid'),
                    'assignerid' => $model->get('id'),
                ]);
                \core\task\manager::queue_adhoc_task($task);

            } else {
                $model->from_record((object) array_diff_key((array) $data, ['id' => true, 'missionid' => true, 'type' => true]));
                $model->update();

                // TODO Should an update retrigger a global sync?
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
        return (bool) $this->get_persistent()->get('id');
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
        $this->get_persistent()->delete();
    }

}
