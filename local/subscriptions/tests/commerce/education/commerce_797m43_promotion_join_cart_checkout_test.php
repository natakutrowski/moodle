<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\cart\catalog\MoodleCommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\currency\CommerceCartCurrencySwitchService;
use local_subscriptions\commerce\cart\ownership\MoodleCommerceCartOwnershipGateway;
use local_subscriptions\commerce\cart\policy\CommerceQuantityPolicyResolver;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPromotionJoinCartTransferService;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutContext;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPurchaseBuilder;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSummaryBuilder;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutValidator;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinOperation;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPricingService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibilityService;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

/** M4.3 integration contract for owner-only promotion_join shopping. */
final class commerce_797m43_promotion_join_cart_checkout_test extends advanced_testcase {
    /**
     * @return array{
     *   user:object,
     *   course:object,
     *   product:CommerceProduct,
     *   promotion:CommercePedagogicalPromotion,
     *   eurprice:CommerceProductPrice,
     *   rubprice:?CommerceProductPrice,
     *   carts:CommerceCartService
     * }
     */
    private function scenario(string $sku, int $now, bool $withrub = false): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $product = $products->save(new CommerceProduct(
            $sku,
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            $sku
        ));

        (new CommerceProductEntitlementRepository($DB, $hydrator, $products))
            ->replace_for_product($sku, [
                new CommerceProductEntitlementDefinition(
                    $sku,
                    'course_access',
                    'course:' . $course->id . ':full'
                ),
            ]);

        $prices = new CommerceProductPriceRepository($DB, $hydrator, $products);
        $eurprice = $prices->save(new CommerceProductPrice(
            $sku,
            CommerceMoney::from_minor(200, 'EUR'),
            true
        ));
        $rubprice = $withrub
            ? $prices->save(new CommerceProductPrice(
                $sku,
                CommerceMoney::from_minor(20000, 'RUB'),
                true
            ))
            : null;

        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                strtolower($sku) . '-promo',
                'Promotion ' . $sku,
                (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now - HOURSECS,
                $now + DAYSECS,
                $now + DAYSECS,
                null,
                3,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            3,
            null,
            $now
        );

        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-m43-' . strtolower($sku),
            'idempotencykey' => 'idem-m43-' . strtolower($sku),
            'purchasereference' => 'purchase-m43-' . strtolower($sku),
            'itemreference' => 'item-m43-' . strtolower($sku),
            'productsku' => $sku,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $course->id . ':full',
            'quantity' => 1,
            'beneficiaryuserid' => (int)$user->id,
            'beneficiaryemail' => $user->email,
            'validfrom' => $now - 10,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $joinpricing = CommercePedagogicalPromotionJoinPricingService::create($DB);
        $joinpricing->configure(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            'EUR',
            100,
            null,
            $now
        );
        if ($withrub) {
            $joinpricing->configure(
                (int)$promotion->get_id(),
                (int)$product->get_id(),
                'RUB',
                15000,
                null,
                $now
            );
        }

