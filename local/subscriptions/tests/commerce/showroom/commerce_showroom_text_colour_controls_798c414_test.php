<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * 7.98C.4.14 — per-block text colour control and automatic dark contrast.
 *
 * @coversNothing
 */
final class commerce_showroom_text_colour_controls_798c414_test extends \advanced_testcase {
    public function test_builder_presenter_runtime_and_css_expose_text_colour_contract(): void {
        global $CFG;

        $registry = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $runtime = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomRuntimeBlockSet.php');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $editor = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/edit.php');

        self::assertIsString($registry);
        self::assertIsString($presenter);
        self::assertIsString($runtime);
        self::assertIsString($css);
        self::assertIsString($editor);

        self::assertStringContainsString("self::select('sectiontextcolor', 'Couleur du texte'", $registry);
        self::assertStringContainsString("['auto', 'brand', 'white']", $presenter);
        self::assertStringContainsString("['dark', 'dark2', 'gradient']", $presenter);
        self::assertStringContainsString("commerce-showroom-text--' . \$resolvedtextcolor", $presenter);
        self::assertStringContainsString("'sectiontextcolor'", $runtime);
        self::assertStringContainsString("'sectiontextcolor' => [", $editor);
        self::assertStringContainsString('7.98C.4.14 — configurable section text colour', $css);
        self::assertStringContainsString('.commerce-showroom-text--white .commerce-showroom-offers__heading h2', $css);
        self::assertStringContainsString('.commerce-showroom-text--brand .commerce-showroom-offers__heading h2', $css);
    }
}
