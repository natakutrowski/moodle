<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\crm\commerce\navigation\CommerceSectionNavigationRegistry;

final class commerce_offers_access_n71_test extends advanced_testcase {
    public function test_n71_unifies_offers_and_grants_in_primary_commerce_navigation(): void {
        $items = (new CommerceSectionNavigationRegistry())->all_items();
        $keys = array_map(static fn($item): string => $item->key, $items);
        $this->assertContains(CommerceSectionNavigationRegistry::OFFERS_ACCESS, $keys);
        $this->assertNotContains(CommerceSectionNavigationRegistry::PERSONAL_OFFERS, $keys);
        $this->assertNotContains(CommerceSectionNavigationRegistry::GRANTS, $keys);
    }

    public function test_n71_workspace_has_four_clear_tabs_and_unified_campaign_page(): void {
        $renderer = file_get_contents(__DIR__ . '/../../../classes/crm/commerce/rendering/CommerceOffersAccessNavigationRenderer.php');
        $overview = file_get_contents(__DIR__ . '/../../../admin/commerce/offers-access/index.php');
        $campaigns = file_get_contents(__DIR__ . '/../../../admin/commerce/offers-access/campaigns.php');
        $this->assertStringContainsString("self::OFFERS", $renderer);
        $this->assertStringContainsString("self::GRANTS", $renderer);
        $this->assertStringContainsString("self::CAMPAIGNS", $renderer);
        $this->assertStringContainsString('crm-offers-access-metrics', $overview);
        $this->assertStringContainsString('CommercePersonalOfferCampaignManager', $campaigns);
        $this->assertStringContainsString('CommerceBulkGrantCampaignService', $campaigns);
    }
}
