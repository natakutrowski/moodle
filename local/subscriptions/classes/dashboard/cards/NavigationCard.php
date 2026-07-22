<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;

final class NavigationCard implements DashboardCard {

    public static function render(): string {
        $cards = self::cards();

        $out = '';

        foreach ($cards as $card) {
            if (!AdminSecurity::can($card['capability'])) {
                continue;
            }

            $out .= html_writer::div(
                html_writer::link(
                    $card['url'],
                    html_writer::div(
                        html_writer::div($card['icon'], 'local-subscriptions-admin-card-icon') .
                        html_writer::tag('h3', $card['title'], ['class' => 'h5 mb-2']) .
                        html_writer::tag('p', $card['description'], ['class' => 'text-muted mb-0']),
                        'card-body'
                    ),
                    ['class' => 'card h-100 local-subscriptions-admin-card text-decoration-none']
                ),
                'col-md-6 col-xl-4 mb-4'
            );
        }

        return $out;
    }

    private static function cards(): array {
        return [
            [
                'title' => get_string(
                    'admin_card_crm_users_title',
                    'local_subscriptions'
                ),
                'description' => get_string(
                    'admin_card_crm_users_desc',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    subscription_config::
                        admin_users_page()
                ),
                'icon' => '👤',
                'capability' =>
                    Capabilities::VIEW_USERS,
            ],

            [
                'title' => get_string(
                    'admin_card_commerce_title',
                    'local_subscriptions'
                ),
                'description' => get_string(
                    'admin_card_commerce_description',
                    'local_subscriptions'
                ),
                'url' => new moodle_url(
                    subscription_config::
                        admin_commerce_page()
                ),
                'icon' => '◆',
                'capability' =>
                    Capabilities::VIEW_DASHBOARD,
            ],
        ];
    }
}