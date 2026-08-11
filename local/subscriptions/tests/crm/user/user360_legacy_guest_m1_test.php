<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\service\UserProfileService;

final class user360_legacy_guest_m1_test extends \advanced_testcase {
    public function test_legacy_digital_customer_without_moodle_account_gets_user360_profile(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $productid = $DB->insert_record('subscription_digital_product', (object)[
            'slug' => 'm1-guest-product',
            'name' => 'M1 Guest Product',
            'filename' => 'm1.pdf',
        ]);

        $email = 'legacy.guest@example.test';
        $purchaseid = $DB->insert_record('subscription_digital_payment_request', (object)[
            'productid' => $productid,
            'userid' => null,
            'email' => $email,
            'firstname' => 'Legacy',
            'lastname' => 'Guest',
            'currency' => 'EUR',
            'price' => 9.90,
            'amount_minor' => 990,
            'payment_provider' => 'legacy',
            'status' => 'paid',
            'creation_date' => 1700000000,
            'last_update' => 1700000100,
            'payment_date' => 1700000050,
        ]);

        $profile = UserProfileService::load_by_email($email);

        self::assertTrue($profile->iscommerceguest);
        self::assertSame(0, (int)$profile->user->id);
        self::assertSame($email, $profile->user->email);
        self::assertSame('Legacy', $profile->user->firstname);
        self::assertSame('Guest', $profile->user->lastname);
        self::assertSame('active_customer', $profile->stats->crmstatus);
        self::assertSame(1, (int)$profile->stats->successfulpurchasecount);
        self::assertEquals(9.90, (float)$profile->stats->spent_eur);
        self::assertCount(1, $profile->digitalpayments);
        self::assertSame($purchaseid, (int)$profile->digitalpayments[0]->id);
        self::assertNotEmpty($profile->actions);
        self::assertStringContainsString('resend_email.php', (string)$profile->actions[0]->url);
        self::assertStringContainsString('returnurl=', (string)$profile->actions[0]->url);
    }

    public function test_email_lookup_prefers_canonical_moodle_user_when_account_exists(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user([
            'email' => 'canonical@example.test',
            'firstname' => 'Canonical',
            'lastname' => 'User',
        ]);

        $productid = $DB->insert_record('subscription_digital_product', (object)[
            'slug' => 'm1-canonical-product',
            'name' => 'M1 Canonical Product',
            'filename' => 'm1-canonical.pdf',
        ]);

        $DB->insert_record('subscription_digital_payment_request', (object)[
            'productid' => $productid,
            'userid' => null,
            'email' => 'canonical@example.test',
            'firstname' => 'Old',
            'lastname' => 'Legacy',
            'currency' => 'EUR',
            'price' => 9.90,
            'amount_minor' => 990,
            'payment_provider' => 'legacy',
            'status' => 'paid',
            'creation_date' => 1700000000,
            'last_update' => 1700000100,
        ]);

        $profile = UserProfileService::load_by_email('CANONICAL@example.test');

        self::assertFalse($profile->iscommerceguest);
        self::assertSame((int)$user->id, (int)$profile->user->id);
        self::assertSame('Canonical', $profile->user->firstname);
        self::assertCount(1, $profile->digitalpayments);
    }

    public function test_user360_legacy_table_exposes_resend_access_action(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/output/UserProfileRenderer.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('digital_purchase_actions($payment)', $source);
        self::assertStringContainsString('digital_purchase_resend_email_admin_page()', $source);
        self::assertStringContainsString("['PAID', 'COMPLETED']", $source);

        $purchaseview = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/view.php'
        );
        self::assertIsString($purchaseview);
        self::assertStringContainsString("['email' => (string)\$summary->customer->email]", $purchaseview);
    }
}
