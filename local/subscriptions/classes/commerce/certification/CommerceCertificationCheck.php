<?php

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/** Immutable certification check result. */
final class CommerceCertificationCheck {
    public const PASS = 'PASS';
    public const WARNING = 'WARNING';
    public const FAIL = 'FAIL';

    public function __construct(
        private readonly string $section,
        private readonly string $code,
        private readonly string $status,
        private readonly string $message,
        private readonly array $context = []
    ) {
        if (!in_array($status, [self::PASS, self::WARNING, self::FAIL], true)) {
            throw new \coding_exception('Invalid Commerce certification status: ' . $status);
        }
    }

    public function section(): string { return $this->section; }
    public function code(): string { return $this->code; }
    public function status(): string { return $this->status; }
    public function message(): string { return $this->message; }
    public function context(): array { return $this->context; }

    public function to_array(): array {
        return [
            'section' => $this->section,
            'code' => $this->code,
            'status' => $this->status,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
