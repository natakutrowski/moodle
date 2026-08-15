<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignService;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\task\process_marketing_mail_campaigns_task;

final class commerce_mail_marketing_campaign_n54_test extends advanced_testcase {
    public function test_generic_marketing_campaign_freezes_template_and_audience(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user([
            'email' => 'nata-n54@example.test',
            'firstname' => 'Nata',
            'lastname' => 'Test',
        ]);

        $library = new CommerceMailLibraryRepository($DB);
        $template = $library->save([
            'name' => 'N5.4 marketing model',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'metadata' => ['editor' => 'mail_builder'],
        ], [
            'fr' => [
                'subject' => 'Bonjour {{firstname}}',
                'preheader' => 'Préheader A',
                'bodyhtml' => '<p>Version A {{fullname}}</p>'
                    . '{{cta|campus_pink}}Découvrir{{/cta}}',
            ],
            'ru' => [
                'subject' => 'Здравствуйте, {{firstname}}',
                'preheader' => '',
                'bodyhtml' => '<p>Версия A</p>',
            ],
        ], (int)$user->id);

        $service = CommerceMarketingCampaignService::create($DB);
        $campaignid = $service->save([
            'name' => 'Campagne marketing N5.4',
            'templateid' => (int)$template->id,
            'ctaurl' => 'https://www.campusfr.fr/marketing-test',
            'audience' => "nata-n54@example.test\nexternal@example.test;External;Person;ru",
        ], (int)$user->id);

        $repository = new CommerceMarketingCampaignRepository($DB);
        self::assertSame(2, $repository->recipient_count($campaignid));
        $contents = $repository->contents($campaignid);
        self::assertSame('Bonjour {{firstname}}', (string)$contents['fr']->subject);
        self::assertStringContainsString('Version A', (string)$contents['fr']->bodyhtml);

        // Later edits to the reusable template do not mutate this campaign.
        $library->save([
            'name' => 'N5.4 marketing model',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'metadata' => ['editor' => 'mail_builder'],
        ], [
            'fr' => [
                'subject' => 'Version B',
                'preheader' => '',
                'bodyhtml' => '<p>Version B</p>',
            ],
        ], (int)$user->id, (int)$template->id);

        $frozen = $repository->contents($campaignid);
        self::assertSame('Bonjour {{firstname}}', (string)$frozen['fr']->subject);
        self::assertStringContainsString('Version A', (string)$frozen['fr']->bodyhtml);
        self::assertStringNotContainsString('Version B', (string)$frozen['fr']->bodyhtml);
    }

    public function test_scheduled_campaign_is_enqueued_into_shared_mail_engine(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();

        $library = new CommerceMailLibraryRepository($DB);
        $template = $library->save([
            'name' => 'Queue model',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'metadata' => ['editor' => 'mail_builder'],
        ], [
            'fr' => [
                'subject' => 'Marketing {{firstname}}',
                'preheader' => '',
                'bodyhtml' => '<p>Hello</p>',
            ],
        ], (int)$user->id);

        $service = CommerceMarketingCampaignService::create($DB);
        $campaignid = $service->save([
            'name' => 'Queue campaign',
            'templateid' => (int)$template->id,
            'ctaurl' => '',
            'audience' => 'queue-n54@example.test;Queue;Recipient;fr',
        ], (int)$user->id);
        $service->schedule($campaignid, time(), (int)$user->id);

        $this->expectOutputRegex('/\\[Marketing Mail\\] campaign=\\d+ queued=1/');
        (new process_marketing_mail_campaigns_task())->execute();

        $mail = $DB->get_record(
            'local_subs_commerce_mail',
            ['mailtype' => CommerceMailType::MARKETING_CAMPAIGN],
            '*',
            MUST_EXIST
        );
        self::assertSame('queue-n54@example.test', (string)$mail->recipientemail);

        $campaign = (new CommerceMarketingCampaignRepository($DB))->get($campaignid);
        self::assertSame('queued', (string)$campaign->status);

        $message = CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::MARKETING_CAMPAIGN)
            ->render(new \local_subscriptions\commerce\mail\CommerceMailRequest(
                CommerceMailType::MARKETING_CAMPAIGN,
                new \local_subscriptions\commerce\mail\CommerceMailRecipient(
                    (string)$mail->recipientemail,
                    (string)$mail->recipientname,
                    $mail->userid === null ? null : (int)$mail->userid
                ),
                new \local_subscriptions\commerce\mail\CommerceMailContext(
                    json_decode((string)$mail->contextjson, true)
                ),
                (string)$mail->language,
                (string)$mail->idempotencykey
            ));

        self::assertStringContainsString('Marketing Queue', $message->get_subject());
        self::assertStringContainsString('Hello', $message->get_html());
    }

    public function test_n54_ui_tasks_configuration_and_navigation_contract(): void {
        $root = dirname(__DIR__, 3);

        $navigation = file_get_contents(
            $root . '/classes/commerce/mail/admin/CommerceMailSectionNavigationRenderer.php'
        );
        $configuration = file_get_contents(
            $root . '/admin/commerce/mail/configuration.php'
        );
        $tasks = file_get_contents($root . '/db/tasks.php');
        $runtime = file_get_contents(
            $root . '/classes/commerce/mail/CommerceMailRuntime.php'
        );
        $type = file_get_contents(
            $root . '/classes/commerce/mail/CommerceMailType.php'
        );

        self::assertStringContainsString(
            '/local/subscriptions/admin/commerce/mail/campaigns/index.php',
            $navigation
        );
        foreach ([
            'commerce_mail_marketing_enabled',
            'commerce_mail_marketing_batch_size',
            'commerce_mail_marketing_hourly_limit',
        ] as $key) {
            self::assertStringContainsString($key, $configuration);
        }
        self::assertStringContainsString('process_marketing_mail_campaigns_task', $tasks);
        self::assertStringContainsString('process_marketing_mail_queue_task', $tasks);
        self::assertStringContainsString('CommerceMarketingCampaignTemplate', $runtime);
        self::assertStringContainsString("MARKETING_CAMPAIGN = 'marketing_campaign'", $type);
    }

    public function test_n541_upgrade_does_not_use_nonexistent_database_manager_key_exists(): void {
        $root = dirname(__DIR__, 3);
        $upgrade = file_get_contents($root . '/db/upgrade.php');

        self::assertIsString($upgrade);
        self::assertStringNotContainsString('$dbman->key_exists(', $upgrade);
        self::assertStringContainsString(
            '$dbman->add_key($table, $key);',
            $upgrade
        );
    }

}
