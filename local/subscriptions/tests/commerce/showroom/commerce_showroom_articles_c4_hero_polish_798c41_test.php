<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_articles_c4_hero_polish_798c41_test extends \advanced_testcase {
    public function test_current_generic_hero_contract_is_preserved(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/default.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');
        $presenter = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );

        self::assertIsString($template);
        self::assertIsString($css);
        self::assertIsString($presenter);

        // Stats now use their authoritative value/label presentation rather than
        // the superseded C4.1 per-stat icon experiment.
        self::assertStringContainsString(
            '<div role="listitem"><strong {{#statsanimate}}data-showroom-counter',
            $template
        );
        self::assertStringNotContainsString('herostat1icon', $template);

        // The secondary video CTA is independently configurable.
        self::assertStringContainsString('{{#showherosecondarycta}}', $template);
        self::assertStringContainsString("'showvideobutton', \$config", $presenter);

        // C4.12 is now the final background geometry: no top/bottom crop.
        self::assertStringContainsString(
            '7.98C.4.12 — preserve the complete Hero artwork',
            $css
        );
        self::assertStringContainsString('background-size: 100% 100%;', $css);
        self::assertStringContainsString(
            '.commerce-showroom--generic .commerce-showroom-hero--cover .commerce-showroom-hero__title-accent',
            $css
        );
        self::assertStringContainsString('color: var(--showroom-pink);', $css);
    }
}
