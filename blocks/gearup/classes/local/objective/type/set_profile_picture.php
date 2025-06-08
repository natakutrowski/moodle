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
 * Set profile picture.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\profile_updated;
use block_gearup\local\availability\admin_setting_info;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\info;
use block_gearup\local\form\extender;
use block_gearup\local\form\extender_with_supporting_url_modes;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use lang_string;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Set profile picture.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_profile_picture implements type, type_with_state_initialisation, type_with_supporting_url, has_availability_info {

    /** URL mode edit profile. */
    const URLMODE_EDIT_PROFILE = 1;

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $userid = $missioninst->get_subject_id();
        if (!$this->has_profile_picture($userid)) {
            return;
        }
        $instance->increment_counter(1);
    }

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$this->has_profile_picture($action->get_user_id())) {
            return;
        }
        $instance->increment_counter(1);
    }

    public function get_availability_info(): info {
        return new admin_setting_info('disableuserimages', new lang_string('disableuserimages', 'core_admin'), false);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        // TODO Set the preference of whether or not a new picture should be created.
        // TODO To track a new picture we need to save the draftitemid of the previous one.
        return new set_profile_picture_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typesetprofilepicture', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new \lang_string('typesetprofilepicturedesc', 'block_gearup');
    }

    public function get_supporting_url(objective $objective, $urltype): ?moodle_url {
        if ($urltype === static::URLMODE_EDIT_PROFILE) {
            return new moodle_url('/user/edit.php');
        }
        return null;
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof profile_updated;
    }

    public function is_action_passing_constraints(action $action, objective_instance $instance,
            mission_instance $missioninst): bool {
        return true;
    }

    /**
     * Whether the user has a profile pic.
     *
     * @param int $userid The user ID.
     * @return bool
     */
    protected function has_profile_picture(int $userid): bool {
        global $DB;
        // We cannot use the $USER object because the latter is not updated when the user object is...
        $userpicture = $DB->get_field('user', 'picture', ['id' => $userid]);
        return !empty($userpicture);
    }
}

/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_profile_picture_config_form_extender implements extender, extender_with_supporting_url_modes {

    public function definition($mform): array {
        $els = [];

        $mform->removeElement('countneeded', true);

        $els[] = $mform->addElement('hidden', 'countneeded');
        $mform->setType('countneeded', PARAM_INT);
        $mform->setConstant('countneeded', 1);

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function get_supporting_url_modes(): array {
        return [
            set_profile_picture::URLMODE_EDIT_PROFILE => get_string('editprofilepage', 'block_gearup'),
        ];
    }

    public function validation($data, $files) {
    }

}
