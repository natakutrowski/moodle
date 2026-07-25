<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\legacy\repository\SubscriptionPurchaseRepository;
use moodle_database;

/** Legacy subscription source ordered by stable ascending identifiers. */
final class LegacySubscriptionPurchaseSource implements CommerceLegacyPurchaseSource {
    public function __construct(
        private readonly moodle_database $database,
        private readonly SubscriptionPurchaseRepository $repository = new SubscriptionPurchaseRepository()
    ) {
    }

    public function get_family(): string { return 'subscription'; }
    public function count(): int { return $this->database->count_records('user_subscription'); }

    public function get_ids(int $afterid = 0, int $limit = 100): array {
        $limit = max(1, min(1000, $limit));
        $records = $this->database->get_records_select(
            'user_subscription',
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
        return $this->repository->get_by_subscription_id($legacyid);
    }
}