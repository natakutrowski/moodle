<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignService;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingOptoutService;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailTerminalCancellationException;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\task\process_marketing_mail_campaigns_task;

final class commerce_marketing_optout_stage2_test extends advanced_testcase {
    private const SECRET = 'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        set_config('commerce_mail_optout_hmac_secret', self::SECRET, 'local_subscriptions');
        set_config('commerce_mail_marketing_enabled', 1, 'local_subscriptions');
    }

    private function create_campaign(string $email): array {
        global $DB;
        $owner = $this->getDataGenerator()->create_user();
        $library = new CommerceMailLibraryRepository($DB);
        $template = $library->save([
            'name' => 'Opt-out test template',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], [
            'fr' => [
                'subject' => 'Votre offre {{firstname}}',
                'bodyhtml' => '<p>Une offre A1</p>',
                'preheader' => '',
            ],
        ], (int)$owner->id);
        $service = CommerceMarketingCampaignService::create($DB);
        $id = $service->save([
            'name' => 'Opt-out test',
            'templateid' => (int)$template->id,
            'audience' => $email . ';Example;Test;fr',
        ], (int)$owner->id);
        $recipient = (new CommerceMarketingCampaignRepository($DB))->recipients($id)[0];
        return [$id, $recipient, $service];
    }

    private function request_for(int $campaignid, string $email): CommerceMailRequest {
        return new CommerceMailRequest(
            CommerceMailType::MARKETING_CAMPAIGN,
            new CommerceMailRecipient($email, 'Example Test'),
            new CommerceMailContext(['campaignid' => $campaignid, 'firstname' => 'Example']),
            'fr',
            'stage2-optout-' . $campaignid
        );
    }

    public function test_email_normalisation_and_idempotent_suppression(): void {
        global $DB;
        $optouts = new CommerceMarketingOptoutService($DB);
        self::assertTrue($optouts->suppress('  TEST.Person@Example.COM  '));
        self::assertFalse($optouts->suppress('test.person@example.com'));
        self::assertTrue($optouts->is_suppressed('TEST.PERSON@EXAMPLE.COM'));
        self::assertSame(1, $DB->count_records('local_subs_mail_optout'));
    }

    public function test_signed_link_is_bound_to_exact_recipient_email(): void {
        global $DB;
        [$campaignid, $recipient] = $this->create_campaign('signed@example.test');
        $optouts = new CommerceMarketingOptoutService($DB);
        $token = $optouts->token_for((int)$recipient->id, (string)$recipient->email);
        self::assertSame('signed@example.test', $optouts->email_from_signed_link((int)$recipient->id, $token));
        self::assertNull($optouts->email_from_signed_link((int)$recipient->id, str_repeat('0', 64)));
        self::assertNull($optouts->email_from_signed_link((int)$recipient->id + 10000, $token));
        self::assertStringNotContainsString('signed%40', $optouts->url_for((int)$recipient->id,
            (string)$recipient->email)->out(false));
        self::assertFalse($optouts->is_suppressed('signed@example.test'));
    }

    public function test_render_includes_html_and_plain_text_links(): void {
        global $DB;
        [$campaignid, $recipient] = $this->create_campaign('footer@example.test');
        $template = CommerceMailRuntime::template_registry()->get(CommerceMailType::MARKETING_CAMPAIGN);
        $message = $template->render($this->request_for($campaignid, 'footer@example.test'));
        self::assertStringContainsString('marketing_unsubscribe.php', $message->get_html());
        self::assertStringContainsString('marketing_unsubscribe.php', $message->get_text());
        self::assertStringContainsString('Se désabonner', $message->get_html());
    }

    public function test_already_suppressed_recipient_not_queued_by_campaign_task(): void {
        global $DB;
        [$campaignid, $recipient, $service] = $this->create_campaign('blocked@example.test');
        (new CommerceMarketingOptoutService($DB))->suppress('blocked@example.test');
        $service->schedule($campaignid, time(), 2);
        ob_start();
        try {
            (new process_marketing_mail_campaigns_task())->execute();
        } finally {
            ob_end_clean();
        }
        // The shared outbox can contain unrelated messages. Check this exact
        // campaign recipient rather than counting all marketing mail globally.
        $recipientnow = $DB->get_record('local_subs_mail_campaign_recipient', ['id' => $recipient->id], '*', MUST_EXIST);
        self::assertSame('cancelled', $recipientnow->status);
        self::assertNull($recipientnow->mailid);
        $key = \local_subscriptions\commerce\mail\CommerceMailIdempotencyKey::normalise(
            'marketing:' . $campaignid . ':recipient:' . (int)$recipient->id
        );
        self::assertFalse($DB->record_exists('local_subs_commerce_mail', [
            'mailtype' => CommerceMailType::MARKETING_CAMPAIGN,
            'idempotencykey' => $key,
        ]));
    }

    public function test_suppression_after_queue_prevents_later_render_and_send(): void {
        global $DB;
        [$campaignid, $recipient] = $this->create_campaign('late@example.test');
        (new CommerceMarketingOptoutService($DB))->suppress('late@example.test');
        $template = CommerceMailRuntime::template_registry()->get(CommerceMailType::MARKETING_CAMPAIGN);
        $this->expectException(CommerceMailTerminalCancellationException::class);
        $template->render($this->request_for($campaignid, 'late@example.test'));
    }

    public function test_suppression_does_not_remove_user_or_change_transactional_outbox(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user(['email' => 'receipt@example.test']);
        (new CommerceMarketingOptoutService($DB))->suppress('receipt@example.test');
        self::assertTrue($DB->record_exists('user', ['id' => $user->id]));
        $request = new CommerceMailRequest(CommerceMailType::PURCHASE_RECEIPT,
            new CommerceMailRecipient('receipt@example.test', '', (int)$user->id),
            new CommerceMailContext(['test' => 'stage2']), 'fr', 'stage2-receipt');
        $queued = CommerceMailRuntime::queue_service()->queue($request);
        self::assertSame(CommerceMailType::PURCHASE_RECEIPT, (string)$queued->mailtype);
    }

    public function test_anonymous_endpoint_does_not_unsubscribe_on_get(): void {
        $root = dirname(__DIR__, 3);
        $code = file_get_contents($root . '/marketing_unsubscribe.php');
        self::assertStringContainsString("\$_SERVER['REQUEST_METHOD'] === 'POST'", $code);
        self::assertStringContainsString("\$optouts->suppress(\$email)", $code);
        self::assertStringContainsString('Referrer-Policy: no-referrer', $code);
    }
}
