<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignService;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignTestMailer;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\task\process_marketing_mail_campaigns_task;

final class commerce_marketing_campaign_stage4_test extends \advanced_testcase {
    /** @return array{0:int,1:int} campaign ID, operator ID */
    private function make_manual_campaign(int $recipients = 2): array {
        global $DB;
        $operator = $this->getDataGenerator()->create_user([
            'email' => 'stage4-operator@example.test',
            'firstname' => 'Opérateur', 'lastname' => 'Test',
        ]);
        $library = new CommerceMailLibraryRepository($DB);
        $template = $library->save([
            'name' => 'Stage 4 test template',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], ['fr' => [
            'subject' => 'Bonjour {{firstname}}', 'preheader' => 'Test',
            'bodyhtml' => '<p>Bonjour {{fullname}}</p>',
        ]], (int)$operator->id);
        $emails = [];
        for ($i = 0; $i < $recipients; ++$i) {
            $emails[] = 'stage4-recipient-' . $i . '@example.test;Client;Test;fr';
        }
        $id = CommerceMarketingCampaignService::create($DB)->save([
            'name' => 'Stage 4 draft',
            'templateid' => (int)$template->id,
            'audiencesource' => 'manual',
            'audience' => implode("\n", $emails),
        ], (int)$operator->id);
        return [$id, (int)$operator->id];
    }

