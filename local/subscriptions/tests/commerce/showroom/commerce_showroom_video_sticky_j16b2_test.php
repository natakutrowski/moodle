<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_video_sticky_j16b2_test extends \advanced_testcase {
    public function test_video_editor_remains_multilingual_and_media_driven(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('video');
        $fields = array_column($schema['fields'], null, 'name');
        self::assertTrue($fields['title']['translatable']);
        self::assertTrue($fields['text']['translatable']);
        self::assertSame('media', $fields['posterurl']['type']);
        self::assertSame('media', $fields['videourl']['type']);
    }

    public function test_sticky_visibility_is_driven_by_comparison_position(): void {
        global $CFG;
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        self::assertStringContainsString('comparisonPassed', $js);
        self::assertStringContainsString('sticky.hidden = !comparisonPassed;', $js);
    }
}
