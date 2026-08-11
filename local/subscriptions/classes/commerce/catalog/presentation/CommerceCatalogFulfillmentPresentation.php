<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\presentation;

defined('MOODLE_INTERNAL') || die();

final class CommerceCatalogFulfillmentPresentation {
    public static function type_options(): array {
        return [
            '' => get_string('choose'),
            'course_access' => get_string('commerce_fulfillment_course_access', 'local_subscriptions'),
            'course_enrolment' => get_string('commerce_fulfillment_course_enrolment', 'local_subscriptions'),
            'digital_download' => get_string('commerce_fulfillment_digital_download', 'local_subscriptions'),
            'digital_product' => get_string('commerce_fulfillment_digital_product', 'local_subscriptions'),
        ];
    }

    public static function label(string $type): string {
        return self::type_options()[$type] ?? get_string('commerce_fulfillment_custom', 'local_subscriptions');
    }

    public static function resource_label(string $resourcekey, \moodle_database $db): string {
        if (preg_match('/^course:(\d+)$/', $resourcekey, $m)) {
            $name = $db->get_field('course', 'fullname', ['id' => (int)$m[1]]);
            return $name ? format_string($name) : get_string('commerce_missing_course', 'local_subscriptions', (int)$m[1]);
        }
        if (preg_match('/^digital-product:(\d+)$/', $resourcekey, $m)) {
            $name = $db->get_field('subscription_digital_product', 'name', ['id' => (int)$m[1]]);
            return $name ? format_string($name) : get_string('commerce_missing_digital_product', 'local_subscriptions', (int)$m[1]);
        }
        return $resourcekey;
    }
}
