<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\promotion;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\domain\CommercePromotionEvaluationContext;
use local_subscriptions\commerce\promotion\repository\CommercePromotionRepository;
use local_subscriptions\commerce\promotion\service\CommercePromotionEligibilityEvaluator;

final class commerce_795g7ab_promotion_domain_test extends \advanced_testcase {
    public function test_manual_promotion_requires_a_code(): void {
        $this->expectException(\coding_exception::class);
        new CommercePromotion(null, 'Welcome', null, 'percentage', 2000, null, 0, null, null, true, false, false, 0, null, null);
    }

    public function test_evaluator_accepts_matching_active_promotion(): void {
        $promotion = new CommercePromotion(7, 'Welcome', 'WELCOME20', 'percentage', 2000, 'EUR', 5000,
            100, 500, true, false, false, 10, null, 1, ['COURSE-A1']);
        $context = new CommercePromotionEvaluationContext(12000, 'EUR', 42,
            [['sku' => 'COURSE-A1', 'type' => 'course', 'subtotalminor' => 12000]], 200);
        $result = (new CommercePromotionEligibilityEvaluator($this->repository()))->evaluate($promotion, $context);
        $this->assertTrue($result->is_eligible());
        $this->assertSame('eligible', $result->get_reason());
    }

    public function test_evaluator_rejects_expired_promotion(): void {
        $promotion = new CommercePromotion(7, 'Summer', 'SUMMER', 'fixed', 1500, 'EUR', 0,
            null, 100, true, false, false, 0, null, null);
        $context = new CommercePromotionEvaluationContext(12000, 'EUR', 42,
            [['sku' => 'COURSE-A1', 'type' => 'course', 'subtotalminor' => 12000]], 100);
        $result = (new CommercePromotionEligibilityEvaluator($this->repository()))->evaluate($promotion, $context);
        $this->assertFalse($result->is_eligible());
        $this->assertSame('expired', $result->get_reason());
    }

    private function repository(): CommercePromotionRepository {
        return new class implements CommercePromotionRepository {
            public function get_by_code(string $code): ?CommercePromotion { return null; }
            public function find_automatic(int $at): array { return []; }
            public function save(CommercePromotion $promotion): CommercePromotion { return $promotion; }
            public function count_redemptions(int $promotionid, ?int $userid = null): int { return 0; }
        };
    }
}
