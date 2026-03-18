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
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\di;
use block_gearup\local\action\action;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webhook implements type {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new webhook_config_form_extender($mission, $objective);
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typewebhook', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typewebhookdesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return false;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {
        return false;
    }

}

/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webhook_config_form_extender implements extender {

    protected $mission;
    protected $objective;

    public function __construct(mission $mission, ?objective $objective) {
        $this->mission = $mission;
        $this->objective = $objective;
    }

    public function definition($mform): array {
        global $USER;

        $renderer = di::get('renderer');
        $resolver = di::get('system_url_resolver');
        $els = [];

        $mform->getElement('countneeded')->setLabel(get_string('counttoattain', 'block_gearup'));

        if (!$this->objective) {
            $els[] = $mform->addElement('static', 'whendpoint', '', get_string('typewebhooksavetogeturl', 'block_gearup'));
        } else {

            $url = $resolver->reverse('objincr', [
                'objectiveid' => $this->objective->get_id(),
                'secret' => $this->mission->get_secret(),
                'user' => 'id-or-email',
                'amount' => 'number',
            ]);
            $urlexample = $resolver->reverse('objincr', [
                'objectiveid' => $this->objective->get_id(),
                'secret' => $this->mission->get_secret(),
                'user' => $USER->email,
                'amount' => '1',
            ]);

            $urlhtml = str_replace('id-or-email',
                '<strong>' . s('<id-or-email>') . '</strong>',
                str_replace('number', '<strong>' . s('<number>') . '</strong>', s($url->out(false)))
            );

            $els[] = $mform->addElement('static',
                'instructions',
                '',
                $renderer->code_instructions(markdown_to_html(
                    get_string('typewebhookinstructions', 'block_gearup', [
                        'url' => $urlhtml,
                        'example' => $urlexample->out(false),
                    ])
                ))
            );
        }

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        return [];
    }

}
