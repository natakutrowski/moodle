<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\promotion;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\ownership\CommerceCartOwnershipGateway;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\domain\CommercePromotionEvaluationContext;
use local_subscriptions\commerce\promotion\eligibility\CommercePromotionCustomerEligibilityEvaluator;
use local_subscriptions\commerce\promotion\eligibility\CommercePromotionEligibilityRuleSet;
use local_subscriptions\commerce\promotion\repository\CommercePromotionRepository;
use local_subscriptions\commerce\promotion\service\CommercePromotionEligibilityEvaluator;
use local_subscriptions\commerce\promotion\service\CommercePromotionEngine;

final class commerce_conditional_promotions_j14f_test extends \advanced_testcase {
    public function test_pdf_owner_can_use_course_coupon_when_course_is_not_owned(): void {
        $promotion = $this->promotion(false);
        $evaluator = $this->evaluator(['DIGITAL.VERBES-3E-GROUPE']);
        $result = $evaluator->evaluate($promotion, $this->context(42));

        $this->assertTrue($result->is_eligible());
    }

    public function test_non_pdf_owner_is_rejected(): void {
        $promotion = $this->promotion(false);
        $result = $this->evaluator([])->evaluate($promotion, $this->context(42));

        $this->assertFalse($result->is_eligible());
        $this->assertSame('missing_required_product', $result->get_reason());
    }

    public function test_course_owner_is_rejected_even_when_pdf_is_owned(): void {
        $promotion = $this->promotion(false);
        $result = $this->evaluator([
            'DIGITAL.VERBES-3E-GROUPE',
            'SUB.PLAN.30',
        ])->evaluate($promotion, $this->context(42));

        $this->assertFalse($result->is_eligible());
        $this->assertSame('already_owns_excluded_product', $result->get_reason());
    }

    public function test_guest_is_asked_to_sign_in(): void {
        $promotion = $this->promotion(false);
        $result = $this->evaluator([])->evaluate($promotion, $this->context(null));

        $this->assertFalse($result->is_eligible());
        $this->assertSame('requires_login', $result->get_reason());
    }

    public function test_automatic_conditional_promotion_is_applied_without_code(): void {
        $promotion = $this->promotion(true);
        $repository = $this->repository($promotion);
        $engine = new CommercePromotionEngine(
            $repository,
            new CommercePromotionEligibilityEvaluator(
                $repository,
                new CommercePromotionCustomerEligibilityEvaluator(
                    $this->ownership(['DIGITAL.VERBES-3E-GROUPE'])
                )
            )
        );

        $calculation = $engine->calculate(
            10000,
            'EUR',
            42,
            [['sku' => 'SUB.PLAN.30', 'type' => 'course', 'subtotalminor' => 10000]],
            null,
            1000
        );

        $this->assertCount(1, $calculation->get_adjustments());
        $this->assertSame(2000, $calculation->get_adjustments()[0]->get_amount()->get_amount_minor());
    }

    private function promotion(bool $automatic): CommercePromotion {
        $rules = CommercePromotionEligibilityRuleSet::create(
            true,
            CommercePromotionEligibilityRuleSet::MODE_ALL,
            ['DIGITAL.VERBES-3E-GROUPE'],
            ['SUB.PLAN.30']
        );

        return new CommercePromotion(
            1,
            'Propriétaire du PDF — cours -20 %',
            $automatic ? null : 'VERBESPDF20',
            CommercePromotion::TYPE_PERCENTAGE,
            2000,
            'EUR',
            0,
            null,
            null,
            true,
            $automatic,
            false,
            100,
            null,
            1,
            ['SUB.PLAN.30'],
            [],
            [CommercePromotionEligibilityRuleSet::METADATA_KEY => $rules->to_metadata()]
        );
    }

    private function context(?int $userid): CommercePromotionEvaluationContext {
        return new CommercePromotionEvaluationContext(
            10000,
            'EUR',
            $userid,
            [['sku' => 'SUB.PLAN.30', 'type' => 'course', 'subtotalminor' => 10000]],
            1000
        );
    }

    /** @param string[] $owned */
    private function evaluator(array $owned): CommercePromotionEligibilityEvaluator {
        $repository = $this->repository($this->promotion(false));
        return new CommercePromotionEligibilityEvaluator(
            $repository,
            new CommercePromotionCustomerEligibilityEvaluator($this->ownership($owned))
        );
    }

    /** @param string[] $owned */
    private function ownership(array $owned): CommerceCartOwnershipGateway {
        return new class($owned) implements CommerceCartOwnershipGateway {
            public function __construct(private array $owned) {
            }

            public function owns(int $customerid, string $productsku): bool {
                return in_array(strtoupper($productsku), $this->owned, true);
            }
        };
    }

    private function repository(CommercePromotion $promotion): CommercePromotionRepository {
        return new class($promotion) implements CommercePromotionRepository {
            public function __construct(private CommercePromotion $promotion) {
            }

            public function get_by_code(string $code): ?CommercePromotion {
                return $this->promotion->get_code() === strtoupper(trim($code))
                    ? $this->promotion
                    : null;
            }

            public function find_automatic(int $at): array {
                return $this->promotion->is_automatic() ? [$this->promotion] : [];
            }

            public function save(CommercePromotion $promotion): CommercePromotion {
                return $promotion;
            }

            public function count_redemptions(int $promotionid, ?int $userid = null): int {
                return 0;
            }
        };
    }
}
