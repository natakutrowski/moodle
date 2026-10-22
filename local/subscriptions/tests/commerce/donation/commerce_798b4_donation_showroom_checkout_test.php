<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\catalog\CommerceCartProductQuote;
use local_subscriptions\commerce\cart\policy\CommerceSingleQuantityPolicy;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\donation\CommerceDonationAmountPolicy;
use local_subscriptions\commerce\donation\CommerceDonationCompanionCartService;
use local_subscriptions\commerce\domain\value\CommerceMoney;

/** 7.98B.4 — client Donation selector + course companion checkout contract. */
final class commerce_798b4_donation_showroom_checkout_test extends \advanced_testcase {
    public function test_course_and_donation_are_two_real_lines_with_quantity_one(): void {
        $this->resetAfterTest(true);
        $gateway = $this->gateway(false);
        $cart = $this->cart_service($gateway);
        $service = new CommerceDonationCompanionCartService($cart);

        $result = $service->add_pair(
            42,
            'EUR',
            'fr',
            'COURSE.B4',
            10,
            'DONATION.B4',
            20,
            25,
            ['showroom' => 'b4'],
            ['showroom_offer' => 'donation']
        );

        self::assertTrue($result->has_changed());
        self::assertCount(2, $result->get_cart()->get_items());
        $items = [];
        foreach ($result->get_cart()->get_items() as $item) {
            $items[$item->get_product_sku()] = $item;
        }
        self::assertSame(1, $items['COURSE.B4']->get_quantity());
        self::assertSame(1, $items['DONATION.B4']->get_quantity());
        self::assertSame(
            25,
            $items['DONATION.B4']->get_metadata()[CommerceDonationAmountPolicy::METADATA_UNITS]
        );

        $snapshot = (new CommerceCartCalculator($gateway))->calculate($result->get_cart(), 'fr');
        self::assertSame(5000, $snapshot->get_totals()->get_total()->get_amount_minor());
    }

    public function test_failed_donation_rolls_back_primary_line(): void {
        $this->resetAfterTest(true);
        set_config('commerce_donation_max_units', 10, 'local_subscriptions');
        $gateway = $this->gateway(false);
        $service = new CommerceDonationCompanionCartService($this->cart_service($gateway));

        $result = $service->add_pair(
            42,
            'EUR',
            'fr',
            'COURSE.B4',
            10,
            'DONATION.B4',
            20,
            25
        );

        self::assertFalse($result->has_changed());
        self::assertTrue($result->get_cart()->is_empty());
        self::assertSame('donation_amount_invalid', $result->get_messages()[0]->get_code());
    }

    public function test_showroom_template_and_js_expose_generic_amount_controls(): void {
        global $CFG;
        $this->resetAfterTest(true);
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        $resolver = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductResolver.php'
        );
        $action = file_get_contents($CFG->dirroot . '/local/subscriptions/cart_action.php');

        self::assertStringContainsString('data-showroom-donation', $template);
        self::assertStringContainsString('name="donationunits"', $template);
        self::assertStringContainsString('name="donationsku"', $template);
        self::assertStringContainsString('data-donation-companion-toggle', $template);
        self::assertStringContainsString('bindDonationControls()', $js);
        self::assertStringContainsString('formatDonationMinor', $js);
        self::assertStringContainsString('apply_donation_merchandising', $resolver);
        self::assertStringContainsString('CommerceProductType::DONATION', $resolver);
        self::assertStringContainsString("optional_param('donationsku'", $action);
        self::assertStringContainsString('CommerceDonationCompanionCartService', $action);
        self::assertStringContainsString('CommercePurchaseFlow::CART', $action);
    }

    private function cart_service(CommerceCartCatalogGateway $gateway): CommerceCartService {
        return new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($gateway),
            $gateway
        );
    }

    private function gateway(bool $missingdonation): CommerceCartCatalogGateway {
        return new class($missingdonation) implements CommerceCartCatalogGateway {
            public function __construct(private readonly bool $missingdonation) {
            }

            public function quote(
                string $productsku,
                int $priceid,
                string $currency,
                string $language,
                ?int $at = null
            ): CommerceCartProductQuote {
                $sku = strtoupper(trim($productsku));
                if ($sku === 'COURSE.B4') {
                    return new CommerceCartProductQuote(
                        $sku,
                        $priceid,
                        'Course B4',
                        CommerceMoney::from_minor(2500, 'EUR'),
                        new CommerceSingleQuantityPolicy(),
                        CommerceProductType::COURSE_ACCESS
                    );
                }
                if ($sku === 'DONATION.B4' && !$this->missingdonation) {
                    return new CommerceCartProductQuote(
                        $sku,
                        $priceid,
                        'Donation B4',
                        CommerceMoney::from_minor(100, 'EUR'),
                        new CommerceSingleQuantityPolicy(),
                        CommerceProductType::DONATION
                    );
                }
                throw new \coding_exception('Unknown B4 product.');
            }
        };
    }
}
