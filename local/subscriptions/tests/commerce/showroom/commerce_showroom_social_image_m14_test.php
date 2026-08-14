<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomDefinition;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomSocialImageService;

final class commerce_showroom_social_image_m14_test extends \advanced_testcase {
    public function test_definition_exposes_custom_social_image(): void {
        $definition = new CommerceShowroomDefinition(
            'demo',
            ['fr' => 'demo'],
            'local_subscriptions/showroom/third_group_verbs',
            [],
            'legacy_title',
            'legacy_description',
            [],
            [],
            'https://www.campusfr.fr/social/demo.jpg'
        );

        $this->assertSame(
            'https://www.campusfr.fr/social/demo.jpg',
            $definition->get_social_image()
        );
    }

    public function test_social_image_filearea_can_be_served_publicly(): void {
        global $CFG;

        $lib = file_get_contents($CFG->dirroot . '/local/subscriptions/lib.php');
        $seo = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomSeoService.php'
        );
        $editor = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/edit.php'
        );

        $this->assertStringContainsString(
            'CommerceShowroomSocialImageService::FILEAREA',
            $lib
        );
        $this->assertStringContainsString(
            '$definition->get_social_image()',
            $seo
        );
        $this->assertStringContainsString("'name' => 'socialimage'", $editor);
        $this->assertStringContainsString("'enctype' => 'multipart/form-data'", $editor);
    }

    public function test_social_image_service_uses_dedicated_filearea(): void {
        $this->assertSame(
            'showroom_social_image',
            CommerceShowroomSocialImageService::FILEAREA
        );
        $this->assertSame(20 * 1024 * 1024, CommerceShowroomSocialImageService::MAX_IMAGE_BYTES);
    }
}
