<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

use context;
use html_writer;

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
            new CrmNavigationRegistry()
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

        $items = $this->registry
            ->visible_items($context);

        if ($items === []) {
            return '';
        }

        $listitems = [];

        foreach ($items as $item) {
            $listitems[] =
                $this->render_item(
                    $item,
                    $item->key === $activekey
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
     * @return string
     */
    private function render_item(
        CrmNavigationItem $item,
        bool $isactive
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

        $icon = html_writer::span(
            s($item->icon),
            'crm-app-navigation-icon',
            [
                'aria-hidden' =>
                    'true',
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

        return html_writer::tag(
            'li',
            $link,
            [
                'class' =>
                    'crm-app-navigation-item',
            ]
        );
    }
}