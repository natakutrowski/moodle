<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\navigation;

defined('MOODLE_INTERNAL') || die();

use context;
use context_system;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use moodle_url;

/** Single source of truth for the Commerce workspace secondary navigation. */
final class CommerceSectionNavigationRegistry {
    public const OVERVIEW = 'overview';
    public const PRODUCTS = 'products';
    public const SHOWROOMS = 'showrooms';
    public const PURCHASES = 'purchases';
    /** @deprecated Compatibility key retained for old callers. */
    public const SUBSCRIPTIONS = self::PURCHASES;
    /** @deprecated Compatibility key retained for old callers. */
    public const DIGITAL_PURCHASES = self::PURCHASES;
    public const DIGITAL_PRODUCTS = 'digital_products';
    public const MAIL = 'mail';
    public const IDENTITIES = 'identities';
    public const UNFINISHED_CHECKOUTS = 'unfinished_checkouts';
    public const OFFERS_ACCESS = 'offers_access';
    /** @deprecated N7.1 compatibility key retained for old callers/tests. */
    public const PERSONAL_OFFERS = 'personal_offers';
    /** @deprecated N7.1 compatibility key retained for old callers/tests. */
    public const GRANTS = 'grants';
    public const STATISTICS = 'statistics';
    public const CONFIGURATION = 'configuration';

    /**
     * @return CommerceSectionNavigationItem[]
     */
    public function visible_items(?context $context = null): array {
        $context ??= context_system::instance();
        $items = array_filter(
            $this->all_items(),
            static fn(CommerceSectionNavigationItem $item): bool => has_capability($item->capability, $context)
        );

        usort(
            $items,
            static fn(CommerceSectionNavigationItem $left, CommerceSectionNavigationItem $right): int =>
                $left->position <=> $right->position
        );

        return array_values($items);
    }

    /**
     * @return CommerceSectionNavigationItem[]
     */
    public function all_items(): array {
        return [
            new CommerceSectionNavigationItem(
                self::OVERVIEW,
                get_string('crm_commerce_nav_overview', 'local_subscriptions'),
                'fa-home',
                new moodle_url(subscription_config::admin_commerce_page()),
                Capabilities::VIEW_DASHBOARD,
                10
            ),
            new CommerceSectionNavigationItem(
                self::PURCHASES,
                get_string('crm_commerce_nav_purchases', 'local_subscriptions'),
                'fa-shopping-cart',
                new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php'),
                Capabilities::VIEW_PAYMENTS,
                20
            ),
            new CommerceSectionNavigationItem(
                self::PRODUCTS,
                get_string('crm_commerce_nav_products', 'local_subscriptions'),
                'fa-cube',
                new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
                Capabilities::MANAGE_CONFIGURATION,
                30
            ),
            new CommerceSectionNavigationItem(
                self::SHOWROOMS,
                get_string('crm_commerce_nav_showrooms', 'local_subscriptions'),
                'fa-window-maximize',
                new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
                Capabilities::MANAGE_CONFIGURATION,
                40
            ),
            new CommerceSectionNavigationItem(
                self::OFFERS_ACCESS,
                get_string('crm_commerce_nav_offers_access', 'local_subscriptions'),
                'fa-gift',
                new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php'),
                Capabilities::VIEW_PAYMENTS,
                50
            ),
            new CommerceSectionNavigationItem(
                self::MAIL,
                get_string('crm_commerce_nav_mail', 'local_subscriptions'),
                'fa-envelope',
                new moodle_url('/local/subscriptions/admin/commerce/mail/index.php'),
                Capabilities::VIEW_PAYMENTS,
                70
            ),
            new CommerceSectionNavigationItem(
                self::STATISTICS,
                get_string('crm_commerce_nav_statistics', 'local_subscriptions'),
                'fa-bar-chart',
                new moodle_url('/local/subscriptions/admin/commerce/statistics/index.php'),
                Capabilities::VIEW_STATISTICS,
                80
            ),
            new CommerceSectionNavigationItem(
                self::CONFIGURATION,
                get_string('crm_commerce_nav_configuration', 'local_subscriptions'),
                'fa-cog',
                new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php'),
                Capabilities::MANAGE_CONFIGURATION,
                100
            ),
        ];
    }

    public static function is_known(string $key): bool {
        return in_array($key, [
            self::OVERVIEW,
            self::PRODUCTS,
            self::SHOWROOMS,
            self::PURCHASES,
            self::DIGITAL_PRODUCTS,
            self::MAIL,
            self::IDENTITIES,
            self::UNFINISHED_CHECKOUTS,
            self::OFFERS_ACCESS,
            self::PERSONAL_OFFERS,
            self::GRANTS,
            self::STATISTICS,
            self::CONFIGURATION,
        ], true);
    }
}
