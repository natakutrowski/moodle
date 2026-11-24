<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Three independently represented roles; no implicit grant to the payer.
 * The final rights holder is NOT inferred from a requested recipient or buyer.
 */
final class CommerceGiftRoles {
    public readonly string $buyeremail;
    public readonly ?string $recipientemail;

    public function __construct(
        public readonly ?int $buyeruserid,
        string $buyeremail,
        public readonly ?int $intendedrecipientuserid,
        ?string $recipientemail,
        public readonly ?int $rightsholderuserid = null
    ) {
        foreach ([$buyeruserid, $intendedrecipientuserid, $rightsholderuserid] as $userid) {
            if ($userid !== null && $userid <= 0) {
                throw new \InvalidArgumentException('A supplied Moodle user identifier must be positive.');
            }
        }
        $buyeremail = strtolower(trim($buyeremail));
        if (filter_var($buyeremail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('The buyer must have a valid email address.');
        }
        $recipientemail = $recipientemail === null ? null : strtolower(trim($recipientemail));
        if ($recipientemail !== null && filter_var($recipientemail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Invalid intended recipient email address.');
        }
        $this->buyeremail = $buyeremail;
        $this->recipientemail = $recipientemail;
    }
}
