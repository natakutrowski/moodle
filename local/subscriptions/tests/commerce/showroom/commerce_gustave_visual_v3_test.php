<?php
// Gustave V3: native header, currency relay, visual draft only.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();
final class commerce_gustave_visual_v3_test extends \advanced_testcase {
    public function test_currency_relay_is_opaque_and_preview_is_not_published(): void {
        global $CFG;
        $base = $CFG->dirroot . '/local/subscriptions/';
        $page = file_get_contents($base . 'admin/commerce/showrooms/lovable_cms_preview.php');
        $frame = file_get_contents($base . 'admin/commerce/showrooms/lovable_cms_frame.php');
        self::assertStringContainsString('echo $OUTPUT->header();', $page);
        self::assertStringContainsString('js_init_code', $page);
        self::assertStringContainsString('event.source!==frame.contentWindow', $page);
        self::assertStringContainsString("event.origin!=='null'", $page);
        self::assertStringContainsString("data.type!=='campusfr-preview-currency-v3'", $page);
        self::assertStringNotContainsString('campusfr-lovable-campus-toolbar', $page);
        self::assertStringContainsString('availablecurrencies', $frame);
        self::assertStringContainsString("connect-src 'none'", $frame);
        self::assertStringContainsString("form-action 'none'", $frame);
        self::assertStringNotContainsString('allow-same-origin', $page);
    }
    public function test_visual_patch_retains_v2_commerce_and_existing_preview_isolation(): void {
        global $CFG;
        $base = $CFG->dirroot . '/local/subscriptions/';
        $css = file_get_contents($base . 'styles/lovable_campus_preview.css');
        $up = file_get_contents($base . 'cli/lovable_builder/upgrade_cms_commerce_preview_v3.php');
        self::assertStringContainsString('position: fixed', $css); // Temporary mode preserved.
        self::assertStringContainsString('z-index: 8000', $css);
        self::assertStringContainsString('campusfr-lovable-cms-preview', $css);
        self::assertStringContainsString("'commerce_preview_v3'", $up);
        self::assertStringContainsString("'commerce_connected'] = false", $up);
        self::assertStringContainsString('data-commerce-review-v3', $up);
        self::assertStringContainsString('start_delegated_transaction()', $up);
    }
}
