<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

namespace local_xp\local\controller;

use block_xp\di;
use block_xp\local\ruletype\ruletype;

/**
 * Rules controller.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_rules_controller extends \block_xp\local\controller\action_rules_controller {

    /**
     * Get the rule types.
     *
     * @return ruletype[]
     */
    protected function get_rule_types() {
        $completiontypes = ['cm_completion', 'course_completion', 'section_completion'];
        $types = parent::get_rule_types();
        $typeresolver = di::get('rule_type_resolver');
        return array_values(array_filter($types, function (ruletype $type) use ($completiontypes, $typeresolver) {
            return !in_array($typeresolver->get_type_name($type), $completiontypes);
        }));
    }
}
