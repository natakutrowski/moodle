<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailAuditCopyPolicy;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailQueueProcessor;
use local_subscriptions\commerce\mail\CommerceMailQueueService;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\context\CommercePurchaseMailContextFactory;

/**
 * Persists and immediately attempts Native purchase transactional messages.
 * The outbox remains the source of truth and cron handles every retry.
 */
final class CommerceTransactionalPurchaseMailService {

    public function __construct(
        private readonly CommercePurchaseMailContextFactory $contexts,
        private readonly CommerceMailQueueService $queue,
        private readonly ?CommerceMailQueueProcessor $processor = null,
        private readonly ?CommerceMailAuditCopyPolicy $auditpolicy = null
    ) {
    }

    public static function create(): self {
        return new self(
            CommercePurchaseMailContextFactory::create(),
            CommerceMailRuntime::queue_service(),
            CommerceMailRuntime::processor(),
            new CommerceMailAuditCopyPolicy()
        );
    }

    /**
     * Payment-confirmed event: queue only the receipt and attempt it immediately.
     */
    public function deliver_payment_confirmed_purchase(string $reference): void {
        $this->deliver_safely($reference, CommerceMailType::PURCHASE_RECEIPT);
    }

    /**
     * Fulfillment-completed event: queue only the access message and attempt it immediately.
     */
    public function deliver_fulfilled_access(string $reference): void {
        $this->deliver_safely($reference, CommerceMailType::PURCHASE_ACCESS);
    }

    /**
     * Backward-compatible queue-only API retained for recovery tools and tests.
     *
     * @return array{access:\stdClass,receipt:\stdClass}
     */
    public function queue_fulfilled_purchase(string $reference): array {
        $mail = $this->contexts->build_by_reference($reference);
        return [
            'access' => $this->queue_customer_message($mail, CommerceMailType::PURCHASE_ACCESS),
            'receipt' => $this->queue_customer_message($mail, CommerceMailType::PURCHASE_RECEIPT),
        ];
    }

    /** @param array{purchaseid:int,recipient:CommerceMailRecipient,context:CommerceMailContext,language:string} $mail */
    private function queue_customer_message(array $mail, string $type): \stdClass {
        return $this->queue->queue(new CommerceMailRequest(
            $type,
            $mail['recipient'],
            $mail['context'],
            $mail['language'],
            CommerceMailIdempotencyKey::for_purchase($mail['purchaseid'], $type),
            $mail['purchaseid']
        ));
    }

    /**
     * @param array{purchaseid:int,recipient:CommerceMailRecipient,context:CommerceMailContext,language:string} $mail
     */
    private function queue_audit_copy(array $mail, string $type): ?\stdClass {
        $policy = $this->auditpolicy ?? new CommerceMailAuditCopyPolicy();
        if (!$policy->is_enabled_for($type)) {
            return null;
        }

        $context = $mail['context']->with('auditcopy', [
            'enabled' => true,
            'includeattachment' => $policy->include_attachment(),
            'originalrecipient' => [
                'email' => $mail['recipient']->get_email(),
                'name' => $mail['recipient']->get_name(),
            ],
        ]);

        return $this->queue->queue(new CommerceMailRequest(
            $type,
            new CommerceMailRecipient($policy->get_address(), 'CampusFR Mail Audit'),
            $context,
            $mail['language'],
            CommerceMailIdempotencyKey::for_purchase_audit_copy($mail['purchaseid'], $type),
            $mail['purchaseid']
        ));
    }

    private function deliver_safely(string $reference, string $type): void {
        try {
            $mail = $this->contexts->build_by_reference($reference);
            $customer = $this->queue_customer_message($mail, $type);

            // Audit copies are deliberately low priority. They are persisted in the same
            // auditable outbox but never consume the immediate customer-delivery lane.
            $this->queue_audit_copy($mail, $type);

            if ($this->processor !== null) {
                $this->processor->process_ids([(int)$customer->id]);
            }
        } catch (\Throwable $exception) {
            // A mail failure must never invalidate a confirmed payment or completed fulfillment.
            debugging(
                '[Commerce Mail] Immediate delivery could not be completed: ' . $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }
}
