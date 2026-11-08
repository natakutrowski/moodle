<?php

declare(strict_types=1);
namespace local_subscriptions;
use advanced_testcase;

/** Visual parity retains the approved 1–2 decorations after React poster upgrade. */
final class commerce_798x419_visual_parity_12_test extends advanced_testcase {
    public function test_compiled_js_checkout_untouched_and_version_cache_busted(): void {
        $root = dirname(__DIR__, 3);
        $base = $root . '/designs/articles_alf_reimport/';
        $manifest = json_decode((string)file_get_contents($base . 'manifest.json'), true);
        self::assertSame('0.4.13', $manifest['version']);
        self::assertSame('33df28eaa16108dddb61c452cc067afa362555d87a925b198cee6aaebef1253c', hash_file('sha256', $base . 'dist/main.js'));
    }
    public function test_hero_and_evidence_decorations_compiled(): void {
        $root = dirname(__DIR__, 3);
        $css = (string)file_get_contents($root . '/designs/articles_alf_reimport/dist/style.css');
        foreach (['CampusFR visual-parity pass 01',
            'main > section:first-of-type a[href="#video"]',
            'main > div[aria-label].fixed.rounded-full',
            'main > section:first-of-type .animate-firefly',
            '.night-scope .grid.max-w-6xl > .relative.flex.flex-col.justify-center.rounded-2xl',
            'campusfr-evidence-pulse', '.night-scope .campusfr-v3-ribbon',
            '.night-scope .campusfr-v3-ribbon > span:first-child'
        ] as $marker) {
            self::assertStringContainsString($marker, $css);
        }
    }
}
