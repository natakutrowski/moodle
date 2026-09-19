<?php

declare(strict_types=1);

namespace local_subscriptions;

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
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseCurrencySwitchService;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

final class commerce_797l82_direct_purchase_currency_switch_test extends advanced_testcase {
    /**
     * @return array{
     *   product:CommerceProduct,
     *   eur:CommerceProductPrice,
     *   rub:?CommerceProductPrice,
     *   promotionid:int,
     *   productid:int
     * }
     */
    private function setup_offer(
        string $sku,
        int $now,
        bool $withrub = true
    ): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $promotion =
            CommercePedagogicalPromotionRepository::create($DB)->save(
                new CommercePedagogicalPromotion(
                    null,
                    'l82-' . strtolower($sku),
                    'L8.2 ' . $sku,
                    (int)$course->id,
                    CommercePedagogicalPromotionStatus::OPEN,
                    true,
                    null,
                    null,
                    $now,
                    null,
                    1,
                    null,
                    null,
                    $now,
                    $now
                )
            );

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $product = $products->save(
            new CommerceProduct(
                $sku,
                CommerceProductType::COURSE_ACCESS,
                CommerceProductStatus::ACTIVE,
                $sku
            )
        );

        $prices = new CommerceProductPriceRepository(
            $DB,
            $hydrator,
            $products
        );
        $eur = $prices->save(
            new CommerceProductPrice(
                $sku,
                CommerceMoney::from_minor(200, 'EUR')
            )
        );
        $rub = $withrub
            ? $prices->save(
                new CommerceProductPrice(
                    $sku,
                    CommerceMoney::from_minor(20000, 'RUB')
                )
            )
            : null;

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            1,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(
            new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                1,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(),
            $sku . ' group',
            0,
            null,
            null,
            null,
            null,
            null,
            $now,
            null,
            (int)$product->get_id()
        );

