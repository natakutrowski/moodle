<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\currency;

use local_subscriptions\currency\Currency;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Free ECB reference-rate source. Called only by an explicit administrator action.
 */
final class CommerceEcbFxRateSource implements CommerceFxRateSourceInterface {
    public const ENDPOINT = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    public function key(): string { return 'ecb'; }
    public function label(): string { return 'European Central Bank (ECB)'; }

    public function refresh(string $basecurrency, array $targetcurrencies): CommerceFxRateRefreshResult {
        $curl = new \curl();
        $raw = $curl->get(self::ENDPOINT, [], [
            'CURLOPT_TIMEOUT' => 15,
            'CURLOPT_CONNECTTIMEOUT' => 8,
        ]);
        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);
        if ($raw === false || $httpcode < 200 || $httpcode >= 300) {
            throw new \runtime_exception('ECB FX request failed (HTTP ' . $httpcode . ').');
        }
        return self::from_xml((string)$raw, $basecurrency, $targetcurrencies);
    }

    /** Public for deterministic TU with a fixture; no network is used by tests.
     * @param string[] $targetcurrencies
     */
    public static function from_xml(string $xml, string $basecurrency, array $targetcurrencies): CommerceFxRateRefreshResult {
        $basecurrency = Currency::sanitize($basecurrency);
        if ($basecurrency === '') {
            throw new \invalid_argument_exception('Invalid FX base currency.');
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $document = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_use_internal_errors($previous);
        }
        if ($document === false) {
            throw new \runtime_exception('ECB returned invalid XML.');
        }

        $nodes = $document->xpath('//*[local-name()="Cube"][@time]');
        if (!$nodes) {
            throw new \runtime_exception('ECB response does not contain a reference date.');
        }
        $datedcube = $nodes[0];
        $referencedate = trim((string)$datedcube['time']);

        $eurates = ['EUR' => 1.0];
        foreach ($datedcube->children() as $child) {
            $code = Currency::sanitize((string)$child['currency']);
            $rate = (float)((string)$child['rate']);
            if ($code !== '' && $rate > 0) {
                $eurates[$code] = $rate;
            }
        }

        $targets = [];
        foreach ($targetcurrencies as $rawcode) {
            $code = Currency::sanitize((string)$rawcode);
            if ($code !== '' && $code !== $basecurrency && !in_array($code, $targets, true)) {
                $targets[] = $code;
            }
        }

        if (!isset($eurates[$basecurrency])) {
            return new CommerceFxRateRefreshResult('ECB', $basecurrency, [], $targets, $referencedate);
        }

        $rates = [];
        $unavailable = [];
        foreach ($targets as $code) {
            if (!isset($eurates[$code])) {
                $unavailable[] = $code;
                continue;
            }
            $crossrate = $eurates[$code] / $eurates[$basecurrency];
            $value = rtrim(rtrim(number_format($crossrate, 8, '.', ''), '0'), '.');
            $rates[$code] = $value === '' ? '0' : $value;
        }
        return new CommerceFxRateRefreshResult('ECB', $basecurrency, $rates, $unavailable, $referencedate);
    }
}
