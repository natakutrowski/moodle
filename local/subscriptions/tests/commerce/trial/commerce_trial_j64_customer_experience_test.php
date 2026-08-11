<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\trial\CommerceTrialCartPricingService;
use local_subscriptions\commerce\trial\CommerceTrialConversionBridge;

/** J6.4 structural and pricing regression tests. */
final class commerce_trial_j64_customer_experience_test extends \advanced_testcase {
    public function test_trial_discount_is_counted_once_in_cart_calculator(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/cart/service/' .
            'CommerceCartCalculator.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            '$discountminor = 0;',
            $source
        );
        $this->assertSame(
            1,
            substr_count(
                $source,
                '$discountminor += $adjustment->get_amount()->get_amount_minor();'
            )
        );
    }

    public function test_restriction_banners_have_no_legacy_checkout_url(): void {
        global $CFG;

        $core = file_get_contents(
            $CFG->dirroot .
            '/course/format/classes/output/local/content/section/availability.php'
        );
        $theme = file_get_contents(
            $CFG->dirroot .
            '/theme/edly/templates/core_courseformat/local/content/availability.mustache'
        );

        $this->assertIsString($core);
        $this->assertStringNotContainsString(
            '/local/subscriptions/subscribe.php',
            $core
        );
        $this->assertStringNotContainsString(
            '/local/subscriptions/checkout.php',
            $core
        );
        $this->assertStringContainsString(
            'CommerceCourseStorefrontTargetResolver',
            $core
        );

        $this->assertIsString($theme);
        $this->assertStringContainsString(
            '{{campusunlockurl}}',
            $theme
        );
    }

    public function test_trial_recommendations_have_a_dedicated_presentation(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/course/recommendation/' .
            'CommerceCourseRecommendationService.php'
        );
        $template = file_get_contents(
            $CFG->dirroot .
            '/local/campus/templates/mycourses/components/recommendations.mustache'
        );

        $this->assertIsString($service);
        $this->assertStringContainsString(
            'CommerceTrialCartPricingService',
            $service
        );
        $this->assertStringContainsString(
            '$istrialoffer',
            $service
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            '{{#trialoffer}}',
            $template
        );
        $this->assertStringContainsString(
            '{{trialpriceformatted}}',
            $template
        );
    }
}
