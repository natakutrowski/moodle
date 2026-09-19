<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a576_authenticated_cart_reconciliation_test extends \advanced_testcase {
    public function test_reconciliation_replays_items_through_canonical_cart_service(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceAuthenticatedCartReconciliationService.php'
        );

        self::assertStringContainsString(
            '->clear_cart(',
            $source
        );
        self::assertStringContainsString(
            '->add_product(',
            $source
        );
        self::assertStringContainsString(
            '$item->get_metadata()',
            $source
        );
        self::assertStringNotContainsString(
            'CommerceStorefrontOwnershipResolver',
            $source
        );
    }

    public function test_runtime_cart_boundary_already_uses_effective_native_and_legacy_ownership(): void {
        global $CFG;

        $factory = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/cart/service/'
            . 'CommerceCartRuntimeFactory.php'
        );
        $resolver = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/ownership/'
            . 'CommerceStorefrontOwnershipResolver.php'
        );

        self::assertStringContainsString(
            'new MoodleCommerceCartOwnershipGateway(',
            $factory
        );
        self::assertStringContainsString(
            'new CommerceStorefrontOwnershipResolver($DB)',
            $factory
        );
        self::assertStringContainsString(
            'owns_native_grant',
            $resolver
        );
        self::assertStringContainsString(
            'owns_native_purchase',
            $resolver
        );
        self::assertStringContainsString(
            'owns_legacy_digital_product',
            $resolver
        );
        self::assertStringContainsString(
            'owns_legacy_plan',
            $resolver
        );
    }

    public function test_resume_reconciles_only_after_authenticated_guest_cart_transfer(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/guest_checkout_resume.php'
        );

        $transfer = strpos(
            $source,
            'CommerceGuestCartTransferService::create()->transfer('
        );
        $reconcile = strpos(
            $source,
            'CommerceAuthenticatedCartReconciliationService('
        );

        self::assertNotFalse($transfer);
        self::assertNotFalse($reconcile);
        self::assertLessThan(
            $reconcile,
            $transfer
        );
        self::assertStringContainsString(
            "'authenticated_cart_items_removed'",
            $source
        );
    }

    public function test_empty_reconciled_cart_never_returns_to_payment_checkout(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/guest_checkout_resume.php'
        );

        self::assertStringContainsString(
            "'commerce_guest_cart_all_already_owned'",
            $source
        );
        self::assertStringContainsString(
            "'commerce_guest_cart_owned_items_removed'",
            $source
        );
    }
}