        return [
            'product' => $product,
            'eur' => $eur,
            'rub' => $rub,
            'promotionid' => (int)$promotion->get_id(),
            'productid' => (int)$product->get_id(),
        ];
    }

    private function carts(
        CommercePedagogicalSeatReservationService $reservations
    ): CommerceCartService {
        $catalog = new class implements CommerceCartCatalogGateway {
            public function quote(
                string $productsku,
                int $priceid,
                string $currency,
                string $language,
                ?int $at = null
            ): CommerceCartProductQuote {
                return new CommerceCartProductQuote(
                    strtoupper($productsku),
                    $priceid,
                    strtoupper($productsku),
                    CommerceMoney::from_minor(
                        strtoupper($currency) === 'RUB' ? 20000 : 200,
                        strtoupper($currency)
                    ),
                    new CommerceSingleQuantityPolicy()
                );
            }
        };

        return new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($catalog),
            $catalog,
            null,
            null,
            null,
            null,
            $reservations
        );
    }

    private function switcher(
        CommerceCartService $carts,
        CommercePedagogicalSeatReservationService $reservations
    ): CommerceDirectPurchaseCurrencySwitchService {
        global $DB;

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);

        return new CommerceDirectPurchaseCurrencySwitchService(
            $carts,
            new CommerceProductPriceRepository($DB, $hydrator, $products),
            $reservations
        );
    }

    public function test_direct_switch_rebinds_same_hold_and_keeps_normal_cart_isolated(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $offer = $this->setup_offer('L82-DIRECT', $now);

        $reservations =
            CommercePedagogicalSeatReservationService::create($DB);
        $reservationrepo =
            CommercePedagogicalSeatReservationRepository::create($DB);
        $carts = $this->carts($reservations);

        // A normal cart already exists and must remain untouched by Buy Now.
        $normal = $carts->add_product(
            0,
            'EUR',
            'fr',
            'L82-NORMAL',
            9001,
            1,
            ['normal_cart_marker' => 'keep-me'],
            $now
        );
        self::assertTrue($normal->has_changed());
        $normaluuid = $normal->get_cart()->get_uuid();

        $direct = $carts->prepare_direct_product(
            0,
            'EUR',
            'fr',
            'L82-DIRECT',
            (int)$offer['eur']->get_id(),
            1,
            [
                'showroom' => 'a1',
                'showroom_offer' => 'fr',
            ],
            $now
        );
        self::assertTrue($direct->has_changed());

        $sourceuuid = $direct->get_cart()->get_uuid();

        $reservations->renew_lease(
            'L82-DIRECT',
            $sourceuuid,
            0,
            1,
            $now + 10,
            600,
            'checkout'
        );
        $reservations->renew_lease(
            'L82-DIRECT',
            $sourceuuid,
            0,
            1,
            $now + 20,
            600,
            'payment'
        );

        $before = $reservationrepo->find(
            $offer['promotionid'],
            $offer['productid'],
            $sourceuuid
        );
        self::assertNotNull($before);
        $reservationid = $before->get_id();
        $expiresat = $before->get_expires_at();
        $checkoutstartedat = $before->get_checkout_started_at();
        $paymentstartedat = $before->get_payment_started_at();
        $timecreated = $before->get_time_created();

        $switched = $this->switcher($carts, $reservations)->switch(
            0,
            [
                'currency' => 'EUR',
                'sku' => 'L82-DIRECT',
                'priceid' => (int)$offer['eur']->get_id(),
                'quantity' => 1,
                'metadata' => $direct->get_cart()->get_items()[0]->get_metadata(),
                'cartuuid' => $sourceuuid,
            ],
            'RUB',
            'fr',
            $now + 30
        );

        self::assertTrue($switched->has_changed());
        $target = $switched->get_cart();
        self::assertSame('RUB', $target->get_currency());
        self::assertNotSame($sourceuuid, $target->get_uuid());
        self::assertCount(1, $target->get_items());
        self::assertSame(
            (int)$offer['rub']->get_id(),
            $target->get_items()[0]->get_price_id()
        );
        self::assertSame(
            'a1',
            $target->get_items()[0]->get_metadata()['showroom']
        );

        self::assertNull(
            $reservationrepo->find(
                $offer['promotionid'],
                $offer['productid'],
                $sourceuuid
            )
        );
        $after = $reservationrepo->find(
            $offer['promotionid'],
            $offer['productid'],
            $target->get_uuid()
        );
        self::assertNotNull($after);
        self::assertSame($reservationid, $after->get_id());
        self::assertSame(
            CommercePedagogicalSeatReservation::ACTIVE,
            $after->get_state()
        );
        self::assertSame($expiresat, $after->get_expires_at());
        self::assertSame(
            $checkoutstartedat,
            $after->get_checkout_started_at()
        );
        self::assertSame(
            $paymentstartedat,
            $after->get_payment_started_at()
        );
        self::assertSame($timecreated, $after->get_time_created());

        $normalafter = $carts->open(0, 'EUR');
        self::assertSame($normaluuid, $normalafter->get_uuid());
        self::assertCount(1, $normalafter->get_items());
        self::assertSame(
            'L82-NORMAL',
            $normalafter->get_items()[0]->get_product_sku()
        );
        self::assertSame(
            'keep-me',
            $normalafter->get_items()[0]->get_metadata()['normal_cart_marker']
        );
    }

    public function test_missing_target_price_keeps_original_hold_untouched(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $offer = $this->setup_offer('L82-NO-RUB', $now, false);

        $reservations =
            CommercePedagogicalSeatReservationService::create($DB);
        $reservationrepo =
            CommercePedagogicalSeatReservationRepository::create($DB);
        $carts = $this->carts($reservations);

        $direct = $carts->prepare_direct_product(
            0,
            'EUR',
            'fr',
            'L82-NO-RUB',
            (int)$offer['eur']->get_id(),
            1,
            [],
            $now
        );
        $sourceuuid = $direct->get_cart()->get_uuid();
        $before = $reservationrepo->find(
            $offer['promotionid'],
            $offer['productid'],
            $sourceuuid
        );
        self::assertNotNull($before);

        try {
            $this->switcher($carts, $reservations)->switch(
                0,
                [
                    'currency' => 'EUR',
                    'sku' => 'L82-NO-RUB',
                    'priceid' => (int)$offer['eur']->get_id(),
                    'quantity' => 1,
                    'metadata' => [],
                    'cartuuid' => $sourceuuid,
                ],
                'RUB',
                'fr',
                $now + 30
            );
            self::fail('Missing target price must reject the switch.');
        } catch (\moodle_exception $exception) {
            self::assertSame('invalidparameter', $exception->errorcode);
        }

        $after = $reservationrepo->find(
            $offer['promotionid'],
            $offer['productid'],
            $sourceuuid
        );
        self::assertNotNull($after);
        self::assertSame($before->get_id(), $after->get_id());
        self::assertSame(
            CommercePedagogicalSeatReservation::ACTIVE,
            $after->get_state()
        );
        self::assertSame(
            $before->get_expires_at(),
            $after->get_expires_at()
        );
    }

    public function test_guest_failed_payment_currency_switch_starts_fresh_target_purchase_context(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();

        $sessions = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $sessions->create(
            'EUR',
            time() + HOURSECS,
            [
                'resume_purchase_reference' => 'cmp_old',
                'payment_started_at' => time() - 20,
                'payment_failure_reason' => 'payment_failed',
                'payment_failed_at' => time() - 10,
            ]
        );
        $session = $sessions->transition(
            $session,
            'payment_failed',
            [
                'userid' => (int)$user->id,
                'purchasereference' => 'cmp_old',
                'paymentreference' => 'cmp_old',
            ]
        );

        $targetdirect = [
            'currency' => 'RUB',
            'sku' => 'L82-DIRECT',
            'priceid' => 42,
            'quantity' => 1,
            'metadata' => ['showroom' => 'a1'],
            'cartuuid' => str_repeat('b', 32),
            'storedat' => time(),
        ];

        $updated =
            CommerceGuestCheckoutService::create()
                ->switch_direct_purchase_currency(
                    $session,
                    'RUB',
                    $targetdirect
                );

        self::assertSame('RUB', $updated->get_currency());
        self::assertSame('provisional', $updated->get_status());
        self::assertNull($updated->get_purchase_reference());
        self::assertNull($updated->get_payment_reference());

        $metadata = $updated->get_metadata();
        self::assertSame(
            $targetdirect,
            $metadata['direct_purchase']
        );
        self::assertSame('EUR', $metadata['currency_switched_from']);
        self::assertArrayNotHasKey(
            'resume_purchase_reference',
            $metadata
        );
        self::assertArrayNotHasKey('payment_started_at', $metadata);
        self::assertArrayNotHasKey(
            'payment_failure_reason',
            $metadata
        );
        self::assertArrayNotHasKey('payment_failed_at', $metadata);
    }

    public function test_direct_purchase_session_is_visible_from_another_surface_currency(): void {
        global $SESSION;

        $this->resetAfterTest(true);
        $uuid = str_repeat('c', 32);

        CommerceDirectPurchaseSession::store(
            'EUR',
            'L82-SURFACE',
            17,
            1,
            ['origin' => 'storefront'],
            $uuid
        );

        self::assertNull(CommerceDirectPurchaseSession::current('RUB'));
        $current = CommerceDirectPurchaseSession::current_any_currency();
        self::assertNotNull($current);
        self::assertSame('EUR', $current['currency']);
        self::assertSame('L82-SURFACE', $current['sku']);
        self::assertSame($uuid, $current['cartuuid']);
        self::assertSame(
            $uuid,
            CommerceDirectPurchaseSession::cart_uuid_for_sku('l82-surface')
        );
        self::assertNull(
            CommerceDirectPurchaseSession::cart_uuid_for_sku('OTHER-SKU')
        );

        unset($SESSION->local_subscriptions_direct_purchase);
    }

    public function test_expired_source_hold_cannot_be_switched_as_if_it_were_owned(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $offer = $this->setup_offer('L82-EXPIRED', $now);

        $reservations =
            CommercePedagogicalSeatReservationService::create($DB);
        $carts = $this->carts($reservations);
        $direct = $carts->prepare_direct_product(
            0,
            'EUR',
            'fr',
            'L82-EXPIRED',
            (int)$offer['eur']->get_id(),
            1,
            [],
            $now
        );
        self::assertTrue($direct->has_changed());

        $sourceuuid = $direct->get_cart()->get_uuid();
        self::assertTrue(
            $reservations->has_active_hold(
                'L82-EXPIRED',
                $sourceuuid,
                $now + 1
            )
        );
        self::assertFalse(
            $reservations->has_active_hold(
                'L82-EXPIRED',
                $sourceuuid,
                $now + CommercePedagogicalSeatReservationService::DEFAULT_TTL + 1
            )
        );

        try {
            $this->switcher($carts, $reservations)->switch(
                0,
                [
                    'currency' => 'EUR',
                    'sku' => 'L82-EXPIRED',
                    'priceid' => (int)$offer['eur']->get_id(),
                    'quantity' => 1,
                    'metadata' => [],
                    'cartuuid' => $sourceuuid,
                ],
                'RUB',
                'fr',
                $now + CommercePedagogicalSeatReservationService::DEFAULT_TTL + 1
            );
            self::fail('An expired source hold must not be rebound.');
        } catch (\moodle_exception $exception) {
            self::assertSame(
                'commerce_cart_checkout_seat_expired',
                $exception->errorcode
            );
        }
    }

    public function test_storefront_and_showroom_expose_own_hold_context_for_capacity(): void {
        global $CFG;

        $storefront = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/presentation/CommerceStorefrontPresenter.php'
        );
        $showroom = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomPresenter.php'
        );
        $card = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache'
        );
        $panel = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache'
        );

        self::assertStringContainsString(
            'CommerceStorefrontPromotionJoinCartContextResolver::create()',
            $storefront
        );
        self::assertStringContainsString(
            '->excluded_cart_uuid(',
            $storefront
        );
        self::assertStringContainsString(
            'CommerceDirectPurchaseSession::cart_uuid_for_sku($offersku)',
            $showroom
        );
        self::assertStringContainsString(
            "empty(\$offer['pedagogicalbuyavailable'])",
            $showroom
        );
        self::assertStringContainsString('{{#pedagogicalavailable}}', $card);
        self::assertStringContainsString('{{#pedagogicalbuyavailable}}', $card);
        self::assertStringContainsString('{{#pedagogicalavailable}}', $panel);
        self::assertStringContainsString('{{#pedagogicalbuyavailable}}', $panel);
    }

    public function test_cart_action_direct_switch_never_materialises_the_normal_cart(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );
        $start = strpos(
            $source,
            "} else if (\$action === 'switchpurchasecurrency') {"
        );
        self::assertNotFalse($start);
        $end = strpos(
            $source,
            '} else {',
            $start
        );
        self::assertNotFalse($end);
        $block = substr($source, $start, $end - $start);

        self::assertStringContainsString(
            'CommerceDirectPurchaseCurrencySwitchService::create()->switch(',
            $block
        );
        self::assertStringContainsString(
            'CommerceDirectPurchaseSession::store(',
            $block
        );
        self::assertStringContainsString(
            'CommercePurchaseFlow::DIRECT',
            $block
        );
        self::assertStringContainsString(
            "'/local/subscriptions/commerce_checkout.php'",
            $block
        );
        self::assertStringNotContainsString('clear_cart(', $block);
        self::assertStringNotContainsString('add_product(', $block);
        self::assertStringNotContainsString(
            'CommerceCartCurrencySwitchService::create()->switch(',
            $block
        );

        self::assertStringContainsString(
            'CommerceDirectPurchaseSession::current_any_currency()',
            $source
        );
        self::assertStringContainsString(
            "\$cartcustomerresolver->resolve(\n                    \$existingdirect['currency']",
            $source
        );
        self::assertStringContainsString(
            'CommerceDirectPurchaseCurrencySwitchService::create()',
            $source
        );
        self::assertStringContainsString(
            '$syncguestdirectcurrency(',
            $source
        );
    }
}
