<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n113b_user360_commerce_access_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_factory_uses_new_commerce_access_renderer(): void {
        $factory = $this->file('classes/crm/user360/workspace/User360WorkspaceFactory.php');
        self::assertStringContainsString('User360CommerceAccessRenderer::render', $factory);
        self::assertStringNotContainsString('UserProfileRenderer::render_commercial_panel(', $factory);
    }

    public function test_commerce_section_is_focused_on_orders_and_legacy_subscriptions(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            'self::orders($profile->commercepurchases ?? [])',
            $renderer
        );
        self::assertStringContainsString(
            'self::legacy_subscriptions($profile->subscriptions ?? [])',
            $renderer
        );
        self::assertStringContainsString(
            "'id' => 'user360-commerce'",
            $renderer
        );

        self::assertStringNotContainsString(
            'render_digital_purchases_content',
            $renderer
        );
        self::assertStringNotContainsString(
            'render_courses_content',
            $renderer
        );
    }


    public function test_courses_are_not_duplicated_as_old_workspace_item(): void {
        $factory = $this->file('classes/crm/user360/workspace/User360WorkspaceFactory.php');
        $createpos = strpos($factory, 'public static function create(');
        self::assertNotFalse($createpos);
        $fragment = substr($factory, $createpos, 5000);
        self::assertStringNotContainsString('self::register_courses(', $fragment);
    }

    public function test_new_strings_exist_in_all_languages(): void {
        $keys = [
            'crm_user360_n113b_title',
            'crm_user360_n113b_orders',
            'crm_user360_n113b_subscriptions',
            'crm_user360_n113b_digital',
            'crm_user360_n113b_access',
            'crm_user360_n113b_guest_access_title',
        ];
        foreach (['en', 'fr', 'ru'] as $lang) {
            $strings = $this->file('lang/' . $lang . '/local_subscriptions.php');
            foreach ($keys as $key) {
                self::assertStringContainsString("\$string['" . $key . "']", $strings);
            }
        }
    }

    public function test_n113b_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString('$plugin->version = 2026081602;', $version);
    }
}
