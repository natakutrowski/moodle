<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\purchase;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\order\presentation\CommerceCustomerRefundPresentation;

final class commerce_798c63e1_customer_refund_revocation_clarity_test extends advanced_testcase {
    public function test_refund_summary_distinguishes_none_partial_and_full(): void {
        $none = CommerceCustomerRefundPresentation::from_amounts(5000, 0);
        self::assertFalse($none['hasrefund']);
        self::assertFalse($none['ispartial']);
        self::assertFalse($none['isfull']);
        self::assertSame('', $none['status']);

        $partial = CommerceCustomerRefundPresentation::from_amounts(5000, 1500);
        self::assertTrue($partial['hasrefund']);
        self::assertTrue($partial['ispartial']);
        self::assertFalse($partial['isfull']);
        self::assertSame('partially_refunded', $partial['status']);
        self::assertSame(1500, $partial['refundedminor']);

        $full = CommerceCustomerRefundPresentation::from_amounts(5000, 5000);
        self::assertTrue($full['hasrefund']);
        self::assertFalse($full['ispartial']);
        self::assertTrue($full['isfull']);
        self::assertSame('refunded', $full['status']);
        self::assertSame(5000, $full['refundedminor']);

        $over = CommerceCustomerRefundPresentation::from_amounts(5000, 9000);
        self::assertSame(5000, $over['refundedminor']);
        self::assertTrue($over['isfull']);
    }

    public function test_order_details_exposes_refund_and_revoked_access_without_rewriting_paid_history(): void {
        $page = $this->plugin_file('order_details.php');
        $template = $this->plugin_file('templates/order_details/page.mustache');
        $css = $this->plugin_file('styles/order_details.css');

        self::assertStringContainsString('CommerceCustomerRefundPresentation::resolve(', $page);
        self::assertStringContainsString("['revoked', 'refunded', 'cancelled', 'canceled']", $page);
        self::assertStringContainsString("'accessstateclass' => \$revokedaccess ? 'revoked'", $page);
        self::assertStringContainsString("'accessstateicon' => \$revokedaccess ? 'fa-ban'", $page);

        self::assertStringContainsString('{{#hasrefund}}', $template);
        self::assertStringContainsString('commerce_customer_refund_label', $template);
        self::assertStringContainsString('commerce_customer_refund_amount', $template);
        self::assertStringContainsString('commerce-order-payment-badge--refund', $template);
        self::assertStringContainsString('fa-solid {{accessstateicon}}', $template);

        self::assertStringContainsString('.commerce-order-access--revoked', $css);
        self::assertStringContainsString('.commerce-order-status--refund', $css);
    }

    public function test_my_purchases_keeps_paid_badge_and_adds_refund_badge(): void {
        $renderer = $this->plugin_file('classes/output/my_purchases/CurrentPresentationRenderer.php');
        $css = $this->plugin_file('styles/my_purchases.css');

        self::assertStringContainsString('CommerceCustomerRefundPresentation::resolve(', $renderer);
        self::assertStringContainsString('commerce_i411_paid_badge', $renderer);
        self::assertStringContainsString('my-purchase-card__status--refunded', $renderer);
        self::assertStringContainsString('commerce_vocabulary_payment_status_client_refunded', $renderer);
        self::assertStringContainsString('commerce_vocabulary_payment_status_client_partially_refunded', $renderer);
        self::assertStringContainsString('.my-purchase-card__status-stack', $css);
        self::assertStringContainsString('.my-purchase-card__status--refunded', $css);
    }

    public function test_customer_refund_and_revoked_access_copy_exists_in_all_languages(): void {
        $expected = [
            'fr' => [
                'commerce_customer_refund_label' => 'Remboursement',
                'commerce_customer_refund_amount' => 'Montant remboursé',
                'commerce_customer_access_revoked' => 'Accès révoqué',
            ],
            'en' => [
                'commerce_customer_refund_label' => 'Refund',
                'commerce_customer_refund_amount' => 'Refunded amount',
                'commerce_customer_access_revoked' => 'Access revoked',
            ],
            'ru' => [
                'commerce_customer_refund_label' => 'Возврат',
                'commerce_customer_refund_amount' => 'Сумма возврата',
                'commerce_customer_access_revoked' => 'Доступ отозван',
            ],
        ];

        foreach ($expected as $language => $strings) {
            foreach ($strings as $identifier => $value) {
                self::assertSame($value, $this->plugin_lang_string($language, $identifier));
            }
        }
    }

    private function plugin_file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $source = file_get_contents($path);
        self::assertIsString($source);
        return $source;
    }

    private function plugin_lang_string(string $language, string $identifier): string {
        $path = dirname(__DIR__, 3) . '/lang/' . $language . '/local_subscriptions.php';
        self::assertFileExists($path);

        $strings = (static function(string $path): array {
            $string = [];
            require $path;
            return $string;
        })($path);

        self::assertArrayHasKey($identifier, $strings);
        return (string)$strings[$identifier];
    }
}
