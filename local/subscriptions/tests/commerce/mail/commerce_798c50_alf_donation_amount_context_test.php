<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\mail;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\donation\CommerceDonationAmountPolicy;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\context\CommercePurchaseMailContextFactory;
use local_subscriptions\commerce\mail\presentation\CommerceMailPurchasePresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderItemPresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;

/** 7.98C5.0 — donation amount comes from persisted order lines, not the order total. */
final class commerce_798c50_alf_donation_amount_context_test extends \advanced_testcase {

    public function test_mixed_order_exposes_only_the_donation_amount(): void {
        $this->resetAfterTest(true);

        $order = $this->synthetic_order([
            new CommerceOrderItemPresentation(
                'COURSE_ACCESS.ARTICLES',
                'course_access',
                'Mini-cours Articles',
                1,
                'EUR',
                2500,
                2500,
                0,
                2500,
                [],
                ['sku' => 'COURSE_ACCESS.ARTICLES']
            ),
            new CommerceOrderItemPresentation(
                'DONATION.ALF',
                'service',
                'Soutien Alf',
                1,
                'EUR',
                1000,
                1000,
                0,
                1000,
                [],
                [
                    'sku' => 'DONATION.ALF',
                    'catalogue_type' => 'donation',
                    CommerceDonationAmountPolicy::METADATA_UNITS => 10,
                ]
            ),
        ], 3500);

        $raw = CommercePurchaseMailContextFactory::create()->context_from_order(
            $order,
            'Nata CampusFR',
            'fr'
        );

        self::assertSame('35,00 EUR', str_replace("\u{00A0}", ' ', $raw['purchase']['totalformatted']));
        self::assertSame('10,00 €', str_replace("\u{00A0}", ' ', $raw['purchase']['donationformatted']));

        $presentation = CommerceMailPurchasePresentation::from_context(
            new CommerceMailContext($raw)
        )->export();
        self::assertTrue($presentation['hasalfcourseanddonation']);
        self::assertSame('10,00 €', str_replace("\u{00A0}", ' ', $presentation['donationformatted']));
    }

    public function test_donation_only_exposes_its_full_paid_amount(): void {
        $this->resetAfterTest(true);

        $order = $this->synthetic_order([
            new CommerceOrderItemPresentation(
                'DONATION.ALF',
                'service',
                'Soutien Alf',
                1,
                'EUR',
                1700,
                1700,
                0,
                1700,
                [],
                [
                    'sku' => 'DONATION.ALF',
                    'catalogue_type' => 'donation',
                    CommerceDonationAmountPolicy::METADATA_UNITS => 17,
                ]
            ),
        ], 1700);

        $raw = CommercePurchaseMailContextFactory::create()->context_from_order(
            $order,
            'Nata CampusFR',
            'fr'
        );

        self::assertSame('17,00 €', str_replace("\u{00A0}", ' ', $raw['purchase']['donationformatted']));
        $presentation = CommerceMailPurchasePresentation::from_context(
            new CommerceMailContext($raw)
        )->export();
        self::assertTrue($presentation['hasalfdonationonly']);
        self::assertSame('17,00 €', str_replace("\u{00A0}", ' ', $presentation['donationformatted']));
    }

    /** @param CommerceOrderItemPresentation[] $items */
    private function synthetic_order(array $items, int $totalminor): CommerceOrderPresentation {
        $now = 1790017200;
        return new CommerceOrderPresentation(
            7985001,
            '798c5000000043338444555555555555',
            'cmp_798c50_alf',
            'course_access',
            null,
            'nata@example.test',
            'EUR',
            $totalminor,
            'completed',
            'paid',
            'fulfilled',
            'stripe',
            $now,
            $now - 60,
            $items,
            [],
            [],
            [],
            null,
            ['metadata' => ['legal_entity_snapshot' => $this->seller_snapshot($now)]],
            $this->customer_snapshot()
        );
    }

    /** @return array<string,mixed> */
    private function seller_snapshot(int $now): array {
        return [
            'version' => 1,
            'legal_entity_key' => 'fr_main',
            'registered_country' => 'FR',
            'name' => 'CampusFR France',
            'address' => 'Snapshot address',
            'legal' => 'Snapshot legal',
            'registration' => 'REG-C50',
            'tax_identifier' => '',
            'email' => 'legal@example.test',
            'phone' => '',
            'website' => '',
            'tax_notice' => '',
            'footer' => '',
            'market_country' => 'FR',
            'resolution_rule' => 'market_row',
            'resolved_at' => $now,
            'currency' => 'EUR',
            'provider' => 'stripe',
        ];
    }

    /** @return array<string,mixed> */
    private function customer_snapshot(): array {
        return [
            'userid' => null,
            'email' => 'nata@example.test',
            'firstname' => 'Nata',
            'lastname' => 'CampusFR',
            'fullname' => 'Nata CampusFR',
            'country' => 'FR',
            'language' => 'fr',
            'metadata' => [],
        ];
    }
}
