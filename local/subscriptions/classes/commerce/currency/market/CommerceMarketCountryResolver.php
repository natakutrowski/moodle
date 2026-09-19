<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency\market;

defined('MOODLE_INTERNAL') || die();

/**
 * Commerce market country detection without language inference.
 *
 * Language is presentation state, not reliable commercial geography.
 */
final class CommerceMarketCountryResolver {
    public function resolve(): string {
        if (function_exists('\get_user_country_code')) {
            $country = $this->normalize(
                (string)\get_user_country_code()
            );
            if ($country !== 'ZZ') {
                return $country;
            }
        }

        return $this->normalize(
            (string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')
        );
    }

    private function normalize(string $country): string {
        $country = strtoupper(trim($country));

        return preg_match('/^[A-Z]{2}$/', $country) === 1
            ? $country
            : 'ZZ';
    }
}
