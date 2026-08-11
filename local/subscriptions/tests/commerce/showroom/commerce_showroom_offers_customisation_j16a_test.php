<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

/** Contracts for J16A Showroom offer customisation. */
final class commerce_showroom_offers_customisation_j16a_test extends \advanced_testcase {
    public function test_offer_editor_exposes_role_specific_multilingual_content(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        self::assertIsString($source);
        foreach ([
            'pdfrolelabel', 'pdftitle', 'pdfdescription', 'pdffeatures',
            'courserolelabel', 'coursetitle', 'coursedescription', 'coursefeatures',
            'bundlefeaturedlabel', 'bundlerolelabel', 'bundletitle', 'bundlesubtitle', 'bundledescription', 'bundlefeatures',
        ] as $field) {
            self::assertStringContainsString("'{$field}'", $source);
        }
    }

    public function test_runtime_applies_builder_content_with_product_fallbacks(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        self::assertIsString($source);
        self::assertStringContainsString("\$role . 'rolelabel'", $source);
        self::assertStringContainsString("\$role . 'title'", $source);
        self::assertStringContainsString("\$role . 'description'", $source);
        self::assertStringContainsString("\$role . 'features'", $source);
        self::assertStringContainsString("'bundlesubtitle'", $source);
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
