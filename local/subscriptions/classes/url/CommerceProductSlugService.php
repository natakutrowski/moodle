<?php

declare(strict_types=1);

namespace local_subscriptions\url;

defined('MOODLE_INTERNAL') || die();

final class CommerceProductSlugService {
    public function __construct(private readonly \moodle_database $db) {}

    public function find_sku(
        string $slug,
        ?string $language = null,
        ?string $category = null
    ): ?string {
        $slug = self::clean($slug);
        if ($slug === '') { return null; }
        $language = self::language($language);
        $records = $this->db->get_records('local_subs_commerce_product', ['status' => 'active'], '', 'sku,type,metadatajson');
        foreach ($records as $record) {
            if ($category !== null && !$this->category_matches((string)$record->type, $category, $language)) {
                continue;
            }
            $metadata = json_decode((string)$record->metadatajson, true);
            $slugs = (array)($metadata['storefront']['routing']['slugs'] ?? []);
            $candidate = $slugs[$language] ?? null;
            if (self::clean((string)$candidate) === $slug) {
                return (string)$record->sku;
            }

            // A user may return from login with a different active language.
            // Keep shared product URLs resolvable across FR/EN/RU sessions.
            foreach (['fr', 'en', 'ru'] as $fallbacklanguage) {
                if ($fallbacklanguage === $language) {
                    continue;
                }
                if (self::clean((string)($slugs[$fallbacklanguage] ?? '')) === $slug) {
                    return (string)$record->sku;
                }
            }
        }
        return null;
    }

    private function category_matches(
        string $producttype,
        string $category,
        ?string $language
    ): bool {
        $segments = \local_subscriptions\subscription_config::product_route_segments(
            $producttype,
            $language
        );
        return self::clean((string)$segments['category']) === self::clean($category);
    }

    public static function clean(string $slug): string {
        $slug = strtolower(trim($slug));
        $slug = \core_text::strtolower(
            \core_text::specialtoascii($slug)
        );
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }

    private static function language(?string $language): string {
        $language = strtolower(trim((string)($language ?: current_language())));
        $language = explode('_', str_replace('-', '_', $language))[0];
        return in_array($language, ['fr','en','ru'], true) ? $language : 'fr';
    }
}
