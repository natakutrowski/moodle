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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\form;

use block_gearup\di;
use html_writer;
use stdClass;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duplicate_mission_dynamic_form extends mission_dynamic_form_base {

    public function definition() {
        parent::definition();

        $mr = di::get('repository');
        $mform = $this->_form;

        $mform->addElement('text', 'title', get_string('missiontitle', 'block_gearup'));
        $mform->addRule('title', null, 'required', null, 'client');

        // TODO When assigners can be enabled/disabled, we should add option 'Yes, disabled'.
        $nassigners = $mr->count_assigners($this->get_mission()->get_id());
        if ($nassigners > 0) {
            $assignersel = [];
            $assignersel[] = $mform->createElement('select',
                'includeassigners',
                get_string('copyassigners', 'block_gearup'),
                [0 => get_string('no', 'core'), 1 => get_string('yes', 'core')]
            );
            $assignersel[] = $mform->createElement('static',
                'includeassigners_help',
                '',
                html_writer::div(get_string('copyassignersexistsdesc', 'block_gearup', ['n' => $nassigners]),
                    'gu-mt-2 gu-text-gray-500'
                )
            );
            $mform->addElement('group', 'assignersgrp', get_string('copyassigners', 'block_gearup'), $assignersel, '', false);
        } else {
            $mform->addElement('hidden', 'includeassigners', 0);
            $mform->setType('includeassigners', PARAM_BOOL);
        }

        $mform->addElement('advcheckbox', 'redirect', get_string('redirectaftermissionduplicate', 'block_gearup'));
    }

    /**
     * Get the default data.
     *
     * @return stdClass
     */
    protected function get_default_data() {
        $data = parent::get_default_data();

        $mission = $this->get_mission();
        $data->title = get_string('duplicatedmodule', 'core', $mission->get_title());
        $data->redirect = true;
        $data->includeassigners = false;

        return $data;
    }

    protected function is_available_when_archived(): bool {
        return true;
    }

    /**
     * Process the form submission.
     *
     * @return mixed
     */
    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {

            $options = ['includeassigners' => (bool) $data->includeassigners];
            $dupedmission = di::get('mission_operator')->duplicate_mission($this->get_mission(), $options);
            $dupedmission->get_persistent()->set('title', $data->title);
            $dupedmission->get_persistent()->save();

            $redirecturl = $this->get_url_resolver()->reverse('mission', ['missionid' => $dupedmission->get_id()]);

            if ($data->redirect) {
                return ['redirecturl' => $redirecturl->out(false)];
            }

            $htmlmessage = markdown_to_html(get_string('missionduplicatedgoto', 'block_gearup', [
                'title' => s($dupedmission->get_title()),
                'url' => $redirecturl->out(false),
            ]));
            \core\notification::add(strip_tags($htmlmessage, '<a>'), \core\notification::SUCCESS);
        }
    }

}
