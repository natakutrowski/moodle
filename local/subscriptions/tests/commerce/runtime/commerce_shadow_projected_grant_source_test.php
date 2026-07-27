<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceProjectedPurchaseShadowGrantSource;

final class commerce_shadow_projected_grant_source_test extends \advanced_testcase {
    public function test_subscription_projection_builds_transient_course_grants_without_ledger_writes(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Shadow plan',
            'accessscopeid' => null,
            'duration_key' => 'lifetime',
            'is_active' => 1,
            'creation_date' => time(),
            'last_update' => time(),
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_enabled' => 0,
        ]);
        $DB->insert_record('subscription_plan_entitlement', (object)[
            'planid' => $planid,
            'courseid' => $course->id,
            'accesslevel' => 'full',
            'roleshortname' => 'student',
            'groupname' => null,
            'priority' => 100,
            'timecreated' => time(),
            'lastupdate' => time(),
        ]);

        $purchaseid = $this->insert_purchase('cmp-shadow-sub', 'subscription');
        $this->insert_item($purchaseid, 'subscription', 'subscription-plan:' . $planid, [
            'plan_id' => $planid,
        ]);

        $grants = (new CommerceProjectedPurchaseShadowGrantSource())->find_for_purchase('cmp-shadow-sub');

        $this->assertCount(1, $grants);
        $this->assertSame('course_access', $grants[0]->get_type());
        $this->assertSame('course:' . $course->id . ':full', $grants[0]->get_resource_key());
        $this->assertSame(0, $DB->count_records('local_subs_commerce_grant'));
    }

    public function test_digital_projection_builds_transient_download_grant_without_ledger_writes(): void {
        global $DB;
        $this->resetAfterTest();

        $purchaseid = $this->insert_purchase('cmp-shadow-digital', 'digital');
        $this->insert_item($purchaseid, 'digital', 'digital-product:verbes', [
            'product_id' => 2,
            'slug' => 'verbes',
            'filename' => 'verbes.pdf',
        ]);

        $grants = (new CommerceProjectedPurchaseShadowGrantSource())->find_for_purchase('cmp-shadow-digital');

        $this->assertCount(1, $grants);
        $this->assertSame('digital_download', $grants[0]->get_type());
        $this->assertSame('digital-product:verbes', $grants[0]->get_resource_key());
        $this->assertSame(0, $DB->count_records('local_subs_commerce_grant'));
    }

    private function insert_purchase(string $reference, string $type): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        return (int)$DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => md5($reference),
            'reference' => $reference,
            'type' => $type,
            'legacyfamily' => $type,
            'legacyid' => random_int(1000, 9999),
            'userid' => $user->id,
            'customeremail' => $user->email,
            'status' => 'fulfilled',
            'currency' => 'EUR',
            'subtotalminor' => 100,
            'discountminor' => 0,
            'totalminor' => 100,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    private function insert_item(int $purchaseid, string $type, string $reference, array $fulfillment): void {
        global $DB;
        $DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $purchaseid,
            'position' => 0,
            'itemtype' => $type,
            'itemreference' => $reference,
            'label' => 'Shadow item',
            'quantity' => 1,
            'currency' => 'EUR',
            'unitminor' => 100,
            'grossminor' => 100,
            'discountminor' => 0,
            'netminor' => 100,
            'pricingjson' => '{}',
            'fulfillmentjson' => json_encode($fulfillment, JSON_THROW_ON_ERROR),
            'metadatajson' => '{}',
        ]);
    }
}
