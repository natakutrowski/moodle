<?php

declare(strict_types=1);

namespace local_subscriptions\crm\admin_tools\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Secondary navigation for CRM administrator tools.
 */
final class AdminToolsSectionNavigationRenderer {

    public const TOOLS = 'tools';
    public const HISTORY = 'history';

    public static function render(string $activekey): string {
        $items = [
            [
                'key' => self::TOOLS,
                'label' => get_string(
                    'crm_admin_tools_title',
                    'local_subscriptions'
                ),
                'icon' => 'fa-wrench',
                'url' => new moodle_url(
                    subscription_config::
                        admin_crm_tools_page()
                ),
            ],
            [
                'key' => self::HISTORY,
                'label' => get_string(
                    'crm_admin_tool_history',
                    'local_subscriptions'
                ),
                'icon' => 'fa-history',
                'url' => new moodle_url(
                    subscription_config::
                        admin_crm_tool_history_page()
                ),
            ],
        ];

        $links = [];

        foreach ($items as $item) {
            $isactive = $item['key'] === $activekey;

            $links[] = html_writer::link(
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
                [
                    'class' =>
                        'crm-admin-tools-section-nav-link'
                        . ($isactive ? ' active' : ''),
                    'aria-current' =>
                        $isactive ? 'page' : null,
                ]
            );
        }

        return html_writer::tag(
            'nav',
            html_writer::div(
                implode('', $links),
                'crm-admin-tools-section-nav-list'
            ),
            [
                'class' =>
                    'crm-admin-tools-section-nav',
                'aria-label' =>
                    get_string(
                        'crm_admin_tools_navigation_n128a',
                        'local_subscriptions'
                    ),
            ]
        );
    }
}
