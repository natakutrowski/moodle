<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;

final class commerce_showroom_ascent_j16m1_test extends \advanced_testcase {
    public function test_ascent_has_current_editor_and_runtime_contract(): void {
        global $CFG;
        $schema = CommerceShowroomBlockEditorRegistry::schema('ascent');
        $names = array_column($schema['fields'], 'name');
        self::assertContains('cards', $names);
        self::assertContains('summaryitems', $names);
        self::assertContains('gradientstart', $names);
        self::assertContains('gradientend', $names);

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('{{#isascent}}', $template);
        self::assertStringContainsString('commerce-showroom-ascent--premium', $template);
        self::assertStringContainsString('--showroom-ascent-gradient-start', $css);
        self::assertStringContainsString('--showroom-ascent-gradient-end', $css);
    }
}
