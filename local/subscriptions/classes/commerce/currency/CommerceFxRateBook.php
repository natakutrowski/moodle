<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency;

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Administrative FX rate book used only to suggest catalogue prices.
 *
 * Rates never drive checkout amounts directly. Published catalogue prices remain
 * authoritative until an administrator explicitly saves them.
 */
final class CommerceFxRateBook {
    private const CONFIG_BASE = 'commerce_fx_base_currency';
    private const CONFIG_RATES = 'commerce_fx_rates_json';

    public function __construct(private readonly ?CommerceCurrencyRegistry $currencies = null) {
    }

    public function base_currency(): string {
        $registry = $this->currencies ?? new CommerceCurrencyRegistry();
        $configured = Currency::sanitize((string)get_config('local_subscriptions', self::CONFIG_BASE));
        if ($configured !== '' && $registry->is_enabled($configured)) {
            return $configured;
        }
        $enabled = $registry->enabled();
        return $enabled[0] ?? 'EUR';
    }

    /** @return array<string,array{rate:string,source:string,updatedat:int,referencedate:string}> */
    public function rates(): array {
        $raw = trim((string)get_config('local_subscriptions', self::CONFIG_RATES));
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $rates = [];
        foreach ($decoded as $code => $entry) {
            $code = Currency::sanitize((string)$code);
            if ($code === '' || !is_array($entry)) {
                continue;
            }
            $rate = self::normalise_rate((string)($entry['rate'] ?? ''));
            if ($rate === null) {
                continue;
            }
            $rates[$code] = [
                'rate' => $rate,
                'source' => trim((string)($entry['source'] ?? 'manual')) ?: 'manual',
                'updatedat' => max(0, (int)($entry['updatedat'] ?? 0)),
                'referencedate' => self::normalise_reference_date(
                    (string)($entry['referencedate'] ?? '')
                ),
            ];
        }
        return $rates;
    }

    /**
     * Save the rate book. One unit of the base currency equals RATE units of target currency.
     *
     * @param array<string,string|int|float> $rates
     */
    public function save(string $basecurrency, array $rates, string $source = 'manual'): void {
        $registry = $this->currencies ?? new CommerceCurrencyRegistry();
        $basecurrency = $registry->require_enabled($basecurrency);
        $now = time();
        $payload = [];
        foreach ($registry->enabled() as $code) {
            if ($code === $basecurrency || !array_key_exists($code, $rates)) {
                continue;
            }
            $rate = self::normalise_rate((string)$rates[$code]);
            if ($rate === null) {
                continue;
            }
            $payload[$code] = [
                'rate' => $rate,
                'source' => trim($source) !== '' ? trim($source) : 'manual',
                'updatedat' => $now,
                'referencedate' => '',
            ];
        }
        set_config(self::CONFIG_BASE, $basecurrency, 'local_subscriptions');
        set_config(self::CONFIG_RATES, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'local_subscriptions');
    }

    /**
     * Apply rates explicitly confirmed by an administrator.
     * Existing rates absent from the refresh are preserved (e.g. manual TND).
     *
     * @param array<string,string|int|float> $rates
     */
    /** @param array<string,array{rate:string,source:string,referencedate?:string}> $entries */
    public function apply_refresh_entries(string $basecurrency, array $entries, int $updatedat): void {
        $registry = $this->currencies ?? new CommerceCurrencyRegistry();
        $basecurrency = $registry->require_enabled($basecurrency);
        $payload = $this->base_currency() === $basecurrency ? $this->rates() : [];
        foreach ($entries as $rawcode => $entry) {
            if (!is_array($entry)) { continue; }
            $code = Currency::sanitize((string)$rawcode);
            if ($code === '' || $code === $basecurrency || !$registry->is_enabled($code)) { continue; }
            $rate = self::normalise_rate((string)($entry['rate'] ?? ''));
            if ($rate === null) { continue; }
            $payload[$code] = [
                'rate' => $rate,
                'source' => trim((string)($entry['source'] ?? '')) ?: 'manual',
                'updatedat' => max(0, $updatedat),
                'referencedate' => self::normalise_reference_date(
                    (string)($entry['referencedate'] ?? '')
                ),
            ];
        }
        set_config(self::CONFIG_BASE, $basecurrency, 'local_subscriptions');
        set_config(self::CONFIG_RATES, json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), 'local_subscriptions');
    }

    public function apply_refresh(string $basecurrency, array $rates, string $source, int $updatedat): void {
        $registry = $this->currencies ?? new CommerceCurrencyRegistry();
        $basecurrency = $registry->require_enabled($basecurrency);
        $payload = $this->base_currency() === $basecurrency ? $this->rates() : [];

        foreach ($rates as $rawcode => $rawrate) {
            $code = Currency::sanitize((string)$rawcode);
            if ($code === '' || $code === $basecurrency || !$registry->is_enabled($code)) {
                continue;
            }
            $rate = self::normalise_rate((string)$rawrate);
            if ($rate === null) {
                continue;
            }
            $payload[$code] = [
                'rate' => $rate,
                'source' => trim($source) !== '' ? trim($source) : 'manual',
                'updatedat' => max(0, $updatedat),
                'referencedate' => '',
            ];
        }
        set_config(self::CONFIG_BASE, $basecurrency, 'local_subscriptions');
        set_config(self::CONFIG_RATES, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'local_subscriptions');
    }

    public function rate_for(string $targetcurrency): ?string {
        $targetcurrency = Currency::sanitize($targetcurrency);
        if ($targetcurrency === $this->base_currency()) {
            return '1';
        }
        return $this->rates()[$targetcurrency]['rate'] ?? null;
    }

    private static function normalise_reference_date(string $date): string {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (
            !$parsed instanceof \DateTimeImmutable
            || $parsed->format('Y-m-d') !== $date
        ) {
            return '';
        }

        return $date;
    }

    private static function normalise_rate(string $rate): ?string {
        $rate = trim(str_replace(',', '.', $rate));
        if ($rate === '' || !preg_match('/^\d+(?:\.\d{1,8})?$/', $rate)) {
            return null;
        }

        // Only trim insignificant zeroes from the fractional part.
        // Integer rates such as 170 must remain 170, never 17.
        if (str_contains($rate, '.')) {
            $rate = rtrim(rtrim($rate, '0'), '.');
        }

        // Normalize leading zeroes without using float formatting, so we keep
        // the configured precision intact.
        [$integer, $fraction] = array_pad(explode('.', $rate, 2), 2, null);
        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;
        $rate = $fraction === null || $fraction === ''
            ? $integer
            : $integer . '.' . $fraction;

        if ((float)$rate <= 0) {
            return null;
        }
        return $rate;
    }
}
