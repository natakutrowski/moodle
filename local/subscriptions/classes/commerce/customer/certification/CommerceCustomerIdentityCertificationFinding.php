<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\certification;

defined('MOODLE_INTERNAL') || die();

/** Immutable finding produced by the Commerce customer identity certification. */
final class CommerceCustomerIdentityCertificationFinding {
    public const OK = 'ok';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    public function __construct(
        public readonly string $key,
        public readonly string $severity,
        public readonly string $label,
        public readonly string $detail = ''
    ) {
        if (!in_array($severity, [self::OK, self::WARNING, self::ERROR], true)) {
            throw new \coding_exception('Invalid customer identity certification severity: ' . $severity);
        }
    }

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
