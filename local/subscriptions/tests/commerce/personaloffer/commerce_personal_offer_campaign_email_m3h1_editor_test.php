<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_campaign_email_m3h1_editor_test extends \advanced_testcase {
    public function test_campaign_email_builder_uses_rich_editor_and_preserves_active_language(): void {
        global $CFG;
        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/personal-offers/campaign_email.php'
        );

        $this->assertStringContainsString('CommerceMailBuilderEditorRenderer::rich_editor(', $source);
        $this->assertStringContainsString('CommerceMailBuilderEditorRenderer::tag_palette(', $source);
        $this->assertStringContainsString("'name' => \$field . 'format_' . \$language", $source);
        $this->assertStringContainsString("'name' => 'activelang'", $source);
        $this->assertStringContainsString("'language' => \$activelanguage", $source);
        $this->assertStringContainsString("'data-language' => \$language", $source);
    }

    public function test_builder_persists_html_formats_instead_of_forcing_plain_text(): void {
        global $CFG;
        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/campaign/'
            . 'CommercePersonalOfferCampaignEmailBuilderService.php'
        );

        $this->assertStringContainsString("\$bodyformat = (int)(\$data['bodyformat']", $source);
        $this->assertStringContainsString("\$closingformat = (int)(\$data['closingformat']", $source);
        $this->assertStringContainsString('$bodyformat,', $source);
        $this->assertStringContainsString('$closingformat,', $source);
        $this->assertStringContainsString('editorial_empty', $source);
        $this->assertStringNotContainsString('M3C intentionally uses plain text fields', $source);
    }
}
