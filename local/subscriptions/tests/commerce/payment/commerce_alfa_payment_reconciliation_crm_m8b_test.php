<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

/** CRM contract for safe Alfa payment reconciliation. */
final class local_subscriptions_commerce_alfa_payment_reconciliation_crm_m8b_test extends advanced_testcase {
    public function test_crm_reconciliation_route_uses_live_engine_and_post_confirmation_guards(): void {
        global $CFG;

        $path = $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/reconcile_alfa.php';
        $source = (string)file_get_contents($path);

        self::assertStringContainsString('AlfaPaymentReconciliationService::create($DB)', $source);
        self::assertStringContainsString('inspect_purchase_reference($summary->reference)', $source);
        self::assertStringContainsString('require_sesskey()', $source);
        self::assertStringContainsString('Capabilities::MANAGE_SUBSCRIPTIONS', $source);
        self::assertStringContainsString('reconcile_payment($inspection->paymentid)', $source);
        self::assertStringNotContainsString("UPDATE ", strtoupper($source));
    }

    public function test_purchase_crm_exposes_read_only_alfa_verification_entrypoints(): void {
        global $CFG;

        $view = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/view.php'
        );
        $index = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/index.php'
        );

        self::assertStringContainsString('reconcile_alfa.php', $view);
        self::assertStringContainsString('commerce_alfa_crm_verify', $view);
        self::assertStringContainsString('reconcile_alfa.php', $index);
        self::assertStringContainsString('commerce_alfa_crm_verify_short', $index);
    }

    public function test_admin_copy_does_not_offer_manual_paid_override(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = (string)file_get_contents(
                $CFG->dirroot . '/local/subscriptions/lang/' . $lang . '/local_subscriptions.php'
            );
            preg_match_all(
                '/^\$string\[[\'"]commerce_alfa_crm_[^\'"]+[\'"]\]\s*=\s*([\'"])(.*?)\1;/ms',
                $source,
                $matches
            );
            $copy = strtolower(implode("\n", $matches[2]));
            self::assertStringNotContainsString('force paid', $copy);
            self::assertStringNotContainsString('marquer payé', $copy);
            self::assertStringNotContainsString('считать оплачен', $copy);
        }
    }
}
