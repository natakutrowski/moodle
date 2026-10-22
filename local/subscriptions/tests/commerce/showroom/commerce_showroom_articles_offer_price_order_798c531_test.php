<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * 7.98C.5.3.1 — donation controls stay compact and final price sits above CTA.
 *
 * @coversNothing
 */
final class commerce_showroom_articles_offer_price_order_798c531_test extends \advanced_testcase {
    public function test_showroom_offer_uses_stepper_and_presets_without_custom_input_and_prices_after_donation(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');
        self::assertIsString($template);

        self::assertStringContainsString('data-donation-step="-1"', $template);
        self::assertStringContainsString('data-donation-preset="{{units}}"', $template);
        self::assertStringNotContainsString('commerce-showroom-donation__custom', $template);
        self::assertStringNotContainsString('data-donation-amount-input', $template);

        $companionEnd = strpos($template, '{{/hasdonationcompanion}}');
        $pricing = strpos($template, '<div class="commerce-showroom-offer__pricing">');
        $actions = strpos($template, '<div class="commerce-showroom-offer__actions">');

        self::assertNotFalse($companionEnd);
        self::assertNotFalse($pricing);
        self::assertNotFalse($actions);
        self::assertGreaterThan($companionEnd, $pricing);
        self::assertGreaterThan($pricing, $actions);
    }

    public function test_storefront_custom_donation_input_is_unchanged(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/donation_amount_selector.mustache'
        );
        self::assertIsString($template);
        self::assertStringContainsString('data-donation-amount-input', $template);
    }
}
