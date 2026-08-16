<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n710_test extends advanced_testcase {
    public function test_guided_phase_marker_is_not_exposed_in_urls(): void {
        $root = dirname(__DIR__, 3);
        foreach ([
            '/admin/commerce/offers-access/create.php',
            '/admin/commerce/personal-offers/create.php',
            '/admin/commerce/personal-offers/campaign_edit.php',
        ] as $relative) {
            $source = file_get_contents($root . $relative);
            self::assertStringNotContainsString(
                'n7guided',
                $source,
                $relative
            );
        }
    }

    public function test_offer_conditions_component_is_shared_by_individual_and_campaign_forms(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommercePersonalOfferConditionsRenderer.php'
        );
        self::assertStringContainsString(
            'public static function pricing',
            $renderer
        );
        self::assertStringContainsString(
            'public static function validity',
            $renderer
        );

        foreach ([
            '/admin/commerce/personal-offers/create.php',
            '/admin/commerce/personal-offers/campaign_edit.php',
        ] as $relative) {
            $source = file_get_contents($root . $relative);
            self::assertStringContainsString(
                'CommercePersonalOfferConditionsRenderer::pricing',
                $source
            );
            self::assertStringContainsString(
                'CommercePersonalOfferConditionsRenderer::validity',
                $source
            );
        }
    }

    public function test_individual_and_campaign_validity_support_timezone_duration_and_no_expiry(): void {
        $root = dirname(__DIR__, 3);
        foreach ([
            '/admin/commerce/personal-offers/create.php',
            '/admin/commerce/personal-offers/campaign_edit.php',
        ] as $relative) {
            $source = file_get_contents($root . $relative);
            self::assertStringContainsString('validitytimezone', $source);
            self::assertStringContainsString('validitymode', $source);
            self::assertStringContainsString('noexpiration', $source);
        }
    }

    public function test_offer_list_uses_business_product_labels_and_sales_style_filter_scope(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/index.php'
        );

        foreach ([
            'business_product_label',
            'crm-sales-filter-panel',
            "'today'",
            "'custom'",
            'commerce_filters_apply',
            'crm-offers-access-condition-price',
            'crm-sales-row-menu-section',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_many_action_activates_campaigns_navigation_before_kind_selection(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/offers-access/create.php'
        );

        self::assertStringContainsString(
            "\$localnav = \$audience === 'many'",
            $source
        );
        self::assertStringContainsString(
            'CommerceOffersAccessNavigationRenderer::CAMPAIGNS',
            $source
        );
    }

    public function test_overview_has_real_recent_activity_queries(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/offers-access/index.php'
        );

        foreach ([
            'local_subs_commerce_offer',
            'local_subs_commerce_grant',
            'local_subs_commerce_offer_campaign',
            'crm-offers-access-recent-activity-row',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }


    public function test_personal_offer_list_reuses_sales_actions_and_period_scope(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/index.php'
        );

        foreach ([
            'fa fa-eye me-1',
            'btn btn-sm btn-outline-primary',
            'crm-sales-row-menu-toggle',
            'crm-sales-row-menu-section',
            'commerce_result_scope_period_range',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
        self::assertStringNotContainsString(
            'fa fa-search crm-sales-filter-search-icon',
            $source
        );
    }

    public function test_campaign_conditions_are_mandatory_and_actions_are_in_main_column(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_edit.php'
        );

        $conditions = strpos(
            $source,
            "get_string('commerce_offers_access_config_conditions'"
        );
        $actions = strpos(
            $source,
            "'d-flex gap-2 crm-offers-access-form-actions'"
        );
        $endmain = strpos(
            $source,
            'CommerceOffersAccessConfigurationRenderer::end_main();'
        );
        $summary = strpos(
            $source,
            'CommerceOffersAccessConfigurationRenderer::summary('
        );

        self::assertNotFalse($conditions);
        self::assertNotFalse($actions);
        self::assertNotFalse($endmain);
        self::assertNotFalse($summary);
        self::assertLessThan($endmain, $actions);
        self::assertLessThan($summary, $endmain);
    }


    public function test_offer_result_scope_matches_sales_and_campaign_advanced_block_is_closed(): void {
        $root = dirname(__DIR__, 3);

        $offers = file_get_contents(
            $root . '/admin/commerce/personal-offers/index.php'
        );
        foreach ([
            'crm-result-summary',
            'crm-result-scope-label',
            'crm-result-scope-pill',
            'commerce_result_scope_period_range',
        ] as $needle) {
            self::assertStringContainsString($needle, $offers);
        }

        $campaign = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_edit.php'
        );
        $advancedclose = strpos(
            $campaign,
            "echo html_writer::end_tag('details');"
        );
        $conditions = strpos(
            $campaign,
            "get_string('commerce_offers_access_config_conditions'"
        );
        $actions = strpos(
            $campaign,
            "'d-flex gap-2 crm-offers-access-form-actions'"
        );
        $summary = strpos(
            $campaign,
            'CommerceOffersAccessConfigurationRenderer::summary('
        );

        self::assertNotFalse($advancedclose);
        self::assertNotFalse($conditions);
        self::assertNotFalse($actions);
        self::assertNotFalse($summary);
        self::assertLessThan($conditions, $advancedclose);
        self::assertLessThan($actions, $conditions);
        self::assertLessThan($summary, $actions);
    }

    public function test_shared_validity_uses_timezone_selector(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommercePersonalOfferConditionsRenderer.php'
        );

        self::assertStringContainsString(
            '\\DateTimeZone::listIdentifiers()',
            $renderer
        );
        self::assertStringContainsString(
            "html_writer::select(\n            \$timezoneoptions",
            $renderer
        );
        self::assertStringNotContainsString(
            "'list' => 'validity-timezones'",
            $renderer
        );
    }


    public function test_period_scope_uses_explicit_date_and_time_everywhere(): void {
        $root = dirname(__DIR__, 3);

        foreach ([
            '/admin/commerce/personal-offers/index.php' => '$offerperiodlabel',
            '/admin/commerce/purchases/index.php' => '$salesperiodlabel',
            '/admin/commerce/mail/index.php' => '$mailperiodlabel',
        ] as $relative => $labelvar) {
            $source = file_get_contents($root . $relative);
            self::assertStringContainsString($labelvar, $source, $relative);
            self::assertStringContainsString(
                "get_string('strftimedatetimeshort', 'langconfig')",
                $source,
                $relative
            );
            self::assertStringContainsString(
                'commerce_result_scope_period_range',
                $source,
                $relative
            );
        }

        $offers = file_get_contents(
            $root . '/admin/commerce/personal-offers/index.php'
        );
        self::assertStringContainsString(
            '$timeto = $now;',
            $offers
        );
        self::assertStringContainsString(
            '$parsedate($customto, true)',
            $offers
        );
    }

    public function test_timezone_selector_displays_current_gmt_offset(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommercePersonalOfferConditionsRenderer.php'
        );

        foreach ([
            '\\DateTimeZone::listIdentifiers()',
            'getOffset($nowutc)',
            "'GMT %s%d:%02d'",
            "html_writer::select(\n            \$timezoneoptions",
        ] as $needle) {
            self::assertStringContainsString($needle, $renderer);
        }
    }

    public function test_campaign_target_product_is_inside_conditions_section(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_edit.php'
        );

        $conditions = strpos(
            $source,
            "get_string('commerce_offers_access_config_conditions'"
        );
        $product = strpos(
            $source,
            "get_string('commerce_personal_offer_target'",
            $conditions
        );
        $pricing = strpos(
            $source,
            'CommercePersonalOfferConditionsRenderer::pricing',
            $product
        );
        $endsection = strpos(
            $source,
            'CommerceOffersAccessConfigurationRenderer::end_section();',
            $conditions
        );

        self::assertNotFalse($conditions);
        self::assertNotFalse($product);
        self::assertNotFalse($pricing);
        self::assertNotFalse($endsection);
        self::assertGreaterThan($conditions, $product);
        self::assertGreaterThan($product, $pricing);
        self::assertGreaterThan($pricing, $endsection);
        self::assertStringNotContainsString(
            "get_string('commerce_personal_offer_offer_title'",
            $source
        );
    }

    public function test_n710_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081601;',
            $version
        );
    }
}
