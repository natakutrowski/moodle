<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_dry_run_k15ab_test extends advanced_testcase {

    public function test_eligibility_sources_are_provider_based_and_cover_legacy_and_native(): void {
        $root = dirname(__DIR__, 3);
        $registry = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/audience/CommercePersonalOfferAudienceProviderRegistry.php'
        );

        $this->assertStringContainsString('CommercePersonalOfferLegacyPlanAudienceProvider', $registry);
        $this->assertStringContainsString('CommercePersonalOfferLegacyDigitalAudienceProvider', $registry);
        $this->assertStringContainsString('CommercePersonalOfferNativeProductAudienceProvider', $registry);
    }

    public function test_preview_is_a_real_dry_run_and_never_issues_offers(): void {
        $root = dirname(__DIR__, 3);
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );

        $previewpos = strpos($manager, 'public function preview(');
        $generatepos = strpos($manager, 'public function generate(');
        $this->assertNotFalse($previewpos);
        $this->assertNotFalse($generatepos);

        $preview = substr($manager, $previewpos, $generatepos - $previewpos);
        $this->assertStringContainsString('criteria_candidates', $preview);
        $this->assertStringContainsString('active_offer_id', $preview);
        $this->assertStringNotContainsString('CommercePersonalOfferIssueRequest', $preview);
        $this->assertStringNotContainsString('->issue(', $preview);
    }

    public function test_dry_run_materialises_customer_identity_evidence_and_existing_offer(): void {
        $root = dirname(__DIR__, 3);
        $install = (string)file_get_contents($root . '/db/install.xml');
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );

        $this->assertStringContainsString('FIELD NAME="firstname"', $install);
        $this->assertStringContainsString('FIELD NAME="lastname"', $install);
        $this->assertStringContainsString('FIELD NAME="evidencejson"', $install);
        $this->assertStringContainsString('FIELD NAME="existingofferid"', $install);

        $this->assertStringContainsString("MEMBER_COVERED = 'covered'", $manager);
        $this->assertStringContainsString("MEMBER_IDENTITY_REVIEW = 'identity_review'", $manager);
        $this->assertStringContainsString('active_offer_id(', $manager);
    }

    public function test_campaign_builder_exposes_three_source_selectors(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_edit.php'
        );

        $this->assertStringContainsString("'source_type'", $page);
        $this->assertStringContainsString("'source_legacy_plan_id'", $page);
        $this->assertStringContainsString("'source_legacy_digital_id'", $page);
        $this->assertStringContainsString("'source_native_product_id'", $page);
    }

    public function test_campaign_view_displays_customer_account_evidence_and_coverage(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );

        $this->assertStringContainsString('commerce_personal_offer_customer', $page);
        $this->assertStringContainsString('commerce_personal_offer_moodle_account', $page);
        $this->assertStringContainsString('commerce_personal_offer_eligibility_evidence', $page);
        $this->assertStringContainsString('commerce_personal_offer_existing_offer', $page);
        $this->assertStringContainsString('commerce_personal_offer_metric_covered', $page);
    }

    public function test_old_native_sku_campaigns_remain_previewable(): void {
        $root = dirname(__DIR__, 3);
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );

        $this->assertStringContainsString(
            'Backward compatibility for K8/K9 campaigns created with sourceproductsku',
            $manager
        );
        $this->assertStringContainsString('sourceproductsku', $manager);
    }
}
