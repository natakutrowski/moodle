<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\purchase\communication\CommercePurchaseCurrentCustomerResolver;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

final class commerce_purchase_mail_identity_m12c_test extends advanced_testcase {
    public function test_corrected_legacy_digital_identity_overrides_historical_purchase_for_communication(): void {
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

        $purchase = $this->details(
            new CommercePurchaseCustomer(
                null,
                'rgrg01099@gmai.com',
                'Кася',
                'Иванова'
            ),
            'digital',
            $legacyid
        );

        $resolved = (new CommercePurchaseCurrentCustomerResolver($DB))->resolve($purchase);

        $this->assertNull($resolved->userid);
        $this->assertSame('rgrg01099@gmail.com', $resolved->email);
        $this->assertSame('Кася', $resolved->firstname);
        $this->assertSame('Иванова', $resolved->lastname);
        $this->assertSame('Кася Иванова', $resolved->display_name());

        // Historical purchase identity is deliberately immutable.
        $this->assertSame('rgrg01099@gmai.com', $purchase->summary->customer->email);
    }

    public function test_current_moodle_user_has_priority_over_legacy_and_historical_identity(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user([
            'email' => 'current@example.test',
            'firstname' => 'Current',
            'lastname' => 'Person',
        ]);

        $purchase = $this->details(
            new CommercePurchaseCustomer(
                (int)$user->id,
                'old@example.test',
                'Old',
                'Name'
            ),
            null,
            null
        );

        $resolved = (new CommercePurchaseCurrentCustomerResolver($DB))->resolve($purchase);

        $this->assertSame((int)$user->id, $resolved->userid);
        $this->assertSame('current@example.test', $resolved->email);
        $this->assertSame('Current Person', $resolved->display_name());
    }

    public function test_purchase_view_and_actions_use_current_identity_and_transactional_mail_engine(): void {
        $root = dirname(__DIR__, 3);

        $view = file_get_contents($root . '/admin/commerce/purchases/view.php');
        $receipt = file_get_contents($root . '/admin/commerce/purchases/resend_receipt.php');
        $access = file_get_contents($root . '/admin/commerce/purchases/resend_access.php');
        $service = file_get_contents(
            $root . '/classes/commerce/purchase/action/CommercePurchaseCommunicationActionService.php'
        );
        $context = file_get_contents(
            $root . '/classes/commerce/mail/context/CommercePurchaseMailContextFactory.php'
        );

        $this->assertIsString($view);
        $this->assertIsString($receipt);
        $this->assertIsString($access);
        $this->assertIsString($service);
        $this->assertIsString($context);

        $this->assertStringContainsString('CommercePurchaseCurrentCustomerResolver', $view);
        $this->assertStringContainsString("'email' => \$currentemail", $view);
        $this->assertStringContainsString('commerce_purchase_historical_email', $view);
        $this->assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/resend_access.php'",
            $view
        );

        $this->assertStringContainsString('require_sesskey()', $receipt);
        $this->assertStringContainsString('require_sesskey()', $access);
        $this->assertStringContainsString('resend_receipt(', $receipt);
        $this->assertStringContainsString('resend_access(', $access);

        $this->assertStringContainsString('CommerceMailType::PURCHASE_RECEIPT', $service);
        $this->assertStringContainsString('CommerceMailType::PURCHASE_ACCESS', $service);
        $this->assertStringContainsString('CommerceMailRuntime::queue_service()', $service);
        $this->assertStringContainsString('CommerceMailRuntime::processor()', $service);
        $this->assertStringContainsString('CommercePurchaseCurrentCustomerResolver', $context);
    }

    private function details(
        CommercePurchaseCustomer $customer,
        ?string $legacyfamily,
        ?int $legacyid
    ): CommercePurchaseDetails {
        return new CommercePurchaseDetails(
            new CommercePurchaseSummary(
                2897,
                'uuid-m12c',
                'cmp_m12c',
                'digital',
                $customer,
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
            []
        );
    }
}
