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
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\form;

use block_gearup\di;
use block_gearup\local\utils\form_utils;
use moodle_exception;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quest_identity_dynamic_form extends mission_dynamic_form_base {

    protected function definition() {
        parent::definition();
        $mform = $this->_form;

        $mform->addElement('text', 'title', get_string('missiontitle', 'block_gearup'));
        $mform->addRule('title', null, 'required', null, 'client');

        $visualsrepo = di::get('quest_narrator_visuals_repository');
        form_utils::add_image_group_from_repository($mform,
            'visual',
            get_string('narrator', 'block_gearup'),
            $visualsrepo,
            $this->get_mission()->get_context()
        );
        $mform->addRule('visual', null, 'required', null, 'client');

        if (di::get('lm')->use_speech()) {
            form_utils::add_voice_selector($mform, 'voiceid');
        }
    }

    protected function check_access_for_mission(): void {
        $mission = $this->get_mission();
        if (!$mission->get_id()) {
            throw new moodle_exception('nopermissiontoeditthis', 'block_gearup');
        }
    }

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {
            unset($data->id);

            $model = $this->get_persistent();
            $beforerecord = $model->to_record();
            $model->from_record($data);
            $model->update();

            $voicechanged = $beforerecord->voiceid !== ($data->voiceid ?? null);
            if ($voicechanged) {
                $fs = get_file_storage();
                $fs->delete_area_files($this->get_mission()->get_context()->id,
                    'block_gearup',
                    'speech',
                    $this->get_mission()->get_id()
                );
            }
        }
    }
}
