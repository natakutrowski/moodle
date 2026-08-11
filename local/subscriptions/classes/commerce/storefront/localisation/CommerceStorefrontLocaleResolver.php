<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\localisation;

defined('MOODLE_INTERNAL') || die();

/** Resolves language-specific Storefront editorial metadata with safe fallbacks. */
final class CommerceStorefrontLocaleResolver {
    public function resolve(array $storefront, ?string $language = null): array {
        $language = strtolower(trim($language ?: current_language()));
        $language = explode('_', str_replace('-', '_', $language))[0];
        $locales = is_array($storefront['locales'] ?? null) ? $storefront['locales'] : [];
        $overlay = $locales[$language] ?? $locales['fr'] ?? [];
        if (!is_array($overlay)) {
            return $storefront;
        }
        foreach (['sections', 'experience'] as $key) {
            if (array_key_exists($key, $overlay)) {
                if ($key === 'experience' && is_array($storefront[$key] ?? null) && is_array($overlay[$key])) {
                    $storefront[$key] = array_replace($storefront[$key], $overlay[$key]);
                } else {
                    $storefront[$key] = $overlay[$key];
                }
            }
        }
        return $storefront;
    }
}
