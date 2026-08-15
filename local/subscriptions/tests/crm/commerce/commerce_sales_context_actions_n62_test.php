<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

final class commerce_sales_context_actions_n62_test extends advanced_testcase {
    public function test_sales_action_policy_exposes_safe_manual_actions(): void {
        $policy = new CommercePurchaseActionPolicy();

        $paidfulfilled = $this->summary(
            'paid',
            'fulfilled',
            'client@example.test'
        );
        self::assertTrue($policy->can_resend_receipt_summary($paidfulfilled));
        self::assertTrue($policy->can_resend_access_summary($paidfulfilled));
        self::assertTrue($policy->can_create_personal_offer_summary($paidfulfilled));

        $paidpending = $this->summary(
            'paid',
            'pending',
            'client@example.test'
        );
        self::assertTrue($policy->can_resend_receipt_summary($paidpending));
        self::assertFalse($policy->can_resend_access_summary($paidpending));

        $failed = $this->summary(
            'failed',
            'none',
            'client@example.test'
        );
        self::assertFalse($policy->can_resend_receipt_summary($failed));
        self::assertFalse($policy->can_resend_access_summary($failed));
        self::assertTrue($policy->can_create_personal_offer_summary($failed));

        $noemail = $this->summary('paid', 'fulfilled', '');
        self::assertFalse($policy->can_create_personal_offer_summary($noemail));
    }

    public function test_sales_list_contains_contextual_workstation_actions(): void {
        $root = dirname(__DIR__, 3);
        $sales = file_get_contents(
            $root . '/admin/commerce/purchases/index.php'
        );

        foreach ([
            'commerce_sales_action_open_user360',
            'commerce_sales_followup_action',
            'commerce_sales_action_resend_invoice',
            'commerce_sales_action_resend_access',
            'commerce_sales_action_create_offer',
            'crm-sales-row-menu-section',
        ] as $needle) {
            self::assertStringContainsString($needle, $sales);
        }

        self::assertStringContainsString(
            "'returnurl' => \$returnurl",
            $sales
        );
        self::assertStringContainsString(
            "'prefillsourcemode' => 'purchase'",
            $sales
        );
    }

    public function test_personal_offer_create_accepts_context_prefill(): void {
        $root = dirname(__DIR__, 3);
        $create = file_get_contents(
            $root . '/admin/commerce/personal-offers/create.php'
        );

        foreach ([
            "optional_param('prefillemail'",
            "'prefillsourcemode'",
            "'prefillsourcepurchase'",
            "'value' => \$prefillemail",
            "'value' => \$prefillsourcepurchase",
        ] as $needle) {
            self::assertStringContainsString($needle, $create);
        }
    }

    public function test_manual_resends_accept_safe_local_return_url(): void {
        $root = dirname(__DIR__, 3);

        foreach ([
            '/admin/commerce/purchases/resend_receipt.php',
            '/admin/commerce/purchases/resend_access.php',
        ] as $relative) {
            $source = file_get_contents($root . $relative);
            self::assertStringContainsString(
                "optional_param('returnurl', '', PARAM_LOCALURL)",
                $source
            );
        }
    }

    public function test_admin_language_does_not_expose_development_phase_names(): void {
        $root = dirname(__DIR__, 3);

        foreach (['fr', 'en', 'ru'] as $language) {
            $source = file_get_contents(
                $root . '/lang/' . $language . '/local_subscriptions.php'
            );

            self::assertDoesNotMatchRegularExpression(
                '/\bN(?:4|5|6)\.\d+(?:\.\d+)?\b/',
                $source,
                $language
            );
        }
    }

    private function summary(
        string $paymentstatus,
        string $fulfillmentstatus,
        string $email
    ): CommercePurchaseSummary {
        return new CommercePurchaseSummary(
            10,
            'uuid-10',
            'P-10',
            'course_access',
            new CommercePurchaseCustomer(
                $email !== '' ? 42 : null,
                $email,
                'Client',
                'Test'
            ),
            ['A1 Full'],
            'EUR',
            4500,
            'paid',
            $paymentstatus,
            $fulfillmentstatus,
            'stripe',
            'native',
            time(),
            [],
            'CFR-10'
        );
    }
}
