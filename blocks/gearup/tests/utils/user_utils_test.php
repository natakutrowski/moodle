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
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\tests\utils;

use block_gearup\local\utils\user_utils;
use block_gearup\tests\base_testcase;
use context_course;
use RecursiveArrayIterator;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     block_gearup\local\utils\user_utils
 */
final class user_utils_test extends base_testcase {

    public function test_can_select_group_no_groups(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['groupmode' => NOGROUPS]);
        $c1ctx = context_course::instance($c1->id);
        $u1 = $dg->create_user();
        $g1 = $dg->create_group(['courseid' => $c1->id]);

        $dg->enrol_user($u1->id, $c1->id, 'editingteacher');
        $dg->create_group_member(['groupid' => $g1->id, 'userid' => $u1->id]);

        $this->assert_cannot_select_group($c1, $u1, $g1);
    }

    public function test_can_select_group_visible_groups(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['groupmode' => VISIBLEGROUPS]);
        $c1ctx = context_course::instance($c1->id);
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $c1g1 = $dg->create_group(['courseid' => $c1->id]);
        $c1g2 = $dg->create_group(['courseid' => $c1->id]);

        $dg->enrol_user($u1->id, $c1->id, 'editingteacher');
        $dg->create_group_member(['groupid' => $c1g1->id, 'userid' => $u1->id]);

        $dg->enrol_user($u2->id, $c1->id, 'student');
        $dg->create_group_member(['groupid' => $c1g2->id, 'userid' => $u2->id]);

        // Teacher can select all groups.
        $this->assert_can_select_group($c1, $u1, $c1g1);
        $this->assert_can_select_group($c1, $u1, $c1g2);

        // Student can select all groups.
        $this->assert_can_select_group($c1, $u2, $c1g1);
        $this->assert_can_select_group($c1, $u2, $c1g2);
    }

    public function test_can_select_group_separate_groups(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['groupmode' => SEPARATEGROUPS]);
        $c1ctx = context_course::instance($c1->id);
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $c1g1 = $dg->create_group(['courseid' => $c1->id]);
        $c1g2 = $dg->create_group(['courseid' => $c1->id]);

        $dg->enrol_user($u1->id, $c1->id, 'editingteacher');
        $dg->enrol_user($u2->id, $c1->id, 'student');
        $dg->enrol_user($u3->id, $c1->id, 'student');

        $dg->create_group_member(['groupid' => $c1g1->id, 'userid' => $u1->id]);
        $dg->create_group_member(['groupid' => $c1g2->id, 'userid' => $u3->id]);

        // User 1 is a teacher and can see all groups.
        $this->assert_can_select_group($c1, $u1, $c1g1);
        $this->assert_can_select_group($c1, $u1, $c1g2);

        // User 2 is a student that does not belong to any group.
        $this->assert_cannot_select_group($c1, $u2, $c1g1);
        $this->assert_cannot_select_group($c1, $u2, $c1g2);

        // User 2 is a student that does only belongs to group 2.
        $this->assert_cannot_select_group($c1, $u3, $c1g1);
        $this->assert_can_select_group($c1, $u3, $c1g2);
    }

    public function test_can_select_group_separate_groups_cannot_cheat(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['groupmode' => SEPARATEGROUPS]);
        $c2 = $dg->create_course(['groupmode' => VISIBLEGROUPS]);
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);
        $u1 = $dg->create_user();
        $c1g1 = $dg->create_group(['courseid' => $c1->id]);
        $c1g2 = $dg->create_group(['courseid' => $c1->id]);
        $c2g1 = $dg->create_group(['courseid' => $c2->id]);

        $dg->enrol_user($u1->id, $c1->id, 'student');
        $dg->enrol_user($u1->id, $c2->id, 'editingteacher');

        $dg->create_group_member(['groupid' => $c1g1->id, 'userid' => $u1->id]);
        $dg->create_group_member(['groupid' => $c2g1->id, 'userid' => $u1->id]);

        // User 1 is a member of group 1, not group 2, in c1.
        $this->assert_can_select_group($c1, $u1, $c1g1);
        $this->assert_cannot_select_group($c1, $u1, $c1g2);

        // User 1 is a member of group 1 in c2.
        $this->assert_can_select_group($c2, $u1, $c2g1);

        // User 1 cannnot abuse its right in c2 to view g2 from c1.
        $this->assert_cannot_select_group($c2, $u1, $c1g2);
    }

    public function test_can_view_all_participants_no_groups(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['groupmode' => NOGROUPS]);
        $u1 = $dg->create_user();
        $this->assert_can_view_all_participants($c1, $u1);
    }

    public function test_can_view_all_participants_visible_groups(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['groupmode' => VISIBLEGROUPS]);
        $u1 = $dg->create_user();
        $this->assert_can_view_all_participants($c1, $u1);
    }

    public function test_can_view_all_participants_separate_groups_without_membership(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['groupmode' => SEPARATEGROUPS]);
        $u1 = $dg->create_user();
        $dg->enrol_user($u1->id, $c1->id, 'student');
        $this->assert_can_view_all_participants($c1, $u1);
    }

    public function test_can_view_all_participants_separate_groups_with_membership(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['groupmode' => SEPARATEGROUPS]);
        $u1 = $dg->create_user();
        $dg->enrol_user($u1->id, $c1->id, 'student');
        $g1 = $dg->create_group(['courseid' => $c1->id]);
        $dg->create_group_member(['groupid' => $g1->id, 'userid' => $u1->id]);
        $this->assert_cannot_view_all_participants($c1, $u1);
    }

    public function test_can_view_all_participants_separate_groups_with_access_all_groups(): void {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['groupmode' => SEPARATEGROUPS]);
        $u1 = $dg->create_user();
        $dg->enrol_user($u1->id, $c1->id, 'editingteacher');
        $g1 = $dg->create_group(['courseid' => $c1->id]);

        // Can view all participants regardless of membership.
        $this->assert_can_view_all_participants($c1, $u1);
        $dg->create_group_member(['groupid' => $g1->id, 'userid' => $u1->id]);
        $this->assert_can_view_all_participants($c1, $u1);
    }

    /**
     * Data provider for testing get_group_select_options.
     *
     * @return array
     */
    public static function get_group_select_options_provider(): array {
        return [
            // 1. NOGROUPS mode
            'nogroups_mode' => [
                'expected' => [],
                'groupmode' => NOGROUPS,
                'hasaag' => false,
                'defaultgrouping' => 0,
                'groupsdata' => [],
                'groupingsdata' => [],
                'usergroups' => [],
            ],

            // 2. SEPARATEGROUPS mode
            'separategroups_no_aag_user_in_no_groups' => [
                'expected' => [0],
                'groupmode' => SEPARATEGROUPS,
                'hasaag' => false,
                'defaultgrouping' => 0,
                'groupsdata' => [1, 2],
                'groupingsdata' => [],
                'usergroups' => [],
            ],
            'separategroups_no_aag_user_in_one_group' => [
                'expected' => [1],
                'groupmode' => SEPARATEGROUPS,
                'hasaag' => false,
                'defaultgrouping' => 0,
                'groupsdata' => [1, 2],
                'groupingsdata' => [],
                'usergroups' => [1],
            ],
            'separategroups_no_aag_user_in_multiple_groups' => [
                'expected' => [1, 2],
                'groupmode' => SEPARATEGROUPS,
                'hasaag' => false,
                'defaultgrouping' => 0,
                'groupsdata' => [1, 2, 3],
                'groupingsdata' => [],
                'usergroups' => [1, 2],
            ],
            'separategroups_with_aag_user_in_no_groups' => [
                'expected' => [0, 1, 2],
                'groupmode' => SEPARATEGROUPS,
                'hasaag' => true,
                'defaultgrouping' => 0,
                'groupsdata' => [1, 2],
                'groupingsdata' => [],
                'usergroups' => [],
            ],
            'separategroups_with_aag_user_in_multiple_groups' => [
                'expected' => [0, 1, 2],
                'groupmode' => SEPARATEGROUPS,
                'hasaag' => true,
                'defaultgrouping' => 0,
                'groupsdata' => [1, 2],
                'groupingsdata' => [],
                'usergroups' => [],
            ],

            // 3. VISIBLEGROUPS mode
            'visiblegroups_no_aag_user_in_no_groups' => [
                'expected' => [0, 1, 2],
                'groupmode' => VISIBLEGROUPS,
                'hasaag' => false,
                'defaultgrouping' => 0,
                'groupsdata' => [1, 2],
                'groupingsdata' => [],
                'usergroups' => [],
            ],
            'visiblegroups_no_aag_user_in_one_group' => [
                'expected' => [0, 1, 2],
                'groupmode' => VISIBLEGROUPS,
                'hasaag' => false,
                'defaultgrouping' => 0,
                'groupsdata' => [1, 2],
                'groupingsdata' => [],
                'usergroups' => [1],
            ],
            'visiblegroups_no_aag_user_in_multiple_groups' => [
                'expected' => [0, 1, 2, 3],
                'groupmode' => VISIBLEGROUPS,
                'hasaag' => false,
                'defaultgrouping' => 0,
                'groupsdata' => [1, 2, 3],
                'groupingsdata' => [],
                'usergroups' => [1, 2],
            ],
            'visiblegroups_with_aag_user_in_no_groups' => [
                'expected' => [0, 1, 2],
                'groupmode' => VISIBLEGROUPS,
                'hasaag' => true,
                'defaultgrouping' => 0,
                'groupsdata' => [1, 2],
                'groupingsdata' => [],
                'usergroups' => [],
            ],
            'visiblegroups_with_aag_user_in_multiple_groups' => [
                'expected' => [0, 1, 2, 3],
                'groupmode' => VISIBLEGROUPS,
                'hasaag' => true,
                'defaultgrouping' => 0,
                'groupsdata' => [1, 2, 3],
                'groupingsdata' => [],
                'usergroups' => [1, 2],
            ],

            // 4. With grouping
            'separategroups_no_aag_with_grouping_user_in_one_group' => [
                'expected' => [1],
                'groupmode' => SEPARATEGROUPS,
                'hasaag' => false,
                'defaultgrouping' => 1,
                'groupsdata' => [1, 2],
                'groupingsdata' => [1 => [1]],
                'usergroups' => [1],
            ],
            'separategroups_no_aag_with_grouping_user_in_multiple_groups' => [
                'expected' => [1],
                'groupmode' => SEPARATEGROUPS,
                'hasaag' => false,
                'defaultgrouping' => 1,
                'groupsdata' => [1, 2, 3],
                'groupingsdata' => [1 => [1]],
                'usergroups' => [1, 2],
            ],
            'visiblegroups_no_aag_with_grouping_user_in_one_group' => [
                'expected' => [0, 1],
                'groupmode' => VISIBLEGROUPS,
                'hasaag' => false,
                'defaultgrouping' => 1,
                'groupsdata' => [1, 2],
                'groupingsdata' => [1 => [1]],
                'usergroups' => [1],
            ],
            'visiblegroups_with_aag_with_grouping_user_in_multiple_groups' => [
                'expected' => [0, 1],
                'groupmode' => VISIBLEGROUPS,
                'hasaag' => true,
                'defaultgrouping' => 1,
                'groupsdata' => [1, 2, 3],
                'groupingsdata' => [1 => [1]],
                'usergroups' => [1, 2],
            ],
        ];
    }

    /**
     * Test get_group_select_options method.
     *
     * @dataProvider get_group_select_options_provider
     * @covers \block_gearup\local\utils\user_utils::get_group_select_options
     * @param array $expected
     * @param int $groupmode
     * @param bool $hasaag
     * @param mixed $defaultgrouping
     * @param array $groupsdata
     * @param array $groupingsdata
     * @param array $usergroups
     */
    public function test_get_group_select_options_group_ids(array $expected, int $groupmode, bool $hasaag, $defaultgrouping,
            array $groupsdata, array $groupingsdata, array $usergroups): void {

        global $DB;
        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['groupmode' => $groupmode]);
        $context = \context_course::instance($course->id);

        $user = $dg->create_user();
        $dg->enrol_user($user->id, $course->id);

        $groups = [];
        foreach ($groupsdata as $groupidnumber) {
            $group = $dg->create_group(['courseid' => $course->id, 'idnumber' => $groupidnumber]);
            $groups[$groupidnumber] = $group;
        }

        $groupings = [];
        foreach ($groupingsdata as $groupingidnumber => $groupingassignments) {
            $grouping = $dg->create_grouping(['courseid' => $course->id, 'idnumber' => $groupingidnumber]);
            $groupings[$groupingidnumber] = $grouping;
            foreach ($groupingassignments as $groupidnumber) {
                $dg->create_grouping_group(['groupingid' => $grouping->id, 'groupid' => $groups[$groupidnumber]->id]);
            }
        }
        if (!empty($defaultgrouping)) {
            $DB->set_field('course', 'defaultgroupingid', $groupings[$defaultgrouping]->id, ['id' => $course->id]);
        }

        foreach ($usergroups as $groupidnumber) {
            $dg->create_group_member(['groupid' => $groups[$groupidnumber]->id, 'userid' => $user->id]);
        }

        if ($hasaag) {
            $aagrole = $dg->create_role();
            assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $aagrole, $context);
            role_assign($aagrole, $user->id, $context);
        }

        $expectedids = array_values(array_map(function($groupidnumber) use ($groups) {
            if ($groupidnumber === 0) {
                return 0;
            }
            return $groups[$groupidnumber]->id;
        }, $expected));
        sort($expectedids);
        $this->assert_get_group_select_options_group_ids($course, $user, $expectedids);
    }

    /**
     * Assert that a user can select a group.
     *
     * @param object $course
     * @param object $user
     * @param object $group
     */
    protected function assert_can_select_group($course, $user, $group) {
        $this->assertTrue(user_utils::can_select_group($course, $user->id, $group->id));
        $this->assertTrue(user_utils::can_select_group($course->id, $user->id, $group->id));
        $this->assertTrue(user_utils::can_select_group(context_course::instance($course->id), $user->id, $group->id));
    }

    /**
     * Assert that a user cannot select a group.
     *
     * @param object $course
     * @param object $user
     * @param object $group
     */
    protected function assert_cannot_select_group($course, $user, $group) {
        $this->assertFalse(user_utils::can_select_group($course, $user->id, $group->id));
        $this->assertFalse(user_utils::can_select_group($course->id, $user->id, $group->id));
        $this->assertFalse(user_utils::can_select_group(context_course::instance($course->id), $user->id, $group->id));
    }

    /**
     * Assert that a user can view all participants.
     *
     * @param object $course
     * @param object $user
     * @param object $group
     */
    protected function assert_can_view_all_participants($course, $user) {
        $this->assertTrue(user_utils::can_view_all_participants($course, $user->id));
        $this->assertTrue(user_utils::can_view_all_participants($course->id, $user->id));
        $this->assertTrue(user_utils::can_view_all_participants(context_course::instance($course->id), $user->id));
    }

    /**
     * Assert that a user cannot view all participants.
     *
     * @param object $course
     * @param object $user
     * @param object $group
     */
    protected function assert_cannot_view_all_participants($course, $user) {
        $this->assertFalse(user_utils::can_view_all_participants($course, $user->id));
        $this->assertFalse(user_utils::can_view_all_participants($course->id, $user->id));
        $this->assertFalse(user_utils::can_view_all_participants(context_course::instance($course->id), $user->id));
    }

    /**
     * Assert that the group select options contain the expected group IDs.
     *
     * @param object $course
     * @param object $user
     * @param array $groupids
     */
    protected function assert_get_group_select_options_group_ids($course, $user, $groupids) {
        sort($groupids);
        $flatten = function($result) {
            if (isset($result[1]) && is_array($result[1])) {
                $result += reset($result[1]);
                unset($result[1]);
            }
            if (isset($result[2]) && is_array($result[2])) {
                $result += reset($result[2]);
                unset($result[2]);
            }
            $keys = array_keys($result);
            sort($keys);
            return $keys;
        };
        $options = user_utils::get_group_select_options($course, $user->id);
        $this->assertEquals($flatten($options), $groupids);
        $options = user_utils::get_group_select_options($course->id, $user->id);
        $this->assertEquals($flatten($options), $groupids);
        $options = user_utils::get_group_select_options(context_course::instance($course->id), $user->id);
        $this->assertEquals($flatten($options), $groupids);
    }

}
