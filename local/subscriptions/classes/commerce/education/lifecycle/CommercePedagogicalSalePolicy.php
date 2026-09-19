<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\lifecycle;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;

final class CommercePedagogicalSalePolicy {
    public function __construct(
        private readonly CommercePedagogicalCapacityService $capacity
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            CommercePedagogicalCapacityService::create($db)
        );
    }

    /**
     * No linked pedagogical offer means no 7.97 lifecycle restriction.
     */
    public function assert_product_available(
        string $productsku,
        int $now,
        ?string $cartuuid = null
    ): void {
        $snapshot = $this->capacity->for_product(
            $productsku,
            $now,
            $cartuuid
        );

        if ($snapshot->is_available()) {
            return;
        }

        $reason = $snapshot->get_blocking_reason();

        throw new CommercePedagogicalSaleException(
            $reason ?? CommercePedagogicalCapacityService::OFFER_FULL,
            match ($reason) {
                CommercePedagogicalCapacityService::SALES_CLOSED =>
                    'Sales are closed for this pedagogical promotion.',
                CommercePedagogicalCapacityService::PROMOTION_FULL =>
                    'This pedagogical promotion is full.',
                CommercePedagogicalCapacityService::OFFER_FULL =>
                    'This pedagogical offer is full.',
                CommercePedagogicalCapacityService::GROUP_FULL =>
                    'No pedagogical group has an available seat for this offer.',
                default =>
                    'This pedagogical offer is not available.',
            }
        );
    }
}
