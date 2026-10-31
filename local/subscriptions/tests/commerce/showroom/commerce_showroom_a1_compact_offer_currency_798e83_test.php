<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Regression coverage for the A1-only compact tariff card and currency control. */
final class commerce_showroom_a1_compact_offer_currency_798e83_test extends \advanced_testcase {
    public function test_compact_mode_is_enabled_only_for_a_single_a1_offer(): void {
        global $CFG;
        $presenter = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomPresenter.php'
        );
        self::assertIsString($presenter);
        self::assertStringContainsString("'a1compactoffercurrency' =>", $presenter);
        self::assertStringContainsString("$" . "definition->get_key() === 'a1-course' && count($" . "offers) === 1", $presenter);
    }

    public function test_currency_control_is_near_capacity_only_in_a1_card(): void {
        global $CFG;
        $heading = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $offer = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');
        self::assertIsString($heading);
        self::assertIsString($offer);

        // Generic Showrooms still have their header control.
        self::assertStringContainsString('{{^a1compactoffercurrency}}', $heading);
        self::assertStringContainsString('data-showroom-currency-ajax', $heading);
        // A1 displays the only currency selector alongside the remaining seat count.
        self::assertStringContainsString('commerce-showroom-offer__enrolment-row', $offer);
        self::assertStringContainsString('commerce-showroom-currency-card--inline', $offer);
        self::assertStringContainsString('data-showroom-currency-toolbar', $offer);
        self::assertStringContainsString('data-showroom-currency-ajax', $offer);
        self::assertStringContainsString('data-showroom-currency-status', $offer);
        self::assertStringContainsString('data-showroom-promotion-join-currency', $offer);
        self::assertStringContainsString('data-showroom-currency-input', $offer);
    }

    public function test_compact_geometry_is_scoped_to_a1(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        $start = strpos($css, 'CampusFR A1 compact offer 2026-10-31');
        self::assertNotFalse($start);
        $addition = substr($css, $start);
        self::assertStringContainsString('.commerce-showroom[data-showroom="a1-course"]', $addition);
        self::assertStringContainsString('commerce-showroom-offer__visual', $addition);
        self::assertStringContainsString('commerce-showroom-currency-card--inline', $addition);
        self::assertStringContainsString('position: static !important', $addition);
        self::assertStringContainsString('pricing-promotion.is-empty', $addition);
    }
}
