<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\security;

defined('MOODLE_INTERNAL') || die();

final class CommercePersonalOfferTokenRecord {
    public function __construct(
        private readonly int $id,
        private readonly int $offerid,
        private readonly int $tokenversion,
        private readonly string $tokenhash,
        private readonly string $issuancekey,
        private readonly string $requesthash
    ) {}

    public function get_id(): int { return $this->id; }
    public function get_offer_id(): int { return $this->offerid; }
    public function get_token_version(): int { return $this->tokenversion; }
    public function get_token_hash(): string { return $this->tokenhash; }
    public function get_issuance_key(): string { return $this->issuancekey; }
    public function get_request_hash(): string { return $this->requesthash; }
}
