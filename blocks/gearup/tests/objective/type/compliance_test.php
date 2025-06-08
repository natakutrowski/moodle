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
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\tests\objective\type;

use block_gearup\local\objective\type\type_with_backup_handling;
use block_gearup\local\objective\type\type_with_state_initialisation;
use block_gearup\local\objective\type\type_with_state_reevaluation;
use block_gearup\local\objective\type\type_with_supporting_url;
use block_gearup\local\objective\type\type_with_update_after_restore;
use block_gearup\tests\base_testcase;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class compliance_test extends base_testcase {

    public function test_objective_compliance(): void {
        foreach ($this->get_instantiable_reflection_classes('local\\objective\\type') as $class) {
            if ($class->hasMethod('get_supporting_url')) {
                $this->assertTrue($class->implementsInterface(type_with_supporting_url::class));
            }
            if ($class->hasMethod('initialise_state')) {
                $this->assertTrue($class->implementsInterface(type_with_state_initialisation::class));
            }
            if ($class->hasMethod('reevaluate_state')) {
                $this->assertTrue($class->implementsInterface(type_with_state_reevaluation::class));
            }
            if ($class->hasMethod('extend_backup')) {
                $this->assertTrue($class->implementsInterface(type_with_backup_handling::class), $class->getName());
            }
            if ($class->hasMethod('update_after_restore')) {
                $this->assertTrue($class->implementsInterface(type_with_update_after_restore::class), $class->getName());
            }
        }
    }

    public function test_objective_availability_compliance(): void {
        foreach ($this->get_instantiable_reflection_classes('local\\objective\\type') as $class) {
            $this->assert_availability_info_compliance($class);
        }
    }

}
