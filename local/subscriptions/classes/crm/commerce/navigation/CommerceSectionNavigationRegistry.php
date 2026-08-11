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
    public const PURCHASES = 'purchases';
    /** @deprecated Compatibility key retained for old callers. */
    public const SUBSCRIPTIONS = self::PURCHASES;
    /** @deprecated Compatibility key retained for old callers. */
    public const DIGITAL_PURCHASES = self::PURCHASES;
    public const DIGITAL_PRODUCTS = 'digital_products';
    public const MAIL = 'mail';
    public const IDENTITIES = 'identities';
    public const PERSONAL_OFFERS = 'personal_offers';
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
                '⌂',
                new moodle_url(subscription_config::admin_commerce_page()),
                Capabilities::VIEW_DASHBOARD,
                10
            ),
            new CommerceSectionNavigationItem(
                self::PRODUCTS,
                get_string('crm_commerce_nav_products', 'local_subscriptions'),
                '▦',
                new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
                Capabilities::MANAGE_CONFIGURATION,
                20
            ),
            new CommerceSectionNavigationItem(
                self::PURCHASES,
                get_string('crm_commerce_nav_purchases', 'local_subscriptions'),
                '▣',
                new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php'),
                Capabilities::VIEW_PAYMENTS,
                25
            ),
            new CommerceSectionNavigationItem(
                self::MAIL,
                get_string('crm_commerce_nav_mail', 'local_subscriptions'),
                '✉',
                new moodle_url('/local/subscriptions/admin/commerce/mail/index.php'),
                Capabilities::VIEW_PAYMENTS,
                55
            ),
            new CommerceSectionNavigationItem(
                self::IDENTITIES,
                get_string('crm_commerce_nav_identities', 'local_subscriptions'),
                '◎',
                new moodle_url('/local/subscriptions/admin/commerce/customer-identities/index.php'),
                Capabilities::VIEW_PAYMENTS,
                57
            ),
            new CommerceSectionNavigationItem(
                self::PERSONAL_OFFERS,
                get_string('crm_commerce_nav_personal_offers', 'local_subscriptions'),
                '◆',
                new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php'),
                Capabilities::VIEW_PAYMENTS,
                58
            ),
            new CommerceSectionNavigationItem(
                self::GRANTS,
                get_string('crm_commerce_nav_grants', 'local_subscriptions'),
                '⊕',
                new moodle_url('/local/subscriptions/admin/commerce/grants/index.php'),
                Capabilities::MANAGE_SUBSCRIPTIONS,
                59
            ),
            new CommerceSectionNavigationItem(
                self::STATISTICS,
                get_string('crm_commerce_nav_statistics', 'local_subscriptions'),
                '▥',
                new moodle_url('/local/subscriptions/admin/commerce/statistics/index.php'),
                Capabilities::VIEW_STATISTICS,
                60
            ),
            new CommerceSectionNavigationItem(
                self::CONFIGURATION,
                get_string('crm_commerce_nav_configuration', 'local_subscriptions'),
                '⚙',
                new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php'),
                Capabilities::MANAGE_CONFIGURATION,
                70
            ),
        ];
    }

    public static function is_known(string $key): bool {
        return in_array($key, [
            self::OVERVIEW,
            self::PRODUCTS,
            self::PURCHASES,
            self::DIGITAL_PRODUCTS,
            self::MAIL,
            self::IDENTITIES,
            self::PERSONAL_OFFERS,
            self::GRANTS,
            self::STATISTICS,
            self::CONFIGURATION,
        ], true);
    }
}
