<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_campaign_m14_1_test extends advanced_testcase {
    public function test_structural_offer_marker_is_preserved_until_layout_split(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/CommercePersonalOfferCampaignMailRenderer.php'
        );

        self::assertStringContainsString('CAMPUSFR_OFFER_MARKER_', $renderer);
        self::assertStringContainsString("str_replace(\$offerplaceholder, \$offersentinel", $renderer);
        self::assertStringContainsString("preg_split('/\\{\\{offer\\}\\}/i'", $renderer);
    }

    public function test_secondary_cta_is_available_as_clickable_structural_tag(): void {
        global $CFG;

        $editor = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/builder/CommerceMailBuilder.php'
        );
        self::assertStringContainsString("'tag' => '{{secondary_cta}}'", $editor);
        self::assertStringContainsString("'scope' => 'personal_offer'", $editor);
    }

    public function test_campaign_footer_image_is_publicly_delivered_and_used_as_override(): void {
        global $CFG;

        $lib = file_get_contents($CFG->dirroot . '/local/subscriptions/lib.php');
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );

        self::assertStringContainsString('CommercePersonalOfferCampaignMailFooterImageService::FILEAREA', $lib);
        self::assertStringContainsString('editorial_footerimageurl', $template);
        self::assertStringContainsString('Backward-compatible fallback', $template);
    }

    public function test_cta_markup_uses_single_table_without_nested_border_wrapper(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/CommercePersonalOfferCampaignMailRenderer.php'
        );

        self::assertStringContainsString("\$secondaryctamarker = '{{secondary_cta}}';", $renderer);
        self::assertStringNotContainsString("padding:2px;border-radius:13px", $renderer);
    }
}
