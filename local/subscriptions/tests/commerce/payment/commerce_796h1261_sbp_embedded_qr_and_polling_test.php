<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1261_sbp_embedded_qr_and_polling_test extends \advanced_testcase {

    public function test_sbp_action_exposes_signed_status_url(): void {
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/alfa/AlfaCommercePaymentProvider.php'
        );
        self::assertIsString($provider);

        self::assertStringContainsString("'status_url' =>", $provider);
        self::assertStringContainsString('build_sbp_status_url', $provider);
        self::assertStringContainsString('hash_hmac(', $provider);
        self::assertStringContainsString('/local/subscriptions/payment/alfa_sbp_status.php', $provider);
    }


    public function test_status_endpoint_reuses_authoritative_alfa_reconciliation(): void {
        global $CFG;

        $endpoint = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/payment/alfa_sbp_status.php'
        );
        self::assertIsString($endpoint);

        self::assertStringContainsString('AlfaPaymentReconciliationService::create', $endpoint);
        self::assertStringContainsString('->inspect_payment(', $endpoint);
        self::assertStringContainsString('->reconcile_payment(', $endpoint);
        self::assertStringContainsString('hash_equals(', $endpoint);
    }


    public function test_sbp_quick_button_is_owned_by_embedded_driver(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_sbp.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString('[data-quick-payment-action="sbp"]', $amd);
        self::assertStringContainsString('event.stopImmediatePropagation();', $amd);
        self::assertMatchesRegularExpression("/body\\.set\\(\\s*'ajax'\\s*,\\s*'1'\\s*\\)/s", $amd);
        self::assertStringContainsString("payload.type !== 'alfa_sbp'", $amd);
    }


    public function test_sbp_polls_and_uses_global_splash_when_paid(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_sbp.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString('const POLL_INTERVAL_MS = 3000;', $amd);
        self::assertStringContainsString('const checkStatus = async statusUrl => {', $amd);
        self::assertStringContainsString("status.state === 'paid'", $amd);
        self::assertStringContainsString("showPaymentSplash(\n            'processing'", $amd);
        self::assertStringContainsString('window.location.assign(', $amd);
    }

}
