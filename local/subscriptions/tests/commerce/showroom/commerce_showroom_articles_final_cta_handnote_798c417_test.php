<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * 7.98C.4.17 — handwritten note is quiet by default on the Articles / Alf Final CTA.
 *
 * @coversNothing
 */
final class commerce_showroom_articles_final_cta_handnote_798c417_test extends \advanced_testcase {
    public function test_article_heart_hides_handnote_by_default_but_builder_can_restore_it(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $template = file_get_contents($root . 'templates/showroom/default.mustache');

        self::assertIsString($registry);
        self::assertIsString($presenter);
        self::assertIsString($template);

        self::assertStringContainsString("self::select('handnotedisplay', 'Affichage note manuscrite'", $registry);
        self::assertStringContainsString("\$handnotedisplay = \$this->option(\$config, 'handnotedisplay', ['auto', 'show', 'hide'], 'auto');", $presenter);
        self::assertStringContainsString("\$handnotedisplay === 'auto' && !\$data['isfinalctaarticle']", $presenter);
        self::assertStringContainsString("\$data['hasfinalhandnote'] = \$data['showfinalhandnote'] && \$data['finalhandnote'] !== '';", $presenter);
        self::assertStringContainsString('{{#hasfinalhandnote}}', $template);
    }
}
