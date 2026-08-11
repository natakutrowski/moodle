<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\merchandising;

defined('MOODLE_INTERNAL') || die();

/** Normalises product metadata into the strict Storefront merchandising contract. */
final class CommerceStorefrontMerchandisingResolver {
    public const DEFAULT_DISPLAY_ORDER = 1000;

    public const BADGES = [
        'new',
        'bestseller',
        'popular',
        'limited_offer',
        'gustave_choice',
        'premium',
        'lifetime_access',
        'complete_course',
    ];

    public function resolve(array $metadata): CommerceStorefrontMerchandising {
        $storefront = $metadata['storefront'] ?? [];
        if (is_string($storefront)) {
            $decoded = json_decode($storefront, true);
            $storefront = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($storefront)) {
            $storefront = [];
        }

        $configuration = $storefront['merchandising'] ?? [];
        if (!is_array($configuration)) {
            $configuration = [];
        }

        $badges = [];
        foreach ((array)($configuration['badges'] ?? []) as $badge) {
            $badge = strtolower(trim((string)$badge));
            if (in_array($badge, self::BADGES, true) && !in_array($badge, $badges, true)) {
                $badges[] = $badge;
            }
        }

        $displayorder = filter_var(
            $configuration['displayorder'] ?? self::DEFAULT_DISPLAY_ORDER,
            FILTER_VALIDATE_INT
        );
        if ($displayorder === false) {
            $displayorder = self::DEFAULT_DISPLAY_ORDER;
        }
        $displayorder = max(0, min(999999, $displayorder));

        $promotions = [];
        foreach ((array)($configuration['promotions'] ?? []) as $currency => $promotion) {
            $currency = strtoupper(trim((string)$currency));
            if (preg_match('/^[A-Z]{3}$/', $currency) !== 1 || !is_array($promotion)) {
                continue;
            }

            $compareamountminor = filter_var($promotion['compareamountminor'] ?? null, FILTER_VALIDATE_INT);
            if ($compareamountminor === false || $compareamountminor <= 0) {
                continue;
            }

            $promotions[$currency] = [
                'compareamountminor' => $compareamountminor,
                'start' => $this->timestamp($promotion['start'] ?? null),
                'end' => $this->timestamp($promotion['end'] ?? null),
            ];
        }

        return new CommerceStorefrontMerchandising(
            !empty($configuration['featured']),
            $displayorder,
            $badges,
            $promotions
        );
    }

    private function timestamp(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }
        $timestamp = filter_var($value, FILTER_VALIDATE_INT);
        return $timestamp !== false && $timestamp > 0 ? $timestamp : null;
    }
}
