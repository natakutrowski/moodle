<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\dto\CommercePaymentView;

final class CommercePaymentReadService {
    public function __construct(private readonly CommercePurchaseReadService $purchases) {
    }

    /** @return CommercePaymentView[] */
    public function find_by_legacy_purchase(string $family, int $legacyid): array {
        return $this->purchases->find_by_legacy_reference($family, $legacyid)?->payments ?? [];
    }
}
