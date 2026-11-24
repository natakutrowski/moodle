<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;
use local_subscriptions\commerce\mail\CommerceMailRecipient;

/** G4B2: DEV-gated delivery, no grant creation and no plaintext invitation stored. */
final class CommerceGiftDeliveryDispatchService {
    private const TABLE = CommerceGiftDeliveryDraftService::TABLE;
    private const PILOT_SKU = 'AVENT_2026';
    public const SWITCH = 'commerce_gifts_delivery_dev_enabled';
    public const LIMIT = 10;
    public const TTL = 30 * DAYSECS;

    public function __construct(private readonly \moodle_database $db) {}

    public static function enabled(): bool {
        return (new CommerceGiftFeatureToggle())->can_issue()
            && (CommerceGiftCommercialRuntimeGate::enabled()
                ? (string)get_config('local_subscriptions', CommerceGiftCommercialRuntimeGate::DELIVERY) === '1'
                : CommerceGiftDevE2EGate::is_dev_environment()
                    && CommerceGiftCommercialDevGate::can_operate()
                    && (string)get_config('local_subscriptions', self::SWITCH) === '1');
    }

    private static function require_enabled(): void {
        if (!self::enabled()) { throw new \DomainException('DEV gift delivery execution is disabled.'); }
    }

    private function lock(int $giftid): \core\lock\lock {
        $lock = lock_config::get_lock_factory('local_subscriptions_commerce_gift_claim')
            ->get_lock('gift:' . $giftid, 10);
        if (!$lock) { throw new \RuntimeException('Gift delivery lock unavailable.'); }
        return $lock;
    }

