<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_media_manager_j15e4_test extends \advanced_testcase {
    public function test_current_visual_slots_are_declared_as_media_fields(): void {
        foreach ([['hero', 'backgroundurl'], ['video', 'posterurl'], ['video', 'videourl']] as [$type, $field]) {
            self::assertTrue(CommerceShowroomBlockEditorRegistry::is_media_field($type, $field), $type . ':' . $field);
        }
        self::assertFalse(CommerceShowroomBlockEditorRegistry::is_media_field('support', 'coverurl'));
    }

    public function test_media_field_configuration_keeps_expected_limits(): void {
        $video = CommerceShowroomBlockEditorRegistry::schema('video');
        $fields = array_column($video['fields'], null, 'name');
        self::assertSame('media', $fields['posterurl']['type']);
        self::assertSame('image', $fields['posterurl']['kind']);
        self::assertSame('video', $fields['videourl']['kind']);
    }
}
