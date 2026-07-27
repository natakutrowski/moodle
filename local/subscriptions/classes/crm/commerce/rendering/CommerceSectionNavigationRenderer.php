<?php

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Renders the shared secondary navigation for the Commerce workspace.
 */
final class CommerceSectionNavigationRenderer {

    public const OVERVIEW = 'overview';
    public const PRODUCTS = 'products';
    public const SUBSCRIPTIONS = 'subscriptions';
    public const DIGITAL_PURCHASES = 'digital_purchases';
    public const DIGITAL_PRODUCTS = 'digital_products';
    public const STATISTICS = 'statistics';
    public const CONFIGURATION = 'configuration';

    public static function render(string $activekey): string {
        $items = [];

        foreach (self::definitions() as $key => $definition) {
            if (!AdminSecurity::can($definition['capability'])) {
                continue;
            }

            $classes = 'crm-commerce-section-nav-link';
            $attributes = [];

            if ($key === $activekey) {
                $classes .= ' active';
                $attributes['aria-current'] = 'page';
            }

            $attributes['class'] = $classes;

            $items[] = html_writer::link(
                $definition['url'],
                html_writer::span(
                    $definition['icon'],
                    'crm-commerce-section-nav-icon',
                    ['aria-hidden' => 'true']
                ) .
                html_writer::span(
                    s($definition['label']),
                    'crm-commerce-section-nav-label'
                ),
                $attributes
            );
        }

        if ($items === []) {
            return '';
        }

        return html_writer::tag(
            'nav',
            html_writer::div(
                implode('', $items),
                'crm-commerce-section-nav-list'
            ),
            [
                'class' => 'crm-commerce-section-nav mb-4',
                'aria-label' => get_string(
                    'crm_commerce_section_navigation',
                    'local_subscriptions'
                ),
            ]
        );
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     icon: string,
     *     url: moodle_url,
     *     capability: string
     * }>
     */
    private static function definitions(): array {
        return [
            self::OVERVIEW => [
                'label' => get_string('crm_commerce_nav_overview', 'local_subscriptions'),
                'icon' => '⌂',
                'url' => new moodle_url(subscription_config::admin_commerce_page()),
                'capability' => Capabilities::VIEW_DASHBOARD,
            ],
            self::PRODUCTS => [
                'label' => get_string('crm_commerce_nav_products', 'local_subscriptions'),
                'icon' => '▦',
                'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
                'capability' => Capabilities::MANAGE_CONFIGURATION,
            ],
            self::SUBSCRIPTIONS => [
                'label' => get_string('crm_commerce_nav_subscriptions', 'local_subscriptions'),
                'icon' => '▣',
                'url' => new moodle_url(subscription_config::user_subscriptions_page()),
                'capability' => Capabilities::MANAGE_SUBSCRIPTIONS,
            ],
            self::DIGITAL_PURCHASES => [
                'label' => get_string('crm_commerce_nav_digital_purchases', 'local_subscriptions'),
                'icon' => '◆',
                'url' => new moodle_url(subscription_config::digital_purchases_admin_page()),
                'capability' => Capabilities::VIEW_DIGITAL,
            ],
            self::DIGITAL_PRODUCTS => [
                'label' => get_string('crm_commerce_nav_digital_products', 'local_subscriptions'),
                'icon' => '▤',
                'url' => new moodle_url(subscription_config::digital_products_admin_page()),
                'capability' => Capabilities::MANAGE_DIGITAL,
            ],
            self::STATISTICS => [
                'label' => get_string('crm_commerce_nav_statistics', 'local_subscriptions'),
                'icon' => '▥',
                'url' => new moodle_url(subscription_config::digital_sales_stats_admin_page()),
                'capability' => Capabilities::VIEW_STATISTICS,
            ],
            self::CONFIGURATION => [
                'label' => get_string('crm_commerce_nav_configuration', 'local_subscriptions'),
                'icon' => '⚙',
                'url' => new moodle_url(subscription_config::manage_page()),
                'capability' => Capabilities::MANAGE_CONFIGURATION,
            ],
        ];
    }
}
