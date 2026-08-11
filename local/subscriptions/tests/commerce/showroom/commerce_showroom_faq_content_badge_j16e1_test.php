<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_faq_content_badge_j16e1_test extends \advanced_testcase {
    public function test_faq_builder_exposes_badge_subtitle_and_items(): void {
        $schema = \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry::schema('faq');
        $names = array_column($schema['fields'], 'name');

        self::assertContains('eyebrow', $names);
        self::assertContains('title', $names);
        self::assertContains('text', $names);
        self::assertContains('items', $names);
        self::assertContains('singleopen', $names);
    }

    public function test_faq_template_uses_badge_with_question_icon(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString('commerce-showroom-faq__badge', $template);
        self::assertStringContainsString('fa-solid fa-circle-question', $template);
    }

    public function test_faq_defaults_support_nine_items(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockDefaultsCatalog.php'
        );

        self::assertStringContainsString('for ($i = 1; $i <= 9; $i++)', $source);
    }
}
