<?php
// CampusFR V4.4: true native scrollbar with opaque, height-observed iframe.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();
final class commerce_gustave_visual_v44_test extends \advanced_testcase {
    public function test_native_scroll_bridge_and_isolation(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $php = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_preview.php');
        $css = file_get_contents($root . 'styles/lovable_campus_preview_v44.css');
        $frame = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_frame.php');
        self::assertStringContainsString('lovable_campus_preview_v44.css', $php);
        self::assertStringContainsString('campusfr-preview-height-v44', $php);
        self::assertStringContainsString('campusfr-preview-scroll-v44', $php);
        self::assertStringContainsString("'sandbox' => 'allow-scripts'", $php);
        self::assertStringNotContainsString('allow-same-origin', $php);
        self::assertStringContainsString('event.source!==frame.contentWindow', $php);
        self::assertStringContainsString("event.origin!=='null'", $php);
        self::assertStringContainsString('overflow-y: auto !important', $css);
        self::assertStringContainsString('position: sticky !important', $css);
        self::assertStringContainsString('position: relative !important', $css);
        self::assertStringContainsString('presentationVideoUrl', $frame);
        self::assertStringContainsString("connect-src 'none'", $frame);
        self::assertStringContainsString("form-action 'none'", $frame);
        self::assertStringContainsString("frame-src 'none'", $frame);
    }
    public function test_versioned_upgrade_and_rollback(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/cli/lovable_builder/';
        $up = file_get_contents($root . 'upgrade_cms_commerce_preview_v44.php');
        $restore = file_get_contents($root . 'restore_cms_commerce_preview_v44.php');
        self::assertStringContainsString("'commerce_preview_v43'", $up);
        self::assertStringContainsString("'commerce_preview_v44'", $up);
        self::assertStringContainsString('start_delegated_transaction', $up);
        self::assertStringContainsString('start_delegated_transaction', $restore);
        self::assertStringContainsString('products-before.json', $restore);
        self::assertStringNotContainsString('cart_action.php', $up);
    }
}
