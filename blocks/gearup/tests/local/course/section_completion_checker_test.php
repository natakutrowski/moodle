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

namespace block_gearup\local\course;

use availability_date\condition;
use block_gearup\local\course\section_completion_checker;
use block_gearup\local\utils\course_utils;
use block_gearup\tests\base_testcase;
use completion_info;
use core_availability\tree;

/**
 * Tests.
 *
 * @package    block_gearup
 * @category   test
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\course\section_completion_checker
 */
final class section_completion_checker_test extends base_testcase {

    /**
     * Test setting the modinfo.
     */
    public function test_setting_invalid_course_modinfo(): void {
        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $u1 = $dg->create_user();

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Course ID mismatch');
        $checker = new section_completion_checker($c1->id, $u1->id);
        $checker->set_modinfo(course_utils::get_modinfo($c2->id, $u1->id));
    }

    /**
     * Test setting the modinfo.
     */
    public function test_setting_invalid_user_modinfo(): void {
        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('User ID mismatch');
        $checker = new section_completion_checker($c1->id, $u1->id);
        $checker->set_modinfo(course_utils::get_modinfo($c1->id, $u2->id));
    }

    /**
     * Test setting the completion info.
     */
    public function test_setting_invalid_completion_info(): void {
        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $u1 = $dg->create_user();

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Course ID mismatch');
        $checker = new section_completion_checker($c1->id, $u1->id);
        $checker->set_completion_info(course_utils::get_completion_info($c2->id));
    }

    /**
     * Test completion.
     */
    public function test_basic_section_completion_manual(): void {
        $dg = $this->getDataGenerator();

        $this->assertTrue(completion_info::is_enabled_for_site());
        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $cm1 = get_coursemodule_from_id('page', $page1->cmid);
        $cm2 = get_coursemodule_from_id('page', $page2->cmid);

        $completioninfo = course_utils::get_completion_info($c1->id);
        $completioninfo->update_state($cm1, COMPLETION_COMPLETE, $u1->id);
        $completioninfo->update_state($cm2, COMPLETION_COMPLETE, $u1->id);

        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertTrue($checker->is_completed($section1num));
    }

