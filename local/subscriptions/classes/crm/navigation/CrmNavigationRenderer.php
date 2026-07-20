<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

use context;
use html_writer;

/**
 * Renders the persistent CRM application navigation.
 */
final class CrmNavigationRenderer {

    public function __construct(
        private readonly CrmNavigationRegistry $registry =
            new CrmNavigationRegistry()
    ) {
    }

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

        $items = $this->registry
            ->visible_items($context);

        if (empty($items)) {
            return '';
        }

        $listitems = [];

        foreach ($items as $item) {
            $isactive =
                $item->key === $activekey;

            $attributes = [
                'class' =>
                    'crm-app-navigation-link' .
                    ($isactive ? ' is-active' : ''),
            ];

            if ($isactive) {
                $attributes['aria-current'] = 'page';
            }

            $icon = html_writer::span(
                s($item->icon),
                'crm-app-navigation-icon',
                [
                    'aria-hidden' => 'true',
                ]
            );

            $label = html_writer::span(
                s($item->label),
                'crm-app-navigation-label'
            );

            $link = html_writer::link(
                $item->url,
                $icon . $label,
                $attributes
            );

            $listitems[] = html_writer::tag(
                'li',
                $link,
                [
                    'class' =>
                        'crm-app-navigation-item',
                ]
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

        return html_writer::tag(
            'nav',
            $list,
            [
                'class' =>
                    'crm-app-navigation',
                'aria-label' => get_string(
                    'crm_app_navigation',
                    'local_subscriptions'
                ),
            ]
        );
    }
}