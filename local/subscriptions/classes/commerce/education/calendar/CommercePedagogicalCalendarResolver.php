<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\calendar;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-side helper for deciding which calendar items are available at a time.
 *
 * Student profile enforcement is intentionally deferred to 7.97E.
 */
final class CommercePedagogicalCalendarResolver {
    public function __construct(
        private readonly CommercePedagogicalCalendarRepository $repository
    ) {
    }

    public static function create(
        ?\moodle_database $db = null
    ): self {
        return new self(
            CommercePedagogicalCalendarRepository::create($db)
        );
    }

    /** @return CommercePedagogicalCalendarItem[] */
    public function unlocked(
        int $promotionid,
        int $timestamp
    ): array {
        return array_values(
            array_filter(
                $this->repository->for_promotion($promotionid),
                static fn(
                    CommercePedagogicalCalendarItem $item
                ): bool => $item->is_unlocked_at($timestamp)
            )
        );
    }

    /** @return CommercePedagogicalCalendarItem[] */
    public function locked(
        int $promotionid,
        int $timestamp
    ): array {
        return array_values(
            array_filter(
                $this->repository->for_promotion($promotionid),
                static fn(
                    CommercePedagogicalCalendarItem $item
                ): bool => !$item->is_unlocked_at($timestamp)
            )
        );
    }

    public function is_complete_at(
        int $promotionid,
        int $timestamp
    ): bool {
        $items =
            $this->repository->for_promotion($promotionid);

        if ($items === []) {
            return false;
        }

        foreach ($items as $item) {
            if (!$item->is_unlocked_at($timestamp)) {
                return false;
            }
        }

        return true;
    }
}
