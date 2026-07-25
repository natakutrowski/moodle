<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\read;

defined('MOODLE_INTERNAL') || die();

/** Supported I7 Commerce runtime read modes. */
final class CommerceRuntimeReadMode {
    public const LEGACY = 'legacy';
    public const SHADOW = 'shadow';
    public const NATIVE = 'native';
    public const AUTO = 'auto';

    public static function normalise(string $mode): string {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, self::all(), true)) {
            throw new \InvalidArgumentException('Unsupported Commerce runtime read mode: ' . $mode);
        }
        return $mode;
    }

    public static function is_valid(string $mode): bool {
        return in_array(strtolower(trim($mode)), self::all(), true);
    }

    public static function all(): array {
        return [self::LEGACY, self::SHADOW, self::NATIVE, self::AUTO];
    }
}
