<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\education;

defined('MOODLE_INTERNAL') || die();

/** Contract tests for the L6.1 background finalisation wiring. */
final class commerce_797l61_progressive_access_finalizer_test extends \advanced_testcase {
    public function test_background_finalizer_contract_is_registered(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/education/access/CommerceProgressiveAccessFinalizer.php'
        );
        $task = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/task/finalise_progressive_pedagogical_access_task.php'
        );
        $tasks = file_get_contents($CFG->dirroot . '/local/subscriptions/db/tasks.php');

        self::assertStringContainsString('CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE', $service);
        self::assertStringContainsString('is_complete_at($promotionid, $now)', $service);
        self::assertStringContainsString('is_active($promotionid, $userid)', $service);
        self::assertStringContainsString('CommerceStudentAccessProfile::LIFETIME_FULL', $service);
        self::assertStringContainsString('CommerceProgressiveAccessFinalizer::create()->finalise(time())', $task);
        self::assertStringContainsString('finalise_progressive_pedagogical_access_task', $tasks);
        self::assertStringContainsString("'minute' => '*/5'", $tasks);
    }

    public function test_individual_resolver_remains_immediate_fallback(): void {
        global $CFG;

        $resolver = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/education/access/CommerceStudentCourseAccessResolver.php'
        );

        self::assertStringContainsString('is_complete_at(', $resolver);
        self::assertStringContainsString('CommerceStudentAccessProfile::LIFETIME_FULL', $resolver);
        self::assertStringContainsString('with_profile(', $resolver);
    }
}