        $catalog = new MoodleCommerceCartCatalogGateway(
            $products,
            $prices,
            new CommerceProductTranslationRepository($DB, $hydrator, $products),
            new CommerceQuantityPolicyResolver()
        );
        $ownership = new MoodleCommerceCartOwnershipGateway(
            new CommerceStorefrontOwnershipResolver($DB)
        );
        $eligibility = CommercePedagogicalPromotionJoinEligibilityService::create($DB);
        $reservations = CommercePedagogicalSeatReservationService::create($DB);
        $calculator = new CommerceCartCalculator(
            $catalog,
            null,
            null,
            null,
            null,
            $eligibility,
            $joinpricing
        );
        $carts = new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            $calculator,
            $catalog,
            $ownership,
            null,
            null,
            null,
            $reservations,
            $eligibility,
            $joinpricing
        );

        return compact(
            'user',
            'course',
            'product',
            'promotion',
            'eurprice',
            'rubprice',
            'carts'
        );
    }

    public function test_owned_course_stays_blocked_normally_but_promotion_join_uses_owner_price_and_pins_seat(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M43-CART', $now);
        $userid = (int)$s['user']->id;
        $priceid = (int)$s['eurprice']->get_id();

        $normal = $s['carts']->add_product(
            $userid,
            'EUR',
            'fr',
            'M43-CART',
            $priceid,
            1,
            [],
            $now
        );
        self::assertFalse($normal->has_changed());
        self::assertSame('already_owned', $normal->get_messages()[0]->get_code());

        $joined = $s['carts']->add_product(
            $userid,
            'EUR',
            'fr',
            'M43-CART',
            $priceid,
            1,
            ['operation' => CommercePedagogicalPromotionJoinOperation::OPERATION],
            $now
        );
        self::assertTrue($joined->has_changed());
        self::assertCount(1, $joined->get_cart()->get_items());

        $item = $joined->get_cart()->get_items()[0];
        $metadata = $item->get_metadata();
        self::assertSame('promotion_join', $metadata['operation']);
        self::assertSame($userid, $metadata['promotion_join_user_id']);
        self::assertSame((int)$s['promotion']->get_id(), $metadata['promotion_join_promotion_id']);
        self::assertSame((int)$s['course']->id, $metadata['promotion_join_course_id']);
        self::assertSame((int)$s['product']->get_id(), $metadata['promotion_join_product_id']);
        self::assertSame('M43-CART', $metadata['promotion_join_product_sku']);
        self::assertSame(100, $metadata['promotion_join_amount_minor']);
        self::assertSame('EUR', $metadata['promotion_join_currency']);

        $snapshot = $s['carts']->snapshot($userid, 'EUR', 'fr', $now);
        self::assertSame(100, $snapshot->get_totals()->get_subtotal()->get_amount_minor());
        self::assertSame(100, $snapshot->get_totals()->get_total()->get_amount_minor());
        self::assertSame([], $snapshot->get_promotion_adjustments());
        self::assertSame([], $snapshot->get_messages());

        $reservation = CommercePedagogicalSeatReservationRepository::create($DB)
            ->find_for_cart_product(
                $joined->get_cart()->get_uuid(),
                (int)$s['product']->get_id()
            );
        self::assertNotNull($reservation);
        self::assertSame((int)$s['promotion']->get_id(), $reservation->get_promotion_id());
        self::assertSame(CommercePedagogicalSeatReservation::ACTIVE, $reservation->get_state());
    }

    public function test_removing_promotion_join_line_releases_the_pinned_hold(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M43-REMOVE', $now);
        $userid = (int)$s['user']->id;
        $priceid = (int)$s['eurprice']->get_id();

        $joined = $s['carts']->add_product(
            $userid,
            'EUR',
            'fr',
            'M43-REMOVE',
            $priceid,
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($joined->has_changed());
        $cartuuid = $joined->get_cart()->get_uuid();

        $removed = $s['carts']->remove_product(
            $userid,
            'EUR',
            'M43-REMOVE',
            $priceid
        );
        self::assertTrue($removed->has_changed());

        $reservation = CommercePedagogicalSeatReservationRepository::create($DB)
            ->find_for_cart_product($cartuuid, (int)$s['product']->get_id());
        self::assertNotNull($reservation);
        self::assertSame(CommercePedagogicalSeatReservation::RELEASED, $reservation->get_state());
    }

    public function test_owner_price_removal_after_cart_add_blocks_checkout(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M43-REPRICE', $now);
        $userid = (int)$s['user']->id;
        $priceid = (int)$s['eurprice']->get_id();

        $joined = $s['carts']->add_product(
            $userid,
            'EUR',
            'fr',
            'M43-REPRICE',
            $priceid,
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($joined->has_changed());

        CommercePedagogicalPromotionJoinPricingService::create($DB)->clear(
            (int)$s['promotion']->get_id(),
            (int)$s['product']->get_id(),
            'EUR'
        );

        $snapshot = $s['carts']->snapshot($userid, 'EUR', 'fr', $now + 1);
        self::assertCount(1, $snapshot->get_messages());
        self::assertSame(
            'promotion_join_price_unavailable',
            $snapshot->get_messages()[0]->get_code()
        );

        $context = new CommerceCheckoutContext(
            $userid,
            'EUR',
            'fr',
            'stripe',
            '/success',
            '/cancel',
            false
        );
        $summary = (new CommerceCheckoutSummaryBuilder(new CommerceCheckoutValidator()))
            ->build($snapshot, $context, $now + 1);
        self::assertFalse($summary->is_valid());
        self::assertSame(
            'cart_promotion_join_price_unavailable',
            $summary->get_validation()->get_issues()[0]->get_code()
        );
    }

    public function test_checkout_freezes_promotion_join_as_accompaniment_price_without_catalogue_discount(): void {
        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M43-CHECKOUT', $now);
        $userid = (int)$s['user']->id;
        $priceid = (int)$s['eurprice']->get_id();

        $joined = $s['carts']->add_product(
            $userid,
            'EUR',
            'fr',
            'M43-CHECKOUT',
            $priceid,
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($joined->has_changed());

        $snapshot = $s['carts']->snapshot($userid, 'EUR', 'fr', $now);
        $context = new CommerceCheckoutContext(
            $userid,
            'EUR',
            'fr',
            'stripe',
            '/success',
            '/cancel',
            false
        );
        $summary = (new CommerceCheckoutSummaryBuilder(new CommerceCheckoutValidator()))
            ->build($snapshot, $context, $now);
        self::assertTrue($summary->is_valid());

        $purchase = (new CommerceCheckoutPurchaseBuilder())->build(
            $summary,
            new CommerceCustomer($userid, $s['user']->email),
            'm43-checkout'
        );
        self::assertSame(100, $purchase->get_total_amount_minor());
        self::assertSame(100, $purchase->get_metadata_value('cart_list_total_minor'));
        self::assertSame(0, $purchase->get_metadata_value('cart_discount_minor'));
        self::assertSame(0, $purchase->get_metadata_value('cart_product_promotion_minor'));

        $items = $purchase->get_items();
        self::assertCount(1, $items);
        self::assertSame(100, $items[0]->get_unit_amount_minor());
        self::assertSame('promotion_join', $items[0]->get_metadata_value('operation'));
        self::assertSame('promotion_join', $items[0]->get_metadata_value('commerceoperation'));
        self::assertSame(
            (int)$s['promotion']->get_id(),
            $items[0]->get_metadata_value('promotion_join_promotion_id')
        );
        self::assertSame(100, $items[0]->get_metadata_value('locked_list_unit_minor'));
        self::assertSame(100, $items[0]->get_metadata_value('locked_payable_unit_minor'));
        self::assertSame(0, $items[0]->get_metadata_value('locked_total_discount_minor'));
    }

    public function test_checkout_builder_revalidates_owner_price_at_the_final_freeze(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M43-FREEZE', $now);
        $userid = (int)$s['user']->id;
        $priceid = (int)$s['eurprice']->get_id();

        $joined = $s['carts']->add_product(
            $userid,
            'EUR',
            'fr',
            'M43-FREEZE',
            $priceid,
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($joined->has_changed());

        $snapshot = $s['carts']->snapshot($userid, 'EUR', 'fr', $now);
        $context = new CommerceCheckoutContext(
            $userid,
            'EUR',
            'fr',
            'stripe',
            '/success',
            '/cancel',
            false
        );
        $summary = (new CommerceCheckoutSummaryBuilder(new CommerceCheckoutValidator()))
            ->build($snapshot, $context, $now);
        self::assertTrue($summary->is_valid());
        self::assertSame(100, $summary->get_total_minor());

        CommercePedagogicalPromotionJoinPricingService::create($DB)->configure(
            (int)$s['promotion']->get_id(),
            (int)$s['product']->get_id(),
            'EUR',
            150,
            null,
            $now + 1
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Promotion join checkout price is no longer valid.');
        (new CommerceCheckoutPurchaseBuilder())->build(
            $summary,
            new CommerceCustomer($userid, $s['user']->email),
            'm43-freeze'
        );
    }

    public function test_buy_now_isolated_cart_uses_the_same_owner_contract(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M43-DIRECT', $now);
        $userid = (int)$s['user']->id;
        $priceid = (int)$s['eurprice']->get_id();

        $prepared = $s['carts']->prepare_direct_product(
            $userid,
            'EUR',
            'fr',
            'M43-DIRECT',
            $priceid,
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($prepared->has_changed());
        self::assertSame('direct', $prepared->get_cart()->get_metadata()['purchase_flow']);

        $snapshot = $s['carts']->direct_snapshot(
            $userid,
            'EUR',
            'fr',
            'M43-DIRECT',
            $priceid,
            1,
            ['operation' => 'promotion_join'],
            $now,
            $prepared->get_cart()->get_uuid()
        );
        self::assertSame(100, $snapshot->get_totals()->get_total()->get_amount_minor());

        $reservation = CommercePedagogicalSeatReservationRepository::create($DB)
            ->find_for_cart_product(
                $prepared->get_cart()->get_uuid(),
                (int)$s['product']->get_id()
            );
        self::assertNotNull($reservation);
        self::assertSame((int)$s['promotion']->get_id(), $reservation->get_promotion_id());
    }

    public function test_public_endpoints_preserve_promotion_join_operation_token(): void {
        global $CFG;

        $this->resetAfterTest(true);

        foreach ([
            $CFG->dirroot . '/local/subscriptions/cart_action.php',
            $CFG->dirroot . '/local/subscriptions/ajax/checkout_express_eligibility.php',
        ] as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);
            self::assertStringContainsString(
                "optional_param('operation', '', PARAM_ALPHANUMEXT)",
                $source
            );
            self::assertStringNotContainsString(
                "optional_param('operation', '', PARAM_ALPHA)",
                $source
            );
        }

        self::assertSame(
            'promotion_join',
            clean_param('promotion_join', PARAM_ALPHANUMEXT)
        );
    }

    public function test_payment_launch_is_released_after_m44_dedicated_fulfillment(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/CommerceCheckoutRuntime.php'
        );
        self::assertIsString($source);
        self::assertStringNotContainsString(
            'commerce_promotion_join_payment_pending_fulfillment',
            $source
        );
        self::assertStringContainsString('persist_with_result', $source);
    }

    public function test_currency_switch_reprices_owner_join_and_drops_stale_currency_price_metadata(): void {
        global $DB, $SESSION;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M43-FX', $now, true);
        $userid = (int)$s['user']->id;
        $SESSION->local_subscriptions_commerce_carts = [];

        $runtime = CommerceCartRuntimeFactory::create();
        $added = $runtime->add_product(
            $userid,
            'EUR',
            'fr',
            'M43-FX',
            (int)$s['eurprice']->get_id(),
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($added->has_changed());
        $sourceuuid = $added->get_cart()->get_uuid();

        $result = CommerceCartCurrencySwitchService::create()->switch(
            $userid,
            'EUR',
            'RUB',
            'fr'
        );
        $snapshot = $result->get_snapshot();
        self::assertSame([], $result->get_removed_skus());
        self::assertSame('RUB', $snapshot->get_cart()->get_currency());
        self::assertSame(15000, $snapshot->get_totals()->get_total()->get_amount_minor());
        self::assertCount(1, $snapshot->get_items());

        $metadata = $snapshot->get_items()[0]->get_item()->get_metadata();
        self::assertSame('promotion_join', $metadata['operation']);
        self::assertSame((int)$s['promotion']->get_id(), $metadata['promotion_join_promotion_id']);
        self::assertArrayNotHasKey('promotion_join_amount_minor', $metadata);
        self::assertArrayNotHasKey('promotion_join_price_id', $metadata);
        self::assertArrayNotHasKey('promotion_join_currency', $metadata);

        $targetuuid = $snapshot->get_cart()->get_uuid();
        self::assertNotSame($sourceuuid, $targetuuid);
        $reservation = CommercePedagogicalSeatReservationRepository::create($DB)
            ->find_for_cart_product($targetuuid, (int)$s['product']->get_id());
        self::assertNotNull($reservation);
        self::assertSame((int)$s['promotion']->get_id(), $reservation->get_promotion_id());
        self::assertSame(CommercePedagogicalSeatReservation::ACTIVE, $reservation->get_state());

        $context = new CommerceCheckoutContext(
            $userid,
            'RUB',
            'fr',
            'alfa',
            '/success',
            '/cancel',
            false
        );
        $summary = (new CommerceCheckoutSummaryBuilder(new CommerceCheckoutValidator()))
            ->build($snapshot, $context, $now + 1);
        self::assertTrue($summary->is_valid());
        $purchase = (new CommerceCheckoutPurchaseBuilder())->build(
            $summary,
            new CommerceCustomer($userid, $s['user']->email),
            'm43-fx'
        );
        self::assertSame(15000, $purchase->get_total_amount_minor());
        $purchaseitem = $purchase->get_items()[0];
        self::assertSame('RUB', $purchaseitem->get_metadata_value('promotion_join_currency'));
        self::assertSame(15000, $purchaseitem->get_metadata_value('promotion_join_amount_minor'));
        self::assertNotNull($purchaseitem->get_metadata_value('promotion_join_price_id'));
    }

    public function test_direct_checkout_join_can_be_moved_to_normal_cart_without_waiting_for_ttl(): void {
        global $DB;

        $this->resetAfterTest(true);
        CommerceDirectPurchaseSession::clear();
        $now = time();
        $s = $this->scenario('M43-DIRECT-TO-CART', $now);
        $userid = (int)$s['user']->id;
        $priceid = (int)$s['eurprice']->get_id();

        $direct = $s['carts']->prepare_direct_product(
            $userid,
            'EUR',
            'fr',
            'M43-DIRECT-TO-CART',
            $priceid,
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($direct->has_changed());
        $sourceuuid = $direct->get_cart()->get_uuid();
        $directitem = $direct->get_cart()->get_items()[0];

        CommerceDirectPurchaseSession::store(
            'EUR',
            'M43-DIRECT-TO-CART',
            $priceid,
            1,
            $directitem->get_metadata(),
            $sourceuuid
        );

        $reservations = CommercePedagogicalSeatReservationService::create($DB);
        $leased = $reservations->renew_lease(
            'M43-DIRECT-TO-CART',
            $sourceuuid,
            $userid,
            1,
            $now + 1,
            10 * MINSECS,
            'checkout'
        );
        self::assertNotNull($leased);
        $holdid = $leased->get_id();
        $expiresat = $leased->get_expires_at();
        $checkoutstartedat = $leased->get_checkout_started_at();

        $before = CommercePedagogicalCapacityService::create($DB)
            ->for_product('M43-DIRECT-TO-CART', $now + 2)
            ->get_remaining();

        $moved = (new CommerceDirectPromotionJoinCartTransferService(
            $s['carts'],
            $reservations
        ))->add_current_to_cart(
            $userid,
            'EUR',
            'fr',
            'M43-DIRECT-TO-CART',
            $priceid,
            1,
            ['operation' => 'promotion_join'],
            $now + 2
        );

        self::assertTrue($moved->has_changed());
        self::assertCount(1, $moved->get_cart()->get_items());
        $targetuuid = $moved->get_cart()->get_uuid();
        self::assertNotSame($sourceuuid, $targetuuid);
        self::assertNull(CommerceDirectPurchaseSession::current_any_currency());

        $repo = CommercePedagogicalSeatReservationRepository::create($DB);
        self::assertNull(
            $repo->find_for_cart_product(
                $sourceuuid,
                (int)$s['product']->get_id()
            )
        );
        $targethold = $repo->find_for_cart_product(
            $targetuuid,
            (int)$s['product']->get_id()
        );
        self::assertNotNull($targethold);
        self::assertSame($holdid, $targethold->get_id());
        self::assertSame($expiresat, $targethold->get_expires_at());
        self::assertSame($checkoutstartedat, $targethold->get_checkout_started_at());
        self::assertSame(
            $before,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product('M43-DIRECT-TO-CART', $now + 2)
                ->get_remaining()
        );
    }

}
