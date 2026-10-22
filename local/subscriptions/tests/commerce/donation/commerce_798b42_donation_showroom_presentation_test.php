<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;

/** 7.98B.4.2 — Donation Showroom presentation harmonisation. */
final class commerce_798b42_donation_showroom_presentation_test extends \advanced_testcase {
    public function test_donation_js_uses_iso_currency_code_format_instead_of_symbol_format(): void {
        global $CFG;
        $this->resetAfterTest(true);

        $js = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');

        self::assertStringContainsString("style: 'decimal'", $js);
        self::assertStringContainsString('minimumFractionDigits: digits', $js);
        self::assertStringContainsString('maximumFractionDigits: digits', $js);
        self::assertStringContainsString('`${number}\\u00a0${code}`', $js);
    }

    public function test_generic_showroom_hides_legacy_packaging_and_donation_controls_share_one_layout(): void {
        global $CFG;
        $this->resetAfterTest(true);

        $page = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $offer = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');
        $css = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertStringContainsString('commerce-showroom--generic', $page);
        self::assertStringContainsString('commerce-showroom--expedition', $page);
        self::assertGreaterThanOrEqual(2, substr_count($offer, 'commerce-showroom-donation__controls'));
        self::assertStringContainsString(
            '.commerce-showroom--generic .commerce-showroom-offer__packaging',
            $css
        );
        self::assertStringContainsString('.commerce-showroom-donation__controls {', $css);
        self::assertStringContainsString('row-gap: .55rem;', $css);
    }

    public function test_custom_course_support_role_can_be_featured_like_historical_bundle(): void {
        $this->resetAfterTest(true);

        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_offers');
        $method->setAccessible(true);

        $data = [
            'offersheading' => 'Offres',
            'offersdescription' => '',
            'offers' => [
                ['role' => 'course', 'sku' => 'COURSE.ALF', 'haspromotion' => false, 'hascompareprice' => false],
                ['role' => 'course_support', 'sku' => 'COURSE.ALF', 'haspromotion' => false, 'hascompareprice' => false],
                ['role' => 'support', 'sku' => 'DONATION.ALF', 'haspromotion' => false, 'hascompareprice' => false],
            ],
            'comparisonoffers' => [],
        ];

        $result = $method->invoke($presenter, $data, [
            'featuredrole' => 'course_support',
            'course_supportfeaturedlabel' => 'Recommandé',
            'showbadges' => true,
            'showsavings' => true,
            'order' => 'course,course_support,support',
        ]);

        self::assertFalse((bool)($result['offers'][0]['isfeatured'] ?? false));
        self::assertTrue((bool)($result['offers'][1]['isfeatured'] ?? false));
        self::assertSame('Recommandé', $result['offers'][1]['featuredlabel']);
        self::assertFalse((bool)($result['offers'][2]['isfeatured'] ?? false));
    }
}
