<?php

declare(strict_types=1);
namespace local_subscriptions;
use advanced_testcase;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeSettings as Settings;

/** Screenshots 3–5: isolated decorative styles + configurable video poster. */
final class commerce_798x420_visual_parity_345_test extends advanced_testcase {
    public function test_showroom_visual_and_video_are_separate_from_checkout(): void {
        $root = dirname(__DIR__, 3);
        $base = $root . '/designs/articles_alf_reimport/';
        $manifest = json_decode((string)file_get_contents($base . 'manifest.json'), true);
        self::assertSame('0.4.13', $manifest['version']);
        $css = (string)file_get_contents($base . 'dist/style.css');
        $js = (string)file_get_contents($base . 'dist/main.js');
        foreach (['campusfr-first-clue-panel', 'campusfr-first-clue-ribbon',
            'campusfr-first-clue-article', 'campusfr-first-clue-meaning',
            'campusfr-first-clue-compass', 'campusfr-piece-card', 'campusfr-piece-label',
            'campusfr-step-circle', 'campusfr-video-topic',
            'campusfr-presentation-start', 'campusfr-presentation-pulse'] as $cssclass) {
            self::assertStringContainsString($cssclass, $css);
            self::assertStringContainsString($cssclass, $js);
        }
        self::assertStringContainsString('CampusFR visual-parity pass 01', $css);
        self::assertFileExists($base . 'dist/assets/video-cover-alf.webp');
        self::assertStringContainsString('presentationPosterUrl', $js);
        self::assertStringContainsString('onClick:()=>{const v=player.current', $js);
        self::assertStringContainsString('onPlay:()=>setStarted(true)', $js);
        self::assertStringContainsString('priceid', $js);
        self::assertStringContainsString('sesskey', $js);
    }
    public function test_cms_poster_url_is_optional_and_restricted(): void {
        $base = json_encode(['designbridge'=>['id'=>'articles_alf_reimport','content'=>[],'videoUrl'=>'']], JSON_THROW_ON_ERROR);
        self::assertSame('', Settings::from_json($base)['posterUrl']);
        self::assertSame('https://cdn.example.org/cover.webp', Settings::validate_poster_url('https://cdn.example.org/cover.webp'));
        foreach (['javascript:alert(1)', 'data:image/png;base64,xxxx', 'http://example.org/x.png',
            'https://user:pass@example.org/a.png', "/a\n.png"] as $unsafe) {
            try { Settings::validate_poster_url($unsafe); self::fail('Unsafe poster URL accepted.'); }
            catch (\invalid_parameter_exception $expected) { self::assertTrue(true); }
        }
        $updated = Settings::merge_into_settings_json($base, 'articles_alf_reimport',
            ['ru'=>'{}','fr'=>'{}','en'=>'{}'],null,'https://cdn.example.org/cover.webp');
        self::assertSame('https://cdn.example.org/cover.webp', Settings::from_json($updated)['posterUrl']);
        self::assertSame('', Settings::from_json($base)['posterUrl']);
    }
    public function test_backend_editor_exposes_configurable_poster(): void {
        $root = dirname(__DIR__, 3);
        $editor = (string)file_get_contents($root . '/admin/commerce/showrooms/edit.php');
        $presenter = (string)file_get_contents($root . '/classes/commerce/showroom/bridge/CommerceShowroomBridgePresenter.php');
        self::assertStringContainsString("'name' => 'bridgeposter_url'", $editor);
        self::assertStringContainsString("'presentationPosterUrl' =>", $presenter);
        self::assertStringContainsString("$" . "settings['posterUrl']", $presenter);
        self::assertStringContainsString("'presentationVideoUrl'", $presenter);
    }
}
