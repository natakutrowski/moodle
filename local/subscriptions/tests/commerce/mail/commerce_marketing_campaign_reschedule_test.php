<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignService;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\task\process_marketing_mail_campaigns_task;

final class commerce_marketing_campaign_reschedule_test extends \advanced_testcase {
    /** @return array{0:int,1:int} */
    private function make_campaign(): array {
        global $DB;
        $operator = $this->getDataGenerator()->create_user();
        $template = (new CommerceMailLibraryRepository($DB))->save([
            'name' => 'Campaign start change test',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], ['fr' => [
            'subject' => 'Bonjour {{firstname}}',
            'preheader' => 'Message',
            'bodyhtml' => '<p>Bonjour {{firstname}}</p>',
        ]], (int)$operator->id);
        $id = CommerceMarketingCampaignService::create($DB)->save([
            'name' => 'Start time test',
            'templateid' => (int)$template->id,
            'audiencesource' => 'manual',
            'audience' => "first@example.test;A;Client;fr\nsecond@example.test;B;Client;fr",
        ], (int)$operator->id);
        return [$id, (int)$operator->id];
    }

    public function test_change_time_preserves_frozen_audience_and_does_not_queue(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('commerce_mail_marketing_enabled', 1, 'local_subscriptions');
        [$id, $user] = $this->make_campaign();
        $service = CommerceMarketingCampaignService::create($DB);
        $repo = new CommerceMarketingCampaignRepository($DB);
        $first = time() + 3600;
        $second = time() + 7200;
        $service->schedule($id, $first, $user);
        $before = $repo->get($id);
        $contents = $repo->contents($id);
        $recipients = $repo->recipients($id);
        self::assertTrue($service->can_change_start($id));
        $service->change_start($id, $second, $user);
        $after = $repo->get($id);
        self::assertSame('scheduled', $after->status);
        self::assertSame($second, (int)$after->scheduledat);
        self::assertSame($before->audienceconfig, $after->audienceconfig);
        self::assertEquals($contents, $repo->contents($id));
        self::assertEquals($recipients, $repo->recipients($id));
        ob_start();
        (new process_marketing_mail_campaigns_task())->execute();
        ob_end_clean();
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail'));
    }

    public function test_change_time_from_pause_reschedules_and_remains_not_due(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('commerce_mail_marketing_enabled', 1, 'local_subscriptions');
        [$id, $user] = $this->make_campaign();
        $service = CommerceMarketingCampaignService::create($DB);
        $repo = new CommerceMarketingCampaignRepository($DB);
        $service->schedule($id, time() + 600, $user);
        $service->pause($id);
        self::assertTrue($service->can_change_start($id));
        $date = time() + 1800;
        $service->change_start($id, $date, $user);
        self::assertSame('scheduled', $repo->get($id)->status);
        self::assertSame($date, (int)$repo->get($id)->scheduledat);
        ob_start();
        (new process_marketing_mail_campaigns_task())->execute();
        ob_end_clean();
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail'));
    }

    public function test_start_now_is_eligible_on_next_cron_without_direct_send(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('commerce_mail_marketing_enabled', 1, 'local_subscriptions');
        [$id, $user] = $this->make_campaign();
        $service = CommerceMarketingCampaignService::create($DB);
        $repo = new CommerceMarketingCampaignRepository($DB);
        $service->schedule($id, time() + 3600, $user);
        $service->change_start($id, null, $user);
        self::assertLessThanOrEqual(time(), (int)$repo->get($id)->scheduledat);
        self::assertSame(0, $DB->count_records('local_subs_commerce_mail'));
        ob_start();
        (new process_marketing_mail_campaigns_task())->execute();
        ob_end_clean();
        self::assertSame('queued', $repo->get($id)->status);
        self::assertSame(2, $DB->count_records('local_subs_mail_campaign_recipient',
            ['campaignid' => $id, 'status' => 'queued']));
        self::assertFalse($service->can_change_start($id));
    }

    public function test_queued_campaign_cannot_be_restarted_or_rescheduled(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('commerce_mail_marketing_enabled', 1, 'local_subscriptions');
        [$id, $user] = $this->make_campaign();
        $service = CommerceMarketingCampaignService::create($DB);
        $service->schedule($id, time(), $user);
        ob_start();
        (new process_marketing_mail_campaigns_task())->execute();
        ob_end_clean();
        self::assertFalse($service->can_change_start($id));
        $this->expectException(\invalid_parameter_exception::class);
        $service->change_start($id, time() + 3600, $user);
    }

    public function test_cancelled_and_draft_campaigns_cannot_change_start(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$id, $user] = $this->make_campaign();
        $service = CommerceMarketingCampaignService::create($DB);
        self::assertFalse($service->can_change_start($id));
        $service->schedule($id, time() + 3600, $user);
        $service->cancel($id);
        self::assertFalse($service->can_change_start($id));
        $this->expectException(\invalid_parameter_exception::class);
        $service->change_start($id, null, $user);
    }

    public function test_past_time_is_rejected_without_modifying_existing_schedule(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$id, $user] = $this->make_campaign();
        $service = CommerceMarketingCampaignService::create($DB);
        $repo = new CommerceMarketingCampaignRepository($DB);
        $date = time() + 3600;
        $service->schedule($id, $date, $user);
        try {
            $service->change_start($id, time() - 600, $user);
            self::fail('Past start time must be rejected.');
        } catch (\invalid_parameter_exception $expected) {
            self::assertSame($date, (int)$repo->get($id)->scheduledat);
            self::assertSame('scheduled', $repo->get($id)->status);
        }
    }
}
