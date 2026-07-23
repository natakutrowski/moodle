<?php

namespace local_subscriptions\crm\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of a CRM Commerce snapshot comparison.
 */
final class CrmCommerceSnapshotComparison {

    /** @var CrmCommerceSnapshotDifference[] */
    private array $differences = [];

    public function __construct(
        private readonly int $userid
    ) {
        if ($userid <= 0) {
            throw new \coding_exception(
                'A CRM Commerce comparison user identifier must be positive.'
            );
        }
    }

    public function get_user_id(): int {
        return $this->userid;
    }

    public function add_difference(
        CrmCommerceSnapshotDifference $difference
    ): void {
        $this->differences[] = $difference;
    }

    /**
     * @return CrmCommerceSnapshotDifference[]
     */
    public function get_differences(): array {
        return $this->differences;
    }

    public function get_difference_count(): int {
        return count($this->differences);
    }

    public function is_equivalent(): bool {
        return $this->differences === [];
    }

    public function to_array(): array {
        return [
            'userid' => $this->userid,
            'equivalent' => $this->is_equivalent(),
            'differences' => array_map(
                static fn(
                    CrmCommerceSnapshotDifference $difference
                ): array => $difference->to_array(),
                $this->differences
            ),
        ];
    }
}