    /**
     * Test completion.
     */
    public function test_basic_section_completion_auto(): void {
        $dg = $this->getDataGenerator();

        $this->assertTrue(completion_info::is_enabled_for_site());
        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_AUTOMATIC]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_AUTOMATIC]);
        $cm1 = get_coursemodule_from_id('page', $page1->cmid);
        $cm2 = get_coursemodule_from_id('page', $page2->cmid);

        $completioninfo = course_utils::get_completion_info($c1->id);
        $completioninfo->update_state($cm1, COMPLETION_COMPLETE, $u1->id);
        $completioninfo->update_state($cm2, COMPLETION_COMPLETE, $u1->id);

        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertTrue($checker->is_completed($section1num));
    }

    /**
     * Test completion disabled.
     */
    public function test_global_completion_disabled(): void {
        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $cm1 = get_coursemodule_from_id('page', $page1->cmid);
        $cm2 = get_coursemodule_from_id('page', $page2->cmid);

        $completioninfo = course_utils::get_completion_info($c1->id);
        $completioninfo->update_state($cm1, COMPLETION_COMPLETE, $u1->id);
        $completioninfo->update_state($cm2, COMPLETION_COMPLETE, $u1->id);

        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertTrue($checker->is_completed($section1num));

        // Disable completion.
        set_config('enablecompletion', COMPLETION_DISABLED);
        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertFalse($checker->is_completed($section1num));
    }

    /**
     * Test completion disabled.
     */
    public function test_course_completion_disabled(): void {
        global $DB;

        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $cm1 = get_coursemodule_from_id('page', $page1->cmid);
        $cm2 = get_coursemodule_from_id('page', $page2->cmid);

        $completioninfo = course_utils::get_completion_info($c1->id);
        $completioninfo->update_state($cm1, COMPLETION_COMPLETE, $u1->id);
        $completioninfo->update_state($cm2, COMPLETION_COMPLETE, $u1->id);

        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertTrue($checker->is_completed($section1num));

        // Disable completion.
        $DB->update_record('course', ['id' => $c1->id, 'enablecompletion' => COMPLETION_DISABLED]);
        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertFalse($checker->is_completed($section1num));
    }

    /**
     * Test no modules.
     */
    public function test_no_modules(): void {
        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);

        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertFalse($checker->is_completed($section1num));
    }

    /**
     * Test no modules with completion.
     */
    public function test_no_modules_with_completion(): void {
        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_DISABLED]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_DISABLED]);

        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertFalse($checker->is_completed($section1num));
    }

    /**
     * Test mixed completion enabled without completion.
     */
    public function test_mixed_completion_enabled_without_completion(): void {
        $dg = $this->getDataGenerator();

        $this->assertTrue(completion_info::is_enabled_for_site());
        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_DISABLED]);
        $page3 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_AUTOMATIC]);

        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertFalse($checker->is_completed($section1num));
    }

    /**
     * Test mixed completion enabled with partial completion.
     */
    public function test_mixed_completion_enabled_with_partial_completion(): void {
        $dg = $this->getDataGenerator();

        $this->assertTrue(completion_info::is_enabled_for_site());
        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_DISABLED]);
        $page3 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_AUTOMATIC]);
        $cm1 = get_coursemodule_from_id('page', $page1->cmid);

        $completioninfo = course_utils::get_completion_info($c1->id);
        $completioninfo->update_state($cm1, COMPLETION_COMPLETE, $u1->id);

        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertFalse($checker->is_completed($section1num));
    }

    /**
     * Test mixed completion enabled with completion.
     */
    public function test_mixed_completion_enabled_with_completion(): void {
        $dg = $this->getDataGenerator();

        $this->assertTrue(completion_info::is_enabled_for_site());
        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_DISABLED]);
        $cm1 = get_coursemodule_from_id('page', $page1->cmid);
        $cm2 = get_coursemodule_from_id('page', $page2->cmid);

        $completioninfo = course_utils::get_completion_info($c1->id);
        $completioninfo->update_state($cm1, COMPLETION_COMPLETE, $u1->id);

        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertTrue($checker->is_completed($section1num));
    }

    /**
     * Test mixed completion states.
     */
    public function test_mixed_completion_state(): void {
        global $CFG;
        $dg = $this->getDataGenerator();

        $this->assertTrue(completion_info::is_enabled_for_site());
        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $cm1 = get_coursemodule_from_id('page', $page1->cmid);
        $cm2 = get_coursemodule_from_id('page', $page2->cmid);

        $completioninfo = course_utils::get_completion_info($c1->id);
        $completioninfo->update_state($cm1, COMPLETION_COMPLETE, $u1->id);
        $completioninfo->update_state($cm2, COMPLETION_INCOMPLETE, $u1->id);

        $checker = new section_completion_checker($c1->id, $u1->id);
        $this->assertFalse($checker->is_completed($section1num));
    }

    /**
     * Provider.
     *
     * @return array
     */
    public static function mixed_completion_with_hidden_provider(): array {
        return [
            'default_unavailable' => [null, false],
            'unavailable_counted_in' => [false, false],
            'unavailable_counted_out' => [true, true],
        ];
    }

    /**
     * Test mixed completion with hidden.
     *
     * @dataProvider mixed_completion_with_hidden_provider
     * @param ?bool $ignoreunavailable
     * @param bool $expected
     */
    public function test_mixed_completion_with_hidden(?bool $ignoreunavailable, bool $expected): void {
        global $CFG;
        $dg = $this->getDataGenerator();

        $this->assertTrue(completion_info::is_enabled_for_site());
        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL, 'visible' => 0]);
        $cm1 = get_coursemodule_from_id('page', $page1->cmid);
        $cm2 = get_coursemodule_from_id('page', $page2->cmid);

        $completioninfo = course_utils::get_completion_info($c1->id);
        $completioninfo->update_state($cm1, COMPLETION_COMPLETE, $u1->id);
        $completioninfo->update_state($cm2, COMPLETION_INCOMPLETE, $u1->id);

        $checker = new section_completion_checker($c1->id, $u1->id);
        if ($ignoreunavailable !== null) {
            $checker->set_ignore_unavailable($ignoreunavailable);
        }
        $this->assertEquals($expected, $checker->is_completed($section1num));
    }

    /**
     * Provider.
     *
     * @return array
     */
    public static function mixed_completion_with_unavailable_provider(): array {
        return [
            // Ignore unavailable, show availability info, expected value.
            'default_unavailable' => [null, true, false],
            'unavailable_counted_in' => [false, true, false],
            'unavailable_counted_out' => [true, true, true],

            // Unavailable activities count the same whether shown or not.
            'default_unavailable_hidden' => [null, false, false],
            'unavailable_counted_in_hidden' => [false, false, false],
            'unavailable_counted_out_hidden' => [true, false, true],
        ];
    }

    /**
     * Test mixed completion with unavailable.
     *
     * @dataProvider mixed_completion_with_unavailable_provider
     * @param ?bool $ignoreunavailable
     * @param bool $show
     * @param bool $expected
     */
    public function test_mixed_completion_with_unavailable(?bool $ignoreunavailable, bool $show, bool $expected): void {
        $dg = $this->getDataGenerator();

        $this->assertTrue(completion_info::is_enabled_for_site());
        $c1 = $dg->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $u1 = $dg->create_user();

        $section1 = $dg->create_course_section(['course' => $c1->id, 'section' => 1]);
        $section1num = $this->get_section_number($section1);
        $unavailablefor1year = tree::get_root_json([condition::get_json(condition::DIRECTION_FROM, time() + YEARSECS)],
            tree::OP_AND,
            $show
        );
        $page1 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $page2 = $dg->create_module('page', ['course' => $c1->id, 'section' => $section1num,
            'completion' => COMPLETION_TRACKING_MANUAL, 'availability' => json_encode($unavailablefor1year)]);
        $cm1 = get_coursemodule_from_id('page', $page1->cmid);
        $cm2 = get_coursemodule_from_id('page', $page2->cmid);

        $completioninfo = course_utils::get_completion_info($c1->id);
        $completioninfo->update_state($cm1, COMPLETION_COMPLETE, $u1->id);
        $completioninfo->update_state($cm2, COMPLETION_INCOMPLETE, $u1->id);

        $checker = new section_completion_checker($c1->id, $u1->id);
        if ($ignoreunavailable !== null) {
            $checker->set_ignore_unavailable($ignoreunavailable);
        }
        $this->assertEquals($expected, $checker->is_completed($section1num));
    }

    /**
     * Get the section number.
     *
     * @param \section_info $info
     */
    protected function get_section_number(\section_info $info) {
        global $CFG;
        if ($CFG->branch < 404) {
            return $info->section;
        }
        return $info->sectionnum;
    }
}