    private function gift_for_buyer(int $giftid, int $buyerid): \stdClass {
        self::require_enabled();
        CommerceGiftCommercialRuntimeGate::assert_buyer($this->db, $buyerid);
        $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
            ['id' => $giftid, 'buyeruserid' => $buyerid, 'sourceproductsku' => self::PILOT_SKU],
            '*', MUST_EXIST);
        $this->assert_unclaimed($gift);
        return $gift;
    }

    private function assert_unclaimed(\stdClass $gift): void {
        if (!in_array((string)$gift->status, ['issued', 'scheduled', 'notified', 'delivery_failed'], true)
            || $gift->claimedat !== null || $gift->activatedat !== null
            || $gift->rightsholderuserid !== null
            || ($gift->expiresat !== null && (int)$gift->expiresat <= time())
            || (string)$gift->sourceproductsku !== self::PILOT_SKU) {
            throw new \DomainException('Gift is not eligible for delivery.');
        }
        CommerceGiftActivationPolicy::reader_right($gift);
        (new CommerceGiftPaidSafety($this->db))->assert_still_paid($gift);
    }

    /**
     * Resolve a recipient using either the original confirmed G3D account or
     * ONE approved, still-unregistered DEV mailbox from the signed preparation.
     * This does not verify inbox ownership for registration (G5B.3B OTP does).
     */
    private function recipient(\stdClass $gift): CommerceMailRecipient {
        if ((string)$gift->deliverymode !== 'email'
            || !filter_var((string)$gift->intendedrecipientemail, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException('Gift is not an email delivery.');
        }
        $email = CommerceGiftDevGuestRecipientPolicy::normalize(
            strtolower(trim((string)$gift->intendedrecipientemail)));
        if ((CommerceGiftCommercialRuntimeGate::enabled() || CommerceGiftCommercialDevGate::is_armed())) {
            // A commercial gift must resolve only the beneficiary bound to its
            // paid and signed preparation; no reliance on the old G3D test account.
            $draft = $this->db->get_record(CommerceGiftPreparedCheckoutService::TABLE,
                ['giftid' => (int)$gift->id, 'status' => 'gift_issued'], '*', IGNORE_MISSING);
            $snapshot = json_decode((string)$gift->purchasesnapshotjson, true);
            if (!$draft || !is_array($snapshot)
                || (int)$draft->buyeruserid !== (int)$gift->buyeruserid
                || !hash_equals(strtolower(trim((string)$draft->buyeremail)),
                    strtolower(trim((string)$gift->buyeremail)))
                || (int)$draft->priceid !== (int)$gift->sourcepriceid
                || (string)$draft->deliverymode !== 'email'
                || (string)$draft->currency !== strtoupper((string)($snapshot['currency'] ?? ''))
                || (int)($draft->scheduledat ?? 0) !== (int)($gift->scheduledat ?? 0)
                || (int)$draft->expiresat !== (int)($gift->expiresat ?? 0)
                || !hash_equals($email, strtolower(trim((string)$draft->recipientemail)))) {
                throw new \DomainException('Commercial recipient is not bound to the paid gift.');
            }
            CommerceGiftCommercialRuntimeGate::assert_recipient($this->db,
                (int)$gift->buyeruserid, (string)$gift->buyeremail, $email);
            // assert_recipient rejects ambiguous, unconfirmed, suspended and
            // administrative identities. Empty means an unregistered mailbox.
            $matches = $this->db->get_records_sql(
                'SELECT id, email, firstname, lastname, confirmed, suspended FROM {user} '
                    . 'WHERE deleted = 0 AND LOWER(email) = :mail',
                ['mail' => $email], 0, 2);
            if (count($matches) > 1) {
                throw new \DomainException('Commercial recipient identity is ambiguous.');
            }
            if ($matches) {
                $user = reset($matches);
                if ((int)$user->id <= 2 || is_siteadmin((int)$user->id)
                    || (int)$user->id === (int)$gift->buyeruserid
                    || empty($user->confirmed) || !empty($user->suspended)) {
                    throw new \DomainException('Commercial recipient account is not eligible.');
                }
                return new CommerceMailRecipient($email,
                    trim((string)$user->firstname . ' ' . (string)$user->lastname), (int)$user->id);
            }
            return new CommerceMailRecipient($email, trim((string)$draft->recipientname));
        }
        $recipientid = (int)get_config('local_subscriptions', CommerceGiftDevE2EGate::RECIPIENT_SETTING);
        if (!(CommerceGiftCommercialRuntimeGate::enabled() || CommerceGiftCommercialDevGate::is_armed())) {
            CommerceGiftDevE2EGate::assert_test_account($recipientid, 'recipient');
        }
        $user = $this->db->get_record('user', ['id' => $recipientid, 'deleted' => 0],
            'id,email,firstname,lastname,confirmed,suspended', IGNORE_MISSING);
        if ($user && !empty($user->confirmed) && empty($user->suspended)
            && !is_siteadmin($recipientid)
            && $recipientid !== (int)$gift->buyeruserid
            && hash_equals($email, strtolower(trim((string)$user->email)))) {
            return new CommerceMailRecipient($email,
                trim((string)$user->firstname . ' ' . (string)$user->lastname), $recipientid);
        }
        // No fallback to an arbitrary account: only the one paid, sealed,
        // prepared mailbox can be addressed, with the expiring DEV allowlist.
        $draft = $this->db->get_record(CommerceGiftPreparedCheckoutService::TABLE,
            ['giftid' => (int)$gift->id, 'status' => 'gift_issued'], '*', IGNORE_MISSING);
        if (!$draft || (int)$draft->buyeruserid !== (int)$gift->buyeruserid
            || (int)$draft->priceid !== (int)$gift->sourcepriceid
            || (string)$draft->deliverymode !== 'email'
            || (string)$draft->currency !== strtoupper((string)
                (json_decode((string)$gift->purchasesnapshotjson, true)['currency'] ?? ''))
            || (int)($draft->scheduledat ?? 0) !== (int)($gift->scheduledat ?? 0)
            || !hash_equals($email, strtolower(trim((string)$draft->recipientemail)))) {
            throw new \DomainException('Guest recipient does not match the paid preparation.');
        }
        if ((CommerceGiftCommercialRuntimeGate::enabled() || CommerceGiftCommercialDevGate::is_armed())) {
            CommerceGiftCommercialRuntimeGate::assert_recipient($this->db,
                (int)$gift->buyeruserid, (string)$gift->buyeremail, $email);
        } else {
            CommerceGiftDevGuestRecipientPolicy::assert_allowed($this->db,
                (int)$gift->buyeruserid, (string)$gift->buyeremail, $email, time());
        }
        return new CommerceMailRecipient($email, trim((string)$draft->recipientname));
    }

    /** Buyer authorises email queue; immutable gift.scheduledat defines actual due time. */
    public function queue_email(int $giftid, int $buyerid): void {
        self::require_enabled();
        $lock = $this->lock($giftid);
        try {
            $tx = $this->db->start_delegated_transaction();
            try {
                $gift = $this->gift_for_buyer($giftid, $buyerid);
                $this->recipient($gift);
                $delivery = $this->db->get_record(self::TABLE, ['giftid' => $giftid], '*', IGNORE_MISSING);
                $now = time();
                if ($delivery) {
                    if ((string)$delivery->status !== 'draft') {
                        throw new \DomainException('Delivery has already been queued.');
                    }
                    $this->db->update_record(self::TABLE, (object)[
                        'id' => (int)$delivery->id, 'status' => 'queued',
                        'queuedat' => $now, 'timemodified' => $now,
                    ]);
                } else {
                    $this->db->insert_record(self::TABLE, (object)[
                        'giftid' => $giftid, 'message' => '', 'locale' => 'fr',
                        'status' => 'queued', 'timecreated' => $now, 'timemodified' => $now,
                        'queuedat' => $now, 'sentat' => null, 'lastattemptat' => null,
                        'attempts' => 0, 'failurecode' => null,
                    ]);
                }
                // Never store secret, email body, or sender-provided recipient data in audit events.
                $this->db->insert_record(CommerceGiftSchema::TABLE_EVENT, (object)[
                    'giftid' => $giftid, 'eventtype' => 'delivery_queued',
                    'actoruserid' => $buyerid, 'occurredat' => $now,
                    'idempotencykey' => hash('sha256', 'campusfr:g4b2:queue:v1:' . $gift->giftuuid),
                    'detailjson' => '{"mode":"email"}',
                ]);
                $tx->allow_commit();
            } catch (\Throwable $e) { $tx->rollback($e); throw $e; }
        } finally { $lock->release(); }
    }

    /** Creates a one-time shareable code in memory; future-scheduled gifts are rejected by G3C. */
    public function issue_link(int $giftid, int $buyerid): string {
        self::require_enabled();
        $gift = $this->gift_for_buyer($giftid, $buyerid);
        if ((string)$gift->deliverymode !== 'link') {
            throw new \DomainException('This gift requires email delivery.');
        }
        // A link is never issued early, and G3C independently validates scheduledat.
        if ($gift->scheduledat !== null && (int)$gift->scheduledat > time()) {
            throw new \DomainException('This gift is scheduled for later.');
        }
        // Reserve the delivery slot *before* issuing the token. Do not hold the
        // gift lock while calling G3C, which acquires that same lock internally.
        $lock = $this->lock($giftid);
        try {
            $tx = $this->db->start_delegated_transaction();
            try {
                $delivery = $this->db->get_record(self::TABLE, ['giftid' => $giftid], '*', IGNORE_MISSING);
                $now = time();
                if ($delivery) {
                    if ((string)$delivery->status !== 'draft') {
                        throw new \DomainException('Gift code already prepared or delivery in progress.');
                    }
                    $this->db->update_record(self::TABLE, (object)[
                        'id' => (int)$delivery->id, 'status' => 'link_issuing', 'timemodified' => $now,
                    ]);
                } else {
                    $this->db->insert_record(self::TABLE, (object)[
                        'giftid' => $giftid, 'message' => '', 'locale' => 'fr',
                        'status' => 'link_issuing', 'timecreated' => $now, 'timemodified' => $now,
                        'queuedat' => null, 'sentat' => null, 'lastattemptat' => null,
                        'attempts' => 0, 'failurecode' => null,
                    ]);
                }
                $tx->allow_commit();
            } catch (\Throwable $e) { $tx->rollback($e); throw $e; }
        } finally { $lock->release(); }
        try {
            // The raw code exists in process memory only and is displayed once.
            $secret = (new CommerceGiftInvitationService($this->db))->issue($giftid, self::TTL);
            $lock = $this->lock($giftid);
            try {
                $tx = $this->db->start_delegated_transaction();
                try {
                    $delivery = $this->db->get_record(self::TABLE, ['giftid' => $giftid], '*', MUST_EXIST);
                    if ((string)$delivery->status !== 'link_issuing') {
                        throw new \DomainException('Gift link generation state changed.');
                    }
                    $now = time();
                    $this->db->update_record(self::TABLE, (object)[
                        'id' => (int)$delivery->id, 'status' => 'link_ready',
                        'sentat' => $now, 'timemodified' => $now,
                    ]);
                    $this->db->insert_record(CommerceGiftSchema::TABLE_EVENT, (object)[
                        'giftid' => $giftid, 'eventtype' => 'link_prepared',
                        'actoruserid' => $buyerid, 'occurredat' => $now,
                        'idempotencykey' => hash('sha256', 'campusfr:g4b2:link:v1:' . $gift->giftuuid),
                        'detailjson' => '{"mode":"link","delivered":false}',
                    ]);
                    $tx->allow_commit();
                } catch (\Throwable $e) { $tx->rollback($e); throw $e; }
            } finally { $lock->release(); }
            return $secret;
        } catch (\Throwable $e) {
            // A secret might have been generated; never attempt a blind retry.
            $lock = $this->lock($giftid);
            try {
                $delivery = $this->db->get_record(self::TABLE, ['giftid' => $giftid], '*', IGNORE_MISSING);
                if ($delivery && (string)$delivery->status === 'link_issuing') {
                    $this->db->update_record(self::TABLE, (object)[
                        'id' => (int)$delivery->id, 'status' => 'manual_review',
                        'failurecode' => 'link_uncertain', 'timemodified' => time(),
                    ]);
                }
            } finally { $lock->release(); }
            throw new \DomainException('Gift invitation requires manual review.');
        }
    }

    /** Process at most 10 queued gifts; safe to call repeatedly from Moodle cron. */
    public function process_due(): array {
        self::require_enabled();
        // Filter due dates in SQL: future-scheduled rows must not starve ready gifts.
        $sql = 'SELECT d.id, d.giftid, d.status FROM {' . self::TABLE . '} d ' .
            'JOIN {' . CommerceGiftSchema::TABLE_GIFT . '} g ON g.id = d.giftid ' .
            'WHERE d.status = :queued AND (g.scheduledat IS NULL OR g.scheduledat <= :due) ' .
            'ORDER BY d.id ASC';
        $due = $this->db->get_records_sql($sql, ['queued' => 'queued', 'due' => time()], 0, self::LIMIT);
        $result = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'review' => 0];
        foreach ($due as $record) {
            $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
                ['id' => (int)$record->giftid], '*', IGNORE_MISSING);
            if ($gift && $gift->scheduledat !== null && (int)$gift->scheduledat > time()) {
                continue;
            }
            $result['processed']++;
            try {
                $status = $this->dispatch_one((int)$record->giftid);
            } catch (\Throwable $e) {
                // Fail closed, do not keep retrying an ineligible paid/claimed gift.
                $lock = $this->lock((int)$record->giftid);
                try {
                    $current = $this->db->get_record(self::TABLE,
                        ['giftid' => (int)$record->giftid], '*', IGNORE_MISSING);
                    if ($current && (string)$current->status === 'queued') {
                        $this->db->update_record(self::TABLE, (object)[
                            'id' => (int)$current->id, 'status' => 'failed',
                            'failurecode' => 'preflight', 'timemodified' => time(),
                        ]);
                    }
                } finally { $lock->release(); }
                $status = 'failed';
            }
            if (array_key_exists($status, $result)) { $result[$status]++; }
        }
        return $result;
    }

    private function update_status(int $giftid, string $status, ?string $failurecode = null): void {
        $lock = $this->lock($giftid);
        try {
            $tx = $this->db->start_delegated_transaction();
            try {
                $record = $this->db->get_record(self::TABLE, ['giftid' => $giftid], '*', MUST_EXIST);
                if ((string)$record->status !== 'dispatching') {
                    $tx->allow_commit();
                    return;
                }
                $now = time();
                $fields = ['id' => (int)$record->id, 'status' => $status,
                    'failurecode' => $failurecode, 'timemodified' => $now];
                if ($status === 'sent') { $fields['sentat'] = $now; }
                $this->db->update_record(self::TABLE, (object)$fields);
                if ($status === 'sent') {
                    $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT, ['id' => $giftid], '*', MUST_EXIST);
                    if (in_array((string)$gift->status, ['issued', 'scheduled', 'delivery_failed'], true)) {
                        $this->db->update_record(CommerceGiftSchema::TABLE_GIFT, (object)[
                            'id' => $giftid, 'status' => CommerceGiftLifecycle::NOTIFIED,
                            'timemodified' => $now, 'rowversion' => (int)$gift->rowversion + 1,
                        ]);
                    }
                    $this->db->insert_record(CommerceGiftSchema::TABLE_EVENT, (object)[
                        'giftid' => $giftid, 'eventtype' => 'delivery_sent',
                        'actoruserid' => null, 'occurredat' => $now,
                        'idempotencykey' => hash('sha256', 'campusfr:g4b2:sent:v1:' . $gift->giftuuid),
                        'detailjson' => '{"mode":"email"}',
                    ]);
                }
                $tx->allow_commit();
            } catch (\Throwable $e) { $tx->rollback($e); throw $e; }
        } finally { $lock->release(); }
    }

    /** An uncertain result is never retried automatically (e-mail could have left the server). */
    public function dispatch_one(int $giftid): string {
        self::require_enabled();
        $lock = $this->lock($giftid);
        try {
            $tx = $this->db->start_delegated_transaction();
            try {
                $record = $this->db->get_record(self::TABLE, ['giftid' => $giftid], '*', IGNORE_MISSING);
                if (!$record || (string)$record->status !== 'queued') {
                    $tx->allow_commit();
                    return 'skipped';
                }
                $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT, ['id' => $giftid], '*', MUST_EXIST);
                if ($gift->scheduledat !== null && (int)$gift->scheduledat > time()) {
                    $tx->allow_commit();
                    return 'skipped';
                }
                $this->assert_unclaimed($gift);
                $this->recipient($gift);
                $now = time();
                $this->db->update_record(self::TABLE, (object)[
                    'id' => (int)$record->id, 'status' => 'dispatching',
                    'lastattemptat' => $now, 'attempts' => (int)$record->attempts + 1,
                    'timemodified' => $now,
                ]);
                $tx->allow_commit();
            } catch (\Throwable $e) { $tx->rollback($e); throw $e; }
        } finally { $lock->release(); }

        // No DB transaction around SMTP. Crash after sending leaves dispatching -> manual review.
        try {
            self::require_enabled();
            $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT, ['id' => $giftid], '*', MUST_EXIST);
            $this->assert_unclaimed($gift);
            $recipient = $this->recipient($gift);
            $record = $this->db->get_record(self::TABLE, ['giftid' => $giftid], '*', MUST_EXIST);
            $locale = CommerceGiftDeliveryDraftService::normalize_locale((string)$record->locale);
            // Validate the active Mail Studio template before creating the one-time code.
            (new CommerceGiftMailStudioInvitation($this->db))->assert_ready($locale);
            $code = (new CommerceGiftInvitationService($this->db))->issue($giftid, self::TTL);
        } catch (\Throwable $e) {
            $this->update_status($giftid, 'failed', 'preflight');
            return 'failed';
        }
        try {
            $locale = CommerceGiftDeliveryDraftService::normalize_locale((string)$record->locale);
            // The secret is provided directly to the Mail Studio renderer and
            // Commerce transport, never persisted in the general outbox JSON.
            self::require_enabled();
            $subject = (new CommerceGiftMailStudioInvitation($this->db))->send_to_recipient(
                $recipient, $locale, trim((string)$record->message), $code
            );
            // SMTP has succeeded. Journal failures must NEVER cause an SMTP retry.
            $this->update_status($giftid, 'sent');
            try {
                $mailrow = (new CommerceGiftCommerceMailJournal($this->db))->record_sent(
                    $giftid, $recipient, $locale, $subject
                );
                // Optional encrypted administrator escrow, only after SMTP + journal success.
                // A failed capture must not cause a duplicate delivery.
                try {
                    CommerceGiftAdminCodeEscrow::capture(
                        $giftid, (int)$mailrow->id, CommerceGiftAdminCodeEscrow::INVITATION, $code);
                } catch (\Throwable $escrowerror) {
                    debugging('Gift admin-code escrow unavailable for gift #' . $giftid, DEBUG_DEVELOPER);
                }
            } catch (\Throwable $journalerror) {
                // No addresses, codes or free-form exception bodies in logs.
                debugging('G6B6 gift mail journal unavailable for gift #' . $giftid, DEBUG_DEVELOPER);
            }
            return 'sent';
        } catch (\Throwable $e) {
            // Could have been sent before an exception; do not retry automatically.
            $this->update_status($giftid, 'manual_review', 'send_uncertain');
            return 'review';
        }
    }

    /** Safe read-only aggregate without recipient email, note, key or invitation. */
    public function audit(): array {
        $counts = [];
        foreach ($this->db->get_records_sql('SELECT status, COUNT(1) AS countrows FROM {' . self::TABLE . '} GROUP BY status') as $row) {
            $counts[(string)$row->status] = (int)$row->countrows;
        }
        return [
            'read_only' => true, 'environment' => CommerceGiftDevE2EGate::is_dev_environment() ? 'DEV' : 'not-dev',
            'execution_enabled' => self::enabled(), 'delivery_status_counts' => $counts,
        ];
    }
}
