<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f7_paypal_operational_ui_test extends \advanced_testcase {

    public function test_payment_architecture_links_back_to_central_payment_configuration_hub(): void {
        global $CFG;

        $architecture = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/payment_architecture.php'
        );
        self::assertIsString($architecture);

        self::assertStringContainsString(
            '/local/subscriptions/admin/commerce/configuration/section.php',
            $architecture
        );
        self::assertStringContainsString(
            "['section' => 'payments']",
            $architecture
        );
        self::assertStringContainsString(
            'commerce_provider_ops_open_hub',
            $architecture
        );
    }


    public function test_operational_provider_page_supports_paypal_and_returns_to_central_payment_hub(): void {
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/provider.php'
        );
        self::assertIsString($provider);

        self::assertStringContainsString('Provider::PAYPAL', $provider);
        self::assertStringContainsString("['section' => 'payments']", $provider);
        self::assertStringContainsString('CommercePaymentProviderOperationalStatusService', $provider);
        self::assertStringContainsString('CommercePaymentProviderConnectionTestService', $provider);
    }


    public function test_webhook_success_wording_is_idempotence_aware(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/PayPalWebhookService.php'
        );
        self::assertIsString($service);

        self::assertStringContainsString('confirmed_idempotently', $service);
    }

}
