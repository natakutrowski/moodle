<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\order\presentation\CommerceOrderAccessActionResolver;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseFulfillmentSummary;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseGrantSummary;

/** @covers \local_subscriptions\commerce\order\presentation\CommerceOrderAccessActionResolver */
final class commerce_795i3_access_action_resolver_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_course_action_uses_controlled_order_route(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $grant = $this->grant('course_access', 'course:' . $course->id . ':full', ['courseid' => (int)$course->id]);
        $access = (new CommerceOrderAccessActionResolver($DB, time()))->resolve(
            'cmp_test',
            $grant,
            $this->completed_fulfillment($grant->reference)
        );

        $this->assertTrue($access->available);
        $this->assertStringContainsString('/courses/' . $course->id, (string)$access->url);
        $this->assertStringNotContainsString('/course/view.php', (string)$access->url);
        $this->assertStringNotContainsString('/local/subscriptions/order_access.php', (string)$access->url);
        $this->assertSame((int)$course->id, $access->metadata['courseid']);
    }

    public function test_digital_action_never_exposes_download_token(): void {
        global $DB;
        $now = time();
        $grant = $this->grant('digital_download', 'digital-product:7');
        $DB->insert_record('local_subs_commerce_dig_access', (object)[
            'grantreference' => $grant->reference,
            'idempotencykey' => 'idem-digital-test',
            'purchasereference' => 'cmp_test',
            'productsku' => 'DIGITAL.TEST',
            'resourcekey' => 'digital-product:7',
            'beneficiaryuserid' => 2,
            'beneficiaryemail' => 'buyer@example.test',
            'downloadtoken' => 'secret-download-token',
            'maxdownloads' => 3,
            'downloadcount' => 1,
            'validfrom' => $now - 10,
            'validuntil' => $now + 1000,
            'status' => 'active',
            'lastdownloadat' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $access = (new CommerceOrderAccessActionResolver($DB, $now))->resolve(
            'cmp_test',
            $grant,
            $this->completed_fulfillment($grant->reference)
        );

        $this->assertTrue($access->available);
        $this->assertStringContainsString('order_access.php', (string)$access->url);
        $this->assertStringNotContainsString('secret-download-token', (string)$access->url);
        $this->assertSame(2, $access->metadata['remainingdownloads']);
    }

    public function test_download_limit_makes_access_unavailable(): void {
        global $DB;
        $now = time();
        $grant = $this->grant('digital_download', 'digital-product:8');
        $DB->insert_record('local_subs_commerce_dig_access', (object)[
            'grantreference' => $grant->reference,
            'idempotencykey' => 'idem-limit-test',
            'purchasereference' => 'cmp_test',
            'productsku' => 'DIGITAL.TEST',
            'resourcekey' => 'digital-product:8',
            'beneficiaryuserid' => 2,
            'beneficiaryemail' => 'buyer@example.test',
            'downloadtoken' => 'limit-token',
            'maxdownloads' => 2,
            'downloadcount' => 2,
            'validfrom' => $now - 10,
            'validuntil' => null,
            'status' => 'active',
            'lastdownloadat' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $access = (new CommerceOrderAccessActionResolver($DB, $now))->resolve(
            'cmp_test',
            $grant,
            $this->completed_fulfillment($grant->reference)
        );

        $this->assertFalse($access->available);
        $this->assertNull($access->url);
        $this->assertSame('download_limit_reached', $access->unavailablereason);
    }

    private function grant(string $type, string $resourcekey, array $configuration = []): CommercePurchaseGrantSummary {
        return new CommercePurchaseGrantSummary(
            'grant-' . substr(hash('sha256', $type . $resourcekey), 0, 16),
            'item-1',
            'PRODUCT.TEST',
            $type,
            $resourcekey,
            1,
            'active',
            2,
            'buyer@example.test',
            time() - 10,
            null,
            $configuration
        );
    }

    private function completed_fulfillment(string $reference): CommercePurchaseFulfillmentSummary {
        return new CommercePurchaseFulfillmentSummary(
            $reference,
            'test',
            'completed',
            'idem-' . $reference,
            'test',
            1,
            'exec-test',
            'phpunit',
            null,
            [],
            'done',
            null,
            time() - 2,
            time() - 1
        );
    }
}
