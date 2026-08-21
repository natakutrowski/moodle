<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n717_test extends advanced_testcase {
    public function test_grant_mail_preview_initialises_page_context_before_render(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/mail_preview.php'
        );

        $context = strpos($source, '$PAGE->set_context($context);');
        $render = strpos(
            $source,
            'CommerceMailRuntime::template_registry()'
        );

        self::assertNotFalse($context);
        self::assertNotFalse($render);
        self::assertLessThan($render, $context);
    }

    public function test_campaigns_use_unified_filters_and_purple_action_family(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/offers-access/campaigns.php'
        );
        $styles = file_get_contents($root . '/styles.css');

        foreach ([
            'crm-sales-filter-panel',
            'crm-sales-filter-grid',
            'crm-result-scope-pill',
            'campaign-custom-period',
            'strftimedatetimeshort',
            'crm-campaign-action-primary',
            'crm-campaign-action-outline',
            'crm-sales-row-actions-menu',
        ] as $needle) {
            self::assertStringContainsString($needle, $source . $styles);
        }

        self::assertStringContainsString(
            'background: #6f55b6 !important;',
            $styles
        );
    }

    public function test_campaign_processing_is_explicitly_beneficiary_processing(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/offers-access/campaigns.php'
        );

        self::assertStringContainsString(
            'commerce_campaign_beneficiaries_processed',
            $source
        );
        self::assertStringContainsString(
            'crm-campaign-processing-copy',
            $source
        );
    }

    public function test_personal_offer_campaign_removes_redundant_preparation_and_email_panels(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );

        self::assertStringNotContainsString(
            "CommerceDesignSystemRenderer::panel(get_string('commerce_personal_offer_workflow_title'",
            $source
        );
        self::assertStringNotContainsString(
            "get_string('commerce_personal_offer_campaign_email_title'",
            $source
        );
        self::assertStringContainsString(
            'crm-offer-campaign-summary-footer',
            $source
        );
        self::assertStringContainsString(
            'commerce_personal_offer_certify_campaign',
            $source
        );
    }

    public function test_personal_offer_campaign_has_compact_population_and_email_preview(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );
        $preview = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_email_preview.php'
        );

        self::assertStringContainsString(
            'CommerceOffersAccessCampaignRenderer::metrics([',
            $source
        );
        self::assertStringContainsString(
            'commerce_offer_campaign_secondary_counts',
            $source
        );
        self::assertStringContainsString(
            "'embed' => 1",
            $source
        );
        self::assertStringContainsString(
            'if ($embed)',
            $preview
        );
    }

    public function test_personal_offer_campaign_audience_is_business_compact(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );

        self::assertStringContainsString(
            'crm-offer-campaign-audience-table',
            $source
        );
        self::assertStringContainsString(
            'crm-offers-access-preview-client-link',
            $source
        );
        self::assertStringContainsString(
            'crm-offer-campaign-client-details',
            $source
        );

        self::assertStringNotContainsString(
            "get_string('commerce_personal_offer_eligibility_evidence', 'local_subscriptions'),\n        get_string('status')",
            $source
        );
    }

    public function test_grant_campaign_uses_summary_wording_instead_of_snapshot_heading(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );

        self::assertStringContainsString(
            "get_string('commerce_offers_access_campaign_summary_title'",
            $source
        );
        self::assertStringNotContainsString(
            "get_string('commerce_bulk_grant_campaign_snapshot_title'",
            $source
        );
    }

    public function test_n717_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
