<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationCollection;
use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationPresentation;

/** Tests the public-safe course recommendation contract. */
final class commerce_course_recommendation_read_model_test extends \advanced_testcase {
    public function test_recommendation_exports_upgrade_price_and_product_link(): void {
        $item = new CommerceCourseRecommendationPresentation(
            'COURSE.A2',
            'course_access',
            'Cours A2',
            'Passez au niveau suivant.',
            'https://example.test/cover.jpg',
            'https://example.test/storefront?sku=COURSE.A2',
            '129,00 EUR',
            '149,00 EUR',
            13,
            true,
            '49,00 EUR',
            'A2 Grammar',
            'A2 Full'
        );
        $collection = new CommerceCourseRecommendationCollection([$item]);
        $export = $collection->all()[0]->to_array();

        $this->assertCount(1, $collection);
        $this->assertTrue($export['upgrade']);
        $this->assertTrue($export['hasprice']);
        $this->assertTrue($export['hascompareprice']);
        $this->assertSame(13, $export['discountpercentage']);
        $this->assertSame('https://example.test/storefront?sku=COURSE.A2', $export['producturl']);
        $this->assertTrue($export['hasupgradeprice']);
        $this->assertSame('49,00 EUR', $export['upgradepriceformatted']);
        $this->assertTrue($export['hasupgradepath']);
        $this->assertSame('A2 Grammar', $export['upgradefromlabel']);
        $this->assertSame('A2 Full', $export['upgradetolabel']);
        $this->assertArrayNotHasKey('badges', $export);
        $this->assertArrayNotHasKey('hasbadges', $export);
    }
}
