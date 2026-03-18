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
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\privacy;

use block_gearup\local\model\assigner;
use block_gearup\local\model\mission;
use block_gearup\local\model\mission_inst;
use block_gearup\local\model\objective;
use block_gearup\local\model\objective_inst;
use block_gearup\local\model\outcome;
use block_gearup\privacy\provider;
use block_gearup\tests\base_testcase;
use context_course;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\privacy\provider
 */
final class provider_test extends base_testcase {

    /**
     * Test.
     */
    public function test_metadata(): void {
        $data = provider::get_metadata(new collection('block_gearup'));
        $this->assertCount(6, $data->get_collection());
    }

    /**
     * Test.
     */
    public function test_export_user_prefs(): void {
        $dg = $this->getDataGenerator();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        provider::export_user_preferences($u1->id);

        $writer = writer::with_context(context_system::instance());
        $prefs = $writer->get_user_preferences('block_gearup');
        $this->assertEmpty((array) $prefs);

        writer::reset();
        provider::export_user_preferences($u2->id);
        $writer = writer::with_context(context_system::instance());
        $prefs = $writer->get_user_preferences('block_gearup');
        $this->assertEmpty((array) $prefs);
    }

    /**
     * Test.
     */
    public function test_get_contexts_for_userid(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();

        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);

        $m1 = new mission(null, (object) ['contextid' => $sysctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m1->create();
        $m2 = new mission(null, (object) ['contextid' => $c1ctx->id, 'type' => mission::TYPE_QUEST, 'title' => '2']);
        $m2->create();
        $m3 = new mission(null, (object) ['contextid' => $c2ctx->id, 'type' => mission::TYPE_QUEST, 'title' => '3']);
        $m3->create();

        $mi1 = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => $u1->id]);
        $mi1->create();
        $mi2 = new mission_inst(null, (object) ['missionid' => $m2->get('id'), 'subjectid' => $u1->id]);
        $mi2->create();
        $mi3 = new mission_inst(null, (object) ['missionid' => $m3->get('id'), 'subjectid' => $u2->id]);
        $mi3->create();

        $contextlist = provider::get_contexts_for_userid($u1->id);
        $this->assert_contextlist_equals($contextlist, [$sysctx->id, $c1ctx->id]);

        $contextlist = provider::get_contexts_for_userid($u2->id);
        $this->assert_contextlist_equals($contextlist, [$c2ctx->id]);

