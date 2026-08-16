<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template\studio;

defined('MOODLE_INTERNAL') || die();

/** Replaces the small, controlled set of tokens supported by the Mail Studio. */
final class CommerceMailTokenResolver {

    /** @param array<string,mixed> $presentation */
    public static function values(array $presentation): array {
        $fullname = trim((string)($presentation['customername'] ?? ''));
        $firstname = $fullname !== '' ? trim((string)preg_split('/\s+/u', $fullname, 2)[0]) : '';
        $links = is_array($presentation['links'] ?? null) ? $presentation['links'] : [];
        $supportemail = (string)(get_config('local_subscriptions', 'support_email') ?: 'support@campusfr.fr');
        $offer = is_array($presentation['personaloffer'] ?? null) ? $presentation['personaloffer'] : [];
        $username = trim((string)($presentation['username'] ?? ''));
        $greeting = self::greeting($firstname);

        $values = [
            'greeting' => $greeting,
            'firstname' => $firstname,
            'fullname' => $fullname,
            'username' => $username,
            'order_reference' => (string)($presentation['reference'] ?? ''),
            'order_total' => (string)($presentation['totalformatted'] ?? ''),
            'order_url' => (string)($links['order'] ?? ''),
            'my_purchases_url' => (string)($links['purchases'] ?? ''),
            'my_courses_url' => (string)($links['courses'] ?? ''),
            'digital_library_url' => (string)($links['resources'] ?? ''),
            'support_email' => $supportemail,
            'offer_url' => (string)($offer['url'] ?? ''),
            'offer_product' => (string)($offer['productname'] ?? ''),
            'offer_expiry' => (string)($offer['expiresformatted'] ?? ''),
            'offer_price' => (string)($offer['priceformatted'] ?? ''),
            'campaign_name' => (string)($offer['campaignname'] ?? ''),
        ];

        $tokens = [];
        foreach ($values as $name => $value) {
            $tokens['{' . $name . '}'] = $value;
            $tokens['{{' . $name . '}}'] = $value;
        }
        return $tokens;
    }

    /** @param array<string,string> $values */
    public static function replace(string $content, array $values): string {
        return strtr($content, $values);
    }

    private static function greeting(string $firstname): string {
        $language = clean_param(current_language(), PARAM_LANG);
        return match ($language) {
            'ru' => $firstname !== '' ? 'Здравствуйте, ' . $firstname . '!' : 'Здравствуйте!',
            'en' => $firstname !== '' ? 'Hello ' . $firstname . '!' : 'Hello!',
            default => $firstname !== '' ? 'Bonjour ' . $firstname . ' !' : 'Bonjour !',
        };
    }

    private function __construct() {
    }
}
