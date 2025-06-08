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

namespace block_gearup\tests\outcome\type;

use block_gearup\di;
use block_gearup\local\outcome\type\xp;
use block_gearup\tests\base_testcase;
use context_course;

/**
 * Tests.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class xp_test extends base_testcase {

    public function setUp(): void {
        parent::setUp();
        if (!class_exists('block_xp\di')) {
            $this->markTestSkipped('XP is not installed');
        }
    }

    /**
     * Test the apply method.
     *
     * @covers \block_gearup\outcome\type\xp::apply
     */
    public function test_apply(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $mo = di::get('mission_operator');
        $mr = di::get('repository');

        $c1 = $dg->create_course();
        $c1ctx = context_course::instance($c1->id);
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $dg->enrol_user($u1->id, $c1->id, 'student');
        $dg->enrol_user($u2->id, $c1->id, 'student');
        $world = \block_xp\di::get('context_world_factory')->get_world_from_context($c1ctx);
        $world->get_config()->set('enabled', true);

        $mission = $gudg->create_quest([
            'contextid' => $c1ctx->id,
            'outcomes' => [
                [
                    'type' => 'xp',
                    'configdata' => ['points' => 10],
                ],
            ],
        ]);
        $missioninst1 = $mo->assign_mission($mission, $u1->id);
        $missioninst2 = $mo->assign_mission($mission, $u2->id);

        $this->assertEquals(0, $world->get_store()->get_state($u1->id)->get_xp());
        $this->assertEquals(0, $world->get_store()->get_state($u2->id)->get_xp());

        $outcome = array_values($mr->get_outcomes($mission->get_id()))[0];
        $this->assertInstanceOf(xp::class, $outcome->get_type());

        $outcome->get_type()->apply($outcome, $missioninst1);
        $this->assertEquals(10, $world->get_store()->get_state($u1->id)->get_xp());
        $this->assertEquals(0, $world->get_store()->get_state($u2->id)->get_xp());
    }

}
