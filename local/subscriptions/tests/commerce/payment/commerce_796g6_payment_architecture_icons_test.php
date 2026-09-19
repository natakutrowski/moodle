<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g6_payment_architecture_icons_test extends advanced_testcase {
    public function test_matrix_supports_svg_brand_icons_with_fontawesome_fallback(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/'
            . 'payment_architecture.php'
        );

        foreach ([
            "'card.svg'",
            "'applepay.svg'",
            "'googlepay.svg'",
            "'paypal.svg'",
            "'link.svg'",
            "'klarna.svg'",
            'is_file($path)',
            'commerce-payment-method-matrix-icon',
            'commerce-payment-method-matrix-icon-fallback',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $contents
            );
        }
    }

    public function test_matrix_surfaces_admin_authorization_state(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/'
            . 'payment_architecture.php'
        );
        $inspector = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentArchitectureInspector.php'
        );

        $this->assertStringContainsString(
            'commerce_payment_architecture_admin_allowed',
            $page
        );
        $this->assertStringContainsString(
            'commerce_payment_architecture_admin_disabled',
            $page
        );
        $this->assertStringContainsString(
            "'adminallowed'",
            $inspector
        );
    }
}
