<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\context\CommerceLegacyDigitalMailAccessResolver;
use local_subscriptions\digital\product_manager;

final class commerce_legacy_digital_access_mail_m12c1_hotfix_test extends advanced_testcase {
    public function test_paid_legacy_digital_purchase_reconstructs_desktop_and_mobile_downloads(): void {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $productid = (int)$DB->insert_record(product_manager::TABLE_PRODUCT, (object)[
            'name' => 'Legacy PDF',
            'slug' => 'legacy-pdf-m12c1',
            'description' => '',
            'enabled' => 1,
            'filename' => 'legacy.pdf',
            'mobile_filename' => 'legacy-mobile.pdf',
            'price_eur' => 4.90,
            'price_rub' => 490.00,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $legacyid = (int)$DB->insert_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
            'productid' => $productid,
            'userid' => null,
            'email' => 'rgrg01099@gmail.com',
            'firstname' => 'Кася',
            'lastname' => 'Иванова',
            'currency' => 'EUR',
            'price' => 4.90,
            'amount_minor' => 490,
            'payment_provider' => 'stripe',
            'status' => 'paid',
            'download_token' => 'm12c1-test-token',
            'emailsent' => 1,
            'receipt_sent' => 1,
            'creation_date' => time(),
            'last_update' => time(),
            'attempts' => 0,
            'locked_list_price' => 4.90,
            'locked_discount_percent' => 0,
            'locked_discount_amount' => 0,
            'locked_final_price' => 4.90,
            'locked_at' => time(),
            'buyer_lang' => 'ru',
        ]);

        $resolved = (new CommerceLegacyDigitalMailAccessResolver($DB))->resolve($legacyid, 'ru');

        $this->assertNotNull($resolved);
        $this->assertCount(2, $resolved['accesses']);
        $this->assertSame('desktop', $resolved['accesses'][0]['variant']);
        $this->assertSame('mobile', $resolved['accesses'][1]['variant']);
        $this->assertStringContainsString(
            '/local/subscriptions/download_pdf.php?token=m12c1-test-token',
            $resolved['accesses'][0]['url']
        );
        $this->assertStringContainsString(
            'version=mobile',
            $resolved['accesses'][1]['url']
        );
    }

    public function test_missing_token_does_not_invent_access(): void {
        global $DB;

        $this->resetAfterTest(true);

        $productid = (int)$DB->insert_record(product_manager::TABLE_PRODUCT, (object)[
            'name' => 'Legacy PDF without token',
            'slug' => 'legacy-pdf-no-token-m12c1',
            'description' => '',
            'enabled' => 1,
            'filename' => 'legacy.pdf',
            'mobile_filename' => '',
            'price_eur' => 4.90,
            'price_rub' => 490.00,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $legacyid = (int)$DB->insert_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
            'productid' => $productid,
            'email' => 'legacy@example.test',
            'firstname' => 'Legacy',
            'lastname' => 'Buyer',
            'currency' => 'EUR',
            'price' => 4.90,
            'amount_minor' => 490,
            'payment_provider' => 'stripe',
            'status' => 'paid',
            'download_token' => '',
            'emailsent' => 0,
            'receipt_sent' => 1,
            'creation_date' => time(),
            'last_update' => time(),
            'attempts' => 0,
            'locked_list_price' => 4.90,
            'locked_discount_percent' => 0,
            'locked_discount_amount' => 0,
            'locked_final_price' => 4.90,
            'locked_at' => time(),
            'buyer_lang' => 'ru',
        ]);

        $this->assertNull(
            (new CommerceLegacyDigitalMailAccessResolver($DB))->resolve($legacyid, 'ru')
        );
    }
}