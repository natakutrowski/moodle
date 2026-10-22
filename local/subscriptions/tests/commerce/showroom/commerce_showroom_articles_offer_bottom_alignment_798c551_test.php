<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * Articles / Alf — contribution + price + CTA remain a compact bottom purchase zone.
 *
 * @coversNothing
 */
final class commerce_showroom_articles_offer_bottom_alignment_798c551_test extends \advanced_testcase {
    public function test_articles_alf_purchase_zone_is_bottom_anchored_without_empty_promo_space(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');

        self::assertIsString($css);
        self::assertIsString($template);

        $marker = '7.98C.4.19.6 — canonical Articles / Alf purchase-zone geometry.';
        $start = strpos($css, $marker);
        self::assertNotFalse($start);
        $section = substr($css, $start);

        // Course-only cards anchor the purchase zone from the price.
        self::assertStringContainsString(
            '.commerce-showroom-offers--alf-support .commerce-showroom-offer--course .commerce-showroom-offer__pricing',
            $section
        );
        self::assertStringContainsString('margin-top: auto;', $section);

        // Contribution cards keep the selector directly after editorial copy; no auto blank margin above it.
        self::assertStringContainsString(
            '.commerce-showroom-offers--alf-support .commerce-showroom-offer--course_support .commerce-showroom-donation',
            $section
        );
        self::assertStringContainsString(
            '.commerce-showroom-offers--alf-support .commerce-showroom-offer--support .commerce-showroom-donation',
            $section
        );
        self::assertStringContainsString('margin-top: 0;', $section);
        self::assertStringContainsString('margin-bottom: 0;', $section);

        // Price sits immediately below the contribution selector: no historical top padding/auto gap.
        self::assertStringContainsString(
            '.commerce-showroom-offers--alf-support .commerce-showroom-offer--course_support .commerce-showroom-offer__pricing',
            $section
        );
        self::assertStringContainsString('margin-top: .45rem;', $section);
        self::assertStringContainsString('padding-top: 0;', $section);

        // No invisible promotion placeholder when no promotion is active.
        self::assertStringContainsString(
            '.commerce-showroom-offers--alf-support .commerce-showroom-offer__pricing-promotion.is-empty',
            $section
        );
        self::assertStringContainsString('display: none;', $section);
        self::assertStringContainsString('min-height: 0;', $section);

        // CTA remains a fixed part of the bottom purchase zone.
        self::assertStringContainsString(
            '.commerce-showroom-offers--alf-support .commerce-showroom-offer__actions',
            $section
        );
        self::assertStringContainsString('flex: 0 0 auto;', $section);

        $donation = strpos($template, '<div class="commerce-showroom-donation');
        $pricing = strpos($template, '<div class="commerce-showroom-offer__pricing">');
        $actions = strpos($template, '<div class="commerce-showroom-offer__actions">');

        self::assertNotFalse($donation);
        self::assertNotFalse($pricing);
        self::assertNotFalse($actions);
        self::assertGreaterThan($donation, $pricing);
        self::assertGreaterThan($pricing, $actions);
    }
}
