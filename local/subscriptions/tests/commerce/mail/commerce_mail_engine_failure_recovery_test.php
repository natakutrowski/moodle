<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
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
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailTransport;
use local_subscriptions\commerce\mail\CommerceMailType;

/** Certifies recovery after a temporary transport failure. */
final class commerce_mail_engine_failure_recovery_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_temporary_smtp_failure_is_retried_and_eventually_sent(): void {
        $repository = new CommerceMailQueueRepository();
        $queue = new CommerceMailQueueService($repository);
        $transport = new class implements CommerceMailTransport {
            public int $attempts = 0;
            /** @var CommerceMailMessage[] */
            public array $messages = [];

            public function send(CommerceMailMessage $message): void {
                $this->attempts++;
                if ($this->attempts === 1) {
                    throw new \RuntimeException('Temporary SMTP failure for certification');
                }
                $this->messages[] = $message;
            }
        };
        $templates = CommerceMailRuntime::template_registry();
        $processor = new CommerceMailQueueProcessor(
            $repository,
            $templates,
            new CommerceMailDispatcher($templates, $transport),
            new CommerceMailRetryPolicy()
        );

        $record = $queue->queue(new CommerceMailRequest(
            CommerceMailType::PAYMENT_PENDING,
            new CommerceMailRecipient('customer@example.test', 'Customer CampusFR'),
            new CommerceMailContext([
                'customer' => ['firstname' => 'Nata'],
                'purchase' => [
                    'reference' => 'CFR-2026-RETRY',
                    'totalformatted' => '49,00 €',
                ],
                'items' => [],
                'payment' => [
                    'providerlabel' => 'Stripe',
                    'transactionreference' => 'pi_retry',
                    'statuslabel' => 'En attente',
                    'amountformatted' => '49,00 €',
                ],
                'links' => ['order' => 'https://example.test/order/retry'],
            ]),
            'fr',
            CommerceMailIdempotencyKey::for_purchase(850, CommerceMailType::PAYMENT_PENDING),
            850
        ));

        $processingnow = (int)$record->nextruntime;
        $first = $processor->process_ids([(int)$record->id], $processingnow);
        $afterfailure = $repository->find_by_id((int)$record->id);

        $this->assertSame(1, $first['retried']);
        $this->assertSame(CommerceMailStatus::QUEUED, $afterfailure->status);
        $this->assertSame(1, (int)$afterfailure->attemptcount);
        $this->assertGreaterThan($processingnow, (int)$afterfailure->nextruntime);
        $this->assertStringContainsString('Temporary SMTP failure', (string)$afterfailure->lasterror);

        $second = $processor->process_due(10, (int)$afterfailure->nextruntime);
        $aftersuccess = $repository->find_by_id((int)$record->id);

        $this->assertSame(1, $second['sent']);
        $this->assertSame(CommerceMailStatus::SENT, $aftersuccess->status);
        $this->assertSame(2, (int)$aftersuccess->attemptcount);
        $this->assertSame(2, $transport->attempts);
        $this->assertCount(1, $transport->messages);
        $this->assertNull($aftersuccess->lasterror);
        $this->assertNotNull($aftersuccess->timesent);
    }

    public function test_all_payment_outcome_templates_render_for_guest_recipient(): void {
        $registry = CommerceMailRuntime::template_registry();

        foreach ([
            CommerceMailType::PAYMENT_PENDING,
            CommerceMailType::PAYMENT_FAILED,
            CommerceMailType::PAYMENT_CANCELLED,
        ] as $type) {
            $message = $registry->get($type)->render(new CommerceMailRequest(
                $type,
                new CommerceMailRecipient('guest@example.test', 'Guest CampusFR'),
                new CommerceMailContext([
                    'customer' => ['firstname' => 'Nata'],
                    'purchase' => [
                        'reference' => 'CFR-2026-STATUS',
                        'totalformatted' => '39,00 €',
                    ],
                    'items' => [],
                    'payment' => [
                        'providerlabel' => 'Stripe',
                        'transactionreference' => 'pi_status',
                        'statuslabel' => $type,
                        'amountformatted' => '39,00 €',
                    ],
                    'links' => ['order' => 'https://example.test/order/status'],
                ]),
                'fr',
                CommerceMailIdempotencyKey::for_purchase(851, $type),
                851
            ));

            $this->assertSame('guest@example.test', $message->get_recipient()->get_email());
            $this->assertNull($message->get_recipient()->get_user_id());
            $this->assertNotSame('', trim($message->get_subject()));
            $this->assertStringContainsString('CFR-2026-STATUS', $message->get_html());
            $this->assertStringNotContainsString('cmp_', strip_tags($message->get_html()));
        }
    }
}
