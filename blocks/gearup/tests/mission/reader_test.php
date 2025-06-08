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

namespace block_gearup\tests\mission;

use block_gearup\di;
use block_gearup\local\mission\achievement;
use block_gearup\local\mission\challenge;
use block_gearup\local\mission\mission as mission_interface;
use block_gearup\local\mission\persisted_mission;
use block_gearup\local\mission\quest;
use block_gearup\local\model\mission;
use block_gearup\local\model\mission_reader;
use block_gearup\local\repository\mission_query;
use block_gearup\local\utils\collection_utils;
use block_gearup\tests\base_testcase;
use context_course;
use context_system;

/**
 * Test case.
 *
 * @package    block_gearup
 * @category   test
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class reader_test extends base_testcase {

    public function test_basic_filtering(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $course = $dg->create_course();

        $sysctx = context_system::instance();
        $coursectx = context_course::instance($course->id);

        $achievement = $gudg->create_persisted_mission([
            'type' => mission::TYPE_ACHIEVEMENT,
        ]);
        $quest = $gudg->create_persisted_mission([
            'type' => mission::TYPE_QUEST,
        ]);
        $challenge = $gudg->create_persisted_mission([
            'type' => mission::TYPE_CHALLENGE,
        ]);
        $courseachievement = $gudg->create_persisted_mission([
            'contextid' => context_course::instance($course->id)->id,
            'type' => mission::TYPE_ACHIEVEMENT,
        ]);

        // Safeguard by filtering out everything when no where are set.
        $query = new mission_query($sysctx);
        $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(0, $reader->count());
        $this->assert_persistent_list_from_reader([], $reader->list(0, 0));

        // Basic count.
        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(3, $reader->count());
        $this->assert_persistent_list_from_reader([$achievement, $quest, $challenge], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id($coursectx->id);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assert_persistent_list_from_reader([$courseachievement], $reader->list(0, 0));

        // Achievements count.
        $query = (new mission_query($sysctx))
            ->set_type(achievement::class);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(2, $reader->count());
        $this->assert_persistent_list_from_reader([$achievement, $courseachievement], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->set_type(achievement::class);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assert_persistent_list_from_reader([$achievement], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id($coursectx->id)
            ->set_type(achievement::class);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assert_persistent_list_from_reader([$courseachievement], $reader->list(0, 0));

        // Quests count.
        $query = (new mission_query($sysctx))
            ->set_type(quest::class);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assert_persistent_list_from_reader([$quest], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->set_type(quest::class);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assert_persistent_list_from_reader([$quest], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id($coursectx->id)
            ->set_type(quest::class);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(0, $reader->count());
        $this->assert_persistent_list_from_reader([], $reader->list(0, 0));

        // Challenges count.
        $query = (new mission_query($sysctx))
            ->set_type(challenge::class);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assert_persistent_list_from_reader([$challenge], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->set_type(challenge::class);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(1, $reader->count());
        $this->assert_persistent_list_from_reader([$challenge], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id($coursectx->id)
            ->set_type(challenge::class);
            $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(0, $reader->count());
        $this->assert_persistent_list_from_reader([], $reader->list(0, 0));
    }

    public function test_basic_ordering(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $sysctx = context_system::instance();

        $m1 = $gudg->create_persisted_mission([
            'title' => 'Foo',
        ]);
        $m2 = $gudg->create_persisted_mission([
            'title' => 'Bar',
        ]);
        $m3 = $gudg->create_persisted_mission([
            'title' => 'Far',
        ]);
        $m4 = $gudg->create_persisted_mission([
            'title' => 'Baz',
        ]);

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->add_order_by('title', SORT_ASC);
        $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(4, $reader->count());
        $this->assert_persistent_list_from_reader([$m2, $m4, $m3, $m1], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->add_order_by('title', SORT_DESC);
        $reader = (new mission_reader())->use_query($query);
        $this->assertEquals(4, $reader->count());
        $this->assert_persistent_list_from_reader([$m1, $m3, $m4, $m2], $reader->list(0, 0));
    }

    public function test_annotations_count(): void {
        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $sysctx = context_system::instance();

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();
        $u5 = $dg->create_user();
        $u6 = $dg->create_user();
        $u7 = $dg->create_user();

        $m1 = $gudg->create_persisted_mission([
            'type' => mission::TYPE_QUEST,
            'startmode' => mission_interface::START_OPTIN,
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);
        $m1obj1 = $m1->get_objectives()[0];
        $m2 = $gudg->create_persisted_mission([
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);
        $m2obj1 = $m2->get_objectives()[0];

        $m1u1 = $mo->assign_mission($m1, $u1->id);
        $m1u2 = $mo->assign_mission($m1, $u2->id);
        $m1u3 = $mo->assign_mission($m1, $u3->id);
        $m1u4 = $mo->assign_mission($m1, $u4->id);
        $m2u1 = $mo->assign_mission($m2, $u1->id);
        $m2u2 = $mo->assign_mission($m2, $u2->id);

        $mo->start_instance($m1u2);
        $mo->start_instance($m1u3);
        $mo->complete_instance($m1u3);
        $mo->start_instance($m1u4);
        $mo->complete_instance($m1u4);
        $mo->end_instance($m1u4);

        $oo->increment_instance_counter($m2u2->get_instance_of_objective($m2obj1->get_id()), 7);
        $mo->evaluate_instance($m2u2);

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->annotate_completed_count()
            ->annotate_not_completed_count()
            ->annotate_inprogress_partial_count()
            ->annotate_inprogress_zero_count()
            ->annotate_recruit_count();
        $reader = (new mission_reader())->use_query($query);

        $this->assertEquals(2, $reader->count());
        $results = collection_utils::iterable_to_array($reader->list(0, 0));
        $this->assertEquals(2, $results[0]['annotations']->completed_count);
        $this->assertEquals(2, $results[0]['annotations']->not_completed_count);
        $this->assertEquals(0, $results[0]['annotations']->inprogress_partial_count);
        $this->assertEquals(1, $results[0]['annotations']->inprogress_zero_count);
        $this->assertEquals(4, $results[0]['annotations']->recruit_count);

        $this->assertEquals(0, $results[1]['annotations']->completed_count);
        $this->assertEquals(2, $results[1]['annotations']->not_completed_count);
        $this->assertEquals(1, $results[1]['annotations']->inprogress_partial_count);
        $this->assertEquals(1, $results[1]['annotations']->inprogress_zero_count);
        $this->assertEquals(2, $results[1]['annotations']->recruit_count);
    }

    public function test_annotations_rate(): void {
        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $sysctx = context_system::instance();

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();

        $m1 = $gudg->create_persisted_mission([
            'type' => mission::TYPE_QUEST,
            'startmode' => mission_interface::START_OPTIN,
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);
        $m2 = $gudg->create_persisted_mission([
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);
        $m2obj1 = $m2->get_objectives()[0];
        $m3 = $gudg->create_persisted_mission([
            'type' => mission::TYPE_CHALLENGE,
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);
        $m3obj1 = $m3->get_objectives()[0];

        $m1u1 = $mo->assign_mission($m1, $u1->id);
        $m1u2 = $mo->assign_mission($m1, $u2->id);
        $m1u3 = $mo->assign_mission($m1, $u3->id);
        $m1u4 = $mo->assign_mission($m1, $u4->id);

        $m2u1 = $mo->assign_mission($m2, $u1->id);
        $m2u2 = $mo->assign_mission($m2, $u2->id);
        $m2u3 = $mo->assign_mission($m2, $u3->id);
        $m2u4 = $mo->assign_mission($m2, $u4->id);

        $m3u1 = $mo->assign_mission($m3, $u1->id);
        $m3u2 = $mo->assign_mission($m3, $u2->id);
        $m3u3 = $mo->assign_mission($m3, $u3->id);
        $m3u4 = $mo->assign_mission($m3, $u4->id);

        // Complete 3 out of 4 in m1.
        $mo->end_instance($m1u1);
        $mo->complete_instance($m1u2);
        $mo->complete_instance($m1u3);

        // Set progress rate in m2, and 1 complete.
        $oo->increment_instance_counter($m2u1->get_instance_of_objective($m2obj1->get_id()), 2);
        $oo->increment_instance_counter($m2u2->get_instance_of_objective($m2obj1->get_id()), 5);
        $oo->increment_instance_counter($m2u3->get_instance_of_objective($m2obj1->get_id()), 8);
        $oo->increment_instance_counter($m2u4->get_instance_of_objective($m2obj1->get_id()), 10);
        $mo->evaluate_instance($m2u1);
        $mo->evaluate_instance($m2u2);
        $mo->evaluate_instance($m2u3);
        $mo->evaluate_instance($m2u4);

        // Fail 1, and success 2.
        $oo->increment_instance_counter($m3u1->get_instance_of_objective($m3obj1->get_id()), 2);
        $oo->increment_instance_counter($m3u2->get_instance_of_objective($m3obj1->get_id()), 10);
        $oo->increment_instance_counter($m3u3->get_instance_of_objective($m3obj1->get_id()), 10);
        $oo->increment_instance_counter($m3u4->get_instance_of_objective($m3obj1->get_id()), 3);
        $mo->end_instance($m3u1);
        $mo->evaluate_instance($m3u2);
        $mo->evaluate_instance($m3u3);
        $mo->evaluate_instance($m3u4);

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->annotate_completion_rate()
            ->annotate_inprogress_average_rate()
            ->annotate_success_rate();
        $reader = (new mission_reader())->use_query($query);

        $this->assertEquals(3, $reader->count());
        $results = collection_utils::iterable_to_array($reader->list(0, 0));
        $this->assertEquals(0.75, $results[0]['annotations']->completion_rate);
        $this->assertEquals(0, $results[0]['annotations']->inprogress_average_rate);
        $this->assertEquals(0, $results[0]['annotations']->success_rate);

        $this->assertEquals(0.25, $results[1]['annotations']->completion_rate);
        $this->assertEquals(0.5, $results[1]['annotations']->inprogress_average_rate);
        $this->assertEquals(0, $results[1]['annotations']->success_rate);

        $this->assertEquals(0.75, $results[2]['annotations']->completion_rate);
        $this->assertEquals(0.3, $results[2]['annotations']->inprogress_average_rate);
        $this->assertGreaterThan(0.66, $results[2]['annotations']->success_rate);
        $this->assertLessThan(0.67, $results[2]['annotations']->success_rate);
    }

    public function test_order_by_annotated_rate(): void {
        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $sysctx = context_system::instance();

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();

        $m1 = $gudg->create_persisted_mission([
            'type' => mission::TYPE_CHALLENGE,
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);
        $m1obj1 = $m1->get_objectives()[0];
        $m2 = $gudg->create_persisted_mission([
            'type' => mission::TYPE_CHALLENGE,
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);
        $m2obj1 = $m2->get_objectives()[0];
        $m3 = $gudg->create_persisted_mission([
            'type' => mission::TYPE_CHALLENGE,
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);
        $m3obj1 = $m3->get_objectives()[0];

        $m1u1 = $mo->assign_mission($m1, $u1->id);
        $m1u2 = $mo->assign_mission($m1, $u2->id);
        $m1u3 = $mo->assign_mission($m1, $u3->id);
        $m1u4 = $mo->assign_mission($m1, $u4->id);

        $m2u1 = $mo->assign_mission($m2, $u1->id);
        $m2u2 = $mo->assign_mission($m2, $u2->id);
        $m2u3 = $mo->assign_mission($m2, $u3->id);
        $m2u4 = $mo->assign_mission($m2, $u4->id);

        $m3u1 = $mo->assign_mission($m3, $u1->id);
        $m3u2 = $mo->assign_mission($m3, $u2->id);
        $m3u3 = $mo->assign_mission($m3, $u3->id);
        $m3u4 = $mo->assign_mission($m3, $u4->id);

        // Completion rate 25%, success rate 0%, progress rate 0%.
        $mo->end_instance($m1u1);

        // Completion rate 100%, success rate 33%, progress rate 10%.
        $mo->end_instance($m2u1);
        $mo->end_instance($m2u2);
        $oo->increment_instance_counter($m2u3->get_instance_of_objective($m2obj1->get_id()), 10);
        $oo->increment_instance_counter($m2u4->get_instance_of_objective($m2obj1->get_id()), 1);
        $mo->evaluate_instance($m2u3);
        $mo->evaluate_instance($m2u4);

        // Completion rate 50%, success rate 0%, progress rate 15%.
        $mo->end_instance($m3u1);
        $mo->end_instance($m3u2);
        $oo->increment_instance_counter($m3u3->get_instance_of_objective($m3obj1->get_id()), 3);
        $mo->evaluate_instance($m3u3);

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->add_order_by_completion_rate(SORT_ASC);
        $reader = (new mission_reader())->use_query($query);
        $this->assert_persistent_list_from_reader([$m1, $m3, $m2], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->add_order_by_completion_rate(SORT_DESC);
        $reader = (new mission_reader())->use_query($query);
        $this->assert_persistent_list_from_reader([$m2, $m3, $m1], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->add_order_by_inprogress_average_rate(SORT_ASC);
        $reader = (new mission_reader())->use_query($query);
        $this->assert_persistent_list_from_reader([$m1, $m2, $m3], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->add_order_by_inprogress_average_rate(SORT_DESC);
        $reader = (new mission_reader())->use_query($query);
        $this->assert_persistent_list_from_reader([$m3, $m2, $m1], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->add_order_by_success_rate(SORT_ASC);
        $reader = (new mission_reader())->use_query($query);
        $this->assert_persistent_list_from_reader([$m1, $m3, $m2], $reader->list(0, 0));

        $query = (new mission_query($sysctx))
            ->set_context_id(SYSCONTEXTID)
            ->add_order_by_success_rate(SORT_DESC);
        $reader = (new mission_reader())->use_query($query);
        $this->assert_persistent_list_from_reader([$m2, $m1, $m3], $reader->list(0, 0));
    }

    protected function assert_persistent_list_from_reader(array $expecteditems, iterable $listfromreader) {
        $i = 0;
        foreach ($listfromreader as $readerrow) {
            $actualpersistent = $readerrow[mission::class];
            $expected = $expecteditems[$i++];
            $persistent = $expected instanceof persisted_mission ? $expected->get_persistent() : $expected;
            $this->assert_persistent_equals($persistent, $actualpersistent);
        }

    }

}
