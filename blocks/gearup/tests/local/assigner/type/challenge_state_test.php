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
use block_gearup\local\assigner\type\challenge_state;
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
 * @covers     \block_gearup\local\assigner\type\challenge_state
 */
final class challenge_state_test extends base_testcase {

    /**
     * Provider.
     *
     * @return \Generator
     */
    public static function get_eligible_users_provider(): Generator {
        $succeeded = challenge_state::STATE_SUCCEEDED;
        $failed = challenge_state::STATE_FAILED;
        $finished = challenge_state::STATE_FINISHED;

        yield ['m1', $succeeded, ['u1']];
        yield ['m1', $failed, ['u2']];
        yield ['m1', $finished, ['u1', 'u2']];

        yield ['m2', $succeeded, ['u1', 'u2']];
        yield ['m2', $failed, ['u2']];
        yield ['m2', $finished, ['u1', 'u2']];

        yield ['m3', $succeeded, ['u2']];
        yield ['m3', $failed, ['u2']];
        yield ['m3', $finished, ['u2']];

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
        $oo = di::get('objective_operator');
        $mo = di::get('mission_operator');
        $repo = di::get('repository');

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();

        $m1 = $gudg->create_challenge(['repeatcount' => mission::REPEAT_NEVER]);
        $m2 = $gudg->create_challenge(['repeatcount' => mission::REPEAT_ALWAYS, 'timelimit' => DAYSECS]);
        $m3 = $gudg->create_challenge(['repeatcount' => mission::REPEAT_ALWAYS, 'timelimit' => DAYSECS]);

        $getmissionid = function () use ($m1, $m2, $m3, $mission) {
            return ${$mission}->get_id();
        };

        $targetmission = $gudg->create_persisted_mission();
        $assignermodel = $gudg->create_assigner_model([
            'missionid' => $targetmission->get_id(),
            'type' => 'challenge_state',
            'configdata' => ['missionid' => $getmissionid(), 'state' => $state],
        ]);
        $assigner = new persisted_assigner($assignermodel, di::get('assigner_type_resolver'));

        // User 1 completed mission 1 with success.
        $mi = $mo->assign_mission($m1, $u1->id);
        foreach ($mi->get_objective_instances() as $oi) {
            $oo->increment_instance_counter($oi, 1);
        }
        $mo->evaluate_instance($mi);

        // User 1 ends with success mission 2.
        $mi = $mo->assign_mission($m2, $u1->id);
        foreach ($mi->get_objective_instances() as $oi) {
            $oo->increment_instance_counter($oi, 1);
        }
        $mo->evaluate_instance($mi);
        $mo->end_instance($mi);

        // User 1 starts mission 3
        $mi = $mo->assign_mission($m3, $u1->id);

        // User 2 fails mission 1.
        $mi = $mo->assign_mission($m1, $u2->id);
        $mo->end_instance($mi);

        // User 2 fails then succeeds mission 2.
        $mi = $mo->assign_mission($m2, $u2->id);
        $mo->end_instance($mi);
        $miid = $DB->get_field(mission_inst::TABLE, 'id', [
            'missionid' => $m2->get_id(),
            'subjectid' => $u2->id,
            'iteration' => 1,
        ]);
        $mi = $repo->get_instance($miid);
        foreach ($mi->get_objective_instances() as $oi) {
            $oo->increment_instance_counter($oi, 1);
        }
        $mo->evaluate_instance($mi);
        $mo->end_instance($mi);

        // User 2 succeeds then fails mission 3.
        $mi = $mo->assign_mission($m3, $u2->id);
        foreach ($mi->get_objective_instances() as $oi) {
            $oo->increment_instance_counter($oi, 1);
        }
        $mo->evaluate_instance($mi);
        $mo->end_instance($mi);
        $miid = $DB->get_field(mission_inst::TABLE, 'id', [
            'missionid' => $m3->get_id(),
            'subjectid' => $u2->id,
            'iteration' => 1,
        ]);
        $mi = $repo->get_instance($miid);
        $mo->end_instance($mi);

        $expecteduserids = array_map(function ($user) use ($u1, $u2) {
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
        $challenge = $gudg->create_challenge(['contextid' => $c1ctx->id]);
        $achievement = $gudg->create_achievement([
            'contextid' => $c1ctx->id,
            'assigners' => [[
                'type' => 'challenge_state',
                'configdata' => ['missionid' => $challenge->get_id(), 'state' => challenge_state::STATE_FINISHED],
            ]],
        ]);

        $this->setAdminUser();
        $backupid = $this->backup($c1);

        $newid = restore_dbops::create_new_course($c1->fullname . 'new', $c1->shortname . 'new', $c1->category);
        $newctx = context_course::instance($newid);
        $this->restore($backupid, $newid, backup::TARGET_NEW_COURSE, ['users' => false]);

        $missions = iterator_to_array($mr->get_missions_from_query((new mission_query($newctx))->set_context_id($newctx->id)));
        $newchallenge = null;
        $newachievement = null;
        foreach ($missions as $row) {
            $mission = $row->mission;
            if ($mission->get_title() === $challenge->get_title()) {
                $newchallenge = $mission;
            } else if ($mission->get_title() === $achievement->get_title()) {
                $newachievement = $mission;
            }
        }
        $this->assertNotNull($newchallenge);
        $this->assertNotNull($newachievement);
        $this->assertNotEquals($achievement->get_id(), $newachievement->get_id());

        $assigners = $mr->get_assigners($newachievement->get_id());
        $this->assertCount(1, $assigners);
        $this->assertEquals(challenge_state::class, get_class($assigners[0]->get_type()));
        $this->assertEquals($newchallenge->get_id(), $assigners[0]->get_type_config()->missionid);
    }

    /**
     * Provider.
     *
     * @return \Generator
     */
    public static function observe_state_changes_provider(): Generator {
        $succeeded = challenge_state::STATE_SUCCEEDED;
        $failed = challenge_state::STATE_FAILED;
        $finished = challenge_state::STATE_FINISHED;

        yield [['c1ctx', 'c1ctx'], $succeeded, 'success', true];
        yield [['c1ctx', 'c1ctx'], $failed, 'success', false];
        yield [['c1ctx', 'c1ctx'], $finished, 'success', true];

        yield [['c1ctx', 'c1ctx'], $succeeded, 'fail', false];
        yield [['c1ctx', 'c1ctx'], $failed, 'fail', true];
        yield [['c1ctx', 'c1ctx'], $finished, 'fail', true];

        yield [['c1ctx', 'c1ctx'], $succeeded, 'start', false];
        yield [['c1ctx', 'c1ctx'], $failed, 'start', false];
        yield [['c1ctx', 'c1ctx'], $finished, 'start', false];

        yield [['c1ctx', 'sysctx'], $finished, 'success', true];
        yield [['sysctx', 'c1ctx'], $finished, 'success', true];
        yield [['sysctx', 'c2ctx'], $finished, 'success', true];
        yield [['c1ctx', 'c2ctx'], $finished, 'success', false];
    }

    /**
     * Test.
     *
     * @param string[] $missionctxs
     * @param mixed $state
     * @param int $op
     * @param bool $isrecruited
     * @dataProvider observe_state_changes_provider
     */
    public function test_observe_state_changes($missionctxs, $state, $op, $isrecruited): void {
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

        // The mission that advances and should affect the target mission.
        $mission = $gudg->create_challenge(['repeatmode' => mission::REPEAT_ALWAYS,
            'timelimit' => DAYSECS, 'contextid' => $missionctx->id]);

        // The mission on which the assigner is set.
        $targetmission = $gudg->create_persisted_mission(['contextid' => $targetmissionctx->id]);
        $assignermodel = $gudg->create_assigner_model([
            'missionid' => $targetmission->get_id(),
            'type' => 'challenge_state',
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

        if ($op === 'success') {
            foreach ($mi->get_objective_instances() as $oi) {
                di::get('objective_operator')->increment_instance_counter($oi, 1);
            }
            $mo->evaluate_instance($mi);

        } else if ($op === 'fail') {
            $mo->end_instance($mi);
        }

        $this->assertEquals($isrecruited, mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u1->id]
        ));
        $this->assertFalse(mission_inst::record_exists_select(
            'missionid = ? AND subjectid = ?',
            [$targetmission->get_id(), $u2->id]
        ));
    }

}
