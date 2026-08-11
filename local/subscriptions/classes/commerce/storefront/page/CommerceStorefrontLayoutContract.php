<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\page;

defined('MOODLE_INTERNAL') || die();

/** Safe layouts and Commerce-panel positions supported by the Storefront. */
final class CommerceStorefrontLayoutContract {
    public const STANDARD = 'standard';
    public const EDITORIAL = 'editorial';
    public const IMMERSIVE = 'immersive';
    public const COURSE = 'course';
    public const DIGITAL = 'digital';
    public const BUNDLE = 'bundle';

    public const HERO_INTEGRATED = 'hero_integrated';
    public const SIDEBAR_STICKY = 'sidebar_sticky';
    public const BELOW_HERO = 'below_hero';
    public const AFTER_INTRO = 'after_intro';
    public const PAGE_BOTTOM = 'page_bottom';
    public const NONE = 'none';


    /** @return string[] */
    public static function shell_modes(): array {
        return ['standard', 'fullwidth', 'landing', 'immersive'];
    }

    public static function normalise_shell_mode(string $mode): string {
        $mode = strtolower(trim($mode));
        return in_array($mode, self::shell_modes(), true) ? $mode : 'standard';
    }

    public static function moodle_page_layout(string $mode): string {
        return match (self::normalise_shell_mode($mode)) {
            'fullwidth' => 'storefront_fullwidth',
            'landing' => 'storefront_landing',
            'immersive' => 'storefront_immersive',
            default => 'storefront',
        };
    }

    /** @return string[] */
    public static function global_zones(): array {
        return ['hero', 'commerce', 'content', 'recommendations'];
    }

    /**
     * @param string|string[] $zones
     * @return string[]
     */
    public static function normalise_global_zones(string|array $zones): array {
        if (is_string($zones)) {
            $zones = preg_split('/\s*,\s*/', trim($zones)) ?: [];
        }
        $clean = [];
        foreach ($zones as $zone) {
            $zone = strtolower(trim((string)$zone));
            if (in_array($zone, self::global_zones(), true) && !in_array($zone, $clean, true)) {
                $clean[] = $zone;
            }
        }
        foreach (self::global_zones() as $zone) {
            if (!in_array($zone, $clean, true)) {
                $clean[] = $zone;
            }
        }
        return $clean;
    }

    /** @param string[] $zones */
    public static function commerce_position_from_zones(array $zones): string {
        $zones = self::normalise_global_zones($zones);
        $commerce = array_search('commerce', $zones, true);
        $content = array_search('content', $zones, true);
        $recommendations = array_search('recommendations', $zones, true);
        if ($commerce !== false && $content !== false && $commerce < $content) {
            return self::BELOW_HERO;
        }
        if ($commerce !== false && $recommendations !== false && $commerce > $recommendations) {
            return self::PAGE_BOTTOM;
        }
        return self::AFTER_INTRO;
    }

    /** @return string[] */
    public static function layouts(): array {
        return [
            self::STANDARD,
            self::EDITORIAL,
            self::IMMERSIVE,
            self::COURSE,
            self::DIGITAL,
            self::BUNDLE,
        ];
    }

    /** @return string[] */
    public static function commerce_positions(): array {
        return [
            self::HERO_INTEGRATED,
            self::SIDEBAR_STICKY,
            self::BELOW_HERO,
            self::AFTER_INTRO,
            self::PAGE_BOTTOM,
            self::NONE,
        ];
    }

    public static function normalise_layout(string $layout): string {
        $layout = strtolower(trim($layout));
        $legacy = [
            'default' => self::STANDARD,
            'editorial' => self::EDITORIAL,
            'immersive' => self::IMMERSIVE,
        ];
        $layout = $legacy[$layout] ?? $layout;

        return in_array($layout, self::layouts(), true)
            ? $layout
            : self::STANDARD;
    }

    public static function normalise_commerce_position(
        string $position,
        string $layout
    ): string {
        $position = strtolower(trim($position));
        if (in_array($position, self::commerce_positions(), true)) {
            return $position;
        }

        return self::default_commerce_position($layout);
    }

    public static function default_commerce_position(string $layout): string {
        return match (self::normalise_layout($layout)) {
            self::IMMERSIVE => self::BELOW_HERO,
            self::EDITORIAL => self::AFTER_INTRO,
            self::COURSE, self::DIGITAL, self::BUNDLE => self::SIDEBAR_STICKY,
            default => self::SIDEBAR_STICKY,
        };
    }

    public static function template(string $layout): string {
        $layout = self::normalise_layout($layout);
        return 'local_subscriptions/storefront/product_templates/' . $layout;
    }

    public static function placeholder_ratio(
        string $layout,
        string $producttype
    ): string {
        $layout = self::normalise_layout($layout);
        if ($layout === self::IMMERSIVE || $layout === self::EDITORIAL) {
            return 'wide';
        }
        if ($layout === self::DIGITAL || $producttype === 'digital_download') {
            return 'portrait';
        }
        return 'landscape';
    }
}
