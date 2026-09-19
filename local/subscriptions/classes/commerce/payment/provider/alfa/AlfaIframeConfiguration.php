<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

/**
 * H12.5.1 opt-in switch for embedding Alfa's hosted Payment Page.
 */
final class AlfaIframeConfiguration {
    public static function is_enabled(): bool {
        return (bool)get_config(
            'local_subscriptions',
            'alfa_iframe_enabled'
        );
    }
}
