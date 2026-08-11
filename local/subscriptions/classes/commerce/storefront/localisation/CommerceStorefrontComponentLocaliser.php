<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\localisation;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves bundle component display text for a requested storefront locale.
 *
 * Keeps catalogue composition technical data intact while replacing only
 * customer-facing text. Fallback order mirrors the Storefront product
 * translation policy: exact language -> base language -> FR -> EN -> RU ->
 * native catalogue value.
 */
final class CommerceStorefrontComponentLocaliser {
    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $components
     * @return array<int, array<string, mixed>>
     */
    public function localise(array $components, string $language): array {
        if ($components === []) {
            return [];
        }

        $productids = [];
        foreach ($components as $component) {
            $id = (int)($component['id'] ?? 0);
            if ($id > 0) {
                $productids[$id] = $id;
            }
        }

        if ($productids === []) {
            return $components;
        }

        $records = $this->db->get_records_list(
            'local_subs_commerce_prod_tr',
            'productid',
            array_values($productids),
            'productid ASC, language ASC'
        );

        $translations = [];
        foreach ($records as $record) {
            $translations[(int)$record->productid][] = [
                'language' => (string)$record->language,
                'name' => (string)$record->name,
                'shortdescription' => (string)($record->shortdescription ?? ''),
                'description' => (string)($record->description ?? ''),
            ];
        }

        foreach ($components as &$component) {
            $productid = (int)($component['id'] ?? 0);
            if ($productid <= 0 || empty($translations[$productid])) {
                continue;
            }

            $translation = $this->translation($translations[$productid], $language);
            if ($translation === null) {
                continue;
            }

            $name = trim((string)($translation['name'] ?? ''));
            if ($name !== '') {
                $component['name'] = $name;
            }

            $shortdescription = trim((string)($translation['shortdescription'] ?? ''));
            $description = trim((string)($translation['description'] ?? ''));
            $component['description'] = $shortdescription !== ''
                ? $shortdescription
                : $description;
        }
        unset($component);

        return $components;
    }

    /**
     * @param array<int, array<string, string>> $translations
     * @return array<string, string>|null
     */
    private function translation(array $translations, string $language): ?array {
        $language = strtolower(trim($language));
        $base = explode('_', str_replace('-', '_', $language))[0];
        $bylanguage = [];

        foreach ($translations as $translation) {
            $candidate = strtolower(trim((string)($translation['language'] ?? '')));
            if ($candidate !== '' && !isset($bylanguage[$candidate])) {
                $bylanguage[$candidate] = $translation;
            }
        }

        foreach (array_values(array_unique(array_filter([
            $language,
            $base,
            'fr',
            'en',
            'ru',
        ]))) as $candidate) {
            if (isset($bylanguage[$candidate])) {
                return $bylanguage[$candidate];
            }
        }

        return $translations !== [] ? reset($translations) : null;
    }
}
