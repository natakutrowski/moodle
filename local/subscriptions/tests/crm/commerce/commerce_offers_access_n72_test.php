<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n72_test extends advanced_testcase {
    public function test_n72_guided_launcher_routes_four_business_paths(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/offers-access/create.php'
        );

        foreach ([
            "'offer:one'",
            "'offer:many'",
            "'grant:one'",
            "'grant:many'",
            '/personal-offers/create.php',
            '/personal-offers/campaign_edit.php',
            'add_manual_subscription_page()',
            '/grants/bulk.php',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_n72_exposes_business_workflow_without_replacing_engines(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommerceOffersAccessWorkflowRenderer.php'
        );

        foreach ([
            'BENEFICIARIES',
            'CONFIGURATION',
            'VERIFICATION',
            'EXECUTION',
            'commerce_offers_access_workflow_beneficiaries',
            'commerce_offers_access_workflow_verification',
        ] as $needle) {
            self::assertStringContainsString($needle, $renderer);
        }
    }

    public function test_existing_offer_and_grant_forms_join_the_n7_workflow(): void {
        $root = dirname(__DIR__, 3);
        $files = [
            '/admin/commerce/personal-offers/create.php' => "'offer',\n    'one'",
            '/admin/commerce/personal-offers/campaign_edit.php' => "'offer',\n    'many'",
            '/admin/commerce/grants/bulk.php' => "'grant',\n    'many'",
            '/admin/subscriptions/add.php' => "'grant',\n        'one'",
        ];

        foreach ($files as $relative => $needle) {
            $source = file_get_contents($root . $relative);
            self::assertStringContainsString(
                'CommerceOffersAccessWorkflowRenderer::render',
                $source,
                $relative
            );
            self::assertStringContainsString(
                $needle,
                $source,
                $relative
            );
        }
    }

    public function test_campaign_page_has_one_unified_new_campaign_entry(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/offers-access/campaigns.php'
        );

        self::assertStringContainsString(
            'commerce_offers_access_new_campaign',
            $source
        );
        self::assertStringContainsString(
            "'audience' => 'many'",
            $source
        );
        self::assertStringNotContainsString(
            'commerce_offers_access_new_offer_campaign',
            $source
        );
        self::assertStringNotContainsString(
            'commerce_offers_access_new_grant_campaign',
            $source
        );
    }

    public function test_n72_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
