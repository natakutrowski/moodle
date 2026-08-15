<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use context;
use context_system;
use html_writer;
use local_subscriptions\crm\commerce\navigation\CommerceSectionNavigationRegistry;

/** Renders the shared secondary navigation for the Commerce workspace. */
final class CommerceSectionNavigationRenderer {
    public const OVERVIEW = CommerceSectionNavigationRegistry::OVERVIEW;
    public const PRODUCTS = CommerceSectionNavigationRegistry::PRODUCTS;
    public const SHOWROOMS = CommerceSectionNavigationRegistry::SHOWROOMS;
    public const PURCHASES = CommerceSectionNavigationRegistry::PURCHASES;
    public const SUBSCRIPTIONS = CommerceSectionNavigationRegistry::SUBSCRIPTIONS;
    public const DIGITAL_PURCHASES = CommerceSectionNavigationRegistry::DIGITAL_PURCHASES;
    public const DIGITAL_PRODUCTS = CommerceSectionNavigationRegistry::DIGITAL_PRODUCTS;
    public const MAIL = CommerceSectionNavigationRegistry::MAIL;
    public const IDENTITIES = CommerceSectionNavigationRegistry::IDENTITIES;
    public const UNFINISHED_CHECKOUTS = CommerceSectionNavigationRegistry::UNFINISHED_CHECKOUTS;
    public const PERSONAL_OFFERS = CommerceSectionNavigationRegistry::PERSONAL_OFFERS;
    public const GRANTS = CommerceSectionNavigationRegistry::GRANTS;
    public const STATISTICS = CommerceSectionNavigationRegistry::STATISTICS;
    public const CONFIGURATION = CommerceSectionNavigationRegistry::CONFIGURATION;

    public static function render(string $activekey, ?context $context = null): string {
        $context ??= context_system::instance();
        $registry = new CommerceSectionNavigationRegistry();
        $items = [];

        foreach ($registry->visible_items($context) as $item) {
            $isactive = $item->key === $activekey;
            $classes = 'crm-commerce-section-nav-link' . ($isactive ? ' active' : '');
            if ($item->key === CommerceSectionNavigationRegistry::OVERVIEW) {
                $classes .= ' crm-commerce-section-nav-link-overview';
            }
            if ($item->key === CommerceSectionNavigationRegistry::CONFIGURATION) {
                $classes .= ' crm-commerce-section-nav-link-configuration';
            }
            $attributes = [
                'class' => $classes,
            ];

            if ($isactive) {
                $attributes['aria-current'] = 'page';
            }

            $items[] = html_writer::link(
                $item->url,
                html_writer::span(
                    html_writer::tag('i', '', [
                        'class' => 'fa ' . s($item->icon),
                        'aria-hidden' => 'true',
                    ]),
                    'crm-commerce-section-nav-icon',
                    ['aria-hidden' => 'true']
                ) .
                html_writer::span(
                    s($item->label),
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
            html_writer::div(implode('', $items), 'crm-commerce-section-nav-list crm-commerce-section-nav-list-natural'),
            [
                'class' => 'crm-commerce-section-nav',
                'aria-label' => get_string(
                    'crm_commerce_section_navigation',
                    'local_subscriptions'
                ),
            ]
        );
    }
}
