<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\promotion;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\catalog\CommerceCartProductQuote;
use local_subscriptions\commerce\cart\policy\CommerceSingleQuantityPolicy;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\CommercePromotionRepository;
use local_subscriptions\commerce\promotion\service\CommercePromotionEligibilityEvaluator;
use local_subscriptions\commerce\promotion\service\CommercePromotionEngine;

final class commerce_795g7cd_promotion_cart_test extends \advanced_testcase {
    public function test_manual_percentage_code_is_applied_to_cart_totals(): void {
        $service = $this->service([
            new CommercePromotion(1, 'Bienvenue', 'WELCOME20', 'percentage', 2000, 'EUR', 0,
                null, null, true, false, false, 10, null, null),
        ]);
        $service->add_product(96, 'EUR', 'fr', 'COURSE-A1', 10);
        $service->apply_promotion_code(96, 'EUR', 'welcome20');
        $snapshot = $service->snapshot(96, 'EUR', 'fr', 1000);

        $this->assertSame(25000, $snapshot->get_totals()->get_subtotal()->get_amount_minor());
        $this->assertSame(5000, $snapshot->get_totals()->get_discount()->get_amount_minor());
        $this->assertSame(20000, $snapshot->get_totals()->get_total()->get_amount_minor());
        $this->assertCount(1, $snapshot->get_promotion_adjustments());
        $this->assertSame('WELCOME20', $snapshot->get_promotion_adjustments()[0]->get_code());
    }

    public function test_fixed_discount_is_capped_to_eligible_subtotal(): void {
        $service = $this->service([
            new CommercePromotion(2, 'Livre offert', 'BOOKFREE', 'fixed', 5000, 'EUR', 0,
                null, null, true, false, false, 10, null, null, ['BOOK']),
        ]);
        $service->add_product(96, 'EUR', 'fr', 'BOOK', 20);
        $service->apply_promotion_code(96, 'EUR', 'BOOKFREE');
        $snapshot = $service->snapshot(96, 'EUR', 'fr', 1000);

        $this->assertSame(1500, $snapshot->get_totals()->get_discount()->get_amount_minor());
        $this->assertSame(0, $snapshot->get_totals()->get_total()->get_amount_minor());
    }

    public function test_unknown_code_is_exposed_as_structured_cart_message(): void {
        $service = $this->service([]);
        $service->add_product(96, 'EUR', 'fr', 'COURSE-A1', 10);
        $result = $service->apply_promotion_code(96, 'EUR', 'UNKNOWN');
        $snapshot = $service->snapshot(96, 'EUR', 'fr', 1000);

        $this->assertSame(0, $snapshot->get_totals()->get_discount()->get_amount_minor());
        $this->assertSame('promotion_not_found', $result->get_messages()[0]->get_code());
        $this->assertArrayNotHasKey('promotion_code', $service->open(96, 'EUR')->get_metadata());
    }

    public function test_only_one_valid_manual_code_is_kept_in_cart_metadata(): void {
        $service = $this->service([
            new CommercePromotion(3, 'Premier', 'FIRST', 'percentage', 1000, 'EUR', 0,
                null, null, true, false, false, 10, null, null),
            new CommercePromotion(4, 'Second', 'SECOND', 'percentage', 1500, 'EUR', 0,
                null, null, true, false, false, 20, null, null),
        ]);
        $service->add_product(96, 'EUR', 'fr', 'COURSE-A1', 10);
        $firstresult = $service->apply_promotion_code(96, 'EUR', 'FIRST');
        $secondresult = $service->apply_promotion_code(96, 'EUR', 'SECOND');

        $this->assertTrue($firstresult->has_changed());
        $this->assertTrue($secondresult->has_changed());
        $this->assertSame('SECOND', $service->open(96, 'EUR')->get_metadata()['promotion_code']);
        $service->remove_promotion_code(96, 'EUR');
        $this->assertArrayNotHasKey('promotion_code', $service->open(96, 'EUR')->get_metadata());
    }

    /** @param CommercePromotion[] $promotions */
    private function service(array $promotions): CommerceCartService {
        $catalog = new class implements CommerceCartCatalogGateway {
            public function quote(string $productsku, int $priceid, string $currency, string $language, ?int $at = null): CommerceCartProductQuote {
                $sku = strtoupper($productsku);
                return new CommerceCartProductQuote(
                    $sku,
                    $priceid,
                    $sku,
                    CommerceMoney::from_minor($sku === 'BOOK' ? 1500 : 25000, $currency),
                    new CommerceSingleQuantityPolicy(),
                    $sku === 'BOOK' ? 'digital' : 'course'
                );
            }
        };
        $repository = new class($promotions) implements CommercePromotionRepository {
            public function __construct(private array $promotions) {}
            public function get_by_code(string $code): ?CommercePromotion {
                foreach ($this->promotions as $promotion) {
                    if ($promotion->get_code() === strtoupper(trim($code))) { return $promotion; }
                }
                return null;
            }
            public function find_automatic(int $at): array {
                return array_values(array_filter($this->promotions, static fn(CommercePromotion $p): bool => $p->is_automatic()));
            }
            public function save(CommercePromotion $promotion): CommercePromotion { return $promotion; }
            public function count_redemptions(int $promotionid, ?int $userid = null): int { return 0; }
        };
        $engine = new CommercePromotionEngine($repository, new CommercePromotionEligibilityEvaluator($repository));
        return new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($catalog, $engine),
            $catalog
        );
    }
}
