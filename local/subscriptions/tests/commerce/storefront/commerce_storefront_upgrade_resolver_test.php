<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\upgrade\CommerceStorefrontUpgradeResolver;

/** Tests the canonical Legacy plan upgrade projection used by Storefront and recommendations. */
final class commerce_storefront_upgrade_resolver_test extends \advanced_testcase {
    public function test_difference_upgrade_is_resolved_for_mapped_target_product(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $now = time();

        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'A2 upgrade scope',
            'course_ids' => json_encode([42]),
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        $fromplanid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'A2 Grammar',
            'accessscopeid' => $scopeid,
            'duration_key' => '1year',
            'is_active' => 1,
            'creation_date' => $now,
            'last_update' => $now,
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_enabled' => 1,
        ]);
        $toplanid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'A2 Full',
            'accessscopeid' => $scopeid,
            'duration_key' => '1year',
            'is_active' => 1,
            'creation_date' => $now,
            'last_update' => $now,
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_enabled' => 1,
        ]);
        foreach ([[$fromplanid, 80.00], [$toplanid, 129.00]] as [$planid, $price]) {
            $DB->insert_record('subscription_plan_price', (object)[
                'planid' => $planid,
                'currency' => 'EUR',
                'price' => $price,
            ]);
        }
        $DB->insert_record('subscription_plan_upgrade', (object)[
            'fromplanid' => $fromplanid,
            'toplanid' => $toplanid,
            'pricingmode' => 'difference',
            'isactive' => 1,
            'timecreated' => $now,
            'lastupdate' => $now,
        ]);
        $DB->insert_record('user_subscription', (object)[
            'userid' => $user->id,
            'planid' => $fromplanid,
            'pricepaid' => 80.00,
            'currency' => 'EUR',
            'start_date' => $now - DAYSECS,
            'end_date' => $now + YEARSECS,
            'status' => 'active',
            'creation_date' => $now,
            'discount_percent' => 0,
            'discount_amount' => 0,
        ]);

        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'SUB.PLAN.' . $toplanid,
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'A2 Full',
            'description' => '',
            'metadatajson' => json_encode(['visibility' => 'visible', 'access' => ['scopeid' => $scopeid]]),
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_prod_map', (object)[
            'productid' => $productid,
            'legacyfamily' => 'subscription',
            'legacytable' => 'subscription_plan',
            'legacyid' => $toplanid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $upgrade = (new CommerceStorefrontUpgradeResolver($DB))->resolve(
            (int)$user->id,
            $productid,
            'EUR'
        );

        $this->assertNotNull($upgrade);
        $this->assertSame(4900, $upgrade->get_amount_minor());
        $this->assertSame('EUR', $upgrade->get_currency());
        $this->assertSame('A2 Grammar', $upgrade->get_from_label());
        $this->assertSame('A2 Full', $upgrade->get_to_label());
    }

    public function test_difference_upgrade_is_resolved_from_native_source_plan_ownership(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $now = time();

        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'A2 native upgrade scope',
            'course_ids' => json_encode([42]),
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        $fromplanid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'A2 Grammar', 'accessscopeid' => $scopeid, 'duration_key' => '1year',
            'is_active' => 1, 'creation_date' => $now, 'last_update' => $now,
            'is_recurring' => 0, 'is_trial' => 0, 'expiry_reminder_enabled' => 1,
        ]);
        $toplanid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'A2 Full', 'accessscopeid' => $scopeid, 'duration_key' => '1year',
            'is_active' => 1, 'creation_date' => $now, 'last_update' => $now,
            'is_recurring' => 0, 'is_trial' => 0, 'expiry_reminder_enabled' => 1,
        ]);
        foreach ([[$fromplanid, 100.00], [$toplanid, 170.00]] as [$planid, $price]) {
            $DB->insert_record('subscription_plan_price', (object)[
                'planid' => $planid, 'currency' => 'EUR', 'price' => $price,
            ]);
        }
        $DB->insert_record('subscription_plan_upgrade', (object)[
            'fromplanid' => $fromplanid, 'toplanid' => $toplanid,
            'pricingmode' => 'difference', 'isactive' => 1,
            'timecreated' => $now, 'lastupdate' => $now,
        ]);

        $targetproductid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'SUB.PLAN.' . $toplanid, 'type' => 'course_access', 'status' => 'active',
            'name' => 'A2 Full', 'description' => '',
            'metadatajson' => json_encode(['legacyfamily' => 'subscription', 'legacyid' => $toplanid]),
            'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_prod_map', (object)[
            'productid' => $targetproductid, 'legacyfamily' => 'subscription',
            'legacytable' => 'subscription_plan', 'legacyid' => $toplanid,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-native-grammar',
            'idempotencykey' => 'grant-native-grammar-key',
            'purchasereference' => 'cmp_native_grammar',
            'itemreference' => 'item-native-grammar',
            'productsku' => 'COURSE_ACCESS.A2_GRAMMAR',
            'type' => 'course_access',
            'resourcekey' => 'course:42:grammar',
            'quantity' => 1,
            'beneficiaryuserid' => $user->id,
            'beneficiaryemail' => $user->email,
            'validfrom' => $now - HOURSECS,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => json_encode([
                'courseid' => 42,
                'accesslevel' => 'grammar',
                'sourceplanid' => $fromplanid,
            ]),
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $upgrade = (new CommerceStorefrontUpgradeResolver($DB))->resolve(
            (int)$user->id,
            $targetproductid,
            'EUR'
        );

        $this->assertNotNull($upgrade);
        $this->assertSame(7000, $upgrade->get_amount_minor());
        $this->assertSame('A2 Grammar', $upgrade->get_from_label());
        $this->assertSame('A2 Full', $upgrade->get_to_label());
    }

}
