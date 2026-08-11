<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\resolution\CommerceLegacyStorefrontProductResolver;

/**
 * @covers \local_subscriptions\commerce\catalog\resolution\CommerceLegacyStorefrontProductResolver
 */
final class commerce_795i412_legacy_storefront_mapping_test extends advanced_testcase {
    public function test_subscription_mapping_is_recovered_and_persisted(): void {
        global $DB;

        $this->resetAfterTest();
        $now = time();
        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'SUB.PLAN.42',
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'Legacy mapped plan',
            'description' => '',
            'metadatajson' => json_encode(['legacyfamily' => 'subscription', 'legacyid' => 42]),
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $resolver = new CommerceLegacyStorefrontProductResolver($DB);
        $product = $resolver->resolve_subscription_plan(42, true);

        $this->assertNotNull($product);
        $this->assertSame($productid, (int)$product->id);
        $this->assertTrue($DB->record_exists('local_subs_commerce_prod_map', [
            'productid' => $productid,
            'legacytable' => 'subscription_plan',
            'legacyid' => 42,
        ]));
        $this->assertStringContainsString('storefront_product.php',
            $resolver->storefront_url('subscription_plan', 42)?->out(false) ?? '');
    }

    public function test_digital_mapping_is_recovered_from_metadata(): void {
        global $DB;

        $this->resetAfterTest();
        $now = time();
        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'DIGITAL.TEST.PDF',
            'type' => 'digital_download',
            'status' => 'active',
            'name' => 'Legacy digital product',
            'description' => '',
            'metadatajson' => json_encode(['legacyfamily' => 'digital', 'legacyid' => 17]),
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $resolver = new CommerceLegacyStorefrontProductResolver($DB);
        $product = $resolver->resolve_digital_product(17, true);

        $this->assertNotNull($product);
        $this->assertSame($productid, (int)$product->id);
        $this->assertTrue($DB->record_exists('local_subs_commerce_prod_map', [
            'productid' => $productid,
            'legacytable' => 'subscription_digital_product',
            'legacyid' => 17,
        ]));
    }
}
