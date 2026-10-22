<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\catalog\CommerceCartProductQuote;
use local_subscriptions\commerce\cart\catalog\MoodleCommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\policy\CommerceQuantityPolicyResolver;
use local_subscriptions\commerce\cart\policy\CommerceSingleQuantityPolicy;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\cart\domain\CommerceCalculatedCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;
use local_subscriptions\commerce\cart\domain\CommerceCartTotals;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutContext;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPurchaseBuilder;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSummaryBuilder;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutValidator;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativeGrantExpectationPolicy;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePaidPurchaseCompleter;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePurchaseGrantPlanner;
use local_subscriptions\commerce\mail\CommerceMailAuditCopyPolicy;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\context\CommercePurchaseMailContextFactory;
use local_subscriptions\commerce\mail\service\CommerceTransactionalPurchaseMailService;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\pricing\CommerceProductPromotionService;
use local_subscriptions\commerce\producttype\CommerceProductTypeRegistry;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\CommercePromotionRepository;
use local_subscriptions\commerce\promotion\service\CommercePromotionEligibilityEvaluator;
use local_subscriptions\commerce\promotion\service\CommercePromotionEngine;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;
use local_subscriptions\payment\dto\InternalEvent;

/** 7.98B.1 — Donation is repeatable, non-discountable and legitimately grantless. */
final class commerce_798b1_donation_foundation_test extends \advanced_testcase {
    private const DONATION_SKU = 'DONATION.ALF.B1';
    private const SERVICE_SKU = 'SERVICE.B1.CONTROL';
    private const PURCHASE_UUID = '798b1000000043338444555555555555';
    private const PURCHASE_REFERENCE = 'cmp_798b1_donation';

    public function test_donation_product_type_declares_explicit_grantless_capabilities(): void {
        $registry = CommerceProductTypeRegistry::create_default();
        $donation = $registry->get(CommerceProductType::DONATION);

        self::assertTrue(CommerceProductType::is_known(CommerceProductType::DONATION));
        self::assertFalse($donation->get_capabilities()->is_composable());
        self::assertFalse($donation->get_capabilities()->is_expandable());
        self::assertTrue($donation->get_capabilities()->is_directly_purchasable());
        self::assertFalse($donation->get_capabilities()->supports_entitlements());
    }

    public function test_grant_planner_accepts_donation_but_still_rejects_entitlement_service_without_definition(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$donationprice, $serviceprice] = $this->create_catalogue_products();
        $purchase = (object)[
            'reference' => self::PURCHASE_REFERENCE,
            'userid' => null,
            'customeremail' => 'donation-b1@example.test',
        ];

        $donationitem = $this->planner_item(1, self::DONATION_SKU, (int)$donationprice->get_id());
        $plan = (new CommerceNativePurchaseGrantPlanner($DB))->plan($purchase, [$donationitem], 1700000000);
        self::assertSame([], $plan->get_grants());

        $policy = new CommerceNativeGrantExpectationPolicy($DB);
        self::assertTrue($policy->allows_empty_grant_plan([$donationitem]));

        $serviceitem = $this->planner_item(2, self::SERVICE_SKU, (int)$serviceprice->get_id());
        self::assertFalse($policy->allows_empty_grant_plan([$serviceitem]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No effective Native entitlement definition exists');
        (new CommerceNativePurchaseGrantPlanner($DB))->plan($purchase, [$serviceitem], 1700000000);
    }

    public function test_paid_donation_finishes_without_grant_fulfillment_or_access_mail_and_replay_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$donationprice] = $this->create_catalogue_products(false);
        $user = $this->getDataGenerator()->create_user([
            'email' => 'donation-b1@example.test',
            'firstname' => 'Donation',
            'lastname' => 'B1',
        ]);
        $purchaseid = $this->insert_purchase((int)$user->id, (int)$donationprice->get_id());

        $payments = new CommercePaymentRepository($DB);
        $attempt = $payments->create(self::PURCHASE_UUID, 'stripe', 2500, 'EUR');
        $attempt = $payments->update_status(
            (int)$attempt->get_id(),
            CommercePaymentAttemptStatus::PAID,
            'pi_798b1_donation',
            null,
            time()
        );

