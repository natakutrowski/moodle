<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;

final class commerce_personal_offer_repository_test extends advanced_testcase {
    public function test_repository_round_trip_and_filters(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'TEST.PERSONAL.OFFER',
            'type' => 'digital',
            'status' => 'active',
            'name' => 'Target product',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $repository = new MoodleCommercePersonalOfferRepository($DB);
        $offer = new CommercePersonalOffer(
            null,
            bin2hex(random_bytes(16)),
            'trainer-launch',
            null,
            $productid,
            null,
            'buyer@example.com',
            CommercePersonalOffer::STATUS_ISSUED,
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000, 'RUB' => 299000]),
            $now,
            $now + 86400,
            ['source' => 'phpunit']
        );

        $saved = $repository->save($offer);
        $this->assertNotNull($saved->get_id());
        $this->assertSame('buyer@example.com', $saved->get_beneficiary_email());
        $this->assertSame(3000, $saved->get_terms()->get_amount_for_currency('EUR'));
        $this->assertSame(1, $repository->count(['email' => 'BUYER@example.com']));
        $this->assertCount(1, $repository->find(['campaignkey' => 'trainer-launch']));
        $this->assertSame($saved->get_id(), $repository->get_by_uuid($saved->get_offer_uuid())?->get_id());
    }
}
