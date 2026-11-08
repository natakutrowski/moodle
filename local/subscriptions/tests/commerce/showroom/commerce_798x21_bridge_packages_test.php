<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePackageRegistry;

/** X2.1: safe discovery of trusted, compiled, versioned Lovable packages. */
final class commerce_798x21_bridge_packages_test extends \advanced_testcase {
    private function fixture(string $dir, string $id, string $version = '1.2.3'): string {
        $base = $dir . '/' . $id;
        mkdir($base . '/content', 0755, true);
        mkdir($base . '/dist', 0755, true);
        file_put_contents($base . '/manifest.json', json_encode([
            'id' => $id, 'label' => 'Démo · Lovable', 'version' => $version,
            'entry' => 'dist/main.js', 'css' => 'dist/style.css', 'content' => 'content/ru.json',
        ], JSON_UNESCAPED_UNICODE));
        foreach (['ru', 'fr', 'en'] as $lang) {
            file_put_contents($base . '/content/' . $lang . '.json', '{"hero":{"title":"ok"}}');
        }
        file_put_contents($base . '/dist/main.js', 'console.log("demo")');
        file_put_contents($base . '/dist/style.css', 'body{margin:0}');
        return $base;
    }

    public function test_existing_articles_design_is_still_available(): void {
        $options = CommerceShowroomBridgePackageRegistry::options();
        self::assertArrayHasKey('articles_alf', $options);
        self::assertSame('articles_alf', CommerceShowroomBridgePackageRegistry::require_manifest('articles_alf')['id']);
    }

    public function test_new_approved_design_is_discovered_from_manifest(): void {
        $root = make_temp_directory('bridge_discovery_' . uniqid());
        $this->fixture($root, 'demo_paris');
        $options = CommerceShowroomBridgePackageRegistry::discover_from_directory($root);
        self::assertSame(['demo_paris' => 'Démo · Lovable'], $options);
    }

    public function test_uncompiled_package_is_hidden(): void {
        $root = make_temp_directory('bridge_incomplete_' . uniqid());
        $base = $this->fixture($root, 'incomplete');
        unlink($base . '/dist/main.js');
        self::assertSame([], CommerceShowroomBridgePackageRegistry::discover_from_directory($root));
    }

    public function test_missing_language_catalogue_is_hidden(): void {
        $root = make_temp_directory('bridge_languages_' . uniqid());
        $base = $this->fixture($root, 'missingfr');
        unlink($base . '/content/fr.json');
        self::assertSame([], CommerceShowroomBridgePackageRegistry::discover_from_directory($root));
    }

    public function test_directory_symlink_is_never_discovered(): void {
        $root = make_temp_directory('bridge_symlinks_' . uniqid());
        $other = make_temp_directory('bridge_other_' . uniqid());
        $this->fixture($other, 'outside');
        if (!function_exists('symlink') || !@symlink($other . '/outside', $root . '/outside')) {
            $this->markTestSkipped('Symlinks unsupported');
        }
        self::assertSame([], CommerceShowroomBridgePackageRegistry::discover_from_directory($root));
    }

    public function test_manifest_path_and_label_injection_are_rejected(): void {
        $root = make_temp_directory('bridge_invalid_' . uniqid());
        $base = $this->fixture($root, 'hijacked');
        $manifest = json_decode((string)file_get_contents($base . '/manifest.json'), true);
        $manifest['css'] = '../secret.css';
        file_put_contents($base . '/manifest.json', json_encode($manifest));
        self::assertSame([], CommerceShowroomBridgePackageRegistry::discover_from_directory($root));
        $manifest['css'] = 'dist/style.css';
        $manifest['label'] = '<img src=x onerror=alert(1)>';
        file_put_contents($base . '/manifest.json', json_encode($manifest));
        self::assertSame([], CommerceShowroomBridgePackageRegistry::discover_from_directory($root));
    }

    public function test_unknown_id_is_rejected_even_if_valid_format(): void {
        $this->expectException(\invalid_parameter_exception::class);
        CommerceShowroomBridgePackageRegistry::require_id('nonexistent_demo');
    }

    public function test_assets_are_only_those_declared_by_manifest(): void {
        $manifest = CommerceShowroomBridgePackageRegistry::require_manifest('articles_alf');
        self::assertSame('dist/main.js', $manifest['entry']);
        self::assertSame('dist/style.css', $manifest['css']);
        // The public page must accept both declared assets, not just reject others.
        // Regression X2.1: a malformed regex used to reject ALL paths.
        $entryurl = CommerceShowroomBridgePackageRegistry::asset_url(
            'articles_alf', $manifest['entry'], $manifest['version']
        );
        self::assertStringContainsString(
            '/local/subscriptions/designs/articles_alf/dist/main.js', $entryurl
        );
        self::assertStringContainsString('v=' . $manifest['version'], $entryurl);

        $cssurl = CommerceShowroomBridgePackageRegistry::asset_url(
            'articles_alf', $manifest['css'], $manifest['version']
        );
        self::assertStringContainsString(
            '/local/subscriptions/designs/articles_alf/dist/style.css', $cssurl
        );

        $this->expectException(\invalid_parameter_exception::class);
        CommerceShowroomBridgePackageRegistry::asset_url('articles_alf', 'dist/unlisted.js');
    }

    public function test_asset_path_traversal_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        CommerceShowroomBridgePackageRegistry::asset_url('articles_alf', 'dist/../config.php');
    }
}
