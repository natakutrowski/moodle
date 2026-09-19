<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h13323_direct_snapshot_context_wiring_fix_test extends \advanced_testcase {
    public function test_checkout_preview_context_receives_direct_purchase_payload(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        $context = strpos(
            $source,
            '$context = new CommerceCheckoutContext('
        );
        self::assertNotFalse($context);

        $block = substr($source, $context, 3200);

        self::assertStringContainsString(
            "'purchase_flow' => \$flow",
            $block
        );
        self::assertStringContainsString(
            "'direct_purchase' => \$directpurchase",
            $block
        );
    }

    public function test_checkout_launch_context_receives_direct_purchase_payload(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        $context = strpos(
            $source,
            '$context = new CommerceCheckoutContext('
        );
        self::assertNotFalse($context);

        $block = substr($source, $context, 3200);

        self::assertStringContainsString(
            "'purchase_flow' => \$flow",
            $block
        );
        self::assertStringContainsString(
            "'direct_purchase' => \$directpurchase",
            $block
        );
    }

    public function test_runtime_direct_path_uses_direct_snapshot_when_payload_present(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutRuntime.php'
        );

        self::assertStringContainsString(
            "\$metadata['direct_purchase']",
            $source
        );
        self::assertStringContainsString(
            '$this->cart->direct_snapshot(',
            $source
        );
    }
}
