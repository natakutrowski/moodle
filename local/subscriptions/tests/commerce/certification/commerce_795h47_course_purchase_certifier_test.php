<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\certification\course\CommerceCoursePurchaseCertifier;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

final class commerce_795h47_course_purchase_certifier_test extends advanced_testcase {
    public function test_certifies_complete_native_course_purchase(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student', 'manual');
        $reference = $this->create_purchase((int)$user->id, 'fulfilled');
        $this->create_payment($reference, 'paid');
        $this->create_course_grant($reference, (int)$user->id, (int)$course->id, 'active', 'completed', true);

        $report = (new CommerceCoursePurchaseCertifier($DB))->certify($reference);

        self::assertTrue($report->certified);
        self::assertNotEmpty($report->checks);
        self::assertSame([], array_values(array_filter($report->checks, static fn(array $check): bool => $check['status'] !== 'PASS')));
    }

    public function test_rejects_missing_moodle_enrolment(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $reference = $this->create_purchase((int)$user->id, 'fulfilled');
        $this->create_payment($reference, 'paid');
        $this->create_course_grant($reference, (int)$user->id, (int)$course->id, 'active', 'completed', true);

        $report = (new CommerceCoursePurchaseCertifier($DB))->certify($reference);
        $check = array_values(array_filter($report->checks, static fn(array $item): bool => $item['key'] === 'moodle_enrolment'))[0];

        self::assertFalse($report->certified);
        self::assertSame('FAIL', $check['status']);
    }

    private function create_purchase(int $userid, string $status): string {
        global $DB;
        $now = time();
        $reference = 'cmp_' . substr(md5((string)microtime(true) . random_int(1, 999999)), 0, 24);
        $DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => md5('uuid-' . $reference),
            'reference' => $reference,
            'type' => 'subscription',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => $userid,
            'customeremail' => 'student@campusfr.test',
            'status' => $status,
            'currency' => 'EUR',
            'subtotalminor' => 1000,
            'discountminor' => 0,
            'totalminor' => 1000,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return $reference;
    }

    private function create_payment(string $reference, string $status): void {
        global $DB;
        $purchaseid = (int)$DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'id', ['reference' => $reference], MUST_EXIST);
        $now = time();
        $DB->insert_record(CommercePersistenceSchema::TABLE_PAYMENT, (object)[
            'purchaseid' => $purchaseid,
            'sequence' => 0,
            'provider' => 'stripe',
            'providerreference' => 'pi_' . $reference,
            'providerorderid' => null,
            'status' => $status,
            'currency' => 'EUR',
            'amountminor' => 1000,
            'transactionid' => 'txn_' . $reference,
            'legacyrequestid' => null,
            'paidat' => $status === 'paid' ? $now : null,
            'metadatajson' => '{}',
            'paymenturl' => null,
            'providerpayload' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function create_course_grant(string $reference, int $userid, int $courseid, string $grantstatus, string $fulfillmentstatus, bool $attempt): void {
        global $DB;
        $now = time();
        $grantreference = 'ent-' . substr(md5($reference), 0, 24);
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => 'grant-' . $grantreference,
            'purchasereference' => $reference,
            'itemreference' => 'course-item',
            'productsku' => 'COURSE-A1',
            'type' => 'course_access',
            'resourcekey' => 'course:' . $courseid . ':full',
            'quantity' => 1,
            'beneficiaryuserid' => $userid,
            'beneficiaryemail' => 'student@campusfr.test',
            'validfrom' => $now,
            'validuntil' => null,
            'status' => $grantstatus,
            'configurationjson' => json_encode(['courseid' => $courseid]),
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ful_state', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => 'state-' . $grantreference,
            'granttype' => 'course_access',
            'handlerclass' => 'CourseHandler',
            'status' => $fulfillmentstatus,
            'attempts' => $attempt ? 1 : 0,
            'lastexecutionreference' => $attempt ? 'exec-' . $grantreference : null,
            'lastsource' => 'phpunit',
            'lastactoruserid' => null,
            'lastpayloadjson' => json_encode(['courseid' => $courseid]),
            'lastmessage' => null,
            'lasterrorclass' => null,
            'timecreated' => $now,
            'timestarted' => $now,
            'timecompleted' => $fulfillmentstatus === 'completed' ? $now : null,
            'timemodified' => $now,
        ]);
        if ($attempt) {
            $DB->insert_record('local_subs_commerce_ful_attempt', (object)[
                'grantreference' => $grantreference,
                'idempotencykey' => 'attempt-' . $grantreference,
                'executionreference' => 'exec-' . $grantreference,
                'granttype' => 'course_access',
                'handlerclass' => 'CourseHandler',
                'status' => 'completed',
                'dryrun' => 0,
                'source' => 'phpunit',
                'actoruserid' => null,
                'payloadjson' => json_encode(['courseid' => $courseid]),
                'message' => null,
                'errorclass' => null,
                'timestarted' => $now,
                'timecompleted' => $now,
            ]);
        }
    }
}
