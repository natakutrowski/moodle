<?php

declare(strict_types=1);

namespace local_subscriptions\crm\help;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\subscription_config;
use moodle_url;

final class HelpInternalNavigationRenderer {

    public static function render(
        string $active = 'home'
    ): string {
        $items = [
            [
                'key' => 'home',
                'label' => get_string(
                    'crm_help_nav_home_n1210d',
                    'local_subscriptions'
                ),
                'icon' => 'fa-home',
                'url' => new moodle_url(
                    subscription_config::
                        admin_help_page()
                ),
            ],
            [
                'key' => 'guides',
                'label' => get_string(
                    'crm_help_nav_guides_n1210d',
                    'local_subscriptions'
                ),
                'icon' => 'fa-list',
                'url' => new moodle_url(
                    subscription_config::
                        admin_help_page(),
                    ['section' => 'guides']
                ),
            ],
            [
                'key' => 'articles',
                'label' => get_string(
                    'crm_help_nav_articles_n1210d',
                    'local_subscriptions'
                ),
                'icon' => 'fa-book',
                'url' => new moodle_url(
                    subscription_config::
                        admin_help_page(),
                    ['section' => 'articles']
                ),
            ],
            [
                'key' => 'diagnostics',
                'label' => get_string(
                    'crm_help_nav_diagnostics_n1210d',
                    'local_subscriptions'
                ),
                'icon' => 'fa-stethoscope',
                'url' => new moodle_url(
                    subscription_config::
                        admin_help_diagnostics_page()
                ),
            ],
        ];

        $content = '';

        foreach ($items as $item) {
            $attributes = [
                'class' =>
                    'crm-help-internal-nav__link',
            ];

            if ($item['key'] === $active) {
                $attributes['class'] .= ' active';
                $attributes['aria-current'] = 'page';
            }

            $content .= html_writer::link(
                $item['url'],
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' =>
                            'fa ' . $item['icon'],
                        'aria-hidden' => 'true',
                    ]
                )
                . html_writer::span(
                    s($item['label'])
                ),
                $attributes
            );
        }

        return html_writer::tag(
            'nav',
            $content,
            [
                'class' =>
                    'crm-help-internal-nav',
                'aria-label' => get_string(
                    'crm_help_internal_navigation_n1210d',
                    'local_subscriptions'
                ),
            ]
        );
    }
}
