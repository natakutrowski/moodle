<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Public-safe upgrade projection shared by the Storefront and recommendations. */
final class CommerceStorefrontUpgrade {
    public function __construct(
        private readonly int $amountminor,
        private readonly string $currency,
        private readonly string $fromlabel,
        private readonly string $tolabel,
        private readonly string $summary,
        private readonly int $fromplanid = 0,
        private readonly int $toplanid = 0
    ) {
    }

    public function get_amount_minor(): int { return $this->amountminor; }
    public function get_currency(): string { return $this->currency; }
    public function get_from_label(): string { return $this->fromlabel; }
    public function get_to_label(): string { return $this->tolabel; }
    public function get_summary(): string { return $this->summary; }
    public function get_from_plan_id(): int { return $this->fromplanid; }
    public function get_to_plan_id(): int { return $this->toplanid; }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'amountminor' => $this->amountminor,
            'currency' => $this->currency,
            'fromlabel' => $this->fromlabel,
            'tolabel' => $this->tolabel,
            'summary' => $this->summary,
            'fromplanid' => $this->fromplanid,
            'toplanid' => $this->toplanid,
        ];
    }
}
