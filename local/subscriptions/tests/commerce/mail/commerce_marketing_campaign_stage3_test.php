<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\audience\CommerceMarketingCampaignAudienceResolver;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignService;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingOptoutService;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

final class commerce_marketing_campaign_stage3_test extends \advanced_testcase {
    /** Creates a fulfilled plan-backed purchase for an enrolled test user. */
    private function add_verified_purchase(int $courseid, \stdClass $user): void {
        global $DB;
        $now = time();
        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Mail Studio proof fixture ' . random_string(5),
            'duration_key' => '1month', 'is_active' => 1,
            'creation_date' => $now, 'last_update' => $now,
        ]);
        $DB->insert_record('subscription_plan_entitlement', (object)[
            'planid' => $planid, 'courseid' => $courseid, 'accesslevel' => 'full',
            'roleshortname' => 'student', 'priority' => 100,
            'timecreated' => $now, 'lastupdate' => $now,
        ]);
        $id = $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => md5('mailstudio-fixture-' . random_string(12)),
            'reference' => 'ms-proof-' . random_string(12),
            'type' => 'subscription', 'userid' => (int)$user->id,
            'customeremail' => (string)$user->email,
            'status' => 'fulfilled', 'currency' => 'EUR',
            'subtotalminor' => 100, 'discountminor' => 0, 'totalminor' => 100,
            'customerjson' => '{}', 'snapshotjson' => '{}', 'metadatajson' => '{}',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $id, 'position' => 0, 'itemtype' => 'subscription',
            'itemreference' => 'SUB.PLAN.' . $planid, 'label' => 'Verified course purchase',
            'quantity' => 1, 'currency' => 'EUR', 'unitminor' => 100,
            'grossminor' => 100, 'discountminor' => 0, 'netminor' => 100,
            'pricingjson' => '{}', 'fulfillmentjson' => '{}', 'metadatajson' => '{}',
        ]);
    }

    public function test_option_parser_rejects_invalid_input_and_deduplicates(): void {
        self::assertSame(['A1-FR-SUPPORT', 'A1-RU-SUPPORT'],
            CommerceMarketingCampaignAudienceResolver::parse_skus('a1-fr-support,A1-RU-SUPPORT, A1-FR-SUPPORT'));
        self::assertSame([12, 23],
            CommerceMarketingCampaignAudienceResolver::parse_excluded_ids('12, 23;12'));
        $this->expectException(\invalid_parameter_exception::class);
        CommerceMarketingCampaignAudienceResolver::parse_excluded_ids('12,someone');
    }

    public function test_course_preview_excludes_explicit_internal_accounts_and_optouts(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $customer = $this->getDataGenerator()->create_user(['email' => 'e3-customer@example.test']);
        $internal = $this->getDataGenerator()->create_user(['email' => 'e3-internal@example.test']);
        $suppressed = $this->getDataGenerator()->create_user(['email' => 'e3-optout@example.test']);
        foreach ([$customer, $internal, $suppressed] as $user) {
            $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
            $this->add_verified_purchase((int)$course->id, $user);
        }
        (new CommerceMarketingOptoutService($DB))->suppress('e3-optout@example.test');
        $report = CommerceMarketingCampaignAudienceResolver::create($DB)
            ->preview_course((int)$course->id, [], [(int)$internal->id]);
        self::assertSame(3, $report['summary']['candidates']);
        self::assertSame(1, $report['summary']['eligible']);
        self::assertSame(2, $report['summary']['excluded']);
        self::assertSame('e3-customer@example.test', $report['recipients'][0]['email']);
        self::assertSame((int)$customer->id, $report['recipients'][0]['userid']);
        self::assertSame(1, $report['summary']['reason_internal_exclusion']);
        self::assertSame(1, $report['summary']['reason_marketing_optout']);
    }

    public function test_course_campaign_freezes_recipients_and_cannot_be_scheduled(): void {
        global $DB;
        $this->resetAfterTest(true);
        $author = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $customer = $this->getDataGenerator()->create_user(['email' => 'e3-freeze@example.test']);
        $this->getDataGenerator()->enrol_user($customer->id, $course->id, 'student');
        $this->add_verified_purchase((int)$course->id, $customer);
        $library = new CommerceMailLibraryRepository($DB);
        $template = $library->save([
            'name' => 'Mail Studio Stage 3 model',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], ['fr' => [
            'subject' => 'Campagne test',
            'preheader' => '',
            'bodyhtml' => '<p>Bonjour !</p>',
        ]], (int)$author->id);
        $service = CommerceMarketingCampaignService::create($DB);
        $campaignid = $service->save([
            'name' => 'E3 course draft',
            'templateid' => (int)$template->id,
            'audiencesource' => 'course',
            'audiencecourseid' => (int)$course->id,
            'audienceskus' => 'A1-FR-SUPPORT,A1-RU-SUPPORT',
            'audienceexcludedids' => '',
            'audience' => 'spoof@example.test;Spoof;Name;fr',
        ], (int)$author->id);
        $repo = new CommerceMarketingCampaignRepository($DB);
        self::assertSame(1, $repo->recipient_count($campaignid));
        self::assertSame('e3-freeze@example.test', $repo->recipients($campaignid)[0]->email);
        $record = $repo->get($campaignid);
        self::assertSame('course', (string)$record->audiencesource);
        $config = json_decode((string)$record->audienceconfig, true);
        self::assertSame((int)$course->id, $config['courseid']);
        try {
            $service->schedule($campaignid, time(), (int)$author->id);
            self::fail('Stage 3 course campaigns must remain draft-only.');
        } catch (\coding_exception $exception) {
            self::assertSame('draft', (string)$repo->get($campaignid)->status);
        }
    }

    public function test_manual_preview_and_save_exclude_optouts(): void {
        global $DB;
        $this->resetAfterTest(true);
        (new CommerceMarketingOptoutService($DB))->suppress('e3-manual-blocked@example.test');
        $raw = "e3-manual-ok@example.test;Ok;Person;fr\n" .
            'e3-manual-blocked@example.test;No;Contact;ru';
        $service = CommerceMarketingCampaignService::create($DB);
        $preview = $service->preview_manual($raw);
        self::assertSame(2, $preview['summary']['candidates']);
        self::assertSame(1, $preview['summary']['eligible']);
        self::assertSame(1, $preview['summary']['excluded']);
        self::assertSame('e3-manual-ok@example.test', $preview['recipients'][0]['email']);
    }

    public function test_admin_editor_preview_uses_session_key_and_never_dispatches_mail(): void {
        $root = dirname(__DIR__, 3);
        $editor = file_get_contents($root . '/admin/commerce/mail/campaigns/edit.php');
        self::assertIsString($editor);
        self::assertStringContainsString('require_sesskey()', $editor);
        self::assertStringContainsString("\$mode === 'preview'", $editor);
        self::assertStringContainsString('CommerceMarketingCampaignAudienceResolver', $editor);
        self::assertStringContainsString('commerce_campaign_stage3_no_schedule', $editor);
        self::assertStringNotContainsString('process_marketing_mail_campaigns_task', $editor);
    }
}
