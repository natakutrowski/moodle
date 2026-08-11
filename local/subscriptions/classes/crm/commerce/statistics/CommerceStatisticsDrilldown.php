<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;
use moodle_url;

/** Builds safe operational links from Commerce statistics metrics. */
final class CommerceStatisticsDrilldown {
    public static function metric_url(string $metrickey, ?string $currency = null): moodle_url {
        $params = [];
        if ($currency !== null && $currency !== '') {
            $params['currency'] = strtoupper($currency);
        }

        switch ($metrickey) {
            case 'failed_payments':
                $params['status'] = 'failed';
                return new moodle_url(subscription_config::digital_purchases_admin_page(), $params);

            case 'refunded_payments':
            case 'refunded_minor':
                $params['status'] = 'refunded';
                return new moodle_url(subscription_config::digital_purchases_admin_page(), $params);

            case 'successful_payments':
            case 'paid_minor':
            case 'net_paid_minor':
                $params['status'] = 'paid';
                return new moodle_url(subscription_config::digital_purchases_admin_page(), $params);

            case 'pending_fulfillments':
                return new moodle_url(subscription_config::admin_commerce_page(), $params);

            default:
                return new moodle_url(subscription_config::admin_commerce_page(), $params);
        }
    }

    /** @return array<int,array{label:string,url:moodle_url}> */
    public static function operational_links(): array {
        return [
            [
                'label' => get_string('commerce_statistics_open_subscriptions', 'local_subscriptions'),
                'url' => new moodle_url(subscription_config::user_subscriptions_page()),
            ],
            [
                'label' => get_string('commerce_statistics_open_digital_purchases', 'local_subscriptions'),
                'url' => new moodle_url(subscription_config::digital_purchases_admin_page()),
            ],
            [
                'label' => get_string('commerce_statistics_open_products', 'local_subscriptions'),
                'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
            ],
        ];
    }
}