    public function test_test_email_is_not_sent_to_customers_or_queued_while_rendering(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$campaignid, $operatorid] = $this->make_manual_campaign();
        $operator = $DB->get_record('user', ['id' => $operatorid], '*', MUST_EXIST);
        $repo = new CommerceMarketingCampaignRepository($DB);
        $message = (new CommerceMarketingCampaignTestMailer($DB, $repo))
            ->render($campaignid, $operator, 'fr');
        self::assertSame('stage4-operator@example.test', $message->get_recipient()->get_email());
        self::assertStringContainsString('[TEST CampusFR]', $message->get_subject());
        self::assertStringContainsString('Opérateur Test', $message->get_html());
        self::assertStringNotContainsString('/marketing_unsubscribe.php?r=', $message->get_html());
        self::assertSame(2, $repo->recipient_count($campaignid));
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail'));
    }

    public function test_russian_only_draft_sends_one_real_test_via_moodle_email_sink(): void {
        global $DB;
        $this->resetAfterTest(true);
        $operator = $this->getDataGenerator()->create_user([
            'email' => 'mailstudio-test-operator@example.test',
            'firstname' => 'Test', 'lastname' => 'Operator',
        ]);
        $library = new CommerceMailLibraryRepository($DB);
        $template = $library->save([
            'name' => 'Russian only marketing template',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], ['ru' => [
            'subject' => 'Здравствуйте {{firstname}}', 'preheader' => 'Предпросмотр',
            'bodyhtml' => '<p>Тестовое сообщение для {{fullname}}</p>',
        ]], (int)$operator->id);
        $campaignid = CommerceMarketingCampaignService::create($DB)->save([
            'name' => 'RU-only test campaign',
            'templateid' => (int)$template->id,
            'audiencesource' => 'manual',
            'audience' => 'mailstudio-customer@example.test;Client;Test;ru',
        ], (int)$operator->id);

        $sink = $this->redirectEmails();
        CommerceMarketingCampaignService::create($DB)->send_test($campaignid, (int)$operator->id, 'ru');
        $messages = $sink->get_messages();
        self::assertCount(1, $messages);
        self::assertSame('mailstudio-test-operator@example.test', $messages[0]->to);
        self::assertStringContainsString('[TEST CampusFR]', $messages[0]->subject);
        self::assertSame(1, (new CommerceMarketingCampaignRepository($DB))->recipient_count($campaignid));
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail'));
    }

    public function test_russian_only_campaign_requires_explicit_delivery_override(): void {
        global $DB;
        $this->resetAfterTest(true);
        $operator = $this->getDataGenerator()->create_user();
        $library = new CommerceMailLibraryRepository($DB);
        $template = $library->save([
            'name' => 'E11 Russian only',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], ['ru' => [
            'subject' => 'Привет', 'preheader' => '', 'bodyhtml' => '<p>Привет!</p>',
        ]], (int)$operator->id);
        $service = CommerceMarketingCampaignService::create($DB);
        $repo = new CommerceMarketingCampaignRepository($DB);
        $data = [
            'name' => 'RU for FR profile',
            'templateid' => (int)$template->id,
            'audiencesource' => 'manual',
            'audience' => 'e11-profile-fr@example.test;Client;Test;fr',
        ];
        $id = $service->save($data, (int)$operator->id);
        self::assertSame('fr', $repo->recipients($id)[0]->language);
        try {
            $service->schedule($id, time() + 3600, (int)$operator->id);
            self::fail('A RU-only campaign must not silently send to an FR profile.');
        } catch (\moodle_exception $exception) {
            self::assertSame('draft', $repo->get($id)->status);
        }

        $data['deliverylanguage'] = 'ru';
        $service->save($data, (int)$operator->id, $id);
        self::assertSame('ru', $repo->recipients($id)[0]->language);
        $config = json_decode((string)$repo->get($id)->audienceconfig, true);
        self::assertSame('ru', $config['deliverylanguage']);
        $service->schedule($id, time() + 3600, (int)$operator->id);
        self::assertSame('scheduled', $repo->get($id)->status);
    }

    public function test_delivery_override_rejects_missing_translation(): void {
        global $DB;
        $this->resetAfterTest(true);
        $operator = $this->getDataGenerator()->create_user();
        $library = new CommerceMailLibraryRepository($DB);
        $template = $library->save([
            'name' => 'E11 RU template', 'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], ['ru' => ['subject' => 'Привет', 'preheader' => '', 'bodyhtml' => '<p>Привет!</p>']],
            (int)$operator->id);
        $this->expectException(\moodle_exception::class);
        CommerceMarketingCampaignService::create($DB)->save([
            'name' => 'Invalid override', 'templateid' => (int)$template->id,
            'audiencesource' => 'manual',
            'audience' => 'e11-fr@example.test;F;R;fr',
            'deliverylanguage' => 'fr',
        ], (int)$operator->id);
    }

    public function test_pause_resume_and_cancel_keep_delivery_history_intact(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$id, $user] = $this->make_manual_campaign();
        $service = CommerceMarketingCampaignService::create($DB);
        $repo = new CommerceMarketingCampaignRepository($DB);
        $queue = new CommerceMailQueueRepository();
        $service->schedule($id, time(), $user);
        $service->pause($id);
        self::assertSame('paused', $repo->get($id)->status);
        ob_start();
        (new process_marketing_mail_campaigns_task())->execute();
        $pausedoutput = ob_get_clean();
        self::assertSame('', $pausedoutput);
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail'));
        $service->resume($id);
        self::assertSame('scheduled', $repo->get($id)->status);
        ob_start();
        (new process_marketing_mail_campaigns_task())->execute();
        $queuedoutput = ob_get_clean();
        self::assertStringContainsString('queued=2', $queuedoutput);
        self::assertSame('queued', $repo->get($id)->status);
        self::assertCount(2, $queue->get_due_active_marketing(100, time()));
        $service->pause($id);
        self::assertSame('paused', $repo->get($id)->status);
        self::assertCount(0, $queue->get_due_active_marketing(100, time()));
        $service->resume($id);
        self::assertSame('queued', $repo->get($id)->status);
        self::assertCount(2, $queue->get_due_active_marketing(100, time()));
        $service->cancel($id);
        self::assertSame('cancelled', $repo->get($id)->status);
        self::assertCount(0, $queue->get_due_active_marketing(100, time()));
        foreach ($repo->recipient_details($id) as $recipient) {
            self::assertSame('cancelled', $recipient->deliverystatus);
        }
    }

    public function test_course_campaign_remains_undeliverable_even_if_forced_scheduled(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$id] = $this->make_manual_campaign(1);
        $DB->set_field('local_subs_mail_campaign', 'audiencesource', 'course', ['id' => $id]);
        $DB->set_field('local_subs_mail_campaign', 'status', 'scheduled', ['id' => $id]);
        $DB->set_field('local_subs_mail_campaign', 'scheduledat', time(), ['id' => $id]);
        ob_start();
        (new process_marketing_mail_campaigns_task())->execute();
        $blockedoutput = ob_get_clean();
        self::assertStringContainsString('course campaign release switch OFF', $blockedoutput);
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail',
            ['mailtype' => CommerceMailType::MARKETING_CAMPAIGN]));
    }

    public function test_paused_future_schedule_keeps_future_time_when_resumed(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$id, $user] = $this->make_manual_campaign(1);
        $date = time() + 7200;
        $service = CommerceMarketingCampaignService::create($DB);
        $service->schedule($id, $date, $user);
        $service->pause($id);
        $service->resume($id);
        $campaign = (new CommerceMarketingCampaignRepository($DB))->get($id);
        self::assertSame('scheduled', $campaign->status);
        self::assertSame($date, (int)$campaign->scheduledat);
    }

    public function test_saved_course_draft_cannot_be_relabelled_as_manual_to_bypass_lock(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$id, $userid] = $this->make_manual_campaign(1);
        $DB->set_field('local_subs_mail_campaign', 'audiencesource', 'course', ['id' => $id]);
        $record = (new CommerceMarketingCampaignRepository($DB))->get($id);
        $this->expectException(\invalid_parameter_exception::class);
        CommerceMarketingCampaignService::create($DB)->save([
            'name' => 'Bypass attempt', 'templateid' => (int)$record->templateid,
            'audiencesource' => 'manual',
            'audience' => 'stage4-recipient-0@example.test;Client;Test;fr',
        ], $userid, $id);
    }

    public function test_operator_cannot_send_test_for_a_scheduled_campaign(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$id, $userid] = $this->make_manual_campaign(1);
        $service = CommerceMarketingCampaignService::create($DB);
        $service->schedule($id, time(), $userid);
        $this->expectException(\coding_exception::class);
        $service->send_test($id, $userid, 'fr');
    }
}
