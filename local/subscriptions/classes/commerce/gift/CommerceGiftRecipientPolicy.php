<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only claim precheck, not a grant or ownership change.
 * Email mode requires confirmed account with matching email.
 * Link mode requires confirmed account plus separately validated secret token.
 */
final class CommerceGiftRecipientPolicy {
    public const MODE_EMAIL = 'email';
    public const MODE_LINK = 'link';

    public static function validate_claim(
        string $mode,
        ?string $intendedemail,
        int $claimantuserid,
        string $claimantemail,
        bool $accountconfirmed,
        ?int $alreadyclaimeduserid = null
    ): void {
        if (!in_array($mode, [self::MODE_EMAIL, self::MODE_LINK], true)) {
            throw new \InvalidArgumentException('Unknown gift delivery mode.');
        }
        if ($claimantuserid <= 0 || !$accountconfirmed) {
            throw new \DomainException('A confirmed Moodle account is required to claim a gift.');
        }
        if ($alreadyclaimeduserid !== null && $alreadyclaimeduserid !== $claimantuserid) {
            throw new \DomainException('Gift is already assigned to another account.');
        }
        $claimantemail = strtolower(trim($claimantemail));
        if (filter_var($claimantemail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \DomainException('Claimant email is invalid.');
        }
        if ($mode === self::MODE_EMAIL) {
            $intendedemail = strtolower(trim((string)$intendedemail));
            if (filter_var($intendedemail, FILTER_VALIDATE_EMAIL) === false
                || !hash_equals($intendedemail, $claimantemail)) {
                throw new \DomainException('Invitation email does not match the confirmed account.');
            }
        }
        // Possession and one-time use of the link token is checked separately
        // by the future transactional redemption service (not delivered here).
    }
}
