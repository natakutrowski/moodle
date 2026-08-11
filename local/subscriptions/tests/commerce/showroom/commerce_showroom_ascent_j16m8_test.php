<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_ascent_j16m8_test extends \advanced_testcase {
    public function test_ascent_builder_exposes_background_and_card_media_controls(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('ascent');
        $fields = [];
        foreach ($schema['fields'] as $field) {
            $fields[(string)$field['name']] = $field;
        }

        foreach (['backgroundurl', 'cardimage1', 'cardimage2', 'cardimage3', 'cardimage4', 'cardimage5'] as $name) {
            self::assertArrayHasKey($name, $fields);
            self::assertSame('media', $fields[$name]['type']);
            self::assertTrue(CommerceShowroomBlockEditorRegistry::is_media_field('ascent', $name));
        }

        self::assertSame('range', $fields['backgroundopacity']['type']);
        self::assertSame('range', $fields['backgroundblur']['type']);
        self::assertSame('checkbox', $fields['backgrounddesktop']['type']);
        self::assertSame('checkbox', $fields['backgroundmobile']['type']);
    }

    public function test_ascent_template_and_css_keep_fallbacks_and_viewport_switches(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString('{{#hasascentbackground}}', $template);
        self::assertStringContainsString('{{#hasimage}}', $template);
        self::assertStringContainsString('{{^hasimage}}', $template);
        self::assertStringContainsString('has-background-desktop', $template);
        self::assertStringContainsString('has-background-mobile', $template);
        self::assertStringContainsString('/* J16M8 — configurable ascent background + per-card images. */', $css);
        self::assertStringContainsString('padding-top: 10.875rem;', $css);
    }
}
