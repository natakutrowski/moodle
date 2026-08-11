<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/** Immutable result of one final customer-journey certification check. */
final class CommerceCustomerJourneyCertificationFinding {
    public const OK = 'ok';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    public function __construct(
        private readonly string $key,
        private readonly string $severity,
        private readonly string $label,
        private readonly string $detail = ''
    ) {
        if (!in_array($severity, [self::OK, self::WARNING, self::ERROR], true)) {
            throw new \coding_exception('Invalid Commerce certification severity: ' . $severity);
        }
    }

    public function get_key(): string { return $this->key; }
    public function get_severity(): string { return $this->severity; }
    public function get_label(): string { return $this->label; }
    public function get_detail(): string { return $this->detail; }

    /** @return array{key:string,severity:string,label:string,detail:string} */
    public function export(): array {
        return [
            'key' => $this->key,
            'severity' => $this->severity,
            'label' => $this->label,
            'detail' => $this->detail,
        ];
    }
}
