<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferDestinationResolver;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferSessionService;
use local_subscriptions\commerce\personaloffer\security\CommercePersonalOfferTokenCodec;

final class commerce_personal_offer_campaign_email_m3e_test extends advanced_testcase {
    public function test_showroom_destination_is_resolved_only_from_server_side_campaign_configuration(): void {
        global $DB;
        $this->resetAfterTest(true);

        $productid = $this->create_product('M3E.TARGET');
        $campaignid = $this->create_campaign($productid, 'm3e-showroom');
        $showroomid = $this->create_showroom('m3e-showroom', 'published', ['bundle' => 'M3E.TARGET'], true);
        $this->configure_showroom_destination($campaignid, $showroomid);
        $offer = $this->offer($productid, 'm3e-showroom');

        $destination = CommercePersonalOfferDestinationResolver::create($DB)->resolve($offer);

        $this->assertSame('showroom', $destination['destination']);
        $this->assertSame($campaignid, $destination['campaignid']);
        $this->assertSame($showroomid, $destination['showroomid']);
        $this->assertSame('m3e-showroom', $destination['showroomkey']);
        $this->assertNotNull($destination['definition']);
        $this->assertSame('m3e-showroom', $destination['definition']->get_key());
    }

    public function test_legacy_or_unconfigured_offer_keeps_direct_checkout_destination(): void {
        global $DB;
        $this->resetAfterTest(true);

        $productid = $this->create_product('M3E.LEGACY');
        $legacy = $this->offer($productid, 'legacy-key-not-a-campaign');
        $destination = CommercePersonalOfferDestinationResolver::create($DB)->resolve($legacy);
        $this->assertSame('checkout', $destination['destination']);
        $this->assertNull($destination['campaignid']);
        $this->assertNull($destination['showroomid']);

        $campaignid = $this->create_campaign($productid, 'm3e-checkout');
        $configured = $this->offer($productid, 'm3e-checkout');
        $destination = CommercePersonalOfferDestinationResolver::create($DB)->resolve($configured);
        $this->assertSame('checkout', $destination['destination']);
        $this->assertSame($campaignid, $destination['campaignid']);
        $this->assertNull($destination['showroomid']);
    }

    public function test_runtime_rejects_showroom_that_is_no_longer_published_or_product_compatible(): void {
        global $DB;
        $this->resetAfterTest(true);

        $productid = $this->create_product('M3E.SECURE');
        $campaignid = $this->create_campaign($productid, 'm3e-secure');
        $showroomid = $this->create_showroom('m3e-secure', 'published', ['course' => 'M3E.SECURE'], true);
        $this->configure_showroom_destination($campaignid, $showroomid);
        $offer = $this->offer($productid, 'm3e-secure');

        $DB->set_field('local_subs_showroom', 'status', 'review', ['id' => $showroomid]);
        try {
            CommercePersonalOfferDestinationResolver::create($DB)->resolve($offer);
            $this->fail('A non-published showroom destination must be rejected at click time.');
        } catch (\moodle_exception $e) {
            $this->assertContains($e->errorcode, ['commerce_showroom_not_found', 'commerce_personal_offer_link_unavailable']);
        }

        $DB->set_field('local_subs_showroom', 'status', 'published', ['id' => $showroomid]);
        $DB->set_field('local_subs_showroom', 'productsjson', json_encode(['course' => 'ANOTHER.SKU']), ['id' => $showroomid]);
        $this->expectException(\moodle_exception::class);
        CommercePersonalOfferDestinationResolver::create($DB)->resolve($offer);
    }

    public function test_session_context_contains_identifiers_but_never_a_client_authoritative_price(): void {
        global $SESSION;
        $this->resetAfterTest(true);

        $productid = $this->create_product('M3E.SESSION');
        $offer = $this->offer($productid, 'm3e-session');
        $token = CommercePersonalOfferTokenCodec::build($offer->get_offer_uuid());

        (new CommercePersonalOfferSessionService())->initialise($token, $offer, 'M3E.SESSION', 'RUB', [
            'destination' => 'showroom',
            'campaignid' => 42,
            'showroomid' => 12,
            'showroomkey' => 'verbs',
        ]);

        $context = (array)$SESSION->local_subscriptions_personal_offer_context;
        $this->assertSame($offer->get_offer_uuid(), $context['offeruuid']);
        $this->assertSame('M3E.SESSION', $context['sku']);
        $this->assertSame('RUB', $context['currency']);
        $this->assertSame(42, $context['campaignid']);
        $this->assertSame(12, $context['showroomid']);
        $this->assertArrayNotHasKey('price', $context);
        $this->assertArrayNotHasKey('amountminor', $context);
        $this->assertArrayNotHasKey('offerprice', $context);
        $this->assertSame($token, $SESSION->local_subscriptions_personal_offer_token);
    }

