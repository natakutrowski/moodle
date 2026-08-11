<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_visual_optimisation_j15h1e_test extends \advanced_testcase {
    public function test_showroom_uses_native_ratio_and_responsive_derivatives(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');
        $manager = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/catalog/assets/CommerceCatalogMediaManager.php');

        self::assertStringContainsString('aspect-ratio: 16 / 9', $css);
        self::assertStringContainsString('srcset="{{coversrcset}}"', $template);
        self::assertStringContainsString('SHOWROOM_DERIVATIVE_WIDTHS = [640, 960]', $manager);
        self::assertStringContainsString('imagecopyresampled', $manager);
    }
}
