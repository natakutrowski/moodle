<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B8 recommendation price projection contract. */
final class commerce_course_recommendation_pricing_j67b8_test
        extends \advanced_testcase {

    public function test_read_model_exports_upgrade_compare_and_percentage(): void {
        $item = new \local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationPresentation(
                'COURSE.A2.FULL',
                'course_access',
                'A2 Full',
                '',
                null,
                '/product',
                '170,00 EUR',
                '200,00 EUR',
                15,
                true,
                '56,00 EUR',
                'A2 Grammar',
                'A2 Full',
                false,
                null,
                null,
                null,
                '170,00 EUR',
                67
            );

        $data = $item->to_array();

        $this->assertTrue($data['upgrade']);
        $this->assertTrue($data['hasupgradecompareprice']);
        $this->assertSame('170,00 EUR', $data['upgradecomparepriceformatted']);
        $this->assertTrue($data['hasupgradediscount']);
        $this->assertSame(67, $data['upgradediscountpercentage']);
    }
}
