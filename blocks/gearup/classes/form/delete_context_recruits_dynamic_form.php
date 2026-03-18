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
use block_gearup\local\repository\user_query;
use block_gearup\local\utils\course_utils;
use block_gearup\local\utils\user_utils;
use block_gearup\task\context_recruits_delete_adhoc;
use context;
use context_system;
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
class delete_context_recruits_dynamic_form extends improved_dynamic_form {

    protected function definition() {
        $repo = di::get('repository');
        $context = $this->_dynamicdata['context'];

        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        $mform->addElement('hidden', 'guctxid');
        $mform->setType('guctxid', PARAM_INT);

        $userquery = (new user_query($context))->set_context_id($context->id);
        $nrecruits = $repo->count_users_from_query($userquery);
        $mform->addElement('html', html_writer::div(markdown_to_html(get_string('deletecontextrecruitsintro',
            'block_gearup',
            ['n' => $nrecruits]
        )), 'last-of-type:[&_p]:gu-mb-0'));
    }

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {

            // Executing or deferring the execution.
            $result = context_recruits_delete_adhoc::process_or_schedule_deletion($this->_dynamicdata['context']);

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

    protected function check_access_for_dynamic_submission(): void {
        global $USER;

        $ap = di::get('access_permissions_factory')->get_permissions_for_context($this->_dynamicdata['context']);
        $ap->require_manage();

        // Validate that the user is not at risk of deleting recruits they should not see.
        if (course_utils::uses_group_mode($this->_dynamicdata['context'])
                && !user_utils::can_view_all_participants($this->_dynamicdata['context'], $USER->id)
        ) {
            throw new \moodle_exception('accessnotpermittedcannotviewallparticipants', 'block_gearup');
        }
    }

    protected function get_default_data() {
        return (object) [
            'guctxid' => $this->optional_param('guctxid', 0, PARAM_INT),
        ];
    }

    protected function initialise_for_dynamic_submission(): void {
        $contextid = $this->optional_param('guctxid', 0, PARAM_INT);

        // Normalize the context.
        $candidatecontext = $contextid ? context::instance_by_id($contextid) : context_system::instance();
        $context = di::get('context_manager')->normalise_context($candidatecontext);

        $this->_dynamicdata['context'] = $context;
    }

    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return $this->get_url_resolver()->reverse('users');
    }

}
