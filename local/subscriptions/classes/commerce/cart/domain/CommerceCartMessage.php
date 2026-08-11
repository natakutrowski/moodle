<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\domain;

defined('MOODLE_INTERNAL') || die();

/** Immutable business message emitted by cart operations or calculation. */
final class CommerceCartMessage {
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    public function __construct(
        private readonly string $code,
        private readonly string $level,
        private readonly array $context = []
    ) {
        if (!in_array($level, [self::LEVEL_NOTICE, self::LEVEL_WARNING, self::LEVEL_ERROR], true)) {
            throw new \coding_exception('Invalid Commerce cart message level.');
        }
        if (trim($code) === '') {
            throw new \coding_exception('A Commerce cart message requires a code.');
        }
    }

    public function get_code(): string {
        return $this->code;
    }

    public function get_level(): string {
        return $this->level;
    }

    public function get_context(): array {
        return $this->context;
    }
}
