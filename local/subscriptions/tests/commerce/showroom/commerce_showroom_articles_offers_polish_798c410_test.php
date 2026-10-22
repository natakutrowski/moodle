<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;

final class commerce_showroom_articles_offers_polish_798c410_test extends \advanced_testcase {
    public function test_alf_support_marks_only_support_roles_for_feature_suppression(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_offers');
        $method->setAccessible(true);

        $data = $method->invoke($presenter, [
            'offersheading' => 'Offres',
            'offersdescription' => '',
            'offersbadgeicon' => 'fa-solid fa-heart',
            'offers' => [
                ['role' => 'course', 'sku' => 'COURSE', 'features' => [['text' => 'A']], 'hasfeatures' => true],
                ['role' => 'course_support', 'sku' => 'COURSE_SUPPORT', 'features' => [['text' => 'B']], 'hasfeatures' => true],
                ['role' => 'support', 'sku' => 'SUPPORT', 'features' => [['text' => 'C']], 'hasfeatures' => true],
            ],
            'comparisonoffers' => [],
        ], [
            'presentation' => 'alf_support',
            'order' => 'course,course_support,support',
        ]);

        self::assertTrue($data['offers'][0]['isalfSupportOffer']);
        self::assertFalse($data['offers'][0]['suppressfeatures']);
        self::assertTrue($data['offers'][1]['suppressfeatures']);
        self::assertTrue($data['offers'][2]['suppressfeatures']);
    }

    public function test_offer_template_and_css_keep_alf_purchase_copy_complete_and_compact(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/offer.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString('{{^isalfSupportOffer}}data-showroom-fit-lines="2"', $template);
        self::assertStringContainsString('{{#hasfeatures}}{{^suppressfeatures}}', $template);
        self::assertStringContainsString('.commerce-showroom-offers--alf-support .commerce-showroom-offer__description', $css);
        self::assertStringContainsString('-webkit-line-clamp: unset;', $css);
        self::assertStringNotContainsString('commerce-showroom-donation__custom', $template);
        self::assertStringNotContainsString('data-donation-amount-input', $template);

        // Course-only still uses the price as its bottom anchor.
        self::assertMatchesRegularExpression(
            '/\.commerce-showroom-offers--alf-support\s+\.commerce-showroom-offer--course\s+\.commerce-showroom-offer__pricing\s*\{[^}]*margin-top:\s*auto;/s',
            $css
        );

        // Contribution offers must not inherit the old margin-bottom:auto spacer.
        self::assertMatchesRegularExpression(
            '/\.commerce-showroom-offers--alf-support\s+\.commerce-showroom-offer--course_support\s+\.commerce-showroom-offer__description,[^{]*\{[^}]*margin-bottom:\s*\.625rem;/s',
            $css
        );
        self::assertMatchesRegularExpression(
            '/\.commerce-showroom-offers--alf-support\s+\.commerce-showroom-offer--course_support\s+\.commerce-showroom-donation,[^{]*\{[^}]*margin-top:\s*0;/s',
            $css
        );

        // Contribution cards use intrinsic height so no unused flex remainder is left below the CTA.
        self::assertMatchesRegularExpression(
            '/\.commerce-showroom-offers--alf-support\s+\.commerce-showroom-offer--course_support,[^{]*\{[^}]*align-self:\s*start;[^}]*height:\s*auto;/s',
            $css
        );

        // No promotion means no invisible placeholder row.
        self::assertStringContainsString(
            '.commerce-showroom-offers--alf-support .commerce-showroom-offer__pricing-promotion.is-empty',
            $css
        );
        self::assertStringContainsString('display: none;', $css);
    }
}
