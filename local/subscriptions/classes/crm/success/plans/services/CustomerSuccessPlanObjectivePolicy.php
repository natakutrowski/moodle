<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessActionCategory;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation;

/**
 * Determines the primary objective of a generated plan.
 */
final class CustomerSuccessPlanObjectivePolicy {

    /**
     * @param CustomerSuccessRecommendationInput[] $recommendations
     */
    public function objective_key(
        array $recommendations
    ): string {
        $categories = array_count_values(
            array_map(
                static fn(
                    CustomerSuccessRecommendationInput $recommendation
                ): string => $recommendation->category,
                $recommendations
            )
        );

        foreach (
            [
                CustomerSuccessActionCategory::RETENTION =>
                    'reduce_churn_risk',

                CustomerSuccessActionCategory::PAYMENT =>
                    'resolve_payment_friction',

                CustomerSuccessActionCategory::SUPPORT =>
                    'resolve_support_pressure',

                CustomerSuccessActionCategory::ACCESS =>
                    'restore_learning_access',

                CustomerSuccessActionCategory::LEARNING =>
                    'restore_learning_engagement',

                CustomerSuccessActionCategory::COMMERCIAL =>
                    'develop_customer_opportunity',
            ]
            as $category => $objectivekey
        ) {
            if (($categories[$category] ?? 0) > 0) {
                return $objectivekey;
            }
        }

        return 'coordinate_customer_success';
    }

    public function title(
        string $objectivekey
    ): string {
        return
            CustomerSuccessPlanPresentation::
                generated_title_value(
                    $objectivekey
                );
    }
}