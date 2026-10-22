<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\purchase;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseGrantSummary;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

final class commerce_798c5131_backoffice_refund_clarity_test extends advanced_testcase {
    public function test_revoked_grants_override_historic_completed_fulfillment_for_access_status(): void {
        $dimensions = CommercePurchasePresentation::status_dimensions(
            3500,
            'fulfilled',
            'paid',
            'completed',
            true,
            'revoked'
        );

        self::assertSame(
            get_string(
                'commerce_purchase_access_status_revoked',
                'local_subscriptions'
            ),
            $dimensions[3]['value']
        );
    }

    public function test_access_resend_is_blocked_when_all_purchase_grants_are_revoked(): void {
        $summary = $this->summary();
        $policy = new CommercePurchaseActionPolicy();

        self::assertTrue($policy->can_resend_access_summary($summary));
        self::assertFalse(
            $policy->can_resend_access_details(
                $this->details($summary, 'revoked')
            )
        );
        self::assertTrue(
            $policy->can_resend_access_details(
                $this->details($summary, 'active')
            )
        );
    }

    public function test_purchase_view_exposes_successful_refund_net_and_details_guard(): void {
        $view = $this->plugin_file('admin/commerce/purchases/view.php');
        $endpoint = $this->plugin_file(
            'admin/commerce/purchases/resend_access.php'
        );

        self::assertStringContainsString(
            'successful_refunded_amount_minor(',
            $view
        );
        self::assertStringContainsString(
            "commerce_refund_net_retained_column",
            $view
        );
        self::assertStringContainsString(
            'can_resend_access_details($purchase)',
            $view
        );
        self::assertStringContainsString(
            'can_resend_access_details($purchase)',
            $endpoint
        );
        self::assertStringContainsString(
            '$accessstatusoverride',
            $view
        );
    }

    public function test_refund_sync_button_copy_is_compact_in_all_languages(): void {
        // PHPUnit dataroot does not necessarily install every non-English
        // language pack. get_string(..., $lang) may therefore legitimately
        // fall back to English even when the plugin ships its own translation.
        // This source-level assertion verifies the strings that will be
        // available in the real Moodle installation without depending on the
        // test dataroot language-pack state.
        self::assertSame(
            'Synchroniser',
            $this->plugin_lang_string('fr', 'commerce_refund_sync_action')
        );
        self::assertSame(
            'Sync',
            $this->plugin_lang_string('en', 'commerce_refund_sync_action')
        );
        self::assertSame(
            'Синхронизировать',
            $this->plugin_lang_string('ru', 'commerce_refund_sync_action')
        );
    }

    private function plugin_lang_string(string $language, string $identifier): string {
        $path = dirname(__DIR__, 3)
            . '/lang/' . $language . '/local_subscriptions.php';
        self::assertFileExists($path);

        $strings = (static function(string $path): array {
            $string = [];
            require $path;
            return $string;
        })($path);

        self::assertArrayHasKey($identifier, $strings);
        return (string)$strings[$identifier];
    }

    private function plugin_file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $source = file_get_contents($path);
        self::assertIsString($source);
        return $source;
    }

    private function summary(): CommercePurchaseSummary {
        return new CommercePurchaseSummary(
            579,
            'uuid-579',
            'cmp_dd1f24ef718bf0a97100c940',
            'bundle',
            new CommercePurchaseCustomer(
                96,
                'customer@example.test',
                'Test',
                'Customer'
            ),
            ['Les articles français', 'Soutenir Alf'],
            'EUR',
            3500,
            'fulfilled',
            'paid',
            'completed',
            'stripe',
            'native',
            time(),
            [
                ['catalogue_type' => 'course_access'],
                ['catalogue_type' => 'donation'],
            ]
        );
    }

    private function details(
        CommercePurchaseSummary $summary,
        string $grantstatus
    ): CommercePurchaseDetails {
        $grant = new CommercePurchaseGrantSummary(
            'ent-test-579',
            'COURSE_ACCESS.ARTICLES',
            'COURSE_ACCESS.ARTICLES',
            'course_access',
            'course:18:full',
            1,
            $grantstatus,
            96,
            'customer@example.test',
            time(),
            null
        );

        return new CommercePurchaseDetails(
            $summary,
            [],
            [],
            [],
            null,
            null,
            [],
            [$grant]
        );
    }
}
