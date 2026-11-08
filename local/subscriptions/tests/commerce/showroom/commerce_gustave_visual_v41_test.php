<?php
// CampusFR Gustave V4.1: parent relay and opaque frame remain data-only.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();

final class commerce_gustave_visual_v41_test extends \advanced_testcase {
    public function test_currency_request_is_authenticated_read_only_and_strict(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $endpoint = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_currency_v41.php');
        $preview = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_preview.php');
        self::assertStringContainsString('require_login()', $endpoint);
        self::assertStringContainsString("require_capability('local/subscriptions:manage_showrooms'", $endpoint);
        self::assertStringContainsString('Currency::active_currencies($DB)', $endpoint);
        self::assertStringContainsString('Draft::require_draft', $endpoint);
        self::assertStringContainsString('Link::read_only_review', $endpoint);
        self::assertStringNotContainsString('cartaction', $endpoint);
        self::assertStringNotContainsString('sesskey', $endpoint);
        self::assertStringContainsString('campusfr-preview-currency-v41', $preview);
        self::assertStringContainsString('campusfr-preview-currency-result-v41', $preview);
        self::assertStringContainsString('event.source!==frame.contentWindow', $preview);
        self::assertStringContainsString('event.origin!=="null"', $preview);
        self::assertStringContainsString('credentials:"same-origin"', $preview);
        self::assertStringNotContainsString('window.location.assign', $preview);
        self::assertStringContainsString("'sandbox' => 'allow-scripts'", $preview);
        self::assertStringNotContainsString('allow-same-origin', $preview);
    }

    public function test_csp_and_rollback_not_weakened(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $frame = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_frame.php');
        $upgrade = file_get_contents($root . 'cli/lovable_builder/upgrade_cms_commerce_preview_v41.php');
        $restore = file_get_contents($root . 'cli/lovable_builder/restore_cms_commerce_preview_v41.php');
        self::assertStringContainsString("connect-src 'none'", $frame);
        self::assertStringContainsString("form-action 'none'", $frame);
        self::assertStringContainsString('Link::read_only_review', $frame);
        self::assertStringContainsString('commerce_preview_v3', $upgrade);
        self::assertStringContainsString('commerce_preview_v41', $upgrade);
        self::assertStringContainsString('EXPECTED_V3_SHA256', $upgrade);
        self::assertStringContainsString('count($catalog[\'page\']) !== 65', $upgrade);
        self::assertStringContainsString('start_delegated_transaction', $upgrade);
        self::assertStringContainsString('start_delegated_transaction', $restore);
        self::assertStringContainsString('products-before.json', $restore);
        self::assertStringNotContainsString('checkout', $upgrade);
    }
}
