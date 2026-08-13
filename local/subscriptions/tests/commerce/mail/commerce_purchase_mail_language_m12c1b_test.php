<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\context\CommercePurchaseMailLanguageResolver;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

final class commerce_purchase_mail_language_m12c1b_test extends advanced_testcase {
    public function test_legacy_digital_buyer_language_is_used_without_moodle_account(): void {
        global $DB;

        $this->resetAfterTest(true);

        $legacyid = (int)$DB->insert_record('subscription_digital_payment_request', (object)[
            'productid' => 1,
            'userid' => null,
            'email' => 'rgrg01099@gmail.com',
            'firstname' => 'Кася',
            'lastname' => 'Иванова',
            'currency' => 'EUR',
            'price' => 4.90,
            'amount_minor' => 490,
            'payment_provider' => 'stripe',
            'status' => 'paid',
            'download_token' => 'm12c1b-token',
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

        $details = $this->details(null, 'digital', $legacyid, []);

        $this->assertSame(
            'ru',
            (new CommercePurchaseMailLanguageResolver($DB))->resolve(null, $details)
        );
    }

    public function test_moodle_user_language_has_priority_over_legacy_buyer_language(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user([
            'email' => 'current-language@example.test',
            'firstname' => 'Current',
            'lastname' => 'Person',
            'lang' => 'en',
        ]);

        $legacyid = (int)$DB->insert_record('subscription_digital_payment_request', (object)[
            'productid' => 1,
            'userid' => null,
            'email' => 'legacy@example.test',
            'firstname' => 'Legacy',
            'lastname' => 'Person',
            'currency' => 'EUR',
            'price' => 4.90,
            'amount_minor' => 490,
            'payment_provider' => 'stripe',
            'status' => 'paid',
            'download_token' => 'm12c1b-user-token',
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

        $details = $this->details((int)$user->id, 'digital', $legacyid, ['language' => 'fr']);

        $this->assertSame(
            'en',
            (new CommercePurchaseMailLanguageResolver($DB))->resolve((int)$user->id, $details)
        );
    }

    public function test_native_purchase_uses_persisted_metadata_language(): void {
        global $DB;

        $this->resetAfterTest(true);

        $details = $this->details(null, null, null, ['buyer_language' => 'ru']);

        $this->assertSame(
            'ru',
            (new CommercePurchaseMailLanguageResolver($DB))->resolve(null, $details)
        );
    }

    private function details(
        ?int $userid,
        ?string $legacyfamily,
        ?int $legacyid,
        array $metadata
    ): CommercePurchaseDetails {
        return new CommercePurchaseDetails(
            new CommercePurchaseSummary(
                2897,
                'uuid-m12c1b',
                'cmp_m12c1b',
                'digital',
                new CommercePurchaseCustomer(
                    $userid,
                    'rgrg01099@gmail.com',
                    'Кася',
                    'Иванова'
                ),
                ['Legacy PDF'],
                'EUR',
                490,
                'fulfilled',
                'paid',
                'fulfilled',
                'stripe',
                $legacyfamily === null ? 'native' : 'legacy',
                time()
            ),
            [],
            [],
            [],
            $legacyfamily,
            $legacyid,
            $metadata
        );
    }
}
