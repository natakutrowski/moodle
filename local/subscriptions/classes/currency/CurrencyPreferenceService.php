<?php

namespace local_subscriptions\currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves the Dashboard currency from available revenue currencies.
 */
final class CurrencyPreferenceService {

    public function __construct(
        private readonly CurrencyPreferenceRepository $repository =
            new CurrencyPreferenceRepository()
    ) {
    }

    /**
     * Resolve the selected currency.
     *
     * Priority:
     * 1. saved user preference if available in the result;
     * 2. EUR if present;
     * 3. first available currency;
     * 4. EUR as an empty-state fallback.
     *
     * @param string[] $availablecurrencies
     */
    public function resolve(
        int $userid,
        array $availablecurrencies
    ): string {
        $availablecurrencies = $this->normalize_available(
            $availablecurrencies
        );

        $preferred = $this->repository->get($userid);

        if (
            $preferred !== ''
            && in_array($preferred, $availablecurrencies, true)
        ) {
            return $preferred;
        }

        if (in_array('EUR', $availablecurrencies, true)) {
            return 'EUR';
        }

        if (!empty($availablecurrencies)) {
            return reset($availablecurrencies);
        }

        return 'EUR';
    }

    /**
     * Persist the selected currency.
     *
     * @param string[] $availablecurrencies
     */
    public function save(
        int $userid,
        string $currency,
        array $availablecurrencies
    ): bool {
        $currency = Currency::sanitize($currency);

        $availablecurrencies = $this->normalize_available(
            $availablecurrencies
        );

        if (
            $currency === ''
            || !in_array($currency, $availablecurrencies, true)
        ) {
            return false;
        }

        $this->repository->set($userid, $currency);

        return true;
    }

    /**
     * @param string[] $currencies
     * @return string[]
     */
    private function normalize_available(array $currencies): array {
        $result = [];

        foreach ($currencies as $currency) {
            $currency = Currency::sanitize((string)$currency);

            if ($currency !== '') {
                $result[$currency] = $currency;
            }
        }

        ksort($result);

        return array_values($result);
    }
}