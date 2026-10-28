<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\catalog\CommerceCartProductQuote;
use local_subscriptions\commerce\cart\policy\CommerceSingleQuantityPolicy;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\CommercePromotionRepository;
use local_subscriptions\commerce\promotion\service\CommercePromotionEligibilityEvaluator;
use local_subscriptions\commerce\promotion\service\CommercePromotionEngine;

/** Checkout coupon does not create or mutate a normal basket. */
final class commerce_798d1_checkout_direct_promo_test extends advanced_testcase {
    public function test_valid_code_reprices_isolated_direct_purchase_and_not_regular_cart(): void {
        $this->resetAfterTest(true);
        CommerceDirectPurchaseSession::clear();
        $service = $this->service();
        $normalcart = $service->open(96, 'EUR');
        $cartuuid = $normalcart->get_uuid();
        $directuuid = str_repeat('b', 32);

        CommerceDirectPurchaseSession::store('EUR', 'COURSE-A1', 10, 1, [], $directuuid);
        $purchase = CommerceDirectPurchaseSession::current('EUR');
        self::assertSame('', $purchase['promotioncode']);
        self::assertNull($service->validate_direct_promotion_code(96, $purchase, 'fr', ' welcome20 '));
        CommerceDirectPurchaseSession::set_promotion_code('EUR', 'welcome20');
        $purchase = CommerceDirectPurchaseSession::current('EUR');

        $snapshot = $service->direct_snapshot(
            96, 'EUR', 'fr', $purchase['sku'], $purchase['priceid'],
            $purchase['quantity'], $purchase['metadata'],
            cartuuid: $purchase['cartuuid'], reservepedagogicalseat: false,
            promotioncode: $purchase['promotioncode']
        );
        self::assertSame(25000, $snapshot->get_totals()->get_subtotal()->get_amount_minor());
        self::assertSame(20000, $snapshot->get_totals()->get_total()->get_amount_minor());
        self::assertSame('WELCOME20', $snapshot->get_cart()->get_metadata()['promotion_code']);
        self::assertSame($directuuid, $snapshot->get_cart()->get_uuid());

        $unchangedcart = $service->open(96, 'EUR');
        self::assertSame($cartuuid, $unchangedcart->get_uuid());
        self::assertTrue($unchangedcart->is_empty());
        self::assertArrayNotHasKey('promotion_code', $unchangedcart->get_metadata());
    }

    public function test_rejected_code_and_remove_are_isolated(): void {
        $this->resetAfterTest(true);
        CommerceDirectPurchaseSession::clear();
        $service = $this->service();
        CommerceDirectPurchaseSession::store('EUR', 'COURSE-A1', 10, 1, [], str_repeat('a', 32));
        $purchase = CommerceDirectPurchaseSession::current('EUR');
        $rejection = $service->validate_direct_promotion_code(96, $purchase, 'fr', 'INVALID');
        self::assertNotNull($rejection);
        self::assertSame('promotion_not_found', $rejection->get_code());
        self::assertSame('', CommerceDirectPurchaseSession::current('EUR')['promotioncode']);

        CommerceDirectPurchaseSession::set_promotion_code('EUR', 'WELCOME20');
        CommerceDirectPurchaseSession::set_promotion_code('EUR', null);
        self::assertSame('', CommerceDirectPurchaseSession::current('EUR')['promotioncode']);
    }

    public function test_new_buy_now_resets_promo_and_currency_is_checked(): void {
        $this->resetAfterTest(true);
        CommerceDirectPurchaseSession::clear();
        CommerceDirectPurchaseSession::store('EUR', 'COURSE-A1', 10, 1);
        CommerceDirectPurchaseSession::set_promotion_code('EUR', 'WELCOME20');
        self::assertSame('WELCOME20', CommerceDirectPurchaseSession::current('EUR')['promotioncode']);
        CommerceDirectPurchaseSession::store('RUB', 'COURSE-A1', 11, 1);
        self::assertNull(CommerceDirectPurchaseSession::current('EUR'));
        self::assertSame('', CommerceDirectPurchaseSession::current('RUB')['promotioncode']);
        $this->expectException(\coding_exception::class);
        CommerceDirectPurchaseSession::set_promotion_code('EUR', 'WELCOME20');
    }

    public function test_checkout_ui_and_payment_path_share_the_promo_validation(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $page = file_get_contents($root . 'templates/checkout/page.mustache');
        $handler = file_get_contents($root . 'commerce_checkout_promotion.php');
        $action = file_get_contents($root . 'commerce_checkout_action.php');
        $runtime = file_get_contents($root . 'classes/commerce/checkout/unified/CommerceCheckoutRuntime.php');
        self::assertStringContainsString('name="promotioncode"', $page);
        self::assertStringContainsString('name="flow" value="{{flow}}"', $page);
        self::assertStringContainsString('name="sesskey"', $page);
        self::assertStringContainsString('require_sesskey();', $handler);
        self::assertStringContainsString('validate_direct_promotion_code(', $handler);
        self::assertStringContainsString('validate_direct_promotion_code(', $action);
        self::assertStringContainsString('promotioncode: (string)($directpurchase[', $runtime);
        $switch = file_get_contents($root . 'cart_action.php');
        self::assertStringContainsString('validate_direct_promotion_code(', $switch);
        self::assertStringContainsString('promotion_currency_removed', $switch);
    }

    private function service(): CommerceCartService {
        $catalog = new class implements CommerceCartCatalogGateway {
            public function quote(string $productsku, int $priceid, string $currency, string $language, ?int $at = null): CommerceCartProductQuote {
                return new CommerceCartProductQuote(
                    strtoupper($productsku), $priceid, 'Cours A1',
                    CommerceMoney::from_minor(25000, $currency),
                    new CommerceSingleQuantityPolicy(), 'course'
                );
            }
        };
        $repository = new class implements CommercePromotionRepository {
            public function get_by_code(string $code): ?CommercePromotion {
                return strtoupper(trim($code)) === 'WELCOME20'
                    ? new CommercePromotion(100, 'Bienvenue', 'WELCOME20', 'percentage', 2000, 'EUR', 0,
                        null, null, true, false, false, 10, null, null)
                    : null;
            }
            public function find_automatic(int $at): array { return []; }
            public function save(CommercePromotion $promotion): CommercePromotion { return $promotion; }
            public function count_redemptions(int $promotionid, ?int $userid = null): int { return 0; }
        };
        return new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($catalog, new CommercePromotionEngine(
                $repository, new CommercePromotionEligibilityEvaluator($repository)
            )),
            $catalog
        );
    }
}
