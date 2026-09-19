<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\currency;
use local_subscriptions\currency\Currency;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/filelib.php');

/** Free, API-keyless secondary FX source. Explicit admin refresh only. */
final class CommerceFrankfurterFxRateSource implements CommerceFxRateSourceInterface {
    public const ENDPOINT = 'https://api.frankfurter.dev/v2/rates';
    public function key(): string { return 'frankfurter'; }
    public function label(): string { return 'Frankfurter'; }

    public function refresh(string $basecurrency, array $targetcurrencies): CommerceFxRateRefreshResult {
        $basecurrency = Currency::sanitize($basecurrency);
        $targets = self::normalise_targets($basecurrency, $targetcurrencies);
        if ($basecurrency === '' || $targets === []) {
            return new CommerceFxRateRefreshResult('Frankfurter', $basecurrency, [], [], '');
        }
        $url = self::ENDPOINT . '?' . http_build_query([
            'base' => $basecurrency,
            'quotes' => implode(',', $targets),
        ], '', '&', PHP_QUERY_RFC3986);
        $curl = new \curl();
        $raw = $curl->get($url, [], ['CURLOPT_TIMEOUT' => 15, 'CURLOPT_CONNECTTIMEOUT' => 8]);
        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);
        if ($raw === false || $httpcode < 200 || $httpcode >= 300) {
            throw new \runtime_exception('Frankfurter FX request failed (HTTP ' . $httpcode . ').');
        }
        return self::from_json((string)$raw, $basecurrency, $targets);
    }

    /** @param string[] $targetcurrencies */
    public static function from_json(string $json, string $basecurrency, array $targetcurrencies): CommerceFxRateRefreshResult {
        $basecurrency = Currency::sanitize($basecurrency);
        $targets = self::normalise_targets($basecurrency, $targetcurrencies);
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \runtime_exception('Frankfurter returned invalid JSON.', 0, $e);
        }
        $rates = [];
        $referencedate = '';
        $rows = [];
        if (is_array($decoded) && array_is_list($decoded)) {
            $rows = $decoded;
        } else if (is_array($decoded['rates'] ?? null)) {
            if (array_is_list($decoded['rates'])) {
                $rows = $decoded['rates'];
            } else {
                foreach ($decoded['rates'] as $quote => $rate) {
                    $rows[] = ['base' => $basecurrency, 'quote' => $quote, 'rate' => $rate, 'date' => $decoded['date'] ?? ''];
                }
            }
        }
        foreach ($rows as $row) {
            if (!is_array($row)) { continue; }
            $quote = Currency::sanitize((string)($row['quote'] ?? $row['currency'] ?? ''));
            $rate = (float)($row['rate'] ?? 0);
            if ($quote === '' || !in_array($quote, $targets, true) || $rate <= 0) { continue; }
            $rates[$quote] = self::normalise_rate($rate);
            if ($referencedate === '') { $referencedate = trim((string)($row['date'] ?? '')); }
        }
        return new CommerceFxRateRefreshResult(
            'Frankfurter', $basecurrency, $rates,
            array_values(array_diff($targets, array_keys($rates))), $referencedate
        );
    }

    /** @param string[] $targetcurrencies @return string[] */
    private static function normalise_targets(string $basecurrency, array $targetcurrencies): array {
        $targets = [];
        foreach ($targetcurrencies as $rawcode) {
            $code = Currency::sanitize((string)$rawcode);
            if ($code !== '' && $code !== $basecurrency && !in_array($code, $targets, true)) { $targets[] = $code; }
        }
        return $targets;
    }
    private static function normalise_rate(float $rate): string {
        $value = rtrim(rtrim(number_format($rate, 8, '.', ''), '0'), '.');
        return $value === '' ? '0' : $value;
    }
}
