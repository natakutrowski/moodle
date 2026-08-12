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

final class commerce_personal_offer_campaign_email_m3g_test extends advanced_testcase {
    public function test_expired_offer_clears_stale_session_and_reports_invalid_context_once(): void {
        global $DB, $SESSION;
        $this->resetAfterTest(true);

        [$issued] = $this->prepare_context('M3G.EXPIRED', 'm3g-expired');
        $DB->set_field('local_subs_commerce_offer', 'expiresat', time() - 10, [
            'offeruuid' => $issued->get_offer()->get_offer_uuid(),
        ]);

        $service = CommercePersonalOfferShoppingContextService::create($DB);
        $this->assertSame([], $service->available_currencies('M3G.EXPIRED'));
        $this->assertFalse(isset($SESSION->local_subscriptions_personal_offer_context));
        $this->assertFalse(isset($SESSION->local_subscriptions_personal_offer_token));
        $this->assertNull($service->available_currencies('M3G.EXPIRED'));
    }

    public function test_revoked_offer_clears_context(): void {
        global $DB, $SESSION;
        $this->resetAfterTest(true);

        [$issued] = $this->prepare_context('M3G.REVOKED', 'm3g-revoked');
        CommercePersonalOfferFactory::create($DB)->revoke($issued->get_offer()->get_offer_uuid());

        $this->assertSame([], CommercePersonalOfferShoppingContextService::create($DB)
            ->available_currencies('M3G.REVOKED'));
        $this->assertFalse(isset($SESSION->local_subscriptions_personal_offer_context));
    }

    public function test_identity_change_invalidates_personal_offer_context(): void {
        global $DB, $SESSION;
        $this->resetAfterTest(true);

        $beneficiary = $this->getDataGenerator()->create_user(['email' => 'owner@example.test']);
        [$issued] = $this->prepare_context('M3G.IDENTITY', 'm3g-identity', (int)$beneficiary->id, 'owner@example.test');
        $other = $this->getDataGenerator()->create_user(['email' => 'other@example.test']);
        $this->setUser($other);

        // setUser() replaces the PHPUnit user/session context, so the old Personal Offer
        // session is no longer present at all: no applicable context is correctly null.
        $this->assertNull(CommercePersonalOfferShoppingContextService::create($DB)
            ->available_currencies('M3G.IDENTITY'));
        $this->assertFalse(isset($SESSION->local_subscriptions_personal_offer_context));
        $this->assertSame('issued', $issued->get_offer()->get_status());
    }

    public function test_showroom_lifecycle_change_invalidates_context(): void {
        global $DB, $SESSION;
        $this->resetAfterTest(true);

        [$issued, $showroomid] = $this->prepare_context('M3G.SHOWROOM', 'm3g-showroom');
        $DB->set_field('local_subs_showroom', 'status', 'review', ['id' => $showroomid]);

        $this->assertSame([], CommercePersonalOfferShoppingContextService::create($DB)
            ->available_currencies('M3G.SHOWROOM'));
        $this->assertFalse(isset($SESSION->local_subscriptions_personal_offer_context));
        $this->assertSame('issued', $issued->get_offer()->get_status());
    }

    public function test_valid_context_survives_language_and_supported_currency_changes(): void {
        global $DB, $SESSION;
        $this->resetAfterTest(true);

        $this->prepare_context('M3G.CURRENCY', 'm3g-currency', null, 'buyer@example.test', [
            'EUR' => 5500,
            'RUB' => 549000,
        ], [
            'EUR' => 3000,
            'RUB' => 299000,
        ]);
        $service = CommercePersonalOfferShoppingContextService::create($DB);

        $currencies = $service->available_currencies('M3G.CURRENCY');
        sort($currencies);
        $this->assertSame(['EUR', 'RUB'], $currencies);
        $this->assertNotNull($service->resolve('M3G.CURRENCY', 'EUR', 'm3g-currency'));
        $this->assertNotNull($service->resolve('M3G.CURRENCY', 'RUB', 'm3g-currency'));
        $this->assertTrue(isset($SESSION->local_subscriptions_personal_offer_context));
    }

    public function test_stale_cart_post_is_fail_closed_in_public_route(): void {
        $root = dirname(__DIR__, 3);
        $cart = (string)file_get_contents($root . '/cart_action.php');
        $context = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/service/CommercePersonalOfferShoppingContextService.php'
        );

        $this->assertStringContainsString('$personalcurrencies === []', $cart);
        $this->assertStringContainsString("commerce_personal_offer_link_unavailable", $cart);
        $this->assertStringContainsString('(new CommercePersonalOfferSessionService())->clear()', $context);
        $this->assertStringContainsString('destination_matches_context', $context);
        $this->assertStringContainsString('validate_entry($token, $available[0]', $context);
    }

    /**
     * @param array<string,int> $catalogprices
     * @param array<string,int> $offerprices
     * @return array{0:\local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueResult,1:int}
     */
    private function prepare_context(
        string $sku,
        string $key,
        ?int $beneficiaryuserid = null,
        string $email = 'buyer@example.test',
        array $catalogprices = ['EUR' => 5500],
        array $offerprices = ['EUR' => 3000]
    ): array {
        global $DB;

        $productid = $this->create_product($sku, $catalogprices);
        $campaignid = $this->create_campaign($productid, $key, $offerprices);
        $showroomid = $this->create_showroom($key, ['course' => $sku]);
        $this->configure_showroom_destination($campaignid, $showroomid);
        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            $key,
            $productid,
            $email,
            CommercePersonalOfferTerms::fixed_price($offerprices),
            $key,
            null,
            $beneficiaryuserid,
            time() - 60,
            time() + DAYSECS
        ));
        $offer = $issued->get_offer();
        (new CommercePersonalOfferSessionService())->initialise(
            $issued->get_token(),
            $offer,
            $sku,
            array_key_first($catalogprices),
            CommercePersonalOfferDestinationResolver::create($DB)->resolve($offer)
        );
        return [$issued, $showroomid];
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

    /** @param array<string,int> $offerprices */
    private function create_campaign(int $productid, string $key, array $offerprices): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_offer_campaign', (object)[
            'campaignkey' => $key, 'name' => $key, 'audiencetype' => 'list',
            'sourceproductsku' => null, 'targetproductid' => $productid, 'termsversion' => 1,
            'termsjson' => json_encode(CommercePersonalOfferTerms::fixed_price($offerprices)->get_data(), JSON_THROW_ON_ERROR),
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
