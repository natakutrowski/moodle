<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferDestinationResolver;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferSessionService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferShoppingContextService;

final class commerce_personal_offer_campaign_email_m3f_test extends advanced_testcase {
    public function test_shopping_context_recalculates_personal_price_and_cart_metadata_from_signed_offer(): void {
        global $DB, $SESSION;
        $this->resetAfterTest(true);

        $productid = $this->create_product('M3F.TARGET', ['EUR' => 5500, 'RUB' => 549000]);
        $campaignid = $this->create_campaign($productid, 'm3f-target');
        $showroomid = $this->create_showroom('m3f-target', ['course' => 'M3F.TARGET']);
        $this->configure_showroom_destination($campaignid, $showroomid);

        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3f-target',
            $productid,
            'buyer@example.test',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000, 'RUB' => 299000]),
            'm3f-target', null, null, time() - 60, time() + DAYSECS
        ));
        $offer = $issued->get_offer();
        $token = $issued->get_token();
        $destination = CommercePersonalOfferDestinationResolver::create($DB)->resolve($offer);
        (new CommercePersonalOfferSessionService())->initialise(
            $token, $offer, 'M3F.TARGET', 'RUB', $destination
        );

        // Even if a client/session tries to invent a price, M3F never reads it.
        $SESSION->local_subscriptions_personal_offer_context['price'] = 1;
        $SESSION->local_subscriptions_personal_offer_context['amountminor'] = 1;

        $service = CommercePersonalOfferShoppingContextService::create($DB);
        $pricing = $service->resolve('M3F.TARGET', 'RUB', 'm3f-target');

        $this->assertNotNull($pricing);
        $this->assertSame(299000, $pricing['offeramountminor']);
        $this->assertSame(549000, $pricing['regularamountminor']);
        $this->assertSame('personaloffer', $pricing['metadata']['operation']);
        $this->assertSame($offer->get_offer_uuid(), $pricing['metadata']['personal_offer_uuid']);
        $currencies = $service->available_currencies('M3F.TARGET');
        $this->assertNotNull($currencies);
        sort($currencies);
        $this->assertSame(['EUR', 'RUB'], $currencies);
    }

    public function test_personal_context_applies_only_to_exact_product_showroom_and_supported_currency(): void {
        global $DB;
        $this->resetAfterTest(true);

        $productid = $this->create_product('M3F.EXACT', ['EUR' => 5500]);
        $campaignid = $this->create_campaign($productid, 'm3f-exact');
        $showroomid = $this->create_showroom('m3f-exact', ['bundle' => 'M3F.EXACT']);
        $this->configure_showroom_destination($campaignid, $showroomid);
        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3f-exact', $productid, 'buyer@example.test',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            'm3f-exact', null, null, time() - 60, time() + DAYSECS
        ));
        $offer = $issued->get_offer();
        (new CommercePersonalOfferSessionService())->initialise(
            $issued->get_token(),
            $offer, 'M3F.EXACT', 'EUR',
            CommercePersonalOfferDestinationResolver::create($DB)->resolve($offer)
        );

        $service = CommercePersonalOfferShoppingContextService::create($DB);
        // A valid context must resolve before any lifecycle-invalidating request.
        $this->assertNotNull($service->resolve('M3F.EXACT', 'EUR', 'm3f-exact'));

        // An unrelated SKU or Showroom does not consume/invalidate the Personal Offer context.
        $this->assertNull($service->resolve('OTHER.SKU', 'EUR', 'm3f-exact'));
        $this->assertNull($service->resolve('M3F.EXACT', 'EUR', 'another-showroom'));
        $this->assertNotNull($service->resolve('M3F.EXACT', 'EUR', 'm3f-exact'));

        // M3G deliberately invalidates a stale/unsupported commercial currency.
        $this->assertNull($service->resolve('M3F.EXACT', 'RUB', 'm3f-exact'));
    }

    public function test_public_shopping_routes_attach_offer_metadata_server_side_and_do_not_accept_offer_price(): void {
        $root = dirname(__DIR__, 3);
        $cart = (string)file_get_contents($root . '/cart_action.php');
        $showroom = (string)file_get_contents($root . '/showroom.php');
        $showroomajax = (string)file_get_contents($root . '/ajax/showroom_prices.php');
        $storefront = (string)file_get_contents($root . '/storefront_product.php');
        $resolver = (string)file_get_contents($root . '/classes/commerce/showroom/CommerceShowroomProductResolver.php');

        $this->assertStringContainsString('CommercePersonalOfferShoppingContextService', $cart);
        $this->assertStringContainsString("array_replace(\$metadata, \$personal['metadata'])", $cart);
        $this->assertStringNotContainsString("optional_param('offerprice'", $cart);
        $this->assertStringNotContainsString("optional_param('personal_offer_uuid'", $cart);
        $this->assertStringContainsString('available_currencies', $showroom);
        $this->assertStringContainsString('available_currencies', $showroomajax);
        $this->assertStringContainsString('Unsupported Personal Offer currency.', $showroomajax);
        $this->assertStringContainsString('available_currencies', $storefront);
        $this->assertStringContainsString('commerce_personal_offer_currency_unavailable', $cart);
        $this->assertStringContainsString('apply_personal_offer_pricing', $resolver);
        $this->assertStringContainsString("\$offer['priceformatted'] = \$personal['offerformatted'];", $resolver);
    }

    /** @param array<string,int> $prices */
    private function create_product(string $sku, array $prices): int {
        global $DB;
        $now = time();
        $id = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku, 'type' => 'digital', 'status' => 'active', 'name' => $sku,
            'description' => '', 'metadatajson' => '{}', 'availablefrom' => null,
            'availableuntil' => null, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        foreach ($prices as $currency => $amountminor) {
            $DB->insert_record('local_subs_commerce_prod_price', (object)[
                'productid' => $id, 'currency' => $currency, 'amountminor' => $amountminor,
                'provider' => null, 'providerpriceid' => null, 'active' => 1,
                'metadatajson' => '{}', 'timecreated' => $now, 'timemodified' => $now,
            ]);
        }
        return $id;
    }

    private function create_campaign(int $productid, string $key): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_offer_campaign', (object)[
            'campaignkey' => $key, 'name' => $key, 'audiencetype' => 'list',
            'sourceproductsku' => null, 'targetproductid' => $productid, 'termsversion' => 1,
            'termsjson' => json_encode(CommercePersonalOfferTerms::fixed_price(['EUR' => 3000])->get_data(), JSON_THROW_ON_ERROR),
            'criteriajson' => '{}', 'validfrom' => $now - 60, 'expiresat' => $now + DAYSECS,
            'status' => 'issued', 'timecreated' => $now, 'timemodified' => $now,
            'usercreated' => null, 'usermodified' => null,
        ]);
    }

    /** @param array<string,string> $products */
    private function create_showroom(string $key, array $products): int {
        global $DB;
        $now = time();
        $id = (int)$DB->insert_record('local_subs_showroom', (object)[
            'showroomkey' => $key, 'status' => 'published', 'name' => $key,
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => $key . '-fr', 'slugen' => $key . '-en', 'slugru' => $key . '-ru',
            'titlekey' => null, 'descriptionkey' => null,
            'productsjson' => json_encode($products, JSON_THROW_ON_ERROR),
            'settingsjson' => '{}', 'timecreated' => $now, 'timemodified' => $now, 'usermodified' => null,
        ]);
        $DB->insert_record('local_subs_showroom_block', (object)[
            'showroomid' => $id, 'blockkey' => 'hero', 'blocktype' => 'hero',
            'sortorder' => 10, 'enabled' => 1, 'configjson' => '{}',
            'timecreated' => $now, 'timemodified' => $now, 'usermodified' => null,
        ]);
        return $id;
    }

    private function configure_showroom_destination(int $campaignid, int $showroomid): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_offer_campaign_email_config', (object)[
            'campaignid' => $campaignid, 'ctadestination' => 'showroom', 'showroomid' => $showroomid,
            'timecreated' => $now, 'timemodified' => $now, 'usercreated' => null, 'usermodified' => null,
        ]);
    }
}
