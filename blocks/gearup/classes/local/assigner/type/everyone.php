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
 * Everyone assigner.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\assigner\type;

use block_gearup\local\assigner\assigner;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\has_availability_info_for_user;
use block_gearup\local\availability\info;
use block_gearup\local\availability\permission_required_info;
use block_gearup\local\availability\static_info;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use context;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Everyone assigner.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class everyone implements type, has_availability_info_for_user, has_availability_info_for_context {

    public function get_availability_info_for_context(context $context): info {
        if ($context->contextlevel >= CONTEXT_COURSE) {
            return new static_info(false, [new lang_string('requirestobeoutsidecourse', 'block_gearup')]);
        }
        return new static_info(true);
    }

    public function get_availability_info_for_user(int $userid, context $context): info {
        return new permission_required_info('moodle/site:config', $context, $userid);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new everyone_config_type_form();
    }

    public function get_display_name(): lang_string {
        return new lang_string('assignereveryone', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('assignereveryonedesc', 'block_gearup');
    }

    public function get_elligible_users_sql(assigner $assigner, mission $mission): array {
        return ['SELECT id FROM {user}', []];
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
class everyone_config_type_form implements extender {

    public function definition($mform): array {
        $els = [];

        $els[] = $mform->addElement('static', 'cd_notes', get_string('notes', 'block_gearup'),
            markdown_to_html(get_string('assignereveryoneformnotes', 'block_gearup')));

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];
        return $errors;
    }

}
