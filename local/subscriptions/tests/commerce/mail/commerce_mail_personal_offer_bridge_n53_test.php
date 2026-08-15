<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferMailStudioBridge;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;

final class commerce_mail_personal_offer_bridge_n53_test extends advanced_testcase {
    public function test_mail_studio_template_is_applied_as_frozen_campaign_snapshot(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$campaignid, $userid] = $this->create_campaign('n53-snapshot');

        $library = new CommerceMailLibraryRepository($DB);
        $template = $library->save([
            'name' => 'Personal Offer reusable',
            'category' => CommerceMailLibrary::CATEGORY_PERSONAL_OFFER,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'metadata' => ['editor' => 'mail_builder'],
        ], [
            'fr' => [
                'subject' => 'Offre FR',
                'preheader' => '',
                'bodyhtml' => '<p>Version A {{firstname}}</p>'
                    . '{{cta|campus_pink}}Profiter de mon offre{{/cta}}',
            ],
            'ru' => [
                'subject' => 'Предложение RU',
                'preheader' => '',
                'bodyhtml' => '<p>Версия A {{firstname}}</p>'
                    . '{{cta|gold}}Открыть предложение{{/cta}}',
            ],
        ], $userid);

        $bridge = CommercePersonalOfferMailStudioBridge::create($DB);
        $bridge->apply_template($campaignid, (int)$template->id, $userid);

        $service = CommercePersonalOfferCampaignEmailService::create($DB);
        $fr = $service->resolve_content($campaignid, 'fr');
        self::assertNotNull($fr);
        self::assertSame('Offre FR', (string)$fr->subject);
        self::assertStringContainsString('Version A', (string)$fr->body);
        self::assertSame((int)$template->id, $service->library_template_source_id($campaignid));

        // Editing the reusable library template afterwards must NOT alter the
        // already prepared campaign.
        $library->save([
            'name' => 'Personal Offer reusable',
            'category' => CommerceMailLibrary::CATEGORY_PERSONAL_OFFER,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'metadata' => ['editor' => 'mail_builder'],
        ], [
            'fr' => [
                'subject' => 'Offre FR modifiée',
                'preheader' => '',
                'bodyhtml' => '<p>Version B</p>',
            ],
        ], $userid, (int)$template->id);

        $frozen = $service->resolve_content($campaignid, 'fr');
        self::assertSame('Offre FR', (string)$frozen->subject);
        self::assertStringContainsString('Version A', (string)$frozen->body);
        self::assertStringNotContainsString('Version B', (string)$frozen->body);
    }

    public function test_campaign_content_can_be_saved_back_as_reusable_draft_template(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$campaignid, $userid] = $this->create_campaign('n53-save');

        $service = CommercePersonalOfferCampaignEmailService::create($DB);
        $service->save_content(
            $campaignid,
            'fr',
            'Campagne à réutiliser',
            '<p>Bonjour {{firstname}}</p>{{offer}}',
            (int)FORMAT_HTML,
            'Voir mon offre',
            null,
            null,
            null,
            (int)FORMAT_HTML,
            $userid
        );

        $template = CommercePersonalOfferMailStudioBridge::create($DB)
            ->save_campaign_as_template(
                $campaignid,
                'Modèle depuis campagne',
                $userid
            );

        self::assertSame(
            CommerceMailLibrary::CATEGORY_PERSONAL_OFFER,
            (string)$template->category
        );
        self::assertSame(
            CommerceMailLibrary::STATUS_DRAFT,
            (string)$template->status
        );

        $contents = (new CommerceMailLibraryRepository($DB))
            ->contents((int)$template->id);
        self::assertSame(
            'Campagne à réutiliser',
            (string)$contents['fr']->subject
        );
        $document = json_decode((string)$contents['fr']->contentjson, true);
        self::assertSame('mail_builder', $document['mode']);
        self::assertStringContainsString('{{offer}}', $document['bodyhtml']);
    }

    public function test_n53_ui_schema_and_compatibility_contract(): void {
        $root = dirname(__DIR__, 3);
        $editor = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_email.php'
        );
        $action = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_email_template_action.php'
        );
        $libraryeditor = file_get_contents(
            $root . '/admin/commerce/mail/templates/library_edit.php'
        );
        $install = file_get_contents($root . '/db/install.xml');
        $upgrade = file_get_contents($root . '/db/upgrade.php');

        self::assertIsString($editor);
        self::assertIsString($action);
        self::assertIsString($libraryeditor);
        self::assertIsString($install);
        self::assertIsString($upgrade);

        self::assertStringContainsString('mailstudiotemplates', $editor);
        self::assertStringContainsString('applytemplate', $action);
        self::assertStringContainsString('savetemplate', $action);
        self::assertStringContainsString(
            'CommerceMailBuilder::personal_offer_structural_tags()',
            $libraryeditor
        );
        self::assertStringContainsString('librarytemplateid', $install);
        self::assertStringContainsString('2026081502', $upgrade);

        // N5.3 retains the proven campaign renderer/storage path. Mail Studio is
        // a reusable source and snapshot layer, not a mutable runtime dependency.
        $renderer = file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/'
                . 'CommercePersonalOfferCampaignMailRenderer.php'
        );
        self::assertStringContainsString(
            'CommercePersonalOfferCampaignEmailService::create',
            $renderer
        );
        self::assertStringNotContainsString(
            'CommerceMailLibraryRepository',
            $renderer
        );
    }

    private function create_campaign(string $key): array {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $now = time();
        $productid = (int)$DB->insert_record(
            'local_subs_commerce_product',
            (object)[
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
            ]
        );

        $manager = CommercePersonalOfferCampaignManager::create($DB);
        $campaignid = $manager->create_campaign([
            'campaignkey' => $key,
            'name' => $key,
            'audiencetype' => CommercePersonalOfferCampaignManager::AUDIENCE_LIST,
            'targetproductid' => $productid,
            'termsversion' => 1,
            'terms' => CommercePersonalOfferTerms::fixed_price(
                ['EUR' => 3000]
            )->get_data(),
            'criteria' => ['list' => (string)$user->email],
        ], (int)$user->id);

        return [$campaignid, (int)$user->id];
    }
}
