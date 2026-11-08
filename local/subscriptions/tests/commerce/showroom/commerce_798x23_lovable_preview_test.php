<?php
// Gustave Builder: authenticated non-publishable preview and ZIP isolation.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgeCompileService.php');
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgePreviewService.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeCompileService as Builder;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;

final class commerce_798x23_lovable_preview_test extends \advanced_testcase {
    private function make_archive(string $folder, string $id, bool $withimage = true): string {
        $path = $folder . '/campusfr-lovable-' . $id . '-apercu-non-publiable.zip';
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $prefix = 'draft/' . $id . '/';
        $zip->addFromString('NON_PUBLIABLE.txt', 'Brouillon uniquement');
        $zip->addFromString($prefix . 'manifest.json', json_encode([
            'id' => $id, 'entry' => 'dist/main.js', 'css' => 'dist/style.css',
            'content' => 'content/ru.json',
        ]));
        $zip->addFromString($prefix . 'content/ru.json', json_encode(['page' => [
            'hero_001' => 'Essai <CampusFR> & texte',
        ]]));
        $zip->addFromString($prefix . 'content/fr.json', '{"page":{}}');
        $zip->addFromString($prefix . 'content/en.json', '{"page":{}}');
        $zip->addFromString($prefix . 'dist/style.css', ':host{background:#123}');
        $zip->addFromString($prefix . 'dist/main.js',
            '/* campusfr-showroom-bridge/v1 data-campusfr-bridge */ '
            . 'const image=new URL("assets/example-Z.jpg",import.meta.url);');
        if ($withimage) {
            $zip->addFromString($prefix . 'dist/assets/example-Z.jpg', "\xff\xd8\xff\xd9");
        }
        $zip->close();
        return $path;
    }

    public function test_valid_zip_renders_self_contained_data_only_html(): void {
        $dir = make_temp_directory('local_subscriptions/test_preview_' . bin2hex(random_bytes(5)));
        $archive = $this->make_archive($dir, 'articles_alf_reimport');
        $html = Preview::render_frame($archive, 'articles_alf_reimport');
        self::assertStringContainsString('data-campusfr-bridge="articles_alf_reimport"', $html);
        self::assertStringContainsString('data:text/javascript;base64,', $html);
        self::assertStringContainsString('data:text/css;base64,', $html);
        // CSS reset for the isolated ShadowRoot is part of the data: stylesheet.
        self::assertStringContainsString('font-family', $this->decode_embedded_css($html));
        self::assertStringContainsString(':host{display:block;width:100%;min-height:100%;',
            $this->decode_embedded_css($html));
        self::assertStringContainsString('"api":"campusfr-showroom-bridge/v1"', $html);
        self::assertStringContainsString('\\u003CCampusFR\\u003E', $html);
        self::assertStringNotContainsString('src="https://', $html);
        self::assertStringNotContainsString('new URL("assets/', $html);
        remove_dir($dir);
    }

    private function decode_embedded_css(string $html): string {
        if (!preg_match('~data-bridge-css="data:text/css;base64,([A-Za-z0-9+/=]+)"~', $html, $matches)) {
            self::fail('Styles de l’aperçu absents.');
        }
        $css = base64_decode($matches[1], true);
        self::assertIsString($css);
        return $css;
    }

    public function test_import_build_and_preview_actions_are_compact_and_no_css_is_global(): void {
        global $CFG;
        $base = $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/';
        foreach (['lovable.php', 'lovable_build.php', 'lovable_preview.php'] as $page) {
            $source = (string)file_get_contents($base . $page);
            self::assertStringContainsString('d-flex flex-wrap align-items-center gap-2', $source);
            self::assertStringContainsString('w-auto', $source);
            self::assertStringContainsString('Étapes de l’import', $source);
        }
        $page = (string)file_get_contents($base . 'lovable_preview.php');
        self::assertStringContainsString('iframe', $page);
        self::assertStringContainsString('allow-scripts', $page);
        self::assertStringNotContainsString('allow-same-origin', $page);
    }

    public function test_missing_asset_refuses_frame(): void {
        $dir = make_temp_directory('local_subscriptions/test_preview_' . bin2hex(random_bytes(5)));
        $archive = $this->make_archive($dir, 'articles_alf_reimport', false);
        try {
            $this->expectException(\invalid_parameter_exception::class);
            Preview::render_frame($archive, 'articles_alf_reimport');
        } finally {
            remove_dir($dir);
        }
    }

    public function test_manifest_mismatch_refuses_to_preview(): void {
        $dir = make_temp_directory('local_subscriptions/test_preview_' . bin2hex(random_bytes(5)));
        $archive = $this->make_archive($dir, 'articles_alf_reimport');
        try {
            $this->expectException(\invalid_parameter_exception::class);
            Preview::render_frame($archive, 'another_design');
        } finally {
            remove_dir($dir);
        }
    }

    public function test_only_ready_matching_job_is_served(): void {
        $dir = make_temp_directory('local_subscriptions/test_preview_' . bin2hex(random_bytes(5)));
        $archive = $this->make_archive($dir, 'articles_alf_reimport');
        Builder::write_status($dir, ['state'=>'error','designId'=>'articles_alf_reimport']);
        try {
            Preview::approved_archive($dir, 'articles_alf_reimport');
            self::fail('Erreur : statut error accepté');
        } catch (\invalid_parameter_exception $expected) {
            self::assertNotEmpty($expected->getMessage());
        }
        Builder::write_status($dir, ['state'=>'ready','designId'=>'other_design']);
        try {
            Preview::approved_archive($dir, 'articles_alf_reimport');
            self::fail('Erreur : mauvais design accepté');
        } catch (\invalid_parameter_exception $expected) {
            self::assertNotEmpty($expected->getMessage());
        }
        Builder::write_status($dir, ['state'=>'ready','designId'=>'articles_alf_reimport']);
        self::assertSame($archive, Preview::approved_archive($dir, 'articles_alf_reimport'));
        remove_dir($dir);
    }

    public function test_preview_endpoint_is_strictly_sandboxed_and_private(): void {
        global $CFG;
        $endpoint = (string)file_get_contents($CFG->dirroot .
            '/local/subscriptions/admin/commerce/showrooms/lovable_preview.php');
        $build = (string)file_get_contents($CFG->dirroot .
            '/local/subscriptions/admin/commerce/showrooms/lovable_build.php');
        foreach (["require_login();", "require_capability('local/subscriptions:manage_showrooms'",
            'Builder::verify_session(', 'Content-Security-Policy:',
            'sandbox allow-scripts', "connect-src 'none'", "form-action 'none'",
            "frame-src 'none'", "worker-src 'none'", 'no-store',
            "'sandbox' => 'allow-scripts'", "'referrerpolicy' => 'no-referrer'"] as $required) {
            self::assertStringContainsString($required, $endpoint);
        }
        self::assertStringContainsString('Voir l’aperçu privé', $build);
        self::assertStringNotContainsString('allow-same-origin', $endpoint);
        self::assertStringNotContainsString('send_file(', $endpoint);
    }
}
