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
use core\notification;
use html_writer;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class archive_mission_dynamic_form extends mission_dynamic_form_base {

    protected function definition() {
        parent::definition();

        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        $mform->addElement('html', html_writer::div(markdown_to_html(get_string('archivemissionintro', 'block_gearup')),
            'last-of-type:[&_p]:gu-mb-0'
        ));

        $mform->addElement('hidden', 'redirecturl');
        $mform->setType('redirecturl', PARAM_LOCALURL);
    }

    protected function get_default_data() {
        return (object) ((array) parent::get_default_data() + [
            'redirecturl' => $this->optional_param('redirecturl', null, PARAM_LOCALURL),
        ]);
    }

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {

            $mission = $this->get_mission();
            di::get('mission_operator')->archive_mission($mission);

            $redirecturl = $this->optional_param('redirecturl', null, PARAM_LOCALURL);
            if (!empty($redirecturl)) {
                notification::add(get_string('missionarchived', 'block_gearup'), notification::SUCCESS);
                return ['redirecturl' => $redirecturl];
            }
        }
    }

}
