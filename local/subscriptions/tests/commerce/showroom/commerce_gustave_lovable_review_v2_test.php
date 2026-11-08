<?php
// Gustave Builder V2: native header, currency moved into React, sandbox and preview-only upgrade.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();
final class commerce_gustave_lovable_review_v2_test extends \advanced_testcase {
    public function test_native_header_and_currency_relay_without_fixed_overlay(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $page = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_preview.php');
        $css = file_get_contents($root . 'styles/lovable_campus_preview.css');
        self::assertStringContainsString('echo $OUTPUT->header();', $page);
        self::assertStringContainsString('campusfr-preview-currency-v3', $page);
        self::assertStringContainsString('js_init_code', $page);
        self::assertStringNotContainsString('campusfr-lovable-currency-form', $page);
        self::assertStringContainsString("'sandbox' => 'allow-scripts'", $page);
        self::assertStringNotContainsString('allow-same-origin', $page);
        self::assertStringContainsString('body.campusfr-lovable-cms-preview .campusfr-lovable-campus-canvas-preview-frame', $css);
        self::assertStringContainsString('position: relative', $css);
        self::assertStringContainsString('overflow-y: auto', $css);
        self::assertStringContainsString('position: fixed', $css); // Temporary preview unaffected.
        self::assertStringContainsString('z-index: 8000', $css); // Legacy preview unchanged.
    }
    public function test_react_archive_upgrade_remains_a_non_published_read_only_draft(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $upgrade = file_get_contents($root . 'cli/lovable_builder/upgrade_cms_commerce_preview_v2.php');
        $restore = file_get_contents($root . 'cli/lovable_builder/restore_cms_commerce_preview_v2.php');
        $frame = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_frame.php');
        self::assertStringContainsString("'commerce_preview_v1'", $upgrade);
        self::assertStringContainsString("'commerce_preview_v2'", $upgrade);
        self::assertStringContainsString("'commerce_connected'] = false", $upgrade);
        self::assertStringContainsString('data-commerce-review-v2', $upgrade);
        self::assertStringContainsString('transaction = $DB->start_delegated_transaction()', $upgrade);
        self::assertStringContainsString('restore_cms_commerce_preview_v2.php', $restore);
        self::assertStringContainsString("connect-src 'none'", $frame);
        self::assertStringContainsString("form-action 'none'", $frame);
        self::assertStringContainsString('Link::read_only_review', $frame);
    }
}
