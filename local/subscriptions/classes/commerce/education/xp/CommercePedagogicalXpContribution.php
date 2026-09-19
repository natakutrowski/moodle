<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable promotion-scoped XP contribution.
 *
 * This is deliberately separate from Level Up XP's personal course state.
 */
final class CommercePedagogicalXpContribution {
    public function __construct(
        private readonly ?int $id,
        private readonly int $promotionid,
        private readonly int $courseid,
        private readonly int $userid,
        private readonly string $sourcecomponent,
        private readonly string $sourcetype,
        private readonly string $sourcekey,
        private readonly string $sourcehash,
        private readonly int $points,
        private readonly int $timeearned,
        private readonly int $timecreated
    ) {
        if ($promotionid <= 0 || $courseid <= 0 || $userid <= 0) {
            throw new \coding_exception('Pedagogical XP contribution requires valid promotion, course and user ids.');
        }
        if (trim($sourcecomponent) === '' || trim($sourcetype) === '' || trim($sourcekey) === '') {
            throw new \coding_exception('Pedagogical XP contribution requires source provenance.');
        }
        if (!preg_match('/^[a-f0-9]{64}$/', $sourcehash)) {
            throw new \coding_exception('Pedagogical XP contribution source hash must be SHA-256.');
        }
        if ($points <= 0) {
            throw new \coding_exception('Pedagogical XP contribution points must be positive.');
        }
        if ($timeearned <= 0 || $timecreated <= 0) {
            throw new \coding_exception('Pedagogical XP contribution requires timestamps.');
        }
    }

    public function get_id(): ?int { return $this->id; }
    public function get_promotion_id(): int { return $this->promotionid; }
    public function get_course_id(): int { return $this->courseid; }
    public function get_user_id(): int { return $this->userid; }
    public function get_source_component(): string { return $this->sourcecomponent; }
    public function get_source_type(): string { return $this->sourcetype; }
    public function get_source_key(): string { return $this->sourcekey; }
    public function get_source_hash(): string { return $this->sourcehash; }
    public function get_points(): int { return $this->points; }
    public function get_time_earned(): int { return $this->timeearned; }
    public function get_time_created(): int { return $this->timecreated; }
}
