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

namespace block_gearup\local\model;

use block_gearup\local\mission\mission_instance;
use block_gearup\local\model\user_reader;
use block_gearup\local\repository\user_query;
use block_gearup\tests\base_testcase;
use context_system;

/**
 * User reader tests.
 *
 * @package    block_gearup
 * @category   test
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_reader_test extends base_testcase {

    /**
     * Validate annotation.
     *
     * @covers \block_gearup\local\repository\user_query
     */
    public function test_mission_instance_counter_latest(): void {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $s1 = $gudg->create_streak();
        $gudg->create_persisted_mission_instance($s1, [
            'timestarted' => time() - 100,
            'missionid' => $s1->get_id(),
            'subjectid' => $u1->id,
            'counter' => 10,
            'state' => mission_instance::STATE_COMPLETED,
            'timecompleted' => time() - 99,
            'timeended' => time() - 98,
        ]);
        $gudg->create_persisted_mission_instance($s1, [
            'timestarted' => time() - 95,
            'missionid' => $s1->get_id(),
            'subjectid' => $u1->id,
            'counter' => 5,
            'state' => mission_instance::STATE_STARTED,
            'iteration' => 1,
        ]);
        $gudg->create_persisted_mission_instance($s1, [
            'timestarted' => time() - 2,
            'missionid' => $s1->get_id(),
            'subjectid' => $u2->id,
            'counter' => 7,
            'state' => mission_instance::STATE_COMPLETED,
            'timecompleted' => time() - 1,
            'timeended' => time(),
        ]);

        $query = (new user_query(context_system::instance()))
            ->set_mission_id($s1->get_id())
            ->add_order_by('id', SORT_ASC)
            ->annotate_mission_instance_counter_latest();
        $reader = (new user_reader())->use_query($query);

        [$u1result, $u2result] = iterator_to_array($reader->list(0, 2));
        $this->assertEquals(5, $u1result->mission_instance_counter_latest);
        $this->assertEquals(7, $u2result->mission_instance_counter_latest);
    }

    /**
     * Validate annotation with edge-case of timestarted clash.
     *
     * @covers \block_gearup\local\repository\user_query
     */
    public function test_mission_instance_counter_latest_conflicting_timestarted(): void {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $s1 = $gudg->create_streak();
        $gudg->create_persisted_mission_instance($s1, [
            'timestarted' => time() - 100,
            'missionid' => $s1->get_id(),
            'subjectid' => $u1->id,
            'counter' => 10,
            'state' => mission_instance::STATE_COMPLETED,
            'timecompleted' => time() - 99,
            'timeended' => time() - 98,
        ]);
        $gudg->create_persisted_mission_instance($s1, [
            'timestarted' => time() - 100,
            'missionid' => $s1->get_id(),
            'subjectid' => $u1->id,
            'counter' => 5,
            'state' => mission_instance::STATE_STARTED,
            'iteration' => 1,
        ]);

        $query = (new user_query(context_system::instance()))
            ->set_mission_id($s1->get_id())
            ->add_order_by('id', 'ASC')
            ->annotate_mission_instance_counter_latest();
        $reader = (new user_reader())->use_query($query);

        $results = iterator_to_array($reader->list(0, 2));
        $u1result = $results[0];
        $this->assertCount(1, $results);
        $this->assertEquals(5, $u1result->mission_instance_counter_latest);
    }

}
