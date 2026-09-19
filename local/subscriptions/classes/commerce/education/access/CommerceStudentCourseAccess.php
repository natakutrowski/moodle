<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\access;

defined('MOODLE_INTERNAL') || die();

/**
 * Persisted individual pedagogical access relation for one student and course.
 *
 * Absence of a row is intentionally interpreted by the resolver as legacy_full
 * for an already enrolled user, preserving all pre-7.97 students.
 */
final class CommerceStudentCourseAccess {
    public function __construct(
        private readonly ?int $id,
        private readonly int $courseid,
        private readonly int $userid,
        private readonly ?int $promotionid,
        private readonly string $profile,
        private readonly ?int $createdby,
        private readonly ?int $modifiedby,
        private readonly int $timecreated,
        private readonly int $timemodified
    ) {
        if ($courseid <= 0) {
            throw new \coding_exception(
                'Student course access requires a valid course id.'
            );
        }
        if ($userid <= 0) {
            throw new \coding_exception(
                'Student course access requires a valid user id.'
            );
        }

        CommerceStudentAccessProfile::normalise($profile);

        if (
            $profile === CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE
            && $promotionid === null
        ) {
            throw new \coding_exception(
                'Progressive access requires a pedagogical promotion.'
            );
        }
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_course_id(): int {
        return $this->courseid;
    }

    public function get_user_id(): int {
        return $this->userid;
    }

    public function get_promotion_id(): ?int {
        return $this->promotionid;
    }

    public function get_profile(): string {
        return $this->profile;
    }

    public function get_created_by(): ?int {
        return $this->createdby;
    }

    public function get_modified_by(): ?int {
        return $this->modifiedby;
    }

    public function get_time_created(): int {
        return $this->timecreated;
    }

    public function get_time_modified(): int {
        return $this->timemodified;
    }

    public function with_profile(
        string $profile,
        ?int $modifiedby,
        int $timemodified
    ): self {
        return new self(
            $this->id,
            $this->courseid,
            $this->userid,
            $this->promotionid,
            CommerceStudentAccessProfile::normalise($profile),
            $this->createdby,
            $modifiedby,
            $this->timecreated,
            $timemodified
        );
    }
}
