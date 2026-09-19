<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\access;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable effective pedagogical access decision for one student/course.
 */
final class CommerceStudentCourseAccessDecision {
    /**
     * @param int[]|null $unlockedsectionids Null means unrestricted/full course.
     */
    public function __construct(
        private readonly string $profile,
        private readonly ?int $promotionid,
        private readonly ?array $unlockedsectionids
    ) {
        CommerceStudentAccessProfile::normalise($profile);
    }

    public function get_profile(): string {
        return $this->profile;
    }

    public function get_promotion_id(): ?int {
        return $this->promotionid;
    }

    public function has_full_course_access(): bool {
        return $this->unlockedsectionids === null;
    }

    /** @return int[]|null */
    public function get_unlocked_section_ids(): ?array {
        return $this->unlockedsectionids;
    }

    public function can_access_section(
        int $sectionid,
        int $sectionnumber
    ): bool {
        if ($this->has_full_course_access()) {
            return true;
        }

        // Moodle general section remains available for orientation/course intro.
        if ($sectionnumber === 0) {
            return true;
        }

        return in_array(
            $sectionid,
            $this->unlockedsectionids ?? [],
            true
        );
    }
}
