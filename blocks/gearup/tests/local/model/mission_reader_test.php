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

use block_gearup\local\mission\mission;
use block_gearup\local\model\mission_reader;
use block_gearup\local\model\mission as mission_model;
use block_gearup\local\repository\mission_query;
use block_gearup\tests\base_testcase;
use context_system;

/**
 * Tests.
 *
 * @package    block_gearup
 * @category   test
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mission_reader_test extends base_testcase {

    /**
     * Test filtering active.
     *
     * @covers \block_gearup\local\repository\mission_query::filter_active
     * @covers \block_gearup\local\model\mission_reader
     */
    public function test_filter_active(): void {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $m1 = $gudg->create_achievement(['state' => mission::STATE_WIZARD]);
        $m2 = $gudg->create_achievement(['state' => mission::STATE_ACTIVE]);
        $m3 = $gudg->create_achievement(['state' => mission::STATE_ARCHIVED]);

        $query = (new mission_query(context_system::instance()))->filter_active();
        $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assertEquals($m2->get_id(), iterator_to_array($reader->list())[0][mission_model::class]->get('id'));
    }

    /**
     * Test set state.
     *
     * @covers \block_gearup\local\repository\mission_query::set_state
     * @covers \block_gearup\local\model\mission_reader
     */
    public function test_set_state(): void {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $m1 = $gudg->create_achievement(['state' => mission::STATE_WIZARD]);
        $m2 = $gudg->create_achievement(['state' => mission::STATE_ACTIVE]);
        $m3 = $gudg->create_achievement(['state' => mission::STATE_ARCHIVED]);

        $query = (new mission_query(context_system::instance()))->set_state(mission::STATE_ARCHIVED);
        $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assertEquals($m3->get_id(), iterator_to_array($reader->list())[0][mission_model::class]->get('id'));

        $query = (new mission_query(context_system::instance()))->set_state(mission::STATE_ACTIVE);
        $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assertEquals($m2->get_id(), iterator_to_array($reader->list())[0][mission_model::class]->get('id'));
    }

    /**
     * Test set repeat_count.
     *
     * @covers \block_gearup\local\repository\mission_query::set_repeat_count
     * @covers \block_gearup\local\model\mission_reader
     */
    public function test_set_repeat_count(): void {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $m1 = $gudg->create_challenge(['repeatcount' => mission::REPEAT_ALWAYS]);
        $m2 = $gudg->create_challenge(['repeatcount' => mission::REPEAT_NEVER]);

        $query = (new mission_query(context_system::instance()))->set_repeat_count(mission::REPEAT_NEVER);
        $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assertEquals($m2->get_id(), iterator_to_array($reader->list())[0][mission_model::class]->get('id'));

        $query = (new mission_query(context_system::instance()))->set_repeat_count(mission::REPEAT_ALWAYS);
        $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assertEquals($m1->get_id(), iterator_to_array($reader->list())[0][mission_model::class]->get('id'));
    }

}
