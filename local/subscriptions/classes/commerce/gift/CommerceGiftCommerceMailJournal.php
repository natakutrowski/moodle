<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailType;

/**
 * G6B6: write a non-dispatchable Commerce history entry for a gift invitation
 * already marked sent. This is an audit projection, NOT an outbox request.
 * No invitation token, URL, personal note or rendered body is persisted.
 */
final class CommerceGiftCommerceMailJournal {
    private const TABLE = 'local_subs_commerce_mail';
    private const SKU = 'AVENT_2026';

    public function __construct(private readonly \moodle_database $db) {}

    public static function key(int $giftid): string {
        if ($giftid <= 0) { throw new \InvalidArgumentException('Invalid gift id.'); }
        return 'gift-invitation:' . $giftid . ':delivery';
    }

    public function row(int $giftid): ?\stdClass {
        return $this->db->get_record(self::TABLE, ['idempotencykey' => self::key($giftid)],
            '*', IGNORE_MISSING) ?: null;
    }

    /** Store only information already appropriate for the Commerce mail journal. */
    public function record_sent(int $giftid, CommerceMailRecipient $recipient, string $locale,
        string $subject, bool $historical = false): \stdClass {
        if (!CommerceGiftCommercialRuntimeGate::can_operate()) {
            throw new \DomainException('Gift mail journal is unavailable.');
        }
        $existing = $this->row($giftid);
        if ($existing) { return $existing; }
        $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
            ['id' => $giftid, 'sourceproductsku' => self::SKU], '*', MUST_EXIST);
        $delivery = $this->db->get_record(CommerceGiftDeliveryDraftService::TABLE,
            ['giftid' => $giftid, 'status' => 'sent'], '*', MUST_EXIST);
        if ((string)$gift->deliverymode !== 'email' || (int)$gift->purchaseid <= 0
            || (int)$delivery->sentat <= 0 || (int)$delivery->attempts <= 0
            || !hash_equals(strtolower(trim((string)$gift->intendedrecipientemail)),
                strtolower(trim($recipient->get_email())))
            || !in_array($locale, ['fr', 'en', 'ru'], true)
            || trim($subject) === '' || strlen($subject) > 500) {
            throw new \DomainException('Not a confirmed gift mail delivery.');
        }
        if (!$this->db->record_exists(CommerceGiftSchema::TABLE_EVENT,
                ['giftid' => $giftid, 'eventtype' => 'delivery_sent'])) {
            throw new \DomainException('Gift delivery event not confirmed.');
        }
        $stamp = (int)$delivery->sentat;
        $now = time();
        $record = (object)[
            'mailtype' => CommerceMailType::GIFT_INVITATION,
            'status' => CommerceMailStatus::SENT,
            'idempotencykey' => self::key($giftid),
            'purchaseid' => (int)$gift->purchaseid,
            'userid' => $recipient->get_user_id(),
            'recipientemail' => $recipient->get_email(),
            'recipientname' => $recipient->get_name(),
            'language' => $locale,
            'subject' => $subject,
            // Important: no code, claim URL, personal note, HTML body or full message.
            'contextjson' => json_encode(['gift_history' => [
                'giftid' => $giftid, 'productsku' => self::SKU,
                'origin' => 'direct_gift_delivery',
                'content_redacted' => true,
                'historical_subject_approximate' => $historical,
            ]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'attemptcount' => (int)$delivery->attempts,
            'maxattempts' => 1,
            'nextruntime' => 0,
            'lasterror' => null,
            'timeprocessing' => null,
            'timesent' => $stamp,
            'timecreated' => $stamp,
            'timemodified' => $now,
        ];
        try {
            $record->id = $this->db->insert_record(self::TABLE, $record);
            return $record;
        } catch (\dml_write_exception $e) {
            // Concurrent worker/backfill: unique idempotency key is authoritative.
            $existing = $this->row($giftid);
            if ($existing) { return $existing; }
            throw $e;
        }
    }

    /** One-off, explicitly requested reconstruction of a delivery already confirmed sent. */
    public function backfill_sent(int $giftid): \stdClass {
        if (!CommerceGiftDevE2EGate::is_dev_environment()) {
            throw new \DomainException('DEV-only backfill.');
        }
        $existing = $this->row($giftid);
        if ($existing) { return $existing; }
        $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT, ['id' => $giftid], '*', MUST_EXIST);
        $draft = $this->db->get_record(CommerceGiftPreparedCheckoutService::TABLE,
            ['giftid' => $giftid], '*', IGNORE_MISSING);
        $delivery = $this->db->get_record(CommerceGiftDeliveryDraftService::TABLE,
            ['giftid' => $giftid, 'status' => 'sent'], '*', MUST_EXIST);
        $email = strtolower(trim((string)$gift->intendedrecipientemail));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException('Gift destination missing.');
        }
        $uid = (int)($gift->intendedrecipientuserid ?? 0);
        if ($uid > 0) {
            $user = $this->db->get_record('user', ['id' => $uid, 'deleted' => 0], 'id,email', IGNORE_MISSING);
            if (!$user || !hash_equals($email, strtolower(trim((string)$user->email)))) { $uid = 0; }
        }
        $recipient = new CommerceMailRecipient($email, (string)($draft->recipientname ?? ''),
            $uid > 0 ? $uid : null);
        // Historical content is unknown: do not pretend to recover original subject or code.
        $approxsubject = match ((string)$delivery->locale) {
            'en' => 'CampusFR gift invitation — historical delivery',
            'ru' => 'Приглашение получить подарок CampusFR — история отправки',
            default => 'Invitation cadeau CampusFR — envoi historique',
        };
        return $this->record_sent($giftid, $recipient, (string)$delivery->locale,
            $approxsubject, true);
    }
}
