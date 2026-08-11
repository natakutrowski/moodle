<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\value\CommerceMoney;

/** One immutable discount line applied to a calculated cart. */
final class CommercePromotionAdjustment {
    public function __construct(
        private readonly ?int $promotionid,
        private readonly string $name,
        private readonly ?string $code,
        private readonly CommerceMoney $amount,
        private readonly bool $automatic
    ) {
        if ($amount->get_amount_minor() < 0) {
            throw new \coding_exception('A Commerce promotion adjustment cannot be negative.');
        }
    }

    public function get_promotion_id(): ?int { return $this->promotionid; }
    public function get_name(): string { return $this->name; }
    public function get_code(): ?string { return $this->code; }
    public function get_amount(): CommerceMoney { return $this->amount; }
    public function is_automatic(): bool { return $this->automatic; }
}
