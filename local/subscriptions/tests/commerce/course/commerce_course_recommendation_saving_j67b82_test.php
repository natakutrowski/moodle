<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B8.2 Upgrade saving projection. */
final class commerce_course_recommendation_saving_j67b82_test
        extends \advanced_testcase {

    public function test_read_model_exports_upgrade_saving(): void {
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
            67,
            '114,00 EUR'
        );

        $data = $item->to_array();

        $this->assertTrue($data['upgrade']);
        $this->assertTrue($data['hasupgradesaving']);
        $this->assertSame(
            '114,00 EUR',
            $data['upgradesavingformatted']
        );
    }
}
