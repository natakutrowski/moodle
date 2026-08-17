<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\pricing;

defined('MOODLE_INTERNAL') || die();

/**
 * Canonical temporary product-promotion pricing.
 *
 * The catalogue price is the regular price. During an active promotion the
 * configured promotional amount becomes authoritative for checkout and the
 * catalogue price is exposed as the crossed-out comparison price.
 *
 * New metadata contract:
 * metadata.pricing.promotions.<CURRENCY> = {
 *   saleamountminor: int,
 *   start: ?int,
 *   end: ?int
 * }
 *
 * The historical Storefront compare-price contract is still read as a
 * compatibility fallback until all data has been migrated.
 */
final class CommerceProductPromotionService {
    /**
     * @return array{
     *     amountminor:int,
     *     compareamountminor:int,
     *     discountpercentage:int,
     *     start:?int,
     *     end:?int,
     *     source:string
     * }|null
     */
    public function resolve(
        array $metadata,
        string $currency,
        int $regularamountminor,
        ?int $now = null
    ): ?array {
        if ($regularamountminor <= 0) {
            return null;
        }

        $currency = strtoupper(trim($currency));
        $now ??= time();

        $configured = $this->configured($metadata, $currency);
        if ($configured !== null) {
            $saleamountminor = (int)($configured['saleamountminor'] ?? 0);
            $start = $configured['start'] ?? null;
            $end = $configured['end'] ?? null;

            if (
                !$this->is_active_window($start, $end, $now)
                || $saleamountminor <= 0
                || $saleamountminor >= $regularamountminor
            ) {
                return null;
            }

            return [
                'amountminor' => $saleamountminor,
                'compareamountminor' => $regularamountminor,
                'discountpercentage' => $this->discount_percentage(
                    $regularamountminor,
                    $saleamountminor
                ),
                'start' => $start,
                'end' => $end,
                'source' => 'pricing',
            ];
        }

        // Backward compatibility with the old Storefront configuration:
        // current catalogue amount = sale price, compareamountminor = regular.
        $legacy = $this->legacy_configured($metadata, $currency);
        if ($legacy === null) {
            return null;
        }

        $compareamountminor = (int)($legacy['compareamountminor'] ?? 0);
        $start = $legacy['start'] ?? null;
        $end = $legacy['end'] ?? null;

        if (
            !$this->is_active_window($start, $end, $now)
            || $compareamountminor <= $regularamountminor
        ) {
            return null;
        }

        return [
            'amountminor' => $regularamountminor,
            'compareamountminor' => $compareamountminor,
            'discountpercentage' => $this->discount_percentage(
                $compareamountminor,
                $regularamountminor
            ),
            'start' => $start,
            'end' => $end,
            'source' => 'legacy_storefront',
        ];
    }

    /**
     * Returns the new canonical configured promotion, regardless of whether
     * its date window is currently active.
     *
     * @return array{saleamountminor:int,start:?int,end:?int}|null
     */
    public function configured(array $metadata, string $currency): ?array {
        $pricing = $metadata['pricing'] ?? [];
        if (is_string($pricing)) {
            $decoded = json_decode($pricing, true);
            $pricing = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($pricing)) {
            return null;
        }

        $promotions = $pricing['promotions'] ?? [];
        if (!is_array($promotions)) {
            return null;
        }

        $promotion = $promotions[strtoupper(trim($currency))] ?? null;
        if (!is_array($promotion)) {
            return null;
        }

        $saleamountminor = filter_var(
            $promotion['saleamountminor'] ?? null,
            FILTER_VALIDATE_INT
        );
        if ($saleamountminor === false || $saleamountminor <= 0) {
            return null;
        }

        return [
            'saleamountminor' => $saleamountminor,
            'start' => $this->timestamp($promotion['start'] ?? null),
            'end' => $this->timestamp($promotion['end'] ?? null),
        ];
    }

