<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\admin\CommerceStorefrontSectionStatusService;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;

final class commerce_storefront_editorial_builder_l44a_test extends \advanced_testcase {
    public function test_statement_title_only_is_ready(): void {
        $this->resetAfterTest(true);

        $service = new CommerceStorefrontSectionStatusService(
            CommerceStorefrontContentFileService::create()
        );

        $this->assertSame(
            CommerceStorefrontSectionStatusService::READY,
            $service->status([
                'type' => 'features',
                'presentation' => 'statement',
                'title' => 'Editorial transition',
                'items' => [],
            ])
        );
    }

    public function test_image_text_without_image_needs_attention(): void {
        $this->resetAfterTest(true);

        $service = new CommerceStorefrontSectionStatusService(
            CommerceStorefrontContentFileService::create()
        );

        $this->assertSame(
            CommerceStorefrontSectionStatusService::ATTENTION,
            $service->status([
                'type' => 'image_text',
                'title' => 'For whom',
                'content' => '<p>Some useful content</p>',
                'mediaitemid' => 0,
                'url' => '',
            ])
        );
    }

    public function test_empty_structured_section_is_empty(): void {
        $this->resetAfterTest(true);

        $service = new CommerceStorefrontSectionStatusService(
            CommerceStorefrontContentFileService::create()
        );

        $this->assertSame(
            CommerceStorefrontSectionStatusService::EMPTY,
            $service->status([
                'type' => 'features',
                'title' => '',
                'content' => '',
                'items' => [],
            ])
        );
    }

    public function test_hero_and_cta_are_registered_as_rich_editors(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/storefront.php'
        );
        $editor = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php'
        );
        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'
        );

        $this->assertStringContainsString(
            "['hero', 'rich_text', 'image_text', 'video', 'h5p', 'cta', 'features']",
            $source
        );
        $this->assertStringContainsString(
            "case 'cta':",
            $editor
        );
        $this->assertStringContainsString(
            "'section_content_itemid_' . \$index",
            $editor
        );
        $this->assertStringContainsString(
            "'cta' => [",
            $presenter
        );
        $this->assertStringContainsString(
            '$this->format_storefront_content(',
            $presenter
        );
    }
}