        $contextlist = provider::get_contexts_for_userid($u3->id);
        $this->assert_contextlist_equals($contextlist, []);
    }

    /**
     * Test.
     */
    public function test_get_contexts_for_userid_usermodified(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();
        $u5 = $dg->create_user();
        $u6 = $dg->create_user();
        $u7 = $dg->create_user();
        $u8 = $dg->create_user();

        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $sysctx = context_system::instance();

        $m1 = new mission(null, (object) ['contextid' => $sysctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m1->create();
        $m2 = new mission(null, (object) ['contextid' => $c1ctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m2->create();
        $mi1 = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => 0]);
        $mi1->create();
        $a1 = new assigner(null, (object) ['missionid' => $m1->get('id'), 'type' => 'everyone']);
        $a1->create();
        $o1 = new objective(null, (object) ['missionid' => $m1->get('id'), 'type' => 'manual', 'label' => '']);
        $o1->create();
        $ot1 = new outcome(null, (object) ['missionid' => $m1->get('id'), 'type' => 'label', 'label' => '']);
        $ot1->create();
        $oi1 = new objective_inst(null, (object) ['missioninstid' => $mi1->get('id'), 'subjectid' => 0,
            'objectiveid' => $o1->get('id')]);
        $oi1->create();

        $DB->set_field('block_gearup_mission', 'usermodified', $u1->id, ['id' => $m1->get('id')]);
        $DB->set_field('block_gearup_mission', 'usermodified', $u2->id, ['id' => $m2->get('id')]);
        $DB->set_field('block_gearup_mission_inst', 'usermodified', $u3->id, ['id' => $mi1->get('id')]);
        $DB->set_field('block_gearup_assigner', 'usermodified', $u4->id, ['id' => $a1->get('id')]);
        $DB->set_field('block_gearup_objective', 'usermodified', $u5->id, ['id' => $o1->get('id')]);
        $DB->set_field('block_gearup_outcome', 'usermodified', $u6->id, ['id' => $ot1->get('id')]);
        $DB->set_field('block_gearup_objective_inst', 'usermodified', $u7->id, ['id' => $oi1->get('id')]);

        $contextlist = provider::get_contexts_for_userid($u1->id);
        $this->assert_contextlist_equals($contextlist, [$sysctx->id]);
        $contextlist = provider::get_contexts_for_userid($u2->id);
        $this->assert_contextlist_equals($contextlist, [$c1ctx->id]);
        $contextlist = provider::get_contexts_for_userid($u3->id);
        $this->assert_contextlist_equals($contextlist, [$sysctx->id]);
        $contextlist = provider::get_contexts_for_userid($u4->id);
        $this->assert_contextlist_equals($contextlist, [$sysctx->id]);
        $contextlist = provider::get_contexts_for_userid($u5->id);
        $this->assert_contextlist_equals($contextlist, [$sysctx->id]);
        $contextlist = provider::get_contexts_for_userid($u6->id);
        $this->assert_contextlist_equals($contextlist, [$sysctx->id]);
        $contextlist = provider::get_contexts_for_userid($u7->id);
        $this->assert_contextlist_equals($contextlist, [$sysctx->id]);
        $contextlist = provider::get_contexts_for_userid($u8->id);
        $this->assert_contextlist_equals($contextlist, []);
    }

    /**
     * Test.
     */
    public function test_get_users_in_context(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $c3 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();

        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);

        $m1 = new mission(null, (object) ['contextid' => $sysctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m1->create();
        $m2 = new mission(null, (object) ['contextid' => $c1ctx->id, 'type' => mission::TYPE_QUEST, 'title' => '2']);
        $m2->create();
        $m3 = new mission(null, (object) ['contextid' => $c2ctx->id, 'type' => mission::TYPE_QUEST, 'title' => '3']);
        $m3->create();

        $mi1 = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => $u1->id]);
        $mi1->create();
        $mi2 = new mission_inst(null, (object) ['missionid' => $m2->get('id'), 'subjectid' => $u1->id]);
        $mi2->create();
        $mi3a = new mission_inst(null, (object) ['missionid' => $m3->get('id'), 'subjectid' => $u1->id]);
        $mi3a->create();
        $mi3b = new mission_inst(null, (object) ['missionid' => $m3->get('id'), 'subjectid' => $u2->id]);
        $mi3b->create();

        $userlist = new userlist(context_system::instance(), 'block_gearup');
        provider::get_users_in_context($userlist);
        $this->assert_userlist_equals($userlist, [$u1->id]);

        $userlist = new userlist(context_course::instance($c1->id), 'block_gearup');
        provider::get_users_in_context($userlist);
        $this->assert_userlist_equals($userlist, [$u1->id]);

        $userlist = new userlist(context_course::instance($c2->id), 'block_gearup');
        provider::get_users_in_context($userlist);
        $this->assert_userlist_equals($userlist, [$u1->id, $u2->id]);

        $userlist = new userlist(context_course::instance($c3->id), 'block_gearup');
        provider::get_users_in_context($userlist);
        $this->assert_userlist_equals($userlist, []);
    }

    /**
     * Test.
     */
    public function test_get_users_in_context_usermodified(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();
        $u5 = $dg->create_user();
        $u6 = $dg->create_user();
        $u7 = $dg->create_user();

        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $sysctx = context_system::instance();

        $m1 = new mission(null, (object) ['contextid' => $sysctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m1->create();
        $m2 = new mission(null, (object) ['contextid' => $c1ctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m2->create();
        $mi1 = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => 0]);
        $mi1->create();
        $a1 = new assigner(null, (object) ['missionid' => $m1->get('id'), 'type' => 'everyone']);
        $a1->create();
        $o1 = new objective(null, (object) ['missionid' => $m1->get('id'), 'type' => 'manual', 'label' => '']);
        $o1->create();
        $ot1 = new outcome(null, (object) ['missionid' => $m1->get('id'), 'type' => 'label', 'label' => '']);
        $ot1->create();
        $oi1 = new objective_inst(null, (object) ['missioninstid' => $mi1->get('id'), 'subjectid' => 0,
            'objectiveid' => $o1->get('id')]);
        $oi1->create();

        $DB->set_field('block_gearup_mission', 'usermodified', $u1->id, ['id' => $m1->get('id')]);
        $DB->set_field('block_gearup_mission', 'usermodified', $u2->id, ['id' => $m2->get('id')]);
        $DB->set_field('block_gearup_mission_inst', 'usermodified', $u3->id, ['id' => $mi1->get('id')]);
        $DB->set_field('block_gearup_assigner', 'usermodified', $u4->id, ['id' => $a1->get('id')]);
        $DB->set_field('block_gearup_objective', 'usermodified', $u5->id, ['id' => $o1->get('id')]);
        $DB->set_field('block_gearup_outcome', 'usermodified', $u6->id, ['id' => $ot1->get('id')]);
        $DB->set_field('block_gearup_objective_inst', 'usermodified', $u7->id, ['id' => $oi1->get('id')]);

        $userlist = new userlist($sysctx, 'block_gearup');
        provider::get_users_in_context($userlist);
        $this->assert_userlist_equals($userlist, [$u1->id, $u3->id, $u4->id, $u5->id, $u6->id, $u7->id]);

        $userlist = new userlist($c1ctx, 'block_gearup');
        provider::get_users_in_context($userlist);
        $this->assert_userlist_equals($userlist, [$u2->id]);
    }

    /**
     * Test.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $sysctx = context_system::instance();

        $m1 = new mission(null, (object) ['contextid' => $sysctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m1->create();
        $m2 = new mission(null, (object) ['contextid' => $c1ctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m2->create();
        $mi1a = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => $u1->id]);
        $mi1a->create();
        $mi1b = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => $u2->id]);
        $mi1b->create();
        $mi2a = new mission_inst(null, (object) ['missionid' => $m2->get('id'), 'subjectid' => $u1->id]);
        $mi2a->create();
        $o1 = new objective(null, (object) ['missionid' => $m1->get('id'), 'type' => 'manual', 'label' => '']);
        $o1->create();
        $o2 = new objective(null, (object) ['missionid' => $m2->get('id'), 'type' => 'manual', 'label' => '']);
        $o2->create();
        $oi1a = new objective_inst(null, (object) ['missioninstid' => $mi1a->get('id'), 'subjectid' => $u1->id,
            'objectiveid' => $o1->get('id')]);
        $oi1a->create();
        $oi1b = new objective_inst(null, (object) ['missioninstid' => $mi1b->get('id'), 'subjectid' => $u2->id,
            'objectiveid' => $o1->get('id')]);
        $oi1b->create();
        $oi2a = new objective_inst(null, (object) ['missioninstid' => $mi2a->get('id'), 'subjectid' => $u1->id,
            'objectiveid' => $o2->get('id')]);
        $oi2a->create();

        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1b->get('id'),
            'subjectid' => $u2->id]));

        provider::delete_data_for_all_users_in_context($c1ctx);

        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1b->get('id'),
            'subjectid' => $u2->id]));

        provider::delete_data_for_all_users_in_context($sysctx);

        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u2->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1b->get('id'),
            'subjectid' => $u2->id]));
    }

    /**
     * Test.
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $sysctx = context_system::instance();

        $m1 = new mission(null, (object) ['contextid' => $sysctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m1->create();
        $m2 = new mission(null, (object) ['contextid' => $c1ctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m2->create();
        $mi1a = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => $u1->id]);
        $mi1a->create();
        $mi1b = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => $u2->id]);
        $mi1b->create();
        $mi2a = new mission_inst(null, (object) ['missionid' => $m2->get('id'), 'subjectid' => $u1->id]);
        $mi2a->create();
        $mi2b = new mission_inst(null, (object) ['missionid' => $m2->get('id'), 'subjectid' => $u2->id]);
        $mi2b->create();
        $o1 = new objective(null, (object) ['missionid' => $m1->get('id'), 'type' => 'manual', 'label' => '']);
        $o1->create();
        $o2 = new objective(null, (object) ['missionid' => $m2->get('id'), 'type' => 'manual', 'label' => '']);
        $o2->create();
        $oi1a = new objective_inst(null, (object) ['missioninstid' => $mi1a->get('id'), 'subjectid' => $u1->id,
            'objectiveid' => $o1->get('id')]);
        $oi1a->create();
        $oi1b = new objective_inst(null, (object) ['missioninstid' => $mi1b->get('id'), 'subjectid' => $u2->id,
            'objectiveid' => $o1->get('id')]);
        $oi1b->create();
        $oi2a = new objective_inst(null, (object) ['missioninstid' => $mi2a->get('id'), 'subjectid' => $u1->id,
            'objectiveid' => $o2->get('id')]);
        $oi2a->create();
        $oi2b = new objective_inst(null, (object) ['missioninstid' => $mi2b->get('id'), 'subjectid' => $u2->id,
            'objectiveid' => $o2->get('id')]);
        $oi2b->create();

        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1b->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2b->get('id'),
            'subjectid' => $u2->id]));

        $contextlist = new approved_contextlist($u2, 'block_gearup', [$sysctx->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1b->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2b->get('id'),
            'subjectid' => $u2->id]));

        $contextlist = new approved_contextlist($u1, 'block_gearup', [$sysctx->id, $c1ctx->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u2->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1b->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2b->get('id'),
            'subjectid' => $u2->id]));
    }

    /**
     * Test.
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $sysctx = context_system::instance();

        $m1 = new mission(null, (object) ['contextid' => $sysctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m1->create();
        $m2 = new mission(null, (object) ['contextid' => $c1ctx->id, 'type' => mission::TYPE_QUEST, 'title' => '1']);
        $m2->create();
        $mi1a = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => $u1->id]);
        $mi1a->create();
        $mi1b = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => $u2->id]);
        $mi1b->create();
        $mi2a = new mission_inst(null, (object) ['missionid' => $m2->get('id'), 'subjectid' => $u1->id]);
        $mi2a->create();
        $mi2b = new mission_inst(null, (object) ['missionid' => $m2->get('id'), 'subjectid' => $u2->id]);
        $mi2b->create();
        $o1 = new objective(null, (object) ['missionid' => $m1->get('id'), 'type' => 'manual', 'label' => '']);
        $o1->create();
        $o2 = new objective(null, (object) ['missionid' => $m2->get('id'), 'type' => 'manual', 'label' => '']);
        $o2->create();
        $oi1a = new objective_inst(null, (object) ['missioninstid' => $mi1a->get('id'), 'subjectid' => $u1->id,
            'objectiveid' => $o1->get('id')]);
        $oi1a->create();
        $oi1b = new objective_inst(null, (object) ['missioninstid' => $mi1b->get('id'), 'subjectid' => $u2->id,
            'objectiveid' => $o1->get('id')]);
        $oi1b->create();
        $oi2a = new objective_inst(null, (object) ['missioninstid' => $mi2a->get('id'), 'subjectid' => $u1->id,
            'objectiveid' => $o2->get('id')]);
        $oi2a->create();
        $oi2b = new objective_inst(null, (object) ['missioninstid' => $mi2b->get('id'), 'subjectid' => $u2->id,
            'objectiveid' => $o2->get('id')]);
        $oi2b->create();

        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1b->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2b->get('id'),
            'subjectid' => $u2->id]));

        $userlist = new approved_userlist($sysctx, 'block_gearup', [$u2->id]);
        provider::delete_data_for_users($userlist);

        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1b->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2b->get('id'),
            'subjectid' => $u2->id]));

        $userlist = new approved_userlist($c1ctx, 'block_gearup', [$u1->id, $u2->id]);
        provider::delete_data_for_users($userlist);

        $this->assertTrue($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m1->get('id'),
            'subjectid' => $u2->id]));
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['missionid' => $m2->get('id'),
            'subjectid' => $u2->id]));
        $this->assertTrue($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2a->get('id'),
            'subjectid' => $u1->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi1b->get('id'),
            'subjectid' => $u2->id]));
        $this->assertFalse($DB->record_exists('block_gearup_objective_inst', ['missioninstid' => $mi2b->get('id'),
            'subjectid' => $u2->id]));
    }

    /**
     * Test.
     */
    public function test_export_data_for_user(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $sysctx = context_system::instance();

        $m1 = new mission(null, (object) ['contextid' => $sysctx->id, 'type' => mission::TYPE_QUEST, 'title' => 'Q1']);
        $m1->create();
        $m2 = new mission(null, (object) ['contextid' => $c1ctx->id, 'type' => mission::TYPE_QUEST, 'title' => 'Q2']);
        $m2->create();
        $m3 = new mission(null, (object) ['contextid' => $c1ctx->id, 'type' => mission::TYPE_QUEST, 'title' => 'Q3']);
        $m3->create();
        $mi1a = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => $u1->id,
            'completionratio' => 0.123, 'timestarted' => 200000, 'timecompleted' => 300000, 'timeended' => 400000]);
        $mi1a->create();
        $mi1b = new mission_inst(null, (object) ['missionid' => $m1->get('id'), 'subjectid' => $u2->id,
            'completionratio' => 0.456, 'timestarted' => 200000, 'timecompleted' => 300000, 'timeended' => 400000]);
        $mi1b->create();
        $mi2a = new mission_inst(null, (object) ['missionid' => $m2->get('id'), 'subjectid' => $u1->id,
            'completionratio' => 0.789, 'timestarted' => 200000]);
        $mi2a->create();
        $mi3a = new mission_inst(null, (object) ['missionid' => $m3->get('id'), 'subjectid' => $u1->id,
            'completionratio' => 0.9, 'timestarted' => 200000, 'timecompleted' => 300000]);
        $mi3a->create();
        $DB->set_field('block_gearup_mission_inst', 'timecreated', 100000);
        $o1 = new objective(null, (object) ['missionid' => $m1->get('id'), 'type' => 'manual', 'label' => 'O1',
            'countneeded' => 10]);
        $o1->create();
        $o2 = new objective(null, (object) ['missionid' => $m2->get('id'), 'type' => 'manual', 'label' => 'O2',
        'countneeded' => 5]);
        $o2->create();
        $o3a = new objective(null, (object) ['missionid' => $m3->get('id'), 'type' => 'manual', 'label' => 'O3a',
        'countneeded' => 7]);
        $o3a->create();
        $o3b = new objective(null, (object) ['missionid' => $m3->get('id'), 'type' => 'manual', 'label' => 'O3b',
        'countneeded' => 8]);
        $o3b->create();
        $oi1a = new objective_inst(null, (object) ['missioninstid' => $mi1a->get('id'), 'subjectid' => $u1->id,
            'objectiveid' => $o1->get('id'), 'counter' => 1]);
        $oi1a->create();
        $oi1b = new objective_inst(null, (object) ['missioninstid' => $mi1b->get('id'), 'subjectid' => $u2->id,
            'objectiveid' => $o1->get('id'), 'counter' => 2]);
        $oi1b->create();
        $oi2a = new objective_inst(null, (object) ['missioninstid' => $mi2a->get('id'), 'subjectid' => $u1->id,
            'objectiveid' => $o2->get('id'), 'counter' => 3]);
        $oi2a->create();
        $oi3a = new objective_inst(null, (object) ['missioninstid' => $mi3a->get('id'), 'subjectid' => $u1->id,
            'objectiveid' => $o3a->get('id'), 'counter' => 6]);
        $oi3a->create();
        $oi3b = new objective_inst(null, (object) ['missioninstid' => $mi3a->get('id'), 'subjectid' => $u1->id,
            'objectiveid' => $o3b->get('id'), 'counter' => 7]);
        $oi3b->create();

        $contextlist = new approved_contextlist($u1, 'block_gearup', [$sysctx->id]);
        provider::export_user_data($contextlist);

        $writer = writer::with_context($sysctx);
        $exported = $writer->get_data([get_string('pluginname', 'block_gearup'),
            get_string('privacy:path:missions', 'block_gearup')]);
        $missions = $exported->data ?? [];

        $this->assertCount(1, $missions);
        $this->assertEquals('Q1', $missions[0]->name);
        $this->assertEquals($u1->id, $missions[0]->subjectid);
        $this->assertEquals(0.123, $missions[0]->completionratio);
        $this->assertEquals(transform::datetime(100000), $missions[0]->timeassigned);
        $this->assertEquals(transform::datetime(200000), $missions[0]->timestarted);
        $this->assertEquals(transform::datetime(300000), $missions[0]->timecompleted);
        $this->assertEquals(transform::datetime(400000), $missions[0]->timeended);
        $this->assertCount(1, $missions[0]->objectives);
        $this->assertEquals('manual', $missions[0]->objectives[0]->type);
        $this->assertEquals('O1', $missions[0]->objectives[0]->name);
        $this->assertEquals(1, $missions[0]->objectives[0]->counter);
        $this->assertEquals(10, $missions[0]->objectives[0]->countneeded);
        $this->assertEquals(null, $missions[0]->objectives[0]->statedata);

        writer::reset();
        $contextlist = new approved_contextlist($u1, 'block_gearup', [$c1ctx->id]);
        provider::export_user_data($contextlist);

        $writer = writer::with_context($c1ctx);
        $exported = $writer->get_data([get_string('pluginname', 'block_gearup'),
            get_string('privacy:path:missions', 'block_gearup')]);
        $missions = $exported->data ?? [];

        $this->assertCount(2, $missions);

        $mission = $missions[0];
        $this->assertEquals('Q2', $mission->name);
        $this->assertEquals($u1->id, $mission->subjectid);
        $this->assertEquals(0.789, $mission->completionratio);
        $this->assertEquals(transform::datetime(100000), $mission->timeassigned);
        $this->assertEquals(transform::datetime(200000), $mission->timestarted);
        $this->assertEquals('-', $mission->timecompleted);
        $this->assertEquals('-', $mission->timeended);
        $this->assertCount(1, $mission->objectives);
        $this->assertEquals('manual', $mission->objectives[0]->type);
        $this->assertEquals('O2', $mission->objectives[0]->name);
        $this->assertEquals(3, $mission->objectives[0]->counter);
        $this->assertEquals(5, $mission->objectives[0]->countneeded);
        $this->assertEquals(null, $mission->objectives[0]->statedata);

        $mission = $missions[1];
        $this->assertEquals('Q3', $mission->name);
        $this->assertEquals($u1->id, $mission->subjectid);
        $this->assertEquals(0.9, $mission->completionratio);
        $this->assertEquals(transform::datetime(100000), $mission->timeassigned);
        $this->assertEquals(transform::datetime(200000), $mission->timestarted);
        $this->assertEquals(transform::datetime(300000), $mission->timecompleted);
        $this->assertEquals('-', $mission->timeended);
        $this->assertCount(2, $mission->objectives);
        $this->assertEquals('manual', $mission->objectives[0]->type);
        $this->assertEquals('O3a', $mission->objectives[0]->name);
        $this->assertEquals(6, $mission->objectives[0]->counter);
        $this->assertEquals(7, $mission->objectives[0]->countneeded);
        $this->assertEquals(null, $mission->objectives[0]->statedata);
        $this->assertEquals('manual', $mission->objectives[1]->type);
        $this->assertEquals('O3b', $mission->objectives[1]->name);
        $this->assertEquals(7, $mission->objectives[1]->counter);
        $this->assertEquals(8, $mission->objectives[1]->countneeded);
        $this->assertEquals(null, $mission->objectives[1]->statedata);
    }

    protected function assert_contextlist_equals($contextlist, $expectedids) {
        $contextids = array_map('intval', $contextlist->get_contextids());
        sort($contextids);
        sort($expectedids);
        $this->assertEquals($expectedids, $contextids);
    }

    protected function assert_userlist_equals($userlist, $expectedids) {
        $userids = array_map('intval', $userlist->get_userids());
        sort($userids);
        sort($expectedids);
        $this->assertEquals($expectedids, $userids);
    }

}
