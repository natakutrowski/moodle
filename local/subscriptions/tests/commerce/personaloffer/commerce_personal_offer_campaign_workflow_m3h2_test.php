<?php
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_campaign_workflow_m3h2_test extends \advanced_testcase {
    public function test_campaign_view_exposes_guided_workflow(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/personal-offers/campaign_view.php');
        $this->assertStringContainsString('CommerceOffersAccessCampaignRenderer::workflow', $source);
        $this->assertStringContainsString('campaign_email_preview.php', $source);
        $this->assertStringContainsString('campaign-audience', $source);
        $this->assertStringContainsString('$emailshowroomname', $source);
    }
    public function test_email_save_continues_to_preview_in_active_language(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/personal-offers/campaign_email.php');
        $this->assertStringContainsString('campaign_email_preview.php', $source);
        $this->assertStringContainsString("'language' => \$activelanguage", $source);
        $this->assertStringContainsString('commerce_personal_offer_campaign_email_saved_preview_next', $source);
    }
    public function test_workflow_does_not_introduce_client_side_pricing(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/personal-offers/campaign_view.php');
        $this->assertStringNotContainsString("'price' =>", $source);
        $this->assertStringNotContainsString('offeramountminor', $source);
    }
}
