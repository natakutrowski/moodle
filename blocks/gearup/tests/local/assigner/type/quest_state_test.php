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

namespace block_gearup\local\assigner\type;

use backup;
use block_gearup\di;
use block_gearup\local\assigner\persisted_assigner;
use block_gearup\local\assigner\type\quest_state;
use block_gearup\local\mission\mission;
use block_gearup\local\model\mission_inst;
use block_gearup\local\repository\mission_query;
use block_gearup\tests\base_testcase;
use context_course;
use context_system;
use Generator;
use restore_dbops;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\assigner\type\quest_state
 */
final class quest_state_test extends base_testcase {

    /**
     * Provider.
     *
     * @return \Generator
     */
    public static function get_eligible_users_provider(): Generator {
        $hasstarted = quest_state::STATE_HAS_STARTED;
        $hascompleted = quest_state::STATE_HAS_COMPLETED;
        $isended = quest_state::STATE_IS_ENDED;

        yield ['m1', $hasstarted, ['u1', 'u4']];
        yield ['m1', $hascompleted, ['u1']];
        yield ['m1', $isended, ['u1']];

        yield ['m2', $hasstarted, ['u1', 'u2']];
        yield ['m2', $hascompleted, ['u1']];
        yield ['m2', $isended, ['u1']];

        yield ['m3', $hasstarted, ['u2', 'u3']];
        yield ['m3', $hascompleted, ['u3']];
        yield ['m3', $isended, []];

        yield ['m1', 0, []];
        yield ['m2', null, []];
        yield ['m3', 'abc', []];
    }

    /**
     * Test.
     *
     * @param string $mission
     * @param mixed $state
     * @param string[] $expectedusers
     * @dataProvider get_eligible_users_provider
     */
    public function test_get_eligible_users(string $mission, $state, array $expectedusers): void {
        global $DB;

        $gudg = $this->generator;

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();
        $u4 = $this->getDataGenerator()->create_user();
        $u5 = $this->getDataGenerator()->create_user();

        $m1 = $gudg->create_quest(['startmode' => mission::START_ALWAYS]);
        $m2 = $gudg->create_quest(['startmode' => mission::START_OPTIN]);
        $m3 = $gudg->create_quest(['startmode' => mission::START_OPTIN, 'visibility' => mission::VISIBLE_SECRET]);

        $getmissionid = function () use ($m1, $m2, $m3, $mission) {
            return ${$mission}->get_id();
        };

        $targetmission = $gudg->create_persisted_mission();
        $assignermodel = $gudg->create_assigner_model([
            'missionid' => $targetmission->get_id(),
            'type' => 'quest_state',
            'configdata' => ['missionid' => $getmissionid(), 'state' => $state],
        ]);
        $assigner = new persisted_assigner($assignermodel, di::get('assigner_type_resolver'));

        $mo = di::get('mission_operator');

        // User 1 ends mission 1 and 2.
        foreach ([$m1, $m2] as $m) {
            $mi = $mo->assign_mission($m, $u1->id);
            if ($m->get_start_mode() !== mission::START_ALWAYS) {
                $mo->start_instance($mi);
            }
            $mo->complete_instance($mi);
            $mo->finish_instance($mi);
        }

        // User 2 starts mission 2 and 3.
        foreach ([$m2, $m3] as $m) {
            $mi = $mo->assign_mission($m, $u2->id);
            if ($m->get_start_mode() !== mission::START_ALWAYS) {
                $mo->start_instance($mi);
            }
        }

        // User 3 completes the missions.
        foreach ([$m3] as $m) {
            $mi = $mo->assign_mission($m, $u3->id);
            if ($m->get_start_mode() !== mission::START_ALWAYS) {
                $mo->start_instance($mi);
            }
            $mo->complete_instance($mi);
        }

        // User 4 is just assigned.
        foreach ([$m1, $m3] as $m) {
            $mi = $mo->assign_mission($m, $u4->id);
        }

        $expecteduserids = array_map(function ($user) use ($u1, $u2, $u3, $u4, $u5) {
            return ${$user}->id;
        }, $expectedusers);

        [$sql, $params] = $assigner->get_type()->get_elligible_users_sql($assigner, $targetmission);
        $userids = $DB->get_fieldset_sql($sql, $params);

        sort($userids);
        sort($expecteduserids);
        $this->assertEquals($expecteduserids, $userids);
    }

