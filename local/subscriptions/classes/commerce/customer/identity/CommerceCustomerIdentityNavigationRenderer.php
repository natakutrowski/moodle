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
                'label' => get_string(
                    'commerce_identity_nav_reconciliation',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/customer-identities/index.php'
                ),
            ],
            self::SIMILARITIES => [
                'label' => get_string(
                    'commerce_identity_nav_similarities',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/customer-identities/similarities.php'
                ),
            ],
            self::MERGE => [
                'label' => get_string(
                    'commerce_identity_nav_merge',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/customer-identities/merge.php'
                ),
            ],
            self::RELATIONSHIPS => [
                'label' => get_string('commerce_identity_nav_relationships', 'local_subscriptions'),
                'url' => new moodle_url('/local/subscriptions/admin/commerce/customer-identities/relationships.php'),
            ],
            self::PROVISIONING => [
                'label' => get_string(
                    'commerce_identity_nav_provisioning',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    '/local/subscriptions/admin/commerce/customer-identities/provisioning.php'
                ),
            ],
            self::LEGACY_QUALITY => [
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
                html_writer::link($item['url'], $item['label'], $attributes),
                ['class' => 'nav-item']
            );
        }

        return html_writer::tag(
            'nav',
            html_writer::tag('ul', implode('', $links), ['class' => 'nav nav-tabs']),
            [
                'class' => 'mb-4',
                'aria-label' => get_string(
                    'commerce_identity_nav_label',
                    'local_subscriptions'
                ),
            ]
        );
    }
}
