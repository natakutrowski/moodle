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
use block_gearup\local\model\assigner;
use block_gearup\task\mission_recruits_delete_adhoc;
use core\notification;
use html_writer;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_mission_recruits_dynamic_form extends mission_dynamic_form_base {

    protected $supportsgroups = true;

    protected function definition() {
        parent::definition();

        $repo = di::get('repository');
        $mh = di::get('mission_helper');

        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        $nrecruits = $repo->count_users($this->get_mission()->get_id());
        $mform->addElement('html', html_writer::div(markdown_to_html(get_string('deleterecruitsintro',
            'block_gearup',
            ['n' => $nrecruits]
        )), 'gu-mb-4 last-of-type:[&_p]:gu-mb-0'));

        $mform->addElement('header', 'optionshdr', get_string('options', 'block_gearup'));
        $noptions = 0;

        $ncompleted = $repo->count_instances_completed($this->get_mission()->get_id());
        if ($ncompleted > 0) {
            $noptions++;

            $helpstring = get_string('deleterecruitscompletedexistsdesc', 'block_gearup', ['n' => $ncompleted]);
            if ($mh->is_repeating($this->get_mission())) {
                $helpstring = get_string('deleterecruitsendedmissionsexistsdesc', 'block_gearup', ['n' => $ncompleted]);
            }

            $els = [];
            $els[] = $mform->createElement('select',
                'deletecompleted',
                get_string('deletecompletedmissions', 'block_gearup'),
                [0 => get_string('no', 'core'), 1 => get_string('yes', 'core')]
            );
            $els[] = $mform->createElement('static',
                'deletecompleted_help',
                '',
                html_writer::div($helpstring, 'gu-mt-2 gu-text-gray-500')
            );
            $mform->addElement('group',
                'deletecompletedgrp',
                get_string('deletecompletedmissions', 'block_gearup'),
                $els,
                '',
                false
            );
        } else {
            $mform->addElement('hidden', 'deletecompleted', 0);
            $mform->setType('deletecompleted', PARAM_BOOL);
        }

        // TODO When assigners can be enabled/disabled, we should add an option disable them.
        $nassigners = $repo->count_assigners($this->get_mission()->get_id());
        if ($nassigners > 0) {
            $noptions++;
            $els = [];
            $els[] = $mform->createElement('select',
                'deleteassigners',
                get_string('deleteassigners', 'block_gearup'),
                [0 => get_string('no', 'core'), 1 => get_string('yes', 'core')]
            );
            $els[] = $mform->createElement('static',
                'deleteassigners_help',
                '',
                html_writer::div(get_string('deleterecruitsassignersexistsdesc', 'block_gearup', ['n' => $nassigners]),
                    'gu-mt-2 gu-text-gray-500'
                )
            );
            $mform->addElement('group', 'deleteassignersgrp', get_string('deleteassigners', 'block_gearup'), $els, '', false);
        } else {
            $mform->addElement('hidden', 'deleteassigners', 0);
            $mform->setType('deleteassigners', PARAM_BOOL);
        }

        // Remove the options fieldset when there are no options.
        if (!$noptions) {
            $mform->removeElement('optionshdr');
        }
    }

    protected function get_default_data() {
        return (object) [
            'deletecompleted' => 0,
            'deleteassigners' => 0,
        ];
    }

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {

            // Deleting the assigners.
            if (!empty($data->deleteassigners)) {
                // We should not be doing this like this.
                foreach (assigner::get_records(['missionid' => $this->get_mission()->get_id()]) as $assigner) {
                    $assigner->delete();
                }
            }

            // Executing or deferring the execution.
            $result = mission_recruits_delete_adhoc::process_or_schedule_deletion($this->get_mission(), $data->deletecompleted);

            // If deferred, show message.
            if ($result->deferred ?? false) {
                notification::add(get_string('deletionrequestprocessinbackground', 'block_gearup'), notification::INFO);
                return;
            }

            // If not deferred, show result.
            $nrecruits = $result->nrecruits ?? 0;
            $ninstances = $result->ninstances ?? 0;
            if ($nrecruits !== $ninstances) {
                $message = get_string('successdeletedxrecruitsforymissions',
                    'block_gearup',
                    ['recruits' => $nrecruits, 'missions' => $ninstances]
                );
            } else {
                $message = get_string('successdeletedxrecruits', 'block_gearup', $nrecruits);
            }

            notification::add($message, notification::SUCCESS);
        }
    }
}
