<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\visual;

defined('MOODLE_INTERNAL') || die();

/**
 * Stable four-format contract for Commerce product artwork.
 *
 * Existing technical roles remain readable during the migration, but all
 * customer-facing surfaces are projected onto these four masters.
 */
final class CommerceProductVisualFormat {
    public const SQUARE = 'square';
    public const LANDSCAPE = 'landscape';
    public const WIDE = 'wide';
    public const PORTRAIT = 'portrait';
    public const SHOWROOM = 'showroom';

    /** @return string[] */
    public static function all(): array {
        return [
            self::SQUARE,
            self::LANDSCAPE,
            self::WIDE,
            self::PORTRAIT,
            self::SHOWROOM,
        ];
    }

    /** @return array<string,array<string,mixed>> */
    public static function definitions(): array {
        return [
            self::SQUARE => [
                'ratio' => '1:1',
                'width' => 1200,
                'height' => 1200,
                'roles' => ['checkout'],
                'surfaces' => [
                    'checkout',
                    'order_result',
                    'order_details_compact',
                    'crm_lists',
                    'email_thumbnail',
                ],
            ],
            self::LANDSCAPE => [
                'ratio' => '4:3',
                'width' => 1600,
                'height' => 1200,
                'roles' => ['storefront', 'recommendation'],
                'surfaces' => [
                    'storefront_catalogue',
                    'my_courses_recommendations',
                    'storefront_recommendations',
                    'standard_product_preview',
                ],
            ],
            self::WIDE => [
                'ratio' => '16:9',
                'width' => 1920,
                'height' => 1080,
                'roles' => ['product', 'social', 'email'],
                'surfaces' => [
                    'product_hero',
                    'immersive_layout',
                    'video_poster',
                    'social_open_graph',
                    'wide_gallery',
                    'email_banner',
                ],
            ],
            self::PORTRAIT => [
                'ratio' => '4:5',
                'width' => 1200,
                'height' => 1500,
                'roles' => ['resources'],
                'surfaces' => [
                    'cart',
                    'printable_cart',
                    'digital_library',
                    'digital_product_cover',
                    'vertical_cards',
                ],
            ],
            self::SHOWROOM => [
                'ratio' => '16:9',
                'width' => 1920,
                'height' => 1080,
                'roles' => ['showroom'],
                'surfaces' => [
                    'showroom_offer_cards',
                    'showroom_comparison',
                    'showroom_campaign_visuals',
                ],
            ],
        ];
    }

    public static function for_role(string $role): string {
        $role = strtolower(trim($role));

        return match ($role) {
            'checkout' => self::SQUARE,
            'storefront', 'recommendation' => self::LANDSCAPE,
            'product', 'social', 'email' => self::WIDE,
            'resources' => self::PORTRAIT,
            'showroom' => self::SHOWROOM,
            'cover' => self::LANDSCAPE,
            default => throw new \coding_exception(
                'Unsupported Commerce visual role: ' . $role
            ),
        };
    }

    /** @return array<string,mixed> */
    public static function definition(string $format): array {
        $definitions = self::definitions();
        if (!isset($definitions[$format])) {
            throw new \coding_exception(
                'Unsupported Commerce visual format: ' . $format
            );
        }

        return $definitions[$format];
    }

    public static function expected_ratio(string $format): float {
        $definition = self::definition($format);

        return (float)$definition['width']
            / (float)$definition['height'];
    }

    public static function ratio_matches(
        string $format,
        int $width,
        int $height,
        float $tolerance = 0.035
    ): bool {
        if ($width <= 0 || $height <= 0) {
            return false;
        }

        $actual = $width / $height;
        $expected = self::expected_ratio($format);

        return abs($actual - $expected) <= $tolerance;
    }
}
