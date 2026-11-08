<?php
// CampusFR V4.2 — video restricted to the existing Moodle File API, currency read-only.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();
final class commerce_gustave_visual_v42_test extends \advanced_testcase {
    public function test_inline_video_uses_native_file_api_and_preserves_sandbox(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $frame = file_get_contents($root.'admin/commerce/showrooms/lovable_cms_frame.php');
        $preview = file_get_contents($root.'admin/commerce/showrooms/lovable_cms_preview.php');
        self::assertStringContainsString('CommerceShowroomBridgeVideoService', $frame);
        self::assertStringContainsString('->get_url((int)$id)', $frame);
        self::assertStringContainsString('->get_url(6)', $frame);
        self::assertStringContainsString('presentationVideoUrl', $frame);
        self::assertStringContainsString('pluginfile.php/', $frame);
        self::assertStringContainsString('media-src data:', $frame);
        self::assertStringContainsString("connect-src 'none'", $frame);
        self::assertStringContainsString("form-action 'none'", $frame);
        self::assertStringContainsString("frame-src 'none'", $frame);
        self::assertStringContainsString('Link::read_only_review($DB, $id, $currency)', $frame);
        self::assertStringContainsString("'sandbox' => 'allow-scripts'", $preview);
        self::assertStringNotContainsString('allow-same-origin', $preview);
        self::assertStringNotContainsString('cart_action.php', $frame);
    }
    public function test_no_payment_or_migrations_added(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $upgrade=file_get_contents($root.'cli/lovable_builder/upgrade_cms_commerce_preview_v42.php');
        $restore=file_get_contents($root.'cli/lovable_builder/restore_cms_commerce_preview_v42.php');
        self::assertStringContainsString('EXPECTED_PARENT_BACKUP', $upgrade);
        self::assertStringContainsString('commerce_preview_v42', $upgrade);
        self::assertStringContainsString('count($catalog[\'page\']) !== 65', $upgrade);
        self::assertStringContainsString('start_delegated_transaction', $upgrade);
        self::assertStringContainsString('start_delegated_transaction', $restore);
        self::assertStringContainsString('products-before.json', $restore);
        self::assertStringNotContainsString('cart_action.php', $upgrade);
    }
}
