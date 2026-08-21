<?php

declare(strict_types=1);

namespace local_subscriptions\crm\work\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Shared secondary navigation for the Work Management workspace.
 */
final class WorkSectionNavigationRenderer {

    public const ITEMS = 'items';
    public const CREATE = 'create';
    public const TEAMS = 'teams';

    public static function render(string $activekey): string {
        $items = [
            [
                'key' => self::ITEMS,
                'label' => get_string(
                    'crm_work_title',
                    'local_subscriptions'
                ),
                'icon' => 'fa-tasks',
                'url' => new moodle_url(
                    subscription_config::
                        admin_work_items_page()
                ),
                'visible' => true,
            ],
            [
                'key' => self::CREATE,
                'label' => get_string(
                    'crm_work_create',
                    'local_subscriptions'
                ),
                'icon' => 'fa-plus-circle',
                'url' => new moodle_url(
                    subscription_config::
                        admin_work_item_create_page()
                ),
                'visible' => AdminSecurity::can(
                    Capabilities::MANAGE_WORK_ITEMS
                ),
            ],
            [
                'key' => self::TEAMS,
                'label' => get_string(
                    'crm_work_teams',
                    'local_subscriptions'
                ),
                'icon' => 'fa-users',
                'url' => new moodle_url(
                    subscription_config::
                        admin_work_teams_page()
                ),
                'visible' => AdminSecurity::can(
                    Capabilities::MANAGE_WORK_CONFIGURATION
                ),
            ],
        ];

        $links = [];

        foreach ($items as $item) {
            if (!$item['visible']) {
                continue;
            }

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
                        'crm-work-section-nav-link'
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
                'crm-work-section-nav-list'
            ),
            [
                'class' => 'crm-work-section-nav',
                'aria-label' => get_string(
                    'crm_work_section_navigation_n127a',
                    'local_subscriptions'
                ),
            ]
        );
    }
}
