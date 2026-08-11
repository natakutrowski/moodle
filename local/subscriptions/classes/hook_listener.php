<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_standard_head_html_generation;
use local_subscriptions\commerce\storefront\seo\CommerceStorefrontSeoHeadRegistry;

/** Output hook callbacks for local_subscriptions. */
final class hook_listener {
    public static function add_storefront_seo_head(
        before_standard_head_html_generation $hook
    ): void {
        $html = CommerceStorefrontSeoHeadRegistry::get();
        if ($html === '') {
            return;
        }

        $hook->add_html($html);
        CommerceStorefrontSeoHeadRegistry::clear();
    }
}
