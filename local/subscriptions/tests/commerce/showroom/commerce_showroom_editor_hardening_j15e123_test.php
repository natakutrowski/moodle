<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_editor_hardening_j15e123_test extends \advanced_testcase {
    public function test_empty_editorial_fields_are_valid_for_runtime_fallback(): void {
        $config = CommerceShowroomBlockEditorRegistry::normalise('support', [
            'translations' => [
                'fr' => ['title' => '', 'text' => '', 'buttonlabel' => ''],
                'en' => ['title' => '', 'text' => '', 'buttonlabel' => ''],
                'ru' => ['title' => '', 'text' => '', 'buttonlabel' => ''],
            ],
            'imageurl' => '',
        ]);

        $this->assertSame('', $config['translations']['fr']['title']);
        $this->assertSame('', $config['title']);
    }

    public function test_builder_stylesheet_contains_functional_language_panels(): void {
        $css = file_get_contents(
            __DIR__ . '/../../../styles/showroom_builder.css'
        );

        $this->assertIsString($css);
        $this->assertStringContainsString(
            '.commerce-showroom-language-panel {',
            $css
        );
        $this->assertStringContainsString(
            '.commerce-showroom-language-panel.is-active',
            $css
        );
    }

    public function test_advanced_json_is_authoritative_when_requested(): void {
        $ajax = file_get_contents(
            __DIR__ . '/../../../admin/commerce/showrooms/ajax.php'
        );
        $builder = file_get_contents(
            __DIR__ . '/../../../js/showroom_builder.js'
        );

        $this->assertIsString($ajax);
        $this->assertStringContainsString("optional_param('advancedjson'", $ajax);
        $this->assertStringContainsString('$savedconfig = $advancedjson', $ajax);

        $this->assertIsString($builder);
        $this->assertStringContainsString("advancedjson = '1'", $builder);
        $this->assertStringContainsString('applyJsonToFields', $builder);
        $this->assertStringContainsString('syncFieldsToJson', $builder);
    }
}
