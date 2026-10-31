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
use core_form\dynamic_form;
use DateTimeImmutable;
use local_xp\local\leaderboard\participation\service;
use local_xp\local\leaderboard\participation\state;
use required_capability_exception;

/**
 * Form.
 *
 * @package    local_xp
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_leaderboard_participation extends dynamic_form {
    use dynamic_world_trait;

    /** @var string The route name. */
    protected $routename = 'report';

    /** @var ?service */
    protected $service;
    /** @var ?state */
    protected $state;

    /**
     * Get participation service.
     *
     * @return service
     */
    protected function get_participation_service(): service {
        if (!$this->service) {
            $this->service = di::get(\local_xp\local\leaderboard\participation\service_factory::class)
                ->get_for_context($this->get_world()->get_context());
        }
        return $this->service;
    }

    /**
     * Get participation state.
     *
     * @return state
     */
    protected function get_participation_state(): state {
        if (!$this->state) {
            $this->state = $this->get_participation_service()->get_state($this->optional_param('userid', 0, PARAM_INT));
        }
        return $this->state;
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

        // Changing participation is only available through the report, so we also require the report permissions.
        if (!$perms instanceof access_report_permissions) {
            throw new required_capability_exception($world->get_context(), 'block/xp:viewreport', 'nopermissions', '');
        }
        $perms->require_access_report();

        $userid = $this->optional_param('userid', 0, PARAM_INT);
        if (!user_utils::is_valid_target($world->get_context(), $userid)) {
            throw new \moodle_exception('invaliddata', 'core_error');
        }
    }

    /**
     * Process the form submission.
     *
     * @return void
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $userid = (int) $data->userid;

        $service = $this->get_participation_service();
        if ((bool) $data->ladderparticipation) {
            $service->add_participant($userid);
        } else {
            $service->remove_participant($userid);
        }

        // When a manager saves the state of someone, we reset the lock time to none. If the ladderparticipation
        // setting was locked by an admin, we only expect a manager to change the state, but never to lock it. By
        // ensuring that the lock state is always reset to none, we allow managers to unlock students.
        $lockuntil = null;
        if ($data->ladderparticipationlocked ?? 0) {
            $lockuntil = new \DateTimeImmutable('@' . $data->ladderparticipationlocked);
        }
        $service->lock_state($userid, $lockuntil);
    }

    /**
     * Set data for dynamic submission.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        $userid = $this->optional_param('userid', 0, PARAM_INT);
        $state = $this->get_participation_state();
        $lockeduntil = $state->get_state_locked_until();
        $now = new \DateTimeImmutable();
        $this->set_data([
            'userid' => $userid,
            'ladderparticipation' => (int) $state->is_participating(),
            'ladderparticipationlocked' => $lockeduntil && $lockeduntil > $now ? $lockeduntil->getTimestamp() : 0,
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

        $mform->addElement('hidden', 'contextid', $this->get_world()->get_context()->id);
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('select', 'ladderparticipation', get_string('userladderparticipation', 'block_xp'), [
            1 => get_string('participating', 'block_xp'),
            0 => get_string('notparticipating', 'block_xp'),
        ]);
        $mform->addHelpButton('ladderparticipation', 'userladderparticipation', 'block_xp');

        // Managers cannot change the lock time when the admin has locked the participation status.
        $configlocked = \block_xp\di::get('config_locked');
        if (!$configlocked->has('ladderparticipation') || !$configlocked->get('ladderparticipation')) {
            $mform->addElement(
                'date_time_selector',
                'ladderparticipationlocked',
                get_string('userladderparticipationlocked', 'block_xp'),
                ['optional' => true,
                'startyear' => (int) date('Y'),
                'step' => 60]
            );
            $mform->addHelpButton('ladderparticipationlocked', 'userladderparticipationlocked', 'block_xp');
        }
    }
}
