<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_bundle_highlight_j15c_test extends \advanced_testcase {
    public function test_bundle_is_the_default_featured_role(): void {
        $presenter = file_get_contents(
            __DIR__ . '/../../../classes/commerce/showroom/cms/'
                . 'CommerceShowroomBlockConfigurationPresenter.php'
        );
        $defaults = file_get_contents(
            __DIR__ . '/../../../classes/commerce/showroom/cms/'
                . 'CommerceShowroomBlockDefaultsCatalog.php'
        );

        $this->assertIsString($presenter);
        $this->assertStringContainsString(
            "'featuredrole', 'bundle'",
            $presenter
        );
        $this->assertIsString($defaults);
        $this->assertStringContainsString(
            "'featuredrole' => 'bundle'",
            $defaults
        );
    }

    public function test_bundle_visual_hierarchy_and_label_are_restored(): void {
        $css = file_get_contents(__DIR__ . '/../../../styles/showroom.css');
        $fr = file_get_contents(
            __DIR__ . '/../../../lang/fr/local_subscriptions.php'
        );

        $this->assertIsString($css);
        $this->assertStringContainsString(
            '.commerce-showroom-offer--bundle.is-featured',
            $css
        );
        $this->assertIsString($fr);
        $this->assertStringContainsString('Offre complète', $fr);
    }
}
