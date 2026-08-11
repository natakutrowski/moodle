<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;

final class CommercePersonalOfferIssueResult {
    public function __construct(
        private readonly CommercePersonalOffer $offer,
        private readonly string $token,
        private readonly bool $replayed
    ) {}
    public function get_offer(): CommercePersonalOffer { return $this->offer; }
    public function get_token(): string { return $this->token; }
    public function is_replayed(): bool { return $this->replayed; }
}
