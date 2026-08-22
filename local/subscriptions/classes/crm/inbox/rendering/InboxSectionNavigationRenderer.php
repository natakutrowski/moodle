<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\repositories\InboxDraftRepository;
use local_subscriptions\crm\inbox\services\InboxUnreadCountService;
use local_subscriptions\subscription_config;
use moodle_url;

final class InboxSectionNavigationRenderer {

    public const INBOX = 'inbox';
    public const COMPOSE = 'compose';
    public const DRAFTS = 'drafts';
    public const TEMPLATES = 'templates';
    public const DIAGNOSTICS = 'diagnostics';

    public static function render(string $active): string {
        global $USER;

        $unreadcount = (new InboxUnreadCountService())->count();
        $draftcount = AdminSecurity::can(Capabilities::MANAGE_INBOX)
            ? (new InboxDraftRepository())->count_compose_drafts((int)$USER->id)
            : 0;

        $items = [
            self::INBOX => [
                'icon' => 'fa-inbox',
                'string' => 'crm_inbox_o15_nav_inbox',
                'url' => new moodle_url(
                    subscription_config::admin_inbox_page()
                ),
                'visible' => true,
                'count' => $unreadcount,
            ],
            self::COMPOSE => [
                'icon' => 'fa-pencil',
                'string' => 'crm_inbox_o15_nav_compose',
                'url' => new moodle_url(
                    subscription_config::admin_inbox_compose_page()
                ),
                'visible' => AdminSecurity::can(
                    Capabilities::MANAGE_INBOX
                ),
            ],
            self::DRAFTS => [
                'icon' => 'fa-file-text-o',
                'string' => 'crm_inbox_o15_nav_drafts',
                'url' => new moodle_url(
                    subscription_config::admin_inbox_drafts_page()
                ),
                'visible' => AdminSecurity::can(
                    Capabilities::MANAGE_INBOX
                ),
                'count' => $draftcount,
            ],
            self::TEMPLATES => [
                'icon' => 'fa-bolt',
                'string' => 'crm_inbox_o15_nav_templates',
                'url' => new moodle_url(
                    subscription_config::admin_inbox_templates_page()
                ),
                'visible' => AdminSecurity::can(
                    Capabilities::MANAGE_INBOX
                ),
            ],
            self::DIAGNOSTICS => [
                'icon' => 'fa-heartbeat',
                'string' => 'crm_inbox_o15_nav_diagnostics',
                'url' => new moodle_url(
                    subscription_config::admin_inbox_diagnostics_page()
                ),
                'visible' => AdminSecurity::can(
                    Capabilities::MANAGE_CONFIGURATION
                ),
            ],
        ];

        $links = '';

        foreach ($items as $key => $item) {
            if (!$item['visible']) {
                continue;
            }

            $class = 'crm-inbox-o15-nav-link';

            if ($key === $active) {
                $class .= ' is-active';
            }

            $attributes = ['class' => $class];

            if ($key === $active) {
                $attributes['aria-current'] = 'page';
            }

            $links .= html_writer::link(
                $item['url'],
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => 'fa ' . $item['icon'],
                        'aria-hidden' => 'true',
                    ]
                )
                . html_writer::span(
                    get_string(
                        $item['string'],
                        'local_subscriptions'
                    )
                )
                . (array_key_exists('count', $item)
                    ? html_writer::span(
                        (string)max(0, (int)$item['count']),
                        'crm-inbox-o15-nav-count',
                        [
                            'aria-hidden' => 'true',
                            'data-inbox-nav-count' => $key,
                        ]
                    )
                    : ''),
                $attributes
            );
        }

        return html_writer::tag(
            'nav',
            html_writer::div(
                $links,
                'crm-inbox-o15-nav-list'
            ),
            [
                'class' => 'crm-inbox-o15-section-nav',
                'aria-label' => get_string(
                    'crm_inbox_o15_nav_label',
                    'local_subscriptions'
                ),
            ]
        );
    }
}
