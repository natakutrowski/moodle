<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_transactional_mail_queue_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_queue_is_idempotent_and_persists_guest_context(): void {
        $repository = new CommerceMailQueueRepository();
        $service = new CommerceMailQueueService($repository);
        $request = $this->request('purchase:42:purchase_access');

        $first = $service->queue($request);
        $second = $service->queue($request);

        $this->assertSame((int)$first->id, (int)$second->id);
        $this->assertSame(CommerceMailStatus::QUEUED, $first->status);
        $this->assertSame('guest@example.test', $first->recipientemail);
        $this->assertNull($first->userid);
        $this->assertSame(['purchase' => ['reference' => 'CFR-42']], json_decode($first->contextjson, true));
    }

    public function test_processor_marks_message_sent(): void {
        $repository = new CommerceMailQueueRepository();
        $record = $repository->enqueue($this->request('purchase:43:purchase_access'));
        $transport = new queue_test_transport();
        $templates = new CommerceMailTemplateRegistry([new queue_test_template()]);
        $processor = new CommerceMailQueueProcessor(
            $repository,
            $templates,
            new CommerceMailDispatcher($templates, $transport),
            new CommerceMailRetryPolicy()
        );

        $result = $processor->process_due(10, (int)$record->nextruntime);
        $stored = $repository->find_by_id((int)$record->id);

        $this->assertSame(1, $result['sent']);
        $this->assertSame(1, $transport->sent);
        $this->assertSame(CommerceMailStatus::SENT, $stored->status);
        $this->assertSame('Transactional test', $stored->subject);
        $this->assertNotNull($stored->timesent);
    }

    public function test_processor_schedules_retry_then_fails_at_limit(): void {
        $repository = new CommerceMailQueueRepository();
        $record = $repository->enqueue($this->request('purchase:44:purchase_access'), 2);
        $transport = new queue_test_transport(true);
        $templates = new CommerceMailTemplateRegistry([new queue_test_template()]);
        $processor = new CommerceMailQueueProcessor(
            $repository,
            $templates,
            new CommerceMailDispatcher($templates, $transport),
            new CommerceMailRetryPolicy()
        );

        $first = $processor->process_due(10, (int)$record->nextruntime);
        $stored = $repository->find_by_id((int)$record->id);
        $this->assertSame(1, $first['retried']);
        $this->assertSame(CommerceMailStatus::QUEUED, $stored->status);
        $this->assertSame(1, (int)$stored->attemptcount);
        $this->assertGreaterThan((int)$record->nextruntime, (int)$stored->nextruntime);

        $second = $processor->process_due(10, (int)$stored->nextruntime);
        $stored = $repository->find_by_id((int)$record->id);
        $this->assertSame(1, $second['failed']);
        $this->assertSame(CommerceMailStatus::FAILED, $stored->status);
        $this->assertSame(2, (int)$stored->attemptcount);
        $this->assertStringContainsString('Transport failure', $stored->lasterror);
    }

    public function test_failed_message_can_be_reset_idempotently(): void {
        $repository = new CommerceMailQueueRepository();
        $record = $repository->enqueue($this->request('purchase:45:purchase_access'));
        $repository->mark_failed((int)$record->id, 'Failure');

        $this->assertTrue($repository->reset_failed((int)$record->id, 123456));
        $stored = $repository->find_by_id((int)$record->id);
        $this->assertSame(CommerceMailStatus::QUEUED, $stored->status);
        $this->assertSame(0, (int)$stored->attemptcount);
        $this->assertSame(123456, (int)$stored->nextruntime);
        $this->assertNull($stored->lasterror);
        $this->assertFalse($repository->reset_failed((int)$record->id));
    }

    public function test_unknown_template_is_left_queued_without_consuming_attempt(): void {
        $repository = new CommerceMailQueueRepository();
        $record = $repository->enqueue($this->request('purchase:46:purchase_access'));
        $templates = new CommerceMailTemplateRegistry();
        $processor = new CommerceMailQueueProcessor(
            $repository,
            $templates,
            new CommerceMailDispatcher($templates, new queue_test_transport()),
            new CommerceMailRetryPolicy()
        );

        $result = $processor->process_due(10, (int)$record->nextruntime);
        $stored = $repository->find_by_id((int)$record->id);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(CommerceMailStatus::QUEUED, $stored->status);
        $this->assertSame(0, (int)$stored->attemptcount);
    }

    private function request(string $key): CommerceMailRequest {
        return new CommerceMailRequest(
            CommerceMailType::PURCHASE_ACCESS,
            new CommerceMailRecipient('guest@example.test', 'Guest Customer'),
            new CommerceMailContext(['purchase' => ['reference' => 'CFR-42']]),
            'fr',
            $key,
            42
        );
    }
}

final class queue_test_template implements CommerceMailTemplate {
    public function get_type(): string {
        return CommerceMailType::PURCHASE_ACCESS;
    }

    public function render(CommerceMailRequest $request): CommerceMailMessage {
        return new CommerceMailMessage(
            $request->get_recipient(),
            'Transactional test',
            '<p>Test</p>',
            'Test'
        );
    }
}

final class queue_test_transport implements CommerceMailTransport {
    public int $sent = 0;

    public function __construct(private readonly bool $fail = false) {
    }

    public function send(CommerceMailMessage $message): void {
        $this->sent++;
        if ($this->fail) {
            throw new \RuntimeException('Transport failure');
        }
    }
}
