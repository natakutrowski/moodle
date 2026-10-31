<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\form;

use block_xp\di;
use block_xp\form\dynamic_world_trait;
use block_xp\local\permission\access_report_permissions;
use block_xp\local\utils\user_utils;
use block_xp\local\utils\world_utils;
use block_xp\local\world\world_with_features;
use core_form\dynamic_form;
use required_capability_exception;

/**
 * Add user points form.
 *
 * @package    local_xp
 * @copyright  2020 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_xp_add extends dynamic_form {
    use dynamic_world_trait;

    /** @var string */
    protected $routename = 'report';

    /**
     * Get the state.
     *
     * @return \block_xp\local\xp\state
     */
    protected function get_state() {
        return $this->get_world()->get_store()->get_state($this->get_target_user_id());
    }

    /**
     * Get the target user id.
     *
     * @return int
     */
    protected function get_target_user_id(): int {
        return $this->optional_param('userid', 0, PARAM_INT);
    }

    /**
     * Check access.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        $world = $this->get_world();
        $perms = $world->get_access_permissions();
        $perms->require_manage();

        if (!world_utils::supports_local_points_management($world)) {
            throw new \moodle_exception('errorfeaturenotenabled', 'block_xp');
        }

        // Adding points is only available through the report, so we also require the report permissions.
        if (!$perms instanceof access_report_permissions) {
            throw new required_capability_exception($this->world->get_context(), 'block/xp:viewreport', 'nopermissions', '');
        }
        $perms->require_access_report();

        // We also check that the user is a valid target for receiving points.
        if (!user_utils::is_valid_target($this->get_world()->get_context(), $this->get_target_user_id())) {
            throw new \moodle_exception('invaliduser', 'core_error');
        }
    }

    /**
     * Process the form submission.
     *
     * @return mixed
     */
    public function process_dynamic_submission() {
        global $USER;

        $state = $this->get_state();
        $userid = $state->get_id();
        $data = $this->get_data();

        $store = $this->get_world()->get_store();
        $reason = \local_xp\local\reason\manual_reason::from_user($USER);

        if ($store instanceof \block_xp\local\xp\state_store_with_reason) {
            $store->increase_with_reason($userid, $data->xp, $reason);
        } else {
            $store->increase($userid, $data->xp);
        }

        if ($data->sendnotification) {
            $notifier = new \local_xp\local\notification\award_notifier(di::get('config'), $this->world, $USER);
            $notifier->notify($userid, $data->points, $data->message ?? null);
        }
    }

    /**
     * Set form data.
     */
    public function set_data_for_dynamic_submission(): void {
        $state = $this->get_state();
        $this->set_data([
            'userid' => $state->get_id(),
            'total' => $state->get_xp(),
        ]);
    }

    /**
     * Form definintion.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        if ($this->_ajaxformdata) {
            $mform->addElement('hidden', 'contextid', $this->get_world()->get_context()->id);
            $mform->setType('contextid', PARAM_INT);
        }

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('text', 'total', get_string('currentpoints', 'local_xp'));
        $mform->setType('total', PARAM_INT);
        $mform->hardFreeze('total');

        $mform->addElement('text', 'xp', get_string('increaseby', 'local_xp'));
        $mform->setType('xp', PARAM_INT);
        $mform->addHelpButton('xp', 'increaseby', 'local_xp');
        $mform->setDefault('xp', 10);

        $mform->addElement('advcheckbox', 'sendnotification', get_string('sendawardnotification', 'local_xp'));
        $mform->addHelpButton('sendnotification', 'sendawardnotification', 'local_xp');

        $mform->addElement('textarea', 'message', get_string('increasemsg', 'local_xp'));
        $mform->setType('message', PARAM_NOTAGS);
        $mform->addHelpButton('message', 'increasemsg', 'local_xp');
        $mform->disabledIf('message', 'sendnotification', 'eq', 0);

        if (!$this->_ajaxformdata) {
            $this->add_action_buttons(true, get_string('confirm'));
        }
    }

    /**
     * Data validate.
     *
     * @param array $data The data submitted.
     * @param array $files The files submitted.
     * @return array of errors.
     */
    public function validation($data, $files) {
        $errors = [];

        // Validating the XP points.
        $xp = (int) $data['xp'];
        if ($xp <= 0) {
            $errors['xp'] = get_string('invalidxp', 'block_xp');
        }

        return $errors;
    }
}
