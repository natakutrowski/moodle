<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_bundle_highlight_j15c_test extends \advanced_testcase {
    public function test_bundle_is_the_default_featured_role_for_legacy_schema(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema(
            'offers',
            ['pdf', 'bundle', 'course']
        );
        $fields = [];
        foreach ($schema['fields'] as $field) {
            $fields[(string)$field['name']] = $field;
        }
        $defaults = file_get_contents(
            __DIR__ . '/../../../classes/commerce/showroom/cms/'
                . 'CommerceShowroomBlockDefaultsCatalog.php'
        );

        $this->assertSame('bundle', $fields['featuredrole']['default']);
        $this->assertIsString($defaults);
        $this->assertStringContainsString("'featuredrole' => 'bundle'", $defaults);
    }

    public function test_bundle_visual_hierarchy_and_label_are_restored(): void {
        $css = file_get_contents(__DIR__ . '/../../../styles/showroom.css');
        $fr = file_get_contents(
            __DIR__ . '/../../../lang/fr/local_subscriptions.php'
        );

        $this->assertIsString($css);
        $this->assertStringContainsString(
            '.commerce-showroom-offer.is-featured',
            $css
        );
        $this->assertIsString($fr);
        $this->assertStringContainsString('Offre complète', $fr);
    }
}
