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
use block_gearup\local\model\mission;
use block_gearup\local\utils\course_utils;
use block_gearup\local\utils\user_utils;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mission_dynamic_form_base extends persistent_dynamic_form {

    protected static $persistentclass = mission::class;

    /** @var int The param name behind which the mission ID is set. */
    protected $paramname = 'id';
    /** @var bool Whether the form supports groups. */
    protected $supportsgroups = false;

    protected function definition() {
        $mform = $this->_form;

        // Add the plugin's class.
        if (!isset($mform->_attributes['class'])) {
            $mform->_attributes['class'] = 'block_gearup';
        } else {
            $mform->_attributes['class'] .= ' block_gearup';
        }

        $mform->addElement('hidden', $this->paramname);
        $mform->setType($this->paramname, PARAM_INT);
        $mform->setConstant($this->paramname, $this->get_persistent()->get('id'));
    }

    /**
     * Get mission.
     *
     * @return \block_gearup\local\mission\mission
     */
    final protected function get_mission() {
        return $this->_dynamicdata['mission'];
    }

    /**
     * Initialise for dynamic submission.
     *
     * @return void
     */
    final protected function initialise_for_dynamic_submission(): void {
        $missionid = $this->optional_param($this->paramname, 0, PARAM_INT);
        $mission = di::get('repository')->get_mission($missionid);
        if (!$mission) {
            throw new \moodle_exception('notfound');
        }

        $this->_dynamicdata['mission'] = $mission;
        $this->_dynamicdata['context'] = $mission->get_context();
        $this->_dynamicdata['persistent'] = $mission->get_persistent();
    }

    /**
     * Whether the form is available when archived.
     *
     * Override this method to declare otherwise.
     *
     * @return bool
     */
    protected function is_available_when_archived(): bool {
        return false;
    }

    /**
     * Whether the form is supporting groups.
     *
     * This must be enabled for group feature to kick in, and other verifications to be done.
     *
     * @return bool
     */
    protected function is_groups_supported(): bool {
        return $this->supportsgroups;
    }

    /**
     * Check permissions.
     *
     * @return void
     */
    final protected function check_access_for_dynamic_submission(): void {
        global $USER;

        // Check the global access permissions.
        $context = $this->get_mission()->get_context();
        $ap = di::get('access_permissions_factory')->get_permissions_for_context($context);
        $ap->require_manage();

        // Validate that the user is not at risk of affecting recruits they should not see.
        if ($this->is_groups_supported() && course_utils::uses_group_mode($context)
                && !user_utils::can_view_all_participants($context, $USER->id)) {
            throw new \moodle_exception('accessnotpermittedcannotviewallparticipants', 'block_gearup');
        }

        // Validate that the mission is not archived.
        if (di::get('mission_helper')->is_archived($this->get_mission()) && !$this->is_available_when_archived()) {
            throw new \moodle_exception('cannoteditarchivedmission', 'block_gearup');
        }

        $this->check_access_for_mission();
    }

    /**
     * Check access to mission.
     *
     * For instance, some forms may not be available to achievements, or quests, or
     * when the mission is completed, etc...
     *
     * @return void
     */
    protected function check_access_for_mission(): void {
    }

    /**
     * Returns url to set in $PAGE->set_url().
     *
     * @return \moodle_url
     */
    final protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return $this->get_url_resolver()->reverse('mission', ['missionid' => $this->get_mission()->get_id()]);
    }
}
