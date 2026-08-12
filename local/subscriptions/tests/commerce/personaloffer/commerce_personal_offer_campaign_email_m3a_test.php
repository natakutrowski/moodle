<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;

final class commerce_personal_offer_campaign_email_m3a_test extends advanced_testcase {
    private function create_campaign(string $key, string $status = CommercePersonalOfferCampaignManager::STATUS_DRAFT): array {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => strtoupper($key) . '.TARGET',
            'type' => 'digital',
            'status' => 'active',
            'name' => $key . ' target',
            'description' => null,
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $manager = CommercePersonalOfferCampaignManager::create($DB);
        $campaignid = $manager->create_campaign([
            'campaignkey' => $key,
            'name' => $key,
            'audiencetype' => CommercePersonalOfferCampaignManager::AUDIENCE_LIST,
            'targetproductid' => $productid,
            'termsversion' => 1,
            'terms' => CommercePersonalOfferTerms::fixed_price(['EUR' => 3000])->get_data(),
            'criteria' => ['list' => (string)$user->email],
        ], (int)$user->id);

        if ($status !== CommercePersonalOfferCampaignManager::STATUS_DRAFT) {
            $DB->set_field('local_subs_commerce_offer_campaign', 'status', $status, ['id' => $campaignid]);
        }

        return [$campaignid, (int)$user->id];
    }

    public function test_existing_campaign_without_m3a_rows_keeps_legacy_fallback_contract(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$campaignid] = $this->create_campaign('m3a-legacy');
        $service = CommercePersonalOfferCampaignEmailService::create($DB);

        $this->assertFalse($service->has_custom_email($campaignid));
        $this->assertNull($service->resolve_content($campaignid, 'ru'));
        $this->assertSame([
            'destination' => CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT,
            'showroomid' => null,
        ], $service->resolve_destination($campaignid));

        $stored = $service->get($campaignid);
        $this->assertNull($stored['config']);
        $this->assertSame([], $stored['translations']);
    }

    public function test_destination_is_single_campaign_configuration_and_showroom_is_server_validated(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$campaignid, $userid] = $this->create_campaign('m3a-destination');
        $service = CommercePersonalOfferCampaignEmailService::create($DB);

        $showroomid = (int)$DB->insert_record('local_subs_showroom', (object)[
            'showroomkey' => 'm3a-showroom',
            'status' => 'draft',
            'name' => 'M3A Showroom',
            'template' => 'default',
            'slugfr' => null,
            'slugen' => null,
            'slugru' => null,
            'titlekey' => null,
            'descriptionkey' => null,
            'productsjson' => '[]',
            'settingsjson' => '{}',
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => $userid,
        ]);

        $service->save_destination(
            $campaignid,
            CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM,
            $showroomid,
            $userid
        );
        $config = $service->get($campaignid)['config'];
        $this->assertSame('showroom', $config->ctadestination);
        $this->assertSame($showroomid, (int)$config->showroomid);

        // Updating to checkout clears any previously persisted showroom target.
        $service->save_destination(
            $campaignid,
            CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT,
            $showroomid,
            $userid
        );
        $config = $service->get($campaignid)['config'];
        $this->assertSame('checkout', $config->ctadestination);
        $this->assertNull($config->showroomid);

        $this->expectException(\coding_exception::class);
        $service->save_destination(
            $campaignid,
            CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM,
            999999,
            $userid
        );
    }

    public function test_localised_content_supports_exact_language_then_french_fallback(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$campaignid, $userid] = $this->create_campaign('m3a-language');
        $service = CommercePersonalOfferCampaignEmailService::create($DB);

        $service->save_content(
            campaignid: $campaignid,
            language: 'fr',
            subject: 'Offre française',
            body: '<p>Bonjour {{firstname}}</p>',
            bodyformat: (int) FORMAT_HTML,
            ctalabel: 'Découvrir',
            closing: '<p>À bientôt</p>',
            closingformat: (int) FORMAT_HTML,
            userid: $userid
        );
        $service->save_content(
            campaignid: $campaignid,
            language: 'ru_RU',
            subject: 'Русское предложение',
            body: '<p>Здравствуйте, {{firstname}}</p>',
            bodyformat: (int) FORMAT_HTML,
            ctalabel: 'Начать',
            closing: null,
            closingformat: (int) FORMAT_HTML,
            userid: $userid
        );

        $ru = $service->resolve_content($campaignid, 'ru');
        $this->assertSame('ru', $ru->language);
        $this->assertSame('Русское предложение', $ru->subject);

        $en = $service->resolve_content($campaignid, 'en-GB');
        $this->assertSame('fr', $en->language);
        $this->assertSame('Offre française', $en->subject);
    }

    public function test_html_is_cleaned_before_persistence_and_editorial_plain_fields_cannot_inject_html(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$campaignid, $userid] = $this->create_campaign('m3a-clean');
        $service = CommercePersonalOfferCampaignEmailService::create($DB);

        $service->save_content(
            campaignid: $campaignid,
            language: 'ru',
            subject: '<b>Тема</b>',
            body: '<p>Текст {{firstname}}</p><script>alert(1)</script>',
            bodyformat: (int) FORMAT_HTML,
            ctalabel: '<i>Купить</i>',
            closing: '<p>До встречи</p><iframe src="https://example.test"></iframe>',
            closingformat: (int) FORMAT_HTML,
            userid: $userid
        );

        $ru = $service->resolve_content($campaignid, 'ru');
        $this->assertSame('Тема', $ru->subject);
        $this->assertSame('Купить', $ru->ctalabel);
        $this->assertStringNotContainsString('<script', strtolower($ru->body));
        $this->assertStringNotContainsString('<iframe', strtolower((string)$ru->closing));
        $this->assertStringContainsString('{{firstname}}', $ru->body);
    }

    public function test_issued_campaign_email_configuration_is_immutable(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$campaignid, $userid] = $this->create_campaign(
            'm3a-issued',
            CommercePersonalOfferCampaignManager::STATUS_ISSUED
        );
        $service = CommercePersonalOfferCampaignEmailService::create($DB);

        $this->expectException(\coding_exception::class);
        $service->save_content(
            campaignid: $campaignid,
            language: 'fr',
            subject: 'Sujet',
            body: 'Corps',
            bodyformat: (int) FORMAT_PLAIN,
            ctalabel: 'Acheter',
            closing: null,
            closingformat: (int) FORMAT_PLAIN,
            userid: $userid
        );
    }
}
