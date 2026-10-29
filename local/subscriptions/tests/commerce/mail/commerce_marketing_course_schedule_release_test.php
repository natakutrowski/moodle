<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignService;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCourseCampaignGate;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingOptoutService;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

final class commerce_marketing_course_schedule_release_test extends \advanced_testcase {
    /** @return array{0:int,1:int,2:\stdClass} */
    private function fixture(): array {
        global $DB;
        $author = $this->getDataGenerator()->create_user();
        $buyer = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user((int)$buyer->id, (int)$course->id, 'student');
        $now = time();
        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Marketing release test ' . random_string(5),
            'duration_key' => '1month', 'is_active' => 1,
            'creation_date' => $now, 'last_update' => $now,
        ]);
        $DB->insert_record('subscription_plan_entitlement', (object)[
            'planid' => $planid, 'courseid' => $course->id,
            'accesslevel' => 'full', 'roleshortname' => 'student', 'priority' => 100,
            'timecreated' => $now, 'lastupdate' => $now,
        ]);
        $purchaseid = $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => md5('release-' . random_string(12)),
            'reference' => 'release-' . random_string(12),
            'type' => 'subscription', 'userid' => $buyer->id,
            'customeremail' => $buyer->email, 'status' => 'fulfilled', 'currency' => 'EUR',
            'subtotalminor' => 100, 'discountminor' => 0, 'totalminor' => 100,
            'customerjson' => '{}', 'snapshotjson' => '{}', 'metadatajson' => '{}',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $purchaseid, 'position' => 0, 'itemtype' => 'subscription',
            'itemreference' => 'SUB.PLAN.' . $planid, 'label' => 'Full course',
            'quantity' => 1, 'currency' => 'EUR', 'unitminor' => 100,
            'grossminor' => 100, 'discountminor' => 0, 'netminor' => 100,
            'pricingjson' => '{}', 'fulfillmentjson' => '{}', 'metadatajson' => '{}',
        ]);
        $library = new CommerceMailLibraryRepository($DB);
        $template = $library->save([
            'name' => 'Marketing release model',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], ['fr' => [
            'subject' => 'Test pédagogique', 'preheader' => '',
            'bodyhtml' => '<p>Bonjour, test seulement.</p>',
        ]], (int)$author->id);
        $service = CommerceMarketingCampaignService::create($DB);
        $campaignid = $service->save([
            'name' => 'Course release test', 'templateid' => (int)$template->id,
            'audiencesource' => 'course', 'audiencecourseid' => (int)$course->id,
            'audienceskus' => '', 'audienceexcludedids' => '',
            'audiencefilters' => [],
        ], (int)$author->id);
        self::assertSame(1, (new CommerceMarketingCampaignRepository($DB))->recipient_count($campaignid));
        return [$campaignid, (int)$author->id, $buyer];
    }

    public function test_course_scheduling_is_disabled_by_default_even_with_operator_confirmation(): void {
        global $DB;
        $this->resetAfterTest(true);
        unset_config('commerce_mail_course_campaigns_enabled', 'local_subscriptions');
        [$id, $author] = $this->fixture();
        self::assertFalse(CommerceMarketingCourseCampaignGate::enabled());
        try {
            CommerceMarketingCampaignService::create($DB)->schedule($id, time() + 3600, $author, true);
            self::fail('Course scheduling must fail closed by default.');
        } catch (\invalid_parameter_exception $e) {
            self::assertSame('draft', (string)(new CommerceMarketingCampaignRepository($DB))->get($id)->status);
        }
    }

    public function test_enabled_flag_does_not_replace_operator_marketing_confirmation(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('commerce_mail_course_campaigns_enabled', '1', 'local_subscriptions');
        [$id, $author] = $this->fixture();
        self::assertTrue(CommerceMarketingCourseCampaignGate::enabled());
        try {
            CommerceMarketingCampaignService::create($DB)->schedule($id, time() + 3600, $author);
            self::fail('Explicit legal review is required.');
        } catch (\invalid_parameter_exception $e) {
            self::assertSame('draft', (string)(new CommerceMarketingCampaignRepository($DB))->get($id)->status);
        }
    }

    public function test_enabled_and_reviewed_course_schedules_with_recorded_operator_signoff(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('commerce_mail_course_campaigns_enabled', '1', 'local_subscriptions');
        [$id, $author] = $this->fixture();
        CommerceMarketingCampaignService::create($DB)->schedule($id, time() + 3600, $author, true);
        $campaign = (new CommerceMarketingCampaignRepository($DB))->get($id);
        self::assertSame('scheduled', (string)$campaign->status);
        $config = json_decode((string)$campaign->audienceconfig, true);
        self::assertSame($author, $config['marketingreview']['by']);
        self::assertTrue($config['marketingreview']['confirmed']);
        self::assertGreaterThan(0, $config['marketingreview']['at']);
    }

    public function test_legacy_course_draft_without_audience_policy_must_be_resaved(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('commerce_mail_course_campaigns_enabled', '1', 'local_subscriptions');
        [$id, $author] = $this->fixture();
        $repo = new CommerceMarketingCampaignRepository($DB);
        $campaign = $repo->get($id);
        $config = json_decode((string)$campaign->audienceconfig, true);
        unset($config['audiencepolicy']);
        $DB->set_field('local_subs_mail_campaign', 'audienceconfig',
            json_encode($config, JSON_THROW_ON_ERROR), ['id' => $id]);
        self::assertFalse(CommerceMarketingCourseCampaignGate::snapshot_is_current($DB, $repo, $repo->get($id)));
        try {
            CommerceMarketingCampaignService::create($DB)->schedule($id, time() + 3600, $author, true);
            self::fail('Historic course drafts must be reviewed and saved again under the new policy.');
        } catch (\invalid_parameter_exception $e) {
            self::assertSame('draft', (string)$repo->get($id)->status);
        }
    }

    public function test_suppressed_recipient_after_save_makes_snapshot_stale(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('commerce_mail_course_campaigns_enabled', '1', 'local_subscriptions');
        [$id, $author, $buyer] = $this->fixture();
        (new CommerceMarketingOptoutService($DB))->suppress((string)$buyer->email);
        self::assertFalse(CommerceMarketingCourseCampaignGate::snapshot_is_current(
            $DB, new CommerceMarketingCampaignRepository($DB),
            (new CommerceMarketingCampaignRepository($DB))->get($id)));
        try {
            CommerceMarketingCampaignService::create($DB)->schedule($id, time() + 3600, $author, true);
            self::fail('Changed course audience must fail closed.');
        } catch (\invalid_parameter_exception $e) {
            self::assertSame('draft', (string)(new CommerceMarketingCampaignRepository($DB))->get($id)->status);
        }
    }

    public function test_release_switch_does_not_allow_legacy_ui_bypass(): void {
        $root = dirname(__DIR__, 3);
        $editor = file_get_contents($root . '/admin/commerce/mail/campaigns/edit.php');
        $worker = file_get_contents($root . '/classes/task/process_marketing_mail_campaigns_task.php');
        $queue = file_get_contents($root . '/classes/commerce/mail/CommerceMailQueueProcessor.php');
        self::assertStringContainsString('confirm_course_legal', (string)$editor);
        self::assertStringContainsString('CommerceMarketingCourseCampaignGate::enabled()', (string)$editor);
        self::assertStringContainsString('CommerceMarketingCourseCampaignGate::snapshot_is_current', (string)$worker);
        self::assertStringContainsString('CommerceMarketingCourseCampaignGate::enabled()', (string)$queue);
    }
}