    /**
     * Update after restore.
     */
    public function test_update_after_restore(): void {
        $mr = di::get('repository');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $c1 = $dg->create_course();
        $c1ctx = context_course::instance($c1->id);

        $this->add_block_to_course($c1->id);
        $quest = $gudg->create_quest(['contextid' => $c1ctx->id]);
        $achievement = $gudg->create_achievement([
            'contextid' => $c1ctx->id,
            'assigners' => [[
                'type' => 'quest_state',
                'configdata' => ['missionid' => $quest->get_id(), 'state' => quest_state::STATE_HAS_STARTED],
            ]],
        ]);

        $this->setAdminUser();
        $backupid = $this->backup($c1);

        $newid = restore_dbops::create_new_course($c1->fullname . 'new', $c1->shortname . 'new', $c1->category);
        $newctx = context_course::instance($newid);
        $this->restore($backupid, $newid, backup::TARGET_NEW_COURSE, ['users' => false]);

        $missions = iterator_to_array($mr->get_missions_from_query((new mission_query($newctx))->set_context_id($newctx->id)));
        $newquest = null;
        $newachievement = null;
        foreach ($missions as $row) {
            $mission = $row->mission;
            if ($mission->get_title() === $quest->get_title()) {
                $newquest = $mission;
            } else if ($mission->get_title() === $achievement->get_title()) {
                $newachievement = $mission;
            }
        }
        $this->assertNotNull($newquest);
        $this->assertNotNull($newachievement);
        $this->assertNotEquals($achievement->get_id(), $newachievement->get_id());

        $assigners = $mr->get_assigners($newachievement->get_id());
        $this->assertCount(1, $assigners);
        $this->assertEquals(quest_state::class, get_class($assigners[0]->get_type()));
        $this->assertEquals($newquest->get_id(), $assigners[0]->get_type_config()->missionid);
    }

    /**
     * Provider.
     *
     * @return \Generator
     */
    public static function observe_state_changes_provider(): Generator {
        $hasstarted = quest_state::STATE_HAS_STARTED;
        $hascompleted = quest_state::STATE_HAS_COMPLETED;
        $isended = quest_state::STATE_IS_ENDED;

        yield [['c1ctx', 'c1ctx'], $hasstarted, 4, [false, true, true, true]];
        yield [['c1ctx', 'c1ctx'], $hascompleted, 4, [false, false, true, true]];
        yield [['c1ctx', 'c1ctx'], $isended, 4, [false, false, false, true]];

        yield [['c1ctx', 'c1ctx'], $hasstarted, 3, [false, true, true, true]];
        yield [['c1ctx', 'c1ctx'], $hascompleted, 3, [false, false, true, true]];
        yield [['c1ctx', 'c1ctx'], $isended, 3, [false, false, false, false]];

        yield [['c1ctx', 'c1ctx'], $hasstarted, 2, [false, true, true, true]];
        yield [['c1ctx', 'c1ctx'], $hascompleted, 2, [false, false, false, false]];
        yield [['c1ctx', 'c1ctx'], $isended, 2, [false, false, false, false]];

        yield [['c1ctx', 'c1ctx'], $hasstarted, 1, [false, false, false, false]];
        yield [['c1ctx', 'c1ctx'], $hascompleted, 1, [false, false, false, false]];
        yield [['c1ctx', 'c1ctx'], $isended, 1, [false, false, false, false]];

        yield [['c1ctx', 'sysctx'], $hasstarted, 4, [false, true, true, true]];
        yield [['sysctx', 'c1ctx'], $hasstarted, 4, [false, true, true, true]];
        yield [['sysctx', 'c2ctx'], $hasstarted, 4, [false, true, true, true]];
        yield [['c1ctx', 'c2ctx'], $hasstarted, 4, [false, false, false, false]];
    }

    /**
     * Test.
     *
     * @param string[] $missionctxs
     * @param mixed $state
     * @param int $opnum
     * @param array $expectedstates
     * @dataProvider observe_state_changes_provider
     */
    public function test_observe_state_changes($missionctxs, $state, int $opnum, $expectedstates): void {
        $gudg = $this->generator;
        $mo = di::get('mission_operator');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($u1->id, $c1->id);
        $this->getDataGenerator()->enrol_user($u2->id, $c1->id);
        $this->getDataGenerator()->enrol_user($u1->id, $c2->id);
        $this->getDataGenerator()->enrol_user($u2->id, $c2->id);

        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);

        $missionctx = ${$missionctxs[0]};
        $targetmissionctx = ${$missionctxs[1]};
        [$assignstate, $startstate, $completestate, $endstate] = $expectedstates;

        // The mission that advances and should affect the target mission.
        $mission = $gudg->create_quest(['startmode' => mission::START_OPTIN, 'contextid' => $missionctx->id]);

        // The mission on which the assigner is set.
        $targetmission = $gudg->create_persisted_mission(['contextid' => $targetmissionctx->id]);
        $assignermodel = $gudg->create_assigner_model([
            'missionid' => $targetmission->get_id(),
            'type' => 'quest_state',
            'configdata' => ['missionid' => $mission->get_id(), 'state' => $state],
        ]);

        $this->assertFalse(mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u1->id]
        ));
        $this->assertFalse(mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u2->id]
        ));

        $mi = $mo->assign_mission($mission, $u1->id);
        $mi2 = $mo->assign_mission($mission, $u2->id);

        $this->assertEquals($assignstate, mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u1->id]
        ));
        $this->assertFalse(mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u2->id]
        ));

        if ($opnum >= 2) {
            $mo->start_instance($mi);
        }

        $this->assertEquals($startstate, mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u1->id]
        ));
        $this->assertFalse(mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u2->id]
        ));

        if ($opnum >= 3) {
            $mo->complete_instance($mi);
        }

        $this->assertEquals($completestate, mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u1->id]
        ));
        $this->assertFalse(mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u2->id]
        ));

        if ($opnum >= 4) {
            $mo->finish_instance($mi);
        }

        $this->assertEquals($endstate, mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u1->id]
        ));
        $this->assertFalse(mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u2->id]
        ));
    }

}
