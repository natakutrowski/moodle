<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\presentation;

defined('MOODLE_INTERNAL') || die();

/**
 * Presentation contexts supported by the Commerce UX layer.
 */
final class CommercePresentationContext {
    public const CLIENT = 'client';
    public const CRM = 'crm';
    public const DIAGNOSTIC = 'diagnostic';

    private const VALID_CONTEXTS = [
        self::CLIENT,
        self::CRM,
        self::DIAGNOSTIC,
    ];

    private function __construct() {
    }

    public static function normalise(string $context): string {
        return strtolower(trim($context));
    }

    public static function require_valid(string $context): string {
        $context = self::normalise($context);

        if (!in_array($context, self::VALID_CONTEXTS, true)) {
            throw new \coding_exception('Unsupported Commerce presentation context: ' . $context);
        }

        return $context;
    }

    public static function allows_technical_details(string $context): bool {
        return self::require_valid($context) === self::DIAGNOSTIC;
    }

    /**
     * @return string[]
     */
    public static function all(): array {
        return self::VALID_CONTEXTS;
    }
}
