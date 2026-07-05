<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

final class CommandTypes {

    public static function action(): string {
        return get_string('command_center_type_action', 'local_subscriptions');
    }

    public static function user(): string {
        return get_string('command_center_type_user', 'local_subscriptions');
    }

    public static function digital_product(): string {
        return get_string('command_center_type_digital_product', 'local_subscriptions');
    }

    public static function digital_purchase(): string {
        return get_string('command_center_type_digital_purchase', 'local_subscriptions');
    }

    public static function subscription(): string {
        return get_string('command_center_type_subscription', 'local_subscriptions');
    }
}