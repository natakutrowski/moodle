<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\sales\CommerceSalesFollowupService;
use local_subscriptions\commerce\mail\template\CommerceSalesFollowupTemplate;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

final class commerce_sales_followup_n61_test extends advanced_testcase {
    public function test_starter_followup_templates_are_seeded_once(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $service = CommerceSalesFollowupService::create($DB);

        $first = $service->template_options((int)$user->id);
        $second = $service->template_options((int)$user->id);

        self::assertCount(3, $first);
        self::assertSame($first, $second);
        self::assertSame(
            3,
            $DB->count_records('local_subs_mail_library', [
                'category' => CommerceMailLibrary::CATEGORY_SALES_FOLLOWUP,
                'status' => CommerceMailLibrary::STATUS_ACTIVE,
            ])
        );
    }

    public function test_followup_is_available_for_pending_failed_or_cancelled_but_not_paid(): void {
        global $DB;

        $this->resetAfterTest(true);
        $service = CommerceSalesFollowupService::create($DB);

        foreach (['payment_pending', 'pending', 'failed', 'cancelled'] as $status) {
            self::assertTrue(
                $service->is_summary_eligible($this->summary($status)),
                $status
            );
        }
        self::assertFalse($service->is_summary_eligible($this->summary('paid')));
        self::assertFalse($service->is_summary_eligible($this->summary('succeeded')));

        $noemail = new CommercePurchaseSummary(
            9,
            'uuid',
            'P-9',
            'course_access',
            new CommercePurchaseCustomer(null, '', 'No', 'Email'),
            ['A1 Full'],
            'EUR',
            4500,
            'pending',
            'failed',
            'none',
            'stripe',
            'native',
            time()
        );
        self::assertFalse($service->is_summary_eligible($noemail));
    }

    public function test_followup_runtime_renders_manual_snapshot_and_resume_button(): void {
        $this->resetAfterTest(true);

        $request = new CommerceMailRequest(
            CommerceMailType::SALES_FOLLOWUP,
            new CommerceMailRecipient('client@example.test', 'Client Test'),
            new CommerceMailContext([
                'subject' => 'Bonjour {{firstname}} — {{order_reference}}',
                'bodyhtml' => '<p>{{product_name}}</p><p>{{resume_payment}}</p>',
                'tokens' => [
                    'firstname' => 'Nata',
                    'order_reference' => 'CFR-123',
                    'product_name' => 'A1 Full',
                    'checkout_url' => 'https://example.test/pay',
                ],
                'source_template_id' => 12,
                'resume_payment_label' => 'Finaliser mon achat',
            ]),
            'fr',
            'sales-followup-test-1',
            77
        );

        $message = (new CommerceSalesFollowupTemplate())->render($request);

        self::assertSame('Bonjour Nata — CFR-123', $message->get_subject());
        self::assertStringContainsString('A1 Full', $message->get_html());
        self::assertStringContainsString('https://example.test/pay', $message->get_html());
        self::assertStringContainsString('Finaliser mon achat', $message->get_html());
        self::assertStringNotContainsString('{{resume_payment}}', $message->get_html());
    }

    public function test_n61_sales_mailstudio_bridge_contract(): void {
        $root = dirname(__DIR__, 3);

        $sales = file_get_contents(
            $root . '/admin/commerce/purchases/index.php'
        );
        $compose = file_get_contents(
            $root . '/admin/commerce/purchases/followup_mail.php'
        );
        $library = file_get_contents(
            $root . '/classes/commerce/mail/library/CommerceMailLibrary.php'
        );
        $runtime = file_get_contents(
            $root . '/classes/commerce/mail/CommerceMailRuntime.php'
        );
        $worker = file_get_contents(
            $root . '/classes/task/process_commerce_mail_queue_task.php'
        );

        self::assertStringContainsString(
            'crm-sales-row-menu-followup',
            $sales
        );
        self::assertStringContainsString(
            'CommerceSalesFollowupService',
            $sales
        );
        self::assertStringContainsString(
            'CommerceMailBuilderEditorRenderer::rich_editor',
            $compose
        );
        self::assertStringContainsString(
            'CommerceMailAdminService())->send_now',
            $compose
        );
        self::assertStringContainsString(
            "CATEGORY_SALES_FOLLOWUP = 'sales_followup'",
            $library
        );
        self::assertStringContainsString(
            'new CommerceSalesFollowupTemplate()',
            $runtime
        );

        // Manual sales follow-ups intentionally use the normal transactional
        // worker, while Personal Offer and Marketing retain dedicated workers.
        self::assertStringNotContainsString(
            'CommerceMailType::SALES_FOLLOWUP',
            $worker
        );
    }

    private function summary(string $paymentstatus): CommercePurchaseSummary {
        return new CommercePurchaseSummary(
            7,
            'uuid',
            'P-7',
            'course_access',
            new CommercePurchaseCustomer(
                5,
                'client@example.test',
                'Client',
                'Test'
            ),
            ['A1 Full'],
            'EUR',
            4500,
            'pending',
            $paymentstatus,
            'none',
            'stripe',
            'native',
            time(),
            [],
            'CFR-7'
        );
    }
}
