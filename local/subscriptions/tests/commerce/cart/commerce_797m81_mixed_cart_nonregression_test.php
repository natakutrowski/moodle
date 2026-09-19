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
use local_subscriptions\commerce\domain\value\CommerceMoney;

/** Non-regression for carts mixing independent Commerce product families. */
final class commerce_797m81_mixed_cart_nonregression_test extends advanced_testcase {
    public function test_course_digital_and_bundle_can_coexist_in_one_cart(): void {
        $service = $this->service();
        $customerid = 96;

        self::assertTrue($service->add_product($customerid, 'EUR', 'fr', 'COURSE-M81', 101)->has_changed());
        self::assertTrue($service->add_product($customerid, 'EUR', 'fr', 'DIGITAL-M81', 102)->has_changed());
        self::assertTrue($service->add_product($customerid, 'EUR', 'fr', 'BUNDLE-M81', 103)->has_changed());

        $snapshot = $service->snapshot($customerid, 'EUR', 'fr', 1789750000);
        self::assertCount(3, $snapshot->get_items());
        self::assertSame(45990, $snapshot->get_totals()->get_subtotal()->get_amount_minor());
        self::assertSame(45990, $snapshot->get_totals()->get_total()->get_amount_minor());
        self::assertSame(0, $snapshot->get_totals()->get_discount()->get_amount_minor());

        $types = [];
        foreach ($snapshot->get_items() as $item) {
            $types[$item->get_item()->get_product_sku()] = $item->get_product_type();
        }
        self::assertSame('course', $types['COURSE-M81'] ?? null);
        self::assertSame('digital', $types['DIGITAL-M81'] ?? null);
        self::assertSame('bundle', $types['BUNDLE-M81'] ?? null);
    }

    public function test_removing_one_product_family_does_not_mutate_the_other_lines(): void {
        $service = $this->service();
        $customerid = 97;

        $service->add_product($customerid, 'EUR', 'fr', 'COURSE-M81', 101);
        $service->add_product($customerid, 'EUR', 'fr', 'DIGITAL-M81', 102);
        $service->add_product($customerid, 'EUR', 'fr', 'BUNDLE-M81', 103);

        $removed = $service->remove_product($customerid, 'EUR', 'DIGITAL-M81', 102);
        self::assertTrue($removed->has_changed());

        $snapshot = $service->snapshot($customerid, 'EUR', 'fr', 1789750000);
        self::assertCount(2, $snapshot->get_items());
        self::assertSame(45000, $snapshot->get_totals()->get_total()->get_amount_minor());

        $skus = array_map(
            static fn($item): string => $item->get_item()->get_product_sku(),
            $snapshot->get_items()
        );
        sort($skus);
        self::assertSame(['BUNDLE-M81', 'COURSE-M81'], $skus);
    }

    public function test_single_quantity_duplicate_is_rejected_without_corrupting_mixed_cart(): void {
        $service = $this->service();
        $customerid = 98;

        $service->add_product($customerid, 'EUR', 'fr', 'COURSE-M81', 101);
        $service->add_product($customerid, 'EUR', 'fr', 'DIGITAL-M81', 102);

        $duplicate = $service->add_product($customerid, 'EUR', 'fr', 'DIGITAL-M81', 102);
        self::assertFalse($duplicate->has_changed());
        self::assertSame('already_in_cart', $duplicate->get_messages()[0]->get_code());

        $snapshot = $service->snapshot($customerid, 'EUR', 'fr', 1789750000);
        self::assertCount(2, $snapshot->get_items());
        self::assertSame(20990, $snapshot->get_totals()->get_total()->get_amount_minor());
    }

    private function service(): CommerceCartService {
        $catalog = new class implements CommerceCartCatalogGateway {
            public function quote(
                string $productsku,
                int $priceid,
                string $currency,
                string $language,
                ?int $at = null
            ): CommerceCartProductQuote {
                $sku = strtoupper(trim($productsku));
                [$amount, $type] = match ($sku) {
                    'COURSE-M81' => [20000, 'course'],
                    'DIGITAL-M81' => [990, 'digital'],
                    'BUNDLE-M81' => [25000, 'bundle'],
                    default => throw new \coding_exception('Unknown M8.1 fixture product ' . $sku),
                };

                return new CommerceCartProductQuote(
                    $sku,
                    $priceid,
                    $sku,
                    CommerceMoney::from_minor($amount, $currency),
                    new CommerceSingleQuantityPolicy(),
                    $type
                );
            }
        };

        return new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($catalog),
            $catalog
        );
    }
}
