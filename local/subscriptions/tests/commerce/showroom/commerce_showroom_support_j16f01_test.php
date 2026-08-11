<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_support_j16f01_test extends \advanced_testcase {
    public function test_support_builder_exposes_current_contact_fields(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('support');
        $fields = array_column($schema['fields'], null, 'name');
        foreach (['title', 'text', 'buttonlabel', 'telegramurl', 'whatsappurl'] as $name) {
            self::assertArrayHasKey($name, $fields);
        }
        self::assertArrayNotHasKey('coverurl', $fields);
        self::assertArrayNotHasKey('coveropacity', $fields);
    }

    public function test_support_template_uses_primary_support_and_optional_channels(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache');
        self::assertStringContainsString('{{#issupport}}', $template);
        self::assertStringContainsString('commerce-showroom-support__actions', $template);
        self::assertStringContainsString('{{supporturl}}', $template);
        self::assertStringContainsString('{{supporttelegramurl}}', $template);
        self::assertStringContainsString('{{supportwhatsappurl}}', $template);
    }
}
