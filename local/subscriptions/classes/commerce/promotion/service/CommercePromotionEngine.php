<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\domain\CommercePromotionAdjustment;
use local_subscriptions\commerce\promotion\domain\CommercePromotionCalculation;
use local_subscriptions\commerce\promotion\domain\CommercePromotionEvaluationContext;
use local_subscriptions\commerce\promotion\repository\CommercePromotionRepository;

/** Calculates manual and automatic promotion adjustments for one cart. */
final class CommercePromotionEngine {
    public function __construct(
        private readonly CommercePromotionRepository $repository,
        private readonly CommercePromotionEligibilityEvaluator $eligibility
    ) {
    }

    /**
     * @param array<int, array{sku:string,type:string,subtotalminor:int}> $items
     */
    public function calculate(
        int $subtotalminor,
        string $currency,
        ?int $userid,
        array $items,
        ?string $manualcode,
        int $at
    ): CommercePromotionCalculation {
        $context = new CommercePromotionEvaluationContext($subtotalminor, $currency, $userid, $items, $at);
        $candidates = [];
        $rejections = [];

        if ($manualcode !== null && trim($manualcode) !== '') {
            $normalised = strtoupper(trim($manualcode));
            $manual = $this->repository->get_by_code($normalised);
            if ($manual === null) {
                $rejections[] = ['code' => $normalised, 'reason' => 'not_found', 'context' => []];
            } else {
                $candidates[] = $manual;
            }
        }

        foreach ($this->repository->find_automatic($at) as $promotion) {
            $candidates[] = $promotion;
        }

        usort($candidates, static fn(CommercePromotion $a, CommercePromotion $b): int =>
            $b->get_priority() <=> $a->get_priority() ?: (($a->get_id() ?? 0) <=> ($b->get_id() ?? 0))
        );

        $adjustments = [];
        $remainingminor = $subtotalminor;
        $stackingopen = true;

        foreach ($candidates as $promotion) {
            if (!$stackingopen) {
                break;
            }
            $result = $this->eligibility->evaluate($promotion, $context);
            if (!$result->is_eligible()) {
                if (!$promotion->is_automatic()) {
                    $rejections[] = [
                        'code' => (string)$promotion->get_code(),
                        'reason' => (string)$result->get_reason(),
                        'context' => $result->get_context(),
                    ];
                }
                continue;
            }

            $eligibleminor = $this->eligible_subtotal($promotion, $items);
            $discountminor = $this->discount_minor($promotion, $eligibleminor);
            $discountminor = min($discountminor, $remainingminor);
            if ($discountminor <= 0) {
                continue;
            }

            $adjustments[] = new CommercePromotionAdjustment(
                $promotion->get_id(),
                $promotion->get_name(),
                $promotion->get_code(),
                CommerceMoney::from_minor($discountminor, $currency),
                $promotion->is_automatic()
            );
            $remainingminor -= $discountminor;
            $stackingopen = $promotion->is_stackable();
        }

        return new CommercePromotionCalculation($adjustments, $rejections);
    }

    /** @param array<int, array{sku:string,type:string,subtotalminor:int}> $items */
    private function eligible_subtotal(CommercePromotion $promotion, array $items): int {
        $skus = $promotion->get_product_skus();
        $types = $promotion->get_product_types();
        $all = $skus === [] && $types === [];
        $total = 0;
        foreach ($items as $item) {
            if ($all || in_array($item['sku'], $skus, true) || in_array($item['type'], $types, true)) {
                $total += $item['subtotalminor'];
            }
        }
        return $total;
    }

    private function discount_minor(CommercePromotion $promotion, int $eligibleminor): int {
        if ($promotion->get_discount_type() === CommercePromotion::TYPE_PERCENTAGE) {
            return intdiv($eligibleminor * $promotion->get_discount_value(), 10000);
        }
        return min($promotion->get_discount_value(), $eligibleminor);
    }
}
