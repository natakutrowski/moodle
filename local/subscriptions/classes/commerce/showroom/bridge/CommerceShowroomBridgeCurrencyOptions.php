<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/** Reuses the existing selected currencies, preserving the CMS preview route and ID. */
final class CommerceShowroomBridgeCurrencyOptions {
    /**
     * @param array<int,array<string,mixed>> $currencies Currency candidates already approved by Commerce.
     * @return array<int,array{value:string,label:string,selected:bool,url:string}>
     */
    public static function from_showroom_page(array $currencies, string $selected, \moodle_url $pageurl): array {
        $selected = strtoupper(trim($selected));
        $result = [];
        $seen = [];
        foreach ($currencies as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $currency = strtoupper(trim((string)($candidate['value'] ?? '')));
            if (!preg_match('/\A[A-Z]{3}\z/', $currency) || isset($seen[$currency])) {
                continue;
            }
            $seen[$currency] = true;
            // Route and query parameters are provided by Moodle, not a browser-supplied URL.
            $link = new \moodle_url($pageurl);
            $link->param('currency', $currency);
            $result[] = [
                'value' => $currency,
                'label' => (string)($candidate['label'] ?? $currency),
                'selected' => $currency === $selected,
                'url' => $link->out(false),
            ];
        }
        return $result;
    }
}
