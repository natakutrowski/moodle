<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\domain\CommercePromotionEligibility;
use local_subscriptions\commerce\promotion\domain\CommercePromotionEvaluationContext;
use local_subscriptions\commerce\promotion\repository\CommercePromotionRepository;
use local_subscriptions\commerce\promotion\eligibility\CommercePromotionEligibilityRuleSet;
use local_subscriptions\commerce\promotion\eligibility\CommercePromotionCustomerEligibilityEvaluator;

/** Applies G7B safety and eligibility rules to one promotion. */
final class CommercePromotionEligibilityEvaluator {
    public function __construct(
        private readonly CommercePromotionRepository $repository,
        private readonly ?CommercePromotionCustomerEligibilityEvaluator $customereligibility = null
    ) {}

    public function evaluate(
        CommercePromotion $promotion,
        CommercePromotionEvaluationContext $context
    ): CommercePromotionEligibility {
        if (!$promotion->is_active()) {
            return CommercePromotionEligibility::rejected('inactive');
        }
        $at = $context->get_evaluated_at();
        if ($promotion->get_starts_at() !== null && $at < $promotion->get_starts_at()) {
            return CommercePromotionEligibility::rejected('not_started');
        }
        if ($promotion->get_ends_at() !== null && $at >= $promotion->get_ends_at()) {
            return CommercePromotionEligibility::rejected('expired');
        }
        if ($promotion->get_currency() !== null && $promotion->get_currency() !== $context->get_currency()) {
            return CommercePromotionEligibility::rejected('currency_mismatch');
        }
        if ($context->get_subtotal_minor() < $promotion->get_minimum_cart_minor()) {
            return CommercePromotionEligibility::rejected('minimum_cart_not_reached', [
                'requiredminor' => $promotion->get_minimum_cart_minor(),
            ]);
        }
        if (!$this->has_eligible_item($promotion, $context)) {
            return CommercePromotionEligibility::rejected('no_eligible_product');
        }
        $customerrules = CommercePromotionEligibilityRuleSet::from_metadata($promotion->get_metadata());
        if (!$customerrules->is_empty()) {
            if ($this->customereligibility === null) {
                return CommercePromotionEligibility::rejected('customer_rule_runtime_unavailable');
            }
            $customerresult = $this->customereligibility->evaluate($customerrules, $context->get_user_id());
            if (!$customerresult->is_eligible()) {
                return $customerresult;
            }
        }
        $promotionid = $promotion->get_id();
        if ($promotionid !== null && $promotion->get_global_usage_limit() !== null
                && $this->repository->count_redemptions($promotionid) >= $promotion->get_global_usage_limit()) {
            return CommercePromotionEligibility::rejected('global_usage_limit_reached');
        }
        if ($promotionid !== null && $context->get_user_id() !== null && $promotion->get_user_usage_limit() !== null
                && $this->repository->count_redemptions($promotionid, $context->get_user_id()) >= $promotion->get_user_usage_limit()) {
            return CommercePromotionEligibility::rejected('user_usage_limit_reached');
        }
        return CommercePromotionEligibility::eligible();
    }

    private function has_eligible_item(
        CommercePromotion $promotion,
        CommercePromotionEvaluationContext $context
    ): bool {
        $skus = $promotion->get_product_skus();
        $types = $promotion->get_product_types();
        if ($skus === [] && $types === []) {
            return $context->get_items() !== [];
        }
        foreach ($context->get_items() as $item) {
            if (in_array($item['sku'], $skus, true) || in_array($item['type'], $types, true)) {
                return true;
            }
        }
        return false;
    }
}
