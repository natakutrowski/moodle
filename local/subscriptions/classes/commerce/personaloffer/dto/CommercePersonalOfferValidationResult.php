<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;

final class CommercePersonalOfferValidationResult {
    public const VALID = 'valid';
    public const INVALID = 'invalid';
    public const NOT_YET_VALID = 'not_yet_valid';
    public const EXPIRED = 'expired';
    public const REDEEMED = 'redeemed';
    public const REVOKED = 'revoked';

    public function __construct(private readonly string $status, private readonly ?CommercePersonalOffer $offer = null) {}
    public function get_status(): string { return $this->status; }
    public function get_offer(): ?CommercePersonalOffer { return $this->offer; }
    public function is_valid(): bool { return $this->status === self::VALID; }
}
