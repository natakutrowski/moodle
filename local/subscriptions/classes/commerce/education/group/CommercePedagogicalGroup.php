<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\group;

defined('MOODLE_INTERNAL') || die();

/**
 * One promotion-specific pedagogical group backed by a distinct Moodle group.
 *
 * displayname is reusable between promotions; moodlegroupid is never reused
 * across promotions.
 */
final class CommercePedagogicalGroup {
    public function __construct(
        private readonly ?int $id,
        private readonly int $promotionid,
        private readonly int $moodlegroupid,
        private readonly string $displayname,
        private readonly int $position,
        private readonly ?int $tutorid,
        private readonly ?string $supportlang,
        private readonly ?string $telegramref,
        private readonly ?int $levelupxp,
        private readonly bool $active,
        private readonly ?int $createdby,
        private readonly ?int $modifiedby,
        private readonly int $timecreated,
        private readonly int $timemodified,
        private readonly ?string $tutorname = null,
        private readonly ?int $productid = null
    ) {
        if ($promotionid <= 0 || $moodlegroupid <= 0) {
            throw new \coding_exception('Pedagogical group requires promotion and Moodle group ids.');
        }
        if (trim($displayname) === '') {
            throw new \coding_exception('Pedagogical group requires a visible name.');
        }
        if ($position < 0) {
            throw new \coding_exception('Pedagogical group position cannot be negative.');
        }
        if ($levelupxp !== null && $levelupxp < 0) {
            throw new \coding_exception('LevelUp XP cannot be negative.');
        }
    }

    public function get_id(): ?int { return $this->id; }
    public function get_promotion_id(): int { return $this->promotionid; }
    public function get_moodle_group_id(): int { return $this->moodlegroupid; }
    public function get_display_name(): string { return $this->displayname; }
    public function get_position(): int { return $this->position; }
    public function get_tutor_id(): ?int { return $this->tutorid; }
    public function get_tutor_name(): ?string { return $this->tutorname; }
    public function get_product_id(): ?int { return $this->productid; }
    public function get_support_language(): ?string { return $this->supportlang; }
    public function get_telegram_reference(): ?string { return $this->telegramref; }
    public function get_levelup_xp(): ?int { return $this->levelupxp; }
    public function is_active(): bool { return $this->active; }
    public function get_created_by(): ?int { return $this->createdby; }
    public function get_modified_by(): ?int { return $this->modifiedby; }
    public function get_time_created(): int { return $this->timecreated; }
    public function get_time_modified(): int { return $this->timemodified; }
}
