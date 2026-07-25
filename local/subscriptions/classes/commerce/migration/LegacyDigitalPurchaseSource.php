<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\legacy\repository\DigitalPurchaseRepository;
use moodle_database;

/** Legacy digital purchase source ordered by stable ascending identifiers. */
final class LegacyDigitalPurchaseSource implements CommerceLegacyPurchaseSource {
    public function __construct(
        private readonly moodle_database $database,
        private readonly DigitalPurchaseRepository $repository = new DigitalPurchaseRepository()
    ) {
    }

    public function get_family(): string { return 'digital'; }
    public function count(): int { return $this->database->count_records('subscription_digital_payment_request'); }

    public function get_ids(int $afterid = 0, int $limit = 100): array {
        $limit = max(1, min(1000, $limit));
        $records = $this->database->get_records_select(
            'subscription_digital_payment_request',
            'id > :afterid',
            ['afterid' => max(0, $afterid)],
            'id ASC',
            'id',
            0,
            $limit
        );
        return array_map('intval', array_keys($records));
    }

    public function get_by_id(int $legacyid): ?CommercePurchase {
        return $this->repository->get_by_purchase_id($legacyid);
    }
}