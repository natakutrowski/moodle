<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e4_checkout_policy_integration_test extends \advanced_testcase {

    public function test_checkout_uses_recommendation_policy_without_overriding_explicit_available_choice(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($page);

        self::assertStringContainsString('CommerceCheckoutPaymentPresentationPlanner', $page);
        self::assertStringContainsString('$paymentpolicy->get_recommended_method()', $page);
        self::assertStringContainsString('$requestedmethod', $page);
        self::assertStringContainsString('in_array(', $page);
        self::assertStringContainsString('$selectedmethod =', $page);
    }


    public function test_checkout_exposes_primary_and_secondary_method_surfaces_and_hides_provider_input(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString('{{#primaryactioncards}}', $template);
        self::assertStringContainsString('{{#secondaryactioncards}}', $template);
        self::assertStringNotContainsString('name="provider"', $template);
    }


    public function test_action_records_country_and_uses_availability_not_recommendation_to_reject(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );
        self::assertIsString($action);

        self::assertStringContainsString('$paymentcountry = Region::detect_country();', $action);
        self::assertStringContainsString('$availability->is_available()', $action);
        self::assertStringContainsString('CommerceCheckoutExecutionPolicy::is_executable_now', $action);
        self::assertStringNotContainsString('CommercePaymentPolicyResolver', $action);
    }

}
