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

namespace block_gearup\assigner;

use block_gearup\di;
use block_gearup\local\mission\mission;
use block_gearup\tests\base_testcase;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @category   test
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class processor_test extends base_testcase {

    public function test_process_all(): void {
        $mr = di::get('repository');
        $ao = di::get('assigner_processor');

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $admin = get_admin();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();

        $m1 = $gudg->create_persisted_mission([
            'objectives' => [['type' => 'manual', 'countneeded' => 2]],
            'assigners' => [['type' => 'everyone', 'enabled' => true]],
        ]);
        $m2 = $gudg->create_persisted_mission([
            'state' => mission::STATE_WIZARD,
            'objectives' => [['type' => 'manual', 'countneeded' => 2]],
            'assigners' => [['type' => 'everyone', 'enabled' => true]],
        ]);

        $ao->process_all();

        $m1is = $mr->get_instances($m1->get_id());
        $m1uids = array_map(fn($mi) => $mi->get_subject_id(), $m1is);
        sort($m1uids);
        $this->assertCount(4, $m1uids);
        $this->assertEquals([$admin->id, $u1->id, $u2->id, $u3->id], $m1uids);

        $m2is = $mr->get_instances($m2->get_id());
        $this->assertCount(0, $m2is);
    }

}
