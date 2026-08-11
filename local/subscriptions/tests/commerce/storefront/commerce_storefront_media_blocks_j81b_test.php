<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_media_blocks_j81b_test
        extends \advanced_testcase {

    public function test_schema_and_editor_support_structured_media(): void {
        global $CFG;

        $schema = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/page/'
            . 'CommerceStorefrontSectionSchema.php'
        );
        $editor = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/'
            . 'storefront.php'
        );

        $this->assertStringContainsString("'h5p'", $schema);
        foreach ([
            'section_image_file_',
            'section_video_file_',
            'section_video_poster_',
            'section_h5p_contentid_',
            'CommerceStorefrontH5pService',
        ] as $needle) {
            $this->assertStringContainsString($needle, $editor);
        }
    }

    public function test_file_api_uses_isolated_media_slots(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'content/CommerceStorefrontContentFileService.php'
        );

        foreach ([
            'store_uploaded_slot',
            'get_slot_url',
            'slot_diagnostic',
            'delete_slot',
        ] as $method) {
            $this->assertStringContainsString($method, $service);
        }
        $this->assertStringContainsString(
            '$file->get_filepath() === $slotpath',
            $service
        );
    }

    public function test_public_templates_support_video_and_h5p_iframes(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_section.mustache'
        );
        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'page/CommerceStorefrontPagePresenter.php'
        );

        $this->assertStringContainsString('{{#ish5p}}', $template);
        $this->assertStringContainsString('{{#isembed}}', $template);
        $this->assertStringContainsString(
            'youtube-nocookie.com/embed',
            $presenter
        );
        $this->assertStringContainsString(
            'player.vimeo.com/video',
            $presenter
        );
    }

    public function test_h5p_selector_uses_content_bank_packages(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'content/CommerceStorefrontH5pService.php'
        );

        $this->assertStringContainsString(
            "'contentbank_content'",
            $service
        );
        $this->assertStringContainsString(
            "'contentbank'",
            $service
        );
        $this->assertStringContainsString(
            "'/h5p/embed.php'",
            $service
        );
    }
}
