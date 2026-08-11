<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_support_j12h_test extends \advanced_testcase {
    public function test_support_confirmation_and_conditional_mail_fields_are_present(): void {
        $root = dirname(__DIR__, 3);
        $controller = file_get_contents($root . '/support_request.php');
        $service = file_get_contents(
            $root . '/classes/commerce/support/CommerceSupportRequestService.php'
        );

        self::assertStringContainsString('commerce-support-confirmation__summary', $controller);
        self::assertStringContainsString('support/gustave_support', $controller);
        self::assertStringContainsString('append_line', $service);
        self::assertStringContainsString('commerce_support_mail_message_heading', $service);
        self::assertStringContainsString('translated_status', $service);
    }

    public function test_hub_and_storefront_expose_the_polished_actions(): void {
        $root = dirname(__DIR__, 3);
        $hub = file_get_contents($root . '/templates/customer/hub.mustache');
        $catalog = file_get_contents($root . '/templates/storefront/catalog.mustache');

        self::assertStringNotContainsString('commerce_customer_hub_profile_help', $hub);
        self::assertStringContainsString('commerce-customer-hub__avatar-link', $hub);
        self::assertStringContainsString('commerce_customer_hub_support_help_short', $hub);
        self::assertStringContainsString('commerce-storefront__currency-form', $catalog);
        self::assertStringContainsString('commerce-cart-trigger', $catalog);
    }

    public function test_crm_direction_strings_exist_in_all_languages(): void {
        $root = dirname(__DIR__, 3);
        foreach (['fr', 'en', 'ru'] as $language) {
            $strings = file_get_contents($root . '/lang/' . $language . '/local_subscriptions.php');
            self::assertStringContainsString("crm_inbox_direction_incoming", $strings);
            self::assertStringContainsString("crm_inbox_direction_outgoing", $strings);
        }
    }
}
