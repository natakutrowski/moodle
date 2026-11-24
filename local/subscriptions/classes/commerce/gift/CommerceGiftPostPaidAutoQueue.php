<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;

/**
 * Queues a gift only AFTER Native has durably marked the source purchase FULFILLED.
 * Never sends mail, grants rights, retries failed deliveries or reissues tokens.
 * Repeated payment webhooks are idempotent under the gift-delivery lock.
 */
final class CommerceGiftPostPaidAutoQueue {
    public function __construct(private readonly \moodle_database $db) {}

    public function queue_if_eligible(int $giftid): bool {
        if ($giftid <= 0 || !CommerceGiftPreparedAutoQueuePolicy::enabled()) { return false; }
        $factory = lock_config::get_lock_factory('local_subscriptions_commerce_gift_claim');
        $lock = $factory->get_lock('gift:' . $giftid, 10);
        if ($lock === false) { throw new \RuntimeException('Gift post-paid queue lock unavailable.'); }
        try {
            $tx = $this->db->start_delegated_transaction();
            try {
                $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
                    ['id' => $giftid, 'sourceproductsku' => 'AVENT_2026'], '*', IGNORE_MISSING);
                $delivery = $this->db->get_record(CommerceGiftDeliveryDraftService::TABLE,
                    ['giftid' => $giftid], '*', IGNORE_MISSING);
                $draft = $this->db->get_record(CommerceGiftPreparedCheckoutService::TABLE,
                    ['giftid' => $giftid, 'status' => 'gift_issued'], '*', IGNORE_MISSING);
                if (!$gift || !$delivery || !$draft || (string)$delivery->status !== 'draft'
                    || !CommerceGiftPreparedAutoQueuePolicy::should_queue(
                        $this->db, $gift, $draft, time())) {
                    $tx->allow_commit();
                    return false;
                }
                $now = time();
                $this->db->update_record(CommerceGiftDeliveryDraftService::TABLE, (object)[
                    'id' => (int)$delivery->id, 'status' => 'queued', 'queuedat' => $now,
                    'timemodified' => $now,
                ]);
                $this->db->insert_record(CommerceGiftSchema::TABLE_EVENT, (object)[
                    'giftid' => $giftid, 'eventtype' => 'delivery_queued', 'actoruserid' => null,
                    'occurredat' => $now,
                    'idempotencykey' => hash('sha256',
                        'campusfr:g5b4b3:autoqueue:v1:' . (string)$gift->giftuuid),
                    'detailjson' => '{"mode":"email","origin":"post_paid_completion"}',
                ]);
                $tx->allow_commit();
                return true;
            } catch (\Throwable $e) {
                $tx->rollback($e);
                throw $e;
            }
        } finally {
            $lock->release();
        }
    }
}
