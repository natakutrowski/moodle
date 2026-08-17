<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/** Internal navigation for the Commerce configuration workspace. */
final class CommerceConfigurationNavigationRenderer {
    public const OVERVIEW = 'overview';

    /** @return array<string,array{label:string,icon:string,url:moodle_url}> */
    private static function items(): array {
        $base = '/local/subscriptions/admin/commerce/configuration/';
        return [
            self::OVERVIEW => [
                'label' => get_string('commerce_configuration_nav_overview', 'local_subscriptions'),
                'icon' => 'fa-solid fa-table-cells-large',
                'url' => new moodle_url($base . 'index.php'),
            ],
            'payments' => [
                'label' => get_string('commerce_configuration_payments_title', 'local_subscriptions'),
                'icon' => 'fa-solid fa-credit-card',
                'url' => new moodle_url($base . 'section.php', ['section' => 'payments']),
            ],
            'localisation' => [
                'label' => get_string('commerce_configuration_localisation_title', 'local_subscriptions'),
                'icon' => 'fa-solid fa-earth-europe',
                'url' => new moodle_url($base . 'section.php', ['section' => 'localisation']),
            ],
            'checkout' => [
                'label' => get_string('commerce_configuration_checkout_title', 'local_subscriptions'),
                'icon' => 'fa-solid fa-cart-shopping',
                'url' => new moodle_url($base . 'section.php', ['section' => 'checkout']),
            ],
            'communications' => [
                'label' => get_string('commerce_configuration_communications_title', 'local_subscriptions'),
                'icon' => 'fa-solid fa-envelope',
                'url' => new moodle_url($base . 'section.php', ['section' => 'communications']),
            ],
            'legal' => [
                'label' => get_string('commerce_configuration_legal_title', 'local_subscriptions'),
                'icon' => 'fa-solid fa-file-invoice',
                'url' => new moodle_url($base . 'section.php', ['section' => 'legal']),
            ],
            'storefront' => [
                'label' => get_string('commerce_configuration_storefront_title', 'local_subscriptions'),
                'icon' => 'fa-solid fa-store',
                'url' => new moodle_url($base . 'section.php', ['section' => 'storefront']),
            ],
            'engine' => [
                'label' => get_string('commerce_configuration_engine_title', 'local_subscriptions'),
                'icon' => 'fa-solid fa-gears',
                'url' => new moodle_url($base . 'section.php', ['section' => 'engine']),
            ],
        ];
    }

    public static function render(string $active): string {
        $links = [];
        foreach (self::items() as $key => $item) {
            $classes = 'commerce-config-subnav__link';
            $attributes = [];
            if ($key === $active) {
                $classes .= ' is-active';
                $attributes['aria-current'] = 'page';
            }
            $attributes['class'] = $classes;
            $content = html_writer::tag('i', '', [
                'class' => $item['icon'],
                'aria-hidden' => 'true',
            ]) . html_writer::span($item['label']);
            $links[] = html_writer::link($item['url'], $content, $attributes);
        }

        return html_writer::tag(
            'nav',
            implode('', $links),
            [
                'class' => 'commerce-config-subnav',
                'aria-label' => get_string('commerce_configuration_internal_navigation', 'local_subscriptions'),
            ]
        );
    }

    private function __construct() {
    }
}