    /**
     * @return array{compareamountminor:int,start:?int,end:?int}|null
     */
    public function legacy_configured(
        array $metadata,
        string $currency
    ): ?array {
        $storefront = $metadata['storefront'] ?? [];
        if (is_string($storefront)) {
            $decoded = json_decode($storefront, true);
            $storefront = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($storefront)) {
            return null;
        }

        $merchandising = $storefront['merchandising'] ?? [];
        if (!is_array($merchandising)) {
            return null;
        }

        $promotions = $merchandising['promotions'] ?? [];
        if (!is_array($promotions)) {
            return null;
        }

        $promotion = $promotions[strtoupper(trim($currency))] ?? null;
        if (!is_array($promotion)) {
            return null;
        }

        $compareamountminor = filter_var(
            $promotion['compareamountminor'] ?? null,
            FILTER_VALIDATE_INT
        );
        if (
            $compareamountminor === false
            || $compareamountminor <= 0
        ) {
            return null;
        }

        return [
            'compareamountminor' => $compareamountminor,
            'start' => $this->timestamp($promotion['start'] ?? null),
            'end' => $this->timestamp($promotion['end'] ?? null),
        ];
    }

    public function with_promotion(
        array $metadata,
        string $currency,
        int $saleamountminor,
        ?int $start,
        ?int $end
    ): array {
        $currency = strtoupper(trim($currency));
        $pricing = $metadata['pricing'] ?? [];
        if (!is_array($pricing)) {
            $pricing = [];
        }
        $promotions = $pricing['promotions'] ?? [];
        if (!is_array($promotions)) {
            $promotions = [];
        }

        $promotions[$currency] = [
            'saleamountminor' => $saleamountminor,
            'start' => $start,
            'end' => $end,
        ];
        $pricing['promotions'] = $promotions;
        $metadata['pricing'] = $pricing;

        return $this->without_legacy_promotion($metadata, $currency);
    }

    public function without_promotion(
        array $metadata,
        string $currency
    ): array {
        $currency = strtoupper(trim($currency));
        $pricing = $metadata['pricing'] ?? [];
        if (is_array($pricing)) {
            $promotions = $pricing['promotions'] ?? [];
            if (is_array($promotions)) {
                unset($promotions[$currency]);
                if ($promotions === []) {
                    unset($pricing['promotions']);
                } else {
                    $pricing['promotions'] = $promotions;
                }
            }
            if ($pricing === []) {
                unset($metadata['pricing']);
            } else {
                $metadata['pricing'] = $pricing;
            }
        }

        return $this->without_legacy_promotion($metadata, $currency);
    }

    private function without_legacy_promotion(
        array $metadata,
        string $currency
    ): array {
        $storefront = $metadata['storefront'] ?? [];
        if (!is_array($storefront)) {
            return $metadata;
        }

        $merchandising = $storefront['merchandising'] ?? [];
        if (!is_array($merchandising)) {
            return $metadata;
        }

        $promotions = $merchandising['promotions'] ?? [];
        if (!is_array($promotions)) {
            return $metadata;
        }

        unset($promotions[$currency]);
        if ($promotions === []) {
            unset($merchandising['promotions']);
        } else {
            $merchandising['promotions'] = $promotions;
        }

        $storefront['merchandising'] = $merchandising;
        $metadata['storefront'] = $storefront;

        return $metadata;
    }

    private function is_active_window(
        ?int $start,
        ?int $end,
        int $now
    ): bool {
        return !($start !== null && $now < $start)
            && !($end !== null && $now > $end);
    }

    private function discount_percentage(
        int $regularamountminor,
        int $saleamountminor
    ): int {
        if ($regularamountminor <= 0) {
            return 0;
        }

        return max(
            0,
            min(
                99,
                (int)round(
                    (
                        1
                        - ($saleamountminor / $regularamountminor)
                    ) * 100
                )
            )
        );
    }

    private function timestamp(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = filter_var($value, FILTER_VALIDATE_INT);
        return $timestamp !== false && $timestamp > 0
            ? $timestamp
            : null;
    }
}
