<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\certification;

defined('MOODLE_INTERNAL') || die();

/** One immutable finding produced by the Commerce mail certification audit. */
final class CommerceMailCertificationFinding {

    public const OK = 'ok';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    public function __construct(
        private readonly string $code,
        private readonly string $severity,
        private readonly string $label,
        private readonly string $detail = ''
    ) {
        if (!in_array($severity, [self::OK, self::WARNING, self::ERROR], true)) {
            throw new \coding_exception('Unsupported Commerce mail certification severity: ' . $severity);
        }
        if (trim($code) === '' || trim($label) === '') {
            throw new \coding_exception('A Commerce mail certification finding requires a code and label.');
        }
    }

    public function get_code(): string {
        return $this->code;
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

    /** @return array{code:string,severity:string,label:string,detail:string} */
    public function export(): array {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'label' => $this->label,
            'detail' => $this->detail,
        ];
    }
}
