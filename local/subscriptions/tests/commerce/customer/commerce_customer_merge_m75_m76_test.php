<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergeExecutionService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergePlanner;

/**
 * @covers \local_subscriptions\commerce\customer\merge\CommerceCustomerLearningMergeService
 * @covers \local_subscriptions\commerce\customer\merge\CommerceCustomerLegacyConsolidationService
 * @covers \local_subscriptions\commerce\customer\merge\CommerceCustomerMergeExecutionService
 */
final class commerce_customer_merge_m75_m76_test extends advanced_testcase {
    public function test_shared_course_completion_keeps_completed_state(): void {
        global $DB;

        $this->resetAfterTest(true);
        $target = $this->getDataGenerator()->create_user(['email' => 'm756-target@example.test']);
        $source = $this->getDataGenerator()->create_user(['email' => 'm756-source@example.test']);
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->enrol_user($target->id, $course->id);
        $this->getDataGenerator()->enrol_user($source->id, $course->id);

        $DB->delete_records('course_completions', ['course' => $course->id, 'userid' => $target->id]);
        $DB->delete_records('course_completions', ['course' => $course->id, 'userid' => $source->id]);
        $now = time();
        $DB->insert_record('course_completions', (object)[
            'userid' => $target->id,
            'course' => $course->id,
            'timeenrolled' => $now - 200,
            'timestarted' => $now - 100,
            'timecompleted' => null,
            'reaggregate' => 0,
        ]);
        $DB->insert_record('course_completions', (object)[
            'userid' => $source->id,
            'course' => $course->id,
            'timeenrolled' => $now - 300,
            'timestarted' => $now - 250,
            'timecompleted' => $now - 50,
            'reaggregate' => 0,
        ]);

        $result = $this->execute_merge((int)$source->id, (int)$target->id);

        $completion = $DB->get_record('course_completions', [
            'userid' => $target->id,
            'course' => $course->id,
        ], '*', MUST_EXIST);
        self::assertSame($now - 50, (int)$completion->timecompleted);
        self::assertSame(0, $DB->count_records('course_completions', ['userid' => $source->id]));
        self::assertGreaterThanOrEqual(1, $result->transfers['learning_coursecompletionsmerged']);
        self::assertSame(1, (int)$DB->get_field('user', 'suspended', ['id' => $source->id]));
    }

    public function test_legacy_subscription_and_payment_request_follow_retained_account(): void {
        global $DB;

        $this->resetAfterTest(true);
        $target = $this->getDataGenerator()->create_user(['email' => 'legacy-target@example.test']);
        $source = $this->getDataGenerator()->create_user(['email' => 'legacy-source@example.test']);
        $now = time();

        $subscriptionid = (int)$DB->insert_record('user_subscription', (object)[
            'userid' => $source->id,
            'planid' => 987654,
            'pricepaid' => 10.00,
            'currency' => 'EUR',
            'transactionid' => 'm756-sub-' . random_int(1000, 9999),
            'payment_provider' => 'manual',
            'start_date' => $now,
            'end_date' => $now + DAYSECS,
            'status' => 'active',
            'last_update' => $now,
            'creation_date' => $now,
        ]);
        $requestid = (int)$DB->insert_record('subscription_payment_request', (object)[
            'planid' => null,
            'userid' => $source->id,
            'email' => $source->email,
            'firstname' => $source->firstname,
            'lastname' => $source->lastname,
            'subscriptionid' => $subscriptionid,
            'currency' => 'EUR',
            'price' => 10.00,
            'amount_minor' => 1000,
            'payment_provider' => 'manual',
            'status' => 'paid',
            'creation_date' => $now,
            'last_update' => $now,
            'operation' => 'purchase_new',
        ]);

        $result = $this->execute_merge((int)$source->id, (int)$target->id);

        self::assertSame((int)$target->id, (int)$DB->get_field('user_subscription', 'userid', ['id' => $subscriptionid]));
        self::assertSame((int)$target->id, (int)$DB->get_field('subscription_payment_request', 'userid', ['id' => $requestid]));
        self::assertSame(1, $result->transfers['legacysubscriptions']);
        self::assertSame(1, $result->transfers['legacypaymentrequests']);
    }

    public function test_native_beneficiary_email_is_rewritten_to_retained_identity(): void {
        global $DB;

        $this->resetAfterTest(true);
        $target = $this->getDataGenerator()->create_user(['email' => 'retained@example.test']);
        $source = $this->getDataGenerator()->create_user(['email' => 'old@example.test']);
        $now = time();

        $grantid = (int)$DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'gr_m756_' . bin2hex(random_bytes(4)),
            'idempotencykey' => 'idem_m756_' . bin2hex(random_bytes(6)),
            'purchasereference' => 'cmp_m756_' . bin2hex(random_bytes(6)),
            'itemreference' => 'm756',
            'productsku' => 'M756.TEST',
            'type' => 'course_access',
            'resourcekey' => 'course:999999',
            'quantity' => 1,
            'beneficiaryuserid' => $source->id,
            'beneficiaryemail' => $source->email,
            'validfrom' => $now,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $this->execute_merge((int)$source->id, (int)$target->id);
        $grant = $DB->get_record('local_subs_commerce_grant', ['id' => $grantid], '*', MUST_EXIST);
        self::assertSame((int)$target->id, (int)$grant->beneficiaryuserid);
        self::assertSame('retained@example.test', $grant->beneficiaryemail);
    }

    private function execute_merge(int $sourceuserid, int $targetuserid) {
        global $DB;
        return (new CommerceCustomerMergeExecutionService(
            $DB,
            new CommerceCustomerMergePlanner($DB)
        ))->execute([$sourceuserid, $targetuserid], $targetuserid, $targetuserid);
    }
}
