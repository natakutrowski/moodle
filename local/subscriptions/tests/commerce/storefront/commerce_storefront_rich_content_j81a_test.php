<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_rich_content_j81a_test
        extends \advanced_testcase {

    public function test_editor_uses_tinymce_and_draft_file_api(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/'
            . 'storefront_builder.php'
        );

        $this->assertStringContainsString(
            'editors_get_preferred_editor',
            $source
        );
        $this->assertStringContainsString(
            '->use_editor(',
            $source
        );
        $this->assertStringContainsString(
            'section_content_draft_',
            $source
        );
        $this->assertStringContainsString(
            'section_content_itemid_',
            $source
        );
        $this->assertStringContainsString(
            'save_editor(',
            $source
        );
    }

    public function test_save_editor_without_draft_keeps_content_unchanged(): void {
        $this->resetAfterTest();

        $service = new \local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService(
            \context_system::instance()
        );
        $content = '<p>Template content without an initialised draft.</p>';

        $saved = $service->save_editor(0, 0, $content);

        $this->assertSame($content, $saved);
    }

    public function test_save_editor_repairs_plain_draft_source_metadata(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance((int)$USER->id);
        get_file_storage()->create_file_from_string(
            [
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftitemid,
                'filepath' => '/',
                'filename' => 'legacy-source.txt',
                'source' => 'https://example.invalid/legacy-source.txt',
            ],
            'Storefront draft content'
        );

        $service = new \local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService(
            \context_system::instance()
        );
        $itemid = $service->ensure_item_id();
        $content = '<p>Content with a legacy draft source.</p>';

        $saved = $service->save_editor(
            $draftitemid,
            $itemid,
            $content
        );

        $this->assertSame($content, $saved);
        $stored = get_file_storage()->get_file(
            \context_system::instance()->id,
            'local_subscriptions',
            'storefront_content',
            $itemid,
            '/',
            'legacy-source.txt'
        );
        $this->assertInstanceOf(\stored_file::class, $stored);
        $this->assertSame(
            'https://example.invalid/legacy-source.txt',
            $stored->get_source()
        );
    }

    public function test_rich_text_metadata_preserves_media_item_id(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'admin/CommerceStorefrontPageEditor.php'
        );

        $this->assertStringContainsString(
            "'mediaitemid'",
            $source
        );
        $this->assertStringContainsString(
            "'section_content_itemid_' . \$index",
            $source
        );
    }

    public function test_public_rendering_rewrites_pluginfile_urls(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'content/CommerceStorefrontContentFileService.php'
        );
        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'page/CommerceStorefrontPagePresenter.php'
        );
        $pluginfile = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/lib.php'
        );

        $this->assertStringContainsString(
            "FILEAREA = 'storefront_content'",
            $service
        );
        $this->assertStringContainsString(
            'file_prepare_draft_area',
            $service
        );
        $this->assertStringContainsString(
            'file_save_draft_area_files',
            $service
        );
        $this->assertStringContainsString(
            'file_rewrite_pluginfile_urls',
            $service
        );
        $this->assertStringContainsString(
            'rewrite_for_display',
            $presenter
        );
        $this->assertStringContainsString(
            'CommerceStorefrontContentFileService::FILEAREA',
            $pluginfile
        );
    }

    public function test_rich_content_files_are_isolated_per_section(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'content/CommerceStorefrontContentFileService.php'
        );

        $this->assertStringContainsString(
            'random_int(100000000, 2000000000)',
            $service
        );
        $this->assertStringContainsString(
            'ensure_item_id',
            $service
        );
    }
}
