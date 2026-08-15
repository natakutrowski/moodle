<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

final class CommerceSalesNavigationRenderer {
    public const SALES = 'sales';
    public const UNFINISHED = 'unfinished';

    public static function render(string $active): string {
        $items = [
            self::SALES => [
                'label' => get_string('commerce_sales_subnav_sales', 'local_subscriptions'),
                'icon' => 'fa-shopping-cart',
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/purchases/index.php'
                ),
            ],
            self::UNFINISHED => [
                'label' => get_string('commerce_sales_subnav_unfinished', 'local_subscriptions'),
                'icon' => 'fa-hourglass-half',
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/unfinished-checkouts/index.php'
                ),
            ],
        ];

        $html = '';
        foreach ($items as $key => $item) {
            $classes = 'crm-sales-subnav-link'
                . ($active === $key ? ' active' : '');
            $html .= html_writer::link(
                $item['url'],
                html_writer::tag('i', '', [
                    'class' => 'fa ' . $item['icon'],
                    'aria-hidden' => 'true',
                ]) . html_writer::span($item['label']),
                [
                    'class' => $classes,
                    'aria-current' => $active === $key ? 'page' : null,
                ]
            );
        }

        return html_writer::tag(
            'nav',
            $html,
            [
                'class' => 'crm-sales-subnav',
                'aria-label' => get_string(
                    'commerce_sales_subnav_label',
                    'local_subscriptions'
                ),
            ]
        );
    }

    private function __construct() {}
}
