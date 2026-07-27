<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/**
 * Reduces Shadow effect attributes to the business outcome shared by Legacy and Native runtimes.
 *
 * Implementation details remain available in the persisted raw effects, but they must not create
 * a business divergence when both runtimes grant the same resource to the same beneficiary.
 */
final class CommerceShadowBusinessEffectNormalizer {
    public function normalise(string $type, array $attributes): array {
        return match ($type) {
            'digital_download' => $this->normalise_digital_download($attributes),
            'course_access' => $this->normalise_course_access($attributes),
            default => $this->normalise_generic($attributes),
        };
    }

    private function normalise_digital_download(array $attributes): array {
        return [
            'active' => (bool)($attributes['active'] ?? true),
        ];
    }

    private function normalise_course_access(array $attributes): array {
        return [
            'accesslevel' => strtolower(trim((string)($attributes['accesslevel'] ?? 'full'))),
            'active' => (bool)($attributes['active'] ?? true),
        ];
    }

    private function normalise_generic(array $attributes): array {
        $ignored = [
            'status',
            'message',
            'action',
            'token',
            'downloadtoken',
            'executionreference',
            'idempotent',
        ];
        foreach ($ignored as $key) {
            unset($attributes[$key]);
        }
        ksort($attributes);
        return $attributes;
    }
}
