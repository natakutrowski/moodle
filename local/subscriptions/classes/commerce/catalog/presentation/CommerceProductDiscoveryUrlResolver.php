<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\presentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomUrl;
use local_subscriptions\url\CommerceProductSlugService;
use local_subscriptions\url\UrlFactory;

/**
 * Resolves the canonical customer-facing discovery destination of a product.
 *
 * Storefront remains directly addressable. This resolver is only used by
 * discovery CTAs ("Découvrir", recommendations, purchase history, etc.).
 */
final class CommerceProductDiscoveryUrlResolver {
    public const MODE_STOREFRONT = 'storefront';
    public const MODE_SHOWROOM = 'showroom';

    /**
     * @param array<string,mixed> $metadata
     * @param array<string,mixed> $params
     */
    public static function resolve(
        string $sku,
        string $type,
        array $metadata,
        array $params = [],
        ?string $currentShowroomKey = null,
        ?string $language = null
    ): \moodle_url {
        $showroom = self::showroom_configuration($metadata);
        $mode = self::normalise_mode((string)($showroom['discoverymode'] ?? self::MODE_STOREFRONT));
        $showroomkey = strtolower(trim((string)($showroom['key'] ?? '')));
        $currentshowroom = strtolower(trim((string)($currentShowroomKey ?? '')));

        if (
            $mode === self::MODE_SHOWROOM
            && $showroomkey !== ''
            && $showroomkey !== $currentshowroom
            && self::is_published_showroom($showroomkey)
        ) {
            $definition = CommerceShowroomRegistry::get($showroomkey);
            if ($definition !== null) {
                return CommerceShowroomUrl::make(
                    $definition,
                    $params,
                    self::language($language)
                );
            }
        }

        return self::storefront($sku, $type, $metadata, $params, $language);
    }

    /**
     * Builds the direct Storefront URL, bypassing discovery routing.
     *
     * This is intentionally used inside a Showroom to guarantee that an
     * optional "En savoir plus" link never loops back to the same Showroom.
     *
     * @param array<string,mixed> $metadata
     * @param array<string,mixed> $params
     */
    public static function storefront(
        string $sku,
        string $type,
        array $metadata,
        array $params = [],
        ?string $language = null
    ): \moodle_url {
        $language = self::language($language);
        $configuration = is_array($metadata['storefront'] ?? null)
            ? $metadata['storefront']
            : [];

        $slug = CommerceProductSlugService::clean((string)(
            $configuration['routing']['slugs'][$language]
            ?? $configuration['routing']['slugs']['fr']
            ?? ''
        ));

        if ($slug !== '') {
            return UrlFactory::product_slug($slug, $params, $type, $language);
        }

        return new \moodle_url(
            '/local/subscriptions/storefront_product.php',
            ['sku' => strtoupper(trim($sku))] + $params
        );
    }

    /** @param array<string,mixed> $metadata */
    public static function storefront_presentation_enabled(array $metadata): bool {
        $showroom = self::showroom_configuration($metadata);
        $key = strtolower(trim((string)($showroom['key'] ?? '')));
        if ($key === '' || !self::is_published_showroom($key)) {
            return false;
        }

        return !array_key_exists('showstorefrontcta', $showroom)
            || !empty($showroom['showstorefrontcta']);
    }

    /** @param array<string,mixed> $metadata */
    public static function configured_showroom_key(array $metadata): string {
        $showroom = self::showroom_configuration($metadata);
        return strtolower(trim((string)($showroom['key'] ?? '')));
    }

    public static function normalise_mode(string $mode): string {
        return strtolower(trim($mode)) === self::MODE_SHOWROOM
            ? self::MODE_SHOWROOM
            : self::MODE_STOREFRONT;
    }

    public static function is_published_showroom(string $key): bool {
        global $DB;

        $key = strtolower(trim($key));
        if ($key === '' || CommerceShowroomRegistry::get($key) === null) {
            return false;
        }

        return $DB->record_exists('local_subs_showroom', [
            'showroomkey' => $key,
            'status' => 'published',
        ]);
    }

    /** @param array<string,mixed> $metadata */
    private static function showroom_configuration(array $metadata): array {
        return is_array($metadata['showroom'] ?? null)
            ? $metadata['showroom']
            : [];
    }

    private static function language(?string $language): string {
        $language = strtolower(trim($language ?: current_language()));
        return explode('_', str_replace('-', '_', $language))[0] ?: 'fr';
    }
}
