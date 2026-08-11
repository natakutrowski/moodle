<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\eligibility;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\ownership\CommerceCartOwnershipGateway;
use local_subscriptions\commerce\promotion\domain\CommercePromotionEligibility;

/** Evaluates customer-specific rules such as effective product ownership. */
final class CommercePromotionCustomerEligibilityEvaluator {
    public function __construct(private readonly CommerceCartOwnershipGateway $ownership) {
    }

    public function evaluate(
        CommercePromotionEligibilityRuleSet $rules,
        ?int $userid
    ): CommercePromotionEligibility {
        if ($rules->is_empty()) {
            return CommercePromotionEligibility::eligible();
        }

        if ($rules->requires_login() && ($userid === null || $userid <= 0)) {
            return CommercePromotionEligibility::rejected('requires_login');
        }

        if ($userid === null || $userid <= 0) {
            return CommercePromotionEligibility::eligible();
        }

        $checks = [];
        foreach ($rules->get_owned_skus() as $sku) {
            $checks[] = [
                'passed' => $this->ownership->owns($userid, $sku),
                'reason' => 'missing_required_product',
            ];
        }
        foreach ($rules->get_not_owned_skus() as $sku) {
            $checks[] = [
                'passed' => !$this->ownership->owns($userid, $sku),
                'reason' => 'already_owns_excluded_product',
            ];
        }

        if ($checks === []) {
            return CommercePromotionEligibility::eligible();
        }

        if ($rules->get_mode() === CommercePromotionEligibilityRuleSet::MODE_ANY) {
            foreach ($checks as $check) {
                if ($check['passed']) {
                    return CommercePromotionEligibility::eligible();
                }
            }
            return CommercePromotionEligibility::rejected('customer_not_eligible');
        }

        foreach ($checks as $check) {
            if (!$check['passed']) {
                return CommercePromotionEligibility::rejected((string)$check['reason']);
            }
        }

        return CommercePromotionEligibility::eligible();
    }
}
