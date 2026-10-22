<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_articles_final_cta_slide10_798c318_test extends \advanced_testcase {
    public function test_article_heart_variant_is_registered_and_rendered(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/default.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');
        $registry = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');

        self::assertStringContainsString("'article_heart' => 'Articles / Alf — dernier appel'", $registry);
        self::assertStringContainsString("self::text('titleaccent', 'Titre — partie accentuée')", $registry);
        self::assertStringContainsString("self::textarea('perkitems'", $registry);

        self::assertStringContainsString('{{finalpresentationclass}}', $template);
        self::assertStringContainsString('commerce-showroom-final__handnote', $template);
        self::assertStringContainsString('commerce-showroom-final__secondary-link', $template);
        self::assertStringContainsString('commerce-showroom-final__perks', $template);

        self::assertStringContainsString('$data[\'isfinalctaarticle\'] = $presentation === \'article_heart\';', $presenter);
        self::assertStringContainsString('private function final_perks(string $raw): array', $presenter);

        self::assertStringContainsString('.commerce-showroom-final--article_heart', $css);
        self::assertStringContainsString('.commerce-showroom-final__perks', $css);
        self::assertStringContainsString('.commerce-showroom-final__handnote', $css);
    }
}
