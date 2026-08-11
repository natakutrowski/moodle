<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\certification;

defined('MOODLE_INTERNAL') || die();

/** Immutable finding produced by the My Courses certification. */
final class CommerceMyCoursesCertificationFinding {
    public const OK = 'ok';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    public function __construct(
        private readonly string $severity,
        private readonly string $label,
        private readonly string $detail = ''
    ) {
        if (!in_array($severity, [self::OK, self::WARNING, self::ERROR], true)) {
            throw new \coding_exception('Invalid My Courses certification severity: ' . $severity);
        }
    }

    public function get_severity(): string {
        return $this->severity;
    }

    public function get_label(): string {
        return $this->label;
    }

    public function get_detail(): string {
        return $this->detail;
    }

    /** @return array{severity:string,label:string,detail:string} */
    public function export(): array {
        return [
            'severity' => $this->severity,
            'label' => $this->label,
            'detail' => $this->detail,
        ];
    }
}
