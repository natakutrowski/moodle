<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_comparison_content_j16d1_test extends \advanced_testcase {
    public function test_comparison_builder_exposes_badge_feature_label_and_rows(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('comparison');
        $names = array_column($schema['fields'], 'name');

        self::assertContains('eyebrow', $names);
        self::assertContains('featurelabel', $names);
        self::assertContains('rows', $names);
    }

    public function test_template_supports_badge_and_textual_cells(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );
        self::assertIsString($template);
        self::assertStringContainsString('commerce-showroom-comparison__badge', $template);
        self::assertStringContainsString('fa-solid fa-compass', $template);
        self::assertStringContainsString('{{#pdf.hastext}}', $template);
        self::assertStringContainsString('{{#bundle.hastext}}', $template);
        self::assertStringContainsString('{{#course.hastext}}', $template);
    }

    public function test_presenter_parses_status_and_text_cells(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();

        $method = $reflection->getMethod('comparison_rows_from_config');
        $method->setAccessible(true);

        $rows = $method->invoke(
            $presenter,
            "Количество упражнений|—|более 4000|более 4000\nДоступ|навсегда|навсегда|навсегда"
        );

        self::assertCount(2, $rows);
        self::assertTrue($rows[0]['pdf']['notincluded']);
        self::assertSame('более 4000', $rows[0]['bundle']['text']);
        self::assertTrue($rows[1]['course']['hastext']);
    }
}
