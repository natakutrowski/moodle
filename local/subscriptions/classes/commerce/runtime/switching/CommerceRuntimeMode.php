<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\switching;

defined('MOODLE_INTERNAL') || die();

final class CommerceRuntimeMode {
    public const LEGACY = 'legacy';
    public const SHADOW = 'shadow';
    public const NATIVE = 'native';

    public static function normalize(?string $mode): string {
        $mode = strtolower(trim((string) $mode));
        return in_array($mode, self::all(), true) ? $mode : self::LEGACY;
    }

    public static function all(): array {
        return [self::LEGACY, self::SHADOW, self::NATIVE];
    }
}
