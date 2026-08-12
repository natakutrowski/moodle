<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferDestinationResolver;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

final class commerce_personal_offer_campaign_email_m3h8_test extends \advanced_testcase {
    public function test_preissue_campaign_test_offer_can_resolve_published_showroom(): void {
        global $DB;

        $this->resetAfterTest(true);

        $productid = $this->create_product('M3H8.TARGET');
        $campaignid = $this->create_campaign($productid, 'm3h8-preissue', 'draft');
        $showroomid = $this->create_published_showroom('m3h8-showroom', 'M3H8.TARGET');

        $DB->insert_record('local_subs_commerce_offer_campaign_email_config', (object)[
            'campaignid' => $campaignid,
            'ctadestination' => CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM,
            'showroomid' => $showroomid,
            'timecreated' => time(),
            'timemodified' => time(),
            'usercreated' => null,
            'usermodified' => null,
        ]);

        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3h8-test-' . bin2hex(random_bytes(6)),
            $productid,
            'recipient@example.test',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            'm3h8-preissue',
            null,
            null,
            time() - 5,
            time() + HOURSECS,
            [
                'campaignemailtest' => true,
                'campaignemailtestcampaignid' => $campaignid,
            ]
        ));

        $destination = CommercePersonalOfferDestinationResolver::create($DB)->resolve($issued->get_offer());

        $this->assertSame(CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM, $destination['destination']);
        $this->assertSame($campaignid, $destination['campaignid']);
        $this->assertSame($showroomid, $destination['showroomid']);
        $this->assertSame('m3h8-showroom', $destination['showroomkey']);
    }

    public function test_real_offer_still_cannot_use_draft_campaign(): void {
        global $DB;

        $this->resetAfterTest(true);

        $productid = $this->create_product('M3H8.REAL');
        $this->create_campaign($productid, 'm3h8-real-draft', 'draft');

        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3h8-real-' . bin2hex(random_bytes(6)),
            $productid,
            'recipient@example.test',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            'm3h8-real-draft',
            null,
            null,
            time() - 5,
            time() + HOURSECS
        ));

        $this->expectException(\moodle_exception::class);
        CommercePersonalOfferDestinationResolver::create($DB)->resolve($issued->get_offer());
    }

    private function create_product(string $sku): int {
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
            'amountminor' => 5500,
            'provider' => null,
            'providerpriceid' => null,
            'active' => 1,
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return $id;
    }

    private function create_campaign(int $productid, string $key, string $status): int {
        global $DB;

        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_offer_campaign', (object)[
            'campaignkey' => $key,
            'name' => $key,
            'audiencetype' => 'list',
            'sourceproductsku' => null,
            'targetproductid' => $productid,
            'termsversion' => 1,
            'termsjson' => json_encode(
                CommercePersonalOfferTerms::fixed_price(['EUR' => 3000])->get_data(),
                JSON_THROW_ON_ERROR
            ),
            'criteriajson' => '{}',
            'validfrom' => null,
            'expiresat' => $now + DAYSECS,
            'validitymode' => 'fixed_datetime',
            'validityduration' => null,
            'validitytimezone' => 'Europe/Paris',
            'status' => $status,
            'timecreated' => $now,
            'timemodified' => $now,
            'usercreated' => null,
            'usermodified' => null,
        ]);
    }

    private function create_published_showroom(string $key, string $sku): int {
        global $DB;

        $now = time();
        $id = (int)$DB->insert_record('local_subs_showroom', (object)[
            'showroomkey' => $key,
            'status' => 'published',
            'name' => $key,
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => $key . '-fr',
            'slugen' => $key . '-en',
            'slugru' => $key . '-ru',
            'titlekey' => null,
            'descriptionkey' => null,
            'productsjson' => json_encode(['course' => $sku], JSON_THROW_ON_ERROR),
            'settingsjson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => null,
        ]);
        $DB->insert_record('local_subs_showroom_block', (object)[
            'showroomid' => $id,
            'blockkey' => 'hero',
            'blocktype' => 'hero',
            'sortorder' => 10,
            'enabled' => 1,
            'configjson' => json_encode(['title' => 'M3H8'], JSON_THROW_ON_ERROR),
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => null,
        ]);
        return $id;
    }
}
