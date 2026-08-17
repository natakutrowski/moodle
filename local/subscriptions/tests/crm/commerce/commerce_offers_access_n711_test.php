<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n711_test extends advanced_testcase {
    public function test_individual_grant_route_uses_semantic_workspace_not_dev_phase_marker(): void {
        $root = dirname(__DIR__, 3);
        $chooser = file_get_contents(
            $root . '/admin/commerce/offers-access/create.php'
        );
        $page = file_get_contents(
            $root . '/admin/subscriptions/add.php'
        );

        self::assertStringContainsString(
            "['workspace' => 'grants']",
            $chooser
        );
        self::assertStringContainsString(
            "optional_param('workspace', '', PARAM_ALPHA) === 'grants'",
            $page
        );
        self::assertStringNotContainsString('n7guided', $page);
        self::assertStringContainsString(
            'CommerceOffersAccessNavigationRenderer::GRANTS',
            $page
        );
    }

    public function test_individual_grant_form_uses_access_semantics_and_blue_action_family(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root . '/renderer/user_subs_renderer.php'
        );

        foreach ([
            'bool $grantworkspace = false',
            'commerce_manual_grant_beneficiary_section',
            'commerce_manual_grant_mode_legacy_access',
            'commerce_manual_grant_mode_commerce_product',
            'commerce_manual_grant_legacy_section',
            'commerce_manual_grant_commerce_section',
            'crm-grant-individual-form',
            'crm-grant-action-primary',
        ] as $needle) {
            self::assertStringContainsString($needle, $renderer);
        }
    }

    public function test_bulk_grant_actions_use_blue_attribution_colour_family(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/bulk.php'
        );

        self::assertStringContainsString(
            'crm-grant-action-outline',
            $source
        );
        self::assertStringContainsString(
            'crm-grant-action-primary',
            $source
        );
        self::assertStringNotContainsString(
            "'class' => 'btn btn-primary'",
            $source
        );
        self::assertStringContainsString(
            "['workspace' => 'grants']",
            $source
        );
    }

    public function test_personal_offer_actions_header_is_left_aligned(): void {
        $root = dirname(__DIR__, 3);
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            '.local-subscriptions-commerce-personal-offers-page'
                . "\n"
                . '.crm-offers-access-list-table th:last-child',
            $styles
        );
        self::assertStringContainsString(
            'text-align: left;',
            $styles
        );
    }

    public function test_n711_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
