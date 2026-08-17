<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_showrooms_n932_test extends advanced_testcase {
    public function test_information_screen_no_longer_exposes_seo_json(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        $advanced = strpos(
            $source,
            'commerce-showroom-information-advanced'
        );
        self::assertNotFalse($advanced);

        $information = substr(
            $source,
            $advanced,
            7000
        );

        self::assertStringNotContainsString(
            'commerce_showroom_config_settings_json',
            $information
        );
        self::assertStringNotContainsString(
            'commerce_showroom_config_titlekey_legacy',
            $information
        );
        self::assertStringContainsString(
            "'name' => 'titlekey'",
            $information
        );
        self::assertStringContainsString(
            "'name' => 'descriptionkey'",
            $information
        );
        self::assertStringContainsString(
            "'name' => 'settingsjson'",
            $information
        );
        self::assertStringContainsString(
            "'type' => 'hidden'",
            $information
        );
    }

    public function test_seo_screen_uses_language_tabs_and_character_counters(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringContainsString(
            'data-region\' => \'showroom-seo-locales',
            $source
        );
        self::assertStringContainsString(
            'data-seo-language',
            $source
        );
        self::assertStringContainsString(
            'data-counter-for',
            $source
        );
    }

    public function test_seo_screen_owns_legacy_fallback_keys_but_not_raw_json_editor(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringContainsString(
            'commerce_showroom_n932_technical',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_config_titlekey_legacy',
            $source
        );

        $technical = strpos(
            $source,
            'commerce-showroom-seo-advanced'
        );
        self::assertNotFalse($technical);
        $tail = substr($source, $technical, 9000);

        self::assertStringNotContainsString(
            "'name' => 'settingsjson'",
            $tail
        );
    }

    public function test_seo_image_is_compact_and_common_to_languages(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );
        $css = file_get_contents(
            $root . '/styles.css'
        );

        self::assertStringContainsString(
            'commerce-showroom-seo-image-card',
            $source
        );
        self::assertStringContainsString(
            'grid-template-columns: minmax(240px,360px) minmax(0,1fr)',
            $css
        );
    }

    public function test_n932_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
