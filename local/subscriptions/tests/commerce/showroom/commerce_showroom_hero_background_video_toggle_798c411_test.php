<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * 7.98C.4.11 — Hero background anchoring and optional video CTA.
 *
 * @coversNothing
 */
final class commerce_showroom_hero_background_video_toggle_798c411_test extends \advanced_testcase {
    public function test_hero_registry_defaults_presenter_template_and_css_cover_the_new_behaviour(): void {
        global $CFG;

        $registry = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $defaults = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockDefaultsCatalog.php');
        $presenter = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        $this->assertIsString($registry);
        $this->assertIsString($defaults);
        $this->assertIsString($presenter);
        $this->assertIsString($template);
        $this->assertIsString($css);

        $this->assertStringContainsString("self::checkbox('showvideobutton', 'Afficher le bouton vers la vidéo')", $registry);
        $this->assertStringContainsString("'showvideobutton' => true", $defaults);
        $this->assertStringContainsString("showherosecondarycta", $presenter);
        $this->assertStringContainsString("showvideobutton', \$config", $presenter);
        $this->assertStringContainsString('{{#showherosecondarycta}}', $template);
        $this->assertStringContainsString('7.98C.4.11 — Hero CMS background stays anchored to the bottom', $css);
        $this->assertStringContainsString('7.98C.4.12 — preserve the complete Hero artwork by stretching it to the Hero box.', $css);
        $this->assertStringContainsString('background-size: 100% 100%;', $css);
    }
}
