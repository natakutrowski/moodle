<?php

namespace local_subscriptions\crm\success\plans;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation;

/**
 * Tests neutral Customer Success storage markers.
 *
 * @covers \local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation
 */
final class presentation_test extends advanced_testcase {

    public function test_generated_title_uses_neutral_marker(): void {
        $this->assertSame(
            '[[csplan-objective:reduce_churn_risk]]',
            CustomerSuccessPlanPresentation::
                generated_title_value(
                    'reduce_churn_risk'
                )
        );
    }

    public function test_unknown_objective_falls_back_safely(): void {
        $this->assertSame(
            '[[csplan-objective:coordinate_customer_success]]',
            CustomerSuccessPlanPresentation::
                generated_title_value(
                    'unknown_objective'
                )
        );
    }

    public function test_generated_description_uses_neutral_marker(): void {
        $this->assertSame(
            '[[csplan-description:recommendations:4]]',
            CustomerSuccessPlanPresentation::
                generated_description_value(
                    4
                )
        );
    }

    public function test_negative_recommendation_count_is_normalized(): void {
        $this->assertSame(
            '[[csplan-description:recommendations:0]]',
            CustomerSuccessPlanPresentation::
                generated_description_value(
                    -2
                )
        );
    }

    public function test_custom_title_is_preserved(): void {
        $this->assertSame(
            'Custom Customer Success title',
            CustomerSuccessPlanPresentation::title(
                'reduce_churn_risk',
                'Custom Customer Success title'
            )
        );
    }

    public function test_empty_custom_title_is_preserved_as_empty(): void {
        $this->assertSame(
            '',
            CustomerSuccessPlanPresentation::title(
                'reduce_churn_risk',
                ''
            )
        );
    }

    public function test_null_description_remains_null(): void {
        $this->assertNull(
            CustomerSuccessPlanPresentation::description(
                null
            )
        );
    }

    public function test_empty_description_becomes_null(): void {
        $this->assertNull(
            CustomerSuccessPlanPresentation::description(
                '   '
            )
        );
    }

    public function test_custom_description_is_preserved(): void {
        $this->assertSame(
            'Custom Customer Success description',
            CustomerSuccessPlanPresentation::description(
                'Custom Customer Success description'
            )
        );
    }

}