        $mail = new CommerceTransactionalPurchaseMailService(
            CommercePurchaseMailContextFactory::create(),
            CommerceMailRuntime::queue_service(),
            null,
            new CommerceMailAuditCopyPolicy()
        );
        $completer = new CommerceNativePaidPurchaseCompleter($DB, $payments, $mail);
        $event = new InternalEvent('checkout_completed', [
            'meta' => [
                'provider' => 'stripe',
                'commerce_payment_id' => (string)$attempt->get_id(),
                'commerce_purchase_uuid' => self::PURCHASE_UUID,
            ],
        ]);

        $completer->complete($event);
        self::assertSame(
            CommercePurchaseStatus::FULFILLED,
            $DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'status', ['id' => $purchaseid])
        );
        self::assertSame(0, $DB->count_records('local_subs_commerce_grant', [
            'purchasereference' => self::PURCHASE_REFERENCE,
        ]));
        self::assertSame(0, $DB->count_records(CommercePersistenceSchema::TABLE_FULFILLMENT, [
            'purchaseid' => $purchaseid,
        ]));
        self::assertSame(1, $DB->count_records('local_subs_commerce_mail', [
            'purchaseid' => $purchaseid,
            'mailtype' => CommerceMailType::PURCHASE_RECEIPT,
        ]));
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail', [
            'purchaseid' => $purchaseid,
            'mailtype' => CommerceMailType::PURCHASE_ACCESS,
        ]));

        $completer->complete($event);
        self::assertSame(1, $DB->count_records('local_subs_commerce_mail', [
            'purchaseid' => $purchaseid,
            'mailtype' => CommerceMailType::PURCHASE_RECEIPT,
        ]));
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail', [
            'purchaseid' => $purchaseid,
            'mailtype' => CommerceMailType::PURCHASE_ACCESS,
        ]));
    }

    public function test_donation_is_repeatable_even_when_a_prior_native_purchase_is_paid(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$donationprice] = $this->create_catalogue_products(false);
        $user = $this->getDataGenerator()->create_user();
        $purchaseid = $this->insert_purchase((int)$user->id, (int)$donationprice->get_id());
        $DB->set_field(
            CommercePersistenceSchema::TABLE_PURCHASE,
            'status',
            CommercePurchaseStatus::PAID,
            ['id' => $purchaseid]
        );

        $ownership = new CommerceStorefrontOwnershipResolver($DB);
        self::assertFalse($ownership->owns((int)$user->id, self::DONATION_SKU));
        self::assertSame('none', $ownership->resolve_source((int)$user->id, self::DONATION_SKU));
    }

    public function test_product_and_cart_promotions_never_reduce_donation_amount(): void {
        global $DB;
        $this->resetAfterTest(true);

        $metadata = (new CommerceProductPromotionService())->with_promotion(
            [],
            'EUR',
            1000,
            null,
            null
        );
        $catalogue = (new CommerceCatalogFactory($DB))->admin();
        $catalogue->save_product(new CommerceProduct(
            self::DONATION_SKU,
            CommerceProductType::DONATION,
            CommerceProductStatus::ACTIVE,
            'Donation B1',
            '',
            $metadata
        ));
        $price = $catalogue->set_price(new CommerceProductPrice(
            self::DONATION_SKU,
            CommerceMoney::from_minor(2500, 'EUR')
        ));

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $prices = new CommerceProductPriceRepository($DB, $hydrator, $products);
        $translations = new CommerceProductTranslationRepository($DB, $hydrator, $products);
        $gateway = new MoodleCommerceCartCatalogGateway(
            $products,
            $prices,
            $translations,
            new CommerceQuantityPolicyResolver()
        );
        $quote = $gateway->quote(self::DONATION_SKU, (int)$price->get_id(), 'EUR', 'fr');
        self::assertSame(2500, $quote->get_unit_price()->get_amount_minor());

        $cart = $this->promotion_cart_service();
        $cart->add_product(96, 'EUR', 'fr', self::DONATION_SKU, 10);
        $donationonly = $cart->snapshot(96, 'EUR', 'fr', 1000);
        self::assertSame(10000, $donationonly->get_totals()->get_subtotal()->get_amount_minor());
        self::assertSame(0, $donationonly->get_totals()->get_discount()->get_amount_minor());

        $cart->add_product(96, 'EUR', 'fr', 'COURSE.B1', 20);
        $mixed = $cart->snapshot(96, 'EUR', 'fr', 1000);
        self::assertSame(12500, $mixed->get_totals()->get_subtotal()->get_amount_minor());
        self::assertSame(500, $mixed->get_totals()->get_discount()->get_amount_minor());
        self::assertSame(12000, $mixed->get_totals()->get_total()->get_amount_minor());
    }

    public function test_checkout_keeps_donation_line_locked_and_persists_catalogue_type(): void {
        $this->resetAfterTest(true);

        $donationcartitem = new CommerceCartItem(self::DONATION_SKU, 10, 1);
        $coursecartitem = new CommerceCartItem('COURSE.B1', 20, 1);
        $cart = new CommerceCart(
            '798b1000000000000000000000000001',
            42,
            'EUR',
            [$donationcartitem, $coursecartitem],
            [],
            1,
            1
        );

        $donation = new CommerceCalculatedCartItem(
            $donationcartitem,
            'Donation B1',
            CommerceMoney::from_minor(10000, 'EUR'),
            CommerceMoney::from_minor(10000, 'EUR'),
            1,
            1,
            1,
            CommerceProductType::DONATION
        );
        $course = new CommerceCalculatedCartItem(
            $coursecartitem,
            'Course B1',
            CommerceMoney::from_minor(2500, 'EUR'),
            CommerceMoney::from_minor(2500, 'EUR'),
            1,
            1,
            1,
            CommerceProductType::COURSE_ACCESS
        );
        $snapshot = new CommerceCartSnapshot(
            $cart,
            [$donation, $course],
            new CommerceCartTotals(
                CommerceMoney::from_minor(12500, 'EUR'),
                CommerceMoney::from_minor(500, 'EUR'),
                CommerceMoney::zero('EUR'),
                CommerceMoney::from_minor(12000, 'EUR')
            ),
            1700000000
        );
        $summary = (new CommerceCheckoutSummaryBuilder(new CommerceCheckoutValidator()))->build(
            $snapshot,
            new CommerceCheckoutContext(42, 'EUR', 'fr', 'stripe', '/success', '/cancel', false),
            1700000000
        );

        self::assertTrue($summary->is_valid());
        $purchase = (new CommerceCheckoutPurchaseBuilder())->build(
            $summary,
            new CommerceCustomer(42, 'donation-checkout@example.test'),
            '798b1-donation-checkout'
        );
        $items = $purchase->get_items();

        self::assertSame(12000, $purchase->get_total_amount_minor());
        self::assertSame(10000, $items[0]->get_total_amount_minor());
        self::assertSame(2000, $items[1]->get_total_amount_minor());
        self::assertSame(CommerceItem::TYPE_SERVICE, $items[0]->get_item()->get_type());
        self::assertSame(
            CommerceProductType::DONATION,
            $items[0]->get_metadata_value('catalogue_type')
        );
    }

    /** @return array{0:CommerceProductPrice,1?:CommerceProductPrice} */
    private function create_catalogue_products(bool $withservice = true): array {
        global $DB;
        $catalogue = (new CommerceCatalogFactory($DB))->admin();
        $catalogue->save_product(new CommerceProduct(
            self::DONATION_SKU,
            CommerceProductType::DONATION,
            CommerceProductStatus::ACTIVE,
            'Donation B1'
        ));
        $donationprice = $catalogue->set_price(new CommerceProductPrice(
            self::DONATION_SKU,
            CommerceMoney::from_minor(2500, 'EUR')
        ));

        if (!$withservice) {
            return [$donationprice];
        }

        $catalogue->save_product(new CommerceProduct(
            self::SERVICE_SKU,
            CommerceProductType::SERVICE,
            CommerceProductStatus::ACTIVE,
            'Service control B1'
        ));
        $serviceprice = $catalogue->set_price(new CommerceProductPrice(
            self::SERVICE_SKU,
            CommerceMoney::from_minor(2500, 'EUR')
        ));

        return [$donationprice, $serviceprice];
    }

    private function planner_item(int $id, string $sku, int $priceid): \stdClass {
        return (object)[
            'id' => $id,
            'itemreference' => $sku,
            'quantity' => 1,
            'metadatajson' => json_encode(['priceid' => $priceid], JSON_THROW_ON_ERROR),
            'fulfillmentjson' => '{}',
        ];
    }

    private function insert_purchase(int $userid, int $priceid): int {
        global $DB;
        $now = time();
        $purchaseid = (int)$DB->insert_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            (object)[
                'purchaseuuid' => self::PURCHASE_UUID,
                'reference' => self::PURCHASE_REFERENCE,
                'type' => CommerceItem::TYPE_SERVICE,
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => $userid,
                'customeremail' => 'donation-b1@example.test',
                'status' => CommercePurchaseStatus::PAYMENT_PENDING,
                'currency' => 'EUR',
                'subtotalminor' => 2500,
                'discountminor' => 0,
                'totalminor' => 2500,
                'customerjson' => json_encode([
                    'firstname' => 'Donation',
                    'lastname' => 'B1',
                ], JSON_THROW_ON_ERROR),
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $DB->insert_record(CommercePersistenceSchema::TABLE_ITEM, (object)[
            'purchaseid' => $purchaseid,
            'position' => 0,
            'itemtype' => CommerceItem::TYPE_SERVICE,
            'itemreference' => self::DONATION_SKU,
            'label' => 'Donation B1',
            'quantity' => 1,
            'currency' => 'EUR',
            'unitminor' => 2500,
            'grossminor' => 2500,
            'discountminor' => 0,
            'netminor' => 2500,
            'pricingjson' => '{}',
            'fulfillmentjson' => json_encode(['priceid' => $priceid], JSON_THROW_ON_ERROR),
            'metadatajson' => json_encode([
                'priceid' => $priceid,
                'catalogue_type' => CommerceProductType::DONATION,
            ], JSON_THROW_ON_ERROR),
        ]);

        return $purchaseid;
    }

    private function promotion_cart_service(): CommerceCartService {
        $catalog = new class implements CommerceCartCatalogGateway {
            public function quote(
                string $productsku,
                int $priceid,
                string $currency,
                string $language,
                ?int $at = null
            ): CommerceCartProductQuote {
                $sku = strtoupper(trim($productsku));
                $isdonation = $sku === 'DONATION.ALF.B1';
                return new CommerceCartProductQuote(
                    $sku,
                    $priceid,
                    $sku,
                    CommerceMoney::from_minor($isdonation ? 10000 : 2500, $currency),
                    new CommerceSingleQuantityPolicy(),
                    $isdonation ? CommerceProductType::DONATION : CommerceProductType::COURSE_ACCESS
                );
            }
        };

        $promotion = new CommercePromotion(
            1,
            'Automatic 20%',
            null,
            CommercePromotion::TYPE_PERCENTAGE,
            2000,
            'EUR',
            0,
            null,
            null,
            true,
            true,
            false,
            10,
            null,
            null
        );
        $repository = new class($promotion) implements CommercePromotionRepository {
            public function __construct(private readonly CommercePromotion $promotion) {
            }

            public function get_by_code(string $code): ?CommercePromotion {
                return null;
            }

            public function find_automatic(int $at): array {
                return [$this->promotion];
            }

            public function save(CommercePromotion $promotion): CommercePromotion {
                return $promotion;
            }

            public function count_redemptions(int $promotionid, ?int $userid = null): int {
                return 0;
            }
        };
        $engine = new CommercePromotionEngine(
            $repository,
            new CommercePromotionEligibilityEvaluator($repository)
        );

        return new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($catalog, $engine),
            $catalog
        );
    }
}
