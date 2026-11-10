<?php

declare(strict_types=1);
namespace local_subscriptions;

use advanced_testcase;

/**
 * Articles / Alf — CSS-only parity for eight desktop capture comparisons.
 *
 * @coversNothing
 */
final class commerce_798x421_articles_visual_borders_captures_1to8_test extends advanced_testcase {
    public function test_eight_capture_borders_and_glows_are_scoped_to_the_shadow_design(): void {
        $base = dirname(__DIR__, 3) . '/designs/articles_alf_reimport/';
        $css = (string)file_get_contents($base . 'dist/style.css');
        $js = (string)file_get_contents($base . 'dist/main.js');
        $manifest = json_decode((string)file_get_contents($base . 'manifest.json'), true);

        self::assertSame('0.4.13', $manifest['version']);
        // Compilation/checkout remains exactly as supplied in the DEV source archive.
        self::assertStringContainsString('currencySelector:s.jsxDEV(S5', (string)file_get_contents(dirname(__DIR__, 3) . '/designs/articles_alf_reimport/dist/main.js'));
        self::assertStringContainsString('CampusFR visual parity pass 03', $css);
        self::assertStringContainsString('CampusFR visual parity pass 04', $css);
        foreach ([
            '.night-scope .campusfr-first-clue-article:not(.bg-primary)',
            '.night-scope .grid.items-stretch > .flex.flex-col.rounded-3xl > .border-b',
            '.night-scope .grid.items-stretch > .space-y-6 > .relative.rounded-3xl > .border-b:not(:last-child)',
            '.night-scope .grid.max-w-6xl > .group.lantern:hover',
            '.night-scope .grid.max-w-6xl > .group.lantern > .relative.rounded-2xl',
            '.night-scope .grid.max-w-6xl > .group.lantern > .relative.rounded-2xl > .absolute.-bottom-2.left-1\/2.size-4.-translate-x-1\/2.rotate-45',
            '#offers.night-scope .mt-7.grid.max-w-6xl > article.border-2.border-primary',
            '#offers.night-scope .campusfr-v3-currency-select',
            '.night-scope .mx-auto.mt-14.max-w-4xl > .relative.rounded-\[2rem\].border.bg-card',
            '.night-scope .is-open > button',
            '#contact > .relative.mx-auto.max-w-4xl',
            '#contact .flex.flex-wrap.justify-center.gap-2 > a:hover',
            'section.night-scope.bg-stars.relative.flex.flex-col.overflow-hidden > div:first-child',
        ] as $marker) {
            self::assertStringContainsString($marker, $css);
        }
        // Names targeted by the new selectors still exist in the compiled component.
        foreach ([
            'campusfr-first-clue-article',
            'campusfr-v3-currency-select',
            'group relative flex flex-col items-center rounded-[2rem]',
            'absolute -bottom-2 left-1/2 size-4 -translate-x-1/2 rotate-45 border-b border-r border-glow/30 bg-night-deep',
            'mx-auto mt-14 max-w-4xl',
            'is-open',
            'id:"contact"',
            'flex flex-wrap justify-center gap-2',
            'left-[6%] top-[3%] size-12 rounded-full bg-night-foreground/90',
        ] as $marker) {
            self::assertStringContainsString($marker, $js);
        }
    }
}
