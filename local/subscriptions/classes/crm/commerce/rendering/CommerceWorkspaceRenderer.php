<?php

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Renders the Commerce workspace entry points.
 */
final class CommerceWorkspaceRenderer {

    public static function render(): string {
        $cards = [];

        foreach (self::definitions() as $definition) {
            if (
                !AdminSecurity::can(
                    $definition['capability']
                )
            ) {
                continue;
            }

            $cards[] = self::render_card(
                $definition
            );
        }

        if ($cards === []) {
            return html_writer::div(
                get_string(
                    'crm_commerce_no_access',
                    'local_subscriptions'
                ),
                'alert alert-info'
            );
        }

        return html_writer::div(
            implode('', $cards),
            'row'
        );
    }

    /**
     * @return array<int, array{
     *     title: string,
     *     description: string,
     *     icon: string,
     *     url: moodle_url,
     *     capability: string
     * }>
     */
    private static function definitions(): array {
        return [
            [
                'title' => get_string('commerce_products_title', 'local_subscriptions'),
                'description' => get_string('commerce_products_card_description', 'local_subscriptions'),
                'icon' => '🛍',
                'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
                'capability' => Capabilities::MANAGE_CONFIGURATION,
            ],

            [
                'title' => get_string(
                    'crm_commerce_subscriptions_title',
                    'local_subscriptions'
                ),
                'description' => get_string(
                    'crm_commerce_subscriptions_description',
                    'local_subscriptions'
                ),
                'icon' => '📋',
                'url' => new moodle_url(
                    subscription_config::
                        user_subscriptions_page()
                ),
                'capability' =>
                    Capabilities::MANAGE_SUBSCRIPTIONS,
            ],

            [
                'title' => get_string(
                    'crm_commerce_imports_title',
                    'local_subscriptions'
                ),
                'description' => get_string(
                    'crm_commerce_imports_description',
                    'local_subscriptions'
                ),
                'icon' => '📥',
                'url' => new moodle_url(
                    subscription_config::
                        import_csv_page()
                ),
                'capability' =>
                    Capabilities::MANAGE_SUBSCRIPTIONS,
            ],

            [
                'title' => get_string(
                    'crm_commerce_configuration_title',
                    'local_subscriptions'
                ),
                'description' => get_string(
                    'crm_commerce_configuration_description',
                    'local_subscriptions'
                ),
                'icon' => '⚙',
                'url' => new moodle_url(
                    subscription_config::
                        manage_page()
                ),
                'capability' =>
                    Capabilities::MANAGE_CONFIGURATION,
            ],

            [
                'title' => get_string(
                    'crm_commerce_digital_products_title',
                    'local_subscriptions'
                ),
                'description' => get_string(
                    'crm_commerce_digital_products_description',
                    'local_subscriptions'
                ),
                'icon' => '📦',
                'url' => new moodle_url(
                    subscription_config::
                        digital_products_admin_page()
                ),
                'capability' =>
                    Capabilities::MANAGE_DIGITAL,
            ],

            [
                'title' => get_string(
                    'crm_commerce_digital_purchases_title',
                    'local_subscriptions'
                ),
                'description' => get_string(
                    'crm_commerce_digital_purchases_description',
                    'local_subscriptions'
                ),
                'icon' => '🧾',
                'url' => new moodle_url(
                    subscription_config::
                        digital_purchases_admin_page()
                ),
                'capability' =>
                    Capabilities::VIEW_DIGITAL,
            ],

            [
                'title' => get_string(
                    'crm_commerce_statistics_title',
                    'local_subscriptions'
                ),
                'description' => get_string(
                    'crm_commerce_statistics_description',
                    'local_subscriptions'
                ),
                'icon' => '📊',
                'url' => new moodle_url(
                    subscription_config::
                        digital_sales_stats_admin_page()
                ),
                'capability' =>
                    Capabilities::VIEW_STATISTICS,
            ],
        ];
    }

    /**
     * @param array{
     *     title: string,
     *     description: string,
     *     icon: string,
     *     url: moodle_url,
     *     capability: string
     * } $definition
     */
    private static function render_card(
        array $definition
    ): string {
        $content =
            html_writer::div(
                $definition['icon'],
                'local-subscriptions-admin-card-icon',
                [
                    'aria-hidden' => 'true',
                ]
            ) .
            html_writer::tag(
                'h2',
                s($definition['title']),
                [
                    'class' => 'h5 mb-2',
                ]
            ) .
            html_writer::tag(
                'p',
                s($definition['description']),
                [
                    'class' =>
                        'text-muted mb-0',
                ]
            );

        $link = html_writer::link(
            $definition['url'],
            html_writer::div(
                $content,
                'card-body'
            ),
            [
                'class' =>
                    'card h-100 ' .
                    'local-subscriptions-admin-card ' .
                    'text-decoration-none',
            ]
        );

        return html_writer::div(
            $link,
            'col-md-6 col-xl-4 mb-4'
        );
    }
}