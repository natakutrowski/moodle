<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailType;

/**
 * Confirmed-delivery-only journal projection for gift signup verification OTPs.
 * It is never a queue request and never contains the code, URL or HTML body.
 */
final class CommerceGiftVerificationMailJournal {
    private const MAIL_TABLE = 'local_subs_commerce_mail';
    private const SIGNUP_TABLE = 'local_subs_commerce_gift_signup';

    public function __construct(private readonly \moodle_database $db) {}

    /** Strictly after SMTP accepted the message and signup status/sentat were persisted. */
    public function record_sent(int $giftid, string $recipientemail, string $lang, string $subject): \stdClass {
        if (!CommerceGiftCommercialRuntimeGate::can_operate()) {
            throw new \DomainException('Gift mail history runtime is unavailable.');
        }
        $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
            ['id' => $giftid, 'sourceproductsku' => 'AVENT_2026'], '*', MUST_EXIST);
        $otp = $this->db->get_record(self::SIGNUP_TABLE, ['giftid' => $giftid, 'status' => 'sent'],
            '*', MUST_EXIST);
        $email = strtolower(trim($recipientemail));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)
            || !hash_equals($email, strtolower(trim((string)$gift->intendedrecipientemail)))
            || (int)$gift->purchaseid <= 0 || (int)$otp->sentat <= 0
            || !in_array($lang, ['fr', 'en', 'ru'], true)
            || trim($subject) === '' || strlen($subject) > 500
            || (int)$otp->sendcount <= 0 || (int)$otp->windowstart <= 0) {
            throw new \DomainException('Gift verification delivery evidence incomplete.');
        }
        (new CommerceGiftPaidSafety($this->db))->assert_still_paid($gift);
        $key = sprintf('gift-verification:%d:%d:%d:%d',
            $giftid, (int)$otp->id, (int)$otp->windowstart, (int)$otp->sendcount);
        $existing = $this->db->get_record(self::MAIL_TABLE, ['idempotencykey' => $key],
            '*', IGNORE_MISSING);
        if ($existing) { return $existing; }
        $sent = (int)$otp->sentat;
        $record = (object)[
            'mailtype' => CommerceMailType::GIFT_VERIFICATION_OTP,
            'status' => CommerceMailStatus::SENT,
            'idempotencykey' => $key,
            'purchaseid' => (int)$gift->purchaseid,
            'userid' => null,
            'recipientemail' => $email,
            'recipientname' => null,
            'language' => $lang,
            'subject' => $subject,
            'contextjson' => json_encode(['gift_verification_history' => [
                'giftid' => $giftid,
                'productsku' => 'AVENT_2026',
                'content_redacted' => true,
                'transport' => 'direct_confirmed',
            ]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'attemptcount' => 1,
            'maxattempts' => 1,
            'nextruntime' => 0,
            'lasterror' => null,
            'timeprocessing' => null,
            'timesent' => $sent,
            'timecreated' => $sent,
            'timemodified' => time(),
        ];
        try {
            $record->id = $this->db->insert_record(self::MAIL_TABLE, $record);
            return $record;
        } catch (\dml_write_exception $e) {
            $existing = $this->db->get_record(self::MAIL_TABLE,
                ['idempotencykey' => $key], '*', IGNORE_MISSING);
            if ($existing) { return $existing; }
            throw $e;
        }
    }
}
