<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailAuditCopyPolicy;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailDispatcher;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailQueueProcessor;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailQueueService;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRetryPolicy;
use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailTemplate;
use local_subscriptions\commerce\mail\CommerceMailTemplateRegistry;
use local_subscriptions\commerce\mail\CommerceMailTransport;
use local_subscriptions\commerce\mail\CommerceMailType;

final class commerce_transactional_mail_immediate_delivery_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_process_ids_sends_selected_message_immediately_and_only_once(): void {
        $repository = new CommerceMailQueueRepository();
        $record = (new CommerceMailQueueService($repository))->queue(new CommerceMailRequest(
            CommerceMailType::PURCHASE_ACCESS,
            new CommerceMailRecipient('customer@example.test', 'Customer'),
            new CommerceMailContext(['purchase' => ['reference' => 'CFR-2026-TEST']]),
            'fr',
            CommerceMailIdempotencyKey::for_purchase(501, CommerceMailType::PURCHASE_ACCESS),
            501
        ));

        $transport = new class implements CommerceMailTransport {
            public int $sent = 0;
            public function send(CommerceMailMessage $message): void {
                $this->sent++;
            }
        };
        $template = new class implements CommerceMailTemplate {
            public function get_type(): string {
                return CommerceMailType::PURCHASE_ACCESS;
            }
            public function render(CommerceMailRequest $request): CommerceMailMessage {
                return new CommerceMailMessage(
                    $request->get_recipient(),
                    'Accès disponibles',
                    '<p>Accès disponibles</p>',
                    'Accès disponibles'
                );
            }
        };
        $templates = new CommerceMailTemplateRegistry([$template]);
        $processor = new CommerceMailQueueProcessor(
            $repository,
            $templates,
            new CommerceMailDispatcher($templates, $transport),
            new CommerceMailRetryPolicy()
        );

        $first = $processor->process_ids([(int)$record->id]);
        $second = $processor->process_ids([(int)$record->id]);

        $this->assertSame(1, $first['sent']);
        $this->assertSame(1, $second['skipped']);
        $this->assertSame(1, $transport->sent);
        $this->assertSame(
            CommerceMailStatus::SENT,
            $repository->find_by_id((int)$record->id)->status
        );
    }

    public function test_failed_immediate_attempt_is_queued_for_cron_retry(): void {
        $repository = new CommerceMailQueueRepository();
        $record = (new CommerceMailQueueService($repository))->queue(new CommerceMailRequest(
            CommerceMailType::PURCHASE_RECEIPT,
            new CommerceMailRecipient('customer@example.test', 'Customer'),
            new CommerceMailContext(['purchase' => ['reference' => 'CFR-2026-TEST']]),
            'fr',
            CommerceMailIdempotencyKey::for_purchase(502, CommerceMailType::PURCHASE_RECEIPT),
            502
        ));

        $transport = new class implements CommerceMailTransport {
            public function send(CommerceMailMessage $message): void {
                throw new \RuntimeException('Temporary SMTP failure');
            }
        };
        $template = new class implements CommerceMailTemplate {
            public function get_type(): string {
                return CommerceMailType::PURCHASE_RECEIPT;
            }
            public function render(CommerceMailRequest $request): CommerceMailMessage {
                return new CommerceMailMessage(
                    $request->get_recipient(),
                    'Confirmation',
                    '<p>Confirmation</p>',
                    'Confirmation'
                );
            }
        };
        $templates = new CommerceMailTemplateRegistry([$template]);
        $processor = new CommerceMailQueueProcessor(
            $repository,
            $templates,
            new CommerceMailDispatcher($templates, $transport),
            new CommerceMailRetryPolicy()
        );

        $processingnow = (int)$record->nextruntime;
        $result = $processor->process_ids([(int)$record->id], $processingnow);
        $persisted = $repository->find_by_id((int)$record->id);

        $this->assertSame(1, $result['retried']);
        $this->assertSame(CommerceMailStatus::QUEUED, $persisted->status);
        $this->assertGreaterThan($processingnow, (int)$persisted->nextruntime);
        $this->assertStringContainsString('SMTP', (string)$persisted->lasterror);
    }

    public function test_audit_copy_policy_is_configurable_and_uses_separate_key(): void {
        set_config('commerce_mail_audit_copy_enabled', 1, 'local_subscriptions');
        set_config('commerce_mail_audit_copy_address', 'log@campusfr.fr', 'local_subscriptions');
        set_config(
            'commerce_mail_audit_copy_types',
            CommerceMailType::PURCHASE_RECEIPT . ',' . CommerceMailType::PURCHASE_ACCESS,
            'local_subscriptions'
        );
        set_config('commerce_mail_audit_copy_include_attachment', 0, 'local_subscriptions');

        $policy = new CommerceMailAuditCopyPolicy();

        $this->assertTrue($policy->is_enabled_for(CommerceMailType::PURCHASE_RECEIPT));
        $this->assertTrue($policy->is_enabled_for(CommerceMailType::PURCHASE_ACCESS));
        $this->assertFalse($policy->is_enabled_for(CommerceMailType::PAYMENT_FAILED));
        $this->assertSame('log@campusfr.fr', $policy->get_address());
        $this->assertFalse($policy->include_attachment());
        $this->assertSame(
            'purchase:502:purchase_receipt:audit',
            CommerceMailIdempotencyKey::for_purchase_audit_copy(
                502,
                CommerceMailType::PURCHASE_RECEIPT
            )
        );
    }
}
