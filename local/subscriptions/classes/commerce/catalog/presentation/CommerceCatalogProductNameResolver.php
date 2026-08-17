<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\presentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogProductSummary;

/**
 * Resolves admin-facing product names without leaking a technical key when a
 * translated business title is available.
 *
 * Resolution order:
 * current language -> Campus/site default language -> RU -> FR -> EN -> fallback.
 */
final class CommerceCatalogProductNameResolver {
    private const FALLBACK_LANGUAGES = ['ru', 'fr', 'en'];

    public static function resolve(
        \moodle_database $database,
        CommerceCatalogProductSummary $product,
        ?string $currentlanguage = null,
        ?string $defaultlanguage = null
    ): string {
        if ($product->get_origin() !== 'native') {
            return CommerceProductDisplayText::title($product->get_name());
        }

        return self::resolve_native_id(
            $database,
            (int)$product->get_id(),
            $product->get_name(),
            $currentlanguage,
            $defaultlanguage
        );
    }

    public static function resolve_native_id(
        \moodle_database $database,
        int $productid,
        string $fallback,
        ?string $currentlanguage = null,
        ?string $defaultlanguage = null
    ): string {
        global $CFG;

        $currentlanguage = $currentlanguage ?? current_language();
        $defaultlanguage = $defaultlanguage ?? (string)($CFG->lang ?? 'fr');

        foreach (
            self::language_candidates(
                $currentlanguage,
                $defaultlanguage
            ) as $language
        ) {
            $name = $database->get_field(
                'local_subs_commerce_prod_tr',
                'name',
                [
                    'productid' => $productid,
                    'language' => $language,
                ],
                IGNORE_MISSING
            );
            $name = CommerceProductDisplayText::title((string)$name);
            if ($name !== '') {
                return $name;
            }
        }

        return CommerceProductDisplayText::title($fallback);
    }

    /**
     * @return string[]
     */
    public static function language_candidates(
        string $currentlanguage,
        string $defaultlanguage
    ): array {
        $candidates = [];

        foreach (
            array_merge(
                self::normalised_language_variants($currentlanguage),
                self::normalised_language_variants($defaultlanguage),
                self::FALLBACK_LANGUAGES
            ) as $language
        ) {
            if ($language !== '' && !in_array($language, $candidates, true)) {
                $candidates[] = $language;
            }
        }

        return $candidates;
    }

    /**
     * @return string[]
     */
    private static function normalised_language_variants(string $language): array {
        $language = strtolower(trim(str_replace('-', '_', $language)));
        if ($language === '') {
            return [];
        }

        $base = explode('_', $language)[0];
        return $base !== $language ? [$language, $base] : [$language];
    }

    private function __construct() {
    }
}
