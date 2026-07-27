<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\reporting;

defined('MOODLE_INTERNAL') || die();

/** Immutable aggregate statistics for persisted Commerce Shadow runs. */
final class CommerceShadowSummary {
    public function __construct(
        private readonly int $total,
        private readonly array $byclassification,
        private readonly array $bystatus,
        private readonly array $bysource,
        private readonly int $averagedurationms
    ) {
    }

    public function get_total(): int {
        return $this->total;
    }

    public function get_by_classification(): array {
        return $this->byclassification;
    }

    public function get_by_status(): array {
        return $this->bystatus;
    }

    public function get_by_source(): array {
        return $this->bysource;
    }

    public function get_average_duration_ms(): int {
        return $this->averagedurationms;
    }

    public function to_array(): array {
        return [
            'total' => $this->total,
            'byclassification' => $this->byclassification,
            'bystatus' => $this->bystatus,
            'bysource' => $this->bysource,
            'averagedurationms' => $this->averagedurationms,
        ];
    }
}
