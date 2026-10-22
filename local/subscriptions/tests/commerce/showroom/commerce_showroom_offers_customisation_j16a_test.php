<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

/** Contracts for J16A Showroom offer customisation. */
final class commerce_showroom_offers_customisation_j16a_test extends \advanced_testcase {
    public function test_offer_editor_generates_role_specific_multilingual_content(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema(
            'offers',
            ['pdf', 'bundle', 'course']
        );
        $names = array_column($schema['fields'], 'name');

        foreach ([
            'pdfrolelabel', 'pdftitle', 'pdfdescription', 'pdffeatures',
            'courserolelabel', 'coursetitle', 'coursedescription', 'coursefeatures',
            'bundlefeaturedlabel', 'bundlerolelabel', 'bundletitle', 'bundlesubtitle', 'bundledescription', 'bundlefeatures',
        ] as $field) {
            self::assertContains($field, $names);
        }
    }

    public function test_runtime_applies_builder_content_with_generic_role_fallbacks(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        self::assertIsString($source);
        self::assertStringContainsString("\$role . 'rolelabel'", $source);
        self::assertStringContainsString("\$role . 'title'", $source);
        self::assertStringContainsString("\$role . 'subtitle'", $source);
        self::assertStringContainsString("\$role . 'description'", $source);
        self::assertStringContainsString("\$role . 'features'", $source);
        self::assertStringContainsString("\$role . 'featuredlabel'", $source);
        self::assertStringContainsString('line_items', $source);
    }

    public function test_media_limits_and_builder_guidance_are_updated(): void {
        $manager = file_get_contents(__DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockMediaManager.php');
        $registry = file_get_contents(__DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        self::assertIsString($manager);
        self::assertIsString($registry);
        self::assertStringContainsString('MAX_IMAGE_BYTES = 20 * 1024 * 1024', $manager);
        self::assertStringContainsString('MAX_VIDEO_BYTES = 500 * 1024 * 1024', $manager);
        self::assertStringContainsString("'help' => get_string", $registry);
    }
}
