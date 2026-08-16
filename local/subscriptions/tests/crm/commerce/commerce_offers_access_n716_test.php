<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_offers_access_n716_test extends \advanced_testcase {
    public function test_grant_preview_uses_supported_idempotency_api(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/grants/mail_preview.php');
        $this->assertStringNotContainsString('CommerceMailIdempotencyKey::from_parts', $source);
        $this->assertStringContainsString('CommerceMailIdempotencyKey::normalise', $source);
    }

    public function test_campaigns_use_type_processing_and_contextual_actions(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/offers-access/campaigns.php');
        $this->assertStringContainsString("commerce_offers_access_campaign_type", $source);
        $this->assertStringContainsString("commerce_offers_access_campaign_processing", $source);
        $this->assertStringContainsString('crm-campaign-action-outline', $source);
        $this->assertStringContainsString('crm-sales-row-actions-menu', $source);
        $this->assertStringContainsString('crm-sales-filter-card', $source);
    }

    public function test_recent_grant_campaign_header_is_the_highlighted_surface(): void {
        $css = file_get_contents(__DIR__ . '/../../../styles.css');
        $this->assertStringContainsString('.crm-offers-access-recent > .crm-offers-access-section-heading', $css);
        $this->assertStringContainsString('background: #f3f5f8 !important', $css);
    }
}
