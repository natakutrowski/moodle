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

namespace block_gearup\tests\repository;

use block_gearup\di;
use block_gearup\local\mission\achievement;
use block_gearup\local\mission\challenge;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\mission\quest;
use block_gearup\local\mission\streak;
use block_gearup\local\model\mission as mission_model;
use block_gearup\local\objective\type\access_activity;
use block_gearup\local\objective\type\access_course;
use block_gearup\local\objective\type\access_platform;
use block_gearup\local\objective\type\complete_activity;
use block_gearup\local\objective\type\complete_course;
use block_gearup\local\objective\type\complete_section;
use block_gearup\local\objective\type\manual;
use block_gearup\tests\base_testcase;
use context_course;
use context_coursecat;
use context_module;
use context_system;
use context_user;
use core_collator;
use DateTimeImmutable;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class repository_test extends base_testcase {

    public function test_get_incomplete_objective_instances_of_types(): void {
        $mr = di::get('repository');
        $mh = di::get('mission_helper');
        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();
        $u5 = $dg->create_user();
        $u6 = $dg->create_user();

        $m1 = $gudg->create_persisted_mission(['objectives' => [
            ['type' => 'manual', 'countneeded' => 1],
            ['type' => 'manual', 'countneeded' => 2],
            ['type' => 'access_platform', 'countneeded' => 3],
        ]]);
        $m2 = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_QUEST,
            'startmode' => mission::START_OPTIN,
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);

        $mi1u1 = $mo->assign_mission($m1, $u1->id);
        $mi1u2 = $mo->assign_mission($m1, $u2->id);
        $mi1u3 = $mo->assign_mission($m1, $u3->id);
        $mi1u4 = $mo->assign_mission($m1, $u4->id);
        $mi2u5 = $mo->assign_mission($m2, $u5->id);
        $mi2u6 = $mo->assign_mission($m2, $u6->id);

        // Complete mission 1 for user 2.
        $oo->increment_instance_counter($mi1u2->get_objective_instances()[0], 1);
        $oo->increment_instance_counter($mi1u2->get_objective_instances()[1], 2);
        $oo->increment_instance_counter($mi1u2->get_objective_instances()[2], 3);
        $mo->evaluate_instance($mi1u2);
        // Complete mission 1 for user 3 without completing objectives.
        $mo->complete_instance($mi1u3);
        // End mission 1 for user 4 without completing objectives.
        $mo->end_instance($mi1u4);
        // Start mission 2 for user 6.
        $mo->start_instance($mi2u6);

        // Validate setup.
        $this->assertFalse($mh->has_completed($mi1u1));
        $this->assertTrue($mh->has_completed($mi1u2));
        $this->assertTrue($mh->has_completed($mi1u3));
        $this->assertTrue($mh->has_completed($mi1u4));
        $this->assertFalse($mh->has_completed($mi2u5));
        $this->assertFalse($mh->has_completed($mi2u6));

        // Check that we retrieve any objective for user 1.
        $ois = $mr->get_incomplete_objective_instances_of_types([new manual()], $u1->id, $m1->get_context());
        $this->assertCount(2, $ois);
        $ois = $mr->get_incomplete_objective_instances_of_types([new manual(), new access_platform()], $u1->id,
            $m1->get_context());
        $this->assertCount(3, $ois);
        $ois = $mr->get_incomplete_objective_instances_of_types([new access_platform()], $u1->id, $m1->get_context());
        $this->assertCount(1, $ois);

        // Nothing for u2, u3, u4, u5.
        $ois = $mr->get_incomplete_objective_instances_of_types([new manual()], $u2->id, $m1->get_context());
        $this->assertCount(0, $ois);
        $ois = $mr->get_incomplete_objective_instances_of_types([new manual()], $u3->id, $m1->get_context());
        $this->assertCount(0, $ois);
        $ois = $mr->get_incomplete_objective_instances_of_types([new manual()], $u4->id, $m1->get_context());
        $this->assertCount(0, $ois);
        $ois = $mr->get_incomplete_objective_instances_of_types([new manual()], $u5->id, $m1->get_context());
        $this->assertCount(0, $ois);

        // User 6 has started and has 1.
        $ois = $mr->get_incomplete_objective_instances_of_types([new manual()], $u6->id, $m1->get_context());
        $this->assertCount(1, $ois);

        // Complete one objective for user 1.
        $oo->increment_instance_counter($mi1u1->get_objective_instances()[0], 1);
        $mo->evaluate_instance($mi1u1);
        $ois = $mr->get_incomplete_objective_instances_of_types([new manual()], $u1->id, $m1->get_context());
        $this->assertCount(1, $ois);
        $ois = $mr->get_incomplete_objective_instances_of_types([new manual(), new access_platform()], $u1->id,
            $m1->get_context());
        $this->assertCount(2, $ois);
        $ois = $mr->get_incomplete_objective_instances_of_types([new access_platform()], $u1->id, $m1->get_context());
        $this->assertCount(1, $ois);
    }

    public function test_get_incomplete_objective_instances_of_types_with_contexts(): void {
        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();

        $cat1 = $dg->create_category();
        $cat2 = $dg->create_category();
        $cat3 = $dg->create_category();
        $c1a = $dg->create_course(['category' => $cat1->id]);
        $c1b = $dg->create_course(['category' => $cat1->id]);
        $c2a = $dg->create_course(['category' => $cat2->id]);
        $cm1a = $dg->create_module('page', ['course' => $c1a->id]);

        $m1 = $gudg->create_persisted_mission([
            'title' => 'System',
            'objectives' => [['type' => 'manual', 'countneeded' => 1, 'label' => 'sys']],
            'contextid' => SYSCONTEXTID,
        ]);
        $m2 = $gudg->create_persisted_mission([
            'title' => 'Course 1a',
            'objectives' => [['type' => 'manual', 'countneeded' => 1, 'label' => 'c1a']],
            'contextid' => context_course::instance($c1a->id)->id,
        ]);
        $m3 = $gudg->create_persisted_mission([
            'title' => 'Course 1b',
            'objectives' => [['type' => 'manual', 'countneeded' => 1, 'label' => 'c1b']],
            'contextid' => context_course::instance($c1b->id)->id,
        ]);
        $m4 = $gudg->create_persisted_mission([
            'title' => 'Course 2a',
            'objectives' => [['type' => 'manual', 'countneeded' => 1, 'label' => 'c2a']],
            'contextid' => context_course::instance($c2a->id)->id,
        ]);

        $mo->assign_mission($m1, $u1->id);
        $mo->assign_mission($m2, $u1->id);
        $mo->assign_mission($m3, $u1->id);
        $mo->assign_mission($m4, $u1->id);

        // Fetch from context system. This returns all objectives.
        $types = [new manual()];
        $ois = $mr->get_incomplete_objective_instances_of_types($types, $u1->id, context_system::instance());
        $this->assertCount(4, $ois);

        // Fetch from user context. Only system one.
        $ois = $mr->get_incomplete_objective_instances_of_types($types, $u1->id, context_user::instance($u1->id));
        $this->assertCount(1, $ois);

        // Fetch from orphan category. Only system one.
        $ois = $mr->get_incomplete_objective_instances_of_types($types, $u1->id, context_coursecat::instance($cat3->id));
        $this->assertCount(1, $ois);
        $this->assertEquals('sys', reset($ois)->get_objective()->get_label());

        // Fetch from category 1. All but c2a.
        $ois = $mr->get_incomplete_objective_instances_of_types($types, $u1->id, context_coursecat::instance($cat1->id));
        $this->assertCount(3, $ois);
        $labels = array_map(function($oi) {
            return $oi->get_objective()->get_label();
        }, $ois);
        core_collator::asort($labels);
        $this->assertEquals(['c1a', 'c1b', 'sys'], array_values($labels));

        // Fetch from course 1a.
        $ois = $mr->get_incomplete_objective_instances_of_types($types, $u1->id, context_course::instance($c1a->id));
        $this->assertCount(2, $ois);
        $labels = array_map(function($oi) {
            return $oi->get_objective()->get_label();
        }, $ois);
        core_collator::asort($labels);
        $this->assertEquals(['c1a', 'sys'], array_values($labels));

        // Fetch from cm 1a.
        $ois = $mr->get_incomplete_objective_instances_of_types($types, $u1->id, context_module::instance($cm1a->cmid));
        $this->assertCount(2, $ois);
        $labels = array_map(function($oi) {
            return $oi->get_objective()->get_label();
        }, $ois);
        core_collator::asort($labels);
        $this->assertEquals(['c1a', 'sys'], array_values($labels));
    }

    public function test_get_incomplete_objective_instances_of_types_with_dormant_until(): void {
        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();

        $m1 = $gudg->create_persisted_mission(['objectives' => [['type' => 'manual', 'countneeded' => 1, 'label' => 'm1']]]);
        $m2 = $gudg->create_persisted_mission(['objectives' => [['type' => 'manual', 'countneeded' => 1, 'label' => 'm2']]]);
        $m3 = $gudg->create_persisted_mission(['objectives' => [['type' => 'manual', 'countneeded' => 1, 'label' => 'm3']]]);
        $m4 = $gudg->create_persisted_mission(['objectives' => [['type' => 'manual', 'countneeded' => 1, 'label' => 'm4']]]);

        $mi1 = $mo->assign_mission($m1, $u1->id);
        $mi2 = $mo->assign_mission($m2, $u1->id);
        $mi3 = $mo->assign_mission($m3, $u1->id);
        $mi4 = $mo->assign_mission($m4, $u1->id);

        $mi1->get_objective_instances()[0]->set_dormant_until(di::get('clock')->now()->modify('+1 hour'));
        $mi2->get_objective_instances()[0]->set_dormant_until(di::get('clock')->now()->modify('-1 hour'));
        $mi3->get_objective_instances()[0]->set_dormant_until(new DateTimeImmutable('@0'));
        $mi4->get_objective_instances()[0]->set_dormant_until(null);

        // All but the dormant one.
        $types = [new manual()];
        $ois = $mr->get_incomplete_objective_instances_of_types($types, $u1->id, context_system::instance());
        $this->assertCount(3, $ois);
        $labels = array_map(function($oi) {
            return $oi->get_objective()->get_label();
        }, $ois);
        core_collator::asort($labels);
        $this->assertEquals(['m2', 'm3', 'm4'], array_values($labels));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public static function get_incomplete_objective_instance_type_names_amongst_types_provider(): array {
        $othertypes = ['manual', 'complete_activity', 'complete_course', 'complete_section'];
        $withothers = function($a) use ($othertypes) {
            return array_unique(array_merge($a, $othertypes));
        };

        return [
            // Fetching in another context.
            ['c1', 'c3', 'c3', null, null, []], // Nothing.
            ['c2', 'c3', 'c3', null, null, []], // Nothing.
            ['cat1', 'c3', 'c3', null, null, []], // Nothing.
            ['cat2', 'c1', 'c1', null, null, []], // Nothing.

            // Fetching in context path.
            ['sys', 'c1', 'c1', null, null, $withothers(['manual', 'access_activity', 'access_course', 'access_platform'])],
            ['sys', 'c1', 'c2', null, null, $withothers(['manual', 'access_activity', 'access_course', 'access_platform'])],
            ['sys', 'c2', 'c3', null, null, $withothers(['manual', 'access_activity', 'access_course', 'access_platform'])],

            ['cat1', 'c1', 'c1', null, null, $withothers(['manual', 'access_activity', 'access_course', 'access_platform'])],
            ['cat1', 'c1', 'c2', null, null, $withothers(['manual', 'access_activity', 'access_course', 'access_platform'])],
            ['cat1', 'c2', 'c3', null, null, ['manual', 'access_activity', 'access_course', 'access_platform']],
            ['cat1', 'sys', 'c3', null, null, ['manual', 'access_activity', 'access_course', 'access_platform']],

            ['c1', 'c1', 'c1', null, null, $withothers(['manual', 'access_activity', 'access_course', 'access_platform'])],
            ['c1', 'c1', 'sys', null, null, $withothers(['manual', 'access_activity', 'access_course', 'access_platform'])],
            ['c1', 'c1', 'c2', null, null, ['manual', 'access_activity', 'access_course', 'access_platform']],

            ['cm1', 'c1', 'c1', null, null, $withothers(['manual', 'access_activity', 'access_course', 'access_platform'])],
            ['cm1', 'c1', 'sys', null, null, $withothers(['manual', 'access_activity', 'access_course', 'access_platform'])],
            ['cm1', 'c1', 'c2', null, null, ['manual', 'access_activity', 'access_course', 'access_platform']],

            // Applying completed and ended.
            ['c1', 'c1', 'c2', 'complete', null, []],
            ['c1', 'c1', 'c2', 'end', null, []],

            // Applying completed objectives.
            ['c1', 'c1', 'c2', null, 'allcompleted', []],
            ['c1', 'c1', 'c2', null, 'somecompleted', ['access_course', 'access_platform']],

            // Applying dormant.
            ['c1', 'c1', 'c2', null, 'somedormant', ['access_activity', 'access_course']],
        ];
    }

    /**
     * Test get incomplete objective instance type names amongst types.
     *
     * @covers block_gearup\local\repository\repository::get_incomplete_objective_instance_type_names_amongst_types
     * @dataProvider get_incomplete_objective_instance_type_names_amongst_types_provider
     */
    public function test_get_incomplete_objective_instance_type_names_amongst_types($lookupctxref, $m1ctxref, $m2ctxref,
            ?string $missionmode, ?string $objmode, array $expectednames): void {

        $clock = $this->get_frozen_clock();
        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $cat1 = $dg->create_category();
        $cat2 = $dg->create_category();
        $c1 = $dg->create_course(['category' => $cat1->id]);
        $c2 = $dg->create_course(['category' => $cat1->id]);
        $c3 = $dg->create_course(['category' => $cat2->id]);
        $cm1 = $dg->create_module('page', ['course' => $c1->id]);

        $sysctx = context_system::instance();
        $cat1ctx = context_coursecat::instance($cat1->id);
        $cat2ctx = context_coursecat::instance($cat2->id);
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);
        $c3ctx = context_course::instance($c3->id);
        $cm1ctx = context_module::instance($cm1->cmid);

        $m1ctx = ${$m1ctxref . 'ctx'};
        $m2ctx = ${$m2ctxref . 'ctx'};
        $lookupctx = ${$lookupctxref . 'ctx'};

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $m1 = $gudg->create_persisted_mission([
            'contextid' => $m1ctx->id,
            'objectives' => [
                ['type' => 'manual', 'countneeded' => 1],
                ['type' => 'access_activity', 'countneeded' => 2, 'configdata' => ['which' => 0]],
                ['type' => 'access_course', 'countneeded' => 3, 'configdata' => ['which' => 0]],
                ['type' => 'access_platform', 'countneeded' => 4, 'configdata' => ['which' => 0]],
            ],
        ]);
        $m2 = $gudg->create_persisted_mission([
            'contextid' => $m2ctx->id,
            'objectives' => [
            ['type' => 'manual', 'countneeded' => 1],
            ['type' => 'complete_activity', 'countneeded' => 2, 'configdata' => ['which' => 0]],
            ['type' => 'complete_course', 'countneeded' => 3, 'configdata' => ['which' => 0]],
            ['type' => 'complete_section', 'countneeded' => 4, 'configdata' => ['whichsection' => 0]],
            ]]);

        $mi1 = $mo->assign_mission($m1, $u1->id);
        $mi2 = $mo->assign_mission($m2, $u1->id);

        if ($objmode === 'allcompleted') {
            foreach ($mi1->get_objective_instances() as $oi) {
                $oo->increment_instance_counter($oi, $oi->get_objective()->get_count_needed());
            }
        } else if ($objmode === 'somecompleted') {
            $ois = $mi1->get_objective_instances();
            $oo->increment_instance_counter($ois[0], $ois[0]->get_objective()->get_count_needed());
            $oo->increment_instance_counter($ois[1], $ois[1]->get_objective()->get_count_needed());

        } else if ($objmode === 'somedormant') {
            $ois = $mi1->get_objective_instances();
            $ois[0]->set_dormant_until($clock->now()->modify('+1 hour'));
            $ois[1]->set_dormant_until($clock->now()->modify('-1 hour'));
            $ois[2]->set_dormant_until(null);
            $ois[3]->set_dormant_until($clock->now());

        } else if ($objmode !== null) {
            throw new \coding_exception('Incorreect test.');
        }

        if ($missionmode === 'complete') {
            $mo->complete_instance($mi1);
        } else if ($missionmode === 'end') {
            $mo->end_instance($mi1);
        } else if ($missionmode !== null) {
            throw new \coding_exception('Incorreect test.');
        }

        $alltypes = [
            new manual(),
            new access_activity(),
            new access_course(),
            new access_platform(),
            new complete_activity(),
            new complete_course(),
            new complete_section(),
        ];
        $othertypes = [new complete_activity(), new complete_course(), new complete_section()];

        // The target context returns the expected types.
        $names = $mr->get_incomplete_objective_instance_type_names_amongst_types($alltypes, $u1->id, $lookupctx);
        $this->assertCount(count($expectednames), $names);
        foreach ($expectednames as $expectedname) {
            $this->assertContains($expectedname, $names);
        }

        // Check that filtering by type name works.
        $filteredtypes = [
            new manual(),
            new access_platform(),
            new complete_activity(),
        ];
        $filterednames = array_intersect($expectednames, ['manual', 'access_platform', 'complete_activity']);
        $names = $mr->get_incomplete_objective_instance_type_names_amongst_types($filteredtypes, $u1->id, $lookupctx);
        $this->assertCount(count($filterednames), $names);
        foreach ($filterednames as $expectedname) {
            $this->assertContains($expectedname, $names);
        }

        // Another person's incomplete objectives has no effect.
        $mo->assign_mission($m1, $u2->id);
        $mo->assign_mission($m2, $u2->id);

        $names = $mr->get_incomplete_objective_instance_type_names_amongst_types($alltypes, $u1->id, $lookupctx);
        $this->assertCount(count($expectednames), $names);
        foreach ($expectednames as $expectedname) {
            $this->assertContains($expectedname, $names);
        }

        $names = $mr->get_incomplete_objective_instance_type_names_amongst_types($filteredtypes, $u1->id, $lookupctx);
        $this->assertCount(count($filterednames), $names);
        foreach ($filterednames as $expectedname) {
            $this->assertContains($expectedname, $names);
        }

        // Test that a mission in draft never returns anyway.
        $m1->get_persistent()->set('state', mission::STATE_WIZARD);
        $m1->get_persistent()->save();
        $othernames = array_intersect($expectednames, ['complete_activity', 'complete_course', 'complete_section']);
        $names = $mr->get_incomplete_objective_instance_type_names_amongst_types($othertypes, $u1->id, $lookupctx);
        $this->assertCount(count($othernames), $names);
        foreach ($othernames as $expectedname) {
            $this->assertContains($expectedname, $names);
        }

        // Test that a mission archived never returns anyway.
        $m1->get_persistent()->set('state', mission::STATE_ARCHIVED);
        $m1->get_persistent()->save();
        $othernames = array_intersect($expectednames, ['complete_activity', 'complete_course', 'complete_section']);
        $names = $mr->get_incomplete_objective_instance_type_names_amongst_types($othertypes, $u1->id, $lookupctx);
        $this->assertCount(count($othernames), $names);
        foreach ($othernames as $expectedname) {
            $this->assertContains($expectedname, $names);
        }
    }

    /**
     * Test get current streaks order.
     *
     * @covers block_gearup\local\repository\repository::get_current_streaks
     */
    public function test_get_current_streaks_order(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $now = time();
        $u1 = $dg->create_user();

        $streak1 = $gudg->create_streak();
        $streak2 = $gudg->create_streak();
        $streak3 = $gudg->create_streak();
        $streak4 = $gudg->create_streak();

        $mi1 = $gudg->create_persisted_mission_instance($streak1, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_STARTED,
            'timecreated' => $now - 100,
            'timestarted' => $now - 50,
        ]);

        $mi2 = $gudg->create_persisted_mission_instance($streak2, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_STARTED,
            'timecreated' => $now - 1000,
            'timestarted' => $now - 500,
        ]);

        $mi3 = $gudg->create_persisted_mission_instance($streak3, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_COMPLETED,
            'timecreated' => $now - 2000,
            'timestarted' => $now - 1000,
            'timecompleted' => $now - 500,
        ]);

        $mi4 = $gudg->create_persisted_mission_instance($streak4, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_ENDED,
            'timecreated' => $now - 4000,
            'timestarted' => $now - 2000,
            'timecompleted' => $now - 1000,
            'timeended' => $now - 1000,
        ]);

        $mr = di::get('repository');
        $mistreaks = $mr->get_current_streaks($u1->id, context_system::instance());
        $this->assertCount(1, $mistreaks);
        $this->assertEquals($mi3->get_id(), $mistreaks[0]->get_id());
    }

    /**
     * Test get latest streak instances gets the latest.
     *
     * @covers block_gearup\local\repository\repository::get_latest_streak_instances
     */
    public function test_get_latest_streak_instances_gets_latest(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $streak1 = $gudg->create_streak();
        $streak2 = $gudg->create_streak();
        $streak3 = $gudg->create_streak();
        $streak4 = $gudg->create_streak();

        $gudg->create_persisted_mission_instance($streak1, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_ENDED,
            'iteration' => 0,
        ]);
        $gudg->create_persisted_mission_instance($streak1, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_ENDED,
            'iteration' => 1,
        ]);
        $mi1c = $gudg->create_persisted_mission_instance($streak1, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_STARTED,
            'iteration' => 2,
        ]);

        $mi2 = $gudg->create_persisted_mission_instance($streak2, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_STARTED,
        ]);

        $gudg->create_persisted_mission_instance($streak3, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_ENDED,
        ]);
        $mi3b = $gudg->create_persisted_mission_instance($streak3, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_COMPLETED,
            'iteration' => 1,
        ]);

        $mi4 = $gudg->create_persisted_mission_instance($streak4, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_COMPLETED,
        ]);

        $mr = di::get('repository');
        $mistreaks = $mr->get_latest_streak_instances($u1->id, context_system::instance());
        $this->assertCount(4, $mistreaks);
        $this->assertEquals([
            $mi1c->get_id(),
            $mi2->get_id(),
            $mi3b->get_id(),
            $mi4->get_id(),
        ], array_map(function($mi) {
            return $mi->get_id();
        }, $mistreaks));
    }

    /**
     * Test get latest streak instances filters users.
     *
     * @covers block_gearup\local\repository\repository::get_latest_streak_instances
     */
    public function test_get_latest_streak_instances_filters_users(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $streak1 = $gudg->create_streak();

        $mi = $gudg->create_persisted_mission_instance($streak1, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_STARTED,
        ]);
        $gudg->create_persisted_mission_instance($streak1, [
            'subjectid' => $u2->id,
            'state' => mission_instance::STATE_STARTED,
        ]);

        $mr = di::get('repository');
        $mistreaks = $mr->get_latest_streak_instances($u1->id, context_system::instance());
        $this->assertCount(1, $mistreaks);
        $this->assertEquals($u1->id, $mistreaks[0]->get_subject_id());
        $this->assertEquals($mi->get_id(), $mistreaks[0]->get_id());
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public static function get_latest_streak_instances_filters_missions_provider(): array {
        return [
            [
                ['context' => 'sys'],
                ['context' => 'c1'],
                'c1',
                1,
                [2],
            ],
            [
                ['context' => 'c1'],
                ['context' => 'sys'],
                'c1',
                1,
                [1],
            ],
            [
                ['context' => 'c1'],
                ['context' => 'c2'],
                'c2',
                1,
                [2],
            ],
            [
                ['state' => mission::STATE_ARCHIVED],
                [],
                'sys',
                1,
                [2],
            ],
            [
                ['state' => mission::STATE_WIZARD],
                ['state' => mission::STATE_ARCHIVED],
                'sys',
                0,
                [],
            ],
            [
                [],
                ['type' => quest::class],
                'sys',
                1,
                [1],
            ],
        ];
    }

    /**
     * Test get latest streak instances filters missions.
     *
     * @dataProvider get_latest_streak_instances_filters_missions_provider
     * @covers block_gearup\local\repository\repository::get_latest_streak_instances
     * @param array $data1 The first mission data.
     * @param array $data2 The second mission data.
     * @param string $queryctx The query context.
     * @param int $expect The expected result.
     */
    public function test_get_latest_streak_instances_filters_missions($data1, $data2, $queryctx, $expcount, $expstreaks): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);

        $getctx = function($ctx) use ($sysctx, $c1ctx, $c2ctx) {
            if ($ctx === 'c1') {
                return $c1ctx;
            } else if ($ctx === 'c2') {
                return $c2ctx;
            }
            return $sysctx;
        };

        $streak1 = $gudg->create_streak($data1 + [
            'contextid' => $getctx($data1['context'] ?? null)->id,
        ]);
        $streak2 = $gudg->create_streak($data2 + [
            'contextid' => $getctx($data2['context'] ?? null)->id,
        ]);

        $gudg->create_persisted_mission_instance($streak1, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_STARTED,
        ]);
        $gudg->create_persisted_mission_instance($streak2, [
            'subjectid' => $u1->id,
            'state' => mission_instance::STATE_STARTED,
        ]);

        $expectedstreaks = array_map(function($n) use ($streak1, $streak2) {
            if ($n === 1) {
                return $streak1->get_id();
            } else if ($n === 2) {
                return $streak2->get_id();
            }
            throw new \coding_exception('Invalid expected value');
        }, $expstreaks);

        $mr = di::get('repository');
        $mistreaks = $mr->get_latest_streak_instances($u1->id, $getctx($queryctx));
        $this->assertCount($expcount, $mistreaks);
        $this->assertEquals($expectedstreaks, array_map(function($mi) {
            return $mi->get_mission()->get_id();
        }, $mistreaks));

    }

    /**
     * Test get stale objective instances.
     *
     * @covers block_gearup\local\repository\repository::get_stale_objective_instances
     */
    public function test_get_stale_objective_instances(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $clock = $this->get_frozen_clock();
        $mr = di::get('repository');
        $mo = di::get('mission_operator');

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $mission = $gudg->create_achievement();
        $objective = $mission->get_objectives()[0];

        $missioninst1 = $mo->assign_mission($mission, $u1->id);
        $objinst1 = $missioninst1->get_instance_of_objective($objective->get_id());
        $objinst1->set_stale_from($clock->now()->modify('+1 hour'));

        // Time is in the future.
        $staleinsts = $mr->get_stale_objective_instances();
        $this->assertCount(0, $staleinsts);

        // Find stale when time is passed.
        $objinst1->set_stale_from($clock->now()->modify('-1 hour'));
        $staleinsts = $mr->get_stale_objective_instances();
        $this->assertCount(1, $staleinsts);
        $this->assertEquals($objinst1->get_objective()->get_id(), $objective->get_id());
        $this->assertEquals($objinst1->get_mission_instance_id(), $missioninst1->get_id());

        // Stale but completed are ignored.
        $objinst1->mark_complete();
        $staleinsts = $mr->get_stale_objective_instances();
        $this->assertCount(0, $staleinsts);
    }

    /**
     * Test get stale objective instances with other states.
     *
     * @covers block_gearup\local\repository\repository::get_stale_objective_instances
     */
    public function test_get_stale_objective_instances_with_other_states(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $clock = $this->get_frozen_clock();
        $mr = di::get('repository');
        $mo = di::get('mission_operator');

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $mission = $gudg->create_quest(['startmode' => mission::START_OPTIN]);
        $objective = $mission->get_objectives()[0];

        $missioninst1 = $mo->assign_mission($mission, $u1->id);
        $mo->start_instance($missioninst1);
        $objinst1 = $missioninst1->get_instance_of_objective($objective->get_id());
        $objinst1->set_stale_from($clock->now()->modify('-1 hour'));

        // First confirm we found the objective.
        $staleinsts = $mr->get_stale_objective_instances();
        $this->assertCount(1, $staleinsts);
        $this->assertEquals(mission_instance::STATE_STARTED, $missioninst1->get_state());

        // If completed, we should not find it.
        $missioninst1->get_persistent()->set('state', mission_instance::STATE_COMPLETED);
        $missioninst1->get_persistent()->save();
        $missioninst1 = $mr->get_instance($missioninst1->get_id());
        $staleinsts = $mr->get_stale_objective_instances();
        $this->assertEquals(mission_instance::STATE_COMPLETED, $missioninst1->get_state());
        $this->assertCount(0, $staleinsts);

        // If ended, we should not find it.
        $missioninst1->get_persistent()->set('state', mission_instance::STATE_ENDED);
        $missioninst1->get_persistent()->save();
        $missioninst1 = $mr->get_instance($missioninst1->get_id());
        $staleinsts = $mr->get_stale_objective_instances();
        $this->assertEquals(mission_instance::STATE_ENDED, $missioninst1->get_state());
        $this->assertCount(0, $staleinsts);

        // If assigned, although unexpected, we should not find it.
        $missioninst1->get_persistent()->set('state', mission_instance::STATE_ASSIGNED);
        $missioninst1->get_persistent()->save();
        $missioninst1 = $mr->get_instance($missioninst1->get_id());
        $staleinsts = $mr->get_stale_objective_instances();
        $this->assertEquals(mission_instance::STATE_ASSIGNED, $missioninst1->get_state());
        $this->assertCount(0, $staleinsts);

        // If back at started, we find it.
        $missioninst1->get_persistent()->set('state', mission_instance::STATE_STARTED);
        $missioninst1->get_persistent()->save();
        $missioninst1 = $mr->get_instance($missioninst1->get_id());
        $staleinsts = $mr->get_stale_objective_instances();
        $this->assertEquals(mission_instance::STATE_STARTED, $missioninst1->get_state());
        $this->assertCount(1, $staleinsts);

        // If mission is draft, although unexpected, we should not find it.
        $mission->get_persistent()->set('state', mission::STATE_WIZARD);
        $mission->get_persistent()->save();
        $mission = $mr->get_mission($mission->get_id());
        $staleinsts = $mr->get_stale_objective_instances();
        $this->assertEquals(mission::STATE_WIZARD, $mission->get_state());
        $this->assertCount(0, $staleinsts);

        // If mission is archived, we should not find it.
        $mission->get_persistent()->set('state', mission::STATE_ARCHIVED);
        $mission->get_persistent()->save();
        $mission = $mr->get_mission($mission->get_id());
        $staleinsts = $mr->get_stale_objective_instances();
        $this->assertEquals(mission::STATE_ARCHIVED, $mission->get_state());
        $this->assertCount(0, $staleinsts);
    }

    /**
     * Provider.
     *
     * @return array
     */
    public static function get_visible_instance_types_in_provider(): array {
        return array_map(function($entry) {
            return [(object) $entry[0], (object) $entry[1], (object) $entry[2], $entry[3]];
        }, [
            // Basic visible quest.
            [
                [],
                ['type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_STARTED],
                true,
            ],

            // Mismatched context.
            [
                ['ctx' => 'c1'],
                ['ctx' => 'sys', 'type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_STARTED],
                false,
            ],
            [
                ['ctx' => 'sys'],
                ['ctx' => 'c1', 'type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_STARTED],
                false,
            ],
            [
                ['ctx' => 'c2'],
                ['ctx' => 'c1', 'type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_STARTED],
                false,
            ],

            // Other user.
            [
                ['user' => 'u2'],
                ['type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['user' => 'u1', 'state' => mission_instance::STATE_STARTED],
                false,
            ],
            [
                ['user' => 'u1'],
                ['type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['user' => 'u2', 'state' => mission_instance::STATE_STARTED],
                false,
            ],

            // Assigned show when visible.
            [
                [],
                ['type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_ASSIGNED],
                true,
            ],
            // Assigned do not show when secret.
            [
                [],
                ['type' => quest::class, 'visible' => mission::VISIBLE_SECRET],
                ['state' => mission_instance::STATE_ASSIGNED],
                false,
            ],

            // Started and completed always show.
            [
                [],
                ['type' => quest::class, 'visible' => mission::VISIBLE_SECRET],
                ['state' => mission_instance::STATE_STARTED],
                true,
            ],
            [
                [],
                ['type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_STARTED],
                true,
            ],
            [
                [],
                ['type' => quest::class, 'visible' => mission::VISIBLE_SECRET],
                ['state' => mission_instance::STATE_COMPLETED],
                true,
            ],
            [
                [],
                ['type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_COMPLETED],
                true,
            ],

            // Ended quest and achievements are visible.
            [
                [],
                ['type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_ENDED],
                true,
            ],
            [
                [],
                ['type' => achievement::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_ENDED],
                true,
            ],

            // Challenges and streaks are not shown when ended.
            [
                [],
                ['type' => challenge::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_ENDED],
                false,
            ],
            [
                [],
                ['type' => streak::class, 'visible' => mission::VISIBLE_ALWAYS, 'timelimit' => DAYSECS,
                    'repeatcount' => mission::REPEAT_ALWAYS],
                ['state' => mission_instance::STATE_ENDED],
                false,
            ],

            // Archived and draft missions never show.
            [
                [],
                ['state' => mission::STATE_ARCHIVED, 'type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_STARTED],
                false,
            ],
            [
                [],
                ['state' => mission::STATE_WIZARD, 'type' => quest::class, 'visible' => mission::VISIBLE_ALWAYS],
                ['state' => mission_instance::STATE_STARTED],
                false,
            ],
        ]);
    }

    /**
     * Test get visible instance types in.
     *
     * @dataProvider get_visible_instance_types_in_provider
     * @covers block_gearup\local\repository\repository::get_visible_instance_types_in
     * @param object $lookupcfg
     * @param object $missioncfg
     * @param object $micfg
     * @param bool $isvisible
     */
    public function test_get_visible_instance_types_in($lookupcfg, $missioncfg, $micfg, bool $isvisible): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);
        $sysctx = context_system::instance();

        $missionctx = ${($missioncfg->ctx ?? 'sys') . 'ctx'};
        $missiontype = $missioncfg->type ?? quest::class;
        $missionvisibility = $missioncfg->visible ?? mission::VISIBLE_ALWAYS;
        $missionstate = $missioncfg->state ?? mission::STATE_ACTIVE;

        $mistate = $micfg->state ?? mission_instance::STATE_STARTED;
        $misubjectid = ${($micfg->user ?? 'u1')}->id;

        $lookupuserid = ${($lookupcfg->user ?? 'u1')}->id;
        $lookupctx = ${($lookupcfg->ctx ?? 'sys') . 'ctx'};

        $mission = $gudg->create_persisted_mission([
            'contextid' => $missionctx->id,
            'visibility' => $missionvisibility,
            'state' => $missionstate,
            'type' => $missiontype,
        ]);

        $mi = $gudg->create_persisted_mission_instance($mission, [
            'state' => $mistate,
            'subjectid' => $misubjectid,
        ]);

        $mr = di::get('repository');
        $vistypes = $mr->get_visible_instance_types_in($lookupuserid, $lookupctx);
        $debug = json_encode(compact('lookupcfg', 'missioncfg', 'micfg', 'isvisible'));
        if ($isvisible) {
            $this->assertCount(1, $vistypes, "Failed for $debug");
            $this->assertContains($missiontype, $vistypes);
        } else {
            $this->assertCount(0, $vistypes, "Failed for $debug");
        }
    }

}
