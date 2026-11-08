<?php
// Commerce read-only preview: server data controls all card prices, never a payment.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;

final class commerce_gustave_lovable_dynamic_preview_test extends \advanced_testcase {
    public function test_preview_payload_contains_only_reviewed_commerce_fields(): void {
        $file = make_request_directory() . '/commerce-v1-test.zip';
        $id = 'articles_alf_reimport';
        $z = new \ZipArchive();
        self::assertTrue($z->open($file, \ZipArchive::CREATE));
        $prefix = 'draft/' . $id . '/';
        $z->addFromString($prefix . 'manifest.json', json_encode([
            'id' => $id, 'entry' => 'dist/main.js',
            'css' => 'dist/style.css', 'content' => 'content/ru.json',
        ], JSON_THROW_ON_ERROR));
        $z->addFromString($prefix . 'content/ru.json', '{"page":{"header_001":"Campus"}}');
        $z->addFromString($prefix . 'content/fr.json', '{"page":{}}');
        $z->addFromString($prefix . 'content/en.json', '{"page":{}}');
        $z->addFromString($prefix . 'dist/main.js',
            'const x="data-campusfr-bridge campusfr-showroom-bridge/v1";'
            . 'new URL("assets/a.png",import.meta.url)');
        $z->addFromString($prefix . 'dist/style.css', ':host{font-family:Nunito}');
        $z->addFromString($prefix . 'dist/assets/a.png', 'png-dummy');
        $z->addFromString('NON_PUBLIABLE.txt', 'STRICTEMENT NON PUBLIABLE');
        self::assertTrue($z->close());
        $commerce = ['currency' => 'RUB', 'offers' => [
            ['role' => 'course', 'sku' => 'COURSE_ACCESS.ARTICLES', 'hasprice' => true,
                'priceformatted' => '2 500,00 RUB', 'amountminor' => 250000],
        ]];
        $html = Preview::render_frame($file, $id, null, $commerce);
        self::assertStringContainsString('2 500,00 RUB', $html);
        self::assertStringContainsString('COURSE_ACCESS.ARTICLES', $html);
        self::assertStringContainsString('"currency":"RUB"', $html);
        self::assertStringNotContainsString('name="sesskey"', $html);
        self::assertStringNotContainsString('cart_action.php', $html);
    }

    public function test_sandbox_and_draft_only_remain_mandatory(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $frame = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_frame.php');
        $page = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_preview.php');
        $cli = file_get_contents($root . 'cli/lovable_builder/upgrade_cms_commerce_preview.php');
        self::assertStringContainsString('Link::read_only_review', $frame);
        self::assertStringContainsString('Currency::active_currencies', $frame);
        self::assertStringContainsString("connect-src 'none'", $frame);
        self::assertStringContainsString("form-action 'none'", $frame);
        self::assertStringContainsString('sandbox allow-scripts', $frame);
        self::assertStringNotContainsString('allow-same-origin', $page);
        self::assertStringContainsString("'commerce_connected'] = false", $cli);
        self::assertStringContainsString("'commerce_preview_v1'] = true", $cli);
    }
}
