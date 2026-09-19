<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\native\course\service\MoodleCourseEnrolmentService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\revocation\CommercePurchaseRightsRevocationService;

final class commerce_797m83f_overlapping_course_rights_revocation_test extends \advanced_testcase {
    public function test_revoking_bundle_course_grant_preserves_course_when_older_active_grant_still_covers_it(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $now = time();

        $enrolment = (new MoodleCourseEnrolmentService())->apply(
            (int)$user->id,
            (int)$course->id,
            'student',
            $now - 30,
            null,
            false
        );
        self::assertSame('created', $enrolment['status']);

        $olderpurchase = $this->purchase((int)$user->id, 'cmp_m83f_older_' . bin2hex(random_bytes(4)), $now - 20);
        $bundlepurchase = $this->purchase((int)$user->id, 'cmp_m83f_bundle_' . bin2hex(random_bytes(4)), $now - 10);

        $oldergrant = $this->grant(
            $olderpurchase,
            (int)$user->id,
            'COURSE.ACCESS.OLDER',
            'COURSE.ACCESS.OLDER',
            'course_access',
            'course:' . $course->id . ':full',
            $now - 20,
            ['courseid' => (int)$course->id, 'roleshortname' => 'student']
        );

        $bundlegrant = $this->grant(
            $bundlepurchase,
            (int)$user->id,
            'BUNDLE.TEST',
            'COURSE.ACCESS.BUNDLE.CHILD',
            'course_access',
            'course:' . $course->id . ':full',
            $now - 10,
            ['courseid' => (int)$course->id, 'roleshortname' => 'student']
        );

        $this->fulfillment_state($bundlegrant, [
            'courseid' => (int)$course->id,
            'userid' => (int)$user->id,
            'enrolment' => $enrolment,
        ], $now - 10);

        $result = CommercePurchaseRightsRevocationService::create($DB)->revoke(
            $bundlepurchase,
            (int)$user->id,
            'M8.3-F overlapping course rights',
            $now
        );

        self::assertSame(1, $result['revoked']);
        self::assertSame(1, $result['course']);
        self::assertSame(1, $result['preservedcourse']);

        self::assertSame('active', $DB->get_field('local_subs_commerce_grant', 'status', [
            'grantreference' => $oldergrant,
        ]));
        self::assertSame('revoked', $DB->get_field('local_subs_commerce_grant', 'status', [
            'grantreference' => $bundlegrant,
        ]));

        self::assertTrue($DB->record_exists('user_enrolments', [
            'enrolid' => (int)$enrolment['enrolid'],
            'userid' => (int)$user->id,
        ]));

        $second = CommercePurchaseRightsRevocationService::create($DB)->revoke(
            $bundlepurchase,
            (int)$user->id,
            'M8.3-F replay',
            $now + 1
        );
        self::assertSame([
            'revoked' => 0,
            'course' => 0,
            'digital' => 0,
            'promotionjoin' => 0,
            'preservedcourse' => 0,
        ], $second);
        self::assertTrue($DB->record_exists('user_enrolments', [
            'enrolid' => (int)$enrolment['enrolid'],
            'userid' => (int)$user->id,
        ]));
    }

    private function purchase(int $userid, string $reference, int $now): string {
        global $DB;

        $DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => bin2hex(random_bytes(16)),
            'reference' => $reference,
            'type' => 'native',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => $userid,
            'customeremail' => 'm83f-' . $userid . '@example.invalid',
            'status' => 'fulfilled',
            'currency' => 'EUR',
            'subtotalminor' => 5900,
            'discountminor' => 0,
            'totalminor' => 5900,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return $reference;
    }

    private function grant(
        string $purchase,
        int $userid,
        string $itemreference,
        string $productsku,
        string $type,
        string $resourcekey,
        int $now,
        array $configuration = []
    ): string {
        global $DB;

        $reference = 'ent-' . substr(hash('sha256', $purchase . '|' . $type . '|' . random_int(1, PHP_INT_MAX)), 0, 32);
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => $reference,
            'idempotencykey' => 'idem-' . substr(hash('sha256', $reference), 0, 40),
            'purchasereference' => $purchase,
            'itemreference' => $itemreference,
            'productsku' => $productsku,
            'type' => $type,
            'resourcekey' => $resourcekey,
            'quantity' => 1,
            'beneficiaryuserid' => $userid,
            'beneficiaryemail' => 'm83f-' . $userid . '@example.invalid',
            'validfrom' => $now,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => json_encode($configuration),
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return $reference;
    }

    private function fulfillment_state(string $grantreference, array $payload, int $now): void {
        global $DB;

        $grant = $DB->get_record('local_subs_commerce_grant', ['grantreference' => $grantreference], '*', MUST_EXIST);
        $DB->insert_record('local_subs_commerce_ful_state', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => (string)$grant->idempotencykey,
            'granttype' => (string)$grant->type,
            'handlerclass' => 'm83f-test',
            'status' => 'completed',
            'attempts' => 1,
            'lastexecutionreference' => 'm83f-test',
            'lastsource' => 'phpunit',
            'lastactoruserid' => null,
            'lastpayloadjson' => json_encode($payload),
            'lastmessage' => 'completed',
            'lasterrorclass' => null,
            'timecreated' => $now,
            'timestarted' => $now,
            'timecompleted' => $now,
            'timemodified' => $now,
        ]);
    }
}
