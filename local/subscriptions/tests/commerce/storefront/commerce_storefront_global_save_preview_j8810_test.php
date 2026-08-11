<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** @coversNothing */
final class commerce_storefront_global_save_preview_j8810_test extends \advanced_testcase {
    public function test_global_save_preserves_durable_media_and_ajax_updates_readiness(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $editor = file_get_contents($root . '/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php');
        $endpoint = file_get_contents($root . '/admin/commerce/products/storefront_section_save.php');
        $javascript = file_get_contents($root . '/amd/src/storefront_builder_drag_drop.js');
        $admin = file_get_contents($root . '/admin/commerce/products/storefront.php');
        $files = file_get_contents($root . '/classes/commerce/storefront/content/CommerceStorefrontContentFileService.php');

        $this->assertStringContainsString('preserve_durable_section_data', $editor);
        $this->assertStringContainsString(
            '$editorialstatus = $statusservice->status($persistedsection);',
            $endpoint
        );
        $this->assertStringContainsString(
            "'ready' => \$editorialstatus === CommerceStorefrontSectionStatusService::READY",
            $endpoint
        );
        $this->assertStringContainsString(
            "'editorialstatus' => \$editorialstatus",
            $endpoint
        );
        $this->assertStringContainsString('data-section-readiness', $admin);
        $this->assertStringContainsString("readiness.classList.toggle('text-bg-success', ready)", $javascript);
        $this->assertStringContainsString('data-section-preview', $admin);
        $this->assertStringContainsString('backup_dedicated_slots', $files);
        $this->assertStringContainsString('restore_dedicated_slots', $files);
        $this->assertStringContainsString("['image', 'video', 'poster', 'h5p']", $files);
    }
}
