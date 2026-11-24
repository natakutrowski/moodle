<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;

/**
 * Isolated G3A gift issuance. No current HTTP, checkout, webhook, or CLI path
 * invokes this writer. FeatureToggle::RUNTIME_WIRED is still false in G3A.
 * Issuance creates only gift + audit rows, never tokens or entitlement grants.
 */
final class CommerceGiftIssuanceService {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function issue_paid_unit(int $purchaseid, int $itemid): \stdClass {
        // Compile-time fail-closed switch inherited from G1. A misconfigured
        // admin flag cannot enable gift issuance before the integration lot.
        if (!(new CommerceGiftFeatureToggle())->can_issue()) {
            throw new \DomainException('Gift issuance runtime is not enabled.');
        }
        if ($purchaseid <= 0 || $itemid <= 0) {
            throw new \InvalidArgumentException('Native purchase and item IDs must be positive.');
        }
        $key = trim((string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex'));
        if (preg_match('/\A[a-f0-9]{64}\z/D', $key) !== 1) {
            throw new \DomainException('No server-owned gift checkout signing key is configured.');
        }
        $issuekey = CommerceGiftIssueIdentity::for_paid_unit($purchaseid, $itemid, 1);
        $factory = lock_config::get_lock_factory('local_subscriptions_commerce_gift_issue');
        $lock = $factory->get_lock('unit:' . $issuekey, 10);
        if ($lock === false) {
            throw new \RuntimeException('Gift issuance lock is unavailable.');
        }
        $tx = null;
        try {
            $tx = $this->db->start_delegated_transaction();
            $existing = $this->db->get_record(
                CommerceGiftSchema::TABLE_GIFT, ['issuekey' => $issuekey], '*', IGNORE_MISSING
            );
            if ($existing) {
                if ((int)$existing->purchaseid !== $purchaseid
                    || (int)$existing->purchaseitemid !== $itemid
                    || (int)$existing->unitindex !== 1) {
                    throw new \DomainException('Gift issuance identity collision.');
                }
                $tx->allow_commit();
                return $existing; // Durable idempotent replay, no event or rights creation.
            }
            $candidate = (new CommerceGiftPaidUnitInspector($this->db))->inspect(
                $purchaseid, $itemid, $key
            );
            $purchase = $candidate['purchase'];
            $item = $candidate['item'];
            $intent = $candidate['intent'];
            $now = time();
            if ($intent['expiresat'] !== null && $intent['expiresat'] <= $now) {
                throw new \DomainException('Cannot issue a gift whose activation window is already over.');
            }
            $buyeremail = strtolower(trim((string)$purchase->customeremail));
            $buyeruserid = (int)($purchase->userid ?? 0);
            $snapshot = [
                'version' => 1,
                'purchasereference' => (string)$purchase->reference,
                'itemposition' => (int)$item->position,
                'currency' => strtoupper((string)$item->currency),
                'netminor' => (int)$item->netminor,
                'priceid' => $candidate['priceid'],
                'paymentid' => $candidate['paymentid'],
                'intentdigest' => hash('sha256', json_encode($intent, JSON_THROW_ON_ERROR)),
            ];
            $record = (object)[
                'giftuuid' => bin2hex(random_bytes(16)),
                'issuekey' => $issuekey,
                'purchaseid' => $purchaseid,
                'purchaseitemid' => $itemid,
                'unitindex' => 1,
                'buyeruserid' => $buyeruserid > 0 ? $buyeruserid : null,
                'buyeremail' => $buyeremail,
                'sourceproductsku' => 'AVENT_2026',
                'sourcepriceid' => $candidate['priceid'],
                'sourcetype' => (string)$item->itemtype,
                'rightssnapshotjson' => json_encode($candidate['rights'], JSON_THROW_ON_ERROR),
                'purchasesnapshotjson' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                'deliverymode' => $intent['deliverymode'],
                'intendedrecipientuserid' => null,
                'intendedrecipientemail' => $intent['recipientemail'],
                'rightsholderuserid' => null,
                'status' => CommerceGiftLifecycle::ISSUED,
                'scheduledat' => $intent['scheduledat'],
                'expiresat' => $intent['expiresat'],
                'claimedat' => null,
                'activatedat' => null,
                'timecreated' => $now,
                'timemodified' => $now,
                'rowversion' => 1,
            ];
            $record->id = (int)$this->db->insert_record(CommerceGiftSchema::TABLE_GIFT, $record);
            $this->db->insert_record(CommerceGiftSchema::TABLE_EVENT, (object)[
                'giftid' => $record->id,
                'eventtype' => 'issued',
                'actoruserid' => null,
                'occurredat' => $now,
                'idempotencykey' => hash('sha256', 'campusfr:gift:issued:v1:' . $issuekey),
                'detailjson' => '{"source":"native_paid_checkout","pilot":"reader"}',
            ]);
            // G5B2: attach the immutable pre-checkout message/locale in the same
            // transaction. Ordinary G3D pilots have no prepared row and are unchanged.
            (new CommerceGiftPreparedCheckoutService($this->db))->attach_paid_gift(
                $record, $purchase, $intent
            );
            $tx->allow_commit();
            return $record;
        } catch (\Throwable $exception) {
            if ($tx !== null) {
                $tx->rollback($exception);
            }
            throw $exception;
        } finally {
            $lock->release();
        }
    }
}
