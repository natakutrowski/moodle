<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\identity;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/**
 * Secondary navigation for Commerce Identity Operations.
 *
 * New operations (merge, provisioning) can be added here without duplicating
 * tabs across every page.
 */
final class CommerceCustomerIdentityNavigationRenderer {
    public const RECONCILIATION = 'reconciliation';
    public const SIMILARITIES = 'similarities';
    public const MERGE = 'merge';
    public const RELATIONSHIPS = 'relationships';
    public const PROVISIONING = 'provisioning';
    public const LEGACY_QUALITY = 'legacy_quality';

    public static function render(string $active): string {
        $items = [
            self::RECONCILIATION => [
                'icon' => 'fa fa-link',
                'label' => get_string(
                    'commerce_identity_nav_reconciliation',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/customer-identities/index.php'
                ),
            ],
            self::SIMILARITIES => [
                'icon' => 'fa fa-search',
                'label' => get_string(
                    'commerce_identity_nav_similarities',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/customer-identities/similarities.php'
                ),
            ],
            self::MERGE => [
                'icon' => 'fa fa-random',
                'label' => get_string(
                    'commerce_identity_nav_merge',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/customer-identities/merge.php'
                ),
            ],
            self::RELATIONSHIPS => [
                'icon' => 'fa fa-sitemap',
                'label' => get_string('commerce_identity_nav_relationships', 'local_subscriptions'),
                'url' => new moodle_url('/local/subscriptions/admin/commerce/customer-identities/relationships.php'),
            ],
            self::PROVISIONING => [
                'icon' => 'fa fa-user-plus',
                'label' => get_string(
                    'commerce_identity_nav_provisioning',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/customer-identities/provisioning.php'
                ),
            ],
            self::LEGACY_QUALITY => [
                'icon' => 'fa fa-shield',
                'label' => get_string(
                    'commerce_identity_nav_legacy_quality',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/customer-identities/legacy-quality.php'
                ),
            ],
        ];

        $links = [];
        foreach ($items as $key => $item) {
            $attributes = [
                'class' => 'nav-link' . ($active === $key ? ' active' : ''),
            ];
            if ($active === $key) {
                $attributes['aria-current'] = 'page';
            }
            $links[] = html_writer::tag(
                'li',
                html_writer::link(
                    $item['url'],
                    html_writer::tag('i', '', [
                        'class' => $item['icon'],
                        'aria-hidden' => 'true',
                    ])
                    . html_writer::span(
                        s($item['label'])
                    ),
                    $attributes
                ),
                ['class' => 'nav-item']
            );
        }

        return html_writer::tag(
            'nav',
            html_writer::tag(
                'ul',
                implode('', $links),
                [
                    'class' =>
                        'nav crm-identity-operations-nav-list',
                ]
            ),
            [
                'class' => 'crm-identity-operations-nav',
                'aria-label' => get_string(
                    'commerce_identity_nav_label',
                    'local_subscriptions'
                ),
            ]
        );
    }
}
