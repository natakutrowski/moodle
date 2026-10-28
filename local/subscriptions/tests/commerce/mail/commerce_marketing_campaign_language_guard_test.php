<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignContentPolicy;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignService;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\task\process_marketing_mail_campaigns_task;

final class commerce_marketing_campaign_language_guard_test extends \advanced_testcase {
    /** @return array{0:int,1:int} Campaign ID, operator ID */
    private function create_russian_campaign(string $recipientlanguage = 'fr'): array {
        global $DB;
        $operator = $this->getDataGenerator()->create_user([
            'email' => 'language-test-operator@example.test',
        ]);
        $template = (new CommerceMailLibraryRepository($DB))->save([
            'name' => 'Russian only',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], [
            'ru' => [
                'subject' => 'Тема кампании',
                'preheader' => 'Тест',
                'bodyhtml' => '<p>Привет, {{firstname}}!</p>',
            ],
        ], (int)$operator->id);
        $campaignid = CommerceMarketingCampaignService::create($DB)->save([
            'name' => 'Language test',
            'templateid' => (int)$template->id,
            'audiencesource' => 'manual',
            'audience' => 'language-test-recipient@example.test;Test;Client;' . $recipientlanguage,
        ], (int)$operator->id);
        return [$campaignid, (int)$operator->id];
    }

    public function test_content_policy_never_falls_back_from_french_to_russian(): void {
        $ru = (object)[
            'language' => 'ru', 'subject' => 'Тема', 'bodyhtml' => '<p>Здравствуйте</p>',
        ];
        $fr = (object)[
            'language' => 'fr', 'subject' => 'Bonjour', 'bodyhtml' => '<p>Bonjour</p>',
        ];
        self::assertNull(CommerceMarketingCampaignContentPolicy::select(['ru' => $ru], 'fr'));
        self::assertSame($ru, CommerceMarketingCampaignContentPolicy::select(['ru' => $ru], 'ru'));
        self::assertSame($fr, CommerceMarketingCampaignContentPolicy::select(['fr' => $fr], 'en'));
        self::assertSame($fr, CommerceMarketingCampaignContentPolicy::select(['fr' => $fr], 'fr'));
        $bad = (object)['language' => 'ru', 'subject' => '  ', 'bodyhtml' => '<p></p>'];
        self::assertNull(CommerceMarketingCampaignContentPolicy::select(['ru' => $bad], 'ru'));
        self::assertSame(['fr' => 2], CommerceMarketingCampaignContentPolicy::missing_languages(
            ['ru' => $ru], [(object)['language' => 'fr'], (object)['language' => 'fr']]
        ));
    }

    public function test_cannot_schedule_russian_only_campaign_for_french_recipient(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$campaignid, $operatorid] = $this->create_russian_campaign();
        try {
            CommerceMarketingCampaignService::create($DB)->schedule($campaignid, time(), $operatorid);
            self::fail('Expected incompatible recipient language to be rejected.');
        } catch (\invalid_parameter_exception $exception) {
            self::assertStringContainsString('fr (1)', $exception->getMessage());
        }
        self::assertSame('draft', (new CommerceMarketingCampaignRepository($DB))->get($campaignid)->status);
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail'));
    }

    public function test_scheduling_works_once_recipient_language_matches_translation(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$campaignid, $operatorid] = $this->create_russian_campaign('ru');
        CommerceMarketingCampaignService::create($DB)->schedule($campaignid, time() + 600, $operatorid);
        self::assertSame('scheduled', (new CommerceMarketingCampaignRepository($DB))->get($campaignid)->status);
    }

    public function test_cron_refuses_to_queue_mismatched_legacy_campaign(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$campaignid] = $this->create_russian_campaign();
        $DB->set_field('local_subs_mail_campaign', 'status', 'scheduled', ['id' => $campaignid]);
        $DB->set_field('local_subs_mail_campaign', 'scheduledat', time() - 60,
            ['id' => $campaignid]);
        ob_start();
        (new process_marketing_mail_campaigns_task())->execute();
        $output = ob_get_clean();
        self::assertStringContainsString('incompatible language fr', $output);
        $recipient = (new CommerceMarketingCampaignRepository($DB))->recipients($campaignid)[0];
        self::assertSame('cancelled', $recipient->status);
        self::assertEmpty($recipient->mailid);
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail'));
    }

    public function test_already_queued_mismatch_is_cancelled_without_retry_or_delivery(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$campaignid] = $this->create_russian_campaign();
        $repository = new CommerceMarketingCampaignRepository($DB);
        $recipient = $repository->recipients($campaignid)[0];
        $DB->set_field('local_subs_mail_campaign', 'status', 'queued', ['id' => $campaignid]);
        $request = new CommerceMailRequest(
            CommerceMailType::MARKETING_CAMPAIGN,
            new CommerceMailRecipient((string)$recipient->email, 'Test Client', null),
            new CommerceMailContext(['campaignid' => $campaignid, 'firstname' => 'Test']),
            'fr',
            CommerceMailIdempotencyKey::normalise('marketing:' . $campaignid . ':recipient:' . (int)$recipient->id)
        );
        $mail = CommerceMailRuntime::queue_service()->queue($request);
        $repository->mark_recipient_queued((int)$recipient->id, (int)$mail->id, time());
        $result = CommerceMailRuntime::processor()->process_ids([(int)$mail->id]);
        self::assertSame(1, $result['cancelled']);
        self::assertSame(0, $result['sent']);
        self::assertSame(0, $result['retried']);
        $stored = $DB->get_record('local_subs_commerce_mail', ['id' => $mail->id], '*', MUST_EXIST);
        self::assertSame('cancelled', $stored->status);
        self::assertStringContainsString('marketing_language_unavailable', (string)$stored->lasterror);
    }

    public function test_mail_preview_page_handles_incompatible_translation(): void {
        $source = file_get_contents(dirname(__DIR__, 3) . '/admin/commerce/mail/view.php');
        self::assertIsString($source);
        self::assertStringContainsString('CommerceMailTerminalCancellationException', $source);
        self::assertStringContainsString('commerce_marketing_campaign_preview_unavailable', $source);
    }
}
