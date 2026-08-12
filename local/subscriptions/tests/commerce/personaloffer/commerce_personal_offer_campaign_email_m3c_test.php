<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailBuilderService;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;

final class commerce_personal_offer_campaign_email_m3c_test extends advanced_testcase {
    public function test_builder_lists_only_published_enabled_showrooms_containing_target_product(): void {
        global $DB;
        $this->resetAfterTest(true);
        $productid = $this->create_product('M3C.TARGET');
        $otherid = $this->create_product('M3C.OTHER');
        $campaignid = $this->create_campaign($productid);

        $compatible = $this->create_showroom('compatible', 'published', ['course' => 'M3C.TARGET'], true);
        $this->create_showroom('draft', 'draft', ['course' => 'M3C.TARGET'], true);
        $this->create_showroom('wrong-product', 'published', ['course' => 'M3C.OTHER'], true);
        $this->create_showroom('empty-runtime', 'published', ['course' => 'M3C.TARGET'], false);

        $options = CommercePersonalOfferCampaignEmailBuilderService::create($DB)->compatible_showrooms($campaignid);
        $this->assertSame([$compatible], array_keys($options));
        $this->assertStringContainsString('/compatible-fr', $options[$compatible]);
        $this->assertGreaterThan(0, $otherid);
    }

    public function test_builder_saves_plain_text_languages_destination_and_exposes_safe_variables(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $productid = $this->create_product('M3C.SAVE');
        $campaignid = $this->create_campaign($productid, (int)$user->id);
        $showroomid = $this->create_showroom('save', 'published', ['bundle' => 'M3C.SAVE'], true);
        $builder = CommercePersonalOfferCampaignEmailBuilderService::create($DB);

        $builder->save($campaignid, CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM, $showroomid, [
            'fr' => [
                'subject' => 'Bonjour {{firstname}}',
                'body' => "Offre valable du {{offer_start}} au {{offer_end}}.\nPrix : {{offer_price}}.",
                'ctalabel' => 'Découvrir',
                'closing' => 'À bientôt ❤️',
            ],
            'en' => ['subject' => '', 'body' => '', 'ctalabel' => '', 'closing' => ''],
            'ru' => ['subject' => '', 'body' => '', 'ctalabel' => '', 'closing' => ''],
        ], (int)$user->id);

        $state = $builder->state($campaignid);
        $this->assertSame('showroom', (string)$state['config']->ctadestination);
        $this->assertSame($showroomid, (int)$state['config']->showroomid);
        $this->assertArrayHasKey('fr', $state['translations']);
        $this->assertArrayNotHasKey('en', $state['translations']);
        $this->assertSame((int)FORMAT_HTML, (int)$state['translations']['fr']->bodyformat);
        $this->assertContains('offer_start', $state['variables']);
        $this->assertContains('offer_price', $state['variables']);
    }

    public function test_builder_rejects_incompatible_showroom_without_partial_persistence(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $productid = $this->create_product('M3C.SECURE');
        $campaignid = $this->create_campaign($productid, (int)$user->id);
        $wrong = $this->create_showroom('wrong', 'published', ['course' => 'M3C.NOT-TARGET'], true);
        $builder = CommercePersonalOfferCampaignEmailBuilderService::create($DB);

        try {
            $builder->save($campaignid, CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM, $wrong, [
                'fr' => ['subject' => 'Sujet', 'body' => 'Corps', 'ctalabel' => 'CTA', 'closing' => ''],
            ], (int)$user->id);
            $this->fail('Expected incompatible showroom to be rejected.');
        } catch (\coding_exception $e) {
            $this->assertStringContainsString('not published and compatible', $e->getMessage());
        }

        $this->assertFalse($DB->record_exists('local_subs_commerce_offer_campaign_email_config', ['campaignid' => $campaignid]));
        $this->assertFalse($DB->record_exists('local_subs_commerce_offer_campaign_email_content', ['campaignid' => $campaignid]));
    }

    private function create_product(string $sku): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku, 'type' => 'digital', 'status' => 'active', 'name' => $sku,
            'description' => '', 'metadatajson' => '{}', 'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
    }

    private function create_campaign(int $productid, int $userid = 0): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_offer_campaign', (object)[
            'campaignkey' => 'm3c-' . $productid . '-' . random_int(1000, 9999), 'name' => 'M3C campaign',
            'audiencetype' => 'list', 'sourceproductsku' => null, 'targetproductid' => $productid,
            'termsversion' => 1, 'termsjson' => json_encode(CommercePersonalOfferTerms::fixed_price(['EUR' => 3000])->get_data(), JSON_THROW_ON_ERROR),
            'criteriajson' => '{}', 'validfrom' => null, 'expiresat' => null, 'status' => 'draft',
            'timecreated' => $now, 'timemodified' => $now, 'usercreated' => $userid ?: null, 'usermodified' => $userid ?: null,
        ]);
    }

    /** @param array<string,string> $products */
    private function create_showroom(string $key, string $status, array $products, bool $enabledblock): int {
        global $DB;
        $now = time();
        $id = (int)$DB->insert_record('local_subs_showroom', (object)[
            'showroomkey' => 'm3c-' . $key, 'status' => $status, 'name' => 'M3C ' . $key,
            'template' => 'local_subscriptions/showroom/third_group_verbs', 'slugfr' => $key . '-fr',
            'slugen' => $key . '-en', 'slugru' => $key . '-ru', 'titlekey' => null, 'descriptionkey' => null,
            'productsjson' => json_encode($products, JSON_THROW_ON_ERROR), 'settingsjson' => '{}',
            'timecreated' => $now, 'timemodified' => $now, 'usermodified' => null,
        ]);
        if ($enabledblock) {
            $DB->insert_record('local_subs_showroom_block', (object)[
                'showroomid' => $id, 'blockkey' => 'hero', 'blocktype' => 'hero', 'sortorder' => 10,
                'enabled' => 1, 'configjson' => '{}', 'timecreated' => $now, 'timemodified' => $now, 'usermodified' => null,
            ]);
        }
        return $id;
    }
}
