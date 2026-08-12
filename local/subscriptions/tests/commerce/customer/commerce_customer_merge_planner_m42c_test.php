<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergePlanner;

/**
 * @covers \local_subscriptions\commerce\customer\merge\CommerceCustomerMergePlanner
 */
final class commerce_customer_merge_planner_m42c_test extends advanced_testcase {
    public function test_pedagogical_history_is_prioritised_for_recommended_target(): void {
        global $DB;

        $this->resetAfterTest(true);

        $quiet = $this->getDataGenerator()->create_user([
            'firstname' => 'Alice',
            'lastname' => 'Merge',
            'email' => 'alice.one@example.com',
            'confirmed' => 1,
        ]);
        $active = $this->getDataGenerator()->create_user([
            'firstname' => 'Alice',
            'lastname' => 'Merge',
            'email' => 'alice.two@example.com',
            'confirmed' => 1,
        ]);

        $course = $this->getDataGenerator()->create_course([
            'enablecompletion' => 1,
        ]);
        $this->getDataGenerator()->enrol_user($active->id, $course->id);

        $plan = (new CommerceCustomerMergePlanner($DB))->build([
            $quiet->id,
            $active->id,
        ]);

        self::assertSame((int)$active->id, $plan->recommendedtargetuserid);
        self::assertSame((int)$active->id, $plan->targetuserid);
    }

    public function test_manual_target_can_override_recommendation_without_writing(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user();
        $second = $this->getDataGenerator()->create_user();

        $beforefirst = $DB->get_record('user', ['id' => $first->id], '*', MUST_EXIST);
        $beforesecond = $DB->get_record('user', ['id' => $second->id], '*', MUST_EXIST);

        $plan = (new CommerceCustomerMergePlanner($DB))->build(
            [$first->id, $second->id],
            (int)$second->id
        );

        self::assertSame((int)$second->id, $plan->targetuserid);

        $afterfirst = $DB->get_record('user', ['id' => $first->id], '*', MUST_EXIST);
        $aftersecond = $DB->get_record('user', ['id' => $second->id], '*', MUST_EXIST);

        self::assertEquals($beforefirst, $afterfirst);
        self::assertEquals($beforesecond, $aftersecond);
    }

    public function test_different_emails_produce_visible_warning(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'email' => 'one@example.com',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'email' => 'two@example.com',
        ]);

        $plan = (new CommerceCustomerMergePlanner($DB))->build([
            $first->id,
            $second->id,
        ]);

        self::assertContains(
            CommerceCustomerMergePlanner::WARNING_DIFFERENT_EMAILS,
            array_column($plan->warnings, 'type')
        );
    }
}
