<?php
namespace local_subscriptions;
final class commerce_personal_offer_campaign_m14_test extends \advanced_testcase {
    public function test_m14_editor_and_scheduler_contract(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents($root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferCampaignMailRenderer.php');
        $editor = file_get_contents($root . '/admin/commerce/personal-offers/campaign_email.php');
        $builder = file_get_contents($root . '/classes/commerce/mail/builder/CommerceMailBuilder.php');
        $buildereditor = file_get_contents($root . '/classes/commerce/mail/builder/CommerceMailBuilderEditorRenderer.php');
        $task = file_get_contents($root . '/classes/task/process_personal_offer_scheduled_campaigns_task.php');
        $this->assertStringContainsString('{{offer}}', $builder);
        $this->assertStringContainsString('{{cta|campus_pink}}', $builder);
        $this->assertStringContainsString('{{cta|legacy_blue}}', $builder);
        $this->assertStringContainsString('navigator.clipboard', $buildereditor);
        $this->assertStringContainsString('CommerceMailBuilderCtaRenderer', $renderer);
        $this->assertStringContainsString('CommerceMailBuilderEditorRenderer::tag_palette', $editor);
        $this->assertStringContainsString('queue_missing_campaign', $task);
        $this->assertStringContainsString("scheduledat <= :now", $task);
    }
}
