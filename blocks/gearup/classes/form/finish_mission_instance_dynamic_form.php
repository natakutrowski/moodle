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

namespace block_gearup\form;

use block_gearup\di;
use html_writer;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class finish_mission_instance_dynamic_form extends mission_instance_dynamic_form_base {

    protected function definition() {
        parent::definition();

        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        $mform->addElement('html', html_writer::div(markdown_to_html(get_string('finaliseinstanceintro', 'block_gearup')),
            'last-of-type:[&_p]:gu-mb-0'
        ));
    }

    protected function check_access_for_mission(): void {
        $missioninst = $this->get_mission_instance();
        $missionhelper = di::get('mission_helper');
        $canend = $missionhelper->is_completed($missioninst) && !$missionhelper->is_ended($missioninst);
        if (!$canend) {
            throw new \moodle_exception('notfound', 'block_gearup');
        }
    }

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {
            di::get('mission_operator')->finish_instance($this->get_mission_instance());
        }
    }
}
