<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

/** A1 contextual navigation, opt-in only. */
final class commerce_showroom_a1_sticky_anchor_798e15_test extends \advanced_testcase {
    /** @return array<string, mixed> */
    private function anchor_data(string $showroom, string $presentation, bool $sticky): array {
        $presenter = new CommerceShowroomBlockConfigurationPresenter();
        $method = new \ReflectionMethod($presenter, 'apply_anchor_nav');
        return $method->invoke($presenter, ['showroomkey' => $showroom], [
            'presentation' => $presentation,
            'sticky' => $sticky,
            'items' => "О курсе|#showroom-audience-profiles\nProgramme|#showroom-program\nFAQ|#showroom-faq",
            'ctalabel' => 'Начать путешествие',
            'ctatarget' => '#showroom-offers',
        ]);
    }

    public function test_after_hero_nav_is_a1_only_and_requires_explicit_opt_in(): void {
        $a1 = $this->anchor_data('a1-course', 'a1_after_hero', true);
        self::assertTrue($a1['anchornavsticky']);
        self::assertTrue($a1['anchornavafterhero']);
        self::assertStringContainsString('is-sticky', $a1['anchornavclass']);
        self::assertStringContainsString('commerce-showroom-anchor-nav--a1-after-hero', $a1['anchornavclass']);
        self::assertCount(3, $a1['anchornavitems']);
        self::assertTrue($a1['hasanchornavcta']);

        foreach ([
            ['a1-course', 'standard', true],
            ['verbs', 'a1_after_hero', true],
            ['a1-course', 'a1_after_hero', false],
        ] as [$showroom, $presentation, $sticky]) {
            $other = $this->anchor_data($showroom, $presentation, $sticky);
            self::assertFalse($other['anchornavafterhero']);
            self::assertStringNotContainsString('commerce-showroom-anchor-nav--a1-after-hero', $other['anchornavclass']);
        }
    }

    public function test_after_hero_nav_does_not_reserve_space_and_is_compact(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        $section = strstr($css, '/* E8.15 — A1 navigation interne. E8.15.2');
        self::assertIsString($section);
        self::assertStringContainsString('position: fixed;', $section);
        self::assertStringContainsString(':is(.commerce-showroom[data-showroom="a1-course"], body)', $section);
        self::assertStringContainsString('height: 48px;', $section);
        self::assertStringContainsString('height: 34px;', $section);
        self::assertStringContainsString('height: 44px;', $section);
        self::assertStringContainsString('visibility: hidden;', $section);
        self::assertStringContainsString('is-after-hero-visible', $section);
        // Legacy Showroom behavior remains styled separately.
        self::assertStringContainsString('.commerce-showroom-anchor-nav.is-sticky { position: sticky;', $css);
    }

    public function test_builder_template_and_amd_are_connected_without_changing_old_nav(): void {
        global $CFG;
        $schemas = CommerceShowroomBlockEditorRegistry::schemas(['course_fr']);
        $fields = $schemas['anchor_nav']['fields'];
        self::assertNotEmpty($fields);
        self::assertStringContainsString('a1_after_hero', json_encode($fields, JSON_THROW_ON_ERROR));

        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/block_anchor_nav.mustache');
        self::assertIsString($template);
        self::assertStringContainsString('data-showroom-after-hero-nav', $template);
        self::assertStringNotContainsString('logo', $template);

        $page = file_get_contents($root . 'showroom.php');
        self::assertIsString($page);
        self::assertStringContainsString("'local_subscriptions/anchor_nav_sticky'", $page);

        $source = file_get_contents($root . 'amd/src/anchor_nav_sticky.js');
        $built = file_get_contents($root . 'amd/build/anchor_nav_sticky.min.js');
        self::assertIsString($source);
        self::assertIsString($built);
        self::assertStringContainsString('document.body.appendChild(nav)', $source);
        // Actual storefront topbar (not nav.navbar) must be measured.
        self::assertStringContainsString("'.showroom-shell__topbar'", $source);
        self::assertStringContainsString('realTopbar.getBoundingClientRect()', $source);
        self::assertStringContainsString('bounds.bottom', $source);
        self::assertStringContainsString('getBoundingClientRect()', $source);
        self::assertStringContainsString('hero.getBoundingClientRect().bottom', $source);
        self::assertStringContainsString("'is-after-hero-visible'", $source);
        self::assertStringContainsString('--showroom-a1-anchor-clearance', $source);
        self::assertStringContainsString('local_subscriptions/anchor_nav_sticky', $built);

        $css = file_get_contents($root . 'styles/showroom.css');
        self::assertIsString($css);
        self::assertStringContainsString('E8.15 — A1 navigation interne', $css);
        self::assertStringContainsString('scroll-margin-top: var(--showroom-a1-anchor-clearance', $css);
    }
}
