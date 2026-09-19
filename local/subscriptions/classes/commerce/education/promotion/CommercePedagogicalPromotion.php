<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotion;

defined('MOODLE_INTERNAL') || die();

/**
 * One pedagogical promotion attached to one Moodle course.
 *
 * This is deliberately separate from commerce\promotion\domain\CommercePromotion,
 * which remains the commercial discount/pricing engine.
 */
final class CommercePedagogicalPromotion {
    public function __construct(
        private readonly ?int $id,
        private readonly string $promotionkey,
        private readonly string $name,
        private readonly int $courseid,
        private readonly string $status,
        private readonly bool $published,
        private readonly ?int $salesopensat,
        private readonly ?int $salesclosesat,
        private readonly ?int $startsat,
        private readonly ?int $endsat,
        private readonly ?int $capacitytotal,
        private readonly ?int $createdby,
        private readonly ?int $modifiedby,
        private readonly int $timecreated,
        private readonly int $timemodified
    ) {
        if ($courseid <= 0) {
            throw new \coding_exception(
                'Pedagogical promotion requires a valid Moodle course id.'
            );
        }

        $key = trim($promotionkey);
        if ($key === '' || !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $key)) {
            throw new \coding_exception(
                'Pedagogical promotion key must use lowercase letters, numbers, hyphens or underscores.'
            );
        }

        if (trim($name) === '') {
            throw new \coding_exception(
                'Pedagogical promotion requires a name.'
            );
        }

        CommercePedagogicalPromotionStatus::normalise($status);

        if (
            $salesopensat !== null
            && $salesclosesat !== null
            && $salesclosesat < $salesopensat
        ) {
            throw new \coding_exception(
                'Pedagogical promotion sales closing date cannot precede opening date.'
            );
        }

        if (
            $startsat !== null
            && $endsat !== null
            && $endsat < $startsat
        ) {
            throw new \coding_exception(
                'Pedagogical promotion end date cannot precede start date.'
            );
        }

        if ($capacitytotal !== null && $capacitytotal <= 0) {
            throw new \coding_exception(
                'Pedagogical promotion capacity must be positive or unlimited.'
            );
        }
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_promotion_key(): string {
        return $this->promotionkey;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_course_id(): int {
        return $this->courseid;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function is_published(): bool {
        return $this->published;
    }

    public function get_sales_opens_at(): ?int {
        return $this->salesopensat;
    }

    public function get_sales_closes_at(): ?int {
        return $this->salesclosesat;
    }

    public function get_starts_at(): ?int {
        return $this->startsat;
    }

    public function get_ends_at(): ?int {
        return $this->endsat;
    }

    public function get_capacity_total(): ?int {
        return $this->capacitytotal;
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

    public function sales_are_open(int $now): bool {
        if (!$this->published) {
            return false;
        }

        if (
            $this->salesopensat !== null
            && $now < $this->salesopensat
        ) {
            return false;
        }

        if (
            $this->salesclosesat !== null
            && $now > $this->salesclosesat
        ) {
            return false;
        }

        return in_array(
            $this->status,
            [
                CommercePedagogicalPromotionStatus::OPEN,
                CommercePedagogicalPromotionStatus::SCHEDULED,
                CommercePedagogicalPromotionStatus::STARTED,
            ],
            true
        );
    }
}
