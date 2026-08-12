<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\mail;

defined('MOODLE_INTERNAL') || die();

/** Safe, closed variable vocabulary for per-campaign Personal Offer emails. */
final class CommercePersonalOfferCampaignMailVariableResolver {
    public const AVAILABLE = [
        'firstname', 'fullname', 'product_name', 'offer_start', 'offer_end', 'offer_price',
        'regular_price', 'discount_amount', 'discount_percent',
    ];

    /** @param array<string,string> $values */
    public static function replace(string $content, array $values, bool $html = false): string {
        $replacements = [];
        foreach (self::AVAILABLE as $name) {
            $value = (string)($values[$name] ?? '');
            $replacements['{{' . $name . '}}'] = $html ? s($value) : $value;
        }
        $resolved = strtr($content, $replacements);
        // Unknown variables are never interpreted and never leak raw placeholders to customers.
        return preg_replace('/\{\{[a-zA-Z0-9_]+\}\}/u', '', $resolved) ?? $resolved;
    }

    private function __construct() {}
}
