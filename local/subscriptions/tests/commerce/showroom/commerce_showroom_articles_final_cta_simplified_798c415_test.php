<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * 7.98C.4.15 — simplified Articles / Alf Final CTA and Support seam cleanup.
 *
 * @coversNothing
 */
final class commerce_showroom_articles_final_cta_simplified_798c415_test extends \advanced_testcase {
    public function test_article_heart_uses_quiet_auto_defaults_with_builder_overrides(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $template = file_get_contents($root . 'templates/showroom/default.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertIsString($registry);
        self::assertIsString($presenter);
        self::assertIsString($template);
        self::assertIsString($css);

        self::assertStringContainsString("self::select('titlesize', 'Taille du titre'", $registry);
        self::assertStringContainsString("self::select('badgedisplay', 'Badge'", $registry);
        self::assertStringContainsString("self::select('textdisplay', 'Sous-titre'", $registry);
        self::assertStringContainsString("self::select('perksdisplay', 'Bandeau bas'", $registry);

        self::assertStringContainsString("\$titlesize = \$data['isfinalctaarticle'] ? 'compact' : 'normal';", $presenter);
        self::assertStringContainsString("\$badgedisplay === 'auto' && !\$data['isfinalctaarticle']", $presenter);
        self::assertStringContainsString("\$textdisplay === 'auto' && !\$data['isfinalctaarticle']", $presenter);
        self::assertStringContainsString("\$perksdisplay === 'auto' && !\$data['isfinalctaarticle']", $presenter);

        self::assertStringContainsString('{{#showfinalbadge}}', $template);
        self::assertStringContainsString('{{#showfinaltext}}', $template);
        self::assertStringContainsString('class="{{finaltitlesizeclass}}"', $template);
        self::assertStringContainsString('{{#hasfinalperks}}', $template);

        self::assertStringContainsString(
            '.commerce-showroom > .commerce-showroom-support + .commerce-showroom-final',
            $css
        );
        self::assertStringContainsString('margin-top: -1px;', $css);
        self::assertStringContainsString(
            '.commerce-showroom-final h2.commerce-showroom-final__title--compact',
            $css
        );
    }
}
