<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

final class CommercePurchaseDetails {
    public function __construct(
        public readonly CommercePurchaseSummary $summary,
        public readonly array $items,
        public readonly array $payments,
        public readonly array $fulfillments,
        public readonly ?string $legacyfamily,
        public readonly ?int $legacyid,
        public readonly array $metadata,
        public readonly array $grants = [],
        public readonly array $fulfillmentattempts = []
    ) {
    }
}
