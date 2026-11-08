<?php
// CampusFR V4.3 — full-viewport sandbox beneath native header; offers shared currency.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();
final class commerce_gustave_visual_v43_test extends \advanced_testcase {
    public function test_parent_preview_viewport_and_sandbox(): void {
        global $CFG;
        $base = $CFG->dirroot . '/local/subscriptions/';
        $preview = file_get_contents($base . 'admin/commerce/showrooms/lovable_cms_preview.php');
        $css = file_get_contents($base . 'styles/lovable_campus_preview_v43.css');
        $frame = file_get_contents($base . 'admin/commerce/showrooms/lovable_cms_frame.php');
        self::assertStringContainsString('lovable_campus_preview_v43.css', $preview);
        self::assertStringContainsString('campusfr-preview-currency-v41', $preview);
        self::assertStringContainsString("'sandbox' => 'allow-scripts'", $preview);
        self::assertStringNotContainsString('allow-same-origin', $preview);
        self::assertStringContainsString('body.campusfr-lovable-cms-preview', $css);
        self::assertStringContainsString('position: fixed !important', $css);
        self::assertStringContainsString('height: 100dvh !important', $css);
        self::assertStringContainsString('z-index: 8100 !important', $css);
        self::assertStringContainsString('z-index: 8000 !important', $css);
        self::assertStringContainsString('presentationVideoUrl', $frame);
        self::assertStringContainsString("connect-src 'none'", $frame);
        self::assertStringContainsString("form-action 'none'", $frame);
        self::assertStringContainsString("frame-src 'none'", $frame);
    }
    public function test_transaction_and_no_checkout(): void {
        global $CFG;
        $dir = $CFG->dirroot . '/local/subscriptions/cli/lovable_builder/';
        $up = file_get_contents($dir . 'upgrade_cms_commerce_preview_v43.php');
        $restore = file_get_contents($dir . 'restore_cms_commerce_preview_v43.php');
        self::assertStringContainsString('commerce_preview_v43', $up);
        self::assertStringContainsString('commerce_preview_v42', $up);
        self::assertStringContainsString('count($catalog[\'page\']) !== 65', $up);
        self::assertStringContainsString('start_delegated_transaction', $up);
        self::assertStringContainsString('start_delegated_transaction', $restore);
        self::assertStringContainsString('products-before.json', $restore);
        self::assertStringNotContainsString('cart_action.php', $up);
    }
}
