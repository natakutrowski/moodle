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

namespace block_gearup\local\outcome\type;

use block_gearup\di;
use block_gearup\local\mission\mission;
use lang_string;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_quest extends assign_mission {

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new assign_quest_config_form_extender($mission, di::get('repository'));
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomeassignquest', 'block_gearup');
    }

    protected function get_missionid_prop_in_config(): string {
        return 'questid';
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomeassignquestdesc', 'block_gearup');
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
class assign_quest_config_form_extender extends assign_mission_config_form_extender {

    protected function get_field_name() {
        return 'cd_questid';
    }

    protected function get_label(): string {
        return get_string('quest', 'block_gearup');
    }

    protected function get_mission_options(): array {
        return array_reduce($this->repository->get_quests($this->context), function ($carry, $quest) {
            if ($quest->get_id() == $this->mission->get_id()) {
                return $carry;
            }
            $carry[$quest->get_id()] = $quest->get_title();
            return $carry;
        }, []);
    }

}