    public function test_validate_entry_checks_signed_offer_currency_without_preparing_checkout_cart(): void {
        global $DB, $SESSION;
        $this->resetAfterTest(true);
        unset($SESSION->local_subscriptions_commerce_carts);

        $productid = $this->create_product('M3E.VALIDATE', 5500);
        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3e-validate',
            $productid,
            'buyer@example.test',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            null,
            null,
            null,
            time() - 60,
            time() + DAYSECS
        ));
        $offer = $issued->get_offer();
        $token = CommercePersonalOfferTokenCodec::build($offer->get_offer_uuid());

        $validated = CommercePersonalOfferCheckoutService::create($DB)->validate_entry(
            $token,
            'EUR',
            null,
            null
        );
        $this->assertSame($offer->get_offer_uuid(), $validated['offer']->get_offer_uuid());
        $this->assertSame('M3E.VALIDATE', $validated['sku']);
        $this->assertSame('EUR', $validated['currency']);

        // M3E deliberately avoids cart mutation until a direct-checkout branch is selected.
        $this->assertFalse(isset($SESSION->local_subscriptions_commerce_carts));
    }

    public function test_public_entry_route_resolves_destination_after_validation_and_never_accepts_a_price_parameter(): void {
        $root = dirname(__DIR__, 3);
        $entry = (string)file_get_contents($root . '/offer.php');

        $this->assertStringContainsString('validate_entry($token, $currency', $entry);
        $this->assertStringContainsString('CommercePersonalOfferDestinationResolver::create()', $entry);
        $this->assertStringContainsString('CommercePersonalOfferSessionService', $entry);
        $this->assertStringContainsString('CommerceShowroomUrl::make(', $entry);
        $this->assertStringContainsString("['currency' => \$currency]", $entry);
        $this->assertStringNotContainsString("optional_param('price'", $entry);
        $this->assertStringNotContainsString("required_param('price'", $entry);
    }

    private function create_product(string $sku, int $eurminor = 3900): int {
        global $DB;
        $now = time();
        $id = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku,
            'type' => 'digital',
            'status' => 'active',
            'name' => $sku,
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_prod_price', (object)[
            'productid' => $id,
            'currency' => 'EUR',
            'amountminor' => $eurminor,
            'provider' => null,
            'providerpriceid' => null,
            'active' => 1,
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return $id;
    }

    private function create_campaign(int $productid, string $campaignkey): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_offer_campaign', (object)[
            'campaignkey' => $campaignkey,
            'name' => $campaignkey,
            'audiencetype' => 'list',
            'sourceproductsku' => null,
            'targetproductid' => $productid,
            'termsversion' => 1,
            'termsjson' => json_encode(CommercePersonalOfferTerms::fixed_price(['EUR' => 3000])->get_data(), JSON_THROW_ON_ERROR),
            'criteriajson' => '{}',
            'validfrom' => $now - 60,
            'expiresat' => $now + DAYSECS,
            'status' => 'issued',
            'timecreated' => $now,
            'timemodified' => $now,
            'usercreated' => null,
            'usermodified' => null,
        ]);
    }

    /** @param array<string,string> $products */
    private function create_showroom(string $key, string $status, array $products, bool $enabledblock): int {
        global $DB;
        $now = time();
        $id = (int)$DB->insert_record('local_subs_showroom', (object)[
            'showroomkey' => $key,
            'status' => $status,
            'name' => $key,
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => $key . '-fr',
            'slugen' => $key . '-en',
            'slugru' => $key . '-ru',
            'titlekey' => null,
            'descriptionkey' => null,
            'productsjson' => json_encode($products, JSON_THROW_ON_ERROR),
            'settingsjson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => null,
        ]);
        if ($enabledblock) {
            $DB->insert_record('local_subs_showroom_block', (object)[
                'showroomid' => $id,
                'blockkey' => 'hero',
                'blocktype' => 'hero',
                'sortorder' => 10,
                'enabled' => 1,
                'configjson' => '{}',
                'timecreated' => $now,
                'timemodified' => $now,
                'usermodified' => null,
            ]);
        }
        return $id;
    }

    private function configure_showroom_destination(int $campaignid, int $showroomid): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_offer_campaign_email_config', (object)[
            'campaignid' => $campaignid,
            'ctadestination' => 'showroom',
            'showroomid' => $showroomid,
            'timecreated' => $now,
            'timemodified' => $now,
            'usercreated' => null,
            'usermodified' => null,
        ]);
    }

    private function offer(int $productid, ?string $campaignkey): CommercePersonalOffer {
        return new CommercePersonalOffer(
            1,
            bin2hex(random_bytes(16)),
            $campaignkey,
            null,
            $productid,
            null,
            'buyer@example.test',
            CommercePersonalOffer::STATUS_ISSUED,
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            time() - 60,
            time() + DAYSECS
        );
    }
}
