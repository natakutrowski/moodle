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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective;

use block_gearup\di;
use block_gearup\local\objective\persisted_objective_instance;
use block_gearup\tests\base_testcase;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\objective\persisted_objective_instance
 */
final class instance_persisted_test extends base_testcase {

    /**
     * Test.
     */
    public function test_increment_counter(): void {
        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u = $dg->create_user();
        $mission = $gudg->create_mission_model();
        $obj1 = $gudg->create_objective_model([
            'missionid' => $mission->get('id'),
            'type' => 'manual',
            'countneeded' => 5,
        ]);
        $m = $mr->get_mission($mission->get('id'));
        $mi = $mo->assign_mission($m, $u->id);

        $objinst1 = $mi->get_instance_of_objective($obj1->get('id'));
        $this->assertInstanceOf(persisted_objective_instance::class, $objinst1);
        $this->assertEquals(0, $objinst1->get_counter());
        $objinst1->increment_counter(1);
        $this->assertEquals(1, $objinst1->get_counter());

        // And even after reloading.
        $mi = $mr->get_instance($mi->get_id());
        $objinst1 = $mi->get_instance_of_objective($obj1->get('id'));
        $this->assertEquals(1, $objinst1->get_counter());

        // Test increasing beyond needed.
        $objinst1->increment_counter(10);
        $this->assertEquals(5, $objinst1->get_counter());

        // And even after reloading.
        $mi = $mr->get_instance($mi->get_id());
        $objinst1 = $mi->get_instance_of_objective($obj1->get('id'));
        $this->assertEquals(5, $objinst1->get_counter());
    }

    /**
     * Test.
     */
    public function test_increment_counter_with_race_conditions(): void {
        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u = $dg->create_user();
        $mission = $gudg->create_mission_model();
        $obj1 = $gudg->create_objective_model([
            'missionid' => $mission->get('id'),
            'type' => 'manual',
            'countneeded' => 5,
        ]);
        $m = $mr->get_mission($mission->get('id'));

        $mia = $mo->assign_mission($m, $u->id);
        $mib = $mr->get_instance($mia->get_id());

        // Simulate having two requests at once.
        $objinst1a = $mia->get_instance_of_objective($obj1->get('id'));
        $objinst1b = $mib->get_instance_of_objective($obj1->get('id'));
        $this->assertInstanceOf(persisted_objective_instance::class, $objinst1a);
        $this->assertInstanceOf(persisted_objective_instance::class, $objinst1b);

        $this->assertEquals(0, $objinst1a->get_counter());
        $this->assertEquals(0, $objinst1b->get_counter());
        $objinst1a->increment_counter(1);
        $this->assertEquals(1, $objinst1a->get_counter());
        $this->assertEquals(0, $objinst1b->get_counter()); // Still outdated, that's OK.
        $objinst1b->increment_counter(1);
        $this->assertEquals(1, $objinst1a->get_counter()); // Still outdated, that's OK.
        $this->assertEquals(2, $objinst1b->get_counter());

        // And after reloading.
        $mia = $mr->get_instance($mia->get_id());
        $this->assertEquals(2, $mia->get_instance_of_objective($obj1->get('id'))->get_counter());

        // Increasing beyond needed.
        $mia = $mr->get_instance($mia->get_id());
        $mib = $mr->get_instance($mia->get_id());
        $objinst1a = $mia->get_instance_of_objective($obj1->get('id'));
        $objinst1b = $mib->get_instance_of_objective($obj1->get('id'));
        $this->assertEquals(2, $objinst1a->get_counter());
        $this->assertEquals(2, $objinst1b->get_counter());
        $objinst1a->increment_counter(10);
        $this->assertEquals(5, $objinst1a->get_counter());
        $this->assertEquals(2, $objinst1b->get_counter()); // Still outdated, that's OK.
        $objinst1b->increment_counter(10);
        $this->assertEquals(5, $objinst1a->get_counter());
        $this->assertEquals(5, $objinst1b->get_counter());

        // And after reloading.
        $mia = $mr->get_instance($mia->get_id());
        $this->assertEquals(5, $mia->get_instance_of_objective($obj1->get('id'))->get_counter());
    }

}
