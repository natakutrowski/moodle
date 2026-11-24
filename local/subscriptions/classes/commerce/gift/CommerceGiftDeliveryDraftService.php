<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;

/** DEV-only G4B1: buyer-owned message draft, never dispatches email or grants. */
final class CommerceGiftDeliveryDraftService {
    public const TABLE = 'local_subs_commerce_gift_delivery';
    public const MAX_CHARS = 500;
    public const LOCALES = ['fr', 'en', 'ru'];

    public function __construct(private readonly \moodle_database $db) {}

    public static function normalize_note(string $message): string {
        if (!preg_match('//u', $message)) {
            throw new \InvalidArgumentException('Invalid message encoding.');
        }
        $message = str_replace(["\r\n", "\r"], "\n", trim($message));
        // Plain-text note only: reject control characters other than newlines and tabs.
        if (preg_match('/[\x00-\x08\x0B-\x1F\x7F]/', $message)
            || \core_text::strlen($message) > self::MAX_CHARS) {
            throw new \InvalidArgumentException('Message is too long or contains invalid characters.');
        }
        return $message;
    }

    public static function normalize_locale(string $locale): string {
        if (!in_array($locale, self::LOCALES, true)) {
            throw new \InvalidArgumentException('Unsupported gift communication locale.');
        }
        return $locale;
    }

    public function for_buyer(int $giftid, int $buyerid): ?\stdClass {
        if ($giftid < 1 || $buyerid <= 2) { return null; }
        if (!$this->db->record_exists(CommerceGiftSchema::TABLE_GIFT,
            ['id' => $giftid, 'buyeruserid' => $buyerid, 'sourceproductsku' => 'AVENT_2026'])) {
            return null;
        }
        return $this->db->get_record(self::TABLE, ['giftid' => $giftid], '*', IGNORE_MISSING) ?: null;
    }

    public function save(int $giftid, int $buyerid, string $message, string $locale): void {
        // This iteration is deliberately not enabled on PROD, even with a config flag.
        if (!CommerceGiftCommercialRuntimeGate::can_operate() || $giftid < 1 || $buyerid <= 2) {
            throw new \DomainException('Gift delivery editing is DEV only.');
        }
        $message = self::normalize_note($message);
        $locale = self::normalize_locale($locale);
        $lock = lock_config::get_lock_factory('local_subscriptions_commerce_gift_claim')
            ->get_lock('gift:' . $giftid, 10);
        if (!$lock) { throw new \RuntimeException('Gift editing lock unavailable.'); }
        try {
            $transaction = $this->db->start_delegated_transaction();
            try {
                $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
                    ['id' => $giftid, 'buyeruserid' => $buyerid, 'sourceproductsku' => 'AVENT_2026'],
                    '*', MUST_EXIST);
                if (!in_array((string)$gift->status, ['issued', 'scheduled', 'notified', 'delivery_failed'], true)
                    || $gift->claimedat !== null || $gift->activatedat !== null
                    || $gift->rightsholderuserid !== null
                    || ($gift->expiresat !== null && (int)$gift->expiresat <= time())
                    || !in_array((string)$gift->deliverymode, ['email', 'link'], true)
                    || ((string)$gift->deliverymode === 'email'
                        && !filter_var((string)$gift->intendedrecipientemail, FILTER_VALIDATE_EMAIL))
                    || ((string)$gift->deliverymode === 'link'
                        && $gift->intendedrecipientemail !== null)) {
                    throw new \DomainException('Gift cannot be customised at this stage.');
                }
                // Verify immutable signed beneficiary, source payment and refund status.
                (new CommerceGiftPaidSafety($this->db))->assert_still_paid($gift);
                $existing = $this->db->get_record(self::TABLE, ['giftid' => $giftid], '*', IGNORE_MISSING);
                $now = time();
                if ($existing) {
                    if ((string)$existing->status !== 'draft') {
                        throw new \DomainException('Delivery has already been committed.');
                    }
                    $this->db->update_record(self::TABLE, (object)[
                        'id' => (int)$existing->id, 'message' => $message,
                        'locale' => $locale, 'timemodified' => $now,
                    ]);
                } else {
                    $this->db->insert_record(self::TABLE, (object)[
                        'giftid' => $giftid, 'message' => $message,
                        'locale' => $locale, 'status' => 'draft',
                        'timecreated' => $now, 'timemodified' => $now,
                    ]);
                }
                // No message content, email or invitation token is written to the audit log.
                $transaction->allow_commit();
            } catch (\Throwable $e) { $transaction->rollback($e); throw $e; }
        } finally { $lock->release(); }
    }
}
