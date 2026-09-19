<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\group;

defined('MOODLE_INTERNAL') || die();

/**
 * Optional group orchestration settings for one pedagogical promotion.
 */
final class CommercePedagogicalGroupConfiguration {
    public function __construct(
        private readonly int $promotionid,
        private readonly bool $enabled,
        private readonly int $groupsize,
        private readonly ?int $createdby,
        private readonly ?int $modifiedby,
        private readonly int $timecreated,
        private readonly int $timemodified
    ) {
        if ($promotionid <= 0) {
            throw new \coding_exception('Group configuration requires a promotion id.');
        }
        if ($groupsize <= 0) {
            throw new \coding_exception('Pedagogical group size must be positive.');
        }
    }

    public function get_promotion_id(): int { return $this->promotionid; }
    public function is_enabled(): bool { return $this->enabled; }
    public function get_group_size(): int { return $this->groupsize; }
    public function get_created_by(): ?int { return $this->createdby; }
    public function get_modified_by(): ?int { return $this->modifiedby; }
    public function get_time_created(): int { return $this->timecreated; }
    public function get_time_modified(): int { return $this->timemodified; }
}
