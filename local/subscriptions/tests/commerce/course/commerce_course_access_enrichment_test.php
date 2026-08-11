<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\course\library\CommerceCourseAccessEnrichmentService;
use local_subscriptions\commerce\course\library\CommerceCourseAccessOrigin;

final class commerce_course_access_enrichment_test extends \advanced_testcase {
    public function test_native_lifetime_purchase_is_preferred_and_exposes_public_order(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user(['email' => 'course@example.test']);
        $now = time();
        $purchase = $this->insert_purchase('cmp-native-course', (int)$user->id, $user->email, $now - 100);
        $this->insert_grant('grant-trial', 'cmp-native-course', 17, (int)$user->id, $user->email, 'trial', $now - 90, $now + 3600, 'active');
        $this->insert_grant('grant-full', 'cmp-native-course', 17, (int)$user->id, $user->email, 'full', $now - 80, null, 'active');

        $collection = (new CommerceCourseAccessEnrichmentService($DB))->get_for_customer(
            (int)$user->id,
            $user->email,
            [17, 18],
            $now
        );

        $course = $collection->get(17);
        $this->assertSame(CommerceCourseAccessOrigin::PURCHASE, $course->origin);
        $this->assertTrue($course->period->lifetime);
        $this->assertSame('native', $course->source);
        $this->assertStringContainsString('order_details.php', (string)$course->purchaseurl);
        $this->assertMatchesRegularExpression('/^CFR-\d{4}-[A-F0-9]{6}$/', (string)$course->commercialreference);
        $this->assertCount(2, $course->sources);
        $this->assertSame(CommerceCourseAccessOrigin::UNKNOWN, $collection->get(18)->origin);
        $this->assertSame(2, count($collection));
    }

    public function test_legacy_plan_enriches_requested_course_without_deciding_enrolment(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $now = time();
        $scopeid = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Test scope', 'course_ids' => '21,22', 'creation_date' => $now, 'last_update' => $now,
        ]);
        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Legacy plan', 'accessscopeid' => $scopeid, 'duration_key' => '1year', 'is_active' => 1,
            'creation_date' => $now, 'last_update' => $now, 'is_recurring' => 0, 'is_trial' => 0,
            'expiry_reminder_enabled' => 1,
        ]);
        $DB->insert_record('user_subscription', (object)[
            'userid' => $user->id, 'planid' => $planid, 'start_date' => $now - 100,
            'end_date' => $now + 86400, 'status' => 'active', 'creation_date' => $now - 100,
            'payment_failed' => 0, 'discount_percent' => 0, 'discount_amount' => 0,
        ]);

        $collection = (new CommerceCourseAccessEnrichmentService($DB))->get_for_customer(
            (int)$user->id,
            (string)$user->email,
            [21, 99],
            $now
        );

        $this->assertSame(CommerceCourseAccessOrigin::PURCHASE, $collection->get(21)->origin);
        $this->assertSame('legacy', $collection->get(21)->source);
        $this->assertFalse($collection->get(21)->period->lifetime);
        $this->assertSame(CommerceCourseAccessOrigin::UNKNOWN, $collection->get(99)->origin);
        $this->assertArrayNotHasKey(22, $collection->all());
    }

    public function test_trial_and_gift_origins_are_resolved_from_reliable_native_metadata(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $now = time();
        $this->insert_purchase('cmp-origin-test', (int)$user->id, $user->email, $now - 200);
        $this->insert_grant('grant-trial-only', 'cmp-origin-test', 31, (int)$user->id, $user->email, 'trial', $now - 100, $now + 1000, 'active');
        $this->insert_grant('grant-gift', 'cmp-origin-test', 32, (int)$user->id, $user->email, 'full', $now - 100, null, 'active', ['origin' => 'gift']);

        $collection = (new CommerceCourseAccessEnrichmentService($DB))->get_for_customer(
            (int)$user->id,
            (string)$user->email,
            [31, 32],
            $now
        );

        $this->assertSame(CommerceCourseAccessOrigin::TRIAL, $collection->get(31)->origin);
        $this->assertSame(CommerceCourseAccessOrigin::GIFT, $collection->get(32)->origin);
    }

    private function insert_purchase(string $reference, int $userid, string $email, int $timecreated): int {
        global $DB;
        return (int)$DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => substr(hash('sha256', $reference), 0, 32),
            'reference' => $reference,
            'type' => 'course_access',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => $userid,
            'customeremail' => $email,
            'status' => 'fulfilled',
            'currency' => 'EUR',
            'subtotalminor' => 1000,
            'discountminor' => 0,
            'totalminor' => 1000,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $timecreated,
            'timemodified' => $timecreated,
        ]);
    }

    private function insert_grant(
        string $reference,
        string $purchasereference,
        int $courseid,
        int $userid,
        string $email,
        string $accesslevel,
        int $validfrom,
        ?int $validuntil,
        string $status,
        array $configuration = []
    ): int {
        global $DB;
        $configuration += ['courseid' => $courseid, 'accesslevel' => $accesslevel];
        return (int)$DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => $reference,
            'idempotencykey' => 'key-' . $reference,
            'purchasereference' => $purchasereference,
            'itemreference' => 'item-' . $reference,
            'productsku' => 'COURSE.' . $courseid,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $courseid . ':' . $accesslevel,
            'quantity' => 1,
            'beneficiaryuserid' => $userid,
            'beneficiaryemail' => $email,
            'validfrom' => $validfrom,
            'validuntil' => $validuntil,
            'status' => $status,
            'configurationjson' => json_encode($configuration, JSON_THROW_ON_ERROR),
            'metadatajson' => '{}',
            'timecreated' => $validfrom,
            'timemodified' => $validfrom,
        ]);
    }
}
