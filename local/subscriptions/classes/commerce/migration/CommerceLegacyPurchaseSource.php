<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;

/** Read-only source for Legacy Commerce purchases. */
interface CommerceLegacyPurchaseSource {
    public function get_family(): string;
    public function count(): int;
    /** @return int[] */
    public function get_ids(int $afterid = 0, int $limit = 100): array;
    public function get_by_id(int $legacyid): ?CommercePurchase;
}