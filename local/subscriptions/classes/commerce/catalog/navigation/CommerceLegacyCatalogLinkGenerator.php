<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\navigation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;

/** Centralises links to the real administration screens. */
final class CommerceLegacyCatalogLinkGenerator {
    public static function plan_view_url(int $planid): \moodle_url {
        return new \moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $planid]);
    }

    public static function plan_edit_url(int $planid): \moodle_url {
        return new \moodle_url(subscription_config::commerce_plan_edit_page(), ['id' => $planid]);
    }

    public static function scope_view_url(int $scopeid): \moodle_url {
        return new \moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $scopeid]);
    }

    public static function scope_edit_url(int $scopeid): \moodle_url {
        return new \moodle_url(subscription_config::commerce_access_scope_edit_page(), ['id' => $scopeid]);
    }

    public static function digital_edit_url(int $productid): \moodle_url {
        return new \moodle_url(subscription_config::digital_product_edit_admin_page(), ['id' => $productid]);
    }
}
