<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailQueueService;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;

final class commerce_mail_management_k10b_test extends advanced_testcase {
    public function test_resend_creates_a_new_auditable_queue_record(): void {
        $this->resetAfterTest(true);

        $repository = new CommerceMailQueueRepository();
        $queue = new CommerceMailQueueService($repository);
        $original = $queue->queue(new CommerceMailRequest(
            CommerceMailType::PURCHASE_RECEIPT,
            new CommerceMailRecipient('buyer@example.test', 'Buyer'),
            new CommerceMailContext(['purchase' => ['reference' => 'CFR-TEST']]),
            'fr',
            'k10b:original',
            123
        ));
        $repository->mark_sent((int)$original->id, 'Receipt', time());

        $resend = (new CommerceMailAdminService($repository))->resend((int)$original->id, 42);
        $persisted = $repository->find_by_id((int)$resend->id);
        $context = json_decode((string)$persisted->contextjson, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotSame((int)$original->id, (int)$persisted->id);
        $this->assertSame(CommerceMailStatus::QUEUED, $persisted->status);
        $this->assertSame('buyer@example.test', $persisted->recipientemail);
        $this->assertSame((int)$original->id, (int)$context['resend']['frommailid']);
        $this->assertSame(42, (int)$context['resend']['byuserid']);
    }

    public function test_audit_and_customer_due_rows_can_be_selected_separately(): void {
        $this->resetAfterTest(true);

        $repository = new CommerceMailQueueRepository();
        $queue = new CommerceMailQueueService($repository);
        $request = static function(string $key): CommerceMailRequest {
            return new CommerceMailRequest(
                CommerceMailType::PURCHASE_RECEIPT,
                new CommerceMailRecipient('buyer@example.test', 'Buyer'),
                new CommerceMailContext(['purchase' => ['reference' => 'CFR-TEST']]),
                'fr',
                $key,
                123
            );
        };

        $customer = $queue->queue($request('purchase:123:purchase_receipt'));
        $audit = $queue->queue($request('purchase:123:purchase_receipt:audit'));

        $customerrows = $repository->get_due(10, time(), null, [], false);
        $auditrows = $repository->get_due(10, time(), null, [], true);

        $this->assertSame([(int)$customer->id], array_map(static fn($row) => (int)$row->id, $customerrows));
        $this->assertSame([(int)$audit->id], array_map(static fn($row) => (int)$row->id, $auditrows));
        $this->assertTrue($repository->has_due_non_audit(time()));
    }

    public function test_audit_copy_is_not_part_of_immediate_customer_delivery(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/mail/service/CommerceTransactionalPurchaseMailService.php'
        );
        $mainworker = (string)file_get_contents(
            $root . '/classes/task/process_commerce_mail_queue_task.php'
        );
        $auditworker = (string)file_get_contents(
            $root . '/classes/task/process_commerce_mail_audit_queue_task.php'
        );

        $this->assertStringContainsString('$this->queue_audit_copy($mail, $type);', $service);
        $this->assertStringContainsString('process_ids([(int)$customer->id])', $service);
        $this->assertStringContainsString('CommerceMailType::PERSONAL_OFFER], false', $mainworker);
        $this->assertStringContainsString('has_due_non_audit', $auditworker);
        $this->assertStringContainsString('count_audit_sent_since', $auditworker);
    }
}
