<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

use context;
use context_system;
use html_writer;
use local_subscriptions\crm\inbox\services\InboxUnreadCountService;

/**
 * Renders the persistent CRM application navigation.
 */
final class CrmNavigationRenderer {

    /**
     * Stable identifier of the collapsible navigation body.
     */
    private const PANEL_ID =
        'crm-app-navigation-panel';

    public function __construct(
        private readonly CrmNavigationRegistry $registry =
            new CrmNavigationRegistry(),
        private readonly InboxUnreadCountService $unreadcount =
            new InboxUnreadCountService()
    ) {
    }

    /**
     * Renders the CRM application navigation.
     *
     * @param string $activekey Active navigation key.
     * @param context|null $context Current page context.
     * @return string
     */
    public function render(
        string $activekey,
        ?context $context = null
    ): string {
        if (
            !CrmNavigationKeys::is_valid(
                $activekey
            )
        ) {
            throw new \coding_exception(
                'Invalid CRM navigation active key: ' .
                $activekey
            );
        }

        $context ??= context_system::instance();

        $items = $this->registry
            ->visible_items($context);

        // Showrooms belong to the Commerce workspace from 7.95N onward.
        $effectiveactivekey = $activekey === CrmNavigationKeys::SHOWROOMS
            ? CrmNavigationKeys::COMMERCE
            : $activekey;

        if ($items === []) {
            return '';
        }

        $inboxunread = $this->unreadcount
            ->count();

        $listitems = [];

        foreach ($items as $item) {
            $listitems[] =
                $this->render_item(
                    $item,
                    $item->key === $effectiveactivekey,
                    $context,
                    $item->key === CrmNavigationKeys::INBOX
                        ? $inboxunread
                        : 0
                );
        }

        $list = html_writer::tag(
            'ul',
            implode('', $listitems),
            [
                'class' =>
                    'crm-app-navigation-list',
            ]
        );

        $panel = html_writer::div(
            $list,
            'crm-app-navigation-panel',
            [
                'id' =>
                    self::PANEL_ID,

                'data-crm-navigation-panel' =>
                    '1',
            ]
        );

        return html_writer::tag(
            'nav',
            $panel,
            [
                'class' =>
                    'crm-app-navigation',

                'aria-label' =>
                    get_string(
                        'crm_app_navigation',
                        'local_subscriptions'
                    ),

                'data-crm-navigation' =>
                    '1',
            ]
        );
    }

    /**
     * Renders one navigation item.
     *
     * @param CrmNavigationItem $item
     * @param bool $isactive
     * @param context|null $context
     * @param int $badgecount Optional numeric badge value.
     * @return string
     */
    private function render_item(
        CrmNavigationItem $item,
        bool $isactive,
        ?context $context = null,
        int $badgecount = 0
    ): string {
        $attributes = [
            'class' =>
                'crm-app-navigation-link' .
                ($isactive ? ' is-active' : ''),

            'data-crm-navigation-link' =>
                '1',

            'data-crm-navigation-key' =>
                $item->key,
        ];

        if ($isactive) {
            $attributes['aria-current'] =
                'page';
        }

        $icon = html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa ' . s($item->icon) . ' crm-app-navigation-icon',
                'aria-hidden' => 'true',
            ]
        );

        $label = html_writer::span(
            s($item->label),
            'crm-app-navigation-label'
        );

        $badge = '';

        if ($badgecount > 0) {
            $displaycount = $badgecount > 99
                ? '99+'
                : (string)$badgecount;

            $badgelabel = get_string(
                'crm_nav_inbox_unread_badge_o3',
                'local_subscriptions',
                $badgecount
            );

            $badge = html_writer::span(
                s($displaycount),
                'crm-app-navigation-badge ' .
                    'crm-app-navigation-badge-inbox',
                [
                    'aria-label' => $badgelabel,
                    'title' => $badgelabel,
                ]
            );
        }

        $link = html_writer::link(
            $item->url,
            $icon . $label . $badge,
            $attributes
        );

        $children = array_values(array_filter(
            $item->children,
            static fn(array $child): bool => has_capability($child['capability'], $context)
        ));

        $submenu = '';
        $toggle = '';
        if ($children !== []) {
            $toggle = html_writer::tag(
                'button',
                html_writer::tag('i', '', ['class' => 'fa fa-angle-down', 'aria-hidden' => 'true']),
                [
                    'type' => 'button',
                    'class' => 'crm-app-navigation-menu-toggle',
                    'aria-label' => get_string('crm_nav_open_menu', 'local_subscriptions', $item->label),
                    'aria-expanded' => 'false',
                    'data-crm-navigation-menu-toggle' => '1',
                ]
            );

            $subitems = [];
            foreach ($children as $child) {
                $subitems[] = html_writer::tag(
                    'li',
                    html_writer::link(
                        $child['url'],
                        html_writer::tag(
                            'i',
                            '',
                            [
                                'class' => 'fa ' . s($child['icon']) . ' crm-app-navigation-submenu-icon',
                                'aria-hidden' => 'true',
                            ]
                        ) . html_writer::span(
                            s($child['label']),
                            'crm-app-navigation-submenu-label'
                        ),
                        ['class' => 'crm-app-navigation-submenu-link']
                    ),
                    ['class' => 'crm-app-navigation-submenu-item']
                );
            }
            $submenu = html_writer::tag(
                'ul',
                implode('', $subitems),
                ['class' => 'crm-app-navigation-submenu']
            );
        }

        return html_writer::tag(
            'li',
            html_writer::div($link . $toggle, 'crm-app-navigation-item-main') . $submenu,
            [
                'class' => 'crm-app-navigation-item'
                    . ($item->key === CrmNavigationKeys::DASHBOARD ? ' is-dashboard' : '')
                    . ($item->key === CrmNavigationKeys::HELP ? ' is-help' : '')
                    . ($children !== [] ? ' has-submenu' : ''),
            ]
        );
    }
}