<?php

namespace local_subscriptions\crm\success\plans\services\dependencies;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessActionCategory;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessDependency;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;

/**
 * Access actions depend on unresolved payment actions.
 */
final class PaymentBeforeAccessRule
    implements CustomerSuccessDependencyRuleInterface {

    public function detect(
        CustomerSuccessRecommendationInput $candidate,
        CustomerSuccessRecommendationInput $possibledependency
    ): ?CustomerSuccessDependency {
        if (
            $candidate->category !==
                CustomerSuccessActionCategory::ACCESS ||
            $possibledependency->category !==
                CustomerSuccessActionCategory::PAYMENT
        ) {
            return null;
        }

        return new CustomerSuccessDependency(
            recommendationid:
                $candidate->recommendationid,
            dependsonrecommendationid:
                $possibledependency->recommendationid,
            reasonkey:
                'payment_before_access'
        );
    }
}