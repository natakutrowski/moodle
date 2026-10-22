<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_articles_dark_theme_hero_798c319_test extends \advanced_testcase {
    public function test_articles_dark_and_hero_contract_tracks_current_showroom_engine(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $css = file_get_contents($root . 'styles/showroom.css');
        $template = file_get_contents($root . 'templates/showroom/default.mustache');
        $presenter = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );

        self::assertIsString($css);
        self::assertIsString($template);
        self::assertIsString($presenter);

        self::assertStringContainsString('7.98C.4.12 — preserve the complete Hero artwork', $css);
        self::assertStringContainsString('7.98C.4.13 — restore Sombre 2', $css);
        self::assertStringContainsString('background: #1A1630;', $css);
        self::assertStringContainsString('7.98C.4.14 — configurable section text colour + dark auto contrast.', $css);
        self::assertStringContainsString('.commerce-showroom-text--white', $css);
        self::assertStringContainsString('color: var(--showroom-pink) !important;', $css);

        self::assertStringContainsString('{{#showherosecondarycta}}', $template);
        self::assertStringContainsString('data-showroom-counter', $template);

        self::assertStringContainsString("'Voir comment ça fonctionne' => 'Découvrir l’entraîneur en vidéo'", $presenter);
        self::assertStringContainsString("'See how it works' => 'Watch the trainer video'", $presenter);
        self::assertStringContainsString("'showvideobutton', \$config", $presenter);
        self::assertStringContainsString("['auto', 'brand', 'white']", $presenter);
    }
}
