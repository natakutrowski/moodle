<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\subscription_config;

final class AdminEntityLinks {

    public static function user(int $userid, string $label, array $attributes = []): string {
        if ($userid <= 0) {
            return $label;
        }

        return html_writer::link(
            new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]),
            $label,
            ['class' => 'crm-entity-link crm-entity-link-user'] + $attributes
        );
    }

    public static function digital_product(int $productid, string $label, array $attributes = []): string {
        if ($productid <= 0) {
            return $label;
        }

        return html_writer::link(
            new moodle_url(subscription_config::digital_product_edit_admin_page(), ['id' => $productid]),
            $label,
            ['class' => 'crm-entity-link crm-entity-link-product'] + $attributes
        );
    }

    public static function course(int $courseid, string $label, array $attributes = []): string {
        if ($courseid <= 0) {
            return $label;
        }

        return html_writer::link(
            new moodle_url('/course/view.php', ['id' => $courseid]),
            $label,
            ['class' => 'crm-entity-link crm-entity-link-course'] + $attributes
        );
    }

    public static function subscription(int $subscriptionid, string $label, array $attributes = []): string {
        if ($subscriptionid <= 0) {
            return $label;
        }

        return html_writer::link(
            new moodle_url(subscription_config::user_subscription_edit_page(), ['id' => $subscriptionid]),
            $label,
            ['class' => 'crm-entity-link crm-entity-link-subscription'] + $attributes
        );
    }
}