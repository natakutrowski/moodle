<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * G5B3A: read-only gift recipient entrypoint inspector.
 * A bearer token alone never creates a user, confirms an email or grants rights.
 * Not a replacement for an email-verification step in G5B3B.
 */
final class CommerceGiftRecipientOnboardingInspector {
    public function __construct(private readonly \moodle_database $db) {}

    /** @return array{giftid:int,email:string,account_exists:bool} */
    public function inspect(string $rawtoken): array {
        if (!CommerceGiftCommercialRuntimeGate::can_operate()) {
            throw new \DomainException('DEV invitation preview disabled.');
        }
        $hash = CommerceGiftToken::fingerprint($rawtoken);
        $token = $this->db->get_record(CommerceGiftSchema::TABLE_TOKEN,
            ['tokenhash' => $hash], '*', IGNORE_MISSING);
        if (!$token) {
            throw new \DomainException('Invalid invitation.');
        }
        $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
            ['id' => (int)$token->giftid], '*', MUST_EXIST);
        CommerceGiftActivationPolicy::assert_claimable($gift, $token, time());
        CommerceGiftActivationPolicy::reader_right($gift);
        (new CommerceGiftPaidSafety($this->db))->assert_still_paid($gift);
        if ((string)$gift->deliverymode !== CommerceGiftRecipientPolicy::MODE_EMAIL
            || !is_string($gift->intendedrecipientemail)
            || filter_var($gift->intendedrecipientemail, FILTER_VALIDATE_EMAIL) === false
            || (int)$gift->buyeruserid <= 2) {
            throw new \DomainException('Recipient enrollment is not eligible.');
        }
        $email = strtolower(trim((string)$gift->intendedrecipientemail));
        if (hash_equals($email, strtolower(trim((string)$gift->buyeremail)))) {
            throw new \DomainException('Buyer cannot claim the gift.');
        }
        // Include unconfirmed/suspended accounts: NEVER create a duplicate or
        // overwrite an existing Moodle identity. Lower-case comparison is needed
        // even if the DB collation is case-sensitive.
        $existing = $this->db->get_records_sql(
            'SELECT id FROM {user} WHERE deleted = 0 AND LOWER(email) = :recipientemail',
            ['recipientemail' => $email], 0, 1);
        return ['giftid' => (int)$gift->id, 'email' => $email,
            'account_exists' => !empty($existing)];
    }
}
