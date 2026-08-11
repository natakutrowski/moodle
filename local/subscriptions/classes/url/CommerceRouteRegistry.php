<?php

declare(strict_types=1);

namespace local_subscriptions\url;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;

final class CommerceRouteRegistry {
    public const MY_CAMPUS = 'my_campus';
    public const MY_COURSES = 'my_courses';
    public const MY_PURCHASES = 'my_purchases';
    public const MY_RESOURCES = 'my_resources';
    public const MY_PROFILE = 'my_profile';
    public const CART = 'cart';
    public const CHECKOUT = 'checkout';
    public const ORDER_DETAILS = 'order_details';
    public const ORDER_RESULT = 'order_result';
    public const STOREFRONT = 'storefront';
    public const COURSE = 'course';
    public const SUPPORT = 'support';
    public const SHOWROOM = 'showroom';

    private const TARGETS = [
        self::MY_CAMPUS => 'mon_campus.php',
        self::MY_COURSES => '../campus/mycourses.php',
        self::MY_PURCHASES => 'my_purchases.php',
        self::MY_RESOURCES => 'my_digital_products.php',
        self::MY_PROFILE => '../../user/profile.php',
        self::CART => 'cart.php',
        self::CHECKOUT => 'commerce_checkout.php',
        self::ORDER_DETAILS => 'order_details.php',
        self::ORDER_RESULT => 'order_result.php',
        self::STOREFRONT => 'digital_catalog.php',
        self::COURSE => '../../course/view.php',
        self::SUPPORT => 'support_request.php',
        self::SHOWROOM => 'showroom.php',
    ];

    public static function path(string $route, ?string $language = null): string {
        return subscription_config::public_route_path($route, $language);
    }

    public static function target(string $route): string {
        if (!isset(self::TARGETS[$route])) {
            throw new \coding_exception('Unknown Commerce route: ' . $route);
        }
        return self::TARGETS[$route];
    }

    public static function route_from_slug(string $slug): ?string {
        $slug = trim($slug, '/');
        foreach (subscription_config::PUBLIC_ROUTES as $route => $translations) {
            if (in_array($slug, $translations, true)) {
                return $route;
            }
        }
        return null;
    }
}
